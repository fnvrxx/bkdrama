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

$drama_id = intval($_POST['drama_id'] ?? 0);
$rating = floatval($_POST['rating'] ?? 0);
$user_id = getUserId();

// Validasi
if ($drama_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid drama ID']);
    exit();
}

if ($rating < 0 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 0 and 5']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Check if drama exists
    $check_query = "SELECT id FROM drama WHERE id = :drama_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':drama_id', $drama_id);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Drama not found']);
        exit();
    }

    // Insert or update rating
    $query = "INSERT INTO ratings (user_id, drama_id, rating)
              VALUES (:user_id, :drama_id, :rating)
              ON DUPLICATE KEY UPDATE rating = :rating2, updated_at = CURRENT_TIMESTAMP";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':drama_id', $drama_id);
    $stmt->bindParam(':rating', $rating);
    $stmt->bindParam(':rating2', $rating);

    if ($stmt->execute()) {
        // Get updated average rating
        $avg_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings
                      FROM ratings WHERE drama_id = :drama_id";
        $avg_stmt = $db->prepare($avg_query);
        $avg_stmt->bindParam(':drama_id', $drama_id);
        $avg_stmt->execute();
        $result = $avg_stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'message' => 'Rating berhasil disimpan',
            'avg_rating' => round($result['avg_rating'], 1),
            'total_ratings' => $result['total_ratings']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save rating']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
