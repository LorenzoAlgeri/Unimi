<?php
	$db = open_pg_connection();

/*
 * si reperisca dalla base di dati titolo, anno di produzione e paese di produzione delle pellicole disponibili
 *
 * si esegua la query
 * 
 * si visualizzi il risultato
 */


?>

<h3 class="uk-card-title">Film disponibili in archivio</h3>
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
 * si vedano le differenze fra array pg_fetch_assoc e pg_fetch_num
 */
?>
</tbody>
</table>
<?php
	close_pg_connection($db);
?>
		