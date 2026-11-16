<?php
$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
$stmt = $db->query("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name");

foreach ($stmt as $row) {
    echo $row['name'] . PHP_EOL;
}
