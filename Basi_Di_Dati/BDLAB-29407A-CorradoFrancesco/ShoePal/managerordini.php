<?php
    ini_set ("display_errors", "On");
	ini_set("error_reporting", E_ALL);
	include_once ('lib/functions.php');
    include_once ('lib/manager_components.php');

    session_start();

    if (!isset($_SESSION['user']) || !isset($_SESSION['ruolo']) || $_SESSION['ruolo'] != 'manager') {
        header("Location: login.php");
        exit();
    }

    $message = '';
    $error = '';
    $numProdotti = 1; // Default
    $currentStep = 'config'; // config, products, summary
    $previews = [];
    $prodottiSelezionati = [];
    $errori = []; // Inizializziamo sempre la variabile errori
    
    // Gestione riordino da pagina disponibilità
    $riordinoProdotto = null;
    if (isset($_GET['riordina']) && isset($_GET['taglia']) && isset($_GET['negozio'])) {
        $riordinoProdotto = [
            'idProdotto' => $_GET['riordina'],
            'taglia' => $_GET['taglia'],
            'idNegozio' => $_GET['negozio'],
            'nome' => $_GET['nome'] ?? 'Prodotto selezionato'
        ];
        // Preimposta la configurazione per un singolo prodotto
        $_SESSION['order_config'] = [
            'numProdotti' => 1,
            'idNegozio' => $riordinoProdotto['idNegozio']
        ];
        // Preimposta i dati del form
        $_SESSION['form_data'] = [
            1 => [
                'idProdotto' => $riordinoProdotto['idProdotto'],
                'quantita' => 10, // Quantità di default
                'taglia' => $riordinoProdotto['taglia']
            ]
        ];
        $currentStep = 'products';
        $numProdotti = 1;
        
        $message = "Prodotto preimpostato per riordino: " . htmlspecialchars($riordinoProdotto['nome']) . " (Taglia " . $riordinoProdotto['taglia'] . ")";
    }
    
    // Gestione form in base allo step
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action'])) {
            // Step 1: Configurazione numero prodotti e negozio
            if ($_POST['action'] == 'configura_ordine') {
                $numProdotti = intval($_POST['numProdotti'] ?? 1);
                $idNegozio = $_POST['idNegozio'] ?? null;
                
                if ($numProdotti > 0 && $numProdotti <= 10 && $idNegozio) {
                    $currentStep = 'products';
                    $_SESSION['order_config'] = [
                        'numProdotti' => $numProdotti,
                        'idNegozio' => $idNegozio
                    ];
                } else {
                    $error = "Configurazione non valida";
                }
            }
            
            // Step 2: Selezione prodotti
            elseif ($_POST['action'] == 'seleziona_prodotti') {
                if (isset($_SESSION['order_config'])) {
                    $config = $_SESSION['order_config'];
                    $numProdotti = $config['numProdotti'];
                    $idNegozio = $config['idNegozio'];
                    
                    $prodotti = [];
                    $errori = [];
                    $prodotti_visti = [];
                    $previews = [];
                    
                    // Mantieni i dati del form per ripopolare i campi
                    $formData = [];
                    
                    // Raccogli e valida tutti i prodotti
                    for ($i = 1; $i <= $numProdotti; $i++) {
                        $idProdotto = $_POST["idProdotto_$i"] ?? null;
                        $quantita = intval($_POST["quantita_$i"] ?? 0);
                        $taglia = $_POST["taglia_$i"] ?? null;
                        
                        // Salva i dati del form per ripopolare
                        $formData[$i] = [
                            'idProdotto' => $idProdotto,
                            'quantita' => $quantita,
                            'taglia' => $taglia
                        ];
                        
                        if ($idProdotto && $quantita > 0 && $taglia) {
                            // Genera SEMPRE l'anteprima per ogni prodotto compilato
                            try {
                                $preview = get_order_preview($idProdotto, $quantita, $taglia);
                                $previews[$i] = $preview;
                            } catch (Exception $e) {
                                $previews[$i] = ['available' => false, 'message' => 'Errore nella verifica: ' . $e->getMessage()];
                            }
                            
                            // CONTROLLO DUPLICATI RIGOROSO
                            $chiave_prodotto = $idProdotto . '_' . $taglia;
                            if (isset($prodotti_visti[$chiave_prodotto])) {
                                // Trova quale prodotto precedente aveva la stessa combinazione
                                $prodotto_precedente = 0;
                                for ($j = 1; $j < $i; $j++) {
                                    $prevId = $_POST["idProdotto_$j"] ?? null;
                                    $prevTaglia = $_POST["taglia_$j"] ?? null;
                                    if ($prevId == $idProdotto && $prevTaglia == $taglia) {
                                        $prodotto_precedente = $j;
                                        break;
                                    }
                                }
                                $errori[] = "DUPLICATO: Prodotto $i ha la stessa combinazione prodotto-taglia del prodotto $prodotto_precedente";
                                continue;
                            }
                            $prodotti_visti[$chiave_prodotto] = $i;
                            
                            if ($quantita > 100) {
                                $errori[] = "Prodotto $i: quantità troppo elevata (massimo 100)";
                                continue;
                            }
                            
                            // Valida disponibilità solo se l'anteprima è stata generata correttamente
                            if (!$previews[$i]['available']) {
                                $messaggioErrore = "Prodotto $i: non disponibile in quantità richiesta";
                                if (isset($previews[$i]['message']) && !empty($previews[$i]['message'])) {
                                    $messaggioErrore .= " - " . $previews[$i]['message'];
                                }
                                $errori[] = $messaggioErrore;
                                continue;
                            }
                            
                            $prodotti[] = [
                                'idProdotto' => $idProdotto,
                                'quantita' => $quantita,
                                'taglia' => $taglia,
                                'preview' => $previews[$i]
                            ];
                        }
                    }
                    
                    // Salva i dati del form e le preview nella sessione
                    $_SESSION['form_data'] = $formData;
                    $_SESSION['previews'] = $previews;
                    $_SESSION['errori'] = $errori; // Salva anche gli errori per mostrarli nel form
                    
                    if (empty($errori) && !empty($prodotti)) {
                        $_SESSION['order_products'] = $prodotti;
                        $currentStep = 'summary';
                        $prodottiSelezionati = $prodotti;
                    } else {
                        // Rimani nello step products ma mostra le anteprime
                        $currentStep = 'products';
                        $numProdotti = $config['numProdotti'];
                        if (empty($prodotti)) {
                            $error = "<strong>Nessun prodotto valido selezionato.</strong> Compila almeno un prodotto disponibile per procedere.";
                        }
                    }
                } else {
                    $error = "Sessione scaduta, ripartire dalla configurazione";
                    $currentStep = 'config';
                }
            }
            
            // Step 3: Conferma ordine
            elseif ($_POST['action'] == 'conferma_ordine') {
                if (isset($_SESSION['order_config']) && isset($_SESSION['order_products'])) {
                    $config = $_SESSION['order_config'];
                    $prodotti = $_SESSION['order_products'];
                    
                    try {
                        $result = ordina_prodotti_multipli_per_negozio($prodotti, $config['idNegozio']);
                        if ($result['success']) {
                            $message = $result['message'] . " (Costo totale: €" . number_format($result['costo_totale'] ?? 0, 2, ',', '.') . ")";
                            // Reset sessione
                            unset($_SESSION['order_config']);
                            unset($_SESSION['order_products']);
                            unset($_SESSION['form_data']);
                            unset($_SESSION['previews']);
                            unset($_SESSION['errori']);
                            $currentStep = 'config';
                        } else {
                            $error = "Errore nell'invio degli ordini: " . ($result['message'] ?? 'Errore sconosciuto');
                            $currentStep = 'summary';
                            $prodottiSelezionati = $prodotti;
                        }
                    } catch (Exception $e) {
                        $error = "Errore durante l'elaborazione dell'ordine: " . $e->getMessage();
                        $currentStep = 'summary';
                        $prodottiSelezionati = $prodotti;
                    }
                } else {
                    $error = "Sessione scaduta";
                    $currentStep = 'config';
                }
            }
            
            // Reset form
            elseif ($_POST['action'] == 'reset_form') {
                unset($_SESSION['order_config']);
                unset($_SESSION['order_products']);
                unset($_SESSION['form_data']);
                unset($_SESSION['previews']);
                unset($_SESSION['errori']);
                $currentStep = 'config';
            }
            
            // Torna al form prodotti mantenendo i dati
            elseif ($_POST['action'] == 'torna_a_prodotti') {
                if (isset($_SESSION['order_config'])) {
                    unset($_SESSION['order_products']); // Rimuovi solo i prodotti confermati
                    unset($_SESSION['errori']); // Rimuovi anche gli errori per un nuovo tentativo
                    $currentStep = 'products';
                    $numProdotti = $_SESSION['order_config']['numProdotti'];
                } else {
                    $currentStep = 'config';
                }
            }
        }
    }
    
    // Recupera configurazione dalla sessione se disponibile
    if (isset($_SESSION['order_config']) && $currentStep == 'config') {
        $currentStep = 'products';
        $numProdotti = $_SESSION['order_config']['numProdotti'];
    }
    
    if (isset($_SESSION['order_products']) && $currentStep == 'products') {
        $currentStep = 'summary';
        $prodottiSelezionati = $_SESSION['order_products'];
    }
    
    // Recupera i dati del form e le preview dalla sessione
    $formData = $_SESSION['form_data'] ?? [];
    $previews = $_SESSION['previews'] ?? [];
    $erroriSessione = $_SESSION['errori'] ?? [];
    
    // Se ci sono errori dalla sessione e non abbiamo errori attuali, usa quelli della sessione
    if (empty($errori) && !empty($erroriSessione)) {
        $errori = $erroriSessione;
    }

    // Gestione filtro fornitore
    $filtroFornitore = $_GET['fornitore'] ?? '';
    $ordini = get_ordini_raggruppati_per_ordine($filtroFornitore);
    $prodotti = get_all_prodotti_manager();
    $negoziAttivi = get_negozi_attivi();
    $fornitori = get_all_fornitori();
