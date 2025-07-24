<?php
    ini_set("display_errors", "On");
    ini_set("error_reporting", E_ALL);
    include_once('lib/functions.php');

    session_start();

    // Gestione logout
    if (isset($_GET['log']) && $_GET['log'] == 'del') {
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // Include il controllo di autenticazione modulare
    include_once('lib/shop_components.php');
    shop_check_authentication();

    // Inizializza carrello se non esiste
    if (!isset($_SESSION['carrello'])) {
        $_SESSION['carrello'] = array();
    }

    $message = '';
    $error = '';

    // Gestione operazioni carrello via GET/POST
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'rimuovi':
                if (isset($_GET['chiave']) && isset($_SESSION['carrello'][$_GET['chiave']])) {
                    unset($_SESSION['carrello'][$_GET['chiave']]);
                    $message = 'Prodotto rimosso dal carrello';
                }
                break;
                
            case 'svuota':
                $_SESSION['carrello'] = array();
                $message = 'Carrello svuotato';
                break;
        }
    }

    // Gestione aggiornamento quantità via POST
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'aggiorna_quantita') {
        $chiave = $_POST['chiave'];
        $quantita = (int)$_POST['quantita'];
        
        if (isset($_SESSION['carrello'][$chiave])) {
            if ($quantita <= 0) {
                unset($_SESSION['carrello'][$chiave]);
                $message = 'Prodotto rimosso dal carrello';
            } else {
                // Verifica disponibilità nel database
                $item = $_SESSION['carrello'][$chiave];
                $connection = open_pg_connection();
                $query = "SELECT quantità FROM shoepal.disponibilità 
                          WHERE idprodotto = $1 AND idnegozio = $2 AND taglia = $3";
                $result = pg_query_params($connection, $query, 
                                        array($item['idProdotto'], $item['idNegozio'], $item['taglia']));
                
                if ($result && pg_num_rows($result) > 0) {
                    $disponibile = pg_fetch_assoc($result);
                    if ($disponibile['quantità'] >= $quantita) {
                        $_SESSION['carrello'][$chiave]['quantita'] = $quantita;
                        $message = 'Quantità aggiornata';
                    } else {
                        $error = 'Quantità non disponibile';
                    }
                } else {
                    $error = 'Prodotto non più disponibile';
                }
                close_pg_connection($connection);
            }
        }
    }

    // Gestione checkout
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'checkout') {
        if (empty($_SESSION['carrello'])) {
            $error = 'Il carrello è vuoto';
        } else {
            // Processa l'ordine
            $connection = open_pg_connection();
            
            try {
                pg_query($connection, "BEGIN");
                
                // Ottieni dati cliente
                $query = "SELECT codicefiscale FROM shoepal.cliente WHERE email = $1";
                $result = pg_query_params($connection, $query, array($_SESSION['user']));
                $cliente = pg_fetch_assoc($result);
                
                // Gestione sconto selezionato
                $sconto_percentuale = 0;
                if (isset($_POST['sconto_selezionato']) && !empty($_POST['sconto_selezionato'])) {
                    $sconto_percentuale = (int)$_POST['sconto_selezionato'];
                    
                    // Verifica che il cliente abbia abbastanza punti per questo sconto
                    $query_punti = "SELECT saldopunti FROM shoepal.tesserafedeltà WHERE codicefiscale = $1";
                    $result_punti = pg_query_params($connection, $query_punti, array($cliente['codicefiscale']));
                    
                    if ($result_punti && pg_num_rows($result_punti) > 0) {
                        $row_punti = pg_fetch_assoc($result_punti);
                        $punti_attuali = $row_punti['saldopunti'];
                        
                        $punti_necessari = 0;
                        if ($sconto_percentuale == 5) $punti_necessari = 100;
                        elseif ($sconto_percentuale == 15) $punti_necessari = 200;
                        elseif ($sconto_percentuale == 30) $punti_necessari = 300;
                        
                        if ($punti_attuali < $punti_necessari) {
                            throw new Exception('Punti insufficienti per applicare lo sconto selezionato');
                        }
                    } else {
                        throw new Exception('Tessera fedeltà non trovata');
                    }
                }
                
                // Raggruppa prodotti per negozio
                $negozi = array();
                $totale_carrello_checkout = 0;
                foreach ($_SESSION['carrello'] as $item) {
                    $negozi[$item['idNegozio']][] = $item;
                    $totale_carrello_checkout += $item['prezzo'] * $item['quantita'];
                }
                
                // Calcola sconto complessivo sul totale del carrello (una sola volta)
                $sconto_euro_totale = 0;
                if ($sconto_percentuale > 0) {
                    $punti_necessari = 0;
                    if ($sconto_percentuale == 5) $punti_necessari = 100;
                    elseif ($sconto_percentuale == 15) $punti_necessari = 200;
                    elseif ($sconto_percentuale == 30) $punti_necessari = 300;
                    
                    // Usa la funzione DB per calcolare lo sconto sul totale complessivo
                    $query_sconto = "SELECT shoepal.calcola_sconto($1, $2) as sconto";
                    $result_sconto = pg_query_params($connection, $query_sconto, [$punti_necessari, $totale_carrello_checkout]);
                    
                    if ($result_sconto) {
                        $row_sconto = pg_fetch_assoc($result_sconto);
                        $sconto_euro_totale = $row_sconto['sconto'];
                    }
                }
                
                // Crea una fattura per ogni negozio
                foreach ($negozi as $idNegozio => $prodotti) {
                    $totale_originale_negozio = 0;
                    
                    // Calcola totale originale per questo negozio
                    foreach ($prodotti as $prodotto) {
                        $totale_originale_negozio += $prodotto['prezzo'] * $prodotto['quantita'];
                    }
                    
                    // Calcola lo sconto proporzionale per questo negozio
                    $sconto_euro_negozio = 0;
                    if ($sconto_euro_totale > 0 && $totale_carrello_checkout > 0) {
                        $proporzione = $totale_originale_negozio / $totale_carrello_checkout;
                        $sconto_euro_negozio = $sconto_euro_totale * $proporzione;
                    }
                    
                    $totale_pagato_negozio = $totale_originale_negozio - $sconto_euro_negozio;
                    
                    // Calcola punti sul totale effettivamente pagato (1 punto ogni 1 euro pagato)
                    $punti = floor($totale_pagato_negozio / 1);
                    
                    // Inserisci fattura con sconto (se applicato)
                    $fattura_query = "INSERT INTO shoepal.fattura (codicefiscale, idnegozio, dataacquisto, puntiaccumulati, totaleoriginale, totalepagato, scontopercentuale) 
                                     VALUES ($1, $2, CURRENT_DATE, $3, $4, $5, $6) RETURNING idfattura";
                    $fattura_params = array($cliente['codicefiscale'], $idNegozio, $punti, $totale_originale_negozio, $totale_pagato_negozio, $sconto_percentuale > 0 ? $sconto_percentuale : null);
                    $fattura_result = pg_query_params($connection, $fattura_query, $fattura_params);
                    $fattura = pg_fetch_assoc($fattura_result);
                    $idFattura = $fattura['idfattura'];
                    
                    // Inserisci dettagli fattura e aggiorna disponibilità
                    foreach ($prodotti as $prodotto) {
                        // Inserisci dettaglio
                        $dettaglio_query = "INSERT INTO shoepal.fatturadettagli (idfattura, idprodotto, taglia, quantità, prezzounitario) 
                                           VALUES ($1, $2, $3, $4, $5)";
                        pg_query_params($connection, $dettaglio_query, 
                                      array($idFattura, $prodotto['idProdotto'], $prodotto['taglia'], $prodotto['quantita'], $prodotto['prezzo']));
                        
                        // Aggiorna disponibilità
                        $update_query = "UPDATE shoepal.disponibilità 
                                        SET quantità = quantità - $1 
                                        WHERE idprodotto = $2 AND idnegozio = $3 AND taglia = $4";
                        pg_query_params($connection, $update_query, 
                                      array($prodotto['quantita'], $prodotto['idProdotto'], $idNegozio, $prodotto['taglia']));
                    }
                    
                    // I punti tessera vengono aggiornati automaticamente dal trigger trg_aggiorna_saldo_punti
                    // I punti per lo sconto vengono detratti automaticamente dal trigger trg_decurta_punti_per_sconto
                }
                
                pg_query($connection, "COMMIT");
                
                // Svuota carrello
                $_SESSION['carrello'] = array();
                
                $message = 'Ordine completato con successo!';
                if ($sconto_percentuale > 0) {
                    $message .= " È stato applicato uno sconto del $sconto_percentuale% (€" . number_format($sconto_euro_totale, 2) . ") su un totale di €" . number_format($totale_carrello_checkout, 2) . ".";
                }
                
            } catch (Exception $e) {
                pg_query($connection, "ROLLBACK");
                $error = 'Errore durante il completamento dell\'ordine: ' . $e->getMessage();
            }
            
            close_pg_connection($connection);
        }
    }

    // Calcola totale carrello
    $totale_carrello = 0;
    foreach ($_SESSION['carrello'] as $item) {
        $totale_carrello += $item['prezzo'] * $item['quantita'];
    }

    // Ottieni punti del cliente per calcolare sconti disponibili
    $punti_cliente = 0;
    $sconti_disponibili = array();
    $sconto_preview = 0; // Per mostrare l'anteprima dello sconto selezionato
    
    // Gestione preview sconto (se selezionato via POST senza checkout)
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'preview_sconto') {
        $sconto_preview = isset($_POST['sconto_selezionato']) ? (int)$_POST['sconto_selezionato'] : 0;
    }
    
    if (!empty($_SESSION['carrello'])) {
        $connection = open_pg_connection();
        $query = "SELECT tf.saldopunti 
                  FROM shoepal.cliente c 
                  JOIN shoepal.tesserafedeltà tf ON c.codicefiscale = tf.codicefiscale 
                  WHERE c.email = $1";
        $result = pg_query_params($connection, $query, array($_SESSION['user']));
        
        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            $punti_cliente = $row['saldopunti'];
            
            // Calcola sconti disponibili basati sui punti usando la funzione DB
            if ($punti_cliente >= 100) {
                $query_sconto = "SELECT shoepal.calcola_sconto($1, $2) as sconto";
                $result_sconto = pg_query_params($connection, $query_sconto, [100, $totale_carrello]);
                $sconto_5_euro = $result_sconto ? pg_fetch_assoc($result_sconto)['sconto'] : 0;
                
                $sconti_disponibili[] = array(
                    'punti' => 100, 
                    'percentuale' => 5, 
                    'descrizione' => '5% di sconto (costa 100 punti)',
                    'sconto_euro' => $sconto_5_euro
                );
            }
            if ($punti_cliente >= 200) {
                $query_sconto = "SELECT shoepal.calcola_sconto($1, $2) as sconto";
                $result_sconto = pg_query_params($connection, $query_sconto, [200, $totale_carrello]);
                $sconto_15_euro = $result_sconto ? pg_fetch_assoc($result_sconto)['sconto'] : 0;
                
                $sconti_disponibili[] = array(
                    'punti' => 200, 
                    'percentuale' => 15, 
                    'descrizione' => '15% di sconto (costa 200 punti)',
                    'sconto_euro' => $sconto_15_euro
                );
            }
            if ($punti_cliente >= 300) {
                $query_sconto = "SELECT shoepal.calcola_sconto($1, $2) as sconto";
                $result_sconto = pg_query_params($connection, $query_sconto, [300, $totale_carrello]);
                $sconto_30_euro = $result_sconto ? pg_fetch_assoc($result_sconto)['sconto'] : 0;
                $sconti_disponibili[] = array(
                    'punti' => 300, 
                    'percentuale' => 30, 
                    'descrizione' => '30% di sconto (costa 300 punti, max 100€)',
                    'sconto_euro' => $sconto_30_euro
                );
            }
        }
        close_pg_connection($connection);
    }
    
    // Calcola totale finale con eventuale sconto preview usando la funzione DB
    $totale_finale = $totale_carrello;
    $sconto_euro = 0;
    $punti_da_spendere = 0;
    
    if ($sconto_preview > 0) {
        // Determina punti da spendere
        if ($sconto_preview == 5) $punti_da_spendere = 100;
        elseif ($sconto_preview == 15) $punti_da_spendere = 200;
        elseif ($sconto_preview == 30) $punti_da_spendere = 300;
        
        // Usa la funzione DB per calcolare lo sconto
        $connection = open_pg_connection();
        $query = "SELECT shoepal.calcola_sconto($1, $2) as sconto";
        $result = pg_query_params($connection, $query, [$punti_da_spendere, $totale_carrello]);
        
        if ($result) {
            $row = pg_fetch_assoc($result);
            $sconto_euro = $row['sconto'];
        }
        close_pg_connection($connection);
        
        $totale_finale = $totale_carrello - $sconto_euro;
    }
