<?php
/**
 * Delete Episode Handler
 * Handles episode deletion with file cleanup
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../includes/auth.php';

// Must be admin or superadmin
requireRole(['admin', 'superadmin']);

$database = new Database();
$db = $database->getConnection();

// Get episode ID
$episode_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($episode_id <= 0) {
    header("Location: manage-movies.php?error=invalid_id");
    exit();
}

// Get episode details before deletion (need drama_id for redirect)
$episode_query = "SELECT e.*, d.id as drama_id 
                  FROM episodes e 
                  JOIN drama d ON e.id_drama = d.id 
                  WHERE e.id = ?";
$episode_stmt = $db->prepare($episode_query);
$episode_stmt->execute([$episode_id]);
$episode = $episode_stmt->fetch(PDO::FETCH_ASSOC);

if (!$episode) {
    header("Location: manage-movies.php?error=episode_not_found");
    exit();
}

$drama_id = $episode['drama_id'];

// Function to safely delete file
function deleteFile($filepath)
{
    if (!empty($filepath) && file_exists($filepath)) {
        if (@unlink($filepath)) {
            error_log("Deleted file: " . $filepath);
            return true;
        } else {
            error_log("Failed to delete file: " . $filepath);
            return false;
        }
    }
    return false;
}

try {
    // Start transaction
    $db->beginTransaction();

    // 1. Delete related watch history first (foreign key constraint)
    $delete_history_query = "DELETE FROM users_history WHERE eps_id = ?";
    $delete_history_stmt = $db->prepare($delete_history_query);
    $delete_history_stmt->execute([$episode_id]);
    $deleted_history = $delete_history_stmt->rowCount();

    error_log("Deleted {$deleted_history} watch history records for episode {$episode_id}");

    // 2. Delete episode from database
    $delete_episode_query = "DELETE FROM episodes WHERE id = ?";
    $delete_episode_stmt = $db->prepare($delete_episode_query);
    $delete_episode_stmt->execute([$episode_id]);

    if ($delete_episode_stmt->rowCount() > 0) {
        // 3. Delete associated files (video and thumbnail)
        $files_deleted = 0;

        // Delete video file
        if (!empty($episode['link_video'])) {
            $video_path = '../' . $episode['link_video'];
            if (deleteFile($video_path)) {
                $files_deleted++;
            }
        }

        // Delete thumbnail file
        if (!empty($episode['thumbnail'])) {
            $thumb_path = '../' . $episode['thumbnail'];
            if (deleteFile($thumb_path)) {
                $files_deleted++;
            }
        }

        // Commit transaction
        $db->commit();

        error_log("Successfully deleted episode {$episode_id}. Files deleted: {$files_deleted}");

        // Redirect with success message
        header("Location: manage-episodes.php?drama_id={$drama_id}&success=deleted");
        exit();

    } else {
        // Rollback if episode deletion failed
        $db->rollBack();
        error_log("Failed to delete episode {$episode_id} from database");
        header("Location: manage-episodes.php?drama_id={$drama_id}&error=delete_failed");
        exit();
    }

} catch (PDOException $e) {
    // Rollback on error
    $db->rollBack();
    error_log("Error deleting episode: " . $e->getMessage());
    header("Location: manage-episodes.php?drama_id={$drama_id}&error=database_error");
    exit();
}
?>