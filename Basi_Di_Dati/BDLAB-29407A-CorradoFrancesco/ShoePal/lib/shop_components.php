<?php
/**
 * Componenti Shop ShoePal
 * 
 * Gestione funzioni di utilità per lo shop
 * 
 * SEZIONI CONTENUTE:
 * 1. Controlli di autenticazione
 * 2. Ricerca pagina shop
 * 3. Visualizza Negozi e Ricerca
 * 4. Prodotti: filtri, griglia e ricerca
 * 5. Carrello
 */

// 1. CONTROLLI DI AUTENTICAZIONE

function shop_check_authentication() {
  // Verifica se l'utente è loggato correttamente o rimanda a login
    if (!isset($_SESSION['user']) || !isset($_SESSION['ruolo']) || $_SESSION['ruolo'] != 'cliente') {
        header("Location: login.php");
        exit();
    }

    // Verifica completamento profilo cliente
    if (!check_cliente_profile($_SESSION['user'])) {
        $_SESSION['register_profile_email'] = $_SESSION['user'];
        $_SESSION['info_msg'] = 'Devi completare la registrazione del profilo prima di accedere.';
        header("Location: login.php");
        exit();
    }
}

// 2. GET PARAMETRI PER PAGINA SHOP

function shop_process_parameters() {
    // Variabili ottenute in GET per gestire filtri ricerca ecc.
    $result = [
        'negozio_selezionato' => null,
        'prodotti' => [],
        'prodotti_per_negozio' => [],
        'filtri_genere' => [],
        'filtri_tipologia' => [],
        'filtri_marche' => [],
        'filtri_taglie' => [],
        'fascia_prezzo' => [],
        'search_term' => '',
        'genere_filtro' => '',
        'tipologia_filtro' => '',
        'marca_filtro' => '',
        'taglia_filtro' => '',
        'prezzo_min_filtro' => null,
        'prezzo_max_filtro' => null,
        'ricerca_globale' => false,
        'negozi' => []
    ];

    // Ricerca di un prodotto nella barra globale (senza quindi aver selezionato un negozio specifico)
    if (isset($_GET['search']) && !empty(trim($_GET['search'])) && (!isset($_GET['negozio_id']) || empty($_GET['negozio_id']))) {
        $result['search_term'] = trim($_GET['search']); //trim per rimuovere spazi iniziali e finali
        $result['ricerca_globale'] = true;
        
        // Ottieni prodotti da tutti i negozi per il termine di ricerca
        $result['prodotti_per_negozio'] = get_prodotti_ricerca_globale($result['search_term']);
        
        // Ottieni tutti i negozi per il layout
        $result['negozi'] = get_all_negozi();
    }

    // Ricerca di un prodotto nel negozio specifico
    elseif (isset($_GET['negozio_id']) && !empty($_GET['negozio_id'])) {
        $negozio_id = (int)$_GET['negozio_id'];
        
        // Ottieni dettagli del negozio selezionato
        $result['negozio_selezionato'] = get_negozio_details($negozio_id);
        
        if ($result['negozio_selezionato']) {
            // Gestione filtri di ricerca
            if (isset($_GET['search'])) {
                $result['search_term'] = trim($_GET['search']);
            }
            if (isset($_GET['genere'])) {
                $result['genere_filtro'] = $_GET['genere'];
            }
            if (isset($_GET['tipologia'])) {
                $result['tipologia_filtro'] = $_GET['tipologia'];
            }
            if (isset($_GET['marca'])) {
                $result['marca_filtro'] = $_GET['marca'];
            }
            if (isset($_GET['taglia'])) {
                $result['taglia_filtro'] = $_GET['taglia'];
            }
            if (isset($_GET['prezzo_min']) && is_numeric($_GET['prezzo_min'])) {
                $result['prezzo_min_filtro'] = (float)$_GET['prezzo_min'];
            }
            if (isset($_GET['prezzo_max']) && is_numeric($_GET['prezzo_max'])) {
                $result['prezzo_max_filtro'] = (float)$_GET['prezzo_max'];
            }
            
            // Ottieni i vari filtri
            $result['filtri_genere'] = get_filtri_genere($negozio_id, $result['search_term'], $result['tipologia_filtro']);
            $result['filtri_tipologia'] = get_filtri_tipologia($negozio_id, $result['search_term'], $result['genere_filtro']);
            $result['filtri_marche'] = get_marche_negozio($negozio_id, $result['genere_filtro']);
            $result['filtri_taglie'] = get_filtri_taglie($negozio_id, $result['search_term'], $result['genere_filtro'], $result['tipologia_filtro'], $result['marca_filtro']);
            $result['fascia_prezzo'] = get_fascia_prezzo_negozio($negozio_id);
            
            // Ottieni prodotti con tutti i filtri applicati
            $result['prodotti'] = get_prodotti_negozio($negozio_id, $result['search_term'], $result['genere_filtro'], $result['tipologia_filtro'], $result['marca_filtro'], $result['prezzo_min_filtro'], $result['prezzo_max_filtro'], $result['taglia_filtro']);
        }
    } else {
        // Ottieni tutti i negozi dal database
        $result['negozi'] = get_all_negozi();
    }
    return $result;
}

