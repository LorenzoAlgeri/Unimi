<?php 
	ini_set ("display_errors", "On");
	ini_set("error_reporting", E_ALL);
	include_once ('lib/functions.php'); 

    /*
     * Si inserisca il codice necessario per gestire il login degli utenti.
     * Solo gli utenti con credenziali valide possono accedere alle funzionalità dell'applicazione.
     */
    $logged = null;

    session_start();

    // controlla il login
    $error_msg = '';
    if(isset($_POST) && isset($_POST['usr']) && isset($_POST['psw'])){
        $logged = login($_POST['usr'], $_POST['psw']);
        if (is_null($logged)) {
            // utente non trovato
            $error_msg = 'Credenziali errate. Ripetere il login';
        }
     }

    // imposta la variabile $logges se esiste una sessione aperta
    if(isset($_SESSION['user'])){
        $logged = $_SESSION['user'];
    }
    
    // aggiorna la variabile di sessione
    if(isset($logged)) {
        $_SESSION['user'] = $logged;
    }

    // inizializza $logged se l'utente fa logout
    if(isset($_GET) && isset($_GET['log']) && $_GET['log'] == 'del'){
        unset($_SESSION['user']);
        $logged = null;
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <?php include_once ('lib/header.php'); ?>
        <title>
        IMDB app
        </title>
    </head>
    <body>
    <div class="uk-container uk-margin-bottom uk-margin-top">
    <?php
    if (isset($logged)) {
        $logout_link = $_SERVER['PHP_SELF'] . "?log=del";
    ?>
    <div class="uk-card uk-card-body uk-margin-remove uk-padding-remove uk-text-right">
    <p>
        <?php echo("Benvenuto $logged"); ?> - 
        <a href="<?php echo($logout_link); ?>">Logout</a> 
    </p>
    </div>
    <?php
    } 
    ?>
    <h1 class="uk-article-title">Accesso a una base di dati</h1>
    <?php include_once ('lib/navigation.php'); ?>

    <div class="uk-section uk-section-default">
    
    <?php

    /*
     * Si visualizzi qui il form di login quando il login non è stato effettuato
     */
    if(!isset($logged)) {

    ?>

    <div class="uk-width-1-3@s uk-container">
    <div class="uk-panel uk-panel-space uk-text-center">
    <form class="uk-form-horizontal" action="<?php echo $_SERVER['PHP_SELF'];?>" method="POST">
        <legend class="uk-legend">Inserisci le credenziali</legend>

        <div class="uk-inline uk-width-1-1">     
            <input class="uk-input" type="text" placeholder="username" name="usr">
        </div>
        <div class="uk-inline uk-width-1-1">
            <input class="uk-input" type="password" placeholder="password" name="psw">
        </div>
        
        <button class="uk-width-1-1 uk-button uk-button-primary uk-button-large uk-margin-small-top">Esegui il login</button>
    </form>
    
	
	<?php
    if (!empty($error_msg)) {
    ?>
    
	
	<div class="uk-alert-danger" uk-alert>
        <a class="uk-alert-close" uk-close></a>
        <p><?php echo $error_msg; ?></p>
    </div>
    <?php
    }
    ?>
    </div>
    </div>
    <?php
    } else {
    ?>
    <div uk-grid>
        <div class="uk-width-1-3">
            <div class="uk-card uk-card-primary uk-card-body uk-padding-small uk-text-left">
                <nav>
                    <ul class="uk-nav uk-nav-default">
                    <?php
                    //list è l'opzione di visualizzazione predefinita
                    if (isset($_GET['mod']))
                        $active = $_GET['mod'];
                    else
                        $active = 'list';
                
                    $menu = get_menu_entries();
                    foreach ($menu as $key => $value) {
                        $active_option = '';
                        if ($key == $active)
                            $active_option = 'class="uk-active"';
                    ?>
                        <li <?php echo $active_option; ?>><a href="<?php echo $_SERVER['PHP_SELF'];?>?mod=<?php echo $key; ?>"><?php echo $value; ?></a></li>
                    <?php
                    }
                    ?>
                    </ul>
                </nav>
            </div>
        </div>
        <div class="uk-width-2-3">
            <div class="uk-card uk-card-default uk-card-body uk-padding-small uk-text-left">
             <?php
                if (isset($_GET) && isset($_GET['mod'])) {
                    switch ($_GET['mod']) {
                    case 'insert':
                        include_once('lib/movieform.php');  
                        break;
                    case 'stats':
                        include_once('lib/moviestats.php'); 
                        break;
                    case 'err':
                        include_once('lib/movieerror.php');   
                        break;
                    case 'fix':
                        include_once('lib/moviefix.php');   
                        break;
                    case 'list':
                    default:
                        include_once('lib/movietable.php'); 
                        break;
                    }
                } else {
                    include_once('lib/movietable.php');     
                }
            ?>       
            </div>
        </div>
    </div>
    <?php
    }
    ?>
	</div>
    </body>
</html>