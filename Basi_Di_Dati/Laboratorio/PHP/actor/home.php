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
    <h1 style="text-align:center">Actor Rating</h1>
    <h3 style="text-align:center">Select an actor</h3>
    <hr>

    <div class="container">
      <form class="row g-3" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET">
        <div class="col-md">
            <input type="text" class="form-control" id="title" name="name" placeholder="Actor">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
      </form>
    </div>

    <?php
    if (!empty($_GET['name'])) {
        $actors = get_actor_stats_by_name($_GET['name']);

        if (count($actors) > 0) {
            echo "<div class='container'>";
            echo "<table border='1' class='table table-striped table-hover'>";
            echo "<tr><th>Nome</th><th>Numero Di Film</th><th>Media Valutazione</th></tr>";

            foreach ($actors as $actor) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($actor['given_name']) . "</td>";
                echo "<td>" . htmlspecialchars($actor['num_movies']) . "</td>";
                echo "<td>" . htmlspecialchars(number_format($actor['avg_score'], 2)) . "</td>";
                echo "</tr>";
            }

            echo "</table>";
            echo "</div>";
        } else {
            echo "<p>Nessun attore trovato per '" . htmlspecialchars($_GET['name']) . "'.</p>";
        }
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
  </body>
</html>