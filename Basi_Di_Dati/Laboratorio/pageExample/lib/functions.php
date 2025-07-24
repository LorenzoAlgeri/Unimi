<?php
function get_movie_genres(){
    $db = open_pg_connection();
    $sql = "SELECT DISTINCT genre FROM imdb.genre ORDER BY genre";
    $result=pg_prepare($db, "get_genres", $sql);
    $param=array();
    $result=pg_execute($db, "get_genres", $param);
    close_pg_connection($db);
    return pg_fetch_all($result, PGSQL_ASSOC);
}

function get_movie_years(){
    $db = open_pg_connection();
    $sql = "SELECT DISTINCT year FROM imdb.movie WHERE year IS NOT NULL ORDER BY year DESC";
    $result=pg_prepare($db, "get_years", $sql);
    $param=array();
    $result=pg_execute($db, "get_years", $param);
    close_pg_connection($db);
    return pg_fetch_all($result, PGSQL_NUM);
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