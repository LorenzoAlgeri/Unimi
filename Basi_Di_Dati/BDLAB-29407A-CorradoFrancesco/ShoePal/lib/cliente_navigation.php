<!-- Header superiore con logo e profilo -->
    <div class="top-header py-3">
      <div class="container">
        <div class="d-flex justify-content-between align-items-center">
          <!-- Logo a sinistra -->
          <div class="d-flex align-items-center">
            <a href="home.php" class="text-decoration-none" style="color: inherit !important;">
              <h1 class="timmana-regular my-4 mb-0 me-3" style="margin-right: 12px;">ShoePal</h1>
            </a>
            <img src="assets/sneakers.png" alt="" width="72" height="72">
          </div>
          
          <!-- Barra di ricerca centrale -->
          <div class="flex-grow-1 mx-4 d-none d-md-block" style="max-width: 400px;">
            <form class="d-flex" action="shop.php" method="GET">
              <input class="form-control me-2" type="search" 
                     placeholder="Cerca prodotti..." 
                     aria-label="Cerca prodotti" name="search"
                     value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
              <button class="btn btn-outline-secondary" type="submit">
                <i class="fas fa-search"></i>
              </button>
            </form>
          </div>
          
          <!-- Menu profilo e carrello a destra -->
          <div class="d-flex align-items-center gap-3">
            <!-- Pulsante carrello -->
            <a href="carrello.php" class="btn btn-outline-success position-relative">
              <i class="fas fa-shopping-cart"></i>
              <?php if (isset($_SESSION['carrello']) && count($_SESSION['carrello']) > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                  <?php echo count($_SESSION['carrello']); ?>
                </span>
              <?php endif; ?>
            </a>
            
            <!-- Dropdown profilo -->
            <div class="dropdown">
              <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-user me-1"></i>
                <?php 
                  // Ottieni il nome del cliente dal database
                  $connection = open_pg_connection();
                  $query = "SELECT nome FROM shoepal.cliente WHERE email = $1";
                  $result = pg_query_params($connection, $query, array($_SESSION['user']));
                  $cliente_nome = "Cliente";
                  if ($result && pg_num_rows($result) > 0) {
                    $row = pg_fetch_assoc($result);
                    $cliente_nome = $row['nome'];
                  }
                  close_pg_connection($connection);
                  echo htmlspecialchars($cliente_nome);
                ?>
              </button>
              <ul class="dropdown-menu dropdown-menu-end profile-dropdown">
                <li><h6 class="dropdown-header">Il mio account</h6></li>
                <li><a class="dropdown-item" href="profilo.php">
                  <i class="fas fa-user-edit me-2"></i>Profilo
                </a></li>
                <li><a class="dropdown-item" href="ordini.php">
                  <i class="fas fa-shopping-bag me-2"></i>I miei ordini
                </a></li>
                <li><a class="dropdown-item" href="carrello.php">
                  <i class="fas fa-shopping-cart me-2"></i>Carrello
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?php echo $_SERVER['PHP_SELF'] . '?log=del'; ?>">
                  <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Navbar di navigazione principale -->
    <nav class="navbar navbar-expand-lg navbar-light main-navbar">
      <div class="container">
        <!-- Toggle button per mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu di navigazione centrato -->
        <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
          <ul class="navbar-nav">
            <?php 
              // Determina la pagina corrente
              $current_page = basename($_SERVER['PHP_SELF']);
            ?>
            <li class="nav-item">
              <a class="nav-link <?php echo ($current_page == 'home.php') ? 'active' : ''; ?>" href="home.php">
                <i class="fas fa-home me-1"></i>Home
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo ($current_page == 'shop.php') ? 'active' : ''; ?>" href="shop.php">
                <i class="fas fa-shopping-cart me-1"></i>Shop
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo ($current_page == 'carrello.php') ? 'active' : ''; ?>" href="carrello.php">
                <i class="fas fa-shopping-bag me-1"></i>Carrello
                <?php if (isset($_SESSION['carrello']) && count($_SESSION['carrello']) > 0): ?>
                  <span class="badge bg-danger ms-1"><?php echo count($_SESSION['carrello']); ?></span>
                <?php endif; ?>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo ($current_page == 'negozi.php') ? 'active' : ''; ?>" href="negozi.php">
                <i class="fas fa-store me-1"></i>Negozi
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo ($current_page == 'info.php') ? 'active' : ''; ?>" href="info.php">
                <i class="fas fa-info-circle me-1"></i>Info
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>