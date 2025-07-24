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

    // Gestione filtri e aggiornamento dati
    $periodo_bilancio = $_POST['periodo_bilancio'] ?? $_GET['periodo_bilancio'] ?? '30 days';
    $periodo_fatture = $_POST['periodo_fatture'] ?? $_GET['periodo_fatture'] ?? '30 days';
    $periodo_rifornimenti = $_POST['periodo_rifornimenti'] ?? $_GET['periodo_rifornimenti'] ?? '30 days';
    
    // Gestione date personalizzate
    $data_inizio_bilancio = $_POST['data_inizio_bilancio'] ?? $_GET['data_inizio_bilancio'] ?? '';
    $data_fine_bilancio = $_POST['data_fine_bilancio'] ?? $_GET['data_fine_bilancio'] ?? '';
    $data_inizio_fatture = $_POST['data_inizio_fatture'] ?? $_GET['data_inizio_fatture'] ?? '';
    $data_fine_fatture = $_POST['data_fine_fatture'] ?? $_GET['data_fine_fatture'] ?? '';
    $data_inizio_rifornimenti = $_POST['data_inizio_rifornimenti'] ?? $_GET['data_inizio_rifornimenti'] ?? '';
    $data_fine_rifornimenti = $_POST['data_fine_rifornimenti'] ?? $_GET['data_fine_rifornimenti'] ?? '';
    
    $periodi_validi = [
        '1 day' => '1 Giorno',
        '7 days' => '1 Settimana', 
        '30 days' => '1 Mese',
        '1 year' => '1 Anno',
        '10 years' => 'Sempre',
        'custom' => 'Personalizzato'
    ];
    
    if (!array_key_exists($periodo_bilancio, $periodi_validi)) {
        $periodo_bilancio = '30 days';
    }
    if (!array_key_exists($periodo_fatture, $periodi_validi)) {
        $periodo_fatture = '30 days';
    }
    if (!array_key_exists($periodo_rifornimenti, $periodi_validi)) {
        $periodo_rifornimenti = '30 days';
    }
    
    // Reset delle date personalizzate quando si seleziona un periodo predefinito
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Se è stato selezionato un periodo predefinito, resetta le date personalizzate corrispondenti
        if (isset($_POST['periodo_bilancio']) && !isset($_POST['data_inizio_bilancio'])) {
            $data_inizio_bilancio = '';
            $data_fine_bilancio = '';
        }
        if (isset($_POST['periodo_fatture']) && !isset($_POST['data_inizio_fatture'])) {
            $data_inizio_fatture = '';
            $data_fine_fatture = '';
        }
        if (isset($_POST['periodo_rifornimenti']) && !isset($_POST['data_inizio_rifornimenti'])) {
            $data_inizio_rifornimenti = '';
            $data_fine_rifornimenti = '';
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] == 'refresh_data') {
            if (refresh_materialized_views()) {
                $message = "Dati aggiornati con successo";
            } else {
                $error = "Errore durante l'aggiornamento dei dati";
            }
        }
    }

    // Ottieni i dati per le statistiche
    // Fatture di vendita - priorità alle date personalizzate
    if ($data_inizio_fatture && $data_fine_fatture) {
        $fattureVendita = get_fatture_vendita_per_date($data_inizio_fatture, $data_fine_fatture);
    } else {
        $fattureVendita = get_fatture_vendita_per_periodo($periodo_fatture);
    }
    
    // Rifornimenti magazzino - priorità alle date personalizzate
    if ($data_inizio_rifornimenti && $data_fine_rifornimenti) {
        $rifornimentiMagazzino = get_rifornimenti_magazzino_per_date($data_inizio_rifornimenti, $data_fine_rifornimenti);
    } else {
        $rifornimentiMagazzino = get_rifornimenti_magazzino($periodo_rifornimenti);
    }
    
    // Bilancio - priorità alle date personalizzate  
    if ($data_inizio_bilancio && $data_fine_bilancio) {
        $bilancio = get_bilancio_per_date($data_inizio_bilancio, $data_fine_bilancio);
    } else {
        $bilancio = get_bilancio_per_periodo($periodo_bilancio);
    }
    
    // Calcola ricavi ultimi 30 giorni dalle fatture di vendita (sempre ultimi 30 giorni per la card)
    $fattureRecenti = get_fatture_vendita_per_periodo('30 days');
    $ricaviUltimi30Giorni = 0;
    foreach ($fattureRecenti as $fattura) {
        $ricaviUltimi30Giorni += $fattura['prezzo'] * $fattura['quantità'];
    }
