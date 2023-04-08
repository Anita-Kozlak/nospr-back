php
Copy code
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Origin, Content-Type, Authorization");

$url = getenv('JAWSDB_URL');
$dbparts = parse_url($url);

$hostname = $dbparts['host'];
$username = $dbparts['user'];
$password = $dbparts['pass'];
$database = ltrim($dbparts['path'],'/');

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| First we need to get an application instance. This creates an instance
| of the application / container and bootstraps the application so it
| is ready to receive HTTP / Console requests from the environment.
|
*/

$app = require __DIR__.'/../bootstrap/app.php';

// Set the database configuration
$app->configure('database');
$app->config['database.connections.mysql.host'] = $hostname;
$app->config['database.connections.mysql.database'] = $database;
$app->config['database.connections.mysql.username'] = $username;
$app->config['database.connections.mysql.password'] = $password;

// Create the database connection
try {
    $pdo = new PDO("mysql:host={$hostname};dbname={$database}", $username, $password);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

define('ROOT_PATH', dirname(__DIR__));

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$app->run();