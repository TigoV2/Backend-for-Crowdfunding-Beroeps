<?php
declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../../cinfo/config.php";
require_once "../../auth/session.inc.php";
require_once "../../auth/auth.inc.php";
require_once "../functions/functions.php";

requireLogin();

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("Ongeldig werk-ID.");
}

$workId = (int) $_GET['id'];


try {
    $deleteWork = deleteWork($pdo, $workId);
} catch (Exception $e) {
    echo "Fout bij verwijderen werk: " . $e->getMessage();
    exit;
} 