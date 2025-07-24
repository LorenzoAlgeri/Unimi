<?php
/**
 * Funzioni di utilità per ShoePal
 * 
 * SEZIONI:
 * 1. Login, Registrazione e Connessione
 * 2. Negozi, Filtri e Prodotti
 * 3. Esecuzione query generiche
 * 4. Funzioni di utilità varie
 */

// 1. LOGIN, REGISTRAZIONE E CONNESSIONE

// Verifica il login dell'utente
function login($user, $psw) {
    include_once("conf/conf.php");
    $connection = open_pg_connection();

    $logged = null;
    $usertype = null;

    $query = "SELECT email, passwordhash, tipoutente FROM shoepal.utente WHERE email = $1";
    $result = pg_query_params($connection, $query, array($user));

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $hashed_password = $row['passwordhash'];
        if (password_verify($psw, $hashed_password)) {
            $logged = $row['email'];
            $usertype = $row['tipoutente'];
            close_pg_connection($connection);
            return ['username' => $logged, 'ruolo' => $usertype];
        }
    }
    close_pg_connection($connection);
    return null;
}

// Registra un nuovo utente
function register_cliente($email, $password) {
    include_once("conf/conf.php");
    $connection = open_pg_connection();
    
    // Verifica se l'email esiste già
    $check_query = "SELECT email FROM shoepal.utente WHERE email = $1";
    $check_result = pg_query_params($connection, $check_query, array($email));
    
    if ($check_result && pg_num_rows($check_result) > 0) { // Se l'amail esiste già
        close_pg_connection($connection);
        return false;
    }
    
    // Hash della password per salvarla nel db
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Inserisce il nuovo utente
    $insert_query = "INSERT INTO shoepal.utente (email, passwordhash, tipoutente) VALUES ($1, $2, 'cliente')";
    $result = pg_query_params($connection, $insert_query, array($email, $password_hash));
    
    close_pg_connection($connection);
    return $result !== false;
}

// Controlla se il profilo cliente esiste già
function check_cliente_profile($email) {
    include_once("conf/conf.php");
    $connection = open_pg_connection();
    
    $query = "SELECT codicefiscale, nome FROM shoepal.cliente WHERE email = $1";
    $result = pg_query_params($connection, $query, array($email));
    
    $profile_exists = false;
    if ($result && pg_num_rows($result) > 0) {
        $profile_exists = true;
    }
    
    close_pg_connection($connection);
    return $profile_exists;
}

// Registra un profilo cliente
function register_cliente_profile($email, $nome, $codice_fiscale) {
    include_once("conf/conf.php");
    $connection = open_pg_connection();
    
    // Verifica se il codice fiscale esiste già
    $check_query = "SELECT codicefiscale FROM shoepal.cliente WHERE codicefiscale = $1";
    $check_result = pg_query_params($connection, $check_query, array($codice_fiscale));
    
    if ($check_result && pg_num_rows($check_result) > 0) {
        close_pg_connection($connection);
        return false; // Codice fiscale già esistente
    }
    
    // Inserisce il profilo cliente
    $insert_query = "INSERT INTO shoepal.cliente (codicefiscale, nome, email) VALUES ($1, $2, $3)";
    $result = pg_query_params($connection, $insert_query, array($codice_fiscale, $nome, $email));
    
    close_pg_connection($connection);
    return $result !== false;
}

// Connessione al database PostgreSQL
function open_pg_connection(){
    include_once("conf/conf.php");
    $connection="host= dbname= user= password=";

    $connection = "host=" . myHost . " dbname=" . myDb . " user=" . myUser . " password=" . myPassword;
    return pg_connect($connection);
}

// Chiude la connessione al database PostgreSQL
function close_pg_connection($database){
    if (is_resource($database) && get_resource_type($database) === 'pgsql link') {
        return pg_close($database);
    }
    return true;
}

