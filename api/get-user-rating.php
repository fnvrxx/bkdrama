<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$drama_id = intval($_GET['drama_id'] ?? 0);
$user_id = getUserId();

if ($drama_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid drama ID']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get user's rating for this drama
    $query = "SELECT rating FROM ratings WHERE user_id = :user_id AND drama_id = :drama_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':drama_id', $drama_id);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get average rating and total ratings
    $avg_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings
                  FROM ratings WHERE drama_id = :drama_id";
    $avg_stmt = $db->prepare($avg_query);
    $avg_stmt->bindParam(':drama_id', $drama_id);
    $avg_stmt->execute();
    $avg_result = $avg_stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'user_rating' => $result ? floatval($result['rating']) : null,
        'avg_rating' => $avg_result['avg_rating'] ? round($avg_result['avg_rating'], 1) : 0,
        'total_ratings' => intval($avg_result['total_ratings'])
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
