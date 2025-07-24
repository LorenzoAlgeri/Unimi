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
    
    // Carica i dati necessari per la validazione dei form
    $clienti = get_all_clienti();
    $utenti = get_all_utenti();

    // Gestione form
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] == 'create_cliente') {
            $codiceFiscale = strtoupper(trim($_POST['codiceFiscale']));
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            
            if (!empty($codiceFiscale) && !empty($nome) && !empty($email)) {
                // Verifica che l'email esista come utente e non sia già associata a un cliente
                $emailExists = false;
                foreach ($utenti as $utente) {
                    if ($utente['email'] === $email && $utente['tipoutente'] === 'cliente') {
                        $emailExists = true;
                        break;
                    }
                }
                
                if (!$emailExists) {
                    $error = "Email non trovata tra gli utenti registrati di tipo cliente";
                } else {
                    // Verifica che l'email non sia già associata a un cliente
                    $emailInUse = false;
                    foreach ($clienti as $cliente) {
                        if ($cliente['email'] === $email) {
                            $emailInUse = true;
                            break;
                        }
                    }
                    
                    if ($emailInUse) {
                        $error = "Email già associata a un altro cliente";
                    } else {
                        if (create_cliente($codiceFiscale, $nome, $email)) {
                            $message = "Cliente creato con successo";
                        } else {
                            $error = "Errore nella creazione del cliente";
                        }
                    }
                }
            } else {
                $error = "Compilare tutti i campi obbligatori";
            }
        } elseif ($_POST['action'] == 'create_utente') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $tipoUtente = $_POST['tipoUtente'];
            
            if (!empty($email) && !empty($password) && !empty($tipoUtente)) {
                if (create_utente($email, $password, $tipoUtente)) {
                    $message = "Utente creato con successo";
                } else {
                    $error = "Errore nella creazione dell'utente (email già esistente?)";
                }
            } else {
                $error = "Compilare tutti i campi obbligatori";
            }
        } elseif ($_POST['action'] == 'delete_utente') {
            $email = $_POST['email'];
            
            if (!empty($email)) {
                $result = delete_utente($email);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['error'];
                }
            } else {
                $error = "Email non specificata";
            }
        } elseif ($_POST['action'] == 'ripristina_tessera') {
            $idTessera = $_POST['idTessera'];
            $idNuovoNegozio = $_POST['idNuovoNegozio'];
            
            if (!empty($idTessera) && !empty($idNuovoNegozio)) {
                $result = ripristina_tessera($idTessera, $idNuovoNegozio);
                if (strpos($result, 'successo') !== false) {
                    $message = $result;
                } else {
                    $error = $result;
                }
            } else {
                $error = "Selezionare tessera e negozio";
            }
        }
    }

    $negozi = get_all_negozi_manager();
    $tessereAttive = get_tessere_attive();
    $storicoTessere = get_storico_tessere();
    $tesserePremium = get_tessere_premium();
    $clientiTesserePerNegozio = get_clienti_tessere_per_negozio();
    
    // Raggruppa clienti per negozio
    $clientiPerNegozio = [];
    $negoziPerModal = get_negozi_attivi(); // Ottieni solo i negozi attivi per il modal
    $negoziNomi = [];
    
    // Crea un array associativo ID negozio -> nome negozio
    foreach ($negozi as $negozio) {
        $negoziNomi[$negozio['idnegozio']] = get_negozio_display_name($negozio['indirizzo']);
    }
    
    foreach ($clientiTesserePerNegozio as $cliente) {
        $idNegozio = $cliente['idnegozio'];
        if (!isset($clientiPerNegozio[$idNegozio])) {
            $clientiPerNegozio[$idNegozio] = [];
        }
        $clientiPerNegozio[$idNegozio][] = $cliente;
    }
    
    // Determina se gli accordion Bootstrap devono essere aperti
    $managersOpen = isset($_GET['managers_open']) || isset($_GET['confirm_delete_manager']);
    $clientsOpen = isset($_GET['clients_open']) || isset($_GET['confirm_delete_user']);
    
    // Conta i manager per la protezione dell'eliminazione
    $managersCount = count_managers();
