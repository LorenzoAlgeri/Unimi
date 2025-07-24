<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Footer Manager -->
<footer class="bg-primary text-light py-4 mt-auto">
    <div class="container">
        <div class="row">
            <!-- Informazioni sistema -->
            <div class="col-md-4 mb-3">
                <div class="d-flex align-items-center mb-3">
                    <h5 class="timmana-regular mb-0 me-2">ShoePal Manager</h5>
                    <i class="fas fa-cogs text-warning" style="font-size: 1.5rem;"></i>
                </div>
                <p class="text-light opacity-75">
                    Sistema di gestione centralizzato per la catena ShoePal. 
                    Controlla negozi, prodotti, clienti e fornitori.
                </p>
            </div>

            <!-- Funzioni rapide -->
            <div class="col-md-4 mb-3">
                <h6 class="text-uppercase fw-bold mb-3">
                    <i class="fas fa-tools me-2"></i>Funzioni Rapide
                </h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="managerdashboard.php" class="text-light text-decoration-none link-light link-opacity-75 link-opacity-100-hover">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="managernegozi.php" class="text-light text-decoration-none link-light link-opacity-75 link-opacity-100-hover">
                            <i class="fas fa-store me-2"></i>Gestione Negozi
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="managerstats.php" class="text-light text-decoration-none link-light link-opacity-75 link-opacity-100-hover">
                            <i class="fas fa-chart-bar me-2"></i>Statistiche
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Informazioni sessione -->
            <div class="col-md-4 mb-3">
                <h6 class="text-uppercase fw-bold mb-3">
                    <i class="fas fa-user-shield me-2"></i>Sessione Manager
                </h6>
                <div class="d-flex flex-column">
                    <div class="mb-2">
                        <i class="fas fa-user me-2 text-warning"></i>
                        <strong>Utente:</strong> <?php echo htmlspecialchars($_SESSION['user'] ?? 'Manager'); ?>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-clock me-2 text-warning"></i>
                        <strong>Accesso:</strong> <?php echo date('H:i d/m/Y'); ?>
                    </div>
                    <div>
                        <a href="login.php?logout=1" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