// Ottieni le informazioni sui messaggi di sessione
function get_session_messages() {
    $messages = [
        'error' => '',
        'success' => '',
        'info' => ''
    ];
    
    if (isset($_SESSION['error_msg'])) {
        $messages['error'] = $_SESSION['error_msg'];
        unset($_SESSION['error_msg']);
    }
    
    if (isset($_SESSION['success_msg'])) {
        $messages['success'] = $_SESSION['success_msg'];
        unset($_SESSION['success_msg']);
    }
    
    if (isset($_SESSION['info_msg'])) {
        $messages['info'] = $_SESSION['info_msg'];
        unset($_SESSION['info_msg']);
    }
    
    return $messages;
}

// Ricarica la pagina con un messaggio di sessione
function redirect_with_message($location, $type, $message) {
    $_SESSION[$type . '_msg'] = $message;
    header("Location: $location");
    exit();
}

// Gestisce il login dell'utente e reindirizza in base al ruolo
function handle_user_login($username, $password) {
    $result = login($username, $password);
    if (is_null($result)) {
        redirect_with_message('login.php', 'error', 'Credenziali errate. Ripetere il login');
    }
    
    if ($result['ruolo'] == 'manager') {
        $_SESSION['user'] = $result['username'];
        $_SESSION['ruolo'] = $result['ruolo'];
        header("Location: managerdashboard.php");
        exit();
    } else if ($result['ruolo'] == 'cliente') {
        if (check_cliente_profile($result['username'])) {
            $_SESSION['user'] = $result['username'];
            $_SESSION['ruolo'] = $result['ruolo'];
            header("Location: home.php");
            exit();
        } else {
            $_SESSION['temp_user'] = $result['username'];
            $_SESSION['temp_ruolo'] = $result['ruolo'];
            $_SESSION['register_profile_email'] = $result['username'];
            $_SESSION['info_msg'] = 'Prima di accedere alla tua area, completa la registrazione del profilo.';
        }
    }
}

// Gestisce la registrazione dell'utente cliente
function handle_cliente_registration($email, $password) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect_with_message('login.php', 'error', 'Email non valida.');
    }
    
    if (register_cliente($email, $password)) {
        redirect_with_message('login.php', 'success', 'Profilo creato con successo! Effettua il login.');
    } else {
        redirect_with_message('login.php', 'error', 'Email già esistente o errore durante la registrazione.');
    }
}

// Gestisce la registrazione del profilo cliente
function handle_cliente_profile_registration($nome, $codice_fiscale, $email) {
    $codice_fiscale = strtoupper(trim($codice_fiscale));
    
    if (strlen($codice_fiscale) != 16 || !preg_match('/^[A-Z0-9]+$/', $codice_fiscale)) {
        redirect_with_message('login.php', 'error', 'Codice fiscale non valido. Deve essere di 16 caratteri.');
    }
    
    if (register_cliente_profile($email, $nome, $codice_fiscale)) {
        if (isset($_SESSION['temp_user']) && isset($_SESSION['temp_ruolo'])) {
            $_SESSION['user'] = $_SESSION['temp_user'];
            $_SESSION['ruolo'] = $_SESSION['temp_ruolo'];
            unset($_SESSION['temp_user']);
            unset($_SESSION['temp_ruolo']);
        }
        unset($_SESSION['register_profile_email']);
        redirect_with_message('home.php', 'success', 'Profilo cliente registrato con successo!');
    } else {
        redirect_with_message('login.php', 'error', 'Codice fiscale già esistente o errore durante la registrazione del profilo.');
    }
}

// Controlla se esiste una sessione utente e reindirizza in base al ruolo
function check_existing_session() {
    if (isset($_SESSION['user']) && isset($_SESSION['ruolo'])) {
        $ruolo = $_SESSION['ruolo'];
        if ($ruolo == 'manager') {
            header("Location: managerdashboard.php");
            exit();
        } else if ($ruolo == 'cliente') {
            if (check_cliente_profile($_SESSION['user'])) {
                header("Location: home.php");
                exit();
            } else {
                $_SESSION['temp_user'] = $_SESSION['user'];
                $_SESSION['temp_ruolo'] = $_SESSION['ruolo'];
                unset($_SESSION['user']);
                unset($_SESSION['ruolo']);
                $_SESSION['register_profile_email'] = $_SESSION['temp_user'];
                $_SESSION['info_msg'] = 'Prima di accedere alla tua area, completa la registrazione del profilo.';
            }
        }
    }
    
    if (isset($_SESSION['temp_user']) && isset($_SESSION['temp_ruolo']) && $_SESSION['temp_ruolo'] == 'cliente') {
        $_SESSION['register_profile_email'] = $_SESSION['temp_user'];
        if (empty($_SESSION['info_msg'])) {
            $_SESSION['info_msg'] = 'Prima di accedere alla tua area, completa la registrazione del profilo.';
        }
    }
}