?>
<!doctype html>
<html lang="it">
<head>
    <title>Gestione Clienti</title>
    <?php include_once ('lib/header.php'); ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include_once ('lib/manager_navigation.php'); ?>
    
    <main class="flex-grow-1">
        <div class="container my-4">
        <h1 class="mb-4">Gestione Clienti</h1>
        
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
            <!-- Form creazione cliente -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Crea Nuovo Cliente</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create_cliente">
                            <div class="mb-3">
                                <label for="codiceFiscale" class="form-label">Codice Fiscale *</label>
                                <input type="text" class="form-control" id="codiceFiscale" name="codiceFiscale" required maxlength="16">
                            </div>
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email (utente cliente esistente) *</label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="Inserisci email di un utente esistente di tipo cliente">
                                <small class="text-muted">L'email deve corrispondere a un utente già registrato come cliente</small>
                            </div>
                            <button type="submit" class="btn btn-primary">Crea Cliente</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Form creazione utente -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Crea Nuovo Utente</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create_utente">
                            <div class="mb-3">
                                <label for="emailUtente" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="emailUtente" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="tipoUtente" class="form-label">Tipo Utente *</label>
                                <select class="form-select" id="tipoUtente" name="tipoUtente" required>
                                    <option value="">Seleziona...</option>
                                    <option value="cliente">Cliente</option>
                                    <option value="manager">Manager</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success">Crea Utente</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Liste utenti con accordion -->
        <div class="accordion mb-4" id="userListsAccordion">
            <!-- Lista utenti Manager -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="managersHeading">
                    <button class="accordion-button <?php echo $managersOpen ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#managersCollapse" aria-expanded="<?php echo $managersOpen ? 'true' : 'false'; ?>" aria-controls="managersCollapse">
                        <i class="fas fa-user-shield me-2"></i>
                        <strong>Lista Utenti Manager</strong>
                        <span class="badge bg-danger ms-2"><?php echo $managersCount; ?> manager</span>
                        <?php if ($managersCount == 1): ?>
                            <span class="badge bg-warning ms-1">Ultimo manager</span>
                        <?php endif; ?>
                    </button>
                </h2>
                <div id="managersCollapse" class="accordion-collapse collapse <?php echo $managersOpen ? 'show' : ''; ?>" aria-labelledby="managersHeading" data-bs-parent="#userListsAccordion">
                    <div class="accordion-body">
                        <?php if ($managersCount == 1): ?>
                            <div class="alert alert-warning alert-sm mb-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Attenzione:</strong> Questo è l'ultimo manager del sistema. L'eliminazione non è consentita per motivi di sicurezza.
                            </div>
                        <?php endif; ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-striped table-sm">
                                <thead class="sticky-top bg-light">
                                    <tr>
                                        <th>Email</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($utenti as $utente): ?>
                                        <?php if ($utente['tipoutente'] == 'manager'): ?>
                                        <?php 
                                        $isCurrentUser = ($_SESSION['user'] == $utente['email']);
                                        $canDelete = ($managersCount > 1 && !$isCurrentUser);
                                        ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($utente['email']); ?>
                                                <?php if ($isCurrentUser): ?>
                                                    <span class="badge bg-info ms-1">Tu</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($canDelete): ?>
                                                    <?php if (isset($_GET['confirm_delete_manager']) && $_GET['confirm_delete_manager'] == $utente['email']): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="delete_utente">
                                                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($utente['email']); ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">Conferma Eliminazione</button>
                                                        </form>
                                                        <a href="?managers_open=1" class="btn btn-sm btn-secondary">Annulla</a>
                                                    <?php else: ?>
                                                        <a href="?confirm_delete_manager=<?php echo urlencode($utente['email']); ?>&managers_open=1#managersCollapse" class="btn btn-sm btn-danger">Elimina</a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($managersCount <= 1): ?>
                                                        <span class="text-muted small">Ultimo manager - Non eliminabile</span>
                                                    <?php elseif ($isCurrentUser): ?>
                                                        <span class="text-muted small">Account corrente - Non eliminabile</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Lista utenti Cliente unificata -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="clientsHeading">
                    <button class="accordion-button <?php echo $clientsOpen ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#clientsCollapse" aria-expanded="<?php echo $clientsOpen ? 'true' : 'false'; ?>" aria-controls="clientsCollapse">
                        <i class="fas fa-users me-2"></i>
                        <strong>Lista Completa Utenti/Clienti</strong>
                        <?php 
                        $clientsCount = count(array_filter($utenti, function($u) { return $u['tipoutente'] == 'cliente'; })); 
                        $clientsConnected = 0;
                        foreach ($utenti as $utente) {
                            if ($utente['tipoutente'] == 'cliente') {
                                foreach ($clienti as $cliente) {
                                    if ($cliente['email'] === $utente['email']) {
                                        $clientsConnected++;
                                        break;
                                    }
                                }
                            }
                        }
                        ?>
                        <span class="badge bg-primary ms-2"><?php echo $clientsCount; ?> utenti</span>
                        <span class="badge bg-success ms-1"><?php echo $clientsConnected; ?> con profilo</span>
                        <?php if ($clientsCount - $clientsConnected > 0): ?>
                            <span class="badge bg-warning ms-1"><?php echo $clientsCount - $clientsConnected; ?> solo utente</span>
                        <?php endif; ?>
                    </button>
                </h2>
                <div id="clientsCollapse" class="accordion-collapse collapse <?php echo $clientsOpen ? 'show' : ''; ?>" aria-labelledby="clientsHeading" data-bs-parent="#userListsAccordion">
                    <div class="accordion-body">
                        <?php if ($clientsCount - $clientsConnected > 0): ?>
                            <div class="alert alert-info alert-sm mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Info:</strong> <?php echo $clientsCount - $clientsConnected; ?> utenti non hanno ancora un profilo cliente completo.
                            </div>
                        <?php endif; ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-striped table-sm">
                                <thead class="sticky-top bg-light">
                                    <tr>
                                        <th>Email</th>
                                        <th>Nome Cliente</th>
                                        <th>Codice Fiscale</th>
                                        <th>Stato</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($utenti as $utente): ?>
                                        <?php if ($utente['tipoutente'] == 'cliente'): ?>
                                        <?php 
                                        $clienteCollegato = null;
                                        foreach ($clienti as $cliente) {
                                            if ($cliente['email'] === $utente['email']) {
                                                $clienteCollegato = $cliente;
                                                break;
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($utente['email']); ?></td>
                                            <td>
                                                <?php if ($clienteCollegato): ?>
                                                    <?php echo htmlspecialchars($clienteCollegato['nome']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted"><em>Non impostato</em></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($clienteCollegato): ?>
                                                    <?php echo htmlspecialchars($clienteCollegato['codicefiscale']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted"><em>Non impostato</em></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($clienteCollegato): ?>
                                                    <span class="badge bg-success">Profilo Completo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Solo Utente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($_GET['confirm_delete_user']) && $_GET['confirm_delete_user'] == $utente['email']): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="delete_utente">
                                                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($utente['email']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Conferma Eliminazione</button>
                                                    </form>
                                                    <a href="?clients_open=1" class="btn btn-sm btn-secondary">Annulla</a>
                                                <?php else: ?>
                                                    <a href="?confirm_delete_user=<?php echo urlencode($utente['email']); ?>&clients_open=1#clientsCollapse" class="btn btn-sm btn-danger">Elimina</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sezioni Tessere e Clienti -->
        <div class="accordion mb-4" id="tessereAccordion">
            <!-- Clienti Premium -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="premiumHeading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#premiumCollapse" aria-expanded="false" aria-controls="premiumCollapse">
                        <i class="fas fa-crown me-2"></i>
                        <strong>Clienti Premium (>300 punti)</strong>
                        <span class="badge bg-warning ms-2"><?php echo count($tesserePremium); ?></span>
                    </button>
                </h2>
                <div id="premiumCollapse" class="accordion-collapse collapse" aria-labelledby="premiumHeading" data-bs-parent="#tessereAccordion">
                    <div class="accordion-body">
                        <?php if (empty($tesserePremium)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nessun cliente premium al momento</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID Tessera</th>
                                            <th>Cliente</th>
                                            <th>Codice Fiscale</th>
                                            <th>Punti</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tesserePremium as $tessera): ?>
                                        <tr>
                                            <td><?php echo $tessera['idtessera']; ?></td>
                                            <td><?php echo htmlspecialchars($tessera['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($tessera['codicefiscale']); ?></td>
                                            <td>
                                                <strong class="text-warning"><?php echo $tessera['saldopunti']; ?> punti</strong>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tessere per Negozio -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="negozioHeading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#negozioCollapse" aria-expanded="false" aria-controls="negozioCollapse">
                        <i class="fas fa-store me-2"></i>
                        <strong>Tessere per Negozio</strong>
                        <span class="badge bg-primary ms-2"><?php echo count($clientiPerNegozio); ?> negozi</span>
                    </button>
                </h2>
                <div id="negozioCollapse" class="accordion-collapse collapse" aria-labelledby="negozioHeading" data-bs-parent="#tessereAccordion">
                    <div class="accordion-body">
                        <?php if (empty($clientiPerNegozio)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nessuna tessera attiva</p>
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="subNegozioAccordion">
                                <?php foreach ($clientiPerNegozio as $idNegozio => $clienti): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingSubNegozio<?php echo $idNegozio; ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSubNegozio<?php echo $idNegozio; ?>">
                                                <div class="d-flex justify-content-between w-100 me-3">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($negoziNomi[$idNegozio] ?? "Negozio ID: $idNegozio"); ?></strong>
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-primary"><?php echo count($clienti); ?> tessere</span>
                                                        <span class="badge bg-success">
                                                            <?php 
                                                            $totalePunti = array_sum(array_column($clienti, 'saldopunti'));
                                                            echo $totalePunti; ?> punti totali
                                                        </span>
                                                    </div>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapseSubNegozio<?php echo $idNegozio; ?>" class="accordion-collapse collapse" data-bs-parent="#subNegozioAccordion">
                                            <div class="accordion-body">
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>ID Tessera</th>
                                                                <th>Cliente</th>
                                                                <th>Codice Fiscale</th>
                                                                <th>Punti</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($clienti as $cliente): ?>
                                                            <tr class="<?php echo $cliente['saldopunti'] > 300 ? 'table-warning' : ''; ?>">
                                                                <td><?php echo $cliente['idtessera']; ?></td>
                                                                <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                                                <td><?php echo htmlspecialchars($cliente['codicefiscale']); ?></td>
                                                                <td>
                                                                    <?php echo $cliente['saldopunti']; ?>
                                                                    <?php if ($cliente['saldopunti'] > 300): ?>
                                                                        <span class="badge bg-warning ms-1">Premium</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
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
            
            <!-- Tessere Attive -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="attiveHeading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#attiveCollapse" aria-expanded="false" aria-controls="attiveCollapse">
                        <i class="fas fa-id-card me-2"></i>
                        <strong>Tutte le Tessere Attive</strong>
                        <span class="badge bg-success ms-2"><?php echo count($tessereAttive); ?></span>
                    </button>
                </h2>
                <div id="attiveCollapse" class="accordion-collapse collapse" aria-labelledby="attiveHeading" data-bs-parent="#tessereAccordion">
                    <div class="accordion-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID Tessera</th>
                                        <th>Cliente</th>
                                        <th>Codice Fiscale</th>
                                        <th>Data Richiesta</th>
                                        <th>Negozio</th>
                                        <th>Punti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tessereAttive as $tessera): ?>
                                    <tr class="<?php echo $tessera['saldopunti'] > 300 ? 'table-warning' : ''; ?>">
                                        <td><?php echo htmlspecialchars($tessera['idtessera']); ?></td>
                                        <td><?php echo htmlspecialchars($tessera['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($tessera['codicefiscale']); ?></td>
                                        <td><?php echo htmlspecialchars($tessera['datarichiesta']); ?></td>
                                        <td><?php echo htmlspecialchars($tessera['responsabile']); ?></td>
                                        <td>
                                            <strong><?php echo $tessera['saldopunti']; ?></strong>
                                            <?php if ($tessera['saldopunti'] > 300): ?>
                                                <span class="badge bg-warning ms-1">Premium</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Storico tessere (sempre visibile in fondo) -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Storico Tessere (Negozi Chiusi)
                </h5>
                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#ripristinaTesseraModal">
                    <i class="fas fa-redo me-2"></i>Ripristina Tessera
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID Tessera</th>
                                <th>Cliente</th>
                                <th>Codice Fiscale</th>
                                <th>Data Richiesta</th>
                                <th>Negozio Originale</th>
                                <th>Punti</th>
                                <th>Stato</th>
                                <th>Negozio Trasferito</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($storicoTessere as $tessera): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tessera['idtessera']); ?></td>
                                <td><?php echo htmlspecialchars($tessera['nome']); ?></td>
                                <td><?php echo htmlspecialchars($tessera['codicefiscale']); ?></td>
                                <td><?php echo htmlspecialchars($tessera['datarichiesta']); ?></td>
                                <td>ID: <?php echo htmlspecialchars($tessera['idnegozio']); ?></td>
                                <td><?php echo $tessera['saldopunti']; ?></td>
                                <td>
                                    <?php if ($tessera['idnegoziotrasferito']): ?>
                                        <span class="badge bg-success">Trasferita</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Da ripristinare</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $tessera['idnegoziotrasferito'] ? 'ID: ' . $tessera['idnegoziotrasferito'] : '-'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal per ripristino tessera -->
    <div class="modal fade" id="ripristinaTesseraModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ripristina Tessera</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="ripristina_tessera">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="idTessera" class="form-label">Tessera da ripristinare *</label>
                            <select class="form-select" id="idTessera" name="idTessera" required>
                                <option value="">Seleziona...</option>
                                <?php foreach ($storicoTessere as $tessera): ?>
                                    <?php if (!$tessera['idnegoziotrasferito']): ?>
                                        <option value="<?php echo $tessera['idtessera']; ?>">
                                            ID <?php echo $tessera['idtessera']; ?> - <?php echo htmlspecialchars($tessera['nome']); ?> (<?php echo $tessera['saldopunti']; ?> punti)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="idNuovoNegozio" class="form-label">Nuovo negozio *</label>
                            <select class="form-select" id="idNuovoNegozio" name="idNuovoNegozio" required>
                                <option value="">Seleziona...</option>
                                <?php foreach ($negoziPerModal as $negozio): ?>
                                    <option value="<?php echo htmlspecialchars($negozio['idnegozio']); ?>">
                                        <?php echo htmlspecialchars(get_negozio_display_name($negozio['indirizzo'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">Ripristina Tessera</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include_once ('lib/manager_footer.php'); ?>
</body>
</html>
