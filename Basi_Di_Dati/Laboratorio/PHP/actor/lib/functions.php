<?php
function get_actor_stats_by_name($name) {
    $db = open_pg_connection();
    $sql = "SELECT * FROM get_actor_stats_by_name($1)";
    pg_prepare($db, "get_actor_stats", $sql);
    $result = pg_execute($db, "get_actor_stats", array($name));
    $actors = pg_fetch_all($result, PGSQL_ASSOC);
    close_pg_connection($db);
    return $actors ?: [];
}

function open_pg_connection(){
    include_once("conf/conf.php");
    $connection="host= dbname= user= password=";

    $connection = "host=" . myHost . " dbname=" . myDb . " user=" . myUser . " password=" . myPassword;
    return pg_connect($connection);
}

function close_pg_connection($database){
    return pg_close($database);
}
?>