// Mostra la sezione di ricerca iniziale (se nessun negozio è selezionato)
function shop_render_search_section() {
    ?>
    <!-- Sezione ricerca -->
    <div class="bg-primary bg-gradient text-white py-5 mb-5">
      <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Shop ShoePal</h1>
        <p class="lead mb-4">Trova le scarpe perfette per te in tutti i nostri negozi</p>
        
        <!-- Barra di ricerca -->
        <div class="mx-auto" style="max-width: 600px;">
          <form class="d-flex" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET">
            <input class="form-control form-control-lg me-2" type="search" 
                   placeholder="Cerca scarpe, marca, modello in tutti i negozi..." 
                   aria-label="Cerca prodotti" name="search"
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button class="btn btn-light btn-lg" type="submit">
              <i class="fas fa-search"></i> Cerca
            </button>
          </form>
          <small class="text-light mt-2 d-block">
            <i class="fas fa-info-circle me-1"></i>
            La ricerca mostrerà prodotti da tutti i negozi ShoePal
          </small>
        </div>
      </div>
    </div>
    <?php
}

// 3. VISUALIZZA NEGOZI E RICERCA

// Visualizza la griglia di tutti i negozi attualmente attivi
function shop_render_negozi_grid($negozi) {
    if (!isset($negozi) || empty($negozi)) {
        return;
    }
    ?>
    <div class="container mb-5">
      <div class="text-center mb-4">
        <h2 class="mb-3">Seleziona uno dei nostri negozi</h2>
        <p class="text-muted">Scegli il negozio più vicino a te per vedere i prodotti disponibili</p>
      </div>

      <div class="row g-4">
        <?php foreach ($negozi as $negozio): ?>
          <?php
            // Ottieni immagine e nome per ogni negozio
            $immagine = get_negozio_image_path($negozio['citta']);
            $nome_negozio = get_negozio_display_name($negozio['citta']);
          ?>
          <div class="col-lg-4 col-md-6">
            <div class="card text-center h-100 shadow border-0 hover-shadow" 
                 style="min-height: 200px; cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease;"
                 onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)';"
                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';"
                 onclick="window.location.href='shop.php?negozio_id=<?php echo $negozio['id']; ?>'">
                <!-- Javascript inline utilizzato per l'effetto hover -->
              <div class="card-body">
                <!-- Immagine del negozio, chiamata per semplicità con il nome del neggozio -->
                <img src="<?php echo $immagine; ?>" 
                     alt="<?php echo htmlspecialchars($nome_negozio); ?>" 
                     class="img-fluid rounded mb-3" 
                     style="width: 100%; height: 150px; object-fit: cover;">
                <!-- Nome del negozio -->
                <h4 class="card-title text-primary">
                  <?php echo htmlspecialchars($nome_negozio); ?>
                </h4>
                <!-- Indirizzo -->
                <p class="card-text text-muted">
                  <?php echo htmlspecialchars($negozio['indirizzo']); ?>
                </p>
                <!-- Badge -->
                <span class="badge bg-primary">
                  <i class="fas fa-store me-1"></i>Visualizza prodotti
                </span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      
      <!-- Messaggio se non ci sono negozi -->
      <?php if (empty($negozi)): ?>
        <div class="text-center py-5">
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Al momento non ci sono negozi disponibili.
          </div>
        </div>
      <?php endif; ?>
    </div>
    <?php
}

