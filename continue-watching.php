<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getUserId();

// Get continue watching list
$query = "SELECT
            uh.drama_id,
            uh.episode_id,
            uh.watched_duration,
            uh.total_duration,
            uh.progress_percentage,
            uh.last_watched_at,
            d.title as drama_title,
            d.thumbnail as drama_thumbnail,
            d.genre,
            e.judul as episode_title,
            e.episode_number
          FROM users_history uh
          JOIN drama d ON uh.drama_id = d.id
          JOIN episodes e ON uh.episode_id = e.id
          WHERE uh.user_id = ?
            AND uh.is_completed = FALSE
            AND uh.progress_percentage > 0
          ORDER BY uh.last_watched_at DESC
          LIMIT 20";

$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$continue_watching = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lanjutkan Menonton - BKDrama</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .navbar {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .navbar h1 {
            font-size: 24px;
            color: #fff;
        }

        .navbar .nav-links {
            display: flex;
            gap: 15px;
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
            padding: 0 20px 40px 20px;
        }

        .page-header {
            margin-bottom: 30px;
            color: white;
        }

        .page-header h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .page-header p {
            opacity: 0.9;
        }

        .continue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .continue-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .continue-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .continue-card .thumbnail {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .continue-card .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .continue-card .thumbnail::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
        }

        .continue-card .play-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(102, 126, 234, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            z-index: 2;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .continue-card:hover .play-overlay {
            opacity: 1;
        }

        .continue-card .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            z-index: 2;
        }

        .continue-card .progress-fill {
            height: 100%;
            background: #667eea;
            transition: width 0.3s;
        }

        .continue-card .info {
            padding: 15px;
        }

        .continue-card .drama-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .continue-card .episode-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .continue-card .meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #999;
        }

        .continue-card .progress-text {
            font-weight: 600;
            color: #667eea;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #5568d3;
        }

        @media (max-width: 768px) {
            .continue-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="navbar">
        <h1>📺 Lanjutkan Menonton</h1>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="favorit.php">Favorit</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>Lanjutkan Menonton</h2>
            <p>Drama yang sedang kamu tonton</p>
        </div>

        <?php if (count($continue_watching) > 0): ?>
            <div class="continue-grid">
                <?php foreach ($continue_watching as $item): ?>
                    <a href="watch-episode.php?id=<?php echo $item['episode_id']; ?>" class="continue-card">
                        <div class="thumbnail">
                            <img src="<?php echo htmlspecialchars($item['drama_thumbnail']); ?>"
                                alt="<?php echo htmlspecialchars($item['drama_title']); ?>">

                            <div class="play-overlay">
                                ▶
                            </div>

                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $item['progress_percentage']; ?>%"></div>
                            </div>
                        </div>

                        <div class="info">
                            <div class="drama-title">
                                <?php echo htmlspecialchars($item['drama_title']); ?>
                            </div>
                            <div class="episode-title">
                                Episode <?php echo $item['episode_number']; ?>:
                                <?php echo htmlspecialchars($item['episode_title']); ?>
                            </div>
                            <div class="meta">
                                <span class="progress-text">
                                    <?php echo round($item['progress_percentage']); ?>% ditonton
                                </span>
                                <span>
                                    <?php
                                    $time_ago = time() - strtotime($item['last_watched_at']);
                                    if ($time_ago < 3600) {
                                        echo floor($time_ago / 60) . ' menit lalu';
                                    } elseif ($time_ago < 86400) {
                                        echo floor($time_ago / 3600) . ' jam lalu';
                                    } else {
                                        echo floor($time_ago / 86400) . ' hari lalu';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">📺</div>
                <h3>Belum Ada Drama yang Ditonton</h3>
                <p>Mulai menonton drama favoritmu dan lanjutkan kapan saja!</p>
                <a href="dashboard.php" class="btn">Jelajahi Drama</a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>