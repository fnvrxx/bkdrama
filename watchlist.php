<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get drama ID from URL
$drama_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($drama_id <= 0) {
    header("Location: movies.php");
    exit();
}

// Get drama details
$drama_query = "SELECT d.*, u.username as creator_name,
                (SELECT COUNT(*) FROM favorit WHERE drama_id = d.id AND user_id = ?) as is_favorited
                FROM drama d
                LEFT JOIN users u ON d.created_by = u.id
                WHERE d.id = ?";
$drama_stmt = $db->prepare($drama_query);
$drama_stmt->bindParam(1, $_SESSION['user_id']);
$drama_stmt->bindParam(2, $drama_id);
$drama_stmt->execute();
$drama = $drama_stmt->fetch();

if (!$drama) {
    header("Location: movies.php?error=drama_not_found");
    exit();
}

// Get all episodes
$episodes_query = "SELECT * FROM episodes WHERE id_drama = ? ORDER BY eps_number ASC";
$episodes_stmt = $db->prepare($episodes_query);
$episodes_stmt->execute([$drama_id]);
$episodes = $episodes_stmt->fetchAll();
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
            background: linear-gradient(135deg, #ffffff 0%, #f3e1dd 32%, #dfa2a3 86%, #ffffff 100%);
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.15);
            z-index: -1;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 24px;
            color: #dfa2a3;
            font-family: 'Brush Script MT', cursive;
        }

        .navbar .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .navbar a {
            color: #5c4b51;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .navbar a:hover {
            background: #dfa2a3;
            color: white;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header-section {
            margin-bottom: 30px;
        }

        .drama-title {
            font-size: 3rem;
            font-family: 'Brush Script MT', cursive;
            color: #5c4b51;
            margin-bottom: 10px;
        }

        .drama-meta {
            display: flex;
            gap: 20px;
            color: #5c4b51;
            font-size: 14px;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .left-column {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .poster-container {
            position: relative;
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        .poster-container img {
            width: 100%;
            display: block;
        }

        .genre-rating {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .genre {
            color: #5c4b51;
            font-weight: 600;
        }

        .stars {
            color: #dfa2a3;
            font-size: 1.2rem;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: block;
        }

        .btn-favorite {
            background: #dfa2a3;
            color: white;
        }

        .btn-favorite:hover {
            background: #c78b8c;
            transform: translateY(-2px);
        }

        .btn-favorite.favorited {
            background: #ffd700;
            color: #5c4b51;
        }

        .right-column {}

        .section-title {
            font-size: 2rem;
            font-family: 'Brush Script MT', cursive;
            color: #5c4b51;
            margin-bottom: 20px;
        }

        .trailer-section {
            margin-bottom: 40px;
        }

        .trailer-container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .trailer-container video {
            width: 100%;
            display: block;
            max-height: 500px;
        }

        .no-trailer {
            padding: 60px 20px;
            text-align: center;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            color: #5c4b51;
        }

        .episodes-section {}

        .episode-item {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-decoration: none;
            color: inherit;
        }

        .episode-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(223, 162, 163, 0.3);
            background: rgba(255, 255, 255, 1);
        }

        .episode-thumbnail {
            width: 200px;
            height: 120px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
            background: linear-gradient(135deg, #f3e1dd, #dfa2a3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .episode-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .episode-info {
            flex: 1;
        }

        .episode-title {
            font-size: 1.5rem;
            font-family: 'Brush Script MT', cursive;
            color: #5c4b51;
            margin-bottom: 8px;
        }

        .episode-desc {
            color: #5c4b51;
            line-height: 1.6;
            font-size: 14px;
        }

        .episode-duration {
            color: #888;
            font-size: 12px;
            margin-top: 8px;
        }

        .no-episodes {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            color: #5c4b51;
        }

        @media (max-width: 968px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .left-column {
                position: relative;
                top: 0;
            }

            .drama-title {
                font-size: 2rem;
            }

            .episode-item {
                flex-direction: column;
            }

            .episode-thumbnail {
                width: 100%;
                height: 200px;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-content">
            <h1>🎬 BKDrama</h1>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="movies.php">Drama</a>
                <a href="favorites.php">Favorit</a>
                <?php if (hasRole(['admin', 'superadmin'])): ?>
                    <a href="admin/">Admin</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header-section">
            <h1 class="drama-title"><?php echo htmlspecialchars($drama['title']); ?></h1>
            <div class="drama-meta">
                <span><?php echo $drama['rilis_tahun']; ?></span>
                <span><?php echo $drama['rating']; ?> ⭐</span>
                <span><?php echo count($episodes); ?> Episode</span>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Left Column: Poster & Actions -->
            <div class="left-column">
                <div class="poster-container">
                    <?php if (!empty($drama['thumbnail']) && file_exists($drama['thumbnail'])): ?>
                        <img src="<?php echo htmlspecialchars($drama['thumbnail']); ?>"
                            alt="<?php echo htmlspecialchars($drama['title']); ?>">
                    <?php else: ?>
                        <div
                            style="aspect-ratio: 2/3; background: linear-gradient(135deg, #f3e1dd, #dfa2a3); display: flex; align-items: center; justify-content: center; font-size: 5rem;">
                            🎬
                        </div>
                    <?php endif; ?>
                </div>

                <div class="genre-rating">
                    <span class="genre">Genre : <?php echo htmlspecialchars($drama['genre']); ?></span>
                    <div class="stars">
                        <!-- <?php
                        $rating = round($drama['rating']);
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $rating ? '★' : '☆';
                        }
                        ?> -->
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-favorite <?php echo $drama['is_favorited'] ? 'favorited' : ''; ?>"
                        onclick="toggleFavorite(<?php echo $drama_id; ?>, this)">
                        <?php echo $drama['is_favorited'] ? '⭐ Difavoritkan' : '☆ Tambah Favorit'; ?>
                    </button>
                </div>

                <?php if (!empty($drama['deskripsi'])): ?>
                    <div style="margin-top: 20px; padding: 15px; background: rgba(255, 255, 255, 0.9); border-radius: 8px;">
                        <h3
                            style="font-family: 'Brush Script MT', cursive; color: #5c4b51; margin-bottom: 10px; font-size: 1.5rem;">
                            Sinopsis</h3>
                        <p style="color: #5c4b51; font-size: 14px; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($drama['deskripsi'])); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Trailer & Episodes -->
            <div class="right-column">
                <!-- Trailer Section -->
                <div class="trailer-section">
                    <h2 class="section-title">Trailer</h2>
                    <?php if (!empty($drama['trailer']) && file_exists($drama['trailer'])): ?>
                        <div class="trailer-container">
                            <video controls preload="metadata">
                                <source src="<?php echo htmlspecialchars($drama['trailer']); ?>" type="video/mp4">
                                <source src="<?php echo htmlspecialchars($drama['trailer']); ?>" type="video/webm">
                                Browser Anda tidak mendukung video player.
                            </video>
                        </div>
                    <?php else: ?>
                        <div class="no-trailer">
                            <div style="font-size: 3rem; margin-bottom: 10px;">🎬</div>
                            <p>Trailer tidak tersedia untuk drama ini</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Episodes Section -->
                <div class="episodes-section">
                    <h2 class="section-title">Daftar Episode (<?php echo count($episodes); ?>)</h2>

                    <?php if (count($episodes) > 0): ?>
                        <?php foreach ($episodes as $episode): ?>
                            <a href="watch-episode.php?id=<?php echo $episode['id']; ?>" class="episode-item">
                                <div class="episode-thumbnail">
                                    <?php if (!empty($episode['thumbnail']) && file_exists($episode['thumbnail'])): ?>
                                        <img src="<?php echo htmlspecialchars($episode['thumbnail']); ?>"
                                            alt="Episode <?php echo $episode['eps_number']; ?>">
                                    <?php else: ?>
                                        <span>Ep <?php echo $episode['eps_number']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="episode-info">
                                    <h3 class="episode-title">
                                        <?php echo htmlspecialchars($drama['title']); ?> - Episode
                                        <?php echo $episode['eps_number']; ?>
                                    </h3>
                                    <p class="episode-desc">
                                        <?php
                                        $desc = $episode['deskripsi'] ?? $episode['eps_title'];
                                        echo htmlspecialchars($desc);
                                        ?>
                                    </p>
                                    <div class="episode-duration">
                                        ⏱️ <?php echo $episode['durasi']; ?> menit
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-episodes">
                            <div style="font-size: 3rem; margin-bottom: 10px;">📺</div>
                            <p>Belum ada episode tersedia untuk drama ini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleFavorite(dramaId, button) {
            const isCurrentlyFavorited = button.classList.contains('favorited');
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
                        button.classList.toggle('favorited');
                        button.innerHTML = isCurrentlyFavorited ? '☆ Tambah Favorit' : '⭐ Difavoritkan';
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