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
        if ($_POST['action'] == 'create_prodotto') {
            $nome = trim($_POST['nome']);
            $descrizione = trim($_POST['descrizione']);
            $marca = trim($_POST['marca']);
            $sesso = $_POST['sesso'];
            $tipologia = $_POST['tipologia'];
            
            if (!empty($nome) && !empty($marca) && !empty($sesso) && !empty($tipologia)) {
                $idProdotto = create_prodotto($nome, $descrizione, $marca, $sesso, $tipologia);
                if ($idProdotto) {
                    $message = "Prodotto creato con successo (ID: $idProdotto)";
                    
                    // Gestione upload foto
                    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                        $uploadDir = 'assets/prodotti/';
                        $fileName = $idProdotto . '.webp';
                        $uploadFile = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadFile)) {
                            $message .= " - Foto caricata con successo";
                        } else {
                            $message .= " - Errore nel caricamento della foto";
                        }
                    }
                } else {
                    $error = "Errore nella creazione del prodotto";
                }
            } else {
                $error = "Compilare tutti i campi obbligatori";
            }
        } elseif ($_POST['action'] == 'update_prodotto') {
            $idProdotto = $_POST['idProdotto'];
            $nome = trim($_POST['nome']);
            $descrizione = trim($_POST['descrizione']);
            $marca = trim($_POST['marca']);
            $sesso = $_POST['sesso'];
            $tipologia = $_POST['tipologia'];
            
            if (!empty($nome) && !empty($marca) && !empty($sesso) && !empty($tipologia)) {
                if (update_prodotto($idProdotto, $nome, $descrizione, $marca, $sesso, $tipologia)) {
                    $message = "Prodotto aggiornato con successo";
                    
                    // Gestione upload foto
                    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                        $uploadDir = 'assets/prodotti/';
                        $fileName = $idProdotto . '.webp';
                        $uploadFile = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadFile)) {
                            $message .= " - Foto aggiornata con successo";
                        } else {
                            $message .= " - Errore nell'aggiornamento della foto";
                        }
                    }
                    
                    // Redirect per rimuovere il parametro edit dall'URL
                    header("Location: managerprodotti.php?message=" . urlencode($message));
                    exit();
                } else {
                    $error = "Errore nell'aggiornamento del prodotto";
                }
            } else {
                $error = "Compilare tutti i campi obbligatori";
            }
        } elseif ($_POST['action'] == 'delete_prodotto') {
            $idProdotto = $_POST['idProdotto'];
            $result = delete_prodotto($idProdotto);
            
            if ($result['success']) {
                $message = $result['message'];
                // Elimina anche la foto se esiste
                $fotoPath = 'assets/prodotti/' . $idProdotto . '.webp';
                if (file_exists($fotoPath)) {
                    unlink($fotoPath);
                }
            } else {
                $error = $result['message'];
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

    // Gestione edit prodotto
    $editProdotto = null;
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $editProdotto = get_prodotto_by_id($_GET['edit']);
    }
    
    $prodotti = get_all_prodotti_manager();
    
    // Applicazione filtri
    $search = trim($_GET['search'] ?? '');
    $marcaFilter = $_GET['marca'] ?? '';
    $tipologiaFilter = $_GET['tipologia'] ?? '';
    
    if ($search || $marcaFilter || $tipologiaFilter) {
        $prodotti = array_filter($prodotti, function($prodotto) use ($search, $marcaFilter, $tipologiaFilter) {
            $matchesSearch = !$search || stripos($prodotto['nome'], $search) !== false;
            $matchesMarca = !$marcaFilter || $prodotto['marca'] === $marcaFilter;
            $matchesTipologia = !$tipologiaFilter || $prodotto['tipologia'] === $tipologiaFilter;
            
            return $matchesSearch && $matchesMarca && $matchesTipologia;
        });
    }
    
    // Estrai marche uniche per il filtro
    $marche = get_marche_options();
    
    // Opzioni per i form dal database
    $sessi = get_sessi_options();
    $tipologie = get_tipologie_options();
?>
<!doctype html>
<html lang="it">
<head>
    <title>Gestione Prodotti</title>
    <?php include_once ('lib/header.php'); ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include_once ('lib/manager_navigation.php'); ?>
    
    <main class="flex-grow-1">
        <div class="container my-4">
        <h1 class="mb-4">Gestione Prodotti</h1>
        
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
        
        <!-- Form creazione nuovo prodotto -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Crea Nuovo Prodotto</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_prodotto">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="marca" class="form-label">Marca *</label>
                                <input type="text" class="form-control" id="marca" name="marca" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sesso" class="form-label">Sesso *</label>
                                <select class="form-select" id="sesso" name="sesso" required>
                                    <option value="">Seleziona...</option>
                                    <?php foreach ($sessi as $s): ?>
                                        <option value="<?php echo $s; ?>"><?php echo ucfirst($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tipologia" class="form-label">Tipologia *</label>
                                <select class="form-select" id="tipologia" name="tipologia" required>
                                    <option value="">Seleziona...</option>
                                    <?php foreach ($tipologie as $t): ?>
                                        <option value="<?php echo $t; ?>"><?php echo ucfirst($t); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="descrizione" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="foto" class="form-label">Foto Prodotto</label>
                        <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                        <small class="text-muted">Formato consigliato: WEBP</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Crea Prodotto</button>
                </form>
            </div>
        </div>
        
        <!-- Form modifica prodotto (mostrato solo se in modalità edit) -->
        <?php if ($editProdotto): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Modifica Prodotto: <?php echo htmlspecialchars($editProdotto['nome']); ?></h5>
                <a href="managerprodotti.php" class="btn btn-sm btn-secondary">Annulla</a>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_prodotto">
                    <input type="hidden" name="idProdotto" value="<?php echo $editProdotto['idprodotto']; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editNome" class="form-label">Nome *</label>
                                <input type="text" class="form-control" id="editNome" name="nome" value="<?php echo htmlspecialchars($editProdotto['nome']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editMarca" class="form-label">Marca *</label>
                                <input type="text" class="form-control" id="editMarca" name="marca" value="<?php echo htmlspecialchars($editProdotto['marca']); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editSesso" class="form-label">Sesso *</label>
                                <select class="form-select" id="editSesso" name="sesso" required>
                                    <?php foreach ($sessi as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $editProdotto['sesso'] === $s ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($s); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editTipologia" class="form-label">Tipologia *</label>
                                <select class="form-select" id="editTipologia" name="tipologia" required>
                                    <?php foreach ($tipologie as $t): ?>
                                        <option value="<?php echo $t; ?>" <?php echo $editProdotto['tipologia'] === $t ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($t); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editDescrizione" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="editDescrizione" name="descrizione" rows="3"><?php echo htmlspecialchars($editProdotto['descrizione']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editFoto" class="form-label">Nuova Foto Prodotto</label>
                        <input type="file" class="form-control" id="editFoto" name="foto" accept="image/*">
                        <small class="text-muted">Lascia vuoto per mantenere la foto attuale</small>
                        <?php 
                        $fotoPath = 'assets/prodotti/' . $editProdotto['idprodotto'] . '.webp';
                        if (file_exists($fotoPath)): ?>
                            <div class="mt-2">
                                <img src="<?php echo $fotoPath; ?>" alt="Foto attuale" style="width: 100px; height: 100px; object-fit: cover;" class="rounded">
                                <small class="text-muted d-block">Foto attuale</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                    <a href="managerprodotti.php" class="btn btn-secondary ms-2">Annulla</a>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Lista prodotti -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">Lista Prodotti</h5>
                    </div>
                    <div class="col-auto">
                        <!-- Filtri di ricerca -->
                        <form method="GET" class="row g-2">
                            <?php if ($editProdotto): ?>
                                <input type="hidden" name="edit" value="<?php echo $editProdotto['idprodotto']; ?>">
                            <?php endif; ?>
                            <div class="col-auto">
                                <input type="text" class="form-control form-control-sm" 
                                       name="search" placeholder="Cerca prodotti..." 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       style="width: 200px;">
                            </div>
                            <div class="col-auto">
                                <select class="form-select form-select-sm" name="marca" style="width: 150px;">
                                    <option value="">Tutte le marche</option>
                                    <?php foreach ($marche as $marca): ?>
                                        <option value="<?php echo htmlspecialchars($marca); ?>" 
                                                <?php echo $marcaFilter === $marca ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($marca); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select form-select-sm" name="tipologia" style="width: 150px;">
                                    <option value="">Tutte le tipologie</option>
                                    <?php foreach ($tipologie as $t): ?>
                                        <option value="<?php echo $t; ?>" 
                                                <?php echo $tipologiaFilter === $t ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($t); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-primary">Filtra</button>
                                <a href="managerprodotti.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                            <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Foto</th>
                                <th>Nome</th>
                                <th>Marca</th>
                                <th>Sesso</th>
                                <th>Tipologia</th>
                                <th>Descrizione</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prodotti as $prodotto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prodotto['idprodotto']); ?></td>
                                <td>
                                    <?php 
                                    $fotoPath = 'assets/prodotti/' . $prodotto['idprodotto'] . '.webp';
                                    if (file_exists($fotoPath)): ?>
                                        <img src="<?php echo $fotoPath; ?>" alt="Foto" style="width: 50px; height: 50px; object-fit: cover;" class="rounded">
                                    <?php else: ?>
                                        <span class="text-muted">Nessuna foto</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($prodotto['nome']); ?></td>
                                <td><?php echo htmlspecialchars($prodotto['marca']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($prodotto['sesso'])); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($prodotto['tipologia'])); ?></td>
                                <td><?php echo htmlspecialchars(substr($prodotto['descrizione'], 0, 50)) . (strlen($prodotto['descrizione']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $prodotto['idprodotto']; ?>" class="btn btn-sm btn-warning me-2">
                                        Modifica
                                    </a>
                                    <?php if (isset($_GET['confirm_delete']) && $_GET['confirm_delete'] == $prodotto['idprodotto']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="delete_prodotto">
                                            <input type="hidden" name="idProdotto" value="<?php echo $prodotto['idprodotto']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Conferma Eliminazione</button>
                                        </form>
                                        <a href="?" class="btn btn-sm btn-secondary">Annulla</a>
                                    <?php else: ?>
                                        <a href="?confirm_delete=<?php echo $prodotto['idprodotto']; ?>" class="btn btn-sm btn-danger">Elimina</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include_once ('lib/manager_footer.php'); ?>
</body>
</html>
