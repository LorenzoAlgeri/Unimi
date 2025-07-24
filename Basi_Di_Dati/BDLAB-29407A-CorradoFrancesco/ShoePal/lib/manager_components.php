<?php
/**
 * COMPONENTI MANAGER ShoePal
 * 
 * Gestione funzioni di utilità per il manager
 * 
 * SEZIONI:
 * 1. Gestione Negozi
 * 2. Gestione Prodotti
 * 3. Gestione Disponibilità e Magazzino
 * 4. Gestione Fornitori e Forniture
 * 5. Gestione Ordini e Rifornimenti
 * 6. Gestione Clienti e Utenti
 * 7. Gestione Tessere Fedeltà
 * 8. Statistiche e Vendite
 * 9. Funzioni AJAX
 */

// La logica di molte funzioni è stata spostata sul database e viene solamente chiamata in php

// Include le funzioni base del sistema
require_once("functions.php");

// 1. GESTIONE NEGOZI

// Ottiene tutti i negozi con informazioni complete per il manager
function get_all_negozi_manager() {
    $query = "SELECT * FROM shoepal.get_all_negozi_manager()";
    return execute_query($query);
}

// Ottiene solo i negozi attivi ordinati per responsabile
function get_negozi_attivi() {
    $query = "SELECT * FROM shoepal.get_negozi_attivi()";
    return execute_query($query);
}

// Ottiene gli orari di apertura di un negozio ordinati per giorno della settimana
function get_orari_negozio($idNegozio) {
    $query = "SELECT * FROM shoepal.get_orari_negozio($1)";
    return execute_query($query, [$idNegozio]);
}

// Crea un nuovo negozio e restituisce l'ID assegnato
function create_negozio($responsabile, $indirizzo) {
    $connection = open_pg_connection();
    $query = "INSERT INTO shoepal.negozio (responsabile, indirizzo, attivo) VALUES ($1, $2, true) RETURNING idnegozio";
    $result = pg_query_params($connection, $query, [$responsabile, $indirizzo]);
    
    $idNegozio = null;
    if ($result) {
        $row = pg_fetch_assoc($result);
        $idNegozio = $row['idnegozio'];
    }
    
    close_pg_connection($connection);
    return $idNegozio;
}

// Chiude un negozio impostando la data di chiusura
function close_negozio($idNegozio) {
    $query = "UPDATE shoepal.negozio SET attivo = false, datachiusura = CURRENT_DATE WHERE idnegozio = $1";
    return execute_modification_query($query, [$idNegozio]);
}

// Riapre un negozio precedentemente chiuso
function reopen_negozio($idNegozio) {
    $query = "UPDATE shoepal.negozio SET attivo = true, datachiusura = NULL WHERE idnegozio = $1";
    return execute_modification_query($query, [$idNegozio]);
}

// Aggiorna gli orari di apertura di un negozio per un giorno specifico
function update_orario_negozio($idNegozio, $giorno, $oraInizio, $oraFine) {
    $connection = open_pg_connection();
    
    // Tento di aggiornare l'orario se esiste
    $query = "UPDATE shoepal.orario SET orainizio = $1, orafine = $2 WHERE idnegozio = $3 AND giorno = $4";
    $result = pg_query_params($connection, $query, [$oraInizio, $oraFine, $idNegozio, $giorno]);
    
    // Se non esiste, lo crea
    if (!$result || pg_affected_rows($result) == 0) {
        $insert_query = "INSERT INTO shoepal.orario (idnegozio, giorno, orainizio, orafine) VALUES ($1, $2, $3, $4)";
        $result = pg_query_params($connection, $insert_query, [$idNegozio, $giorno, $oraInizio, $oraFine]);
    }
    
    close_pg_connection($connection);
    return $result !== false;
}

