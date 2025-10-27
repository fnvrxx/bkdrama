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

// Get drama details
$drama_query = "SELECT * FROM drama WHERE id = ?";
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
                ➕ Tambah Episode
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
                <span>rating: <?php echo $drama['rating']; ?></span> |
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
                            <th>Durasi</th>
                            <th>Video URL</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($episodes as $episode): ?>
                            <tr>
                                <td><strong><?php echo $episode['eps_number']; ?></strong></td>
                                <td><?php echo htmlspecialchars($episode['eps_title']); ?></td>
                                <td>⏱️ <?php echo $episode['durasi']; ?> menit</td>
                                <td style="font-size: 12px; color: #666;">
                                    <?php echo htmlspecialchars(substr($episode['link_video'], 0, 30)); ?>...
                                </td>
                                <td><?php echo date('d M Y', strtotime($episode['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="../watch.php?episode=<?php echo $episode['id']; ?>" class="btn btn-success"
                                            target="_blank">Preview</a>
                                        <a href="edit-episode.php?id=<?php echo $episode['id']; ?>"
                                            class="btn btn-warning">Edit</a>
                                        <button
                                            onclick="confirmDelete(<?php echo $episode['id']; ?>, <?php echo $episode['eps_number']; ?>)"
                                            class="btn btn-danger">Delete</button>
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
        function confirmDelete(id, episodeNum) {
            if (confirm(`Hapus Episode ${episodeNum}?\n\nData episode akan dihapus permanen!`)) {
                window.location.href = `delete-episode.php?id=${id}&drama_id=<?php echo $drama_id; ?>`;
            }
        }
    </script>
</body>

</html>