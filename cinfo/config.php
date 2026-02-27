
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/env.php';

$servername = $_ENV['DB_HOST'];
$mydb = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$mydb", $username, $password, $options);

    // echo 'Connected to the database successfully!';
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}