// Pulisce le variabili di sessione temporanee
function clear_temp_session() {
    unset($_SESSION['temp_user']);
    unset($_SESSION['temp_ruolo']);
    unset($_SESSION['register_profile_email']);
    unset($_SESSION['info_msg']);
}

// 2. Negozi, Filtri e Prodotti

// Ottiene i dettagli di un negozio specifico
function get_negozio_details($negozio_id) {
    $connection = open_pg_connection();
    
    $query = "SELECT idNegozio as id, indirizzo, responsabile
              FROM shoepal.negozio 
              WHERE idNegozio = $1 AND attivo = true";
    
    $result = pg_query_params($connection, $query, array($negozio_id));
    $negozio = null;
    
    if ($result && pg_num_rows($result) > 0) {
        $negozio = pg_fetch_assoc($result);
        // Estrai la città dall'indirizzo usando PHP invece di SQL (più efficiente)
        $parts = explode(',', $negozio['indirizzo']);
        $negozio['citta'] = strtolower(trim(end($parts)));
    }
    
    close_pg_connection($connection);
    return $negozio;
}

 // Ottiene tutti i negozi attivi ordinati per città
function get_all_negozi() {
    $connection = open_pg_connection();
    
    $query = "SELECT idNegozio as id, indirizzo
              FROM shoepal.negozio 
              WHERE attivo = true
              ORDER BY indirizzo";
    
    $result = pg_query_params($connection, $query, []);
    $negozi = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            // Estrai la città dall'indirizzo usando PHP invece di SQL (più efficiente)
            $parts = explode(',', $row['indirizzo']);
            $row['citta'] = strtolower(trim(end($parts)));
            $negozi[] = $row;
        }
        
        // Ordina i negozi per città usando PHP
        usort($negozi, function($a, $b) {
            return strcmp($a['citta'], $b['citta']);
        });
    }
    
    close_pg_connection($connection);
    return $negozi;
}

 // Ottiene i filtri per genere con conteggi per un negozio specifico
function get_filtri_genere($negozio_id, $search_term = '', $tipologia_filtro = '') {
    $connection = open_pg_connection();
    
    $query = "SELECT p.sesso, COUNT(DISTINCT p.idProdotto) as count
              FROM shoepal.prodotto p
              JOIN shoepal.disponibilità d ON p.idProdotto = d.idProdotto
              WHERE d.idNegozio = $1 AND d.quantità > 0";
    
    $params = array($negozio_id);
    $param_counter = 2;
    
    if (!empty($search_term)) {
        $search_param = '%' . $search_term . '%';
        $query .= " AND (LOWER(p.nome) LIKE LOWER($" . $param_counter . ") OR LOWER(p.descrizione) LIKE LOWER($" . $param_counter . ") OR LOWER(p.tipologia) LIKE LOWER($" . $param_counter . "))";
        $params[] = $search_param;
        $param_counter++;
    }
    
    if (!empty($tipologia_filtro)) {
        $query .= " AND p.tipologia = $" . $param_counter;
        $params[] = $tipologia_filtro;
    }
    
    $query .= " GROUP BY p.sesso ORDER BY p.sesso";
    $result = pg_query_params($connection, $query, $params);
    
    $filtri = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $filtri[] = $row;
        }
    }
    
    close_pg_connection($connection);
    return $filtri;
}

