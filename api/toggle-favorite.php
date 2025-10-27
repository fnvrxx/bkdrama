<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$action = $_POST['action'] ?? '';
$drama_id = intval($_POST['drama_id'] ?? 0);
$user_id = getUserId();

if (empty($action) || $drama_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    if ($action === 'add') {
        // Tambah favorit
        $query = "INSERT INTO favorit (user_id, drama_id) VALUES (:user_id, :drama_id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':drama_id', $drama_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Ditambahkan ke favorit']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan favorit']);
        }
    } elseif ($action === 'remove') {
        // Hapus favorit
        $query = "DELETE FROM favorit WHERE user_id = :user_id AND drama_id = :drama_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':drama_id', $drama_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Dihapus dari favorit']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus favorit']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Drama sudah ada di favorit']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>