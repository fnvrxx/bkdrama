<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole(['admin', 'superadmin']);

$database = new Database();
$db = $database->getConnection();

$drama_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($drama_id <= 0) {
    header("Location: manage-movies.php?error=invalid_id");
    exit();
}

try {
    // Hapus drama (CASCADE akan otomatis hapus episodes, favorit, dll)
    $delete_query = "DELETE FROM drama WHERE id = ?";
    $delete_stmt = $db->prepare($delete_query);

    if ($delete_stmt->execute([$drama_id])) {
        header("Location: manage-movies.php?success=deleted");
    } else {
        header("Location: manage-movies.php?error=delete_failed");
    }
} catch (PDOException $e) {
    header("Location: manage-movies.php?error=database");
}

exit();
?>