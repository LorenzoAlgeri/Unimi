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

    $connection = open_pg_connection();
    $message = '';
    $error = '';

    // Ottieni i dati del cliente
    $query = "SELECT c.*, tf.saldopunti, tf.datarichiesta, n.indirizzo as negozio_tessera 
              FROM shoepal.cliente c 
              LEFT JOIN shoepal.tesserafedeltà tf ON c.codicefiscale = tf.codicefiscale 
              LEFT JOIN shoepal.negozio n ON tf.idnegozio = n.idnegozio 
              WHERE c.email = $1";
    $result = pg_query_params($connection, $query, array($_SESSION['user']));
    $cliente = pg_fetch_assoc($result);

    // Verifica tessere nello storico non ancora trasferite
    $storico_query = "SELECT st.idtessera, st.saldopunti, st.datarichiesta, st.idnegoziotrasferito, n.indirizzo as negozio_storico
                      FROM shoepal.StoricoTessere st
                      LEFT JOIN shoepal.negozio n ON st.idnegozio = n.idnegozio
                      WHERE st.codicefiscale = $1
                      ORDER BY st.saldopunti DESC";
    $storico_result = pg_query_params($connection, $storico_query, array($cliente['codicefiscale']));
    $tessere_storico = array();
    while ($tessera_storico = pg_fetch_assoc($storico_result)) {
        $tessere_storico[] = $tessera_storico;
    }

    // Ottieni lista negozi per il form tessera
    $negozi_query = "SELECT idnegozio, indirizzo FROM shoepal.negozio WHERE attivo = true ORDER BY indirizzo";
    $negozi_result = pg_query($connection, $negozi_query);
    $negozi = array();
    while ($negozio = pg_fetch_assoc($negozi_result)) {
        $negozi[] = $negozio;
    }

    // Funzione per calcolare sconti disponibili
    function calcolaScontiDisponibili($punti) {
        $sconti = array();
        
        if ($punti >= 100) {
            $sconti[] = array('punti' => 100, 'sconto' => '5%', 'descrizione' => '5% di sconto sul prossimo acquisto');
        }
        if ($punti >= 200) {
            $sconti[] = array('punti' => 200, 'sconto' => '15%', 'descrizione' => '15% di sconto sul prossimo acquisto');
        }
        if ($punti >= 300) {
            $sconti[] = array('punti' => 300, 'sconto' => '30%', 'descrizione' => '30% di sconto sul prossimo acquisto (max 100€)');
        }
        
        return $sconti;
    }

    // Gestione richiesta tessera fedeltà
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'richiedi_tessera') {
        // Verifica che l'utente non abbia già una tessera attiva
        $check_tessera_query = "SELECT idtessera FROM shoepal.tesserafedeltà WHERE codicefiscale = $1";
        $check_tessera_result = pg_query_params($connection, $check_tessera_query, array($cliente['codicefiscale']));
        
        if ($check_tessera_result && pg_num_rows($check_tessera_result) > 0) {
            $error = "Hai già una tessera fedeltà attiva.";
        } else {
            $idNegozio = (int)$_POST['idNegozio'];
            
            // Verifica che il negozio esista
            $check_query = "SELECT idnegozio FROM shoepal.negozio WHERE idnegozio = $1 AND attivo = true";
            $check_result = pg_query_params($connection, $check_query, array($idNegozio));
            
            if ($check_result && pg_num_rows($check_result) > 0) {
                // Inserisci tessera fedeltà (il trigger gestirà automaticamente il trasferimento punti se esiste tessera nello storico)
                $insert_query = "INSERT INTO shoepal.tesserafedeltà (codicefiscale, datarichiesta, idnegozio, saldopunti) 
                                VALUES ($1, CURRENT_DATE, $2, 0)";
                $insert_result = pg_query_params($connection, $insert_query, 
                                               array($cliente['codicefiscale'], $idNegozio));
            
            if ($insert_result) {
                $message = "Tessera fedeltà richiesta con successo!";
                // Se c'erano punti nello storico, informa l'utente
                if (!empty($tessere_storico) && $tessere_storico[0]['saldopunti'] > 0) {
                    $message .= " I tuoi " . $tessere_storico[0]['saldopunti'] . " punti precedenti sono stati trasferiti automaticamente!";
                }
                
                // Ricarica i dati del cliente per mostrare la nuova tessera
                $query = "SELECT c.*, tf.saldopunti, tf.datarichiesta, n.indirizzo as negozio_tessera 
                          FROM shoepal.cliente c 
                          LEFT JOIN shoepal.tesserafedeltà tf ON c.codicefiscale = tf.codicefiscale 
                          LEFT JOIN shoepal.negozio n ON tf.idnegozio = n.idnegozio 
                          WHERE c.email = $1";
                $result = pg_query_params($connection, $query, array($_SESSION['user']));
                $cliente = pg_fetch_assoc($result);
                
                // Aggiorna anche lo storico tessere
                $storico_result = pg_query_params($connection, $storico_query, array($cliente['codicefiscale']));
                $tessere_storico = array();
                while ($tessera_storico = pg_fetch_assoc($storico_result)) {
                    $tessere_storico[] = $tessera_storico;
                }
            } else {
                $error = "Errore durante la richiesta della tessera.";
            }
        } else {
            $error = "Negozio non valido.";
        }
        }
    }

    // Gestione ripristino tessera specifica
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'ripristina_tessera') {
        // Verifica che l'utente non abbia già una tessera attiva
        $check_tessera_query = "SELECT idtessera FROM shoepal.tesserafedeltà WHERE codicefiscale = $1";
        $check_tessera_result = pg_query_params($connection, $check_tessera_query, array($cliente['codicefiscale']));
        
        if ($check_tessera_result && pg_num_rows($check_tessera_result) > 0) {
            $error = "Hai già una tessera fedeltà attiva. Non puoi ripristinarne un'altra.";
        } else {
            $idTessera = (int)$_POST['idTessera'];
            $idNegozio = (int)$_POST['idNegozio'];
            
            // Verifica che il negozio esista
            $check_query = "SELECT idnegozio FROM shoepal.negozio WHERE idnegozio = $1 AND attivo = true";
            $check_result = pg_query_params($connection, $check_query, array($idNegozio));
        
        if ($check_result && pg_num_rows($check_result) > 0) {
            // Chiama la funzione per ripristinare la tessera
            $ripristina_query = "SELECT shoepal.ripristina_tessera($1, $2) as risultato";
            $ripristina_result = pg_query_params($connection, $ripristina_query, array($idTessera, $idNegozio));
            
            if ($ripristina_result) {
                $risultato = pg_fetch_assoc($ripristina_result);
                if ($risultato['risultato'] === 'Tessera ripristinata con successo') {
                    $message = "Tessera ripristinata con successo!";
                    
                    // Ricarica i dati del cliente
                    $query = "SELECT c.*, tf.saldopunti, tf.datarichiesta, n.indirizzo as negozio_tessera 
                              FROM shoepal.cliente c 
                              LEFT JOIN shoepal.tesserafedeltà tf ON c.codicefiscale = tf.codicefiscale 
                              LEFT JOIN shoepal.negozio n ON tf.idnegozio = n.idnegozio 
                              WHERE c.email = $1";
                    $result = pg_query_params($connection, $query, array($_SESSION['user']));
                    $cliente = pg_fetch_assoc($result);
                    
                    // Aggiorna lo storico tessere
                    $storico_result = pg_query_params($connection, $storico_query, array($cliente['codicefiscale']));
                    $tessere_storico = array();
                    while ($tessera_storico = pg_fetch_assoc($storico_result)) {
                        $tessere_storico[] = $tessera_storico;
                    }
                } else {
                    $error = $risultato['risultato'];
                }
            } else {
                $error = "Errore durante il ripristino della tessera.";
            }
        } else {
            $error = "Negozio non valido.";
        }
        }
    }

    // Gestione cambio password
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error = "Le nuove password non coincidono.";
        } else {
            // Verifica password attuale
            $query = "SELECT passwordhash FROM shoepal.utente WHERE email = $1";
            $result = pg_query_params($connection, $query, array($_SESSION['user']));
            $user = pg_fetch_assoc($result);

            if (password_verify($current_password, $user['passwordhash'])) {
                // Aggiorna password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE shoepal.utente SET passwordhash = $1 WHERE email = $2";
                $update_result = pg_query_params($connection, $update_query, array($new_hash, $_SESSION['user']));

                if ($update_result) {
                    $message = "Password cambiata con successo!";
                } else {
                    $error = "Errore durante il cambio password.";
                }
            } else {
                $error = "Password attuale non corretta.";
            }
        }
    }

    close_pg_connection($connection);
