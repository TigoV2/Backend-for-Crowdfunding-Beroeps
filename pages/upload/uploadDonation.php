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

$errors = [];
$amount = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['amount'])) {
        $amount = (float) $_POST['amount'];
    }
}

if (isset($_SERVER['HTTP_REFERER']) &&
    strpos($_SERVER['HTTP_REFERER'], '102575.stu.sd-lab.nl') === false) {
    $errors['sender'] = "<p>Verkeerde afzender!</p>";
}

if (empty($amount)) {
    $errors['amount'] = "<p>Dit is verplicht!</p>";
}

if ($amount <= 0) {
    $errors['amount'] = "<p>Ongeldig donatiebedrag.</p>";
}

if (empty($errors)) {
    try {
        uploadDonations($pdo, $amount);

        header("Location: ../index.php?success=1");
        exit();
    } catch (Throwable $e) {
        $errors['exception'] =
            "<p>Fout bij het verwerken van de donatie.</p>";
    }
} else {
    header("Location: ../index.php");
    exit();
}