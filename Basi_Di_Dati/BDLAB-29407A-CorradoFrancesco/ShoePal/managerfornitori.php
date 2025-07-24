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
    
    // Gestione navigazione tra lista fornitori e gestione singolo fornitore
    $fornitore_selezionato = $_GET['fornitore'] ?? '';
    $modalita = empty($fornitore_selezionato) ? 'lista' : 'gestione_fornitore';

    // Gestione form
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] == 'create_fornitore') {
            $partitaIVA = trim($_POST['partitaIVA']);
            $indirizzo = trim($_POST['indirizzo']);
            
            if (!empty($partitaIVA) && !empty($indirizzo)) {
                if (create_fornitore($partitaIVA, $indirizzo)) {
                    $message = "Fornitore creato con successo";
                } else {
                    $error = "Errore nella creazione del fornitore (P.IVA già esistente?)";
                }
            } else {
                $error = "Compilare tutti i campi obbligatori";
            }
        } elseif ($_POST['action'] == 'update_fornitore') {
            $partitaIVA = $_POST['partitaIVA'];
            $indirizzo = trim($_POST['indirizzo']);
            
            if (!empty($indirizzo)) {
                if (update_fornitore($partitaIVA, $indirizzo)) {
                    $message = "Fornitore aggiornato con successo";
                } else {
                    $error = "Errore nell'aggiornamento del fornitore";
                }
            } else {
                $error = "Indirizzo obbligatorio";
            }
        } 
        // Gestione forniture
        elseif ($_POST['action'] == 'create_fornitura') {
            $fornitore = $_POST['fornitore'];
            $idProdotto = $_POST['idProdotto'];
            $taglia = $_POST['taglia'] ?? '42';
            $disponibilita = $_POST['disponibilita'];
            $costo = $_POST['costo'];
            
            if (create_fornitura($fornitore, $idProdotto, $taglia, $disponibilita, $costo)) {
                $message = "Fornitura creata con successo";
            } else {
                $error = "Errore nella creazione della fornitura";
            }
        } elseif ($_POST['action'] == 'update_fornitura') {
            $fornitore = $_POST['fornitore'];
            $idProdotto = $_POST['idProdotto'];
            $taglia = $_POST['taglia'] ?? '42';
            $disponibilita = $_POST['disponibilita'];
            $costo = $_POST['costo'];
            
            if (update_fornitura($fornitore, $idProdotto, $taglia, $disponibilita, $costo)) {
                $message = "Fornitura aggiornata con successo";
            } else {
                $error = "Errore nell'aggiornamento della fornitura";
            }
        } elseif ($_POST['action'] == 'delete_fornitura') {
            $fornitore = $_POST['fornitore'];
            $idProdotto = $_POST['idProdotto'];
            $taglia = $_POST['taglia'] ?? '42';
            
            if (delete_fornitura($fornitore, $idProdotto, $taglia)) {
                $message = "Fornitura eliminata con successo";
            } else {
                $error = "Errore nell'eliminazione della fornitura";
            }
        }
    }

    // Gestione edit fornitore
    $editFornitore = null;
    if (isset($_GET['edit'])) {
        $editPartitaIVA = $_GET['edit'];
        $fornitori = get_all_fornitori();
        foreach ($fornitori as $f) {
            if ($f['partitaiva'] === $editPartitaIVA) {
                $editFornitore = $f;
                break;
            }
        }
    }

    $fornitori = get_all_fornitori();
    
    // Carica dati specifici del fornitore se in modalità gestione
    $fornitore_data = null;
    $forniture = [];
    $prodotti_disponibili = [];
    $statistiche = [];
    $forniture_taglie = [];
    $cronologia_ordini = [];
    $prodotti_top = [];
    
    if ($modalita === 'gestione_fornitore' && !empty($fornitore_selezionato)) {
        $fornitore_data = get_fornitore_by_piva($fornitore_selezionato);
        $forniture = get_forniture_by_fornitore($fornitore_selezionato);
        $prodotti_disponibili = get_all_prodotti_manager();
        $statistiche = get_fornitore_statistiche($fornitore_selezionato);
        $forniture_taglie = get_forniture_per_taglia($fornitore_selezionato);
        $cronologia_ordini = get_cronologia_ordini_fornitore($fornitore_selezionato, 10);
        $prodotti_top = get_prodotti_piu_ordinati_fornitore($fornitore_selezionato, 5);
    }
