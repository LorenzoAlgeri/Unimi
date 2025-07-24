<?php
    $db = open_pg_connection();

    /*
     * si reperisca dalla base di dati titolo, anno di produzione e paese di produzione delle pellicole disponibili
     */
    
    // si usi pg_query per impostare la variabile search_path
    // in alternativa, si prefigga il nome dello schema in ogni riferimento a ogni tabella del db che si intende utilizzare
    $result = pg_query($db, 'SET SEARCH_PATH TO imdb');

    /**
     * ESEMPIO DI INJECTION
     * 2010'; DELETE FROM movie WHERE official_title = 'Hereafter
     */

    $where = "";
    if(isset($_POST) && !empty($_POST['year'])){
        $where = " WHERE year = '". $_POST['year'] ."'";
    }
    
    $sql = "SELECT movie.id, official_title, year, country FROM imdb.movie LEFT JOIN imdb.produced ON movie.id=produced.movie";
    $sql .= $where;

    // print($sql)

    /*
     * si esegua la query
     */
    $result = pg_query($db, $sql);
    
    /* 
     * si visualizzi il risultato
     */

    $movies = array();
    // si vedano le differenze fra pg_fetch_assoc e pg_fetch_num
    /* ESERCIZIO
     * si faccia in modo che ogni film abbia un unico record nell'array $movies. HINT: concatenare i country in un'unica stringa
     */
    while($row = pg_fetch_assoc($result)){
        // print_r($row)

        $id = $row['id'];
        $title = $row['official_title'];
        $year = $row['year'];
        $country = $row['country'];

        // RISOLUZIONE ESERCIZIO
        // raggruppo le righe che si riferiscono allo stesso movie
        if(in_array($id, array_keys($movies))){
            $country = $movies[$id][2] . ', ' . $country;
        }
        // FINE RISOLUZIONE ESERCIZIO

        $movies[$id] = array($title, $year, $country);
    }

    // link da usare nella clausola action del form di ricerca
    $pagelink = $_SERVER['PHP_SELF'] . '?mod=err';

?>
<form class="uk-form-horizontal" action="<?php echo $pagelink; ?>" method="POST">
    <legend class="uk-legend">Filtra le pellicole per anno di produzione</legend>

    <div class="uk-margin">     
        <div class="uk-form-controls">
            <input class="uk-input" type="text" placeholder="inserisci l'anno" name="year">
        </div>
    </div>
    
    <button class="uk-button uk-button-default">Cerca</button>
</form>
<?php
$year_value = "";
if(isset($_POST) && !empty($_POST['year'])){
        $year_value = " per l'anno ". $_POST['year'];
    }
?>
<h3 class="uk-card-title">Film disponibili in archivio <?php echo $year_value; ?></h3>
<table class="uk-table uk-table-divider">
<thead>
	<tr>
		<th>Titolo del film</th>
		<th>Anno di produzione</th>
		<th>Paese di produzione</th>
	</tr>
</thead>
<tbody>
<?php
/*
 * Visualizzare i record ricevuti dalla base di dati
 */
foreach($movies as $id=>$values){
    /* ESERCIZIO
     * si faccia in modo che il titolo sia un link che punta alla pagina moviedetail.php passando come parametro get l'identificativo $id di ciascun film
     */
    $link = 'moviedetail.php?id=' . $id;
?>
    <tr>
        <td><a href="<?php echo $link; ?>"><?php echo $values[0]; ?></a></td>
        <td><?php echo $values[1]; ?></td>
        <td><?php echo $values[2]; ?></td>
    </tr>
<?php
}
?>
</tbody>
</table>
<?php
    close_pg_connection($db);
?>	