// Salva l'orario di un negozio sostituendo completamente l'orario esistente per il giorno
function save_orario_negozio($idNegozio, $giorno, $oraInizio, $oraFine) {
    $connection = open_pg_connection();
    
    // Elimino l'orario esistente per il giorno specificato
    $delete_query = "DELETE FROM shoepal.orario WHERE idnegozio = $1 AND giorno = $2";
    pg_query_params($connection, $delete_query, [$idNegozio, $giorno]);
    
    // Inserisce il nuovo orario
    $insert_query = "INSERT INTO shoepal.orario (idnegozio, giorno, orainizio, orafine) VALUES ($1, $2, $3, $4)";
    $result = pg_query_params($connection, $insert_query, [$idNegozio, $giorno, $oraInizio, $oraFine]);
    
    close_pg_connection($connection);
    return $result !== false;
}

// 2- GESTIONE PRODOTTI

// Ottiene tutti i prodotti con informazioni aggiuntive per il manager (include statistiche sui fornitori e disponibilità totali)
function get_all_prodotti_manager() {
    $query = "SELECT * FROM shoepal.get_all_prodotti_manager()";
    return execute_query($query);
}

// Crea un nuovo prodotto e restituisce l'ID assegnato
function create_prodotto($nome, $descrizione, $marca, $sesso, $tipologia) {
    $connection = open_pg_connection();
    $query = "INSERT INTO shoepal.prodotto (nome, descrizione, marca, sesso, tipologia) 
              VALUES ($1, $2, $3, $4, $5) RETURNING idprodotto";
    $result = pg_query_params($connection, $query, [$nome, $descrizione, $marca, $sesso, $tipologia]);
    
    $idProdotto = null;
    if ($result) {
        $row = pg_fetch_assoc($result);
        $idProdotto = $row['idprodotto'];
    }
    
    close_pg_connection($connection);
    return $idProdotto;
}

// Aggiorna i dati di un prodotto esistente
function update_prodotto($idProdotto, $nome, $descrizione, $marca, $sesso, $tipologia) {
    $query = "UPDATE shoepal.prodotto SET nome = $1, descrizione = $2, marca = $3, sesso = $4, tipologia = $5 
              WHERE idprodotto = $6";
    return execute_modification_query($query, [$nome, $descrizione, $marca, $sesso, $tipologia, $idProdotto]);
}

// Elimina un prodotto dopo aver verificato che non ci siano dipendenze. Restituisce un array con successo e messaggio.
function delete_prodotto($idProdotto) {
    $connection = open_pg_connection();
    
    // Verifica disponibilità attive
    $check_query = "SELECT COUNT(*) as count FROM shoepal.disponibilità WHERE idprodotto = $1 AND quantità > 0";
    $check_result = pg_query_params($connection, $check_query, [$idProdotto]);
    
    if ($check_result) {
        $row = pg_fetch_assoc($check_result);
        if ($row['count'] > 0) {
            close_pg_connection($connection);
            return ['success' => false, 'message' => 'Impossibile eliminare: prodotto con disponibilità attive'];
        }
    }
    
    // Verifica fatture associate
    $check_fatture = "SELECT COUNT(*) as count FROM shoepal.fatturadettagli WHERE idprodotto = $1";
    $fatture_result = pg_query_params($connection, $check_fatture, [$idProdotto]);
    
    if ($fatture_result) {
        $row = pg_fetch_assoc($fatture_result);
        if ($row['count'] > 0) {
            close_pg_connection($connection);
            return ['success' => false, 'message' => 'Impossibile eliminare: prodotto con fatture associate'];
        }
    }
    
    // Elimina il prodotto
    $query = "DELETE FROM shoepal.prodotto WHERE idprodotto = $1";
    $result = pg_query_params($connection, $query, [$idProdotto]);
    
    close_pg_connection($connection);
    return ['success' => $result !== false, 'message' => 'Prodotto eliminato con successo'];
}

// 3. GESTIONE DISPONIBILITÀ E MAGAZZINO

// Ottiene tutte le disponibilità organizzate per negozio con dettagli prodotto
function get_disponibilita_by_negozio() {
    $query = "SELECT * FROM shoepal.get_disponibilita_by_negozio()";
    return execute_query($query);
}