// Ottiene i filtri per tipologia con conteggi per un negozio specifico
function get_filtri_tipologia($negozio_id, $search_term = '', $genere_filtro = '') {
    $connection = open_pg_connection();
    
    $query = "SELECT p.tipologia, COUNT(DISTINCT p.idProdotto) as count
              FROM shoepal.prodotto p
              JOIN shoepal.disponibilità d ON p.idProdotto = d.idProdotto
              WHERE d.idNegozio = $1 AND d.quantità > 0";
    
    $params = array($negozio_id);
    $param_counter = 2;
    
    if (!empty($search_term)) {
        $search_param = '%' . $search_term . '%';
        $query .= " AND (LOWER(p.nome) LIKE LOWER($" . $param_counter . ") OR LOWER(p.descrizione) LIKE LOWER($" . $param_counter . ") OR LOWER(p.tipologia) LIKE LOWER($" . $param_counter . "))";
        $params[] = $search_param;
        $param_counter++;
    }
    
    if (!empty($genere_filtro)) {
        $query .= " AND p.sesso = $" . $param_counter;
        $params[] = $genere_filtro;
    }
    
    $query .= " GROUP BY p.tipologia ORDER BY p.tipologia";
    $result = pg_query_params($connection, $query, $params);
    
    $filtri = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $filtri[] = $row;
        }
    }
    
    close_pg_connection($connection);
    return $filtri;
}

// Ottiene i prodotti disponibili in un negozio con filtri applicati
function get_prodotti_negozio($negozio_id, $search_term = '', $genere_filtro = '', $tipologia_filtro = '', $marca_filtro = '', $prezzo_min_filtro = null, $prezzo_max_filtro = null, $taglia_filtro = '') {
    $connection = open_pg_connection();
    
    $query = "SELECT p.idProdotto, p.nome, p.descrizione, p.marca, p.sesso, p.tipologia,
                     MIN(d.prezzo) as prezzo_min, MAX(d.prezzo) as prezzo_max,
                     ARRAY_AGG(d.taglia ORDER BY d.taglia) as taglie_disponibili,
                     ARRAY_AGG(d.quantità ORDER BY d.taglia) as quantita_disponibili
              FROM shoepal.prodotto p
              JOIN shoepal.disponibilità d ON p.idProdotto = d.idProdotto
              WHERE d.idNegozio = $1 AND d.quantità > 0";
    
    $params = array($negozio_id);
    $param_counter = 2;
    
    if (!empty($search_term)) {
        $search_param = '%' . $search_term . '%';
        $query .= " AND (LOWER(p.nome) LIKE LOWER($" . $param_counter . ") OR LOWER(p.descrizione) LIKE LOWER($" . $param_counter . ") OR LOWER(p.tipologia) LIKE LOWER($" . $param_counter . ") OR LOWER(p.marca) LIKE LOWER($" . $param_counter . "))";
        $params[] = $search_param;
        $param_counter++;
    }
    
    if (!empty($genere_filtro)) {
        $query .= " AND p.sesso = $" . $param_counter;
        $params[] = $genere_filtro;
        $param_counter++;
    }
    
    if (!empty($tipologia_filtro)) {
        $query .= " AND p.tipologia = $" . $param_counter;
        $params[] = $tipologia_filtro;
        $param_counter++;
    }
    
    if (!empty($marca_filtro)) {
        $query .= " AND p.marca = $" . $param_counter;
        $params[] = $marca_filtro;
        $param_counter++;
    }
    
    if (!empty($taglia_filtro)) {
        $query .= " AND d.taglia = $" . $param_counter;
        $params[] = $taglia_filtro;
        $param_counter++;
    }
    
    // Filtri per fascia di prezzo applicati dopo il GROUP BY tramite HAVING
    $having_conditions = array();
    if (!is_null($prezzo_min_filtro) && $prezzo_min_filtro > 0) {
        $having_conditions[] = "MIN(d.prezzo) >= $" . $param_counter;
        $params[] = $prezzo_min_filtro;
        $param_counter++;
    }
    
    if (!is_null($prezzo_max_filtro) && $prezzo_max_filtro > 0) {
        $having_conditions[] = "MAX(d.prezzo) <= $" . $param_counter;
        $params[] = $prezzo_max_filtro;
        $param_counter++;
    }
    
    $query .= " GROUP BY p.idProdotto, p.nome, p.descrizione, p.marca, p.sesso, p.tipologia";
    
    if (!empty($having_conditions)) {
        $query .= " HAVING " . implode(' AND ', $having_conditions);
    }
    
    $query .= " ORDER BY p.nome";
    
    $result = pg_query_params($connection, $query, $params);
    
    $prodotti = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            // Converti gli array PostgreSQL in array PHP
            $row['taglie_disponibili'] = convert_pg_array_to_php($row['taglie_disponibili']);
            $row['quantita_disponibili'] = convert_pg_array_to_php($row['quantita_disponibili']);
            
            // Crea un array associativo taglia => quantità per facilità d'uso
            $taglie = $row['taglie_disponibili'];
            $quantita = $row['quantita_disponibili'];
            $row['disponibilita_per_taglia'] = array();
            
            if (count($taglie) === count($quantita)) {
                for ($i = 0; $i < count($taglie); $i++) {
                    $row['disponibilita_per_taglia'][$taglie[$i]] = (int)$quantita[$i];
                }
            }
            
            $prodotti[] = $row;
        }
    }
    
    close_pg_connection($connection);
    return $prodotti;
}

