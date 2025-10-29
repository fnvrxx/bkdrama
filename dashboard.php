<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Statistik untuk user
$stats = [
    'total_drama' => 0,
    'my_favorites' => 0,
    'watched_episodes' => 0
];

// Total drama
$drama_query = "SELECT COUNT(*) as total FROM drama";
$drama_stmt = $db->query($drama_query);
$stats['total_drama'] = $drama_stmt->fetch()['total'];

// Favorit user
$fav_query = "SELECT COUNT(*) as total FROM favorit WHERE user_id = ?";
$fav_stmt = $db->prepare($fav_query);
$fav_stmt->execute([getUserId()]);
$stats['my_favorites'] = $fav_stmt->fetch()['total'];

// Episode yang sudah ditonton
$watch_query = "SELECT COUNT(*) as total FROM users_history WHERE user_id = ?";
$watch_stmt = $db->prepare($watch_query);
$watch_stmt->execute([getUserId()]);
$stats['watched_episodes'] = $watch_stmt->fetch()['total'];

// Drama terbaru (limit 6) dengan trailer info
$recent_query = "SELECT * FROM drama ORDER BY created_at DESC LIMIT 6";
$recent_stmt = $db->query($recent_query);
$recent_dramas = $recent_stmt->fetchAll();

// Continue watching (drama yang sedang ditonton)
// FIX: Ambil eps_id dan thumbnail dari drama
$continue_query = "SELECT 
                    d.id as drama_id,
                    d.title as drama_title,
                    d.thumbnail as drama_thumbnail,
                    e.id as eps_id,
                    e.eps_number,
                    e.eps_title,
                    h.progress,
                    h.last_watched
                   FROM users_history h
                   JOIN episodes e ON h.eps_id = e.id
                   JOIN drama d ON e.id_drama = d.id
                   WHERE h.user_id = ? AND h.completed = 0
                   ORDER BY h.last_watched DESC
                   LIMIT 4";
$continue_stmt = $db->prepare($continue_query);
$continue_stmt->execute([getUserId()]);
$continue_watching = $continue_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BKDrama</title>
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
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

        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar .role-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .role-user {
            background: #4CAF50;
        }

        .role-admin {
            background: #FF9800;
        }

        .role-superadmin {
            background: #F44336;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .welcome-section {
            margin-bottom: 40px;
        }

        .welcome-section h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .welcome-section p {
            color: #aaa;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .stat-card h3 {
            font-size: 36px;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }

        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title a {
            font-size: 14px;
            color: #667eea;
            text-decoration: none;
        }

        .section-title a:hover {
            text-decoration: underline;
        }

        .drama-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .drama-card {
            background: #1a1a1a;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .drama-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .drama-thumbnail {
            width: 100%;
            height: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .drama-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .drama-info {
            padding: 15px;
        }

        .drama-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .drama-meta {
            display: flex;
            gap: 10px;
            font-size: 12px;
            color: #888;
        }

        .continue-card {
            background: #1a1a1a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
            transition: background 0.3s;
            position: relative;
        }

        .continue-card:hover {
            background: #252525;
        }

        .continue-thumbnail {
            width: 160px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
        }

        .continue-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .continue-thumbnail .play-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            background: rgba(102, 126, 234, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .continue-card:hover .play-overlay {
            opacity: 1;
        }

        .continue-info {
            flex: 1;
        }

        .continue-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 18px;
        }

        .continue-episode {
            font-size: 14px;
            color: #aaa;
            margin-bottom: 8px;
        }

        .continue-progress-text {
            font-size: 12px;
            color: #667eea;
            margin-bottom: 5px;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: #333;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-fill {
            height: 100%;
            background: #667eea;
        }

        .btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #5568d3;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }

            .navbar .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .drama-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .continue-card {
                flex-direction: column;
            }

            .continue-thumbnail {
                width: 100%;
                height: 180px;
            }
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
            <div class="user-info">
                <span class="role-badge role-<?php echo getRole(); ?>">
                    <?php echo strtoupper(getRole()); ?>
                </span>
                <span><?php echo getUsername(); ?></span>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <h2>Selamat datang, <?php echo getUsername(); ?>! 👋</h2>
            <p>Nikmati koleksi drama Korea terbaik</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total_drama']; ?></h3>
                <p>Total Drama Tersedia</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['my_favorites']; ?></h3>
                <p>Drama Favorit Saya</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['watched_episodes']; ?></h3>
                <p>Episode Ditonton</p>
            </div>
        </div>

        <!-- Continue Watching Section -->
        <?php if (count($continue_watching) > 0): ?>
            <div class="section-title">
                <h3>📺 Lanjutkan Menonton</h3>
                <a href="continue-watching.php">Lihat Semua →</a>
            </div>
            <?php foreach ($continue_watching as $item): ?>
                <div class="continue-card">
                    <div class="continue-thumbnail">
                        <?php if (!empty($item['drama_thumbnail']) && file_exists($item['drama_thumbnail'])): ?>
                            <img src="<?php echo htmlspecialchars($item['drama_thumbnail']); ?>"
                                alt="<?php echo htmlspecialchars($item['drama_title']); ?>">
                        <?php else: ?>
                            🎬
                        <?php endif; ?>
                        <div class="play-overlay">▶</div>
                    </div>
                    <div class="continue-info">
                        <div class="continue-title"><?php echo htmlspecialchars($item['drama_title']); ?></div>
                        <div class="continue-episode">
                            Episode <?php echo $item['eps_number']; ?>: <?php echo htmlspecialchars($item['eps_title']); ?>
                        </div>
                        <div class="continue-progress-text">
                            Progress: <?php echo gmdate("i:s", $item['progress']); ?>
                        </div>
                        <div class="progress-bar">
                            <?php
                            // Assume average episode is 60 minutes (3600 seconds)
                            $estimated_duration = 3600;
                            $progress_percent = min(100, ($item['progress'] / $estimated_duration) * 100);
                            ?>
                            <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                        </div>
                        <a href="watch-episode.php?id=<?php echo $item['eps_id']; ?>" class="btn">
                            ▶ Lanjutkan
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Recent Dramas Section -->
        <div class="section-title">
            <h3>🎬 Drama Terbaru</h3>
            <a href="movies.php">Lihat Semua →</a>
        </div>

        <?php if (count($recent_dramas) > 0): ?>
            <div class="drama-grid">
                <?php foreach ($recent_dramas as $drama): ?>
                    <a href="watchlist.php?id=<?php echo $drama['id']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="drama-card">
                            <div class="drama-thumbnail">
                                <?php if (!empty($drama['thumbnail']) && file_exists($drama['thumbnail'])): ?>
                                    <img src="<?php echo htmlspecialchars($drama['thumbnail']); ?>"
                                        alt="<?php echo htmlspecialchars($drama['title']); ?>">
                                <?php else: ?>
                                    🎬
                                <?php endif; ?>
                            </div>
                            <div class="drama-info">
                                <div class="drama-title"><?php echo htmlspecialchars($drama['title']); ?></div>
                                <div class="drama-meta">
                                    <span>⭐ <?php echo $drama['rating']; ?></span>
                                    <span>📅 <?php echo $drama['rilis_tahun']; ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎬</div>
                <p>Belum ada drama tersedia</p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>