// Visualizza l'header del negozio selezionato
function shop_render_negozio_header($negozio_selezionato) {
    if (!isset($negozio_selezionato) || empty($negozio_selezionato)) {
        return;
    }
    ?>
    <!-- Pulsante torna indietro -->
    <div class="sticky-top mb-4" style="top: 20px; z-index: 100;">
      <a href="shop.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left me-2"></i>Torna ai negozi
      </a>
    </div>
    <!-- Header negozio -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <h2 class="card-title mb-1">
              <i class="fas fa-store me-2"></i>
              <?php echo get_negozio_display_name($negozio_selezionato['citta']); ?>
            </h2>
            <p class="card-text mb-0">
              <i class="fas fa-map-marker-alt me-1"></i>
              <?php echo htmlspecialchars($negozio_selezionato['indirizzo']); ?>
            </p>
          </div>
        </div>
      </div>
    </div>
    <?php
}

// Visualizza i risultati della ricerca globale su tutti i negozi
function shop_render_global_search_results($prodotti_per_negozio, $search_term) {
    // Conta il totale dei prodotti
    $totale_prodotti = 0;
    foreach ($prodotti_per_negozio as $negozio_data) {
        $totale_prodotti += count($negozio_data['prodotti']);
    }
    ?>
    <div class="container my-5">
        <?php if (!empty($prodotti_per_negozio)): ?>
            <!-- Header risultati ricerca -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h4 class="alert-heading">
                            <i class="fas fa-search me-2"></i>Risultati ricerca globale
                        </h4>
                        <p class="mb-2">
                            Trovati <strong><?php echo $totale_prodotti; ?> prodotti</strong> 
                            per "<strong><?php echo htmlspecialchars($search_term); ?></strong>" 
                            in <strong><?php echo count($prodotti_per_negozio); ?> negozi</strong>
                        </p>
                        <hr>
                        <p class="mb-0">
                            <small><i class="fas fa-info-circle me-1"></i>
                            I prodotti sono raggruppati per negozio. Clicca su un prodotto per visualizzare tutte le taglie e i prezzi disponibili.</small>
                        </p>
                    </div>
                </div>
            </div>

            <?php foreach ($prodotti_per_negozio as $negozio_data): ?>
            <div class="mb-5">
                <!-- Header negozio -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between border-bottom pb-2">
                            <div>
                                <h3 class="mb-1">
                                    <i class="fas fa-store me-2 text-primary"></i>
                                    <?php echo htmlspecialchars($negozio_data['negozio_nome']); ?>
                                </h3>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($negozio_data['negozio_indirizzo']); ?>
                                </p>
                            </div>
                            <div>
                                <a href="?negozio_id=<?php echo $negozio_data['negozio_id']; ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-right me-1"></i>
                                    Vai al negozio
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Griglia prodotti per questo negozio -->
                <div class="row">
                    <?php foreach ($negozio_data['prodotti'] as $prodotto): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card h-100 shadow-sm">
                                <!-- Immagine prodotto -->
                                <div class="position-relative">
                                    <img src="assets/prodotti/<?php echo $prodotto['idprodotto']; ?>.webp" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($prodotto['nome']); ?>"
                                         style="height: 250px; object-fit: cover;">
                                    
                                    <!-- Badge genere -->
                                    <span class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-<?php echo $prodotto['sesso'] == 'M' ? 'primary' : ($prodotto['sesso'] == 'F' ? 'danger' : 'success'); ?>">
                                            <?php echo $prodotto['sesso'] == 'M' ? 'Uomo' : ($prodotto['sesso'] == 'F' ? 'Donna' : 'Unisex'); ?>
                                        </span>
                                    </span>
                                </div>

                                <div class="card-body d-flex flex-column">
                                    <!-- Nome e tipologia -->
                                    <h5 class="card-title"><?php echo htmlspecialchars($prodotto['nome']); ?></h5>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars(ucfirst($prodotto['tipologia'])); ?></p>
                                    
                                    <!-- Descrizione -->
                                    <p class="card-text flex-grow-1">
                                        <?php 
                                        $descrizione = htmlspecialchars($prodotto['descrizione']);
                                        echo strlen($descrizione) > 80 ? substr($descrizione, 0, 77) . '...' : $descrizione; 
                                        ?>
                                    </p>

                                    <!-- Prezzo -->
                                    <div class="mb-3">
                                        <?php if ($prodotto['prezzo_min'] == $prodotto['prezzo_max']): ?>
                                            <h4 class="text-primary mb-0">€<?php echo number_format($prodotto['prezzo_min'], 2); ?></h4>
                                        <?php else: ?>
                                            <h4 class="text-primary mb-0">
                                                €<?php echo number_format($prodotto['prezzo_min'], 2); ?> - 
                                                €<?php echo number_format($prodotto['prezzo_max'], 2); ?>
                                            </h4>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Taglie disponibili -->
                                    <div class="mb-3">
                                        <small class="text-muted">Taglie disponibili:</small>
                                        <div class="mt-1">
                                            <?php 
                                            $taglie_mostrate = array_slice($prodotto['taglie_disponibili'], 0, 5);
                                            foreach ($taglie_mostrate as $taglia): 
                                            ?>
                                                <span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars($taglia); ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($prodotto['taglie_disponibili']) > 5): ?>
                                                <span class="badge bg-secondary">+<?php echo count($prodotto['taglie_disponibili']) - 5; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Bottone per vedere dettagli -->
                                    <a href="?negozio_id=<?php echo $negozio_data['negozio_id']; ?>&search=<?php echo urlencode($search_term); ?>#prodotto_<?php echo $prodotto['idprodotto']; ?>" 
                                       class="btn btn-primary mt-auto">
                                        <i class="fas fa-eye me-1"></i>
                                        Vedi dettagli
                                    </a>
                                    <!-- Rimanda alla pagina del prodotto con i dettagli -->
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

        <!-- Se la ricerca non produce risultati -->
        <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        <h4><i class="fas fa-search me-2"></i>Nessun risultato trovato</h4>
                        <p class="mb-3">Non sono stati trovati prodotti che corrispondono alla ricerca "<strong><?php echo htmlspecialchars($search_term); ?></strong>"</p>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Torna alla homepage
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// 4. PRODOTTI: FILTRI, GRIGLIA E RICERCA

