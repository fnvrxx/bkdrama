<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';

requireRole(['admin', 'superadmin']);

$database = new Database();
$db = $database->getConnection();

// Handle AJAX upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_upload'])) {
    header('Content-Type: application/json');

    $title = sanitizeInput($_POST['title'] ?? '');
    $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
    $genre = sanitizeInput($_POST['genre'] ?? '');
    $rilis_tahun = intval($_POST['rilis_tahun'] ?? date('Y'));
    $rating = floatval($_POST['rating'] ?? 0);
    $created_by = getUserId();

    // Validasi
    if (empty($title) || empty($deskripsi) || empty($genre)) {
        echo json_encode(['success' => false, 'message' => 'Judul, deskripsi, dan genre harus diisi!']);
        exit;
    }

    if ($rating < 0 || $rating > 10) {
        echo json_encode(['success' => false, 'message' => 'Rating harus antara 0-10!']);
        exit;
    }

    try {
        // Upload poster (optional)
        $thumbnail = '';
        if (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $posterUpload = uploadImage($_FILES['poster_file'], '../uploads/posters');
            if ($posterUpload['success']) {
                $thumbnail = 'uploads/posters/' . $posterUpload['filename'];
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal upload poster: ' . $posterUpload['error']]);
                exit;
            }
        }

        // Upload trailer (optional)
        $trailer = '';
        if (isset($_FILES['trailer_file']) && $_FILES['trailer_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $trailerUpload = uploadVideo($_FILES['trailer_file'], '../uploads/trailers');
            if ($trailerUpload['success']) {
                $trailer = 'uploads/trailers/' . $trailerUpload['filename'];
            } else {
                // Delete poster if trailer upload fails
                if ($thumbnail)
                    deleteFile('../' . $thumbnail);
                echo json_encode(['success' => false, 'message' => 'Gagal upload trailer: ' . $trailerUpload['error']]);
                exit;
            }
        }

        $query = "INSERT INTO drama (title, deskripsi, genre, rilis_tahun, rating, thumbnail, trailer, created_by)
                 VALUES (:title, :deskripsi, :genre, :rilis_tahun, :rating, :thumbnail, :trailer, :created_by)";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':deskripsi', $deskripsi);
        $stmt->bindParam(':genre', $genre);
        $stmt->bindParam(':rilis_tahun', $rilis_tahun);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':thumbnail', $thumbnail);
        $stmt->bindParam(':trailer', $trailer);
        $stmt->bindParam(':created_by', $created_by);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Drama berhasil ditambahkan!',
                'redirect' => 'manage-movies.php?success=added'
            ]);
        } else {
            // Delete uploaded files if insert fails
            if ($thumbnail)
                deleteFile('../' . $thumbnail);
            if ($trailer)
                deleteFile('../' . $trailer);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Drama - Admin BKDrama</title>
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

        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
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
            display: none;
        }

        .alert.show {
            display: block;
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

        /* Upload Progress Bar */
        .upload-progress {
            display: none;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #667eea;
        }

        .upload-progress.show {
            display: block;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .progress-bar-container {
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        .progress-details {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
            display: flex;
            justify-content: space-between;
        }

        .upload-stage {
            margin-top: 10px;
            padding: 10px;
            background: white;
            border-radius: 5px;
            font-size: 13px;
        }

        .upload-stage-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 0;
        }

        .upload-stage-item.active {
            color: #667eea;
            font-weight: 600;
        }

        .upload-stage-item.complete {
            color: #28a745;
        }

        .upload-stage-item .icon {
            font-size: 16px;
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
        <h1>🎬 Tambah Drama</h1>
        <div class="nav-links">
            <a href="manage-movies.php">← Kembali</a>
            <a href="index.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>➕ Tambah Drama Baru</h2>
            <p>Isi form di bawah untuk menambahkan drama baru</p>
        </div>

        <div id="alert" class="alert"></div>

        <!-- Upload Progress -->
        <div id="uploadProgress" class="upload-progress">
            <div class="progress-header">
                <span id="progressTitle">Mengupload...</span>
                <span id="progressPercent">0%</span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" id="progressBarFill">0%</div>
            </div>
            <div class="progress-details">
                <span id="uploadedSize">0 MB</span>
                <span id="uploadSpeed">0 MB/s</span>
                <span id="totalSize">0 MB</span>
            </div>
            <div class="upload-stage">
                <div class="upload-stage-item" id="stage-validate">
                    <span class="icon">⏳</span>
                    <span>Validasi file...</span>
                </div>
                <div class="upload-stage-item" id="stage-poster">
                    <span class="icon">⏳</span>
                    <span>Upload poster...</span>
                </div>
                <div class="upload-stage-item" id="stage-trailer">
                    <span class="icon">⏳</span>
                    <span>Upload trailer...</span>
                </div>
                <div class="upload-stage-item" id="stage-save">
                    <span class="icon">⏳</span>
                    <span>Menyimpan data...</span>
                </div>
            </div>
        </div>

        <div class="form-container">
            <form id="dramaForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Judul Drama *</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi / Sinopsis *</label>
                    <textarea id="deskripsi" name="deskripsi" required></textarea>
                    <small>Jelaskan sinopsis drama dengan detail</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="genre">Genre *</label>
                        <input type="text" id="genre" name="genre" required placeholder="Romance, Comedy, Drama">
                        <small>Pisahkan dengan koma untuk multiple genre</small>
                    </div>

                    <div class="form-group">
                        <label for="rilis_tahun">Tahun Rilis *</label>
                        <input type="number" id="rilis_tahun" name="rilis_tahun" min="1900"
                            max="<?php echo date('Y') + 1; ?>" value="<?php echo date('Y'); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="rating">Rating (0-10)</label>
                    <input type="number" id="rating" name="rating" step="0.1" min="0" max="10" value="0">
                    <small>Rating dari 0.0 sampai 10.0</small>
                </div>

                <div class="form-group">
                    <label for="poster_file">Upload Poster Drama (Optional) 🖼️</label>
                    <input type="file" id="poster_file" name="poster_file" accept="image/*"
                        onchange="showFileName(this, 'poster-name'); previewImage(this, 'poster-preview')">
                    <small>Maksimal 5MB - Format: JPG, PNG, WebP, GIF</small>
                    <div id="poster-name" style="margin-top: 8px; color: #667eea; font-size: 13px;"></div>
                    <img id="poster-preview"
                        style="max-width: 200px; margin-top: 10px; display: none; border-radius: 5px; border: 2px solid #667eea;">
                </div>

                <div class="form-group">
                    <label for="trailer_file">Upload Video Trailer (Optional) 🎬</label>
                    <input type="file" id="trailer_file" name="trailer_file" accept="video/*"
                        onchange="showFileName(this, 'trailer-name')">
                    <small>Maksimal 500MB - Format: MP4, WebM, MOV</small>
                    <div id="trailer-name" style="margin-top: 8px; color: #667eea; font-size: 13px;"></div>
                </div>

                <div class="form-actions">
                    <button type="submit" id="submitBtn" class="btn btn-primary">💾 Simpan Drama</button>
                    <a href="manage-movies.php" class="btn btn-secondary">❌ Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Helper functions
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

        function showAlert(message, type = 'danger') {
            const alert = document.getElementById('alert');
            alert.textContent = (type === 'success' ? '✅ ' : '❌ ') + message;
            alert.className = `alert alert-${type} show`;
            
            // Scroll to alert
            alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Auto hide after 5 seconds for success
            if (type === 'success') {
                setTimeout(() => {
                    alert.classList.remove('show');
                }, 5000);
            }
        }

        function updateStage(stageId, status) {
            const stage = document.getElementById(stageId);
            const icon = stage.querySelector('.icon');
            
            if (status === 'active') {
                stage.classList.add('active');
                stage.classList.remove('complete');
                icon.textContent = '⏳';
            } else if (status === 'complete') {
                stage.classList.remove('active');
                stage.classList.add('complete');
                icon.textContent = '✅';
            }
        }

        // Form submission with AJAX
        document.getElementById('dramaForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = document.getElementById('submitBtn');
            const uploadProgress = document.getElementById('uploadProgress');
            const progressBarFill = document.getElementById('progressBarFill');
            const progressPercent = document.getElementById('progressPercent');
            const uploadedSize = document.getElementById('uploadedSize');
            const uploadSpeed = document.getElementById('uploadSpeed');
            const totalSize = document.getElementById('totalSize');
            
            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Show upload progress
            uploadProgress.classList.add('show');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Mengupload...';
            
            // Reset stages
            updateStage('stage-validate', 'active');
            
            // Prepare form data
            const formData = new FormData(form);
            formData.append('ajax_upload', '1');
            
            // Calculate total size
            let totalBytes = 0;
            const posterFile = document.getElementById('poster_file').files[0];
            const trailerFile = document.getElementById('trailer_file').files[0];
            
            if (posterFile) totalBytes += posterFile.size;
            if (trailerFile) totalBytes += trailerFile.size;
            
            totalSize.textContent = (totalBytes / 1048576).toFixed(2) + ' MB';
            
            // Create XMLHttpRequest for progress tracking
            const xhr = new XMLHttpRequest();
            
            let startTime = Date.now();
            let lastLoaded = 0;
            
            // Upload progress
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBarFill.style.width = percentComplete + '%';
                    progressBarFill.textContent = Math.round(percentComplete) + '%';
                    progressPercent.textContent = Math.round(percentComplete) + '%';
                    
                    const mbLoaded = (e.loaded / 1048576).toFixed(2);
                    uploadedSize.textContent = mbLoaded + ' MB';
                    
                    // Calculate speed
                    const elapsed = (Date.now() - startTime) / 1000; // seconds
                    const speed = e.loaded / elapsed / 1048576; // MB/s
                    uploadSpeed.textContent = speed.toFixed(2) + ' MB/s';
                    
                    // Update stages based on progress
                    if (percentComplete < 30) {
                        updateStage('stage-validate', 'complete');
                        updateStage('stage-poster', 'active');
                    } else if (percentComplete < 90) {
                        updateStage('stage-poster', 'complete');
                        updateStage('stage-trailer', 'active');
                    } else {
                        updateStage('stage-trailer', 'complete');
                        updateStage('stage-save', 'active');
                    }
                }
            });
            
            // Upload complete
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            updateStage('stage-save', 'complete');
                            progressBarFill.style.width = '100%';
                            progressBarFill.textContent = '100%';
                            progressPercent.textContent = '100%';
                            
                            showAlert(response.message, 'success');
                            
                            // Redirect after 2 seconds
                            setTimeout(() => {
                                window.location.href = response.redirect;
                            }, 2000);
                        } else {
                            showAlert(response.message, 'danger');
                            uploadProgress.classList.remove('show');
                            submitBtn.disabled = false;
                            submitBtn.textContent = '💾 Simpan Drama';
                        }
                    } catch (e) {
                        showAlert('Invalid response from server', 'danger');
                        uploadProgress.classList.remove('show');
                        submitBtn.disabled = false;
                        submitBtn.textContent = '💾 Simpan Drama';
                    }
                } else {
                    showAlert('Server error: ' + xhr.status, 'danger');
                    uploadProgress.classList.remove('show');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '💾 Simpan Drama';
                }
            });
            
            // Upload error
            xhr.addEventListener('error', function() {
                showAlert('Network error. Please check your connection.', 'danger');
                uploadProgress.classList.remove('show');
                submitBtn.disabled = false;
                submitBtn.textContent = '💾 Simpan Drama';
            });
            
            // Upload timeout
            xhr.addEventListener('timeout', function() {
                showAlert('Upload timeout. Please try again with smaller files.', 'danger');
                uploadProgress.classList.remove('show');
                submitBtn.disabled = false;
                submitBtn.textContent = '💾 Simpan Drama';
            });
            
            // Send request
            xhr.open('POST', window.location.href);
            xhr.timeout = 600000; // 10 minutes timeout
            xhr.send(formData);
        });
    </script>
</body>

</html>