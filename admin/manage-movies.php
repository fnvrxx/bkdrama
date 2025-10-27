<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole(['admin', 'superadmin']);

$database = new Database();
$db = $database->getConnection();

// Filter & Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';

// Query drama
$query = "SELECT d.*, u.username as creator_name,
          (SELECT COUNT(*) FROM episodes WHERE id_drama = d.id) as episode_count
          FROM drama d
          LEFT JOIN users u ON d.created_by = u.id
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (d.title LIKE :search OR d.deskripsi LIKE :search)";
}

if (!empty($genre_filter)) {
    $query .= " AND d.genre LIKE :genre";
}

$query .= " ORDER BY d.created_at DESC";

$stmt = $db->prepare($query);

if (!empty($search)) {
    $search_param = "%{$search}%";
    $stmt->bindParam(':search', $search_param);
}

if (!empty($genre_filter)) {
    $genre_param = "%{$genre_filter}%";
    $stmt->bindParam(':genre', $genre_param);
}

$stmt->execute();
$dramas = $stmt->fetchAll();

// Get genres
$genre_query = "SELECT DISTINCT genre FROM drama ORDER BY genre";
$genre_stmt = $db->query($genre_query);
$genres = $genre_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Drama - Admin BKDrama</title>
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

        .btn-warning {
            background: #ffc107;
            color: #000;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filters input,
        .filters select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .filters input {
            flex: 1;
            min-width: 200px;
        }

        .drama-table {
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
        <h1>🎬 BKDrama Admin</h1>
        <div class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="../dashboard.php">← Kembali ke Site</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>📺 Kelola Drama</h2>
            <a href="add-movies.php" class="btn btn-primary">➕ Tambah Drama Baru</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                if ($_GET['success'] == 'added')
                    echo '✅ Drama berhasil ditambahkan!';
                if ($_GET['success'] == 'updated')
                    echo '✅ Drama berhasil diupdate!';
                if ($_GET['success'] == 'deleted')
                    echo '✅ Drama berhasil dihapus!';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                ❌ Terjadi kesalahan! Silakan coba lagi.
            </div>
        <?php endif; ?>

        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="Cari drama..."
                value="<?php echo htmlspecialchars($search); ?>">
            <select name="genre">
                <option value="">Semua Genre</option>
                <?php foreach ($genres as $genre): ?>
                    <option value="<?php echo htmlspecialchars($genre); ?>" <?php echo $genre_filter === $genre ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($genre); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Cari</button>
            <?php if (!empty($search) || !empty($genre_filter)): ?>
                <a href="manage-movies.php" class="btn" style="background: #6c757d; color: white;">Reset</a>
            <?php endif; ?>
        </form>

        <div class="drama-table">
            <?php if (count($dramas) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Judul</th>
                            <th>Genre</th>
                            <th>Tahun</th>
                            <th>Rating</th>
                            <th>Episodes</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dramas as $drama): ?>
                            <tr>
                                <td><?php echo $drama['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($drama['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($drama['genre']); ?></td>
                                <td><?php echo $drama['rilis_tahun']; ?></td>
                                <td>⭐ <?php echo $drama['rating']; ?></td>
                                <td><?php echo $drama['episode_count']; ?> eps</td>

                                <td>
                                    <div class="actions">
                                        <a href="../watchlist.php?id=<?php echo $drama['id']; ?>" class="btn btn-primary"
                                            target="_blank" title="Preview">Preview</a>
                                        <a href="manage-episodes.php?drama_id=<?php echo $drama['id']; ?>"
                                            class="btn btn-primary" title="Kelola Episode">Add Episode</a>
                                        <a href="edit-movies.php?id=<?php echo $drama['id']; ?>" class="btn btn-warning"
                                            title="Edit Drama">Edit Drama</a>
                                        <button
                                            onclick="confirmDelete(<?php echo $drama['id']; ?>, '<?php echo htmlspecialchars($drama['title']); ?>')"
                                            class="btn btn-danger" title="Hapus Drama">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>Tidak ada drama ditemukan</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmDelete(id, title) {
            if (confirm(`Hapus drama "${title}"?\n\nSemua episode dan data terkait akan ikut terhapus!`)) {
                window.location.href = `delete-movies.php?id=${id}`;
            }
        }
    </script>
</body>

</html>