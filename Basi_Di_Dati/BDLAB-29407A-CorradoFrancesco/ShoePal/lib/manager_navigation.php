<?php
    $current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="managerdashboard.php">ShoePal Manager</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'managerdashboard.php') ? 'active' : ''; ?>" href="managerdashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'managernegozi.php') ? 'active' : ''; ?>" href="managernegozi.php">Negozi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'managerprodotti.php') ? 'active' : ''; ?>" href="managerprodotti.php">Prodotti</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'managerdisponibilita.php') ? 'active' : ''; ?>" href="managerdisponibilita.php">Disponibilità</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'managerordini.php') ? 'active' : ''; ?>" href="managerordini.php">Ordini</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'managerclienti.php') ? 'active' : ''; ?>" href="managerclienti.php">Clienti</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'managerfornitori.php') ? 'active' : ''; ?>" href="managerfornitori.php">Fornitori</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'managerstats.php') ? 'active' : ''; ?>" href="managerstats.php">Statistiche</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <?php echo $_SESSION['user']; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="managerdashboard.php?log=del">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