// Aggiorna o inserisce la disponibilità di un prodotto in un negozio: se non esiste lo crea, altrimenti aggiorna i valori
function update_disponibilita($idNegozio, $idProdotto, $taglia, $prezzo, $quantita) {
    $query = "INSERT INTO shoepal.disponibilità (idnegozio, idprodotto, taglia, prezzo, quantità) 
              VALUES ($1, $2, $3, $4, $5) 
              ON CONFLICT (idnegozio, idprodotto, taglia) 
              DO UPDATE SET prezzo = $4, quantità = $5";
    return execute_modification_query($query, [$idNegozio, $idProdotto, $taglia, $prezzo, $quantita]);
}

// Sposta prodotto tra i negozi
function sposta_prodotto_tra_negozi($idNegozioOrigine, $idNegozioDest, $idProdotto, $taglia, $quantita) {
    $query = "SELECT shoepal.sposta_prodotto_tra_negozi($1, $2, $3, $4, $5) as result";
    $result = execute_single_query($query, [$idNegozioOrigine, $idNegozioDest, $idProdotto, $taglia, $quantita]);
    return ($result && ($result['result'] === true || $result['result'] === 't'));
}

// 4. GESTIONE FORNITORI E FORNITURE

// Ottiene tutti i fornitori
function get_all_fornitori() {
    $query = "SELECT * FROM shoepal.get_all_fornitori()";
    return execute_query($query);
}

// Crea un nuovo fornitore
function create_fornitore($partitaIVA, $indirizzo) {
    $query = "INSERT INTO shoepal.fornitore (partitaiva, indirizzo) VALUES ($1, $2)";
    return execute_modification_query($query, [$partitaIVA, $indirizzo]);
}

// Aggiorna l'indirizzo di un fornitore
function update_fornitore($partitaIVA, $indirizzo) {
    $query = "UPDATE shoepal.fornitore SET indirizzo = $1 WHERE partitaiva = $2";
    return execute_modification_query($query, [$indirizzo, $partitaIVA]);
}

// Ottiene un fornitore per partita IVA
function get_fornitore_by_piva($partitaIVA) {
    $query = "SELECT * FROM shoepal.get_fornitore_by_piva($1)";
    return execute_single_query($query, [$partitaIVA]);
}

// Ottiene i prodotti più ordinati da un fornitore specifico
function get_prodotti_piu_ordinati_fornitore($partitaIVA, $limit = 5) {
    $query = "SELECT * FROM shoepal.get_prodotti_piu_ordinati_fornitore($1, $2)";
    return execute_query($query, [$partitaIVA, $limit]);
}

// Ottiene le forniture di un fornitore specifico
function get_forniture_by_fornitore($partitaIVA) {
    $query = "SELECT * FROM shoepal.get_forniture_by_fornitore($1)";
    return execute_query($query, [$partitaIVA]);
}

// Ottiene statistiche di un fornitore specifico
function get_fornitore_statistiche($partitaIVA) {
    $result = execute_single_query("SELECT * FROM shoepal.get_fornitore_statistiche($1)", [$partitaIVA]);
    return $result ? $result : [
        'prodotti_totali' => 0,
        'scorte_totali' => 0,
        'valore_totale' => 0,
        'costo_medio' => 0,
        'costo_minimo' => 0,
        'costo_massimo' => 0,
        'tipologie_prodotti' => 0
    ];
}

// Ottiene forniture raggruppate per taglia di un fornitore
function get_forniture_per_taglia($partitaIVA) {
    $query = "SELECT * FROM shoepal.get_forniture_per_taglia($1)";
    return execute_query($query, [$partitaIVA]);
}

// Ottiene cronologia ordini per fornitore
function get_cronologia_ordini_fornitore($partitaIVA, $limit = 10) {
    $query = "SELECT * FROM shoepal.get_cronologia_ordini_fornitore($1, $2)";
    return execute_query($query, [$partitaIVA, $limit]);
}

// Crea una nuova fornitura
function create_fornitura($partitaIVA, $idProdotto, $taglia, $disponibilita, $costo) {
    $query = "INSERT INTO shoepal.fornitura (partitaiva, idprodotto, taglia, disponibilità, costo) 
              VALUES ($1, $2, $3, $4, $5)";
    return execute_modification_query($query, [$partitaIVA, $idProdotto, $taglia, $disponibilita, $costo]);
}

