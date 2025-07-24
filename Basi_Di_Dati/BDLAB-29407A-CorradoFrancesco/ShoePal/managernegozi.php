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

    // Gestione form creazione negozio
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] == 'create_negozio') {
            $responsabile = trim($_POST['responsabile']);
            $indirizzo = trim($_POST['indirizzo']);
            
            if (!empty($responsabile) && !empty($indirizzo)) {
                $idNegozio = create_negozio($responsabile, $indirizzo);
                if ($idNegozio) {
                    $message = "Negozio creato con successo (ID: $idNegozio)";
                } else {
                    $error = "Errore nella creazione del negozio";
                }
            } else {
                $error = "Compilare tutti i campi obbligatori";
            }
        } elseif ($_POST['action'] == 'close_negozio') {
            $idNegozio = $_POST['idNegozio'];
            if (close_negozio($idNegozio)) {
                $message = "Negozio chiuso con successo";
            } else {
                $error = "Errore nella chiusura del negozio";
            }
        } elseif ($_POST['action'] == 'reopen_negozio') {
            $idNegozio = $_POST['idNegozio'];
            if (reopen_negozio($idNegozio)) {
                $message = "Negozio riaperto con successo";
            } else {
                $error = "Errore nella riapertura del negozio";
            }
        } elseif ($_POST['action'] == 'save_orari') {
            $idNegozio = $_POST['idNegozio'];
            $success = true;
            $giorni = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
            
            foreach ($giorni as $giorno) {
                $orainizio = $_POST[$giorno . '_inizio'] ?? '';
                $orafine = $_POST[$giorno . '_fine'] ?? '';
                
                if (!empty($orainizio) && !empty($orafine)) {
                    if (!save_orario_negozio($idNegozio, $giorno, $orainizio, $orafine)) {
                        $success = false;
                        break;
                    }
                }
            }
            
            if ($success) {
                $message = "Orari salvati con successo";
                // Rimani nella vista orari
                header("Location: managernegozi.php?orari=" . $idNegozio . "&message=" . urlencode($message));
                exit();
            } else {
                $error = "Errore nel salvataggio degli orari";
                // Rimani nella vista orari
                header("Location: managernegozi.php?orari=" . $idNegozio . "&error=" . urlencode($error));
                exit();
            }
        } elseif ($_POST['action'] == 'update_orari') {
            $idNegozio = $_POST['idNegozio'];
            $giorni = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
            
            $success = true;
            foreach ($giorni as $giorno) {
                $oraInizio = !empty($_POST['orario_' . $giorno . '_inizio']) ? $_POST['orario_' . $giorno . '_inizio'] : null;
                $oraFine = !empty($_POST['orario_' . $giorno . '_fine']) ? $_POST['orario_' . $giorno . '_fine'] : null;
                
                if (!update_orario_negozio($idNegozio, $giorno, $oraInizio, $oraFine)) {
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                $message = "Orari aggiornati con successo";
            } else {
                $error = "Errore nell'aggiornamento degli orari";
            }
        }
    }

    // Gestione messaggi da redirect
    if (isset($_GET['error'])) {
        $error = $_GET['error'];
    }
    if (isset($_GET['message'])) {
        $message = $_GET['message'];
    }

    // Gestione vista orari
    $show_orari = isset($_GET['orari']) && !empty($_GET['orari']);
    $idNegozioOrari = $_GET['orari'] ?? '';
    $nome_negozio = '';
    $negozio_info = null;
    $orari = [];
    $orariByGiorno = [];

    if ($show_orari && !empty($idNegozioOrari)) {
        // Ottieni i dettagli del negozio per estrarre la città (anche per negozi chiusi)
        $connection = open_pg_connection();
        $query = "SELECT idnegozio, indirizzo, responsabile, attivo 
                  FROM shoepal.negozio 
                  WHERE idnegozio = $1";
        $result = pg_query_params($connection, $query, array($idNegozioOrari));
        
        if ($result && pg_num_rows($result) > 0) {
            $negozio_info = pg_fetch_assoc($result);
        }
        close_pg_connection($connection);
        
        $nome_negozio = "Negozio #" . htmlspecialchars($idNegozioOrari);
        
        if ($negozio_info && !empty($negozio_info['indirizzo'])) {
            $indirizzo_parti = explode(',', $negozio_info['indirizzo']);
            $citta = trim(end($indirizzo_parti));
            $nome_negozio = get_negozio_display_name($citta);
        } elseif ($show_orari) {
            // Se il negozio non esiste, reindirizza
            header("Location: managernegozi.php?error=" . urlencode("Negozio non trovato"));
            exit();
        }

        if ($negozio_info) {
            $orari = get_orari_negozio($idNegozioOrari);
            foreach ($orari as $orario) {
                $orariByGiorno[$orario['giorno']] = $orario;
            }
        }
    }

    $negozi = get_all_negozi_manager();