?>
<!doctype html>
<html lang="it">
<head>
    <title><?php echo $modalita === 'gestione_fornitore' ? 'Gestione Fornitore' : 'Gestione Fornitori'; ?> - ShoePal</title>
    <?php include_once ('lib/header.php'); ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include_once ('lib/manager_navigation.php'); ?>
    
    <main class="flex-grow-1">
        <div class="container my-4">
        <?php if ($modalita === 'lista'): ?>
            <!-- MODALITÀ LISTA FORNITORI -->
            <h1 class="mb-4">Gestione Fornitori</h1>
        <?php else: ?>
            <!-- MODALITÀ GESTIONE SINGOLO FORNITORE -->
            <div class="row">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1><i class="fas fa-truck me-2"></i>Panoramica Fornitore</h1>
                            <p class="lead mb-0">
                                <strong><?php echo htmlspecialchars($fornitore_data['nome'] ?? $fornitore_data['partitaiva'] ?? 'Fornitore'); ?></strong> 
                                (P.IVA: <?php echo htmlspecialchars($fornitore_selezionato); ?>)
                            </p>
                            <small class="text-muted">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php echo htmlspecialchars($fornitore_data['indirizzo'] ?? 'Indirizzo non disponibile'); ?>
                            </small>
                        </div>
                        <a href="managerfornitori.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Torna ai Fornitori
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
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
        
        <?php if ($modalita === 'lista'): ?>
            <!-- CONTENUTO MODALITÀ LISTA FORNITORI -->
        
        <!-- Form creazione nuovo fornitore -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Crea Nuovo Fornitore</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create_fornitore">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="partitaIVA" class="form-label">Partita IVA *</label>
                                <input type="text" class="form-control" id="partitaIVA" name="partitaIVA" required maxlength="11" placeholder="es. 12345678901">
                                <div class="form-text">Inserire la partita IVA (massimo 11 caratteri)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="indirizzo" class="form-label">Indirizzo *</label>
                                <input type="text" class="form-control" id="indirizzo" name="indirizzo" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Crea Fornitore</button>
                </form>
            </div>
        </div>
        
        <!-- Form modifica fornitore (mostrato solo se in modalità edit) -->
        <?php if ($editFornitore): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Modifica Fornitore: <?php echo htmlspecialchars($editFornitore['partitaiva']); ?></h5>
                <a href="managerfornitori.php" class="btn btn-sm btn-secondary">Annulla</a>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_fornitore">
                    <input type="hidden" name="partitaIVA" value="<?php echo htmlspecialchars($editFornitore['partitaiva']); ?>">
                    <div class="mb-3">
                        <label class="form-label">Partita IVA</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($editFornitore['partitaiva']); ?>" readonly>
                        <small class="text-muted">La Partita IVA non può essere modificata</small>
                    </div>
                    <div class="mb-3">
                        <label for="editIndirizzo" class="form-label">Indirizzo *</label>
                        <textarea class="form-control" id="editIndirizzo" name="indirizzo" rows="3" required><?php echo htmlspecialchars($editFornitore['indirizzo']); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                    <a href="managerfornitori.php" class="btn btn-secondary ms-2">Annulla</a>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Lista fornitori -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Lista Fornitori</h5>
            </div>
            <div class="card-body">
                <?php if (empty($fornitori)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nessun fornitore trovato</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Partita IVA</th>
                                    <th>Indirizzo</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fornitori as $fornitore): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fornitore['partitaiva']); ?></td>
                                    <td><?php echo htmlspecialchars($fornitore['indirizzo']); ?></td>
                                    <td>
                                        <a href="?fornitore=<?php echo urlencode($fornitore['partitaiva']); ?>" class="btn btn-sm btn-info me-2">
                                            Prodotti Forniti
                                        </a>
                                        <a href="?edit=<?php echo urlencode($fornitore['partitaiva']); ?>" class="btn btn-sm btn-warning">
                                            Modifica
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php else: ?>
            <!-- MODALITÀ GESTIONE SINGOLO FORNITORE -->
            
            <!-- Statistiche generali fornitore -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-bar me-2"></i>Statistiche Generali
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-2">
                                    <div class="border rounded p-3 h-100">
                                        <h3 class="text-primary mb-1"><?php echo $statistiche['prodotti_totali'] ?? 0; ?></h3>
                                        <small class="text-muted">Prodotti Forniti</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="border rounded p-3 h-100">
                                        <h3 class="text-success mb-1"><?php echo number_format($statistiche['scorte_totali'] ?? 0); ?></h3>
                                        <small class="text-muted">Scorte Totali</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="border rounded p-3 h-100">
                                        <h3 class="text-warning mb-1">€<?php echo number_format($statistiche['costo_medio'] ?? 0, 2, ',', '.'); ?></h3>
                                        <small class="text-muted">Costo Medio</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="border rounded p-3 h-100">
                                        <h3 class="text-info mb-1">€<?php echo number_format($statistiche['costo_minimo'] ?? 0, 2, ',', '.'); ?></h3>
                                        <small class="text-muted">Prezzo Min</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="border rounded p-3 h-100">
                                        <h3 class="text-danger mb-1">€<?php echo number_format($statistiche['costo_massimo'] ?? 0, 2, ',', '.'); ?></h3>
                                        <small class="text-muted">Prezzo Max</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="border rounded p-3 h-100">
                                        <h3 class="text-secondary mb-1">€<?php echo number_format($statistiche['valore_totale'] ?? 0, 2, ',', '.'); ?></h3>
                                        <small class="text-muted">Valore Totale</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form aggiunta nuova fornitura -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-plus me-2"></i>Aggiungi Nuova Fornitura
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="create_fornitura">
                                <input type="hidden" name="fornitore" value="<?php echo htmlspecialchars($fornitore_selezionato); ?>">
                                
                                <div class="col-md-3">
                                    <label for="idProdotto" class="form-label">Prodotto *</label>
                                    <select class="form-select" id="idProdotto" name="idProdotto" required>
                                        <option value="">Seleziona prodotto</option>
                                        <?php foreach ($prodotti_disponibili as $prodotto): ?>
                                        <option value="<?php echo $prodotto['idprodotto']; ?>">
                                            <?php echo htmlspecialchars($prodotto['nome'] . ' - ' . $prodotto['marca']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="taglia" class="form-label">Taglia *</label>
                                    <select class="form-select" id="taglia" name="taglia" required>
                                        <option value="">Seleziona...</option>
                                        <?php for($t = 37; $t <= 46; $t++): ?>
                                            <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="disponibilita" class="form-label">Disponibilità *</label>
                                    <input type="number" class="form-control" id="disponibilita" 
                                           name="disponibilita" min="0" required placeholder="Es. 50">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="costo" class="form-label">Costo (€) *</label>
                                    <input type="number" class="form-control" id="costo" 
                                           name="costo" step="0.01" min="0" required placeholder="Es. 49.99">
                                </div>
                                
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-plus me-2"></i>Aggiungi Fornitura
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabella forniture per taglia -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-boxes me-2"></i>Forniture Dettagliate per Taglia (<?php echo count($forniture_taglie); ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($forniture_taglie)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nessuna fornitura trovata per questo fornitore.</p>
                            </div>
                            <?php else: ?>
                            
                            <!-- Raggruppamento per prodotto -->
                            <?php 
                            $prodotti_raggruppati = [];
                            foreach ($forniture_taglie as $fornitura) {
                                $key = $fornitura['idprodotto'];
                                if (!isset($prodotti_raggruppati[$key])) {
                                    $prodotti_raggruppati[$key] = [
                                        'prodotto' => $fornitura,
                                        'taglie' => []
                                    ];
                                }
                                $prodotti_raggruppati[$key]['taglie'][] = $fornitura;
                            }
                            ?>
                            
                            <div class="accordion" id="fornitureAccordion">
                                <?php foreach ($prodotti_raggruppati as $key => $gruppo): ?>
                                <?php 
                                $prodotto = $gruppo['prodotto'];
                                $taglie = $gruppo['taglie'];
                                $totale_disponibilita = !empty($taglie) ? array_sum(array_column($taglie, 'disponibilità')) : 0;
                                $costo_medio = !empty($taglie) ? array_sum(array_column($taglie, 'costo')) / count($taglie) : 0;
                                ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo $key; ?>">
                                        <button class="accordion-button collapsed" type="button" 
                                                data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $key; ?>"
                                                aria-expanded="false" aria-controls="collapse<?php echo $key; ?>">
                                            <div class="d-flex justify-content-between w-100 me-3">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($prodotto['nome']); ?></strong>
                                                    <span class="ms-3 text-muted">
                                                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($prodotto['marca']); ?>
                                                    </span>
                                                    <span class="ms-3 text-muted">
                                                        <i class="fas fa-category me-1"></i><?php echo htmlspecialchars($prodotto['tipologia']); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="badge bg-info me-2">
                                                        <?php echo count($taglie); ?> taglie
                                                    </span>
                                                    <span class="badge bg-success me-2">
                                                        Tot: <?php echo $totale_disponibilita; ?>
                                                    </span>
                                                    <span class="badge bg-warning text-dark">
                                                        Medio: €<?php echo number_format($costo_medio, 2, ',', '.'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $key; ?>" class="accordion-collapse collapse" 
                                         data-bs-parent="#fornitureAccordion">
                                        <div class="accordion-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Taglia</th>
                                                            <th>Disponibilità</th>
                                                            <th>Costo Unitario</th>
                                                            <th>Valore Totale</th>
                                                            <th>Azioni</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($taglie as $taglia): ?>
                                                        <tr>
                                                            <td>
                                                                <span class="badge bg-secondary fs-6"><?php echo $taglia['taglia']; ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="badge <?php echo $taglia['disponibilità'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                                                    <?php echo $taglia['disponibilità']; ?> paia
                                                                </span>
                                                            </td>
                                                            <td>€<?php echo number_format($taglia['costo'], 2, ',', '.'); ?></td>
                                                            <td>
                                                                <strong>€<?php echo number_format($taglia['costo'] * $taglia['disponibilità'], 2, ',', '.'); ?></strong>
                                                            </td>
                                                            <td>
                                                                <a href="managerordini.php?riordina=<?php echo $taglia['idprodotto']; ?>&taglia=<?php echo $taglia['taglia']; ?>&negozio=1&nome=Prodotto+da+fornitore" 
                                                                   class="btn btn-sm btn-outline-primary" 
                                                                   target="_blank">
                                                                    <i class="fas fa-shopping-cart me-1"></i>Ordina
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot class="table-light">
                                                        <tr>
                                                            <th>TOTALE</th>
                                                            <th>
                                                                <span class="badge bg-primary"><?php echo $totale_disponibilita; ?> paia</span>
                                                            </th>
                                                            <th>Medio: €<?php echo number_format($costo_medio, 2, ',', '.'); ?></th>
                                                            <th>
                                                                <strong>€<?php echo number_format(array_sum(array_map(function($t) { return $t['costo'] * $t['disponibilità']; }, $taglie)), 2, ',', '.'); ?></strong>
                                                            </th>
                                                            <th></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php endif; ?>
    </main>

    <?php include_once ('lib/manager_footer.php'); ?>
</body>
</html>
