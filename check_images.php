<?php
// check_images.php
echo "<h1>Image Location Check</h1>";

$paths = [
    __DIR__ . '/images/logo-original.png',
    __DIR__ . '/images/Picture1.png',
    __DIR__ . '/images/Picture2.png',
    __DIR__ . '/logo-original.png',
    __DIR__ . '/Picture1.png',
    __DIR__ . '/Picture2.png',
];

foreach ($paths as $path) {
    echo "<p>";
    echo "Checking: $path<br>";
    if (file_exists($path)) {
        echo "✅ FOUND!<br>";
        echo "File size: " . filesize($path) . " bytes<br>";
    } else {
        echo "❌ Not found<br>";
    }
    echo "</p>";
}
?>
