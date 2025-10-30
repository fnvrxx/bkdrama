<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$episode_id = intval($_GET['id'] ?? 0);
$user_id = getUserId();

if ($episode_id <= 0) {
    header("Location: index.php");
    exit();
}

// Get episode details with drama info
// FIX: Ambil link_video dari episodes, bukan dari drama
$query = "SELECT e.*, 
                 e.link_video as video_url,
                 d.title as drama_title, 
                 d.id as id_drama, 
                 d.thumbnail as drama_thumbnail
          FROM episodes e
          JOIN drama d ON e.id_drama = d.id
          WHERE e.id = ?";

$stmt = $db->prepare($query);
$stmt->execute([$episode_id]);
$episode = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$episode) {
    header("Location: index.php");
    exit();
}

// Get watch progress for this episode
$progress_query = "SELECT progress AS watched_duration, completed AS is_completed
                   FROM users_history 
                   WHERE user_id = ? AND eps_id = ?";
$progress_stmt = $db->prepare($progress_query);
$progress_stmt->execute([$user_id, $episode_id]);
$watch_progress = $progress_stmt->fetch(PDO::FETCH_ASSOC);

$start_time = $watch_progress ? $watch_progress['watched_duration'] : 0;
$progress_percentage = 0;
if ($watch_progress && $watch_progress['watched_duration'] > 0) {
    // Calculate rough percentage (will be accurate after video loads)
    $progress_percentage = 50; // Default to show badge
}

// Get other episodes of this drama
$episodes_query = "SELECT id, eps_number, eps_title
                   FROM episodes 
                   WHERE id_drama = ? 
                   ORDER BY eps_number ASC";
