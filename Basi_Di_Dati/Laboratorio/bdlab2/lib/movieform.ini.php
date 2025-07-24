<?php
	$error_msg = '';
	$success_msg = '';

    if(isset($_POST) && isset($_POST['movie'])) {
        $movie = $_POST['movie'];
        // print_r ($movie);
        
        $id = null;
        if(!empty($movie['id']))
        	$id = $movie['id'];
        else
        	$error_msg = "Errore. E' necessario inserire il codice del film";
        	
        $title = null;
        if(!empty($movie['title']))
        	$title = $movie['title'];
        else
        	$error_msg = "Errore. E' necessario inserire il titolo del film";;
        	
        $budget = null;
        if(!empty($movie['budget']))
        	if (is_numeric($movie['budget']))
        		$budget = $movie['budget'];
        		
        $year = null;
        if(!empty($movie['year']))
        	if (is_numeric($movie['year']))
        		$year = $movie['year'];
        		
        $length = null;
        if(!empty($movie['length']))
        	if (is_numeric($movie['length']))
        		$length = $movie['length'];
        		
        $plot = null;
        if(!empty($movie['plot']))
        	$plot = $movie['plot'];
        	
        if (empty($error_msg)) {
        	$db = open_pg_connection();
        	
        	$result = pg_query($db, 'SET SEARCH_PATH TO imdb');
	
			$sql = "INSERT INTO movie(id, official_title, budget, year, length, plot) VALUES ($1, $2, $3, $4, $5, $6)";
	
            /*
             * definire le istruzioni per l'inserimento della pellicola nel db
             */
        }
    }

    // link da usare nella clausola action del form di inserimento
    $pagelink = $_SERVER['PHP_SELF'] . '?mod=insert';
        	
?>
<?php
if (!empty($success_msg)) {
?>
<div class="uk-alert-success" uk-alert>
    <a class="uk-alert-close" uk-close></a>
    <p><?php echo $success_msg; ?></p>
</div>
<?php
}
?>
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
<form class="uk-form-horizontal" action="<?php echo $pagelink; ?>" method="POST">
	<legend class="uk-legend">Inserisci un nuovo film</legend>

	<div class="uk-margin">     
		<label class="uk-form-label" for="movie-id">Codice</label>
		<div class="uk-form-controls">
			<input class="uk-input" id="movie-id" type="text" placeholder="inserisci il codice" name="movie[id]">
		</div>
	</div>
	<div class="uk-margin">     
		<label class="uk-form-label" for="movie-title">Titolo</label>
		<div class="uk-form-controls">
			<input class="uk-input" id="movie-title" type="text" placeholder="inserisci il titolo" name="movie[title]">
		</div>
	</div>
	<div class="uk-margin">
		<label class="uk-form-label" for="movie-budget">Budget</label>
		<div class="uk-form-controls">
			<input class="uk-input" id="movie-budget" type="number" min="0" value="0" step=".01" placeholder="inserisci il budget" name="movie[budget]">
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
		<label class="uk-form-label" for="movie-plot">Trama</label>
		<div class="uk-form-controls">
			<textarea class="uk-textarea" rows="5" placeholder="Inserisci la trama" name="movie[plot]"></textarea>
		</div>
	</div>
	
	<button class="uk-button uk-button-default">Inserisci</button>
</form>