// Aggiorna una fornitura esistente
function update_fornitura($partitaIVA, $idProdotto, $taglia, $disponibilita, $costo) {
    $query = "UPDATE shoepal.fornitura 
              SET disponibilità = $4, costo = $5 
              WHERE partitaiva = $1 AND idprodotto = $2 AND taglia = $3";
    return execute_modification_query($query, [$partitaIVA, $idProdotto, $taglia, $disponibilita, $costo]);
}

// Elimina una fornitura
function delete_fornitura($partitaIVA, $idProdotto, $taglia) {
    $query = "DELETE FROM shoepal.fornitura WHERE partitaiva = $1 AND idprodotto = $2 AND taglia = $3";
    return execute_modification_query($query, [$partitaIVA, $idProdotto, $taglia]);
}

// Ottiene il costo minimo di un prodotto dai fornitori, aggiungendo un guadagno del 50% per prodotti non ancora presenti
function get_costo_minimo_prodotto($idProdotto) {
    $result = execute_single_query("SELECT shoepal.get_costo_minimo_prodotto($1) as costo_minimo", [$idProdotto]);
    return $result ? (float)$result['costo_minimo'] : 50.0;
}

// Ottiene la disponibilità massima di un prodotto dai fornitori
function get_disponibilita_massima_prodotto($idProdotto) {
    $result = execute_single_query("SELECT shoepal.get_disponibilita_massima_prodotto($1) as disponibilita_massima", [$idProdotto]);
    return $result ? (int)$result['disponibilita_massima'] : 0;
}

// 5. GESTIONE ORDINI E RIFORNIMENTI

// Ottiene preview di un ordine per validazione prima dell'acquisto
function get_order_preview($idProdotto, $quantita, $taglia) {
    $result = execute_single_query("SELECT * FROM shoepal.get_order_preview($1, $2, $3)", [$idProdotto, $quantita, $taglia]);
    
    if (!$result) {
        return [
            'available' => false,
            'message' => 'Errore nel recupero delle informazioni prodotto',
            'max_disponibile' => 0,
            'quantita_richiesta' => $quantita
        ];
    }
    
    // Controlla se il risultato indica disponibilità E ha valori validi
    if ($result['available'] && 
        !empty($result['partitaiva']) && 
        isset($result['costo']) && 
        $result['costo'] > 0 && 
        isset($result['disponibilità']) && 
        $result['disponibilità'] > 0) {
        
        $costoTotale = $result['costo'] * $quantita;
        return [
            'available' => true,
            'fornitore' => $result['partitaiva'] . (!empty($result['indirizzo']) ? ' (' . $result['indirizzo'] . ')' : ''),
            'prezzo_unitario' => number_format($result['costo'] ?? 0, 2, ',', '.'),
            'disponibilita' => $result['disponibilità'],
            'costo_totale' => $costoTotale
        ];
    } else {
        // Se available è false o mancano valori essenziali, tratta come non disponibile
        $message = $result['message'] ?? '';
        
        // Se available era true ma mancano i dati, crea un messaggio più specifico
        if ($result['available'] && (empty($result['partitaiva']) || !$result['costo'] || $result['costo'] <= 0)) {
            $message = 'Nessun fornitore disponibile per questa combinazione prodotto-taglia-quantità';
        }
        
        return [
            'available' => false,
            'message' => $message ?: 'Prodotto non disponibile presso i fornitori',
            'max_disponibile' => $result['max_disponibile'] ?? 0,
            'quantita_richiesta' => $result['quantita_richiesta'] ?? $quantita
        ];
    }
}