// Formatta il range di prezzo per la visualizzazione
function format_price_range($prezzo_min, $prezzo_max, $decimali = 2) {
    if ($prezzo_min == $prezzo_max) {
        return '€' . number_format($prezzo_min, $decimali);
    } else {
        return '€' . number_format($prezzo_min, $decimali) . ' - €' . number_format($prezzo_max, $decimali);
    }
}

// Ottiene la disponibilità di un prodotto in un negozio specifico
function get_disponibilita_per_taglia($prodotto_id, $negozio_id) {
    $connection = open_pg_connection();
    
    $query = "SELECT taglia, quantità, prezzo 
              FROM shoepal.disponibilità 
              WHERE idprodotto = $1 AND idnegozio = $2 
              ORDER BY taglia";
    
    $result = pg_query_params($connection, $query, array($prodotto_id, $negozio_id));
    
    $disponibilita = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $disponibilita[$row['taglia']] = [
                'quantita' => (int)$row['quantità'],
                'prezzo' => (float)$row['prezzo']
            ];
        }
    }
    
    close_pg_connection($connection);
    return $disponibilita;
}

// Cerca prodotti in tutti i negozi
function get_prodotti_ricerca_globale($search_term) {
    $connection = open_pg_connection();
    
    $query = "SELECT DISTINCT p.idProdotto, p.nome, p.descrizione, p.sesso, p.tipologia,
                     d.idNegozio, n.indirizzo as negozio_indirizzo,
                     MIN(d.prezzo) as prezzo_min, MAX(d.prezzo) as prezzo_max,
                     ARRAY_AGG(DISTINCT d.taglia ORDER BY d.taglia) as taglie_disponibili
              FROM shoepal.prodotto p
              JOIN shoepal.disponibilità d ON p.idProdotto = d.idProdotto
              JOIN shoepal.negozio n ON d.idNegozio = n.idNegozio
              WHERE d.quantità > 0 AND n.attivo = true
              AND (LOWER(p.nome) LIKE LOWER($1) OR LOWER(p.descrizione) LIKE LOWER($1) OR LOWER(p.tipologia) LIKE LOWER($1))
              GROUP BY p.idProdotto, p.nome, p.descrizione, p.sesso, p.tipologia, d.idNegozio, n.indirizzo
              ORDER BY n.indirizzo, p.nome";
    
    $search_param = '%' . $search_term . '%';
    $result = pg_query_params($connection, $query, array($search_param));
    
    $prodotti_per_negozio = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $negozio_id = $row['idnegozio'];
            $negozio_indirizzo = $row['negozio_indirizzo'];
            
            // Crea nome negozio friendly
            $indirizzo_parti = explode(',', $negozio_indirizzo);
            $citta = trim(end($indirizzo_parti));
            $nome_negozio = get_negozio_display_name($citta);
            
            if (!isset($prodotti_per_negozio[$negozio_id])) {
                $prodotti_per_negozio[$negozio_id] = [
                    'negozio_id' => $negozio_id,
                    'negozio_nome' => $nome_negozio,
                    'negozio_indirizzo' => $negozio_indirizzo,
                    'prodotti' => []
                ];
            }
            
            // Converti taglie_disponibili se è una stringa
            $taglie = $row['taglie_disponibili'];
            if (is_string($taglie)) {
                $taglie = str_replace(['{', '}'], '', $taglie);
                $taglie = array_filter(explode(',', $taglie));
            }
            
            $prodotti_per_negozio[$negozio_id]['prodotti'][] = [
                'idprodotto' => $row['idprodotto'],
                'nome' => $row['nome'],
                'descrizione' => $row['descrizione'],
                'sesso' => $row['sesso'],
                'tipologia' => $row['tipologia'],
                'prezzo_min' => (float)$row['prezzo_min'],
                'prezzo_max' => (float)$row['prezzo_max'],
                'taglie_disponibili' => $taglie
            ];
        }
    }
    
    close_pg_connection($connection);
    return $prodotti_per_negozio;
}

