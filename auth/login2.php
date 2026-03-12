<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "../cinfo/config.php";
require "session.inc.php";

$error = '';

function getInput($key) {
    return isset($_POST[$key]) ? trim(htmlspecialchars(strip_tags($_POST[$key]), ENT_QUOTES, 'UTF-8')) : null;
}

function getUserByUsername($pdo, $username) {
    $query = "SELECT * FROM users WHERE username = :username";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserByEmail($pdo, $email) {
    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

function loginUser($user) {
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];

    header("Location: ../index.php");
    exit;
}

function handleLogin($pdo) {
    global $error;

    $usernameOrEmail = getInput('username') ?: getInput('email');
    $password = getInput('password');

    if ($usernameOrEmail && $password) {
        if (filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
            $user = getUserByEmail($pdo, $usernameOrEmail);
        } else {
            $user = getUserByUsername($pdo, $usernameOrEmail);
        }

        if ($user && verifyPassword($password, $user['password'])) {
            loginUser($user);
        } else {
            $error = "Username/Email or Password doesn't match!";
        }
    } else {
        $error = "Please enter either username or email and password!";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    handleLogin($pdo);
}