// Ordina prodotti multipli per un negozio creando un singolo ordine
function ordina_prodotti_multipli_per_negozio($prodotti, $idNegozio) {
    $connection = open_pg_connection();
    
    try {
        pg_query($connection, "BEGIN");
        
        // Verifica negozio attivo
        $negozio = execute_single_query("SELECT attivo, indirizzo FROM shoepal.negozio WHERE idnegozio = $1", [$idNegozio]);
        if (!$negozio || !$negozio['attivo']) {
            throw new Exception("Negozio non trovato o chiuso");
        }
        
        // Valida e processa prodotti
        $prodotti_validati = [];
        $costo_totale = 0;
        $fornitore_principale = null;
        
        foreach ($prodotti as $prodotto) {
            $idProdotto = $prodotto['idProdotto'];
            $quantita = $prodotto['quantita'];
            $taglia = $prodotto['taglia'];
            
            // Trova fornitore migliore
            $find_query = "SELECT partitaiva, costo FROM shoepal.fornitura 
                          WHERE idprodotto = $1 AND disponibilità >= $2 AND taglia = $3
                          ORDER BY costo ASC LIMIT 1";
            $fornitore = execute_single_query($find_query, [$idProdotto, $quantita, $taglia]);
            
            if (!$fornitore) {
                throw new Exception("Nessun fornitore disponibile per prodotto ID $idProdotto in taglia $taglia");
            }
            
            if (!$fornitore_principale) {
                $fornitore_principale = $fornitore['partitaiva'];
            }
            
            $prodotti_validati[] = [
                'idProdotto' => $idProdotto,
                'quantita' => $quantita,
                'taglia' => $taglia,
                'costo' => $fornitore['costo'],
                'partitaiva' => $fornitore['partitaiva']
            ];
            
            $costo_totale += $fornitore['costo'] * $quantita;
        }
        
        // Crea ordine
        $insert_order_query = "INSERT INTO shoepal.ordine(partitaiva, idnegozio, dataconsegna) 
                              VALUES ($1, $2, CURRENT_DATE) RETURNING idordine";
        $order_result = pg_query_params($connection, $insert_order_query, [$fornitore_principale, $idNegozio]);
        
        if (!$order_result) {
            throw new Exception("Errore nella creazione dell'ordine");
        }
        
        $order_row = pg_fetch_assoc($order_result);
        $idOrdine = $order_row['idordine'];
        
        // Aggiungi dettagli e aggiorna disponibilità
        foreach ($prodotti_validati as $prod) {
            // Dettaglio ordine
            $insert_detail_query = "INSERT INTO shoepal.ordinedettagli(idordine, idprodotto, taglia, quantità, prezzo) 
                                   VALUES ($1, $2, $3, $4, $5)";
            pg_query_params($connection, $insert_detail_query, 
                           [$idOrdine, $prod['idProdotto'], $prod['taglia'], $prod['quantita'], $prod['costo']]);
            
            // Aggiorna fornitore
            $update_fornitore_query = "UPDATE shoepal.fornitura SET disponibilità = disponibilità - $1 
                                      WHERE partitaiva = $2 AND idprodotto = $3 AND taglia = $4";
            pg_query_params($connection, $update_fornitore_query, 
                           [$prod['quantita'], $prod['partitaiva'], $prod['idProdotto'], $prod['taglia']]);
            
            // Aggiorna negozio
            $prezzoVendita = $prod['costo'] * 1.5;
            $upsert_disp_query = "INSERT INTO shoepal.disponibilità (idnegozio, idprodotto, taglia, quantità, prezzo) 
                                 VALUES ($1, $2, $3, $4, $5)
                                 ON CONFLICT (idnegozio, idprodotto, taglia)
                                 DO UPDATE SET quantità = disponibilità.quantità + $4, prezzo = $5";
            pg_query_params($connection, $upsert_disp_query, 
                           [$idNegozio, $prod['idProdotto'], $prod['taglia'], $prod['quantita'], $prezzoVendita]);
        }
        
        pg_query($connection, "COMMIT");
        close_pg_connection($connection);
        
        return [
            'success' => true, 
            'message' => "Ordine creato con successo: #$idOrdine con " . count($prodotti_validati) . " prodotti",
            'ordini_creati' => [$idOrdine],
            'costo_totale' => $costo_totale,
            'num_prodotti' => count($prodotti_validati)
        ];
        
    } catch (Exception $e) {
        pg_query($connection, "ROLLBACK");
        close_pg_connection($connection);
        throw $e;
    }
}

