<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';

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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eps_number = intval($_POST['eps_number'] ?? 0);
    $eps_title = sanitizeInput($_POST['eps_title'] ?? '');
    $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
    $durasi = intval($_POST['durasi'] ?? 0);

    // Keep old values
    $link_video = $episode['link_video'];
    $thumbnail = $episode['thumbnail'];

    // Validasi
    if (empty($eps_title) || $eps_number <= 0 || $durasi <= 0) {
        $error = "Episode number, judul, dan durasi harus diisi!";
    } else {
        try {
            // Upload new video if provided
            if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $videoUpload = uploadVideo($_FILES['video_file'], '../uploads/videos');

                if ($videoUpload['success']) {
                    // Delete old video
                    if ($link_video && file_exists('../' . $link_video)) {
                        deleteFile('../' . $link_video);
                    }
                    // Set new video
                    $link_video = 'uploads/videos/' . $videoUpload['filename'];
                } else {
                    $error = "Gagal upload video: " . $videoUpload['error'];
                }
            }

            // Upload new thumbnail if provided
            if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $thumbUpload = uploadImage($_FILES['thumbnail_file'], '../uploads/thumbnails');

                if ($thumbUpload['success']) {
                    // Delete old thumbnail
                    if ($thumbnail && file_exists('../' . $thumbnail)) {
                        deleteFile('../' . $thumbnail);
                    }
                    // Set new thumbnail
                    $thumbnail = 'uploads/thumbnails/' . $thumbUpload['filename'];
                } else {
                    $error = "Gagal upload thumbnail: " . $thumbUpload['error'];
                }
            }

            // Only proceed if no upload errors
            if (empty($error)) {
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
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Pre-fill form values
$eps_number = $episode['eps_number'];
$eps_title = $episode['eps_title'];
$deskripsi = $episode['deskripsi'];
$durasi = $episode['durasi'];
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

        .current-file {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .current-file-icon {
            font-size: 24px;
        }

        .current-file-info {
            flex: 1;
        }

        .current-file-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }

        .current-file-path {
            font-size: 12px;
            color: #666;
        }

        .current-thumbnail {
            max-width: 200px;
            margin-top: 10px;
            border-radius: 5px;
            border: 2px solid #667eea;
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

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .upload-progress {
            display: none;
            margin-top: 20px;
        }

        .progress-wrap {
            background: #eef2ff;
            border-radius: 6px;
            padding: 6px;
        }

        .progress-bar {
            height: 14px;
            width: 0%;
            background: #667eea;
            border-radius: 4px;
            transition: width 0.2s;
        }

        .progress-text {
            margin-top: 8px;
            font-size: 13px;
            color: #333;
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

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="" id="upload-form" enctype="multipart/form-data">
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

                <!-- VIDEO UPLOAD -->
                <div class="form-group">
                    <label for="video_file">Upload Video Episode 🎬</label>

                    <?php if (!empty($episode['link_video'])): ?>
                        <div class="current-file">
                            <div class="current-file-icon">🎬</div>
                            <div class="current-file-info">
                                <div class="current-file-name">
                                    <?php
                                    $video_name = basename($episode['link_video']);
                                    echo htmlspecialchars($video_name);
                                    ?>
                                </div>
                                <div class="current-file-path">
                                    <?php echo htmlspecialchars($episode['link_video']); ?>
                                    <?php if (file_exists('../' . $episode['link_video'])): ?>
                                        <span style="color: #28a745;">✓ File exists</span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;">✗ File not found</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="current-file" style="background: #fff3cd; border: 1px solid #ffc107;">
                            <div class="current-file-icon">⚠️</div>
                            <div class="current-file-info">
                                <div class="current-file-name" style="color: #856404;">Tidak ada video</div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <input type="file" id="video_file" name="video_file" accept="video/*"
                        onchange="showFileName(this, 'video-name')">
                    <small>Opsional - Upload video baru jika ingin mengganti. Maksimal 500MB - Format: MP4, WebM, OGG,
                        AVI, MKV, MOV</small>
                    <div id="video-name" style="margin-top: 8px; color: #667eea; font-size: 13px;"></div>
                </div>

                <!-- THUMBNAIL UPLOAD -->
                <div class="form-group">
                    <label for="thumbnail_file">Upload Thumbnail 🖼️</label>

                    <?php if (!empty($episode['thumbnail'])): ?>
                        <div class="current-file">
                            <div class="current-file-icon">🖼️</div>
                            <div class="current-file-info">
                                <div class="current-file-name">
                                    <?php
                                    $thumb_name = basename($episode['thumbnail']);
                                    echo htmlspecialchars($thumb_name);
                                    ?>
                                </div>
                                <div class="current-file-path">
                                    <?php echo htmlspecialchars($episode['thumbnail']); ?>
                                    <?php if (file_exists('../' . $episode['thumbnail'])): ?>
                                        <span style="color: #28a745;">✓ File exists</span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;">✗ File not found</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if (file_exists('../' . $episode['thumbnail'])): ?>
                            <img src="../<?php echo htmlspecialchars($episode['thumbnail']); ?>" alt="Current thumbnail"
                                class="current-thumbnail">
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="current-file" style="background: #fff3cd; border: 1px solid #ffc107;">
                            <div class="current-file-icon">⚠️</div>
                            <div class="current-file-info">
                                <div class="current-file-name" style="color: #856404;">Tidak ada thumbnail</div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <input type="file" id="thumbnail_file" name="thumbnail_file" accept="image/*"
                        onchange="showFileName(this, 'thumb-name'); previewImage(this, 'thumb-preview')">
                    <small>Opsional - Upload thumbnail baru jika ingin mengganti. Maksimal 5MB - Format: JPG, PNG, WebP,
                        GIF</small>
                    <div id="thumb-name" style="margin-top: 8px; color: #667eea; font-size: 13px;"></div>
                    <img id="thumb-preview" class="current-thumbnail" style="display: none;">
                </div>

                <!-- Upload progress -->
                <div class="upload-progress" id="upload-progress">
                    <div class="progress-wrap">
                        <div class="progress-bar" id="progress-bar"></div>
                    </div>
                    <div class="progress-text" id="progress-text">Menunggu upload...</div>
                </div>

                <div class="form-actions">
                    <button id="submit-btn" type="submit" class="btn btn-primary">💾 Update Episode</button>
                    <a href="manage-episodes.php?drama_id=<?php echo $episode['drama_id']; ?>"
                        class="btn btn-secondary">❌ Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showFileName(input, targetId) {
            const target = document.getElementById(targetId);
            if (input.files && input.files[0]) {
                const fileSize = (input.files[0].size / 1048576).toFixed(2); // MB
                target.textContent = `📁 File baru: ${input.files[0].name} (${fileSize} MB)`;
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

        // Handle AJAX upload with progress bar
        (function () {
            const form = document.getElementById('upload-form');
            if (!form) return;

            const progressWrap = document.getElementById('upload-progress');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const submitBtn = document.getElementById('submit-btn');

            form.addEventListener('submit', function (e) {
                // Check if any file is being uploaded
                const videoInput = document.getElementById('video_file');
                const thumbInput = document.getElementById('thumbnail_file');

                const hasVideo = videoInput && videoInput.files && videoInput.files.length > 0;
                const hasThumb = thumbInput && thumbInput.files && thumbInput.files.length > 0;

                // If no files are being uploaded, use normal form submit
                if (!hasVideo && !hasThumb) {
                    return true; // Allow normal form submission
                }

                // Use AJAX for file uploads
                e.preventDefault();

                const fd = new FormData(form);
                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action || window.location.href, true);

                xhr.upload.addEventListener('progress', function (ev) {
                    if (ev.lengthComputable) {
                        const percent = Math.round((ev.loaded / ev.total) * 100);
                        progressWrap.style.display = 'block';
                        progressBar.style.width = percent + '%';
                        progressText.textContent = `Uploading: ${percent}% (${Math.round(ev.loaded / 1048576)} / ${Math.round(ev.total / 1048576)} MB)`;
                    }
                });

                xhr.addEventListener('load', function () {
                    submitBtn.disabled = false;

                    // Check if redirect happened
                    try {
                        const respUrl = xhr.responseURL || '';
                        if (respUrl.includes('manage-episodes.php')) {
                            window.location.href = respUrl;
                            return;
                        }
                    } catch (err) {
                        // ignore
                    }

                    // Check for success in response
                    if (xhr.responseText && xhr.responseText.indexOf('success=updated') !== -1) {
                        const match = xhr.responseText.match(/manage-episodes\.php\?[^"'<>\s]*/);
                        if (match) {
                            window.location.href = match[0];
                            return;
                        }
                    }

                    progressText.innerHTML = 'Upload selesai. Memproses...';

                    // Check for errors
                    if (xhr.status >= 200 && xhr.status < 300) {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(xhr.responseText, 'text/html');
                        const alertEl = doc.querySelector('.alert-danger');
                        if (alertEl) {
                            alert(alertEl.textContent.trim());
                            progressWrap.style.display = 'none';
                        } else {
                            // Success - reload to show updated data
                            window.location.reload();
                        }
                    } else {
                        alert('Terjadi kesalahan saat mengupload. Silakan coba lagi.');
                        progressWrap.style.display = 'none';
                    }
                });

                xhr.addEventListener('error', function () {
                    submitBtn.disabled = false;
                    alert('Upload gagal karena kesalahan jaringan.');
                    progressWrap.style.display = 'none';
                });

                // Disable button to prevent double submits
                submitBtn.disabled = true;
                progressWrap.style.display = 'block';
                progressBar.style.width = '0%';
                progressText.textContent = 'Memulai upload...';

                xhr.send(fd);
            });
        })();
    </script>
</body>

</html>