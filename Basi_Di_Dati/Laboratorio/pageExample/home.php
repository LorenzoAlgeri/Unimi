<?php 
	ini_set ("display_errors", "On");
	ini_set("error_reporting", E_ALL);
	include_once ('lib/functions.php'); 
?>

<!doctype html>
<html lang="en">
  <head>
      <?php include_once ('lib/header.php'); ?>
  </head>
  <body>
    <?php include_once ('lib/navigation.php'); ?>
    <h1 style="text-align:center">Imdb Movies</h1>
    <h3 style="text-align:center">Search for a movie</h3>
    <hr>
    <div class="container">
      <form class="row g-3" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        <div class="col-md-6">
            <input type="text" class="form-control" id="title" name="movie[title]" placeholder="Title">
        </div>
        <div class="col-6">
            <select class="form-select" id="movie-year" name="movie[year]">
              <option value="" selected="selected">Year</option>
                <?php
                  $years = get_movie_years();
                  foreach ($years as $code => $value) {
                ?>
                  <option value="<?php echo $value[0]; ?>"> <?php echo $value[0]; ?> </option>
                <?php
                  }
                ?>
          </select>
        </div>
        <div class="col-md-6">
            <label for="length" class="form-label">Length</label>
            <input type="number" class="form-control" id="length" name="movie[length]">
        </div>
        <div class="col-md-6">
            <label for="release" class="form-label">Release Date (Italy)</label>
            <input type="date" class="form-control" id="release" name="movie[release]">
        </div>
        <select class="form-select" id="movie-genre" name="movie[genre]">
            <option value="" selected="selected">Genre</option>
              <?php
                $genres = get_movie_genres();
                foreach ($genres as $code => $value) {
              ?>
                <option value="<?php echo $value['genre']; ?>"> <?php echo $value['genre']; ?> </option>
              <?php
                }
              ?>
        </select>
        <button type="submit" class="btn btn-primary">Search</button>
      </form>
    </div>

    <?php
      if (isset($_POST['movie'])){
        $movie = $_POST['movie'];
        $title = 'ND';
        if (!empty($movie['title']))
          $title = $movie['title'];
        $year = 'ND';
        if (!empty($movie['year']))
          $year = $movie['year'];
        $length = 'ND';
        if (!empty($movie['length']))
          $length = $movie['length'];
        $release = 'ND';
        if (!empty($movie['release']))
          $release = $movie['release'];
        $genre = 'ND';
        if (!empty($movie['genre']))
          $genre = $movie['genre'];
    ?>
    <hr>
    <div>
    <h3>Titolo: <span> <?php echo $title; ?> </span></h3>
    <h3>Anno: <span> <?php echo $year; ?> </span></h3>
    <h3>Durata: <span> <?php echo $length; ?> </span></h3>
    <h3>Data di uscita: <span> <?php echo $release; ?> </span></h3>
    <h3>Genere: <span> <?php echo $genre; ?> </span></h3>
    </div>
    <?php
      }
    ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
  </body>
</html>