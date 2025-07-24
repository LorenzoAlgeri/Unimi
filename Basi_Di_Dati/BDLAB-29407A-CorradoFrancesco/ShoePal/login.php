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

    // Gestione pulizia sessioni temporanee
    if (isset($_GET['action']) && $_GET['action'] == 'clear_temp_session') {
        clear_temp_session();
        header("Location: login.php");
        exit();
    }

    // Gestione form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['cliente_nome'], $_POST['cliente_cf'], $_POST['cliente_email'])) {
            // Registrazione profilo cliente
            handle_cliente_profile_registration(
                trim($_POST['cliente_nome']), 
                trim($_POST['cliente_cf']), 
                trim($_POST['cliente_email'])
            );
        } elseif (isset($_POST['register_email'], $_POST['register_password'])) {
            // Registrazione nuovo cliente
            handle_cliente_registration(
                trim($_POST['register_email']), 
                $_POST['register_password']
            );
        } elseif (isset($_POST['usr'], $_POST['psw'])) {
            // Login
            handle_user_login($_POST['usr'], $_POST['psw']);
        }
    }

    // Controlla sessioni esistenti e imposta redirect se necessario
    check_existing_session();

    // Recupera i messaggi dalla sessione
    $messages = get_session_messages();
    $error_msg = $messages['error'];
    $success_msg = $messages['success'];
    $info_msg = $messages['info'];
?>

<!doctype html>
<html lang="en">
<head>
    <title>Login</title>
    <?php include_once('lib/header.php'); ?>
</head>
<body class="bg-light">

    <div class="container-fluid vh-100 d-flex align-items-center justify-content-center text-center">
        <div class="row justify-content-center w-100">
            <div class="col-md-6 col-lg-4">
                <div id="formsContainer">
                    <!-- Login Form -->
                    <div id="loginSection" class="collapse <?php echo empty($_SESSION['register_profile_email']) ? 'show' : ''; ?>" data-bs-parent="#formsContainer">
                    <form class="w-100 mx-auto p-3" style="max-width: 330px;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="loginForm">
                        <div class="d-flex align-items-center justify-content-center mb-4">
                            <h1 class="timmana-regular text-center my-4 mb-0 mr-3" style="margin-right: 12px;">ShoePal</h1>
                            <img src="assets/sneakers.png" alt="" width="72" height="72">
                        </div>
                        <h2 class="h3 mb-3 font-weight-normal">Effettua l'accesso</h2>
                        <div class="form-floating mb-3"> 
                            <input type="text" class="form-control" id="floatingInput" placeholder="email" name="usr" required> 
                            <label for="floatingInput">Email</label> 
                        </div>
                        <div class="form-floating mb-3">
                            <input class="form-control" type="password" placeholder="Password" name="psw" required>
                            <label for="floatingPassword">Password</label>
                        </div>
                        <button class="btn btn-primary w-100 py-2 mb-3" type="submit">
                            Accedi
                        </button>
                        <div class="text-center">
                            <a href="#" data-bs-toggle="collapse" data-bs-target="#registerSection" 
                               aria-expanded="false" aria-controls="registerSection"
                               data-bs-parent="#formsContainer" class="text-decoration-none">
                                Crea nuovo profilo utente
                            </a>
                        </div>
                        <p class="mt-4 mb-2 text-muted">&copy; 2025</p>
                    </form>
                </div>

                <!-- Registration Form -->
                <div id="registerSection" class="collapse" data-bs-parent="#formsContainer">
                    <form class="w-100 mx-auto p-3" style="max-width: 330px;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="registerForm">
                        <div class="d-flex align-items-center justify-content-center mb-4">
                            <h1 class="timmana-regular text-center my-4 mb-0 mr-3" style="margin-right: 12px;">ShoePal</h1>
                            <img src="assets/sneakers.png" alt="" width="72" height="72">
                        </div>
                        <h2 class="h3 mb-3 font-weight-normal">Crea nuovo profilo</h2>
                        <div class="form-floating mb-3"> 
                            <input type="email" class="form-control" id="registerEmail" placeholder="email" name="register_email" required> 
                            <label for="registerEmail">Email</label> 
                        </div>
                        <div class="form-floating mb-3">
                            <input class="form-control" type="password" id="registerPassword" placeholder="Password" name="register_password" required minlength="6">
                            <label for="registerPassword">Password</label>
                        </div>
                        <button class="btn btn-success w-100 py-2 mb-3" type="submit">
                            Registrati
                        </button>
                        <div class="text-center">
                            <a href="#" data-bs-toggle="collapse" data-bs-target="#loginSection" 
                               aria-expanded="false" aria-controls="loginSection"
                               data-bs-parent="#formsContainer" class="text-decoration-none">
                                Torna al login
                            </a>
                        </div>
                        <p class="mt-4 mb-2 text-muted">&copy; 2025</p>
                    </form>
                </div>

                <!-- Cliente Profile Registration Form -->
                <div id="clienteProfileSection" class="collapse <?php echo !empty($_SESSION['register_profile_email']) ? 'show' : ''; ?>" data-bs-parent="#formsContainer">
                    <form class="w-100 mx-auto p-3" style="max-width: 330px;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="clienteProfileForm">
                        <div class="d-flex align-items-center justify-content-center mb-4">
                            <h1 class="timmana-regular text-center my-4 mb-0 mr-3" style="margin-right: 12px;">ShoePal</h1>
                            <img src="assets/sneakers.png" alt="" width="72" height="72">
                        </div>
                        <h2 class="h3 mb-3 font-weight-normal">Associa il tuo profilo utente</h2>
                        <div class="form-floating mb-3"> 
                            <input type="email" class="form-control" id="clienteEmail" placeholder="email" name="cliente_email" 
                                   value="<?php echo isset($_SESSION['register_profile_email']) ? htmlspecialchars($_SESSION['register_profile_email']) : ''; ?>" 
                                   readonly required> 
                            <label for="clienteEmail">Email</label> 
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="clienteNome" placeholder="Nome completo" name="cliente_nome" required>
                            <label for="clienteNome">Nome completo</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control text-uppercase" id="clienteCF" placeholder="Codice Fiscale" name="cliente_cf" 
                                   required maxlength="16" pattern="[A-Z0-9]{16}"
                                   title="Il codice fiscale deve essere di 16 caratteri alfanumerici">
                            <label for="clienteCF">Codice Fiscale</label>
                        </div>
                        <button class="btn btn-primary w-100 py-2 mb-3" type="submit">
                            Completa Registrazione
                        </button>
                        <div class="text-center">
                            <a href="login.php?action=clear_temp_session" class="text-decoration-none">
                                Torna al login
                            </a>
                        </div>
                        <p class="mt-4 mb-2 text-muted">&copy; 2025</p>
                    </form>
                </div>
                </div>

                <!-- Messages -->
                <div style="position: relative; min-height: 60px; margin-top: 20px;">
                    <?php if (!empty($error_msg)) { ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
                        <?php echo $error_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php } ?>
                    
                    <?php if (!empty($success_msg)) { ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
                        <?php echo $success_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php } ?>
                    
                    <?php if (!empty($info_msg)) { ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert" id="infoAlert">
                        <?php echo $info_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>
</html>
