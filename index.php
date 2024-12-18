<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fontom | PHP library for handling font files</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <h1>Fontom</h1>
        <h3>PHP library for handling font files</h3>
        <br>

        <?php
        require_once 'vendor/autoload.php';

        use Fontom\Fontom;

        // Example usage
        try {
            //$font = Fontom::load(__DIR__ . "/assets/fonts/Golos Text_Regular.ttf");
            //$font = Fontom::load(__DIR__ . "/assets/fonts/CoTextCorp.ttf");
            $font = Fontom::load(__DIR__ . "/assets/fonts/Calypso.ttf");
            //$font = Fontom::load(__DIR__ . "/assets/fonts/arial.ttf");
            //echo "Font Name: " . $font->getFontName() . "\n";
            echo "Font Author: " . $font->getFontAuthor() . "\n";
            // echo "Available Tables: " . implode(", ", $font->getTables()) . "\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>

</html>