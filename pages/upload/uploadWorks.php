<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once "../../cinfo/config.php";
require_once "../../auth/session.inc.php";
require_once "../../auth/auth.inc.php";
require_once "../functions/functions.php";

requireLogin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim(htmlspecialchars(strip_tags($_POST['title']), ENT_QUOTES, 'UTF-8')) : '';
    $description = isset($_POST['description']) ? trim(htmlspecialchars(strip_tags($_POST['description']), ENT_QUOTES, 'UTF-8')) : '';
    $role = isset($_POST['role']) ? trim(htmlspecialchars(strip_tags($_POST['role']), ENT_QUOTES, 'UTF-8')) : '';
    $photoUploaded = isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK;
    $goal = isset($_POST['goal']) ? (float) $_POST['goal'] : 0;

    $sender = $_SERVER['HTTP_REFERER'] ?? '';
    if (!empty($sender) && strpos($sender, '102575.stu.sd-lab.nl') === false) {
        $errors['sender'] = "Verkeerde afzender!";
    }

    if (empty($title) || empty($description) || !$photoUploaded || empty($role)) {
        $errors['fields'] = "Alle velden zijn verplicht!";
    }

    if (empty($errors)) {
        $targetPath = "";
        try {
            $pdo->beginTransaction();

            $photoName = uniqid('', true) . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $_FILES['photo']['name']);
            $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/beroeps2/Beroeps_CrowdFunding/pages/upload-img/';
            
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $targetPath = $targetDir . $photoName;
            $photoUrl = '/beroeps2/Beroeps_CrowdFunding/pages/upload-img/'. $photoName;
            $fileTmp = $_FILES['photo']['tmp_name'];

            $imageInfo = getimagesize($fileTmp);
            if ($imageInfo === false) {
                throw new Exception("Dit is geen geldige afbeelding.");
            }

            if (!move_uploaded_file($fileTmp, $targetPath)) {
                throw new Exception("Uploaden mislukt: Controleer folder permissies.");
            }   

            $result = uploadWorks($pdo, $_SESSION['user_id'], $title, $description, $photoUrl, $role, $goal);
            $workId = $result['work_id'];
            
            $pdo->commit();
            echo json_encode(['success' => true, 'work' => ['work_id' => $workId, 'title' => $title, 'description' => $description, 'photo' => $photoUrl, 'role' => $role, 'name' => $_SESSION['username'], 'amount' => 0, 'goal' => $goal]]);
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (!empty($targetPath) && file_exists($targetPath)) {
                unlink($targetPath);
            }
            
            error_log($e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }
}