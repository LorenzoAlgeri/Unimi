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
    $negozi = get_negozi_with_orari($connection);
    close_pg_connection($connection);
?>
<!doctype html>
<html lang="it">
<head>
    <title>I nostri negozi - ShoePal</title>
    <?php include_once('lib/header.php'); ?>
    <link href="css/shoepal.css" rel="stylesheet">
</head>
<body>
    
    <?php include_once('lib/cliente_navigation.php'); ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4"><i class="fas fa-store me-2"></i>I nostri negozi</h2>
                <p class="text-muted mb-4">Trova il negozio ShoePal più vicino a te</p>

                <div class="row">
                    <?php foreach ($negozi as $negozio): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <?php 
                                    // Estrai la città dall'indirizzo (ultima parte dopo l'ultima virgola)
                                    $indirizzo_parti = explode(',', $negozio['indirizzo']);
                                    $citta = trim(end($indirizzo_parti));
                                    $nome_negozio = get_negozio_display_name($citta);
                                    ?>
                                    <h5 class="card-title"><?php echo htmlspecialchars($nome_negozio); ?></h5>
                                    <p class="card-text">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        <?php echo htmlspecialchars($negozio['indirizzo']); ?>
                                    </p>
                                    <p class="card-text">
                                        <i class="fas fa-user me-2"></i>
                                        Responsabile: <?php echo htmlspecialchars($negozio['responsabile']); ?>
                                    </p>
                                    
                                    <!-- Orari del negozio -->
                                    <div class="mt-3">
                                        <h6 class="text-primary">
                                            <i class="fas fa-clock me-2"></i>Orari di apertura
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    <?php foreach ($negozio['orari'] as $orario): ?>
                                                        <tr>
                                                            <td class="fw-bold py-1" style="width: 50%;">
                                                                <?php echo $orario['giorno']; ?>:
                                                            </td>
                                                            <td class="py-1">
                                                                <?php 
                                                                if ($orario['orainizio'] && $orario['orafine']) {
                                                                    echo substr($orario['orainizio'], 0, 5) . ' - ' . substr($orario['orafine'], 0, 5);
                                                                } else {
                                                                    echo '<span class="text-muted">Chiuso</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a href="shop.php?negozio=<?php echo $negozio['idnegozio']; ?>" class="btn btn-primary">
                                        <i class="fas fa-shopping-cart me-1"></i>Visita negozio
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include_once('lib/footer.php'); ?>


</body>
</html>
