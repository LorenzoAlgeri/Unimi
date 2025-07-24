<?php
    ini_set("display_errors", "On");
    ini_set("error_reporting", E_ALL);

    include_once('lib/functions.php');
    include_once('lib/shop_components.php');

    session_start();

    // Gestione logout
    if (isset($_GET['log']) && $_GET['log'] == 'del') {
      session_unset();
      session_destroy();
      header("Location: login.php");
      exit();
    }

    // Controllo autenticazione
    shop_check_authentication();
?>

<!doctype html>
<html lang="it">
<head>
    <title>Home</title>
    <?php include_once('lib/header.php'); ?>
    <link href="css/shoepal.css" rel="stylesheet">
</head>
<body>
    <?php include_once('lib/cliente_navigation.php'); ?>

    <div class="container-fluid">
        <!-- Introduzione -->
        <div class="jumbotron jumbotron-fluid bg-primary text-white text-center py-5 mb-5">
            <div class="container">
                <h1 class="display-4 fw-bold">
                    <i class="fas fa-home me-3"></i>Benvenuto in ShoePal
                </h1>
                <p class="lead">Il tuo negozio di scarpe di fiducia</p>
                <p class="mb-0">Ciao <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong>!</p>
            </div>
        </div>

        <!-- Dashboard principale -->
        <div class="container mb-5">
            <div class="row g-4">
                <!-- Card Acquista -->
                <div class="col-lg-6 col-md-6">
                    <div class="card text-center h-100 border-0 shadow" 
                         style="min-height: 200px; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                         onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.2)';"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                        <!-- Javascript inline utilizzato per l'effetto hover -->
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="text-primary mb-3" style="font-size: 3rem;">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h3 class="card-title">Acquista</h3>
                            <p class="card-text">Esplora il nostro catalogo e acquista le scarpe che preferisci</p>
                            <a href="shop.php" class="btn btn-primary mt-3 stretched-link">Vai al Catalogo</a>
                        </div>
                    </div>
                </div>

                <!-- Card Negozi -->
                <div class="col-lg-6 col-md-6">
                    <div class="card text-center h-100 border-0 shadow" 
                         style="min-height: 200px; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                         onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.2)';"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="text-success mb-3" style="font-size: 3rem;">
                                <i class="fas fa-store"></i>
                            </div>
                            <h3 class="card-title">Negozi</h3>
                            <p class="card-text">Trova il negozio ShoePal più vicino a te</p>
                            <a href="negozi.php" class="btn btn-success mt-3 stretched-link">Trova Negozi</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sezione ordini se cliente loggato -->
            <div class="row g-4 mt-4">
                <div class="col-lg-6 col-md-6">
                    <div class="card text-center h-100 border-0 shadow" 
                         style="min-height: 200px; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                         onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.2)';"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="text-warning mb-3" style="font-size: 3rem;">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h3 class="card-title">I Tuoi Ordini</h3>
                            <p class="card-text">Visualizza lo storico dei tuoi acquisti e ordini</p>
                            <a href="ordini.php" class="btn btn-warning mt-3 stretched-link">Visualizza Ordini</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 col-md-6">
                    <div class="card text-center h-100 border-0 shadow" 
                         style="min-height: 200px; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                         onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.2)';"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="text-info mb-3" style="font-size: 3rem;">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <h3 class="card-title">Informazioni</h3>
                            <p class="card-text">Informazioni utili sui nostri servizi</p>
                            <a href="info.php" class="btn btn-info mt-3 stretched-link">Scopri di Più</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once("lib/footer.php"); ?>

</body>
</html>