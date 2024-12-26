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

        try {
            // Укажите путь к шрифту
            $fontPath = __DIR__ . "/assets/fonts/Golos Text_Regular.ttf";
            $fontPath = __DIR__ . "/assets/fonts/CoTextCorp.ttf";
            //$fontPath = __DIR__ . "/assets/fonts/Calypso.ttf";
            //$fontPath = __DIR__ . "/assets/fonts/arial.ttf";


            // Создайте экземпляр Fontom
            $fontom = new Fontom($fontPath);

            // Получите название шрифта
            echo "<strong>Font Name:</strong> " . $fontom->getFontName() . PHP_EOL . "<br>";

            // Получите автора или дизайнера шрифта
            echo "<strong>Font Author:</strong> " . $fontom->getFontAuthor() . PHP_EOL . "<br>";

            // Количество глифов шрифта
            echo "<strong>Font glyphs num:</strong> " . $fontom->getNumberOfGlyphs() . PHP_EOL . "<br>";

            // Все записи таблицы name
            $nameTableRows = '';
            foreach($fontom->getAllNameRecords() as $nameRecord){
                $nameTableRows .= "<tr><td>{$nameRecord['nameId']}</td><td>{$nameRecord['nameDescription']}</td><td>{$nameRecord['value']}</td></tr>";
            }
            echo "<table>{$nameTableRows}</table>";
        } catch (Exception $e) {
            // Обработайте исключения
            echo "Error: " . $e->getMessage() . PHP_EOL;
        }
        ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>

</html>