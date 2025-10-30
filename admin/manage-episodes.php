<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole(['admin', 'superadmin']);

$database = new Database();
$db = $database->getConnection();

// Get drama_id from URL
$drama_id = isset($_GET['drama_id']) ? intval($_GET['drama_id']) : 0;

if ($drama_id <= 0) {
    header("Location: manage-movies.php?error=invalid_drama");
    exit();
}

// Get drama details dengan ratings dari user
$drama_query = "SELECT d.*,
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(DISTINCT r.id) as total_ratings
                FROM drama d
                LEFT JOIN ratings r ON d.id = r.drama_id
                WHERE d.id = ?
                GROUP BY d.id";
$drama_stmt = $db->prepare($drama_query);
$drama_stmt->execute([$drama_id]);
$drama = $drama_stmt->fetch();

if (!$drama) {
    header("Location: manage-movies.php?error=drama_not_found");
    exit();
}

// Get all episodes for this drama
$episodes_query = "SELECT * FROM episodes WHERE id_drama = ? ORDER BY eps_number ASC";
$episodes_stmt = $db->prepare($episodes_query);
$episodes_stmt->execute([$drama_id]);
$episodes = $episodes_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Episode - <?php echo htmlspecialchars($drama['title']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .navbar {
            background: #667eea;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar h1 {
            font-size: 24px;
            color: #fff;
        }

        .navbar .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .navbar a {
            color: #fff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .navbar a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h2 {
            font-size: 28px;
        }

        .drama-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .drama-info h3 {
            margin-bottom: 10px;
            color: #667eea;
        }

        .drama-meta {
            color: #666;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .episodes-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        .actions a,
        .actions button {
            padding: 6px 12px;
            font-size: 12px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <h1>Kelola Episode</h1>
        <div class="nav-links">
            <a href="manage-movies.php">← Kembali ke Drama</a>
            <a href="index.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>📺 Episode: <?php echo htmlspecialchars($drama['title']); ?></h2>
            <a href="add-episode.php?drama_id=<?php echo $drama_id; ?>" class="btn btn-primary">
                Tambah Episode
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                if ($_GET['success'] == 'added')
                    echo '✅ Episode berhasil ditambahkan!';
                if ($_GET['success'] == 'updated')
                    echo '✅ Episode berhasil diupdate!';
                if ($_GET['success'] == 'deleted')
                    echo '✅ Episode berhasil dihapus!';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                ❌ Terjadi kesalahan! Silakan coba lagi.
            </div>
        <?php endif; ?>

        <div class="drama-info">
            <h3><?php echo htmlspecialchars($drama['title']); ?></h3>
            <div class="drama-meta">
                <span>Genre: <?php echo htmlspecialchars($drama['genre']); ?></span> |
                <span>Rilis: <?php echo $drama['rilis_tahun']; ?></span> |
                <span>⭐ Rating: <?php echo number_format($drama['avg_rating'], 1); ?>
                    (<?php echo $drama['total_ratings']; ?> user)</span> |
                <span>Episode: <?php echo count($episodes); ?> Episode</span>
            </div>
        </div>

        <div class="episodes-table">
            <?php if (count($episodes) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Eps #</th>
                            <th>Judul Episode</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($episodes as $episode): ?>
                            <tr>
                                <td><strong><?php echo $episode['eps_number']; ?></strong></td>
                                <td><?php echo htmlspecialchars($episode['eps_title']); ?></td>
                                <td><?php echo date('d M Y', strtotime($episode['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="../watch-episode.php?id=<?php echo $episode['id']; ?>" class="btn btn-success"
                                            target="_blank">Preview</a>
                                        <a href="edit-episode.php?id=<?php echo $episode['id']; ?>"
                                            class="btn btn-warning">Edit</a>
                                        <button
                                            onclick="confirmDelete(<?php echo $episode['id']; ?>, <?php echo $episode['eps_number']; ?>, '<?php echo htmlspecialchars(addslashes($episode['eps_title'])); ?>')"
                                            class="btn btn-danger" title="Hapus episode">
                                            🗑️ Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>😔 Belum ada episode untuk drama ini</p>
                    <a href="add-episode.php?drama_id=<?php echo $drama_id; ?>" class="btn btn-primary"
                        style="margin-top: 20px;">
                        ➕ Tambah Episode Pertama
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        /**
               * Confirm and delete episode
               */
        function confirmDelete(episodeId, episodeNum, episodeTitle) {
            console.log('Delete requested for:', { episodeId, episodeNum, episodeTitle });

            // Validate ID
            if (!episodeId || episodeId <= 0) {
                alert('Error: Invalid episode ID');
                return;
            }

            // Confirm deletion
            const confirmMsg = `Hapus Episode ${episodeNum}?\n\n` +
                `Judul: ${episodeTitle}\n\n` +
                `PERINGATAN:\n` +
                `• Data episode akan dihapus permanen\n` +
                `• File video akan dihapus dari server\n` +
                `• Watch history pengguna akan dihapus\n` +
                `• Tindakan ini TIDAK DAPAT dibatalkan!\n\n` +
                `Lanjutkan?`;

            if (confirm(confirmMsg)) {
                console.log('User confirmed deletion');

                // Disable all buttons to prevent double-click
                const allButtons = document.querySelectorAll('button');
                allButtons.forEach(btn => {
                    btn.disabled = true;
                    btn.style.opacity = '0.6';
                });

                // Show loading message
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '⏳ Menghapus...';

                // Redirect to delete handler
                console.log('Redirecting to delete-episode.php?id=' + episodeId);
                window.location.href = 'delete-episode.php?id=' + episodeId;
            } else {
                console.log('User cancelled deletion');
            }
        }

        // Show alert on page load if there's a message
        window.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);

            // Auto-hide success messages after 5 seconds
            if (urlParams.get('success')) {
                setTimeout(function () {
                    const alert = document.querySelector('.alert-success');
                    if (alert) {
                        alert.style.transition = 'opacity 0.5s';
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 500);
                    }
                }, 5000);
            }
        });
    </script>
</body>

</html>