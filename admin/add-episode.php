<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';

requireRole(['admin', 'superadmin']);

$database = new Database();
$db = $database->getConnection();

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

// Get next episode number
$next_ep_query = "SELECT MAX(eps_number) as max_eps FROM episodes WHERE id_drama = ?";
$next_ep_stmt = $db->prepare($next_ep_query);
$next_ep_stmt->execute([$drama_id]);
$next_ep_result = $next_ep_stmt->fetch();
$next_episode_number = ($next_ep_result['max_eps'] ?? 0) + 1;

$error = '';
$uploadError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eps_number = intval($_POST['eps_number'] ?? 0);
    $eps_title = sanitizeInput($_POST['eps_title'] ?? '');
    $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
    $durasi = intval($_POST['durasi'] ?? 0);

    // Validasi basic
    if (empty($eps_title) || $eps_number <= 0 || $durasi <= 0) {
        $error = "Episode number, judul, dan durasi harus diisi!";
    }
    // Validasi: Video harus diupload
    elseif (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = "File video harus diupload!";
    } else {
        try {
            // Upload video
            $videoUpload = uploadVideo($_FILES['video_file'], '../uploads/videos');

            if (!$videoUpload['success']) {
                $error = "Error upload video: " . $videoUpload['message'];
            } else {
                $link_video = 'uploads/videos/' . $videoUpload['filename'];

                // Upload thumbnail (optional)
                $thumbnail = '';
                if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $thumbUpload = uploadImage($_FILES['thumbnail_file'], '../uploads/thumbnails');
                    if ($thumbUpload['success']) {
                        $thumbnail = 'uploads/thumbnails/' . $thumbUpload['filename'];
                    }
                }

                // Cek duplicate episode number
                $check_query = "SELECT id FROM episodes WHERE id_drama = ? AND eps_number = ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$drama_id, $eps_number]);

                if ($check_stmt->rowCount() > 0) {
                    $error = "Episode number {$eps_number} sudah ada!";
                    // Delete uploaded files
                    deleteFile('../' . $link_video);
                    if ($thumbnail)
                        deleteFile('../' . $thumbnail);
                } else {
                    $insert_query = "INSERT INTO episodes (id_drama, eps_number, eps_title, deskripsi, durasi, link_video, thumbnail)
                                    VALUES (:id_drama, :eps_number, :eps_title, :deskripsi, :durasi, :link_video, :thumbnail)";

                    $insert_stmt = $db->prepare($insert_query);
                    $insert_stmt->bindParam(':id_drama', $drama_id);
                    $insert_stmt->bindParam(':eps_number', $eps_number);
                    $insert_stmt->bindParam(':eps_title', $eps_title);
                    $insert_stmt->bindParam(':deskripsi', $deskripsi);
                    $insert_stmt->bindParam(':durasi', $durasi);
                    $insert_stmt->bindParam(':link_video', $link_video);
                    $insert_stmt->bindParam(':thumbnail', $thumbnail);

                    if ($insert_stmt->execute()) {
                        // Update total_eps di tabel drama
                        $update_drama = "UPDATE drama SET total_eps = (SELECT COUNT(*) FROM episodes WHERE id_drama = ?) WHERE id = ?";
                        $update_stmt = $db->prepare($update_drama);
                        $update_stmt->execute([$drama_id, $drama_id]);

                        header("Location: manage-episodes.php?drama_id={$drama_id}&success=added");
                        exit();
                    } else {
                        $error = "Gagal menambahkan episode!";
                        // Delete uploaded files
                        deleteFile('../' . $link_video);
                        if ($thumbnail)
                            deleteFile('../' . $thumbnail);
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Episode - <?php echo htmlspecialchars($drama['title']); ?></title>
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

        .drama-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
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
        <h1>📺 Tambah Episode</h1>
        <div class="nav-links">
            <a href="manage-episodes.php?drama_id=<?php echo $drama_id; ?>">← Kembali</a>
            <a href="index.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>➕ Tambah Episode Baru</h2>
            <p>Tambahkan episode untuk: <strong><?php echo htmlspecialchars($drama['title']); ?></strong></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="drama-info">
            <strong>📺 <?php echo htmlspecialchars($drama['title']); ?></strong><br>
            <small>Episode selanjutnya disarankan: #<?php echo $next_episode_number; ?></small>
        </div>

        <div class="form-container">
            <form id="upload-form" method="POST" action="" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="eps_number">Episode Number *</label>
                        <input type="number" id="eps_number" name="eps_number" required min="1"
                            value="<?php echo $next_episode_number; ?>">
                        <small>Nomor urut episode</small>
                    </div>

                    <div class="form-group">
                        <label for="durasi">Durasi (menit) *</label>
                        <input type="number" id="durasi" name="durasi" required min="1" value="60">
                        <small>Durasi dalam menit</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="eps_title">Judul Episode *</label>
                    <input type="text" id="eps_title" name="eps_title" required placeholder="Episode 1: The Beginning">
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi Episode</label>
                    <textarea id="deskripsi" name="deskripsi" placeholder="Sinopsis singkat episode ini..."></textarea>
                    <small>Opsional - jelaskan isi episode</small>
                </div>

                <div class="form-group">
                    <label for="video_file">Upload Video Episode * 🎬</label>
                    <input type="file" id="video_file" name="video_file" required accept="video/*"
                        onchange="showFileName(this, 'video-name')">
                    <small>Maksimal 500MB - Format: MP4, WebM, OGG, AVI, MKV, MOV</small>
                    <div id="video-name" style="margin-top: 8px; color: #667eea; font-size: 13px;"></div>
                </div>

                <div class="form-group">
                    <label for="thumbnail_file">Upload Thumbnail</label>
                    <input type="file" id="thumbnail_file" name="thumbnail_file" accept="image/*"
                        onchange="showFileName(this, 'thumb-name'); previewImage(this, 'thumb-preview')">
                    <small>Maksimal 5MB - Format: JPG, PNG, WebP, GIF</small>
                    <div id="thumb-name" style="margin-top: 8px; color: #667eea; font-size: 13px;"></div>
                    <img id="thumb-preview"
                        style="max-width: 200px; margin-top: 10px; display: none; border-radius: 5px; border: 2px solid #667eea;">
                </div>

                <!-- Upload progress (hidden until upload starts) -->
                <div id="upload-progress" style="display:none; margin-top:20px;">
                    <div style="background:#eef2ff; border-radius:6px; padding:6px;">
                        <div id="progress-bar"
                            style="height:14px; width:0%; background:#667eea; border-radius:4px; transition:width 0.2s;">
                        </div>
                    </div>
                    <div id="progress-text" style="margin-top:8px; font-size:13px; color:#333;">Menunggu upload...</div>
                </div>

                <div class="form-actions">
                    <button id="submit-btn" type="submit" class="btn btn-primary">💾 Simpan Episode</button>
                    <a href="manage-episodes.php?drama_id=<?php echo $drama_id; ?>" class="btn btn-secondary">❌
                        Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showFileName(input, targetId) {
            const target = document.getElementById(targetId);
            if (input.files && input.files[0]) {
                const fileSize = (input.files[0].size / 1048576).toFixed(2); // MB
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

        // Handle AJAX upload with progress bar. Falls back to normal form submit if JS disabled.
        (function () {
            const form = document.getElementById('upload-form');
            if (!form) return;

            const progressWrap = document.getElementById('upload-progress');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const submitBtn = document.getElementById('submit-btn');

            form.addEventListener('submit', function (e) {
                // Use AJAX to upload files so we can show progress
                e.preventDefault();

                // Basic client-side validation
                const videoInput = document.getElementById('video_file');
                if (!videoInput || !videoInput.files || videoInput.files.length === 0) {
                    alert('Silakan pilih file video sebelum mengirim.');
                    return;
                }

                // Build FormData
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
                    // If server redirected to manage-episodes, navigate there
                    try {
                        const respUrl = xhr.responseURL || '';
                        if (respUrl.includes('manage-episodes.php')) {
                            window.location.href = respUrl;
                            return;
                        }
                    } catch (err) {
                        // ignore
                    }

                    // If response contains known success query, redirect
                    if (xhr.responseText && xhr.responseText.indexOf('success=added') !== -1) {
                        // find URL in response text
                        const match = xhr.responseText.match(/manage-episodes\.php\?[^"'<>\s]*/);
                        if (match) {
                            window.location.href = match[0];
                            return;
                        }
                    }

                    // Otherwise show server response (simple fallback)
                    progressText.innerHTML = 'Upload selesai. Memproses...';
                    // If server returned HTML with error message, display it in an alert
                    if (xhr.status >= 200 && xhr.status < 300) {
                        // Try to extract error message from returned HTML
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(xhr.responseText, 'text/html');
                        const alertEl = doc.querySelector('.alert-danger');
                        if (alertEl) {
                            alert(alertEl.textContent.trim());
                        } else {
                            // No alert found — reload page to show server-rendered result
                            window.location.reload();
                        }
                    } else {
                        alert('Terjadi kesalahan saat mengupload. Silakan coba lagi.');
                    }
                });

                xhr.addEventListener('error', function () {
                    submitBtn.disabled = false;
                    alert('Upload gagal karena kesalahan jaringan.');
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