<?php
// la pagina visualizza alcune statistiche a fronte di varie tipologie di contenuti selezionate
if ((isset($_POST)) && (!empty($_POST['stat_choice']))) {
	$stat_choice = $_POST['stat_choice'];
} else {
	$stat_choice = null;
}
?>
<form action="<?php print($_SERVER['PHP_SELF']); ?>?mod=stats" method="POST">
<fieldset class="uk-fieldset">

    <legend class="uk-legend">Seleziona la statistica di interesse</legend>

	<div class="uk-margin">
        <div class="uk-form-controls">
            <select class="uk-select" name="stat_choice">
            	<?php
            	// definire l'opzione attualmente selezionata tramite l'attributo selected
            	$stats_keys = get_stats_entries();
            	foreach ($stats_keys as $k => $v) {
            		$selected = '';
            		if ((!is_null($stat_choice)) && ($stat_choice == $k)) {
            			$selected = 'selected="selected"';
            		}

            	?>
                <option value="<?php print($k); ?>" <?php print($selected); ?>><?php print($v); ?></option>
        		<?php
        		}
        		?>
            </select>
        </div>
    </div>
  
  	<button type="submit" class="uk-button uk-button-primary">Mostra statistiche</button>
</form>

<hr>

<?php
// mostra i risultati della selezione utente
if (!is_null($stat_choice)) {
	$stats = get_stats($stat_choice);
	if (!is_null($stats)) {

?>
<div class="uk-card uk-card-secondary uk-card-body">
    <h3 class="uk-card-title">Statistiche disponibili</h3>
    <p>
    <?php
    	$stats_elements = array();
    	foreach ($stats as $key => $value) {
    		$stats_elements[] = $key . ': ' . $value;

    	}

    	print (implode('<br>', $stats_elements));
    ?>
    </p>
</div>
<?php
	}
}
?>