// Ottiene tutti gli ordini con informazioni aggregate
function get_ordini_raggruppati_per_ordine($filtroFornitore = '') {
    $query = "SELECT * FROM shoepal.get_ordini_raggruppati_per_ordine($1)";
    return execute_query($query, [$filtroFornitore]);
}

// Ottiene i dettagli di un ordine specifico
function get_dettagli_ordine($idOrdine) {
    $query = "SELECT * FROM shoepal.get_dettagli_ordine($1)";
    return execute_query($query, [$idOrdine]);
}

// 6. GESTIONE CLIENTI E UTENTI

// Ottiene tutti i clienti con informazioni complete
function get_all_clienti() {
    $query = "SELECT * FROM shoepal.get_all_clienti()";
    return execute_query($query);
}

// Ottiene tutti gli utenti del sistema
function get_all_utenti() {
    $query = "SELECT * FROM shoepal.get_all_utenti()";
    return execute_query($query);
}

// Crea un nuovo cliente
function create_cliente($codiceFiscale, $nome, $email) {
    $query = "INSERT INTO shoepal.cliente (codicefiscale, nome, email) VALUES ($1, $2, $3)";
    return execute_modification_query($query, [$codiceFiscale, $nome, $email]);
}

// Crea un nuovo utente con password hashata
function create_utente($email, $password, $tipoUtente) {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $query = "INSERT INTO shoepal.utente (email, passwordhash, tipoutente) VALUES ($1, $2, $3)";
    return execute_modification_query($query, [$email, $password_hash, $tipoUtente]);
}

// Ottiene il tipo di utente per email
function get_user_type($email) {
    $result = execute_single_query("SELECT shoepal.get_user_type($1) as tipoutente", [$email]);
    return $result ? $result['tipoutente'] : null;
}

// Conta il numero di manager nel sistema (utilizzato nella funzione successiva per la sicurezza)
function count_managers() {
    $result = execute_single_query("SELECT shoepal.count_managers() as count");
    return $result ? (int)$result['count'] : 0;
}

// Elimina un utente con controlli di sicurezza (non posso eliminare l'ultimo manager o l'utente connesso)
function delete_utente($email) {
    $userType = get_user_type($email);
    
    if ($userType === 'manager') {
        $managersCount = count_managers();
        
        if ($managersCount <= 1) {
            return ['success' => false, 'error' => 'Non è possibile eliminare l\'ultimo manager del sistema'];
        }
        
        if (isset($_SESSION['user']) && $_SESSION['user'] === $email) {
            return ['success' => false, 'error' => 'Non puoi eliminare il tuo account mentre sei connesso'];
        }
    }
    
    $connection = open_pg_connection();
    
    try {
        pg_query($connection, "BEGIN");
        
        // Elimina cliente se esiste
        pg_query_params($connection, "DELETE FROM shoepal.cliente WHERE email = $1", [$email]);
        
        // Elimina utente
        $result = pg_query_params($connection, "DELETE FROM shoepal.utente WHERE email = $1", [$email]);
        
        if ($result && pg_affected_rows($result) > 0) {
            pg_query($connection, "COMMIT");
            close_pg_connection($connection);
            return ['success' => true, 'message' => 'Utente eliminato con successo'];
        } else {
            pg_query($connection, "ROLLBACK");
            close_pg_connection($connection);
            return ['success' => false, 'error' => 'Utente non trovato'];
        }
    } catch (Exception $e) {
        pg_query($connection, "ROLLBACK");
        close_pg_connection($connection);
        return ['success' => false, 'error' => 'Errore interno: ' . $e->getMessage()];
    }
}

// 7. GESTIONE TESSERE FEDELTÀ

// Ottiene tutte le tessere fedeltà attive
function get_tessere_attive() {
    $query = "SELECT * FROM shoepal.get_tessere_attive()";
    return execute_query($query);
}