// Form di ricerca prodotti con i vari filtri selezionabili
function shop_render_filtri_form($data) {
    extract($data);
    
    if (!isset($negozio_selezionato) || !isset($filtri_genere) || !isset($filtri_tipologia) || !isset($filtri_marche) || !isset($filtri_taglie) || !isset($fascia_prezzo)) {
        return;
    }
    ?>
    <!-- Filtri e ricerca prodotti -->
    <div class="bg-light rounded p-4 mb-4">
      <form method="GET" action="shop.php">
        <input type="hidden" name="negozio_id" value="<?php echo $negozio_selezionato['id']; ?>">
        
        <div class="row align-items-end">
          <!-- Barra di ricerca -->
          <div class="col-lg-4 col-md-6 mb-3">
            <label for="search" class="form-label">
              <i class="fas fa-search me-1"></i>Cerca prodotti
            </label>
            <input type="text" class="form-control" id="search" name="search" 
                   placeholder="Cerca un prodotto" 
                   value="<?php echo htmlspecialchars($search_term); ?>">
          </div>
          
          <!-- Filtro genere -->
          <div class="col-lg-2 col-md-6 mb-3">
            <label for="genere" class="form-label">
              <i class="fas fa-venus-mars me-1"></i>Genere
            </label>
            <select class="form-select" id="genere" name="genere">
              <option value="">Tutti</option>
              <?php foreach ($filtri_genere as $filtro): ?>
                <option value="<?php echo $filtro['sesso']; ?>" 
                        <?php echo ($genere_filtro === $filtro['sesso']) ? 'selected' : ''; ?>>
                  <?php echo ucfirst($filtro['sesso']); ?> (<?php echo $filtro['count']; ?>) <!-- ucfirst per rendere la prima lettera maiuscola -->
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <!-- Filtro tipologia -->
          <div class="col-lg-2 col-md-6 mb-3">
            <label for="tipologia" class="form-label">
              <i class="fas fa-shoe-prints me-1"></i>Tipologia
            </label>
            <select class="form-select" id="tipologia" name="tipologia">
              <option value="">Tutte</option>
              <?php foreach ($filtri_tipologia as $filtro): ?>
                <option value="<?php echo $filtro['tipologia']; ?>" 
                        <?php echo ($tipologia_filtro === $filtro['tipologia']) ? 'selected' : ''; ?>>
                  <?php echo ucfirst($filtro['tipologia']); ?> (<?php echo $filtro['count']; ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <!-- Filtro marca -->
          <div class="col-lg-2 col-md-6 mb-3">
            <label for="marca" class="form-label">
              <i class="fas fa-tag me-1"></i>Marca
            </label>
            <select class="form-select" id="marca" name="marca">
              <option value="">Tutte</option>
              <?php foreach ($filtri_marche as $filtro): ?>
                <option value="<?php echo $filtro['marca']; ?>" 
                        <?php echo ($marca_filtro === $filtro['marca']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($filtro['marca']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <!-- Pulsanti -->
          <div class="col-lg-2 col-md-6 mb-3">
            <button type="submit" class="btn btn-primary w-100 mb-2">
              <i class="fas fa-filter me-1"></i>Filtra
            </button>
            <a href="shop.php?negozio_id=<?php echo $negozio_selezionato['id']; ?>" 
               class="btn btn-outline-secondary w-100">
              <i class="fas fa-times me-1"></i>Reset
            </a>
          </div>
        </div>
        
        <div class="row align-items-end">
          <!-- Filtro taglia -->
          <div class="col-lg-3 col-md-6 mb-3">
            <label for="taglia" class="form-label">
              <i class="fas fa-ruler me-1"></i>Taglia
            </label>
            <select class="form-select" id="taglia" name="taglia">
              <option value="">Tutte</option>
              <?php foreach ($filtri_taglie as $filtro): ?>
                <option value="<?php echo $filtro['taglia']; ?>" 
                        <?php echo ($taglia_filtro === $filtro['taglia']) ? 'selected' : ''; ?>>
                  <?php echo $filtro['taglia']; ?> (<?php echo $filtro['count']; ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <!-- Filtro fascia prezzo -->
          <div class="col-lg-3 col-md-6 mb-3">
            <label class="form-label">
              <i class="fas fa-euro-sign me-1"></i>Prezzo
            </label>
            <div class="input-group input-group-sm">
              <input type="number" class="form-control" name="prezzo_min" 
                     placeholder="Min" step="0.01" min="0"
                     value="<?php echo $prezzo_min_filtro ? number_format($prezzo_min_filtro, 2, '.', '') : ''; ?>">
              <!-- Step 0.01 serve per poter selezionare i centesimi, min 0 impedisce valori negativi -->
              <span class="input-group-text">-</span>
              <input type="number" class="form-control" name="prezzo_max" 
                     placeholder="Max" step="0.01" min="0" 
                     value="<?php echo $prezzo_max_filtro ? number_format($prezzo_max_filtro, 2, '.', '') : ''; ?>">
            </div>
            <small class="text-muted">€<?php echo number_format($fascia_prezzo['prezzo_min'], 0); ?> - €<?php echo number_format($fascia_prezzo['prezzo_max'], 0); ?></small>
            <!-- Stampa in piccolo i valori minimi e massimi della fascia di prezzo -->
          </div>
          <!-- Spazio vuoto per allineare a sinistra -->
          <div class="col-lg-6 col-md-12 mb-3">
          </div>
        </div>
      </form>
    </div>
    <?php
}

// Visualizza i prodotti di un negozio in una griglia
function shop_render_prodotti_grid($prodotti, $negozio_selezionato) {
    if (!isset($prodotti) || !isset($negozio_selezionato)) {
        return;
    }
    ?>
    <!-- Griglia prodotti -->
    <?php if (!empty($prodotti)): ?>
    <div class="row g-4">
      <?php foreach ($prodotti as $prodotto): ?>
        <?php
          // Ottieni immagine e prezzo formattato
          $immagine_prodotto = get_product_image_path($prodotto['idprodotto']);
          $prezzo_display = format_price_range($prodotto['prezzo_min'], $prodotto['prezzo_max']);
        ?>
        <div class="col-lg-4 col-md-6">
          <div class="card shadow border-0 h-100">
            <!-- Immagine prodotto - clickable per aprire il modal del prodotto-->
            <!-- Trasforma il cursore in un puntatore -->
            <div style="cursor: pointer;" 
                 data-bs-toggle="modal" 
                 data-bs-target="#productModal<?php echo $prodotto['idprodotto']; ?>">
              <img src="<?php echo $immagine_prodotto; ?>" 
                   alt="<?php echo htmlspecialchars($prodotto['nome']); ?>" 
                   class="img-fluid" 
                   style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px 8px 0 0;">
            </div>
            
            <div class="card-body">
              <!-- Nome prodotto - clickable per aprire il modal del prodotto-->
              <div style="cursor: pointer;" 
                   data-bs-toggle="modal" 
                   data-bs-target="#productModal<?php echo $prodotto['idprodotto']; ?>">
                <h5 class="card-title text-primary">
                  <?php echo htmlspecialchars($prodotto['nome']); ?>
                </h5>
                
                <!-- Tag del prodotto (taglia, marca ecc.) -->
                <div class="mb-2">
                  <?php if (!empty($prodotto['marca'])): ?>
                  <span class="badge bg-primary small me-1">
                    <?php echo htmlspecialchars($prodotto['marca']); ?>
                  </span>
                  <?php endif; ?>
                  <span class="badge bg-secondary small me-1">
                    <?php echo ucfirst($prodotto['sesso']); ?>
                  </span>
                  <span class="badge bg-info small me-1">
                    <?php echo ucfirst($prodotto['tipologia']); ?>
                  </span>
                </div>
                
                <!-- Prezzo -->
                <p class="card-text">
                  <strong class="text-success fs-5"><?php echo $prezzo_display; ?></strong>
                </p>
                
                <!-- Taglie disponibili -->
                <div class="mb-3">
                  <small class="text-muted">Taglie e disponibilità:</small>
                  <div>
                    <?php foreach ($prodotto['taglie_disponibili'] as $taglia): ?>
                      <?php 
                      $quantita = isset($prodotto['disponibilita_per_taglia'][$taglia]) ? $prodotto['disponibilita_per_taglia'][$taglia] : 0;
                      $badge_class = $quantita > 5 ? 'bg-success' : ($quantita > 2 ? 'bg-warning text-dark' : 'bg-danger');
                      ?>
                      <span class="badge <?php echo $badge_class; ?> me-1" title="<?php echo $quantita; ?> disponibili">
                        <?php echo trim($taglia); ?> (<?php echo $quantita; ?>)
                      </span>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
              
              <!-- Selettore taglia e pulsante carrello -->
              <form method="POST" action="shop.php" class="d-flex gap-2">
                <input type="hidden" name="action" value="aggiungi_carrello">
                <input type="hidden" name="idProdotto" value="<?php echo $prodotto['idprodotto']; ?>">
                <input type="hidden" name="idNegozio" value="<?php echo $negozio_selezionato['id']; ?>">
                <select class="form-select form-select-sm flex-grow-1" name="taglia" required>
                  <option value="">Taglia</option>
                  <?php foreach ($prodotto['taglie_disponibili'] as $taglia): ?>
                    <?php 
                    $quantita = isset($prodotto['disponibilita_per_taglia'][$taglia]) ? $prodotto['disponibilita_per_taglia'][$taglia] : 0;
                    $disabled = $quantita <= 0 ? 'disabled' : '';
                    ?>
                    <option value="<?php echo trim($taglia); ?>" <?php echo $disabled; ?>>
                      <?php echo trim($taglia); ?> (<?php echo $quantita; ?> disp.)
                    </option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-success btn-sm">
                  <i class="fas fa-shopping-cart me-1"></i>Carrello
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Modal per visualizzare i dettagli del prodotto se cliccato -->
        <div class="modal fade product-modal" id="productModal<?php echo $prodotto['idprodotto']; ?>" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title"><?php echo htmlspecialchars($prodotto['nome']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-6">
                    <img src="<?php echo $immagine_prodotto; ?>" 
                         alt="<?php echo htmlspecialchars($prodotto['nome']); ?>" 
                         class="img-fluid rounded">
                  </div>
                  <div class="col-md-6">
                    <h4><?php echo htmlspecialchars($prodotto['nome']); ?></h4>
                    
                    <table class="table table-striped">
                      <tr>
                        <th>Nome:</th>
                        <td><?php echo htmlspecialchars($prodotto['nome']); ?></td>
                      </tr>
                      <?php if (!empty($prodotto['marca'])): ?>
                      <tr>
                        <th>Marca:</th>
                        <td><?php echo htmlspecialchars($prodotto['marca']); ?></td>
                      </tr>
                      <?php endif; ?>
                      <tr>
                        <th>Genere:</th>
                        <td><?php echo ucfirst($prodotto['sesso']); ?></td>
                      </tr>
                      <tr>
                        <th>Tipologia:</th>
                        <td><?php echo ucfirst($prodotto['tipologia']); ?></td>
                      </tr>
                      <tr>
                        <th>Prezzo:</th>
                        <td><strong class="text-success"><?php echo $prezzo_display; ?></strong></td>
                      </tr>
                      <tr>
                        <th>Taglie e disponibilità:</th>
                        <td>
                          <?php foreach ($prodotto['taglie_disponibili'] as $taglia): ?>
                            <?php 
                            $quantita = isset($prodotto['disponibilita_per_taglia'][$taglia]) ? $prodotto['disponibilita_per_taglia'][$taglia] : 0;
                            $badge_class = $quantita > 10 ? 'bg-success' : ($quantita > 5 ? 'bg-warning text-dark' : 'bg-danger');
                            ?>
                            <span class="badge <?php echo $badge_class; ?> me-1" title="<?php echo $quantita; ?> disponibili">
                              <?php echo trim($taglia); ?> (<?php echo $quantita; ?>)
                            </span>
                          <?php endforeach; ?>
                        </td>
                      </tr>
                    </table>
                    
                    <?php if (!empty($prodotto['descrizione'])): ?>
                    <div class="mt-3">
                      <h6>Descrizione:</h6>
                      <p><?php echo htmlspecialchars($prodotto['descrizione']); ?></p>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                <form method="POST" action="shop.php" class="d-flex gap-2">
                  <input type="hidden" name="action" value="aggiungi_carrello">
                  <input type="hidden" name="idProdotto" value="<?php echo $prodotto['idprodotto']; ?>">
                  <input type="hidden" name="idNegozio" value="<?php echo $negozio_selezionato['id']; ?>">
                  <select class="form-select" name="taglia" required>
                    <option value="">Seleziona taglia</option>
                    <?php foreach ($prodotto['taglie_disponibili'] as $taglia): ?>
                      <?php 
                      $quantita = isset($prodotto['disponibilita_per_taglia'][$taglia]) ? $prodotto['disponibilita_per_taglia'][$taglia] : 0;
                      $disabled = $quantita <= 0 ? 'disabled' : '';
                      ?>
                      <option value="<?php echo trim($taglia); ?>" <?php echo $disabled; ?>>
                        <?php echo trim($taglia); ?> (<?php echo $quantita; ?> disp.)
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-success">
                    <i class="fas fa-shopping-cart me-1"></i>Aggiungi al carrello
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Messaggio se non ci sono prodotti -->
    <?php else: ?>
    <div class="text-center py-5">
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Nessun prodotto trovato con i filtri selezionati.
      </div>
    </div>
    <?php endif; ?>
    <?php
}

// Messaggio con i risultati della ricerca
function shop_render_search_results($prodotti, $search_term, $genere_filtro, $tipologia_filtro, $marca_filtro, $taglia_filtro) {
    // Verifica se ci sono filtri attivi
    $has_filters = !empty($search_term) || !empty($genere_filtro) || !empty($tipologia_filtro) || !empty($marca_filtro) || !empty($taglia_filtro);

    if (!$has_filters) {
        return;
    }
    ?>
    <!-- Risultati ricerca -->
    <div class="mb-3">
      <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Trovati <?php echo count($prodotti); ?> prodotti
        <?php if (!empty($search_term)): ?>
          per "<strong><?php echo htmlspecialchars($search_term); ?></strong>"
        <?php endif; ?>
        <?php if (!empty($genere_filtro)): ?>
          nel genere "<strong><?php echo ucfirst($genere_filtro); ?></strong>"
        <?php endif; ?>
        <?php if (!empty($tipologia_filtro)): ?>
          della tipologia "<strong><?php echo ucfirst($tipologia_filtro); ?></strong>"
        <?php endif; ?>
      </div>
    </div>
    <?php
}

// 5. CARRELLO

// Aggiunta di prodotti al carrello tramite POST
function handle_carrello_post() {
    // Inizializza carrello se non esiste
    if (!isset($_SESSION['carrello'])) {
        $_SESSION['carrello'] = array();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'aggiungi_carrello') {
        $result = aggiungi_prodotto_carrello_post();
        
        // Imposta messaggio di feedback nella sessione
        if ($result['success']) {
            $_SESSION['success_msg'] = $result['message'];
        } else {
            $_SESSION['error_msg'] = $result['message'];
        }
        
        // Redirect riportare alla stessa pagina dopo l'invio del form
        $redirect_url = $_SERVER['PHP_SELF'];
        if (!empty($_SERVER['QUERY_STRING'])) { // QUERY_STRING contiene i parametri della query passati con l'URL
            $redirect_url .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: $redirect_url");
        exit();
    }
}

// Funzione post per aggiungere un prodotto al carrello
function aggiungi_prodotto_carrello_post() {
    $response = array('success' => false, 'message' => '');
    
    $idProdotto = (int)$_POST['idProdotto'];
    $idNegozio = (int)$_POST['idNegozio'];
    $taglia = trim($_POST['taglia'] ?? '');
    $quantita = 1; // Sempre 1 per semplicità
    
    if (empty($taglia)) {
        $response['message'] = 'Seleziona una taglia';
        return $response;
    }
    
    // Verifica disponibilità nel database
    include_once("conf/conf.php");
    $connection = open_pg_connection();
    $query = "SELECT d.quantità, d.prezzo, p.nome 
              FROM shoepal.disponibilità d
              JOIN shoepal.prodotto p ON d.idprodotto = p.idprodotto
              WHERE d.idprodotto = $1 AND d.idnegozio = $2 AND d.taglia = $3";
    $result = pg_query_params($connection, $query, array($idProdotto, $idNegozio, $taglia));
    
    if (!$result || pg_num_rows($result) == 0) {
        $response['message'] = 'Prodotto non disponibile nella taglia selezionata';
        close_pg_connection($connection);
        return $response;
    }
    
    $prodotto = pg_fetch_assoc($result);
    close_pg_connection($connection);
    
    if ($prodotto['quantità'] < $quantita) {
        $response['message'] = 'Quantità non disponibile';
        return $response;
    }
    
    // Chiave univoca per il prodotto nel carrello
    $chiave = $idProdotto . '_' . $idNegozio . '_' . $taglia;
    
    // Aggiungi al carrello
    if (isset($_SESSION['carrello'][$chiave])) {
        $_SESSION['carrello'][$chiave]['quantita'] += $quantita;
        $response['message'] = 'Quantità aggiornata nel carrello';
    } else {
        $_SESSION['carrello'][$chiave] = array(
            'idProdotto' => $idProdotto,
            'idNegozio' => $idNegozio,
            'taglia' => $taglia,
            'quantita' => $quantita,
            'prezzo' => $prodotto['prezzo'],
            'nome' => $prodotto['nome']
        );
        $response['message'] = 'Prodotto aggiunto al carrello';
    }
    
    $response['success'] = true;
    return $response;
}

// Ottiene il numero di elementi nel carrello per stamparlo
function get_carrello_count() {
    return isset($_SESSION['carrello']) ? count($_SESSION['carrello']) : 0;
}
?>
