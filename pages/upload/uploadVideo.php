<?php
declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../../cinfo/config.php";
require_once "../../auth/session.inc.php";
require_once "../../auth/auth.inc.php";

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Geen video geüpload.']);
        exit;
    }

    $fileTmp  = $_FILES['video']['tmp_name'];
    $fileName = $_FILES['video']['name'];
    $fileSize = $_FILES['video']['size'];

    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($extension !== 'mp4') {
        echo json_encode(['success' => false, 'message' => 'Alleen MP4 video’s toegestaan.']);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $fileTmp);
    finfo_close($finfo);

    if ($mime !== 'video/mp4') {
        echo json_encode(['success' => false, 'message' => 'Ongeldig videobestand.']);
        exit;
    }

    if ($fileSize > 100 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Video is te groot.']);
        exit;
    }

    $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/beroeps2/Beroeps_CrowdFunding/pages/upload-video/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $newFileName = uniqid('video_', true) . '.mp4';
    $targetPath  = $targetDir . $newFileName;
    $videoUrl    = '/beroeps2/Beroeps_CrowdFunding/pages/upload-video/' . $newFileName;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT video FROM users WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $oldVideo = $stmt->fetchColumn();

        if (!move_uploaded_file($fileTmp, $targetPath)) {
            throw new Exception('Uploaden mislukt.');
        }

        $stmt = $pdo->prepare("
            UPDATE users
            SET video = :video
            WHERE user_id = :user_id
        ");
        $stmt->execute([
            'video'   => $videoUrl,
            'user_id' => $_SESSION['user_id']
        ]);

        if (!empty($oldVideo)) {
            $oldPath = $_SERVER['DOCUMENT_ROOT'] . $oldVideo;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'videoUrl' => $videoUrl,
            'message' => 'Video succesvol geüpload!'
        ]);
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if (file_exists($targetPath)) unlink($targetPath);

        echo json_encode(['success' => false, 'message' => 'Er ging iets mis bij het uploaden.']);
        exit;
    }
}