// Ottiene le marche disponibili in un negozio
function get_marche_negozio($negozio_id, $genere_filtro = '') {
    $connection = open_pg_connection();
    
    $query = "SELECT DISTINCT p.marca
              FROM shoepal.prodotto p
              JOIN shoepal.disponibilità d ON p.idProdotto = d.idProdotto
              WHERE d.idNegozio = $1 AND d.quantità > 0 AND p.marca IS NOT NULL";
    
    $params = array($negozio_id);
    $param_counter = 2;
    
    if (!empty($genere_filtro)) {
        $query .= " AND p.sesso = $" . $param_counter;
        $params[] = $genere_filtro;
    }
    
    $query .= " ORDER BY p.marca";
    $result = pg_query_params($connection, $query, $params);
    
    $marche = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $marche[] = $row;
        }
    }
    
    close_pg_connection($connection);
    return $marche;
}

// Ottiene la fascia di prezzo minima e massima per un negozio
function get_fascia_prezzo_negozio($negozio_id) {
    $connection = open_pg_connection();
    
    $query = "SELECT MIN(d.prezzo) as prezzo_min, MAX(d.prezzo) as prezzo_max
              FROM shoepal.disponibilità d
              WHERE d.idNegozio = $1 AND d.quantità > 0";
    
    $result = pg_query_params($connection, $query, array($negozio_id));
    
    $fascia = array('prezzo_min' => 0, 'prezzo_max' => 1000);
    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $fascia = array(
            'prezzo_min' => (float)$row['prezzo_min'],
            'prezzo_max' => (float)$row['prezzo_max']
        );
    }
    
    close_pg_connection($connection);
    return $fascia;
}

// Ottiene le taglie disponibili in un negozio con conteggio prodotti
function get_filtri_taglie($negozio_id, $search_term = '', $genere_filtro = '', $tipologia_filtro = '', $marca_filtro = '') {
    $connection = open_pg_connection();
    
    $query = "SELECT d.taglia, COUNT(DISTINCT p.idProdotto) as count
              FROM shoepal.prodotto p
              JOIN shoepal.disponibilità d ON p.idProdotto = d.idProdotto
              WHERE d.idNegozio = $1 AND d.quantità > 0";
    
    $params = array($negozio_id);
    $param_counter = 2;
    
    if (!empty($search_term)) {
        $search_param = '%' . $search_term . '%';
        $query .= " AND (LOWER(p.nome) LIKE LOWER($" . $param_counter . ") OR LOWER(p.descrizione) LIKE LOWER($" . $param_counter . ") OR LOWER(p.tipologia) LIKE LOWER($" . $param_counter . ") OR LOWER(p.marca) LIKE LOWER($" . $param_counter . "))";
        $params[] = $search_param;
        $param_counter++;
    }
    
    if (!empty($genere_filtro)) {
        $query .= " AND p.sesso = $" . $param_counter;
        $params[] = $genere_filtro;
        $param_counter++;
    }
    
    if (!empty($tipologia_filtro)) {
        $query .= " AND p.tipologia = $" . $param_counter;
        $params[] = $tipologia_filtro;
        $param_counter++;
    }
    
    if (!empty($marca_filtro)) {
        $query .= " AND p.marca = $" . $param_counter;
        $params[] = $marca_filtro;
        $param_counter++;
    }
    
    $query .= " GROUP BY d.taglia ORDER BY d.taglia::numeric";
    
    $result = pg_query_params($connection, $query, $params);
    
    $taglie = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $taglie[] = $row;
        }
    }
    
    close_pg_connection($connection);
    return $taglie;
}

