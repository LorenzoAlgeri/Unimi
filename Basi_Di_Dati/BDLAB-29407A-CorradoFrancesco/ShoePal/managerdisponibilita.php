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

    // Gestione form
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] == 'update_disponibilita') {
            $idNegozio = $_POST['idNegozio'];
            $idProdotto = $_POST['idProdotto'];
            $taglia = $_POST['taglia'];
            $prezzo = floatval($_POST['prezzo']);
            $quantita = intval($_POST['quantita']);
            
            if ($idNegozio && $idProdotto && $taglia && $prezzo > 0 && $quantita >= 0) {
                if (update_disponibilita($idNegozio, $idProdotto, $taglia, $prezzo, $quantita)) {
                    $message = "Disponibilità aggiornata con successo";
                    // Redirect per rimuovere i parametri edit dall'URL
                    header("Location: managerdisponibilita.php?negozio=" . $idNegozio . "&message=" . urlencode($message));
                    exit();
                } else {
                    $error = "Errore nell'aggiornamento della disponibilità";
                }
            } else {
                $error = "Dati non validi";
            }
        } elseif ($_POST['action'] == 'ordina_prodotto') {
            $idProdotto = $_POST['idProdotto'];
            $idNegozio = $_POST['idNegozio'] ?? null;
            $taglia = $_POST['taglia'] ?? null;
            $quantita = intval($_POST['quantita']);
            
            if ($idProdotto && $quantita > 0) {
                try {
                    if (ordina_prodotto($idProdotto, $quantita, $idNegozio, $taglia)) {
                        $message = "Ordine inviato con successo - Prodotti aggiunti al negozio";
                    } else {
                        $error = "Errore nell'invio dell'ordine - Controlla che ci sia un fornitore con disponibilità sufficiente";
                    }
                } catch (Exception $e) {
                    $error = "Errore: " . $e->getMessage();
                }
            } else {
                $error = "Dati non validi per l'ordine";
            }
        } elseif ($_POST['action'] == 'sposta_prodotto') {
            $idNegozioOrigine = $_POST['idNegozioOrigine'];
            $idNegozioDest = $_POST['idNegozioDest'];
            $idProdotto = $_POST['idProdotto'];
            $taglia = $_POST['taglia'];
            $quantita = intval($_POST['quantita']);
            
            if ($idNegozioOrigine && $idNegozioDest && $idProdotto && $taglia && $quantita > 0) {
                if (sposta_prodotto_tra_negozi($idNegozioOrigine, $idNegozioDest, $idProdotto, $taglia, $quantita)) {
                    $message = "Prodotto spostato con successo da negozio $idNegozioOrigine a negozio $idNegozioDest";
                } else {
                    $error = "Errore nello spostamento del prodotto";
                }
            } else {
                $error = "Dati non validi per lo spostamento";
            }
        }
    }

    // Gestione messaggi da redirect
    if (isset($_GET['message'])) {
        $message = $_GET['message'];
    }
    if (isset($_GET['error'])) {
        $error = $_GET['error'];
    }

    $disponibilita = get_disponibilita_by_negozio();
    $prodotti = get_all_prodotti_manager();
    $negozi = get_all_negozi_manager();
    
    // Gestione modalità modifica e aggiunta
    $editDisp = null;
    $addDisp = null;
    if (isset($_GET['edit_disp'])) {
        $parts = explode('_', $_GET['edit_disp'], 3);
        if (count($parts) == 3) {
            $editIdNegozio = $parts[0];
            $editIdProdotto = $parts[1];
            $editTaglia = urldecode($parts[2]);
            foreach ($disponibilita as $disp) {
                if ($disp['idnegozio'] == $editIdNegozio && 
                    $disp['idprodotto'] == $editIdProdotto && 
                    $disp['taglia'] == $editTaglia) {
                    $editDisp = $disp;
                    break;
                }
            }
        }
    }
    if (isset($_GET['add_disp'])) {
        $parts = explode('_', $_GET['add_disp'], 2);
        if (count($parts) == 2) {
            $addIdNegozio = $parts[0];
            $addIdProdotto = $parts[1];
            foreach ($prodotti as $prod) {
                if ($prod['idprodotto'] == $addIdProdotto) {
                    $addDisp = ['idnegozio' => $addIdNegozio, 'prodotto' => $prod];
                    break;
                }
            }
        }
    }
    
    // Raggruppa disponibilità per negozio
    $disponibilitaPerNegozio = [];
    foreach ($disponibilita as $disp) {
        $disponibilitaPerNegozio[$disp['idnegozio']][] = $disp;
    }
    
    // Ottieni solo negozi attivi per il trasferimento
    $negoziAttivi = array_filter($negozi, function($negozio) {
        $attivo = $negozio['attivo'] ?? false;
        return ($attivo === true || $attivo === 't' || $attivo === '1' || $attivo == 1);
    });
