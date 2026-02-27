<?php
declare(strict_types=1);

function requireLogin(): void
{
    if (empty($_SESSION['username'])) {
        header('Location: ./login/login.html', true, 302);
        exit;
    }
}