<?php
    ini_set ("display_errors", "On");
	ini_set("error_reporting", E_ALL);
	include_once ('lib/functions.php');

    session_start();

    // Inizializza carrello se non esiste
    if (!isset($_SESSION['carrello'])) {
        $_SESSION['carrello'] = array();
    }

    $carrello_message = '';
    $carrello_error = '';

    // Recupera messaggio carrello dalla sessione se presente
    if (isset($_SESSION['carrello_message'])) {
        $carrello_message = $_SESSION['carrello_message'];
        unset($_SESSION['carrello_message']);
    }
    if (isset($_SESSION['carrello_error'])) {
        $carrello_error = $_SESSION['carrello_error'];
        unset($_SESSION['carrello_error']);
    }

    // Funzione helper per costruire URL di redirect mantenendo i parametri
    function build_shop_redirect_url($negozio_id) {
        $redirect_url = 'shop.php?negozio_id=' . $negozio_id;
        
        $params_to_preserve = ['search', 'genere', 'tipologia', 'marca', 'taglia', 'prezzo_min', 'prezzo_max'];
        foreach ($params_to_preserve as $param) {
            if (!empty($_GET[$param])) {
                $redirect_url .= '&' . $param . '=' . urlencode($_GET[$param]);
            }
        }
        
        return $redirect_url;
    }

    // Gestione aggiunta al carrello
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'aggiungi_carrello') {
        $idProdotto = (int)$_POST['idProdotto'];
        $idNegozio = (int)$_POST['idNegozio'];
        $taglia = trim($_POST['taglia'] ?? '');
        $quantita = (int)($_POST['quantita'] ?? 1);
        
        if (empty($taglia)) {
            $_SESSION['carrello_error'] = 'Seleziona una taglia';
            
            // Se c'è un negozio in GET, redirect mantenendolo
            if (!empty($_GET['negozio_id'])) {
                $redirect_url = build_shop_redirect_url((int)$_GET['negozio_id']);
                header("Location: $redirect_url");
                exit();
            } else {
                $carrello_error = 'Seleziona una taglia';
            }
        } else {
            // Controllo negozio: tutti i prodotti nel carrello devono essere dello stesso negozio
            if (!empty($_SESSION['carrello'])) {
                $primo_prodotto = reset($_SESSION['carrello']);
                $negozio_carrello = $primo_prodotto['idNegozio'];
                
                if ($negozio_carrello != $idNegozio) {
                    // Ottieni nome del negozio corrente nel carrello per il messaggio
                    $connection = open_pg_connection();
                    $query_negozio = "SELECT indirizzo FROM shoepal.negozio WHERE idnegozio = $1";
                    $result_negozio = pg_query_params($connection, $query_negozio, array($negozio_carrello));
                    
                    if ($result_negozio && pg_num_rows($result_negozio) > 0) {
                        $negozio_info = pg_fetch_assoc($result_negozio);
                        $indirizzo_parti = explode(',', $negozio_info['indirizzo']);
                        $citta_carrello = trim(end($indirizzo_parti));
                        $nome_negozio_carrello = get_negozio_display_name($citta_carrello);
                    } else {
                        $nome_negozio_carrello = "un altro negozio";
                    }
                    close_pg_connection($connection);
                    
                    $_SESSION['carrello_error'] = "Non puoi aggiungere prodotti da negozi diversi. Il tuo carrello contiene già prodotti da $nome_negozio_carrello. Completa l'ordine o svuota il carrello per aggiungere prodotti da questo negozio.";
                    
                    // Redirect mantenendo il negozio corrente
                    $redirect_url = build_shop_redirect_url($idNegozio);
                    header("Location: $redirect_url");
                    exit();
                } else {
                    // Procedi con la verifica di disponibilità
                    $verifica_disponibilita = true;
                }
            } else {
                // Carrello vuoto, procedi
                $verifica_disponibilita = true;
            }
            
            if (isset($verifica_disponibilita) && $verifica_disponibilita) {
            // Verifica disponibilità nel database
            $connection = open_pg_connection();
            $query = "SELECT d.quantità, d.prezzo, p.nome 
                      FROM shoepal.disponibilità d
                      JOIN shoepal.prodotto p ON d.idprodotto = p.idprodotto
                      WHERE d.idprodotto = $1 AND d.idnegozio = $2 AND d.taglia = $3";
            $result = pg_query_params($connection, $query, array($idProdotto, $idNegozio, $taglia));
            
            if (!$result || pg_num_rows($result) == 0) {
                $_SESSION['carrello_error'] = 'Prodotto non disponibile nella taglia selezionata';
                close_pg_connection($connection);
                
                // Redirect mantenendo il negozio corrente
                $redirect_url = build_shop_redirect_url($idNegozio);
                header("Location: $redirect_url");
                exit();
            } else {
                $prodotto = pg_fetch_assoc($result);
                
                if ($prodotto['quantità'] < $quantita) {
                    $_SESSION['carrello_error'] = 'Quantità non disponibile';
                    close_pg_connection($connection);
                    
                    // Redirect mantenendo il negozio corrente
                    $redirect_url = build_shop_redirect_url($idNegozio);
                    header("Location: $redirect_url");
                    exit();
                } else {
                    // Chiave univoca per il prodotto nel carrello
                    $chiave = $idProdotto . '_' . $idNegozio . '_' . $taglia;
                    
                    // Aggiungi al carrello
                    if (isset($_SESSION['carrello'][$chiave])) {
                        $_SESSION['carrello'][$chiave]['quantita'] += $quantita;
                    } else {
                        $_SESSION['carrello'][$chiave] = array(
                            'idProdotto' => $idProdotto,
                            'idNegozio' => $idNegozio,
                            'taglia' => $taglia,
                            'quantita' => $quantita,
                            'prezzo' => $prodotto['prezzo'],
                            'nome' => $prodotto['nome']
                        );
                    }
                    
                    $carrello_message = 'Prodotto aggiunto al carrello!';
                    
                    // Redirect per mantenere il negozio selezionato
                    $redirect_url = build_shop_redirect_url($idNegozio);
                    
                    $_SESSION['carrello_message'] = $carrello_message;
                    header("Location: $redirect_url");
                    exit();
                }
            }
            close_pg_connection($connection);
            }
        }
    }

    // Gestione logout
    if (isset($_GET['log']) && $_GET['log'] == 'del') {
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // Include il file unificato dei componenti shop
    include_once('lib/shop_components.php');

    // Esegui controllo di autenticazione
    shop_check_authentication();

    // Elabora parametri e carica dati
    $shop_data = shop_process_parameters();
    extract($shop_data);
?>
<!doctype html>
<html lang="it">
  <head>
    <title>Shop</title>
    <?php include_once ('lib/header.php'); ?>
    <link href="css/shoepal.css" rel="stylesheet">
  </head>
  <body>
    <?php include_once ('lib/cliente_navigation.php'); ?>

    <!-- Messaggi carrello -->
    <?php if ($carrello_message): ?>
      <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($carrello_message); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($carrello_error): ?>
      <div class="container mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($carrello_error); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      </div>
    <?php endif; ?>

    <!-- Sezione ricerca iniziale (solo se nessun negozio è selezionato e nessuna ricerca globale) -->
    <?php if (!$negozio_selezionato && !$ricerca_globale): ?>
      <?php shop_render_search_section(); ?>
    <?php endif; ?>

    <!-- Risultati ricerca globale -->
    <?php if ($ricerca_globale): ?>
      <?php shop_render_global_search_results($prodotti_per_negozio, $search_term); ?>
    <?php endif; ?>

    <!-- Selezione negozio (solo se nessun negozio è selezionato e nessuna ricerca globale) -->
    <?php if (!$negozio_selezionato && !$ricerca_globale): ?>
      <?php shop_render_negozi_grid($negozi); ?>
    <?php elseif ($negozio_selezionato): ?>
      <!-- Sezione prodotti negozio selezionato -->
      <div class="container mb-5">
        <!-- Header del negozio selezionato -->
        <?php shop_render_negozio_header($negozio_selezionato); ?>

        <!-- Form filtri e ricerca -->
        <?php shop_render_filtri_form($shop_data); ?>

        <!-- Risultati ricerca -->
        <?php shop_render_search_results($prodotti, $search_term, $genere_filtro, $tipologia_filtro, $marca_filtro, $taglia_filtro); ?>

        <!-- Griglia prodotti -->
        <?php shop_render_prodotti_grid($prodotti, $negozio_selezionato); ?>
      </div>
    <?php endif; ?>

    <?php include_once('lib/footer.php'); ?>

  </body>
</html>