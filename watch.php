<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getUserId();

// Ambil drama ID atau episode ID
$drama_id = isset($_GET['drama']) ? intval($_GET['drama']) : 0;
$episode_id = isset($_GET['episode']) ? intval($_GET['episode']) : 0;

// Jika episode_id ada, ambil drama dari episode
if ($episode_id > 0) {
    $ep_query = "SELECT id_drama FROM episodes WHERE id = ?";
    $ep_stmt = $db->prepare($ep_query);
    $ep_stmt->execute([$episode_id]);
    $ep_result = $ep_stmt->fetch();
    
    if ($ep_result) {
        $drama_id = $ep_result['id_drama'];
    }
}

// Ambil detail drama
$drama_query = "SELECT d.*, u.username as creator_name,
                (SELECT COUNT(*) FROM favorit WHERE drama_id = d.id AND user_id = ?) as is_favorited
                FROM drama d
                LEFT JOIN users u ON d.created_by = u.id
                WHERE d.id = ?";
$drama_stmt = $db->prepare($drama_query);
$drama_stmt->execute([$user_id, $drama_id]);
$drama = $drama_stmt->fetch();

if (!$drama) {
    header("Location: movies.php");
    exit();
}

// Ambil semua episode
$episodes_query = "SELECT * FROM episodes WHERE id_drama = ? ORDER BY eps_number ASC";
$episodes_stmt = $db->prepare($episodes_query);
$episodes_stmt->execute([$drama_id]);
$episodes = $episodes_stmt->fetchAll();

// Episode yang sedang diputar
$current_episode = null;
if ($episode_id > 0) {
    foreach ($episodes as $ep) {
        if ($ep['id'] == $episode_id) {
            $current_episode = $ep;
            break;
        }
    }
}

// Jika tidak ada episode spesifik, ambil episode pertama
if (!$current_episode && count($episodes) > 0) {
    $current_episode = $episodes[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($drama['title']); ?> - BKDrama</title>
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
            background: #1a1a1a;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar h1 {
            font-size: 24px;
            color: #667eea;
        }

        .navbar .nav-links {
            display: flex;
            gap: 20px;
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
            background: rgba(102, 126, 234, 0.3);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .video-section {
            margin-bottom: 30px;
        }

        .video-player {
            width: 100%;
            height: 600px;
            background: #000;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            margin-bottom: 20px;
        }

        .video-info {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 8px;
        }

        .video-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .video-meta {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .drama-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .drama-details {
            background: #1a1a1a;
            padding: 25px;
            border-radius: 8px;
        }

        .drama-header {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .drama-poster {
            width: 150px;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            flex-shrink: 0;
        }

        .drama-info h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .drama-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #aaa;
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

        .btn-favorite {
            background: #ffd700;
            color: #000;
        }

        .btn-favorite.not-favorited {
            background: #2a2a2a;
            color: #fff;
        }

        .drama-description {
            color: #ccc;
            line-height: 1.6;
        }

        .episodes-list {
            background: #1a1a1a;
            padding: 25px;
            border-radius: 8px;
            max-height: 600px;
            overflow-y: auto;
        }

        .episodes-list h3 {
            margin-bottom: 20px;
        }

        .episode-item {
            background: #252525;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .episode-item:hover {
            background: #2f2f2f;
        }

        .episode-item.active {
            background: #667eea;
        }

        .episode-info {
            flex: 1;
        }

        .episode-number {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .episode-title {
            font-size: 14px;
            color: #aaa;
        }

        .episode-item.active .episode-title {
            color: #fff;
        }

        .episode-duration {
            font-size: 12px;
            color: #888;
        }

        @media (max-width: 968px) {
            .drama-section {
                grid-template-columns: 1fr;
            }

            .video-player {
                height: 400px;
            }

            .drama-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #1a1a1a;
        }

        ::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🎬 BKDrama</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="movies.php">Drama</a>
            <a href="favorites.php">Favorit</a>
            <?php if (hasRole(['admin', 'superadmin'])): ?>
                <a href="admin/">Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($current_episode): ?>
        <div class="video-section">
            <div class="video-player">
                🎬
                <p style="font-size: 16px; color: #666;">Video Player</p>
            </div>
            <div class="video-info">
                <div class="video-title">
                    Episode <?php echo $current_episode['eps_number']; ?>: 
                    <?php echo htmlspecialchars($current_episode['eps_title']); ?>
                </div>
                <div class="video-meta">
                    ⏱️ Durasi: <?php echo $current_episode['durasi']; ?> menit
                    <?php if ($current_episode['deskripsi']): ?>
                        | 📝 <?php echo htmlspecialchars($current_episode['deskripsi']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="drama-section">
            <div class="drama-details">
                <div class="drama-header">
                    <div class="drama-poster">🎬</div>
                    <div class="drama-info">
                        <h2><?php echo htmlspecialchars($drama['title']); ?></h2>
                        <div class="drama-meta">
                            <span>⭐ <?php echo $drama['rating']; ?></span>
                            <span>📅 <?php echo $drama['rilis_tahun']; ?></span>
                            <span>🎭 <?php echo htmlspecialchars($drama['genre']); ?></span>
                            <span>📺 <?php echo count($episodes); ?> Episode</span>
                        </div>
                        <button class="btn btn-favorite <?php echo $drama['is_favorited'] ? '' : 'not-favorited'; ?>"
                                onclick="toggleFavorite(<?php echo $drama_id; ?>, this)">
                            <?php echo $drama['is_favorited'] ? '⭐ Favorit' : '☆ Tambah Favorit'; ?>
                        </button>
                    </div>
                </div>
                <div class="drama-description">
                    <h3 style="margin-bottom: 10px;">📖 Sinopsis</h3>
                    <p><?php echo nl2br(htmlspecialchars($drama['deskripsi'])); ?></p>
                </div>
            </div>

            <div class="episodes-list">
                <h3>📺 Daftar Episode (<?php echo count($episodes); ?>)</h3>
                <?php if (count($episodes) > 0): ?>
                    <?php foreach ($episodes as $ep): ?>
                        <div class="episode-item <?php echo ($current_episode && $ep['id'] == $current_episode['id']) ? 'active' : ''; ?>"
                             onclick="window.location.href='watch.php?episode=<?php echo $ep['id']; ?>'">
                            <div class="episode-info">
                                <div class="episode-number">Episode <?php echo $ep['eps_number']; ?></div>
                                <div class="episode-title"><?php echo htmlspecialchars($ep['eps_title']); ?></div>
                            </div>
                            <div class="episode-duration">⏱️ <?php echo $ep['durasi']; ?> min</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 20px;">Belum ada episode tersedia</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleFavorite(dramaId, button) {
            const isCurrentlyFavorited = !button.classList.contains('not-favorited');
            const action = isCurrentlyFavorited ? 'remove' : 'add';

            fetch('api/toggle-favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&drama_id=${dramaId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.classList.toggle('not-favorited');
                    button.textContent = isCurrentlyFavorited ? '☆ Tambah Favorit' : '⭐ Favorit';
                } else {
                    alert(data.message || 'Terjadi kesalahan');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengupdate favorit');
            });
        }
    </script>
</body>
</html>