<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole(['admin', 'superadmin']);

$database = new Database();
$db = $database->getConnection();

$episode_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($episode_id <= 0) {
    header("Location: manage-movies.php");
    exit();
}

// Get episode details with drama info
$query = "SELECT e.*, d.title as drama_title, d.id as drama_id 
          FROM episodes e 
          JOIN drama d ON e.id_drama = d.id 
          WHERE e.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$episode_id]);
$episode = $stmt->fetch();

if (!$episode) {
    header("Location: manage-movies.php?error=episode_not_found");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eps_number = intval($_POST['eps_number'] ?? 0);
    $eps_title = sanitizeInput($_POST['eps_title'] ?? '');
    $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
    $durasi = intval($_POST['durasi'] ?? 0);
    $link_video = sanitizeInput($_POST['link_video'] ?? '');
    $thumbnail = sanitizeInput($_POST['thumbnail'] ?? '');

    // Validasi
    if (empty($eps_title) || $eps_number <= 0 || $durasi <= 0 || empty($link_video)) {
        $error = "Episode number, judul, durasi, dan link video harus diisi!";
    } else {
        try {
            // Cek duplicate episode number (exclude current episode)
            $check_query = "SELECT id FROM episodes WHERE id_drama = ? AND eps_number = ? AND id != ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$episode['drama_id'], $eps_number, $episode_id]);

            if ($check_stmt->rowCount() > 0) {
                $error = "Episode number {$eps_number} sudah digunakan episode lain!";
            } else {
                $update_query = "UPDATE episodes SET 
                                eps_number = :eps_number,
                                eps_title = :eps_title,
                                deskripsi = :deskripsi,
                                durasi = :durasi,
                                link_video = :link_video,
                                thumbnail = :thumbnail
                                WHERE id = :id";

                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':eps_number', $eps_number);
                $update_stmt->bindParam(':eps_title', $eps_title);
                $update_stmt->bindParam(':deskripsi', $deskripsi);
                $update_stmt->bindParam(':durasi', $durasi);
                $update_stmt->bindParam(':link_video', $link_video);
                $update_stmt->bindParam(':thumbnail', $thumbnail);
                $update_stmt->bindParam(':id', $episode_id);

                if ($update_stmt->execute()) {
                    header("Location: manage-episodes.php?drama_id={$episode['drama_id']}&success=updated");
                    exit();
                } else {
                    $error = "Gagal mengupdate episode!";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
} else {
    // Pre-fill form
    $eps_number = $episode['eps_number'];
    $eps_title = $episode['eps_title'];
    $deskripsi = $episode['deskripsi'];
    $durasi = $episode['durasi'];
    $link_video = $episode['link_video'];
    $thumbnail = $episode['thumbnail'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Episode - <?php echo htmlspecialchars($episode['drama_title']); ?></title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h2 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #666;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn {
            padding: 12px 24px;
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="navbar">
        <h1>📺 Edit Episode</h1>
        <div class="nav-links">
            <a href="manage-episodes.php?drama_id=<?php echo $episode['drama_id']; ?>">← Kembali</a>
            <a href="index.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>✏️ Edit Episode</h2>
            <p>Update episode: <strong><?php echo htmlspecialchars($episode['drama_title']); ?></strong></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="eps_number">Episode Number *</label>
                        <input type="number" id="eps_number" name="eps_number" required min="1"
                            value="<?php echo htmlspecialchars($eps_number); ?>">
                        <small>Nomor urut episode</small>
                    </div>

                    <div class="form-group">
                        <label for="durasi">Durasi (menit) *</label>
                        <input type="number" id="durasi" name="durasi" required min="1"
                            value="<?php echo htmlspecialchars($durasi); ?>">
                        <small>Durasi dalam menit</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="eps_title">Judul Episode *</label>
                    <input type="text" id="eps_title" name="eps_title" required
                        value="<?php echo htmlspecialchars($eps_title); ?>">
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi Episode</label>
                    <textarea id="deskripsi" name="deskripsi"><?php echo htmlspecialchars($deskripsi); ?></textarea>
                    <small>Opsional - jelaskan isi episode</small>
                </div>

                <div class="form-group">
                    <label for="link_video">Link Video *</label>
                    <input type="text" id="link_video" name="link_video" required
                        value="<?php echo htmlspecialchars($link_video); ?>">
                    <small>Path file video atau URL streaming</small>
                </div>

                <div class="form-group">
                    <label for="thumbnail">Thumbnail URL</label>
                    <input type="text" id="thumbnail" name="thumbnail"
                        value="<?php echo htmlspecialchars($thumbnail); ?>">
                    <small>Opsional - thumbnail preview episode</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Update Episode</button>
                    <a href="manage-episodes.php?drama_id=<?php echo $episode['drama_id']; ?>"
                        class="btn btn-secondary">❌ Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>