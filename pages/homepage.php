<?php
declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../cinfo/config.php";
require_once "../auth/session.inc.php";
require_once "../auth/auth.inc.php";
require_once "./functions/functions.php";

requireLogin();

try {
    $home = fetchHome($pdo);
    $aantalRows = count($home);
} catch (PDOException $e) {
    handleServerError($e);
}

echo '<script>';
echo 'console.log("' . session_id() . ' | ' . $_SESSION['username'] . '")';
echo '</script>';

include_once '../';