// Ottiene i negozi con gli orari di apertura
function get_negozi_with_orari($connection) {
    $query = "
        SELECT n.*, o.giorno, o.orainizio, o.orafine
        FROM shoepal.negozio n
        LEFT JOIN shoepal.orario o ON n.idnegozio = o.idnegozio
        WHERE n.attivo = true
        ORDER BY n.indirizzo, 
            CASE o.giorno 
                WHEN 'Lunedì' THEN 1
                WHEN 'Martedì' THEN 2
                WHEN 'Mercoledì' THEN 3
                WHEN 'Giovedì' THEN 4
                WHEN 'Venerdì' THEN 5
                WHEN 'Sabato' THEN 6
                WHEN 'Domenica' THEN 7
                ELSE 8
            END";
    
    $result = pg_query($connection, $query);
    
    // Raggruppa i dati per negozio
    $negozi = array();
    while ($row = pg_fetch_assoc($result)) {
        $idNegozio = $row['idnegozio'];
        
        if (!isset($negozi[$idNegozio])) {
            $negozi[$idNegozio] = array(
                'idnegozio' => $row['idnegozio'],
                'indirizzo' => $row['indirizzo'],
                'responsabile' => $row['responsabile'],
                'attivo' => $row['attivo'],
                'orari' => array()
            );
        }
        
        if ($row['giorno']) {
            $negozi[$idNegozio]['orari'][] = array(
                'giorno' => $row['giorno'],
                'orainizio' => $row['orainizio'],
                'orafine' => $row['orafine']
            );
        }
    }
    
    return $negozi;
}

// Ottiene gli ordini di un cliente basandosi sul codice fiscale
function get_ordini_cliente($connection, $codice_fiscale) {
    $query = "SELECT f.idfattura, f.dataacquisto, f.totaleoriginale, f.totalepagato, f.puntiaccumulati, 
                     f.scontopercentuale, n.indirizzo as negozio_indirizzo
              FROM shoepal.fattura f
              JOIN shoepal.negozio n ON f.idnegozio = n.idnegozio
              WHERE f.codicefiscale = $1
              ORDER BY f.dataacquisto DESC";
              
    return pg_query_params($connection, $query, array($codice_fiscale));
}

// Ottiene i dettagli di una fattura specifica
function get_dettagli_fattura($connection, $id_fattura) {
    $query = "SELECT fd.quantità, fd.prezzounitario, fd.taglia, p.nome 
              FROM shoepal.fatturadettagli fd
              JOIN shoepal.prodotto p ON fd.idprodotto = p.idprodotto
              WHERE fd.idfattura = $1";
              
    return pg_query_params($connection, $query, array($id_fattura));
}

// Ottiene tutti i valori unici di sesso dai prodotti
function get_sessi_options() {
    $query = "SELECT DISTINCT sesso FROM shoepal.prodotto WHERE sesso IS NOT NULL ORDER BY sesso";
    $result = execute_query($query);
    return array_column($result, 'sesso');
}

// Ottiene tutti i valori unici di tipologia dai prodotti
function get_tipologie_options() {
    $query = "SELECT DISTINCT tipologia FROM shoepal.prodotto WHERE tipologia IS NOT NULL ORDER BY tipologia";
    $result = execute_query($query);
    return array_column($result, 'tipologia');
}

