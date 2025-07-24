<?php 
	ini_set ("display_errors", "On");
	ini_set("error_reporting", E_ALL);
	include_once ('lib/functions.php'); 
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
    <h1 class="uk-article-title">Passaggio di parametri GET/POST - esempi con IMDB</h1>
    <?php include_once ('lib/navigation.php'); ?>

    <div class="uk-section uk-section-default">
    <div class="uk-container">
        <form class="uk-form-horizontal" action="<?php echo $_SERVER['PHP_SELF'];?>" method="POST">
            <legend class="uk-legend">Passaggio di parametri con il metodo POST</legend>

            <div class="uk-margin">     
                <label class="uk-form-label" for="movie-title">Titolo</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="movie-title" type="text" placeholder="inserisci il titolo" name="movie[title]">
                </div>
            </div>
            <div class="uk-margin">
                <label class="uk-form-label" for="movie-year">Anno</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="movie-year" type="text" placeholder="inserisci l'anno di produzione" name="movie[year]">
                </div>
            </div>
            <div class="uk-margin">
                <label class="uk-form-label" for="movie-length">Durata</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="movie-length" step="1" type="number" placeholder=" durata in minuti" name="movie[length]">
                </div>
            </div>
            <div class="uk-margin">
                <label class="uk-form-label" for="movie-release">Data di rilascio in Italia</label>
                <div class="uk-form-controls">
                    <input class="uk-input" id="movie-release" type="date" placeholder="inserisci la data" name="movie[release]">
                </div>
            </div>
            <div class="uk-margin">
                <label class="uk-form-label" for="movie-genre">Genere</label>
                <div class="uk-form-controls">
                    <select class="uk-select" id="movie-genre" name="movie[genre]">
                    <option value="" selected="selected">Seleziona una voce</option>
                    <?php 
                    $genres = get_movie_genres();
                    foreach ($genres as $code => $value) {
                    ?>
                        <option value="<?php echo $code; ?>"><?php echo $value; ?></option>
                    <?php
                  	}		
                    ?>
                    </select>
                </div>
            </div>
            <button class="uk-button uk-button-default">Invia</button>
        </form>
    </div>
    </div>

    <?php
    if(isset($_POST['movie'])) {
        $movie = $_POST['movie'];
        // print_r ($movie);
        $title = 'ND';
        if(!empty($movie['title']))
        	$title = $movie['title'];
        $year = 'ND';
        if(!empty($movie['year']))
        	$year = $movie['year'];
        $length = 'ND';
        if(!empty($movie['length']))
        	$length = $movie['length'] . ' minuti';
        $release_date = 'ND';
        if(!empty($movie['release']))
        	$release_date = date('d/m/Y',strtotime($movie['release']));
    ?>
    <hr>
    <div class="uk-card uk-card-primary uk-card-body uk-margin-bottom">
        <h3 class="uk-card-title">Sto per inserire i seguenti dati:</h3>
        <div uk-grid>
            <div class="uk-width-1-3">
                <span class="uk-text-bold">Titolo (anno): </span> 
                <?php echo $title . " (" . $year . ")"; ?>
            </div>
            
            <div class="uk-width-1-3">
                <span class="uk-text-bold">Durata: </span>
                <?php echo $length; ?><br>
                
                <?php 
                $genre = get_genre_name($movie['genre']);
                if (!is_null($genre)) {
                ?>
                <span class="uk-text-bold">Genere: </span>
                <?php echo $genre; ?>
                <?php
                }
                ?>
            </div>  

            <div class="uk-width-1-3">
                <span class="uk-text-bold">Data di rilascio in Italia: </span>
                <?php echo $release_date; ?>
            </div>       
        </div>
    </div>
    <?php
    }
    ?>

    <div class="uk-card uk-card-secondary uk-card-body">
    <?php
    if (isset($_GET['country'])) {
    	$country = $_GET['country'];
    	
    ?>
    	<h3 class="uk-card-title">Film prodotti in <?php echo $country ?></h3>
		<table class="uk-table uk-table-divider">
		<thead>
			<tr>
				<th>Titolo del film</th>
				<th>Anno di produzione</th>
			</tr>
		</thead>
		<tbody>
    
    
    <?php
    	$movies = get_movie_country($country);
    	foreach ($movies as $year => $title) {
    ?>
    	<tr>
            <td><?php echo $title; ?></td>
            <td><?php echo $year; ?></td>
        </tr>
    <?php
    	}
    ?>
        </tbody>
		</table>
    <?php
    } 
    ?>
        <h3 class="uk-card-title">Seleziona il paese di interesse (passaggio di parametri con il metodo GET)</h3>
        Vedi i film prodotti in <a href="<?php echo $_SERVER['PHP_SELF']; ?>?country=ITA">Italia</a>.<br>
        Vedi i film prodotti in <a href="<?php echo $_SERVER['PHP_SELF']; ?>?country=FRA">Francia</a>.<br> 
        Vedi i film prodotti in <a href="<?php echo $_SERVER['PHP_SELF']; ?>?country=USA">Stati Uniti</a>.<br>
    </div>
	</div>
    </body>
</html>