// Ottiene lo storico delle tessere
function get_storico_tessere() {
    $query = "SELECT * FROM shoepal.get_storico_tessere()";
    return execute_query($query);
}

// Ripristina una tessera fedeltà
function ripristina_tessera($idTessera, $idNuovoNegozio) {
    $query = "SELECT shoepal.ripristina_tessera($1, $2) as result";
    $result = execute_single_query($query, [$idTessera, $idNuovoNegozio]);
    return $result ? $result['result'] : null;
}

// 8. STATISTICHE E VENDITE

// Ottiene statistiche vendite degli ultimi 30 giorni
function get_statistiche_vendite() {
    $query = "SELECT * FROM shoepal.get_statistiche_vendite()";
    return execute_query($query);
}

// Ottiene le tessere premium attive
function get_tessere_premium() {
    $query = "SELECT * FROM shoepal.get_tessere_premium()";
    return execute_query($query);
}

// Ottiene clienti con tessere per negozio
function get_clienti_tessere_per_negozio() {
    $query = "SELECT * FROM shoepal.get_clienti_tessere_per_negozio()";
    return execute_query($query);
}

// Aggiorna le viste materializzate delle statistiche
function refresh_materialized_views() {
    $query = "REFRESH MATERIALIZED VIEW shoepal.statistichevenditepergiorno";
    return execute_modification_query($query);
}

// Ottiene le fatture di vendita per periodo specificato
function get_fatture_vendita_per_periodo($periodo = '30 days') {
    $periodi_validi = ['1 day', '7 days', '30 days', '1 year', '10 years'];
    if (!in_array($periodo, $periodi_validi)) {
        $periodo = '30 days';
    }
    
    $query = "SELECT * FROM shoepal.get_fatture_vendita_per_periodo($1)";
    return execute_query($query, [$periodo]);
}

// Ottiene le fatture di vendita per range di date specifico
function get_fatture_vendita_per_date($data_inizio, $data_fine) {
    $query = "SELECT * FROM shoepal.get_fatture_vendita_per_date($1, $2)";
    return execute_query($query, [$data_inizio, $data_fine]);
}

// Ottiene i rifornimenti magazzino per periodo specificato
function get_rifornimenti_magazzino($periodo = '30 days') {
    $periodi_validi = ['1 day', '7 days', '30 days', '1 year', '10 years'];
    if (!in_array($periodo, $periodi_validi)) {
        $periodo = '30 days';
    }
    
    $query = "SELECT * FROM shoepal.get_rifornimenti_magazzino($1)";
    return execute_query($query, [$periodo]);
}

// Ottiene i rifornimenti magazzino per range di date specifico
function get_rifornimenti_magazzino_per_date($data_inizio, $data_fine) {
    $query = "SELECT * FROM shoepal.get_rifornimenti_magazzino_per_date($1, $2)";
    return execute_query($query, [$data_inizio, $data_fine]);
}

// Calcola il bilancio per periodo specificato
function get_bilancio_per_periodo($periodo = '30 days') {
    $fatture = get_fatture_vendita_per_periodo($periodo);
    $rifornimenti = get_rifornimenti_magazzino($periodo);
    
    // Calcola entrate sommando i totali delle fatture (evitando doppi conteggi)
    $fatture_processate = array();
    $totale_entrate = 0;
    
    foreach ($fatture as $f) {
        if (!in_array($f['idfattura'], $fatture_processate)) {
            $totale_entrate += $f['totale']; // Usa il totale pagato della fattura
            $fatture_processate[] = $f['idfattura'];
        }
    }
    
    $totale_uscite = array_sum(array_map(function($r) { 
        return $r['prezzo'] * $r['quantità']; 
    }, $rifornimenti));
    
    return [
        'entrate' => $totale_entrate,
        'uscite' => $totale_uscite,
        'bilancio' => $totale_entrate - $totale_uscite,
        'fatture' => $fatture,
        'rifornimenti' => $rifornimenti
    ];
}