?>
<!doctype html>
<html lang="it">
<head>
    <title>Statistiche e Analisi</title>
    <?php include_once ('lib/header.php'); ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include_once ('lib/manager_navigation.php'); ?>
    
    <main class="flex-grow-1">
        <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Statistiche e Analisi</h1>
            <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="refresh_data">
                <button type="submit" class="btn btn-info" title="Aggiorna dati">
                    <i class="fas fa-sync-alt"></i> Aggiorna Dati
                </button>
            </form>
        </div>
        
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
        
        <!-- Cards riassuntive -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-euro-sign fa-2x text-success mb-2"></i>
                        <h5 class="card-title">Ricavi Vendite 30gg</h5>
                        <h2 class="text-success">€ <?php echo number_format($ricaviUltimi30Giorni, 2); ?></h2>
                        <small class="text-muted">Da fatture di vendita</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                        <h5 class="card-title">Bilancio <?php echo $periodi_validi[$periodo_bilancio]; ?></h5>
                        <h2 class="<?php echo $bilancio['bilancio'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            €ₓ <?php echo number_format($bilancio['bilancio'], 2); ?>
                        </h2>
                        <small class="text-muted">Entrate - Uscite</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sezione Bilancio -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-balance-scale me-2"></i>
                        Bilancio Aziendale
                    </h5>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtri bilancio -->
                <div class="bg-light rounded p-3 mb-3">
                    <form method="POST" id="form_bilancio">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Periodo Predefinito:</label>
                                <select name="periodo_bilancio" class="form-select">
                                    <?php foreach ($periodi_validi as $valore => $label): ?>
                                        <?php if ($valore != 'custom'): ?>
                                        <option value="<?php echo $valore; ?>" <?php echo $periodo_bilancio == $valore ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Da (opzionale):</label>
                                <input type="date" name="data_inizio_bilancio" class="form-control" value="<?php echo $data_inizio_bilancio; ?>" placeholder="Data inizio">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">A (opzionale):</label>
                                <input type="date" name="data_fine_bilancio" class="form-control" value="<?php echo $data_fine_bilancio; ?>" placeholder="Data fine">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">Filtra</button>
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php if ($data_inizio_bilancio && $data_fine_bilancio): ?>
                                <i class="fas fa-calendar-alt me-1"></i>
                                <strong>Periodo personalizzato attivo:</strong> dal <?php echo date('d/m/Y', strtotime($data_inizio_bilancio)); ?> al <?php echo date('d/m/Y', strtotime($data_fine_bilancio)); ?>
                                <a href="?periodo_bilancio=<?php echo $periodo_bilancio; ?>" class="ms-2 text-decoration-none">
                                    <i class="fas fa-times"></i> Rimuovi date personalizzate
                                </a>
                            <?php else: ?>
                                Seleziona un periodo predefinito oppure specifica date personalizzate (le date hanno priorità)
                            <?php endif; ?>
                        </small>
                    </form>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-arrow-up fa-2x text-success mb-2"></i>
                                <h5 class="text-success">Entrate</h5>
                                <h3 class="text-success">€ <?php echo number_format($bilancio['entrate'], 2); ?></h3>
                                <small class="text-muted">Vendite ai clienti</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-arrow-down fa-2x text-danger mb-2"></i>
                                <h5 class="text-danger">Uscite</h5>
                                <h3 class="text-danger">€ <?php echo number_format($bilancio['uscite'], 2); ?></h3>
                                <small class="text-muted">Acquisti da fornitori</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-balance-scale fa-2x <?php echo $bilancio['bilancio'] >= 0 ? 'text-success' : 'text-danger'; ?> mb-2"></i>
                                <h5 class="<?php echo $bilancio['bilancio'] >= 0 ? 'text-success' : 'text-danger'; ?>">Bilancio</h5>
                                <h3 class="<?php echo $bilancio['bilancio'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    €ₓ <?php echo number_format($bilancio['bilancio'], 2); ?>
                                </h3>
                                <small class="text-muted">
                                    <?php echo $bilancio['bilancio'] >= 0 ? 'Profitto' : 'Perdita'; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fatture di vendita recenti -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-receipt me-2"></i>
                    Fatture di Vendita
                    <?php if ($data_inizio_fatture && $data_fine_fatture): ?>
                        (dal <?php echo date('d/m/Y', strtotime($data_inizio_fatture)); ?> al <?php echo date('d/m/Y', strtotime($data_fine_fatture)); ?>)
                    <?php else: ?>
                        (<?php echo $periodi_validi[$periodo_fatture]; ?>)
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <!-- Filtri fatture -->
                <div class="bg-light rounded p-3 mb-3">
                    <form method="POST" id="form_fatture">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Periodo Predefinito:</label>
                                <select name="periodo_fatture" class="form-select">
                                    <?php foreach ($periodi_validi as $valore => $label): ?>
                                        <?php if ($valore != 'custom'): ?>
                                        <option value="<?php echo $valore; ?>" <?php echo $periodo_fatture == $valore ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Da (opzionale):</label>
                                <input type="date" name="data_inizio_fatture" class="form-control" value="<?php echo $data_inizio_fatture; ?>" placeholder="Data inizio">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">A (opzionale):</label>
                                <input type="date" name="data_fine_fatture" class="form-control" value="<?php echo $data_fine_fatture; ?>" placeholder="Data fine">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">Filtra</button>
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php if ($data_inizio_fatture && $data_fine_fatture): ?>
                                <i class="fas fa-calendar-alt me-1"></i>
                                <strong>Periodo personalizzato attivo:</strong> dal <?php echo date('d/m/Y', strtotime($data_inizio_fatture)); ?> al <?php echo date('d/m/Y', strtotime($data_fine_fatture)); ?>
                                <a href="?periodo_fatture=<?php echo $periodo_fatture; ?>" class="ms-2 text-decoration-none">
                                    <i class="fas fa-times"></i> Rimuovi date personalizzate
                                </a>
                            <?php else: ?>
                                Seleziona un periodo predefinito oppure specifica date personalizzate (le date hanno priorità)
                            <?php endif; ?>
                        </small>
                    </form>
                </div>
                
                <?php if (empty($fattureVendita)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nessuna fattura di vendita nel periodo selezionato</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Prodotto</th>
                                    <th>Taglia</th>
                                    <th>Quantità</th>
                                    <th>Prezzo Unit.</th>
                                    <th>Totale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totaleFatture = 0;
                                $fattureProcessate = array(); // Per evitare di contare la stessa fattura più volte
                                
                                foreach ($fattureVendita as $fattura): 
                                    $totaleRiga = $fattura['prezzo'] * $fattura['quantità'];
                                    
                                    // Aggiungi il totale della fattura solo una volta per ogni fattura
                                    if (!in_array($fattura['idfattura'], $fattureProcessate)) {
                                        $totaleFatture += $fattura['totale']; // Usa il totale pagato della fattura
                                        $fattureProcessate[] = $fattura['idfattura'];
                                    }
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($fattura['dataacquisto'])); ?></td>
                                    <td><?php echo htmlspecialchars($fattura['nome_cliente'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($fattura['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($fattura['taglia']); ?></td>
                                    <td><?php echo $fattura['quantità']; ?></td>
                                    <td>€ <?php echo number_format($fattura['prezzo'], 2); ?></td>
                                    <td>€ <?php echo number_format($totaleRiga, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <td colspan="6"><strong>Totale Vendite</strong></td>
                                    <td><strong>€ <?php echo number_format($totaleFatture, 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Rifornimenti magazzino -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-truck me-2"></i>
                    Rifornimenti Magazzino
                    <?php if ($data_inizio_rifornimenti && $data_fine_rifornimenti): ?>
                        (dal <?php echo date('d/m/Y', strtotime($data_inizio_rifornimenti)); ?> al <?php echo date('d/m/Y', strtotime($data_fine_rifornimenti)); ?>)
                    <?php else: ?>
                        (<?php echo $periodi_validi[$periodo_rifornimenti]; ?>)
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <!-- Filtri rifornimenti -->
                <div class="bg-light rounded p-3 mb-3">
                    <form method="POST" id="form_rifornimenti">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Periodo Predefinito:</label>
                                <select name="periodo_rifornimenti" class="form-select">
                                    <?php foreach ($periodi_validi as $valore => $label): ?>
                                        <?php if ($valore != 'custom'): ?>
                                        <option value="<?php echo $valore; ?>" <?php echo $periodo_rifornimenti == $valore ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Da (opzionale):</label>
                                <input type="date" name="data_inizio_rifornimenti" class="form-control" value="<?php echo $data_inizio_rifornimenti; ?>" placeholder="Data inizio">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">A (opzionale):</label>
                                <input type="date" name="data_fine_rifornimenti" class="form-control" value="<?php echo $data_fine_rifornimenti; ?>" placeholder="Data fine">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">Filtra</button>
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php if ($data_inizio_rifornimenti && $data_fine_rifornimenti): ?>
                                <i class="fas fa-calendar-alt me-1"></i>
                                <strong>Periodo personalizzato attivo:</strong> dal <?php echo date('d/m/Y', strtotime($data_inizio_rifornimenti)); ?> al <?php echo date('d/m/Y', strtotime($data_fine_rifornimenti)); ?>
                                <a href="?periodo_rifornimenti=<?php echo $periodo_rifornimenti; ?>" class="ms-2 text-decoration-none">
                                    <i class="fas fa-times"></i> Rimuovi date personalizzate
                                </a>
                            <?php else: ?>
                                Seleziona un periodo predefinito oppure specifica date personalizzate (le date hanno priorità)
                            <?php endif; ?>
                        </small>
                    </form>
                </div>
                
                <?php if (empty($rifornimentiMagazzino)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nessun rifornimento nel periodo selezionato</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Fornitore</th>
                                    <th>Prodotto</th>
                                    <th>Taglia</th>
                                    <th>Quantità</th>
                                    <th>Prezzo Unit.</th>
                                    <th>Totale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totaleRifornimenti = 0;
                                foreach ($rifornimentiMagazzino as $rifornimento): 
                                    $totaleRiga = $rifornimento['prezzo'] * $rifornimento['quantità'];
                                    $totaleRifornimenti += $totaleRiga;
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($rifornimento['dataconsegna'])); ?></td>
                                    <td><?php echo htmlspecialchars($rifornimento['nome_fornitore'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($rifornimento['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($rifornimento['taglia']); ?></td>
                                    <td><?php echo $rifornimento['quantità']; ?></td>
                                    <td>€ <?php echo number_format($rifornimento['prezzo'], 2); ?></td>
                                    <td>€ <?php echo number_format($totaleRiga, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <td colspan="6"><strong>Totale Rifornimenti</strong></td>
                                    <td><strong>€ <?php echo number_format($totaleRifornimenti, 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </main>

    <?php include_once ('lib/manager_footer.php'); ?>
</body>
</html>