?>
<!doctype html>
<html lang="it">
<head>
    <title>Profilo</title>
    <?php include_once('lib/header.php'); ?>
    <link href="css/shoepal.css" rel="stylesheet">
</head>
<body>
    
    <?php include_once('lib/cliente_navigation.php'); ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4"><i class="fas fa-user-circle me-2"></i>Il mio profilo</h2>

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

                <div class="row">
                    <!-- Colonna sinistra: Informazioni personali e Cambia password -->
                    <div class="col-md-6">
                        <!-- Informazioni personali -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-user me-2"></i>Informazioni personali</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Nome:</strong> <?php echo htmlspecialchars($cliente['nome']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($cliente['email']); ?></p>
                                <p><strong>Codice Fiscale:</strong> <?php echo htmlspecialchars($cliente['codicefiscale']); ?></p>
                            </div>
                        </div>

                        <!-- Cambia password -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-key me-2"></i>Cambia password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Password attuale</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nuova password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Conferma nuova password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Cambia password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Colonna destra: Tessera fedeltà -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-star me-2"></i>Tessera fedeltà</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($cliente['saldopunti'] !== null): ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Punti disponibili:</strong> 
                                                <span class="badge bg-primary fs-6"><?php echo $cliente['saldopunti']; ?></span>
                                            </p>
                                            <p><strong>Data richiesta:</strong> <?php echo date('d/m/Y', strtotime($cliente['datarichiesta'])); ?></p>
                                            <p><strong>Negozio di riferimento:</strong> 
                                                <?php 
                                                $indirizzo_parti = explode(',', $cliente['negozio_tessera']);
                                                $citta = trim(end($indirizzo_parti));
                                                echo get_negozio_display_name($citta);
                                                ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <?php 
                                            $sconti_disponibili = calcolaScontiDisponibili($cliente['saldopunti']);
                                            if (!empty($sconti_disponibili)): 
                                            ?>
                                                <h6 class="text-success">Sconti disponibili:</h6>
                                                <div class="list-group list-group-flush">
                                                    <?php foreach ($sconti_disponibili as $sconto): ?>
                                                        <div class="list-group-item p-2 border-0">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted"><?php echo $sconto['punti']; ?> punti</small>
                                                                <span class="badge bg-success"><?php echo $sconto['sconto']; ?></span>
                                                            </div>
                                                            <small class="text-muted"><?php echo $sconto['descrizione']; ?></small>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted"><small>Accumula almeno 100 punti per ottenere il primo sconto!</small></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center">
                                        <p class="text-muted mb-3">Non hai una tessera fedeltà attiva.</p>
                                        
                                        <?php if (!empty($tessere_storico)): ?>
                                            <!-- Ha tessere nello storico -->
                                            <div class="alert alert-info mb-4">
                                                <h6><i class="fas fa-history me-2"></i>Tessere precedenti trovate!</h6>
                                                <p class="mb-2">Hai delle tessere fedeltà da negozi precedenti. Puoi:</p>
                                                <ul class="text-start mb-0">
                                                    <li><strong>Creare una nuova tessera</strong>: I punti della tessera con più punti verranno trasferiti automaticamente</li>
                                                    <li><strong>Ripristinare una tessera specifica</strong>: Riattiva una tessera specifica mantenendo tutti i punti</li>
                                                </ul>
                                            </div>
                                            
                                            <!-- Opzione 1: Nuova tessera (trasferimento automatico) -->
                                            <div class="card mb-3">
                                                <div class="card-header">
                                                    <h6 class="mb-0"><i class="fas fa-plus me-2"></i>Crea nuova tessera</h6>
                                                </div>
                                                <div class="card-body">
                                                    <p class="text-muted mb-3">
                                                        <small>Verrà trasferita automaticamente la tessera con più punti (<?php echo $tessere_storico[0]['saldopunti']; ?> punti)</small>
                                                    </p>
                                                    <form method="POST" class="mb-2">
                                                        <input type="hidden" name="action" value="richiedi_tessera">
                                                        <div class="mb-3">
                                                            <select class="form-select" name="idNegozio" required>
                                                                <option value="">Seleziona negozio</option>
                                                                <?php foreach ($negozi as $negozio): ?>
                                                                    <?php 
                                                                    $indirizzo_parti = explode(',', $negozio['indirizzo']);
                                                                    $citta = trim(end($indirizzo_parti));
                                                                    $nome_negozio = get_negozio_display_name($citta);
                                                                    ?>
                                                                    <option value="<?php echo $negozio['idnegozio']; ?>">
                                                                        <?php echo htmlspecialchars($nome_negozio . ' - ' . $negozio['indirizzo']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-plus me-1"></i>Crea nuova tessera
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            
                                            <!-- Opzione 2: Ripristina tessera specifica -->
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="mb-0"><i class="fas fa-undo me-2"></i>Ripristina tessera specifica</h6>
                                                </div>
                                                <div class="card-body">
                                                    <?php foreach ($tessere_storico as $tessera): ?>
                                                        <div class="card mb-2">
                                                            <div class="card-body p-3">
                                                                <div class="row align-items-center">
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1">
                                                                            <strong>Punti:</strong> 
                                                                            <?php if ($tessera['saldopunti'] > 0): ?>
                                                                                <span class="badge bg-primary"><?php echo $tessera['saldopunti']; ?></span>
                                                                            <?php else: ?>
                                                                                <span class="badge bg-secondary">0 (già utilizzati)</span>
                                                                            <?php endif; ?>
                                                                        </p>
                                                                        <p class="mb-1">
                                                                            <strong>Data:</strong> 
                                                                            <?php echo date('d/m/Y', strtotime($tessera['datarichiesta'])); ?>
                                                                        </p>
                                                                        <p class="mb-0">
                                                                            <strong>Negozio:</strong> 
                                                                            <?php 
                                                                            if ($tessera['negozio_storico']) {
                                                                                $indirizzo_parti = explode(',', $tessera['negozio_storico']);
                                                                                $citta = trim(end($indirizzo_parti));
                                                                                echo get_negozio_display_name($citta);
                                                                            } else {
                                                                                echo "Negozio non più attivo";
                                                                            }
                                                                            ?>
                                                                        </p>
                                                                        <?php if ($tessera['idnegoziotrasferito']): ?>
                                                                            <p class="mb-0">
                                                                                <small class="text-muted">
                                                                                    <i class="fas fa-check me-1"></i>Punti già trasferiti
                                                                                </small>
                                                                            </p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <?php if ($tessera['saldopunti'] > 0 && !$tessera['idnegoziotrasferito']): ?>
                                                                            <form method="POST" class="d-inline">
                                                                                <input type="hidden" name="action" value="ripristina_tessera">
                                                                                <input type="hidden" name="idTessera" value="<?php echo $tessera['idtessera']; ?>">
                                                                                <div class="mb-2">
                                                                                    <select class="form-select form-select-sm" name="idNegozio" required>
                                                                                        <option value="">Nuovo negozio</option>
                                                                                        <?php foreach ($negozi as $negozio): ?>
                                                                                            <?php 
                                                                                            $indirizzo_parti = explode(',', $negozio['indirizzo']);
                                                                                            $citta = trim(end($indirizzo_parti));
                                                                                            $nome_negozio = get_negozio_display_name($citta);
                                                                                            ?>
                                                                                            <option value="<?php echo $negozio['idnegozio']; ?>">
                                                                                                <?php echo htmlspecialchars($nome_negozio); ?>
                                                                                            </option>
                                                                                        <?php endforeach; ?>
                                                                                    </select>
                                                                                </div>
                                                                                <button type="submit" class="btn btn-outline-primary btn-sm">
                                                                                    <i class="fas fa-undo me-1"></i>Ripristina
                                                                                </button>
                                                                            </form>
                                                                        <?php else: ?>
                                                                            <div class="text-muted">
                                                                                <small>
                                                                                    <?php if ($tessera['idnegoziotrasferito']): ?>
                                                                                        <i class="fas fa-check me-1"></i>Punti già utilizzati
                                                                                    <?php else: ?>
                                                                                        <i class="fas fa-ban me-1"></i>Nessun punto disponibile
                                                                                    <?php endif; ?>
                                                                                </small>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            
                                        <?php else: ?>
                                            <!-- Prima tessera -->
                                            <p class="text-muted mb-4"><small>Richiedi la tua prima tessera per accumulare punti e ottenere sconti esclusivi!</small></p>
                                            
                                            <form method="POST" class="mb-3">
                                                <input type="hidden" name="action" value="richiedi_tessera">
                                                <div class="mb-3">
                                                    <label for="idNegozio" class="form-label">Scegli il tuo negozio di riferimento:</label>
                                                    <select class="form-select" id="idNegozio" name="idNegozio" required>
                                                        <option value="">Seleziona un negozio</option>
                                                        <?php foreach ($negozi as $negozio): ?>
                                                            <?php 
                                                            $indirizzo_parti = explode(',', $negozio['indirizzo']);
                                                            $citta = trim(end($indirizzo_parti));
                                                            $nome_negozio = get_negozio_display_name($citta);
                                                            ?>
                                                            <option value="<?php echo $negozio['idnegozio']; ?>">
                                                                <?php echo htmlspecialchars($nome_negozio . ' - ' . $negozio['indirizzo']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-star me-1"></i>Richiedi tessera fedeltà
                                                </button>
                                            </form>
                                            
                                            <div class="text-muted">
                                                <small>
                                                    <strong>Vantaggi della tessera:</strong><br>
                                                    • 1 punto ogni 1€ di spesa<br>
                                                    • 5% di sconto a partire da 100 punti<br>
                                                    • 15% di sconto a partire da 200 punti<br>
                                                    • 30% di sconto a partire da 300 punti (max 100€)
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Azioni rapide -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-lightning-bolt me-2"></i>Azioni rapide</h5>
                            </div>
                            <div class="card-body">
                                <a href="ordini.php" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-shopping-bag me-1"></i>I miei ordini
                                </a>
                                <a href="shop.php" class="btn btn-outline-success">
                                    <i class="fas fa-shopping-cart me-1"></i>Continua a comprare
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once('lib/footer.php'); ?>

</body>
</html>
