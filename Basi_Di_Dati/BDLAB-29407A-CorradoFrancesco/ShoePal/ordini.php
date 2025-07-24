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

    $connection = open_pg_connection();

    // Ottieni i dati del cliente
    $query = "SELECT codicefiscale FROM shoepal.cliente WHERE email = $1";
    $result = pg_query_params($connection, $query, array($_SESSION['user']));
    $cliente = pg_fetch_assoc($result);

    // Ottieni gli ordini del cliente
    $ordini_result = get_ordini_cliente($connection, $cliente['codicefiscale']);

    close_pg_connection($connection);
?>
<!doctype html>
<html lang="it">
<head>
    <title>I miei ordini</title>
    <?php include_once('lib/header.php'); ?>
    <link href="css/shoepal.css" rel="stylesheet">
</head>
<body>
    
    <?php include_once('lib/cliente_navigation.php'); ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4"><i class="fas fa-shopping-bag me-2"></i>I miei ordini</h2>

                <?php if (pg_num_rows($ordini_result) > 0): ?>
                    <div class="row">
                        <?php while ($ordine = pg_fetch_assoc($ordini_result)): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Ordine #<?php echo $ordine['idfattura']; ?></h6>
                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($ordine['dataacquisto'])); ?></small>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2">
                                            <strong>Totale:</strong> 
                                            <?php if ($ordine['scontopercentuale'] > 0): ?>
                                                <span class="text-muted text-decoration-line-through">€ <?php echo number_format($ordine['totaleoriginale'], 2); ?></span>
                                                <span class="text-success ms-2">€ <?php echo number_format($ordine['totalepagato'], 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-success">€ <?php echo number_format($ordine['totalepagato'], 2); ?></span>
                                            <?php endif; ?>
                                        </p>
                                        
                                        <?php if ($ordine['scontopercentuale'] > 0): ?>
                                            <p class="mb-2">
                                                <small class="text-info">
                                                    <i class="fas fa-tag me-1"></i>Sconto applicato: <?php echo $ordine['scontopercentuale']; ?>%
                                                    (risparmiati € <?php echo number_format($ordine['totaleoriginale'] - $ordine['totalepagato'], 2); ?>)
                                                </small>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <p class="mb-2">
                                            <small><i class="fas fa-star text-warning me-1"></i>
                                            Punti guadagnati: <strong><?php echo $ordine['puntiaccumulati']; ?></strong>
                                            </small>
                                        </p>
                                        
                                        <p class="mb-3">
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($ordine['negozio_indirizzo']); ?>
                                            </small>
                                        </p>
                                        
                                        <button class="btn btn-outline-primary btn-sm" type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#dettaglio-<?php echo $ordine['idfattura']; ?>">
                                            <i class="fas fa-eye me-1"></i>Dettagli
                                        </button>
                                    </div>
                                    
                                    <div class="collapse" id="dettaglio-<?php echo $ordine['idfattura']; ?>">
                                        <div class="card-footer">
                                            <h6 class="mb-2">Prodotti acquistati:</h6>
                                            <?php
                                            // Ottieni i dettagli dell'ordine
                                            $connection = open_pg_connection();
                                            $dettagli_result = get_dettagli_fattura($connection, $ordine['idfattura']);
                                            ?>
                                            
                                            <div class="list-group list-group-flush">
                                                <?php while ($dettaglio = pg_fetch_assoc($dettagli_result)): ?>
                                                    <div class="list-group-item px-0 py-2 border-0">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <small><strong><?php echo htmlspecialchars($dettaglio['nome']); ?></strong></small><br>
                                                                <small class="text-muted">Taglia: <?php echo $dettaglio['taglia']; ?> - Quantità: <?php echo $dettaglio['quantità']; ?></small>
                                                            </div>
                                                            <small class="text-success">
                                                                € <?php echo number_format($dettaglio['prezzounitario'], 2); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                            
                                            <?php close_pg_connection($connection); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Nessun ordine trovato</h4>
                        <p class="text-muted">Non hai ancora effettuato nessun acquisto.</p>
                        <a href="shop.php" class="btn btn-primary">
                            <i class="fas fa-shopping-cart me-1"></i>Inizia a comprare
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Azioni rapide -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="mb-3">Azioni rapide</h6>
                                <a href="profilo.php" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-user me-1"></i>Il mio profilo
                                </a>
                                <a href="shop.php" class="btn btn-outline-success">
                                    <i class="fas fa-shopping-cart me-1"></i>Continua a comprare
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
