<?php
    ini_set ("display_errors", "On");
	ini_set("error_reporting", E_ALL);
	include_once ('lib/functions.php');
    include_once ('lib/manager_components.php');

    session_start();

    // Gestione logout
    if (isset($_GET['log']) && $_GET['log'] == 'del') {
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }

    if (!isset($_SESSION['user']) || !isset($_SESSION['ruolo']) || $_SESSION['ruolo'] != 'manager') {
        header("Location: login.php");
        exit();
    }
?>
<!doctype html>
<html lang="it">
<head>
    <title>Dashboard Manager</title>
    <?php include_once ('lib/header.php'); ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include_once ('lib/manager_navigation.php'); ?>
    
    <main class="flex-grow-1">
        <div class="container my-4">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mb-4">Dashboard Manager</h1>
                <p class="lead">Benvenuto nel pannello di controllo manager, <?php echo $_SESSION['user']; ?>!</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-store fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Gestione Negozi</h5>
                        <p class="card-text">Visualizza, crea e gestisci i negozi e i loro orari</p>
                        <a href="managernegozi.php" class="btn btn-primary">Gestisci Negozi</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-box fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Gestione Prodotti</h5>
                        <p class="card-text">Inserisci, modifica ed elimina prodotti dal catalogo</p>
                        <a href="managerprodotti.php" class="btn btn-success">Gestisci Prodotti</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-warehouse fa-3x text-info mb-3"></i>
                        <h5 class="card-title">Gestione Disponibilità</h5>
                        <p class="card-text">Monitora e aggiorna le scorte per negozio</p>
                        <a href="managerdisponibilita.php" class="btn btn-info">Gestisci Disponibilità</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Gestione Clienti</h5>
                        <p class="card-text">Visualizza clienti, utenti e gestisci le tessere fedeltà</p>
                        <a href="managerclienti.php" class="btn btn-warning">Gestisci Clienti</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart fa-3x text-danger mb-3"></i>
                        <h5 class="card-title">Gestione Ordini</h5>
                        <p class="card-text">Visualizza e gestisci gli ordini ai fornitori</p>
                        <a href="managerordini.php" class="btn btn-danger">Gestisci Ordini</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-truck fa-3x text-secondary mb-3"></i>
                        <h5 class="card-title">Gestione Fornitori</h5>
                        <p class="card-text">Gestisci fornitori e visualizza i prodotti forniti</p>
                        <a href="managerfornitori.php" class="btn btn-secondary">Gestisci Fornitori</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-12 col-lg-6 mx-auto">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-3x text-dark mb-3"></i>
                        <h5 class="card-title">Statistiche</h5>
                        <p class="card-text">Visualizza statistiche di vendita e dati analitici</p>
                        <a href="managerstats.php" class="btn btn-dark">Visualizza Statistiche</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include_once ('lib/manager_footer.php'); ?>
</body>
</html>