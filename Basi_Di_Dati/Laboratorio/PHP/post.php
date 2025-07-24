<?php 
	ini_set ("display_errors", "On");
	ini_set("error_reporting", E_ALL);
	//include_once ('lib/functions.php'); 
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <title>Passaggio parametri</title>
  </head>
  <body>
    <h1>POST test</h1>
<div class="container">
<form class="row g-3" action="resultpost.php" method="POST">
  <div class="col-md-6">
    <label for="nome" class="form-label">Nome</label>
    <input type="text" class="form-control" id="nome" name="nome">
  </div>
  <div class="col-6">
    <label for="cognome" class="form-label">Cognome</label>
    <input type="text" class="form-control" id="cognome" name="cognome">
  </div>
  <div class="col-md-6">
    <label for="eta" class="form-label">Età</label>
    <input type="number" class="form-control" id="eta" name="eta">
  </div>
  <div class="col-12">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="gridCheck">
      <label class="form-check-label" for="gridCheck">
        Check me out
      </label>
    </div>
  </div>
  <div class="col-12">
    <button type="submit" class="btn btn-primary">Sign in</button>
  </div>
</form>
</div>
	    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>

  </body>
</html>