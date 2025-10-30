<?php
/**
 * Watch History API - Fixed for actual database structure
 * Table: users_history
 * Columns: id, user_id, eps_id, progress, completed, last_watched
 * 
 * Note: No id_drama column, no duration column
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/auth.php';

// CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = getUserId();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ============================================
// SAVE WATCH PROGRESS
// ============================================
if ($action === 'save_progress' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Note: We receive drama_id but don't save it (table doesn't have id_drama column)
    $drama_id = intval($_POST['drama_id'] ?? 0); // Not used in table
    $episode_id = intval($_POST['episode_id'] ?? 0);
    $watched_duration = intval($_POST['watched_duration'] ?? 0); // in seconds
    $total_duration = intval($_POST['total_duration'] ?? 0); // Not saved in table

    // Log untuk debugging
    error_log("=== Save Progress Request ===");
    error_log("User ID: {$user_id}");
    error_log("Episode ID: {$episode_id}");
    error_log("Watched: {$watched_duration} seconds");
    error_log("Total: {$total_duration} seconds");

    // Validate input
    if ($episode_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid episode ID']);
        exit;
    }

    if ($watched_duration < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid duration']);
        exit;
    }

    $is_completed = 0;

    if ($total_duration > 0) {
        // Jika durasi ditonton >= total durasi → completed = 1
        if ($watched_duration >= $total_duration) {
            $is_completed = 1;
        }
        // Atau jika sudah mencapai ≥90% (untuk antisipasi buffering, dll)
        elseif ($watched_duration >= ($total_duration * 0.9)) {
            $is_completed = 1;
        }
    } else {
        // Jika total_duration tidak diketahui, jangan tandai sebagai completed
        // (karena tidak bisa diverifikasi)
        $is_completed = 0;
    }

    try {
        // Check if record exists
        // Table structure: id, user_id, eps_id, progress, completed, last_watched
        $check_query = "SELECT id FROM users_history WHERE user_id = ? AND eps_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$user_id, $episode_id]);

        if ($check_stmt->rowCount() > 0) {
            // Update existing record
            $update_query = "UPDATE users_history 
                           SET progress = ?,
                               completed = ?,
                               last_watched = NOW()
                           WHERE user_id = ? AND eps_id = ?";

            $update_stmt = $db->prepare($update_query);
            $result = $update_stmt->execute([
                $watched_duration,
                $is_completed,
                $user_id,
                $episode_id
            ]);

            if ($result) {
                error_log("✅ UPDATE SUCCESS - Progress: {$watched_duration}s, Completed: {$is_completed}");
            } else {
                error_log("❌ UPDATE FAILED");
                error_log("Error Info: " . json_encode($update_stmt->errorInfo()));
            }
        } else {
            // Insert new record
            $insert_query = "INSERT INTO users_history 
                           (user_id, eps_id, progress, completed, last_watched)
                           VALUES (?, ?, ?, ?, NOW())";

            $insert_stmt = $db->prepare($insert_query);
            $result = $insert_stmt->execute([
                $user_id,
                $episode_id,
                $watched_duration,
                $is_completed
            ]);

            if ($result) {
                error_log("✅ INSERT SUCCESS - Progress: {$watched_duration}s, Completed: {$is_completed}");
            } else {
                error_log("❌ INSERT FAILED");
                error_log("Error Info: " . json_encode($insert_stmt->errorInfo()));
            }
        }

        // Calculate percentage for response (if total_duration available)
        $progress_percentage = 0;
        if ($total_duration > 0) {
            $progress_percentage = round(($watched_duration / $total_duration) * 100, 2);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Progress saved',
            'data' => [
                'progress_seconds' => $watched_duration,
                'progress_percentage' => $progress_percentage,
                'is_completed' => $is_completed,
                'saved_at' => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (PDOException $e) {
        error_log("❌ DATABASE ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

    exit;
}

// ============================================
// GET WATCH PROGRESS FOR EPISODE
// ============================================
if ($action === 'get_progress' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $episode_id = intval($_GET['episode_id'] ?? 0);

    if ($episode_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid episode ID']);
        exit;
    }

    try {
        $query = "SELECT 
                    progress as watched_duration,
                    completed as is_completed,
                    last_watched
                  FROM users_history
                  WHERE user_id = ? AND eps_id = ?";

        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $episode_id]);

        $progress = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($progress) {
            echo json_encode([
                'success' => true,
                'data' => $progress
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'data' => [
                    'watched_duration' => 0,
                    'is_completed' => false,
                    'last_watched' => null
                ]
            ]);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

    exit;
}

// ============================================
// GET CONTINUE WATCHING LIST
// ============================================
if ($action === 'continue_watching' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = intval($_GET['limit'] ?? 10);
    $limit = min(50, max(1, $limit));

    try {
        // Get episode info to determine drama_id
        // Note: LIMIT must be an integer, not a bound parameter with quotes
        $query = "SELECT
                    uh.eps_id as episode_id,
                    uh.progress as watched_duration,
                    uh.last_watched,
                    e.id_drama as drama_id,
                    e.eps_title as episode_title,
                    e.eps_number as episode_number,
                    e.link_video as video_url,
                    d.title as drama_title,
                    d.thumbnail as drama_thumbnail,
                    d.genre
                  FROM users_history uh
                  JOIN episodes e ON uh.eps_id = e.id
                  JOIN drama d ON e.id_drama = d.id
                  WHERE uh.user_id = ?
                    AND uh.completed = 0
                    AND uh.progress > 0
                  ORDER BY uh.last_watched DESC
                  LIMIT " . $limit;

        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);

        $continue_watching = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'count' => count($continue_watching),
            'data' => $continue_watching
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

    exit;
}

// ============================================
// GET WATCH HISTORY FOR DRAMA
// ============================================
if ($action === 'drama_history' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $drama_id = intval($_GET['drama_id'] ?? 0);

    if ($drama_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid drama ID']);
        exit;
    }

    try {
        $query = "SELECT 
                    uh.eps_id as episode_id,
                    uh.progress as watched_duration,
                    uh.completed as is_completed,
                    e.eps_number as episode_number,
                    e.eps_title as episode_title
                  FROM users_history uh
                  JOIN episodes e ON uh.eps_id = e.id
                  WHERE uh.user_id = ? AND e.id_drama = ?
                  ORDER BY e.eps_number ASC";

        $stmt = $db->prepare($query);
        $stmt->execute([$user_id, $drama_id]);

        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate statistics
        $total_episodes = count($history);
        $completed_episodes = array_sum(array_column($history, 'is_completed'));

        echo json_encode([
            'success' => true,
            'statistics' => [
                'total_episodes_watched' => $total_episodes,
                'completed_episodes' => $completed_episodes
            ],
            'data' => $history
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

    exit;
}

// ============================================
// DELETE WATCH HISTORY
// ============================================
if ($action === 'delete_progress' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $episode_id = intval($_POST['episode_id'] ?? 0);

    if ($episode_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid episode ID']);
        exit;
    }

    try {
        $delete_query = "DELETE FROM users_history WHERE user_id = ? AND eps_id = ?";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([$user_id, $episode_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Watch history deleted'
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

    exit;
}

// ============================================
// TEST ENDPOINT
// ============================================
if ($action === 'test') {
    // Check table structure
    try {
        $check_query = "SHOW COLUMNS FROM users_history";
        $check_stmt = $db->query($check_query);
        $columns = $check_stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'success' => true,
            'message' => 'API is working!',
            'user_id' => $user_id,
            'timestamp' => date('Y-m-d H:i:s'),
            'table_columns' => $columns
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => true,
            'message' => 'API is working but could not check table',
            'user_id' => $user_id,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    exit;
}

// Invalid action
http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'Invalid action',
    'received_action' => $action,
    'available_actions' => [
        'save_progress (POST)',
        'get_progress (GET)',
        'continue_watching (GET)',
        'drama_history (GET)',
        'delete_progress (POST)',
        'test (GET)'
    ]
]);
?>