$episodes_stmt = $db->prepare($episodes_query);
$episodes_stmt->execute([$episode['id_drama']]);
$all_episodes = $episodes_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($episode['eps_title']); ?> - BKDrama</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f0f0f;
            color: #fff;
        }

        .navbar {
            background: rgba(0, 0, 0, 0.95);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .navbar h1 {
            font-size: 24px;
            color: #667eea;
        }

        .navbar a {
            color: #fff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .navbar a:hover {
            background: rgba(102, 126, 234, 0.2);
        }

        .video-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .video-wrapper {
            position: relative;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        video {
            width: 100%;
            height: auto;
            display: block;
            max-height: 80vh;
        }

        .video-error {
            background: #dc3545;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px;
            text-align: center;
        }

        .video-error h3 {
            margin-bottom: 10px;
        }

        .video-info {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .video-info h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .video-info .meta {
            display: flex;
            gap: 20px;
            color: #999;
            font-size: 14px;
            margin-top: 10px;
        }

        .continue-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .episodes-list {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
        }

        .episodes-list h3 {
            margin-bottom: 15px;
            font-size: 20px;
        }

        .episode-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #fff;
        }

        .episode-item:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateX(5px);
        }

        .episode-item.active {
            background: rgba(102, 126, 234, 0.3);
            border-left: 4px solid #667eea;
        }

        .episode-item .number {
            font-weight: 600;
            font-size: 18px;
            margin-right: 15px;
            min-width: 40px;
        }

        .episode-item .title {
            flex: 1;
        }

        .episode-item .watched-icon {
            color: #28a745;
            font-size: 18px;
        }

        .save-status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(102, 126, 234, 0.9);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1000;
        }

        .save-status.show {
            opacity: 1;
        }

        .debug-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <h1>🎬 BKDrama</h1>
        <div>
            <a href="watchlist.php?id=<?php echo $episode['id_drama']; ?>">← Kembali ke Detail</a>
            <a href="dashboard.php">Dashboard</a>
        </div>
    </div>

    <div class="video-container">
        <div class="video-wrapper">
            <?php if (!empty($episode['video_url'])): ?>
                <video id="videoPlayer" controls preload="metadata">
                    <source src="<?php echo htmlspecialchars($episode['video_url']); ?>" type="video/mp4">
                    <source src="<?php echo htmlspecialchars($episode['video_url']); ?>" type="video/webm">
                    Your browser does not support the video tag.
                </video>
            <?php else: ?>
                <div class="video-error">
                    <h3>❌ Video Tidak Tersedia</h3>
                    <p>URL video tidak ditemukan untuk episode ini.</p>
                    <p style="margin-top: 10px; font-size: 14px;">
                        Episode ID: <?php echo $episode_id; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="video-info">
            <?php if ($watch_progress && $progress_percentage > 0 && !$watch_progress['is_completed']): ?>
                <div class="continue-badge">
                    📺 Melanjutkan menonton
                </div>
            <?php endif; ?>

            <h2><?php echo htmlspecialchars($episode['eps_title']); ?></h2>
            <p><?php echo htmlspecialchars($episode['drama_title']); ?></p>

            <div class="meta">
                <span>Episode <?php echo $episode['eps_number']; ?></span>
                <span id="currentTime">00:00</span>
                <span>/</span>
                <span id="duration">00:00</span>
            </div>

            <!-- Debug Info (hapus setelah testing)
            <div class="debug-info">
                <strong>Debug Info:</strong><br>
                Video URL: <?php echo htmlspecialchars($episode['video_url'] ?? 'EMPTY'); ?><br>
                Episode ID: <?php echo $episode_id; ?><br>
                Drama ID: <?php echo $episode['id_drama']; ?><br>
                Start Time: <?php echo $start_time; ?> seconds<br>
                <span id="videoStatus">Checking video...</span>
            </div> -->
        </div>
    </div>

    <div id="saveStatus" class="save-status">
        💾 Progress tersimpan
    </div>

    <script>
        const video = document.getElementById('videoPlayer');
        const currentTimeEl = document.getElementById('currentTime');
        const durationEl = document.getElementById('duration');
        const saveStatus = document.getElementById('saveStatus');
        const videoStatus = document.getElementById('videoStatus');

        const DRAMA_ID = <?php echo $episode['id_drama']; ?>;
        const EPISODE_ID = <?php echo $episode_id; ?>;
        const START_TIME = <?php echo $start_time; ?>;

        let lastSaveTime = 0;
        let saveInterval = null;
        let isMetadataLoaded = false; // ✅ Dideklarasikan di sini (global scope)

        // Format time (seconds to MM:SS)
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        // Save progress function (didefinisikan sekali)
        function saveProgress() {
            // Pastikan metadata sudah siap
            if (!isMetadataLoaded || isNaN(video?.duration) || video?.duration <= 0) {
                console.warn('Skip save: video metadata not ready or invalid duration');
                return;
            }

            const currentTime = Math.floor(video.currentTime);
            const duration = Math.floor(video.duration);

            // Hindari save berulang terlalu cepat
            if (Math.abs(currentTime - lastSaveTime) < 5) {
                return;
            }

            // Jangan simpan jika terlalu awal atau terlalu akhir (kecuali ended)
            // Catatan: untuk 'ended', kita biarkan lewat karena currentTime ≈ duration
            if (currentTime < 5 && duration - currentTime > 10) {
                return;
            }

            lastSaveTime = currentTime;

            const formData = new FormData();
            formData.append('action', 'save_progress');
            formData.append('drama_id', DRAMA_ID);
            formData.append('episode_id', EPISODE_ID);
            formData.append('watched_duration', currentTime);
            formData.append('total_duration', duration);

            fetch('api/watch-history-api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        saveStatus.classList.add('show');
                        setTimeout(() => saveStatus.classList.remove('show'), 2000);
                    } else {
                        console.error('Save failed:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error saving progress:', error);
                });
        }

        if (video) {
            video.addEventListener('loadstart', () => {
                videoStatus.textContent = 'Loading video...';
            });

            video.addEventListener('canplay', () => {
                videoStatus.textContent = 'Video ready! ✅';
                videoStatus.style.color = '#28a745';
            });

            video.addEventListener('error', (e) => {
                let errorMsg = 'Unknown error';
                if (video.error) {
                    switch (video.error.code) {
                        case 1: errorMsg = 'Video loading aborted'; break;
                        case 2: errorMsg = 'Network error'; break;
                        case 3: errorMsg = 'Video decoding failed'; break;
                        case 4: errorMsg = 'Video format not supported or file not found'; break;
                    }
                }
                videoStatus.textContent = `❌ Error: ${errorMsg}`;
                videoStatus.style.color = '#dc3545';
                alert(`Video tidak dapat diputar!\n\nError: ${errorMsg}`);
            });

            video.addEventListener('loadedmetadata', () => {
                console.log('Video metadata loaded. Duration:', video.duration);
                durationEl.textContent = formatTime(video.duration);
                isMetadataLoaded = true; // ✅ Set flag

                if (START_TIME > 0 && START_TIME < video.duration) {
                    video.currentTime = START_TIME;
                }
            });

            video.addEventListener('timeupdate', () => {
                currentTimeEl.textContent = formatTime(video.currentTime);
            });

            video.addEventListener('play', () => {
                // Mulai auto-save setiap 30 detik saat diputar
                saveInterval = setInterval(saveProgress, 30000);
            });

            video.addEventListener('pause', () => {
                clearInterval(saveInterval);
                saveProgress(); // Simpan saat pause
            });

            video.addEventListener('ended', () => {
                clearInterval(saveInterval);
                // Force set currentTime ke akhir agar completed = 1
                video.currentTime = video.duration;
                saveProgress();
            });

            window.addEventListener('beforeunload', () => {
                saveProgress();
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT') return;

                if (e.code === 'Space') {
                    e.preventDefault();
                    video[video.paused ? 'play' : 'pause']();
                } else if (e.code === 'ArrowLeft') {
                    e.preventDefault();
                    video.currentTime = Math.max(0, video.currentTime - 10);
                } else if (e.code === 'ArrowRight') {
                    e.preventDefault();
                    video.currentTime = Math.min(video.duration, video.currentTime + 10);
                } else if (e.code === 'KeyF') {
                    e.preventDefault();
                    if (video.requestFullscreen) video.requestFullscreen();
                    else if (video.webkitRequestFullscreen) video.webkitRequestFullscreen();
                }
            });
        } else {
            videoStatus.textContent = '❌ Video player not found';
        }
    </script>
</body>

</html>