?>
<!doctype html>
<html lang="it">
<head>
    <title>Carrello</title>
    <?php include_once('lib/header.php'); ?>
    <link href="css/shoepal.css" rel="stylesheet">
</head>
<body>
    
    <?php include_once('lib/cliente_navigation.php'); ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>Il mio carrello</h2>

                <!-- Messaggi -->
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['carrello'])): ?>
                    <?php 
                    // Ottieni informazioni sul negozio del carrello
                    $primo_prodotto = reset($_SESSION['carrello']);
                    $idNegozio = $primo_prodotto['idNegozio'];
                    
                    $connection = open_pg_connection();
                    $query_negozio = "SELECT indirizzo FROM shoepal.negozio WHERE idnegozio = $1";
                    $result_negozio = pg_query_params($connection, $query_negozio, array($idNegozio));
                    $negozio_info = pg_fetch_assoc($result_negozio);
                    
                    $indirizzo_parti = explode(',', $negozio_info['indirizzo']);
                    $citta = trim(end($indirizzo_parti));
                    $nome_negozio = get_negozio_display_name($citta);
                    close_pg_connection($connection);
                    ?>
                    
                    <!-- Info negozio -->
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-store me-2"></i>
                        <strong>Negozio:</strong> <?php echo htmlspecialchars($nome_negozio); ?>
                        <span class="text-muted">- Tutti i prodotti nel carrello provengono da questo negozio</span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Prodotti nel carrello -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Prodotti nel carrello</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($_SESSION['carrello'] as $chiave => $item): ?>
                                        <div class="row align-items-center border-bottom py-3" id="item-<?php echo $chiave; ?>">
                                            <div class="col-md-2">
                                                <img src="assets/prodotti/<?php echo $item['idProdotto']; ?>.webp" 
                                                     alt="<?php echo htmlspecialchars($item['nome']); ?>"
                                                     class="img-fluid rounded"
                                                     style="max-height: 80px;">
                                            </div>
                                            <div class="col-md-4">
                                                <h6><?php echo htmlspecialchars($item['nome']); ?></h6>
                                                <small class="text-muted">Taglia: <?php echo $item['taglia']; ?></small>
                                            </div>
                                            <div class="col-md-2">
                                                <span class="text-success">€ <?php echo number_format($item['prezzo'], 2); ?></span>
                                            </div>
                                            <div class="col-md-2">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="aggiorna_quantita">
                                                    <input type="hidden" name="chiave" value="<?php echo $chiave; ?>">
                                                    <div class="input-group input-group-sm">
                                                        <button class="btn btn-outline-secondary" type="submit" name="quantita" value="<?php echo $item['quantita'] - 1; ?>">-</button>
                                                        <input type="number" class="form-control text-center" 
                                                               value="<?php echo $item['quantita']; ?>" min="1" 
                                                               name="quantita">
                                                        <button class="btn btn-outline-secondary" type="submit" name="quantita" value="<?php echo $item['quantita'] + 1; ?>">+</button>
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="col-md-2 text-end">
                                                <strong>€ <?php echo number_format($item['prezzo'] * $item['quantita'], 2); ?></strong>
                                                <br>
                                                <a href="?action=rimuovi&chiave=<?php echo urlencode($chiave); ?>" 
                                                   class="btn btn-sm btn-outline-danger mt-1"
                                                   onclick="return confirm('Sei sicuro di voler rimuovere questo prodotto?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Riepilogo ordine -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Riepilogo ordine</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotale:</span>
                                        <span id="subtotale">€ <?php echo number_format($totale_carrello, 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Spedizione:</span>
                                        <span class="text-success">Gratis</span>
                                    </div>
                                    
                                    <!-- Sezione sconti disponibili -->
                                    <?php if (!empty($sconti_disponibili)): ?>
                                        <div class="mb-3">
                                            <hr>
                                            <h6 class="text-primary mb-3">
                                                <i class="fas fa-star me-1"></i>
                                                Usa i tuoi punti (Hai <?php echo $punti_cliente; ?> punti)
                                            </h6>
                                            
                                            <form method="POST" id="sconto-form">
                                                <input type="hidden" name="action" value="preview_sconto">
                                                
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="radio" name="sconto_selezionato" 
                                                           id="nessuno_sconto" value="0" 
                                                           <?php echo ($sconto_preview == 0) ? 'checked' : ''; ?>
                                                           onchange="this.form.submit()">
                                                    <label class="form-check-label" for="nessuno_sconto">
                                                        <strong>Nessuno sconto - Accumula punti</strong>
                                                    </label>
                                                </div>
                                                
                                                <?php foreach ($sconti_disponibili as $sconto): ?>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="radio" name="sconto_selezionato" 
                                                               id="sconto_<?php echo $sconto['percentuale']; ?>" 
                                                               value="<?php echo $sconto['percentuale']; ?>"
                                                               <?php echo ($sconto_preview == $sconto['percentuale']) ? 'checked' : ''; ?>
                                                               onchange="this.form.submit()">
                                                        <label class="form-check-label" for="sconto_<?php echo $sconto['percentuale']; ?>">
                                                            <strong><?php echo $sconto['descrizione']; ?></strong>
                                                            <br><small class="text-muted">
                                                                Sconto: €<?php echo number_format($sconto['sconto_euro'], 2); ?>
                                                            </small>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                                
                                                <noscript>
                                                    <button type="submit" class="btn btn-sm btn-outline-primary mt-2">
                                                        Applica sconto
                                                    </button>
                                                </noscript>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <hr>
                                    
                                    <!-- Visualizzazione sconto applicato -->
                                    <?php if ($sconto_preview > 0 && $sconto_euro > 0): ?>
                                        <div class="d-flex justify-content-between mb-2 text-success">
                                            <span>Sconto (<?php echo $sconto_preview; ?>%):</span>
                                            <span>-€ <?php echo number_format($sconto_euro, 2); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between mb-3">
                                        <strong>Totale:</strong>
                                        <strong>€ <?php echo number_format($totale_finale, 2); ?></strong>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-info">
                                            <i class="fas fa-star me-1"></i>
                                            <?php if ($sconto_preview > 0): ?>
                                                Spenderai <?php echo $punti_da_spendere; ?> punti e guadagnerai <?php echo floor($totale_finale / 1); ?> punti fedeltà
                                            <?php else: ?>
                                                Guadagnerai <?php echo floor($totale_carrello / 1); ?> punti fedeltà
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="action" value="checkout">
                                        <input type="hidden" name="sconto_selezionato" value="<?php echo $sconto_preview; ?>">
                                        <button type="submit" class="btn btn-success btn-lg w-100">
                                            <i class="fas fa-credit-card me-1"></i>Completa ordine
                                        </button>
                                    </form>
                                    
                                    <a href="?action=svuota" class="btn btn-outline-danger btn-sm w-100 mt-2"
                                       onclick="return confirm('Sei sicuro di voler svuotare tutto il carrello?')">
                                        <i class="fas fa-trash me-1"></i>Svuota carrello
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Il tuo carrello è vuoto</h4>
                        <p class="text-muted">Aggiungi alcuni prodotti per iniziare!</p>
                        <a href="shop.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag me-1"></i>Continua a comprare
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include_once('lib/footer.php'); ?>

</body>
</html>
