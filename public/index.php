<?php
$url = getenv('JAWSDB_URL');
$dbparts = parse_url($url);

$hostname = $dbparts['host'];
$username = $dbparts['user'];
$password = $dbparts['pass'];
$database = ltrim($dbparts['path'],'/');

try {
    $pdo = new PDO("mysql:host={$hostname};dbname={$database}", $username, $password);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

echo "Connected successfully to JawsDB MySQL database!";

// Here, you can add any code that needs to use the database connection.

?>