// Calcola il bilancio per range di date specifico
function get_bilancio_per_date($data_inizio, $data_fine) {
    $fatture = get_fatture_vendita_per_date($data_inizio, $data_fine);
    $rifornimenti = get_rifornimenti_magazzino_per_date($data_inizio, $data_fine);
    
    // Calcola entrate sommando i totali delle fatture (evitando doppi conteggi)
    $fatture_processate = array();
    $totale_entrate = 0;
    
    foreach ($fatture as $f) {
        if (!in_array($f['idfattura'], $fatture_processate)) {
            $totale_entrate += $f['totale']; // Usa il totale pagato della fattura
            $fatture_processate[] = $f['idfattura'];
        }
    }
    
    $totale_uscite = array_sum(array_map(function($r) { 
        return $r['prezzo'] * $r['quantità']; 
    }, $rifornimenti));
    
    return [
        'entrate' => $totale_entrate,
        'uscite' => $totale_uscite,
        'bilancio' => $totale_entrate - $totale_uscite,
        'fatture' => $fatture,
        'rifornimenti' => $rifornimenti
    ];
}

// 9. FUNZIONI AJAX

// Queste funzioni sono utilizzate per gestire le richieste AJAX (Asinchronous JavaScript and XML) per ottenere informazioni senza ricaricare la pagina

// Ottiene gli orari di apertura di un negozio
function get_orari() {
    if (!isset($_SESSION['user']) || !isset($_SESSION['ruolo']) || $_SESSION['ruolo'] != 'manager') {
        http_response_code(403);
        exit();
    }

    if (!isset($_GET['idNegozio'])) {
        http_response_code(400);
        exit();
    }

    $idNegozio = $_GET['idNegozio'];
    $orari = get_orari_negozio($idNegozio);

    header('Content-Type: application/json');
    echo json_encode($orari);
    exit();
}

// Ottiene le richieste di fornitura per un fornitore specifico
function get_forniture() {
    if (!isset($_SESSION['user']) || !isset($_SESSION['ruolo']) || $_SESSION['ruolo'] != 'manager') {
        http_response_code(403);
        exit();
    }

    if (!isset($_GET['partitaIVA'])) {
        http_response_code(400);
        exit();
    }

    $partitaIVA = $_GET['partitaIVA'];
    $forniture = get_forniture_by_fornitore($partitaIVA);

    header('Content-Type: application/json');
    echo json_encode($forniture);
    exit();
}

// Ottiene le richieste per il costo minimo di un prodotto
function get_costo_prodotto() {
    if (!isset($_SESSION['user']) || !isset($_SESSION['ruolo']) || $_SESSION['ruolo'] != 'manager') {
        http_response_code(403);
        exit();
    }

    if (!isset($_GET['idProdotto'])) {
        http_response_code(400);
        exit();
    }

    $idProdotto = $_GET['idProdotto'];
    $costo = get_costo_minimo_prodotto($idProdotto);

    header('Content-Type: application/json');
    echo json_encode(['costo_minimo' => $costo]);
    exit();
}

// Ottiene la disponibilità massima di un prodotto
function get_disponibilita_massima() {
    if (!isset($_SESSION['user']) || !isset($_SESSION['ruolo']) || $_SESSION['ruolo'] != 'manager') {
        http_response_code(403);
        exit();
    }

    if (!isset($_GET['idProdotto'])) {
        http_response_code(400);
        exit();
    }

    $idProdotto = $_GET['idProdotto'];
    $disponibilita = get_disponibilita_massima_prodotto($idProdotto);

    header('Content-Type: application/json');
    echo json_encode(['disponibilita_massima' => $disponibilita]);
    exit();
}

// Inizializza la sessione e gestisce le richieste AJAX
// Solo se il file viene eseguito direttamente, non se incluso da altri script
if (basename($_SERVER['PHP_SELF']) == 'manager_components.php') {
    session_start();
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'get_orari':
                get_orari();
                break;
            case 'get_forniture':
                get_forniture();
                break;
            case 'get_costo_prodotto':
                get_costo_prodotto();
                break;
            case 'get_disponibilita_massima':
                get_disponibilita_massima();
                break;
            default:
                http_response_code(400);
                exit();
        }
    }
}
?>
