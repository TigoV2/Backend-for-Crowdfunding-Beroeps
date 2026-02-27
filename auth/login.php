<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "../cinfo/config.php";
require "session.inc.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['username']) &&
        isset($_POST['password']))  {

        $username1 = trim(htmlspecialchars(strip_tags($_POST['username']), ENT_QUOTES, 'UTF-8'));
        $password1 = trim(htmlspecialchars(strip_tags($_POST['password']), ENT_QUOTES, 'UTF-8'));
    }

    if (strlen($username1)>0 && strlen($password1)>0)
    {
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $username1, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password1, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];

            header("Location: ../index.php");
            exit;
        } else {
            $error = "Username or Password doesn't match!";
        }

    } else {
        $error = "Please enter both username and password!";
    }
}