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
?>
<!doctype html>
<html lang="it">
<head>
    <title>Informazioni - ShoePal</title>
    <?php include_once('lib/header.php'); ?>
    <link href="css/shoepal.css" rel="stylesheet">
</head>
<body>
    
    <?php include_once('lib/cliente_navigation.php'); ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4"><i class="fas fa-info-circle me-2"></i>Informazioni su ShoePal</h2>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-shoe-prints me-2"></i>Chi siamo</h5>
                            </div>
                            <div class="card-body">
                                <p>ShoePal è la tua destinazione per trovare le scarpe perfette. Offriamo una vasta selezione di calzature per ogni occasione e stile.</p>
                                <p>Con i nostri negozi nelle principali città italiane, garantiamo qualità e varietà per soddisfare ogni esigenza.</p>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-star me-2"></i>Tessera Fedeltà</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Come funziona:</strong></p>
                                <ul>
                                    <li>Guadagni 1 punto ogni 1 euro di spesa</li>
                                    <li>I punti si accumulano automaticamente ad ogni acquisto</li>
                                    <li>Potrai utilizzare i punti per ottenere sconti sui futuri acquisti:</li>
                                    <ul>
                                        <li>100 punti = 5% di sconto</li>
                                        <li>200 punti = 10% di sconto</li>
                                        <li>500 punti = 30% di sconto</li>
                                    </ul>
                                </p>
                                <p><strong>Come ottenere la tessera:</strong><br>
                                Richiedila nel tuo negozio oppure online!</p>
                                <p>Nota bene: max 100 euro di sconto per acquisto.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-phone me-2"></i>Contatti</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Email:</strong><br>info@shoepal.it</p>
                                <p><strong>Telefono:</strong><br>+39 1112223333</p>
                                <p><strong>Assistenza clienti:</strong><br>Lun-Ven 9:00-18:00</p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-shipping-fast me-2"></i>Spedizioni</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Ritiro in negozio:</strong> Gratuito</p>
                                <p><strong>Consegna a domicilio:</strong> In fase di implementazione</p>
                                <small class="text-muted">Attualmente è possibile solo il ritiro presso i nostri negozi.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Azioni rapide -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="mb-3">Inizia subito</h6>
                                <a href="shop.php" class="btn btn-primary me-2">
                                    <i class="fas fa-shopping-cart me-1"></i>Vai allo shop
                                </a>
                                <a href="negozi.php" class="btn btn-outline-primary">
                                    <i class="fas fa-store me-1"></i>Trova un negozio
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
