<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';

requireRole(['admin', 'superadmin']);

$database = new Database();
$db = $database->getConnection();

$drama_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($drama_id <= 0) {
    header("Location: manage-movies.php");
    exit();
}

// Ambil data drama
$query = "SELECT * FROM drama WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$drama_id]);
$drama = $stmt->fetch();

if (!$drama) {
    header("Location: manage-movies.php?error=not_found");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
    $genre = sanitizeInput($_POST['genre'] ?? '');
    $rilis_tahun = intval($_POST['rilis_tahun'] ?? date('Y'));
    // Rating tidak lagi diubah dari admin, diisi oleh user

    // Keep old values
    $thumbnail = $drama['thumbnail'];
    $trailer = $drama['trailer'];

    // Validasi
    if (empty($title) || empty($deskripsi) || empty($genre)) {
        $error = "Judul, deskripsi, dan genre harus diisi!";
    } else {
        try {
            // Upload new poster if provided
            if (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $posterUpload = uploadImage($_FILES['poster_file'], '../uploads/posters');

                if ($posterUpload['success']) {
                    // Delete old poster
                    if ($thumbnail)
                        deleteFile('../' . $thumbnail);
                    // Set new poster
                    $thumbnail = 'uploads/posters/' . $posterUpload['filename'];
                }
            }

            // Upload new trailer if provided
            if (isset($_FILES['trailer_file']) && $_FILES['trailer_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $trailerUpload = uploadVideo($_FILES['trailer_file'], '../uploads/trailers');

                if ($trailerUpload['success']) {
                    // Delete old trailer
                    if ($trailer)
                        deleteFile('../' . $trailer);
                    // Set new trailer
                    $trailer = 'uploads/trailers/' . $trailerUpload['filename'];
                }
            }

            $update_query = "UPDATE drama SET
                            title = :title,
                            deskripsi = :deskripsi,
                            genre = :genre,
                            rilis_tahun = :rilis_tahun,
                            thumbnail = :thumbnail,
                            trailer = :trailer
                            WHERE id = :id";

            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':title', $title);
            $update_stmt->bindParam(':deskripsi', $deskripsi);
            $update_stmt->bindParam(':genre', $genre);
            $update_stmt->bindParam(':rilis_tahun', $rilis_tahun);
            $update_stmt->bindParam(':thumbnail', $thumbnail);
            $update_stmt->bindParam(':trailer', $trailer);
            $update_stmt->bindParam(':id', $drama_id);

            if ($update_stmt->execute()) {
                header("Location: manage-movies.php?success=updated");
                exit();
            } else {
                $error = "Gagal mengupdate drama!";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
} else {
    // Pre-fill form dengan data yang ada
    $title = $drama['title'];
    $deskripsi = $drama['deskripsi'];
    $genre = $drama['genre'];
    $rilis_tahun = $drama['rilis_tahun'];
    // Rating tidak ditampilkan di form, diisi oleh user
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Drama - Admin BKDrama</title>
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
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

        .current-file {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 13px;
            color: #666;
        }

        .current-file img,
        .current-file video {
            max-width: 200px;
            margin-top: 10px;
            border-radius: 5px;
            border: 2px solid #ddd;
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
        <h1>🎬 Edit Drama</h1>
        <div class="nav-links">
            <a href="manage-movies.php">← Kembali</a>
            <a href="index.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>✏️ Edit Drama</h2>
            <p>Update informasi drama: <?php echo htmlspecialchars($drama['title']); ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Judul Drama *</label>
                    <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($title); ?>">
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi / Sinopsis *</label>
                    <textarea id="deskripsi" name="deskripsi"
                        required><?php echo htmlspecialchars($deskripsi); ?></textarea>
                    <small>Jelaskan sinopsis drama dengan detail</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="genre">Genre *</label>
                        <input type="text" id="genre" name="genre" required
                            value="<?php echo htmlspecialchars($genre); ?>" placeholder="Romance, Comedy, Drama">
                        <small>Pisahkan dengan koma untuk multiple genre</small>
                    </div>

                    <div class="form-group">
                        <label for="rilis_tahun">Tahun Rilis *</label>
                        <input type="number" id="rilis_tahun" name="rilis_tahun" min="1900"
                            max="<?php echo date('Y') + 1; ?>" value="<?php echo htmlspecialchars($rilis_tahun); ?>"
                            required>
                    </div>
                </div>

                <!-- Rating dihapus - akan diisi oleh user melalui sistem rating -->

                <div class="form-group">
                    <label for="poster_file">Upload Poster Baru (Optional) 🖼️</label>
                    <input type="file" id="poster_file" name="poster_file" accept="image/*"
                        onchange="showFileName(this, 'poster-name'); previewImage(this, 'poster-preview')">
                    <small>Kosongkan jika tidak ingin mengganti poster. Max 5MB</small>
                    <div id="poster-name" style="margin-top: 8px; color: #667eea; font-size: 13px;"></div>

                    <?php if (!empty($drama['thumbnail']) && file_exists('../' . $drama['thumbnail'])): ?>
                        <div class="current-file">
                            <strong>📁 Poster saat ini:</strong><br>
                            <?php echo basename($drama['thumbnail']); ?>
                            <img src="../<?php echo htmlspecialchars($drama['thumbnail']); ?>" alt="Current poster">
                        </div>
                    <?php endif; ?>
                    <img id="poster-preview"
                        style="max-width: 200px; margin-top: 10px; display: none; border-radius: 5px; border: 2px solid #667eea;">
                </div>

                <div class="form-group">
                    <label for="trailer_file">Upload Video Trailer Baru (Optional) 🎬</label>
                    <input type="file" id="trailer_file" name="trailer_file" accept="video/*"
                        onchange="showFileName(this, 'trailer-name')">
                    <small>Kosongkan jika tidak ingin mengganti trailer. Max 500MB</small>
                    <div id="trailer-name" style="margin-top: 8px; color: #667eea; font-size: 13px;"></div>

                    <?php if (!empty($drama['trailer']) && file_exists('../' . $drama['trailer'])): ?>
                        <div class="current-file">
                            <strong>🎬 Trailer saat ini:</strong><br>
                            <?php echo basename($drama['trailer']); ?>
                            <video controls style="max-width: 300px; margin-top: 10px;">
                                <source src="../<?php echo htmlspecialchars($drama['trailer']); ?>" type="video/mp4">
                            </video>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Update Drama</button>
                    <a href="manage-movies.php" class="btn btn-secondary">❌ Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showFileName(input, targetId) {
            const target = document.getElementById(targetId);
            if (input.files && input.files[0]) {
                const fileSize = (input.files[0].size / 1048576).toFixed(2);
                target.textContent = `📁 ${input.files[0].name} (${fileSize} MB)`;
            }
        }

        function previewImage(input, imgId) {
            const img = document.getElementById(imgId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    img.src = e.target.result;
                    img.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>

</html>