?>
<!doctype html>
<html lang="it">
<head>
    <title>Gestione Disponibilità</title>
    <?php include_once ('lib/header.php'); ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include_once ('lib/manager_navigation.php'); ?>
    
    <main class="flex-grow-1">
        <div class="container my-4">
        <h1 class="mb-4">Gestione Disponibilità</h1>
        
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
        
        <!-- Selezione negozio -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Seleziona Negozio</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <select class="form-select me-2" name="negozio">
                                <option value="">Seleziona un negozio...</option>
                                
                                <!-- Negozi Attivi -->
                                <optgroup label="Negozi Attivi">
                                <?php foreach ($negozi as $negozio): ?>
                                    <?php 
                                    $attivo = $negozio['attivo'] ?? false;
                                    if ($attivo === true || $attivo === 't' || $attivo === '1' || $attivo == 1): ?>
                                        <?php 
                                        // Estrai la città dall'indirizzo
                                        $indirizzo = $negozio['indirizzo'] ?? '';
                                        $parts = explode(',', $indirizzo);
                                        $citta = trim(end($parts));
                                        $nomeNegozio = get_negozio_display_name($citta);
                                        $isSelected = (isset($_GET['negozio']) && $_GET['negozio'] == $negozio['idnegozio']);
                                        ?>
                                        <option value="<?php echo htmlspecialchars($negozio['idnegozio'] ?? ''); ?>" 
                                                <?php echo $isSelected ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($nomeNegozio); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                </optgroup>
                                
                                <!-- Negozi Chiusi -->
                                <optgroup label="Negozi Chiusi">
                                <?php foreach ($negozi as $negozio): ?>
                                    <?php 
                                    $attivo = $negozio['attivo'] ?? false;
                                    if (!($attivo === true || $attivo === 't' || $attivo === '1' || $attivo == 1)): ?>
                                        <?php 
                                        // Estrai la città dall'indirizzo
                                        $indirizzo = $negozio['indirizzo'] ?? '';
                                        $parts = explode(',', $indirizzo);
                                        $citta = trim(end($parts));
                                        $nomeNegozio = get_negozio_display_name($citta) . " (CHIUSO)";
                                        $isSelected = (isset($_GET['negozio']) && $_GET['negozio'] == $negozio['idnegozio']);
                                        ?>
                                        <option value="<?php echo htmlspecialchars($negozio['idnegozio'] ?? ''); ?>" 
                                                <?php echo $isSelected ? 'selected' : ''; ?> style="color: #dc3545;">
                                            <?php echo htmlspecialchars($nomeNegozio); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                </optgroup>
                            </select>
                            <button type="submit" class="btn btn-primary">Seleziona</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form modifica disponibilità (mostrato solo se in modalità edit) -->
        <?php if ($editDisp): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Modifica Disponibilità: <?php echo htmlspecialchars($editDisp['nome']); ?> - Taglia <?php echo htmlspecialchars($editDisp['taglia']); ?></h5>
                <a href="?negozio=<?php echo $_GET['negozio']; ?>" class="btn btn-sm btn-secondary">Annulla</a>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_disponibilita">
                    <input type="hidden" name="idNegozio" value="<?php echo $editDisp['idnegozio']; ?>">
                    <input type="hidden" name="idProdotto" value="<?php echo $editDisp['idprodotto']; ?>">
                    <input type="hidden" name="taglia" value="<?php echo htmlspecialchars($editDisp['taglia']); ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prodotto</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($editDisp['nome']); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Taglia</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($editDisp['taglia']); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editPrezzo" class="form-label">Prezzo *</label>
                                <input type="number" step="0.01" class="form-control" id="editPrezzo" name="prezzo" value="<?php echo $editDisp['prezzo']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editQuantita" class="form-label">Quantità *</label>
                                <input type="number" class="form-control" id="editQuantita" name="quantita" value="<?php echo $editDisp['quantità']; ?>" required min="0">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                    <a href="?negozio=<?php echo $_GET['negozio']; ?>" class="btn btn-secondary ms-2">Annulla</a>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Form aggiungi disponibilità (mostrato solo se in modalità add) -->
        <?php if ($addDisp): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Aggiungi Disponibilità: <?php echo htmlspecialchars($addDisp['prodotto']['nome'] . ' - ' . $addDisp['prodotto']['marca']); ?></h5>
                <a href="?negozio=<?php echo $_GET['negozio']; ?>" class="btn btn-sm btn-secondary">Annulla</a>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_disponibilita">
                    <input type="hidden" name="idNegozio" value="<?php echo $addDisp['idnegozio']; ?>">
                    <input type="hidden" name="idProdotto" value="<?php echo $addDisp['prodotto']['idprodotto']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Prodotto</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($addDisp['prodotto']['nome'] . ' - ' . $addDisp['prodotto']['marca']); ?>" readonly>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="addTaglia" class="form-label">Taglia *</label>
                                <input type="text" class="form-control" id="addTaglia" name="taglia" required placeholder="es. 42">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="addPrezzo" class="form-label">Prezzo *</label>
                                <input type="number" step="0.01" class="form-control" id="addPrezzo" name="prezzo" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="addQuantita" class="form-label">Quantità *</label>
                                <input type="number" class="form-control" id="addQuantita" name="quantita" required min="0">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Aggiungi Disponibilità</button>
                    <a href="?negozio=<?php echo $_GET['negozio']; ?>" class="btn btn-secondary ms-2">Annulla</a>
                </form>
            </div>
        </div>
        <?php endif; ?>

        
        <!-- Disponibilità per negozio selezionato -->
        <?php 
        $negozioSelezionato = $_GET['negozio'] ?? null;
        if ($negozioSelezionato): 
            foreach ($negozi as $negozio): 
                if ($negozio['idnegozio'] == $negozioSelezionato):
        ?>
                <?php 
                // Estrai la città dall'indirizzo per il nome del negozio
                $indirizzo = $negozio['indirizzo'] ?? '';
                $parts = explode(',', $indirizzo);
                $citta = trim(end($parts));
                $attivo = $negozio['attivo'] ?? false;
                $isNegozioAttivo = ($attivo === true || $attivo === 't' || $attivo === '1' || $attivo == 1);
                $nomeNegozio = get_negozio_display_name($citta);
                if (!$isNegozioAttivo) {
                    $nomeNegozio .= " (CHIUSO)";
                }
                $idNegozio = $negozio['idnegozio'];
                ?>
                <div class="card mb-4 <?php echo !$isNegozioAttivo ? 'border-danger' : ''; ?>">
                    <div class="card-header d-flex justify-content-between align-items-center <?php echo !$isNegozioAttivo ? 'bg-danger text-white' : ''; ?>">
                        <h5 class="mb-0"><?php echo htmlspecialchars($nomeNegozio); ?></h5>
                        <?php if (!$isNegozioAttivo): ?>
                            <span class="badge bg-warning text-dark">Negozio Chiuso - Solo Gestione Magazzino</span>
                        <?php else: ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="ordina_prodotto">
                                <input type="hidden" name="idNegozio" value="<?php echo $idNegozio; ?>">
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#ordinaModal">
                                    Ordina Prodotti
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Prodotto</th>
                                        <th>Taglia</th>
                                        <th>Prezzo</th>
                                        <th>Quantità</th>
                                        <th>Stato</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $disponibilitaNegozio = $disponibilitaPerNegozio[$idNegozio] ?? [];
                                    foreach ($disponibilitaNegozio as $disp): ?>
                                    <tr class="<?php echo $disp['quantità'] == 0 ? 'table-danger' : ($disp['quantità'] < 5 ? 'table-warning' : ''); ?>">
                                        <td><?php echo htmlspecialchars($disp['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($disp['taglia']); ?></td>
                                        <td>€ <?php echo number_format($disp['prezzo'], 2); ?></td>
                                        <td><?php echo $disp['quantità']; ?></td>
                                        <td>
                                            <?php if ($disp['quantità'] == 0): ?>
                                                <span class="badge bg-danger">Esaurito</span>
                                            <?php elseif ($disp['quantità'] < 5): ?>
                                                <span class="badge bg-warning">Scorte basse</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Disponibile</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isNegozioAttivo): ?>
                                            <form method="POST" class="d-inline me-1">
                                                <input type="hidden" name="action" value="update_disponibilita">
                                                <input type="hidden" name="idNegozio" value="<?php echo $disp['idnegozio']; ?>">
                                                <input type="hidden" name="idProdotto" value="<?php echo $disp['idprodotto']; ?>">
                                                <input type="hidden" name="taglia" value="<?php echo htmlspecialchars($disp['taglia']); ?>">
                                                <a href="?negozio=<?php echo $negozioSelezionato; ?>&edit_disp=<?php echo $disp['idnegozio']; ?>_<?php echo $disp['idprodotto']; ?>_<?php echo urlencode($disp['taglia']); ?>" class="btn btn-sm btn-primary me-1">
                                                    Modifica
                                                </a>
                                            </form>
                            <a href="managerordini.php?riordina=<?php echo $disp['idprodotto']; ?>&taglia=<?php echo urlencode($disp['taglia']); ?>&negozio=<?php echo $idNegozio; ?>&nome=<?php echo urlencode($disp['nome']); ?>" 
                               class="btn btn-sm btn-warning me-1">
                                <i class="fas fa-plus-circle me-1"></i>Riordina
                            </a>
                            <?php endif; ?>
                            
                            <!-- Funzione di spostamento disponibile per tutti i negozi -->
                            <?php if (isset($_GET['sposta']) && $_GET['sposta'] == $disp['idnegozio'].'_'.$disp['idprodotto'].'_'.urlencode($disp['taglia'])): ?>
                                <!-- Form di spostamento inline -->
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="sposta_prodotto">
                                    <input type="hidden" name="idNegozioOrigine" value="<?php echo $disp['idnegozio']; ?>">
                                    <input type="hidden" name="idProdotto" value="<?php echo $disp['idprodotto']; ?>">
                                    <input type="hidden" name="taglia" value="<?php echo htmlspecialchars($disp['taglia']); ?>">
                                    <div class="row g-2">
                                        <div class="col-auto">
                                            <select name="idNegozioDest" class="form-select form-select-sm" required>
                                                <option value="">Seleziona negozio...</option>
                                                <?php foreach ($negoziAttivi as $negozioAttivo): ?>
                                                    <?php 
                                                    $indirizzoDest = $negozioAttivo['indirizzo'] ?? '';
                                                    $partsDest = explode(',', $indirizzoDest);
                                                    $cittaDest = trim(end($partsDest));
                                                    $nomeNegozioAttivo = get_negozio_display_name($cittaDest);
                                                    ?>
                                                    <option value="<?php echo $negozioAttivo['idnegozio']; ?>">
                                                        <?php echo htmlspecialchars($nomeNegozioAttivo); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <input type="number" name="quantita" class="form-control form-control-sm" 
                                                   value="<?php echo $disp['quantità']; ?>" min="1" max="<?php echo $disp['quantità']; ?>" 
                                                   style="width: 80px;" required>
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-sm btn-success">Sposta</button>
                                            <a href="?negozio=<?php echo $negozioSelezionato; ?>" class="btn btn-sm btn-secondary">Annulla</a>
                                        </div>
                                    </div>
                                </form>
                            <?php else: ?>
                            <a href="?negozio=<?php echo $negozioSelezionato; ?>&sposta=<?php echo $disp['idnegozio'].'_'.$disp['idprodotto'].'_'.urlencode($disp['taglia']); ?>" 
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-exchange-alt me-1"></i>Sposta in altro negozio
                            </a>
                            <?php endif; ?>
                             <!-- Sezione per negozi chiusi (solo spostamento) -->
                            <?php if (!$isNegozioAttivo): ?>
                                <small class="text-muted">Negozio chiuso - Solo spostamento disponibile</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                                    
                                    <!-- Mostra tutti i prodotti per permettere di aggiungere nuove disponibilità (solo per negozi attivi) -->
                                    <?php if ($isNegozioAttivo): ?>
                                    <?php 
                                    $prodottiPresenti = array_column($disponibilitaNegozio, 'idprodotto');
                                    foreach ($prodotti as $prodotto): 
                                        if (!in_array($prodotto['idprodotto'], $prodottiPresenti)): ?>
                                    <tr class="table-light">
                                        <td colspan="4">
                                            <strong><?php echo htmlspecialchars($prodotto['nome'] . ' - ' . $prodotto['marca']); ?></strong>
                                            <small class="text-muted d-block">Non disponibile in questo negozio</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">Non disponibile</span>
                                        </td>
                                        <td>
                                            <a href="?negozio=<?php echo $negozioSelezionato; ?>&add_disp=<?php echo $idNegozio; ?>_<?php echo $prodotto['idprodotto']; ?>" class="btn btn-sm btn-success">
                                                Aggiungi
                                            </a>
                                            <a href="managerordini.php?riordina=<?php echo $prodotto['idprodotto']; ?>&taglia=42&negozio=<?php echo $idNegozio; ?>&nome=<?php echo urlencode($prodotto['nome'] . ' - ' . $prodotto['marca']); ?>" 
                               class="btn btn-sm btn-warning ms-1">
                                <i class="fas fa-plus-circle me-1"></i>Ordina
                            </a>
                                        </td>
                                    </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
        <?php 
                endif; 
            endforeach; 
        else: 
        ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                Seleziona un negozio dal menu a tendina per visualizzare le disponibilità
            </div>
        <?php endif; ?>
    </main>

    <?php include_once ('lib/manager_footer.php'); ?>
</body>
</html>
