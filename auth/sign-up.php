<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../cinfo/config.php';
require 'session.inc.php';

$errorMessages = [];

function sanitizeInput(string $input): string
{
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

function validateInput(array $data): array
{
    $errors = [];

    if ($data['username'] === '' || $data['password'] === '') {
        $errors[] = "username en wachtwoord zijn verplicht!";
    }
    if ($data['name'] === '') {
        $errors[] = "Naam is verplicht!";
    }
    if ($data['email'] === '') {
        $errors[] = "Geen e-mailadres ingevoerd!";
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Ongeldig e-mailadres!";
    }

    if ($data['DoB'] === '') {
        $errors[] = "Geboortedatum is verplicht!";
    } else {
        $begin = strtotime($data['DoB']);
        if ($begin === false) {
            $errors[] = "Onbegrijpelijke datum invoer!";
        } else {
            $data['DoB'] = date("Y-m-d", $begin);
        }
    }

    return ['errors' => $errors, 'data' => $data];
}

function registerUser(PDO $pdo, array $data): int|string
{
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, password, name, email, DoB, about) 
            VALUES (:username, :password, :name, :email, :DoB, :about)"
        );
        $stmt->execute([
            'username' => $data['username'],
            'password' => $hashedPassword,
            'name' => $data['name'],
            'email' => $data['email'],
            'DoB' => $data['DoB'],
            'about' => $data['about']
        ]);
        
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) return "Username or Email already exists!";
        return "Database error!";
    }
}

function loginUser(int $userId, string $username): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'DoB' => sanitizeInput($_POST['DoB'] ?? ''),
        'about' => sanitizeInput($_POST['about'] ?? '')
    ];

    $validation = validateInput($input);
    $errorMessages = $validation['errors'];
    $input = $validation['data'];

    if (empty($errorMessages)) {
        $result = registerUser($pdo, $input);

        if (is_int($result)) {
            loginUser($result, $input['username']);          
        } else {
            $_SESSION['errors'] = [$result];
            header("Location: ../login/login.html");
            exit;
        }
    } else {
        $_SESSION['errors'] = $errorMessages;
        header("Location: ../login/sign-up.html");
        exit;
    }
}