// Ottiene tutti i valori unici di marca dai prodotti
function get_marche_options() {
    $query = "SELECT DISTINCT marca FROM shoepal.prodotto WHERE marca IS NOT NULL ORDER BY marca";
    $result = execute_query($query);
    return array_column($result, 'marca');
}

// 3. Esecuzione di query generiche

// Funzioni genereriche per l'esecuzione di query, per semplificare il codice e migliorare la leggibilità e riusabilità

// Esegue una query parametrizzata e restituisce un array con tutti i risultati
function execute_query($query, $params = []) {
    $connection = open_pg_connection();
    
    if (empty($params)) {
        $result = pg_query($connection, $query);
    } else {
        $result = pg_query_params($connection, $query, $params);
    }
    
    $data = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    
    close_pg_connection($connection);
    return $data;
}

// Esegue una query parametrizzata e restituisce un singolo risultato
function execute_single_query($query, $params = []) {
    $connection = open_pg_connection();
    
    if (empty($params)) {
        $result = pg_query($connection, $query);
    } else {
        $result = pg_query_params($connection, $query, $params);
    }
    
    $data = null;
    if ($result && pg_num_rows($result) > 0) {
        $data = pg_fetch_assoc($result);
    }
    
    close_pg_connection($connection);
    return $data;
}

//Esegue una query di modifica (INSERT, UPDATE, DELETE) e restituisce il successo
function execute_modification_query($query, $params = []) {
    $connection = open_pg_connection();
    
    if (empty($params)) {
        $result = pg_query($connection, $query);
    } else {
        $result = pg_query_params($connection, $query, $params);
    }
    
    $success = $result !== false;
    close_pg_connection($connection);
    return $success;
}

// 4. Funzioni di utilità varie

// Ottiene i dettagli di un prodotto specifico
function get_prodotto_by_id($idProdotto) {
    $query = "SELECT idprodotto, nome, descrizione, marca, sesso, tipologia FROM shoepal.prodotto WHERE idprodotto = $1";
    return execute_single_query($query, [$idProdotto]);
}

// Ottiene tutti i prodotti del catalogo
function get_all_prodotti_catalogo() {
    $query = "SELECT idprodotto, nome, descrizione, marca, sesso, tipologia FROM shoepal.prodotto ORDER BY nome";
    return execute_query($query);
}

// Converte un array PostgreSQL in formato stringa in un array PHP
function convert_pg_array_to_php($pg_array) {
    $cleaned = str_replace(['{', '}'], '', $pg_array);
    return explode(',', $cleaned);
}

 // Genera il nome visualizzato di un negozio basandosi sull'input dell'indirizzo o città
function get_negozio_display_name($input) {
    if (empty($input)) {
        return 'Shoepal';
    }
    
    // Se contiene una virgola, estrae la città dall'indirizzo
    if (strpos($input, ',') !== false) {
        $parti = array_map('trim', explode(',', $input));
        $citta = end($parti);
    } else {
        // Altrimenti assume che sia già solo il nome della città
        $citta = $input;
    }
    
    // Pulisce caratteri speciali e capitalizza
    $citta = str_replace(['-', '_'], ' ', $citta);
    $citta = ucwords(strtolower($citta));
    
    return "Shoepal " . $citta;
}

// Ottiene il percorso dell'immagine di un prodotto basandosi sull'ID
function get_product_image_path($product_id, $fallback_image = 'assets/sneakers.png') {
    $image_path = "assets/prodotti/{$product_id}.webp";
    
    if (!file_exists($image_path)) {
        return $fallback_image;
    }
    
    return $image_path;
}

// Genera il percorso dell'immagine di un negozio basandosi sulla città
function get_negozio_image_path($citta, $fallback_image = 'assets/sneakers.png') {
    $image_path = "assets/negozi/{$citta}.webp";
    
    if (!file_exists($image_path)) {
        return $fallback_image;
    }
    
    return $image_path;
}