?>
<!doctype html>
<html lang="it">
<head>
    <title>Gestione Ordini - ShoePal</title>
    <?php include_once ('lib/header.php'); ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include_once ('lib/manager_navigation.php'); ?>
    
    <main class="flex-grow-1">
        <div class="container my-4">
        <h1 class="mb-4">Gestione Ordini Fornitori</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['riordina'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-arrow-left me-2"></i>
                <strong>Riordino veloce attivato!</strong> Il prodotto è stato preimpostato nel form sottostante.
                <a href="managerdisponibilita.php?negozio=<?php echo htmlspecialchars($_GET['negozio']); ?>" class="btn btn-sm btn-outline-primary ms-3">
                    <i class="fas fa-arrow-left me-1"></i>Torna alle Disponibilità
                </a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Form per ordine multiprodotto per negozio -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-store me-2"></i>Ordina Prodotti per Negozio
                </h5>
            </div>
            <div class="card-body">
                <?php if ($currentStep == 'config'): ?>
                    <!-- Step 1: Configurazione -->
                    <h6 class="mb-3">Step 1: Configurazione Ordine</h6>
                    <form method="POST">
                        <input type="hidden" name="action" value="configura_ordine">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="numProdotti" class="form-label">Numero di prodotti da ordinare *</label>
                                <select class="form-select" name="numProdotti" required>
                                    <?php for($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($numProdotti == $i) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> prodott<?php echo $i != 1 ? 'i' : 'o'; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="idNegozio" class="form-label">Negozio di Destinazione *</label>
                                <select class="form-select" name="idNegozio" required>
                                    <option value="">Seleziona negozio...</option>
                                    <?php foreach ($negoziAttivi as $negozio): ?>
                                        <option value="<?php echo $negozio['idnegozio']; ?>">
                                            <?php 
                                            $indirizzo = $negozio['indirizzo'];
                                            $parti = explode(',', $indirizzo);
                                            $citta = trim(end($parti));
                                            echo get_negozio_display_name($citta);
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-arrow-right me-2"></i>Continua alla Selezione Prodotti
                            </button>
                        </div>
                    </form>
                    
                <?php elseif ($currentStep == 'products'): ?>
                    <!-- Step 2: Selezione Prodotti -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Step 2: Selezione Prodotti (<?php echo $_SESSION['order_config']['numProdotti']; ?> prodotti)</h6>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="reset_form">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-undo me-1"></i>Ricomincia
                            </button>
                        </form>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Negozio destinazione:</strong> 
                        <?php 
                        $negozioSelezionato = array_filter($negoziAttivi, function($n) {
                            return $n['idnegozio'] == $_SESSION['order_config']['idNegozio'];
                        });
                        $negozioSelezionato = reset($negozioSelezionato);
                        if ($negozioSelezionato) {
                            $indirizzo = $negozioSelezionato['indirizzo'];
                            $parti = explode(',', $indirizzo);
                            $citta = trim(end($parti));
                            echo get_negozio_display_name($citta);
                        }
                        ?>
                    </div>
                    
                    <?php 
                    // Controllo se ci sono duplicati negli errori per mostrare un avviso speciale
                    $duplicatiPresenti = false;
                    if (!empty($errori)) {
                        foreach ($errori as $errore) {
                            if (strpos($errore, 'DUPLICATO') !== false) {
                                $duplicatiPresenti = true;
                                break;
                            }
                        }
                    }
                    ?>
                    
                    <?php if ($duplicatiPresenti): ?>
                        <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>ATTENZIONE: Prodotti Duplicati Rilevati!</h6>
                            <p class="mb-0">Non puoi ordinare lo stesso prodotto nella stessa taglia più volte nello stesso ordine. Ogni combinazione <strong>Prodotto + Taglia</strong> deve essere unica.</p>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="seleziona_prodotti">
                        
                        <?php for ($i = 1; $i <= $_SESSION['order_config']['numProdotti']; $i++): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Prodotto <?php echo $i; ?></h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <label for="idProdotto_<?php echo $i; ?>" class="form-label">Prodotto *</label>
                                            <select class="form-select" name="idProdotto_<?php echo $i; ?>" required>
                                                <option value="">Seleziona prodotto...</option>
                                                <?php foreach ($prodotti as $prodotto): ?>
                                                    <option value="<?php echo $prodotto['idprodotto']; ?>" 
                                                            <?php echo (isset($formData[$i]) && $formData[$i]['idProdotto'] == $prodotto['idprodotto']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($prodotto['nome'] . ' - ' . $prodotto['marca'] . ' (' . $prodotto['tipologia'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="taglia_<?php echo $i; ?>" class="form-label">Taglia *</label>
                                            <select class="form-select" name="taglia_<?php echo $i; ?>" required>
                                                <option value="">Seleziona...</option>
                                                <?php for($t = 37; $t <= 46; $t++): ?>
                                                    <option value="<?php echo $t; ?>" 
                                                            <?php echo (isset($formData[$i]) && $formData[$i]['taglia'] == $t) ? 'selected' : ''; ?>>
                                                        <?php echo $t; ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="quantita_<?php echo $i; ?>" class="form-label">Quantità *</label>
                                            <input type="number" class="form-control" name="quantita_<?php echo $i; ?>" 
                                                   required min="1" max="100" 
                                                   value="<?php echo isset($formData[$i]) ? $formData[$i]['quantita'] : 5; ?>">
                                        </div>
                                    </div>
                                    
                                    <?php if (isset($previews[$i])): ?>
                                        <?php $preview = $previews[$i]; ?>
                                        <div class="mt-3">
                                            <?php if ($preview['available']): ?>
                                                <div class="alert alert-success">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong><i class="fas fa-check-circle me-1"></i>Disponibile</strong><br>
                                                            <strong>Fornitore:</strong> <?php echo htmlspecialchars($preview['fornitore']); ?><br>
                                                            <strong>Prezzo unitario:</strong> €<?php echo $preview['prezzo_unitario']; ?><br>
                                                            <strong>Disponibilità:</strong> <?php echo $preview['disponibilita']; ?> paia<br>
                                                            <strong>Costo totale:</strong> <span class="text-success">€<?php echo number_format($preview['costo_totale'] ?? 0, 2, ',', '.'); ?></span>
                                                        </div>
                                                        <span class="badge bg-success fs-6">✓ OK</span>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-danger">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong><i class="fas fa-exclamation-triangle me-1"></i>Non Disponibile</strong><br>
                                                            <?php echo isset($preview['message']) ? htmlspecialchars($preview['message']) : 'Nessun fornitore disponibile per questa taglia e quantità'; ?>
                                                            
                                                            <?php if (isset($preview['max_disponibile']) && $preview['max_disponibile'] > 0): ?>
                                                                <br><br>
                                                                <div class="alert alert-info mt-2 mb-0">
                                                                    <strong><i class="fas fa-lightbulb me-1"></i>Suggerimento:</strong> 
                                                                    Modifica la quantità a massimo <strong><?php echo $preview['max_disponibile']; ?> paia</strong> per completare l'ordine.
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <span class="badge bg-danger fs-6">✗ NON OK</span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif (isset($formData[$i]) && $formData[$i]['idProdotto'] && $formData[$i]['taglia']): ?>
                                        <div class="mt-3">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <strong>Clicca "Verifica Disponibilità" per vedere fornitore e prezzi</strong>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                        
                        <?php 
                        // Calcola riepilogo parziale se ci sono preview disponibili
                        $prodottiValidi = [];
                        $totaleProvvisorio = 0;
                        foreach ($previews as $i => $preview) {
                            if ($preview['available'] && isset($formData[$i])) {
                                $prodottiValidi[] = [
                                    'indice' => $i,
                                    'preview' => $preview,
                                    'data' => $formData[$i]
                                ];
                                $totaleProvvisorio += $preview['costo_totale'];
                            }
                        }
                        ?>
                        
                        <?php if (!empty($prodottiValidi)): ?>
                            <div class="alert alert-info">
                                <h6><i class="fas fa-calculator me-2"></i>Riepilogo Parziale</h6>
                                <?php foreach ($prodottiValidi as $prodotto): ?>
                                    <?php 
                                    $prodottoInfo = array_filter($prodotti, function($p) use ($prodotto) {
                                        return $p['idprodotto'] == $prodotto['data']['idProdotto'];
                                    });
                                    $prodottoInfo = reset($prodottoInfo);
                                    ?>
                                    <div class="d-flex justify-content-between">
                                        <span><?php echo htmlspecialchars($prodottoInfo['nome']); ?> (Taglia <?php echo $prodotto['data']['taglia']; ?>) x<?php echo $prodotto['data']['quantita']; ?></span>
                                        <span class="fw-bold">€<?php echo number_format($prodotto['preview']['costo_totale'] ?? 0, 2, ',', '.'); ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Totale Parziale:</strong>
                                    <strong>€<?php echo number_format($totaleProvvisorio ?? 0, 2, ',', '.'); ?></strong>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-search me-2"></i>Verifica Disponibilità e Prezzi
                            </button>
                            <?php if (count($prodottiValidi) == $_SESSION['order_config']['numProdotti'] && empty($error)): ?>
                                <button type="submit" name="action" value="seleziona_prodotti" class="btn btn-primary btn-lg ms-2">
                                    <i class="fas fa-arrow-right me-2"></i>Procedi al Riepilogo
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                <?php elseif ($currentStep == 'summary'): ?>
                    <!-- Step 3: Riepilogo e Conferma -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Step 3: Riepilogo Ordine</h6>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="reset_form">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-undo me-1"></i>Ricomincia
                            </button>
                        </form>
                    </div>
                    
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Negozio destinazione:</strong> 
                        <?php 
                        $negozioSelezionato = array_filter($negoziAttivi, function($n) {
                            return $n['idnegozio'] == $_SESSION['order_config']['idNegozio'];
                        });
                        $negozioSelezionato = reset($negozioSelezionato);
                        if ($negozioSelezionato) {
                            $indirizzo = $negozioSelezionato['indirizzo'];
                            $parti = explode(',', $indirizzo);
                            $citta = trim(end($parti));
                            echo get_negozio_display_name($citta);
                        }
                        ?>
                    </div>
                    
                    <div class="table-responsive mb-3">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Prodotto</th>
                                    <th>Taglia</th>
                                    <th>Quantità</th>
                                    <th>Fornitore</th>
                                    <th>Prezzo Unit.</th>
                                    <th>Subtotale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totaleComplessivo = 0;
                                foreach ($prodottiSelezionati as $i => $prodotto): 
                                    $prodottoInfo = array_filter($prodotti, function($p) use ($prodotto) {
                                        return $p['idprodotto'] == $prodotto['idProdotto'];
                                    });
                                    $prodottoInfo = reset($prodottoInfo);
                                    $preview = $prodotto['preview'];
                                    $subtotale = $preview['costo_totale'];
                                    $totaleComplessivo += $subtotale;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($prodottoInfo['nome']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($prodottoInfo['marca'] . ' - ' . $prodottoInfo['tipologia']); ?></small>
                                    </td>
                                    <td><span class="badge bg-info"><?php echo $prodotto['taglia']; ?></span></td>
                                    <td><span class="badge bg-warning text-dark"><?php echo $prodotto['quantita']; ?></span></td>
                                    <td><small><?php echo htmlspecialchars($preview['fornitore']); ?></small></td>
                                    <td>€<?php echo $preview['prezzo_unitario']; ?></td>
                                    <td><strong>€<?php echo number_format($subtotale ?? 0, 2, ',', '.'); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark">
                                    <td colspan="5"><strong>Totale Complessivo</strong></td>
                                    <td><strong>€<?php echo number_format($totaleComplessivo ?? 0, 2, ',', '.'); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="torna_a_prodotti">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Modifica Prodotti
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6 text-end">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="conferma_ordine">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-check me-2"></i>Conferma Ordine
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attenzione:</strong> Confermando l'ordine, i prodotti saranno ordinati dai fornitori e aggiunti al magazzino del negozio selezionato.
                    </div>
                    
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Lista ordini raggruppati -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">Lista Ordini ai Fornitori</h5>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <select name="fornitore" class="form-select me-2">
                                <option value="">🔍 Filtra per fornitore...</option>
                                <?php foreach ($fornitori as $fornitore): ?>
                                    <option value="<?php echo htmlspecialchars($fornitore['partitaiva']); ?>" 
                                            <?php echo $filtroFornitore == $fornitore['partitaiva'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($fornitore['partitaiva']); ?>
                                        <?php if (!empty($fornitore['indirizzo'])): ?>
                                            - <?php echo htmlspecialchars(substr($fornitore['indirizzo'], 0, 30)); ?>
                                            <?php if (strlen($fornitore['indirizzo']) > 30): ?>...<?php endif; ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Filtra</button>
                            <?php if ($filtroFornitore): ?>
                                <a href="?<?php echo http_build_query(array_diff_key($_GET, ['fornitore' => ''])); ?>" 
                                   class="btn btn-outline-secondary ms-2" title="Rimuovi filtro">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if ($filtroFornitore): ?>
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-filter me-2"></i>
                        <strong>Filtro attivo:</strong> Visualizzando ordini del fornitore: <?php echo htmlspecialchars($filtroFornitore); ?>
                        <?php 
                        // Trova i dettagli del fornitore per mostrare indirizzo se disponibile
                        $fornitore_dettagli = array_filter($fornitori, function($f) use ($filtroFornitore) {
                            return $f['partitaiva'] == $filtroFornitore;
                        });
                        if (!empty($fornitore_dettagli)) {
                            $fornitore_dettagli = reset($fornitore_dettagli);
                            if (!empty($fornitore_dettagli['indirizzo'])) {
                                echo " (" . htmlspecialchars(substr($fornitore_dettagli['indirizzo'], 0, 50)) . ")";
                            }
                        }
                        ?>
                        <a href="?" class="btn btn-sm btn-outline-info ms-2">
                            <i class="fas fa-times me-1"></i>Rimuovi filtro
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($ordini)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <?php if ($filtroFornitore): ?>
                            <p class="text-muted">Nessun ordine trovato per il fornitore selezionato</p>
                        <?php else: ?>
                            <p class="text-muted">Nessun ordine trovato</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="accordion" id="ordiniAccordion">
                        <?php foreach ($ordini as $ordine): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?php echo $ordine['idordine']; ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $ordine['idordine']; ?>">
                                        <div class="d-flex justify-content-between w-100 me-3">
                                            <div>
                                                <strong>Ordine #<?php echo $ordine['idordine']; ?></strong>
                                                <span class="ms-3 text-muted">
                                                    <i class="fas fa-store me-1"></i><?php echo htmlspecialchars($ordine['nome_negozio']); ?>
                                                </span>
                                                <span class="ms-3 text-muted">
                                                    <i class="fas fa-truck me-1"></i><?php echo htmlspecialchars($ordine['partitaiva']); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="badge bg-info me-2">
                                                    <?php echo $ordine['num_prodotti']; ?> prodott<?php echo $ordine['num_prodotti'] != 1 ? 'i' : 'o'; ?>
                                                </span>
                                                <span class="badge bg-warning text-dark me-2">
                                                    <?php echo date('d/m/Y', strtotime($ordine['dataconsegna'])); ?>
                                                </span>
                                                <span class="badge bg-success">
                                                    € <?php echo number_format($ordine['valore_totale'] ?? 0, 2); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $ordine['idordine']; ?>" class="accordion-collapse collapse" data-bs-parent="#ordiniAccordion">
                                    <div class="accordion-body">
                                        <?php 
                                        $dettagli = get_dettagli_ordine($ordine['idordine']);
                                        if (!empty($dettagli)): 
                                        ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Prodotto</th>
                                                        <th>Marca</th>
                                                        <th>Tipo</th>
                                                        <th>Taglia</th>
                                                        <th>Quantità</th>
                                                        <th>Prezzo Unit.</th>
                                                        <th>Subtotale</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($dettagli as $dettaglio): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($dettaglio['nome']); ?></td>
                                                        <td><?php echo htmlspecialchars($dettaglio['marca'] ?? 'N/A'); ?></td>
                                                        <td>
                                                            <span class="badge bg-secondary">
                                                                <?php echo htmlspecialchars($dettaglio['tipologia'] ?? 'N/A'); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info">
                                                                <?php echo htmlspecialchars($dettaglio['taglia']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-warning text-dark"><?php echo $dettaglio['quantità']; ?></span>
                                                        </td>
                                                        <td>€ <?php echo number_format($dettaglio['prezzo'] ?? 0, 2); ?></td>
                                                        <td>
                                                            <strong>€ <?php echo number_format(($dettaglio['prezzo'] ?? 0) * ($dettaglio['quantità'] ?? 0), 2); ?></strong>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-dark">
                                                        <td colspan="6"><strong>Totale Ordine</strong></td>
                                                        <td><strong>€ <?php echo number_format($ordine['valore_totale'] ?? 0, 2); ?></strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <h6>Dettagli Ordine:</h6>
                                                <ul class="list-unstyled">
                                                    <li><strong>ID Ordine:</strong> #<?php echo $ordine['idordine']; ?></li>
                                                    <li><strong>Negozio destinazione:</strong> <?php echo htmlspecialchars($ordine['nome_negozio']); ?></li>
                                                    <li><strong>Fornitore:</strong> <?php echo htmlspecialchars($ordine['partitaiva']); ?></li>
                                                    <li><strong>Indirizzo Fornitore:</strong> <?php echo htmlspecialchars($ordine['fornitore_indirizzo'] ?? 'N/A'); ?></li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Stato Consegna:</h6>
                                                <?php 
                                                $dataConsegna = new DateTime($ordine['dataconsegna']);
                                                $oggi = new DateTime();
                                                $differenza = $oggi->diff($dataConsegna);
                                                ?>
                                                <?php if ($dataConsegna > $oggi): ?>
                                                    <span class="badge bg-warning">
                                                        In attesa (<?php echo $differenza->days; ?>g)
                                                    </span>
                                                <?php elseif ($dataConsegna->format('Y-m-d') == $oggi->format('Y-m-d')): ?>
                                                    <span class="badge bg-info">Consegna oggi</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Consegnato</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Statistiche ordini -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">
                            Totale Ordini
                            <?php if ($filtroFornitore): ?>
                                <small class="text-muted">(filtrati)</small>
                            <?php endif; ?>
                        </h5>
                        <h2 class="text-primary"><?php echo count($ordini); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">
                            Valore Totale
                            <?php if ($filtroFornitore): ?>
                                <small class="text-muted">(filtrato)</small>
                            <?php endif; ?>
                        </h5>
                        <h2 class="text-success">
                            € <?php 
                            $valoreTotal = 0;
                            foreach ($ordini as $ordine) {
                                $valoreTotal += $ordine['valore_totale'] ?? 0;
                            }
                            echo number_format($valoreTotal ?? 0, 2);
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">
                            Fornitori Coinvolti
                            <?php if ($filtroFornitore): ?>
                                <small class="text-muted">(filtrati)</small>
                            <?php endif; ?>
                        </h5>
                        <h2 class="text-info">
                            <?php 
                            $fornitori = array_unique(array_column($ordini, 'partitaiva'));
                            echo count($fornitori);
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include_once ('lib/manager_footer.php'); ?>
</body>
</html>
