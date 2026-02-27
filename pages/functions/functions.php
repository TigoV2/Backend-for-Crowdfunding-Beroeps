<?php
declare(strict_types=1);

function fetchHome(PDO $pdo): array {
    $query = "
        SELECT title, description, photo
        FROM works 
        ORDER BY created_at DESC LIMIT 3
        ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchUser(PDO $pdo, int $ID): array {
    $query = "
        SELECT 
            u.user_id,
            u.username,
            u.name,
            u.email,
            w.title,
            w.description,
            w.photo,
            uw.role,
            work_counts.total_works,
            donation_sums.total_donations
        FROM users u
        LEFT JOIN user_works uw ON u.user_id = uw.user_id
        LEFT JOIN works w ON uw.work_id = w.work_id
        LEFT JOIN (
            SELECT user_id, COUNT(*) AS total_works
            FROM user_works
            GROUP BY user_id
        ) AS work_counts ON u.user_id = work_counts.user_id
        LEFT JOIN (
            SELECT user_id, SUM(amount) AS total_donations
            FROM donations
            GROUP BY user_id
        ) AS donation_sums ON u.user_id = donation_sums.user_id
        WHERE u.user_id = :user_id
    ";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':user_id', $ID, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchSessionUser(PDO $pdo): array {
    if (!isset($_SESSION['username'])) {
        return [];
    }

    $query = "SELECT * FROM users WHERE username = :username";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $_SESSION['username'], PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}


function fetchWorks(PDO $pdo): array {
    $query = "
        SELECT 
            w.work_id,
            w.title,
            w.description,
            w.photo,
            u.name,
            w.goal,
            COALESCE(SUM(d.amount), 0) AS amount
        FROM works w
        JOIN user_works uw ON w.work_id = uw.work_id
        JOIN users u       ON uw.user_id = u.user_id
        LEFT JOIN donations d ON w.work_id = d.work_id
        GROUP BY
            w.work_id,
            w.title,
            w.description,
            w.photo,
            u.name
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchWork(PDO $pdo, int $userId): array {
    $query = "
        SELECT  
            w.work_id,
            w.title,
            w.description,
            w.photo,
            uw.role,
            w.goal,
            creator.name AS name,
            COALESCE(SUM(d.amount), 0) AS amount
        FROM works w
        JOIN user_works uw ON w.work_id = uw.work_id
        JOIN users creator ON uw.user_id = creator.user_id
        LEFT JOIN donations d ON w.work_id = d.work_id
        WHERE uw.user_id = :user_id
        GROUP BY 
            w.work_id,
            w.title,
            w.description,
            w.photo,
            uw.role,
            creator.name
    ";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function uploadWorks(PDO $pdo, int $userId, string $title, string $description, string $photo, string $role, float $goal): array {
    $queryWorks = "INSERT INTO works (title, description, photo, goal) VALUES (:title, :description, :photo, :goal)";
    $stmt = $pdo->prepare($queryWorks);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':photo', $photo);
    $stmt->bindParam(':goal', $goal);
    $stmt->execute();

    $workId = $pdo->lastInsertId();

    $queryUserWorks = "INSERT INTO user_works (user_id, work_id, role) VALUES (:user_id, :work_id, :role)";
    $stmt = $pdo->prepare($queryUserWorks); 
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':work_id', $workId);
    $stmt->bindParam(':role', $role);
    $stmt->execute();

    if ($stmt->rowCount()){
        $resultaat = "Het item is toegevoegd!";
    } else {
        $resultaat = "Er is iets fout gegaan en het item is niet toegevoegd!";
    }

    return [
        'work_id' => $workId,
        'result' => $resultaat
    ];
}

function uploadDonationsForWork(PDO $pdo, int $workId, float $amount): string {
    $query = "INSERT INTO donations (user_id, work_id, amount) VALUES (:user_id, :work_id, :amount)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':work_id', $workId, PDO::PARAM_INT);
    $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount()) {
        $resultaat = "De donatie is toegevoegd aan het werk!";
    } else {
        $resultaat = "Er is iets fout gegaan en de donatie is niet toegevoegd!";
    }

    return $resultaat;
}

function uploadDonations(PDO $pdo, float $amount): string {
    $query = "INSERT INTO donations (user_id, work_id, amount) VALUES (:user_id, NULL, :amount)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount()){
        $resultaat = "Het item is toegevoegd!";
    } else {
        $resultaat = "Er is iets fout gegaan en het item is niet toegevoegd!";
    }

    return $resultaat;
}

function isWorkCreator(PDO $pdo, int $workId, int $userId): bool {
    $query = "
        SELECT 1
        FROM user_works
        WHERE work_id = :work_id
          AND user_id = :user_id
          AND role = 'creator'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':work_id' => $workId,
        ':user_id' => $userId
    ]);

    return (bool) $stmt->fetchColumn();
}


function deleteWork(PDO $pdo, int $workId): string {
    $userId = $_SESSION['user_id'];

    if (!isWorkCreator($pdo, $workId, $userId)) {
        return "Je hebt geen rechten om dit werk te verwijderen.";
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT title FROM works WHERE work_id = :work_id");
        $stmt->execute([':work_id' => $workId]);
        $work = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$work) {
            throw new Exception("Werk niet gevonden.");
        }

        $workTitle = $work['title'];
        $stmt = $pdo->prepare("
            UPDATE donations
            SET work_id = NULL, deleted_work_title = :title
            WHERE work_id = :work_id
        ");
        $stmt->execute([
            ':title'   => $workTitle,
            ':work_id'=> $workId
        ]);

        $stmt = $pdo->prepare("DELETE FROM user_works WHERE work_id = :work_id");
        $stmt->execute([':work_id' => $workId]);

        $stmt = $pdo->prepare("DELETE FROM works WHERE work_id = :work_id");
        $stmt->execute([':work_id' => $workId]);

        $pdo->commit();
        return "Het werk en alle gerelateerde data zijn succesvol verwijderd.";

    } catch (Exception $e) {
        $pdo->rollBack();
        return "Fout bij verwijderen: " . $e->getMessage();
    }
}

function handleServerError(PDOException $e): void {
    http_response_code(500);
    error_log($e->getMessage());

    echo "Internal Server Error. Please try again later.";
    echo "<!-- Error details: " . $e->getMessage() . " -->";
    exit;
}