?>
<!doctype html>
<html lang="it">
<head>
    <title>Gestione Negozi</title>
    <?php include_once ('lib/header.php'); ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include_once ('lib/manager_navigation.php'); ?>
    
    <main class="flex-grow-1">
        <div class="container my-4">
        <h1 class="mb-4">Gestione Negozi</h1>
        
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
        
        <?php if (!$show_orari): ?>
        <!-- Form creazione nuovo negozio -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Crea Nuovo Negozio</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create_negozio">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="responsabile" class="form-label">Responsabile *</label>
                                <input type="text" class="form-control" id="responsabile" name="responsabile" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="indirizzo" class="form-label">Indirizzo *</label>
                                <input type="text" class="form-control" id="indirizzo" name="indirizzo" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Crea Negozio</button>
                </form>
            </div>
        </div>
        
        <!-- Lista negozi -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Lista Negozi</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Responsabile</th>
                                <th>Indirizzo</th>
                                <th>Stato</th>
                                <th>Data Chiusura</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($negozi as $negozio): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($negozio['idnegozio'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($negozio['responsabile'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($negozio['indirizzo'] ?? ''); ?></td>
                                <td>
                                    <?php 
                                    $attivo = $negozio['attivo'] ?? false;
                                    if ($attivo === true || $attivo === 't' || $attivo === '1' || $attivo == 1): ?>
                                        <span class="badge bg-success">Attivo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Chiuso</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo !empty($negozio['datachiusura']) ? htmlspecialchars($negozio['datachiusura']) : '-'; ?></td>
                                <td>
                                    <?php 
                                    $attivo = $negozio['attivo'] ?? false;
                                    if ($attivo === true || $attivo === 't' || $attivo === '1' || $attivo == 1): ?>
                                        <a href="?orari=<?php echo htmlspecialchars($negozio['idnegozio'] ?? ''); ?>" class="btn btn-sm btn-warning me-2">
                                            Gestisci Orari
                                        </a>
                                        <?php if (isset($_GET['confirm_close']) && $_GET['confirm_close'] == $negozio['idnegozio']): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="close_negozio">
                                                <input type="hidden" name="idNegozio" value="<?php echo htmlspecialchars($negozio['idnegozio'] ?? ''); ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Conferma Chiusura</button>
                                            </form>
                                            <a href="?" class="btn btn-sm btn-secondary">Annulla</a>
                                        <?php else: ?>
                                            <a href="?confirm_close=<?php echo $negozio['idnegozio']; ?>" class="btn btn-sm btn-danger">Chiudi</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if (isset($_GET['confirm_reopen']) && $_GET['confirm_reopen'] == $negozio['idnegozio']): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="reopen_negozio">
                                                <input type="hidden" name="idNegozio" value="<?php echo htmlspecialchars($negozio['idnegozio'] ?? ''); ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Conferma Riapertura</button>
                                            </form>
                                            <a href="?" class="btn btn-sm btn-secondary">Annulla</a>
                                        <?php else: ?>
                                            <a href="?confirm_reopen=<?php echo $negozio['idnegozio']; ?>" class="btn btn-sm btn-success">Riapri</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    
        <?php if ($show_orari && $negozio_info): ?>
        <!-- Vista Gestione Orari -->
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Gestione Orari - <?php echo htmlspecialchars($nome_negozio); ?></h1>
                    <a href="managernegozi.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Torna ai Negozi
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock me-2"></i>Orari di Apertura
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="save_orari">
                            <input type="hidden" name="idNegozio" value="<?php echo htmlspecialchars($idNegozioOrari); ?>">
                            
                            <?php 
                            $giorni = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
                            foreach ($giorni as $giorno): 
                                $orario = $orariByGiorno[$giorno] ?? null;
                            ?>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold"><?php echo $giorno; ?></label>
                                </div>
                                <div class="col-md-4">
                                    <input type="time" class="form-control" 
                                           name="<?php echo $giorno; ?>_inizio" 
                                           value="<?php echo $orario ? htmlspecialchars($orario['orainizio']) : ''; ?>"
                                           placeholder="Ora apertura">
                                </div>
                                <div class="col-md-4">
                                    <input type="time" class="form-control" 
                                           name="<?php echo $giorno; ?>_fine" 
                                           value="<?php echo $orario ? htmlspecialchars($orario['orafine']) : ''; ?>"
                                           placeholder="Ora chiusura">
                                </div>
                                <div class="col-md-1 d-flex align-items-center">
                                    <?php if ($orario): ?>
                                        <i class="fas fa-check-circle text-success"></i>
                                    <?php else: ?>
                                        <i class="fas fa-times-circle text-muted"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <hr>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Salva Orari
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Informazioni
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">
                            <small>Imposta gli orari di apertura per ogni giorno della settimana. 
                            Lascia vuoti i campi per i giorni di chiusura.</small>
                        </p>
                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Suggerimento:</strong> Gli orari vengono salvati automaticamente quando clicchi "Salva Orari".
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    </main>

    <?php include_once ('lib/manager_footer.php'); ?>
</body>
</html>
