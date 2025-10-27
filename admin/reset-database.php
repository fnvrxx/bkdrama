<?php
/**
 * RESET DATABASE TOOL
 * Tool untuk reset AUTO_INCREMENT IDs
 * 
 * ⚠️ WARNING: Use with caution!
 * Akses: http://localhost/bkdrama/admin/reset-database.php
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole('superadmin'); // Only superadmin!

$database = new Database();
$db = $database->getConnection();

$result = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($confirm !== 'RESET') {
        $result = '<div class="alert alert-error">❌ Konfirmasi salah! Ketik "RESET" dengan huruf kapital.</div>';
    } else {
        try {
            $db->beginTransaction();

            switch ($action) {
                case 'reset_episodes':
                    // Delete all episodes
                    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
                    $db->exec("TRUNCATE TABLE episodes");
                    $db->exec("UPDATE drama SET total_eps = 0");
                    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
                    $result = '<div class="alert alert-success">✅ Semua episode berhasil dihapus & ID direset!</div>';
                    $success = true;
                    break;

                case 'reset_drama':
                    // Delete all drama & episodes
                    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
                    $db->exec("TRUNCATE TABLE users_history");
                    $db->exec("TRUNCATE TABLE favorit");
                    $db->exec("TRUNCATE TABLE episodes");
                    $db->exec("TRUNCATE TABLE drama");
                    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
                    $result = '<div class="alert alert-success">✅ Semua drama & episode berhasil dihapus & ID direset!</div>';
                    $success = true;
                    break;

                case 'reset_favorites':
                    // Delete all favorites
                    $db->exec("TRUNCATE TABLE favorit");
                    $result = '<div class="alert alert-success">✅ Semua favorit berhasil dihapus & ID direset!</div>';
                    $success = true;
                    break;

                case 'reset_history':
                    // Delete watch history
                    $db->exec("TRUNCATE TABLE users_history");
                    $result = '<div class="alert alert-success">✅ Watch history berhasil dihapus & ID direset!</div>';
                    $success = true;
                    break;

                case 'reset_all':
                    // Full reset (keep users)
                    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
                    $db->exec("TRUNCATE TABLE users_history");
                    $db->exec("TRUNCATE TABLE favorit");
                    $db->exec("TRUNCATE TABLE episodes");
                    $db->exec("TRUNCATE TABLE drama");
                    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
                    $result = '<div class="alert alert-success">✅ Database berhasil direset! (Users tetap ada)</div>';
                    $success = true;
                    break;

                default:
                    $result = '<div class="alert alert-error">❌ Action tidak valid!</div>';
            }

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            $result = '<div class="alert alert-error">❌ Error: ' . $e->getMessage() . '</div>';
        }
    }
}

// Get current counts
$counts = [];
$counts['users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$counts['drama'] = $db->query("SELECT COUNT(*) FROM drama")->fetchColumn();
$counts['episodes'] = $db->query("SELECT COUNT(*) FROM episodes")->fetchColumn();
$counts['favorit'] = $db->query("SELECT COUNT(*) FROM favorit")->fetchColumn();
$counts['history'] = $db->query("SELECT COUNT(*) FROM users_history")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Database - BKDrama</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #dc3545;
            margin-bottom: 10px;
        }

        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            color: #856404;
        }

        .stats {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .stats h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .stats table {
            width: 100%;
            border-collapse: collapse;
        }

        .stats td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        .stats td:first-child {
            font-weight: 600;
        }

        .reset-option {
            background: white;
            border: 2px solid #ddd;
            padding: 20px;
            margin: 15px 0;
            border-radius: 5px;
        }

        .reset-option h4 {
            color: #333;
            margin-bottom: 10px;
        }

        .reset-option p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .form-group {
            margin: 15px 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Admin</a>

        <h1>⚠️ Reset Database Tool</h1>
        <p style="color: #666; margin-bottom: 20px;">SuperAdmin Only - Use with extreme caution!</p>

        <?php echo $result; ?>

        <div class="warning">
            <strong>⚠️ WARNING:</strong> Reset akan menghapus data secara permanen!<br>
            <strong>📦 Backup dulu</strong> sebelum melakukan reset!
        </div>

        <div class="stats">
            <h3>📊 Current Database Stats:</h3>
            <table>
                <tr>
                    <td>👥 Users:</td>
                    <td><strong><?php echo $counts['users']; ?></strong></td>
                </tr>
                <tr>
                    <td>🎭 Drama:</td>
                    <td><strong><?php echo $counts['drama']; ?></strong></td>
                </tr>
                <tr>
                    <td>📺 Episodes:</td>
                    <td><strong><?php echo $counts['episodes']; ?></strong></td>
                </tr>
                <tr>
                    <td>⭐ Favorites:</td>
                    <td><strong><?php echo $counts['favorit']; ?></strong></td>
                </tr>
                <tr>
                    <td>📜 Watch History:</td>
                    <td><strong><?php echo $counts['history']; ?></strong></td>
                </tr>
            </table>
        </div>

        <h3 style="margin: 30px 0 20px 0;">🔄 Reset Options:</h3>

        <!-- Option 1: Reset Episodes -->
        <div class="reset-option">
            <h4>1️⃣ Reset Episodes Only</h4>
            <p>Hapus semua episode, ID mulai dari 1. Drama tetap ada.</p>
            <form method="POST" onsubmit="return confirm('Yakin ingin reset SEMUA EPISODES?');">
                <input type="hidden" name="action" value="reset_episodes">
                <div class="form-group">
                    <label>Ketik "RESET" untuk konfirmasi:</label>
                    <input type="text" name="confirm" placeholder="RESET" required>
                </div>
                <button type="submit" class="btn btn-danger">🗑️ Reset Episodes</button>
            </form>
        </div>

        <!-- Option 2: Reset Drama -->
        <div class="reset-option">
            <h4>2️⃣ Reset Drama & Episodes</h4>
            <p>Hapus semua drama + episode, ID mulai dari 1. Users tetap ada.</p>
            <form method="POST" onsubmit="return confirm('Yakin ingin reset DRAMA & EPISODES?');">
                <input type="hidden" name="action" value="reset_drama">
                <div class="form-group">
                    <label>Ketik "RESET" untuk konfirmasi:</label>
                    <input type="text" name="confirm" placeholder="RESET" required>
                </div>
                <button type="submit" class="btn btn-danger">🗑️ Reset Drama</button>
            </form>
        </div>

        <!-- Option 3: Reset Favorites -->
        <div class="reset-option">
            <h4>3️⃣ Reset Favorites</h4>
            <p>Hapus semua favorit users. Drama & episode tetap ada.</p>
            <form method="POST" onsubmit="return confirm('Yakin ingin reset FAVORITES?');">
                <input type="hidden" name="action" value="reset_favorites">
                <div class="form-group">
                    <label>Ketik "RESET" untuk konfirmasi:</label>
                    <input type="text" name="confirm" placeholder="RESET" required>
                </div>
                <button type="submit" class="btn btn-danger">🗑️ Reset Favorites</button>
            </form>
        </div>

        <!-- Option 4: Reset History -->
        <div class="reset-option">
            <h4>4️⃣ Reset Watch History</h4>
            <p>Hapus history menonton users. Drama & episode tetap ada.</p>
            <form method="POST" onsubmit="return confirm('Yakin ingin reset WATCH HISTORY?');">
                <input type="hidden" name="action" value="reset_history">
                <div class="form-group">
                    <label>Ketik "RESET" untuk konfirmasi:</label>
                    <input type="text" name="confirm" placeholder="RESET" required>
                </div>
                <button type="submit" class="btn btn-danger">🗑️ Reset History</button>
            </form>
        </div>

        <!-- Option 5: Reset All -->
        <div class="reset-option" style="border-color: #dc3545;">
            <h4 style="color: #dc3545;">5️⃣ FULL RESET (Except Users)</h4>
            <p><strong>⚠️ DANGER:</strong> Hapus SEMUA data (drama, episode, favorit, history). Users tetap ada.</p>
            <form method="POST" onsubmit="return confirm('⚠️ YAKIN RESET SEMUA DATA? Ini tidak bisa dibatalkan!');">
                <input type="hidden" name="action" value="reset_all">
                <div class="form-group">
                    <label>Ketik "RESET" untuk konfirmasi:</label>
                    <input type="text" name="confirm" placeholder="RESET" required>
                </div>
                <button type="submit" class="btn btn-danger">💣 FULL RESET</button>
            </form>
        </div>

        <div style="margin-top: 30px; padding: 15px; background: #e7f3ff; border-radius: 5px;">
            <strong>💡 Tips:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <li>Backup database dulu: <code>mysqldump -u root bkdrama > backup.sql</code></li>
                <li>Hapus file uploads manual jika perlu: <code>uploads/videos/*</code></li>
                <li>Setelah reset, upload ulang drama & episode</li>
            </ul>
        </div>
    </div>
</body>

</html>