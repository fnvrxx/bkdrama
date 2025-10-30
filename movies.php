<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Filter
$genre_filter = isset($_GET['genre']) ? $_GET['genre'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query drama with average ratings from users
$query = "SELECT d.*,
          COUNT(DISTINCT e.id) as episode_count,
          COALESCE(AVG(r.rating), 0) as avg_rating,
          COUNT(DISTINCT r.id) as total_ratings
          FROM drama d
          LEFT JOIN episodes e ON d.id = e.id_drama
          LEFT JOIN ratings r ON d.id = r.drama_id
          WHERE 1=1";

if (!empty($genre_filter)) {
    $query .= " AND d.genre LIKE :genre";
}

if (!empty($search)) {
    $query .= " AND (d.title LIKE :search OR d.deskripsi LIKE :search)";
}

$query .= " GROUP BY d.id ORDER BY d.created_at DESC";

$stmt = $db->prepare($query);

if (!empty($genre_filter)) {
    $genre_param = "%{$genre_filter}%";
    $stmt->bindParam(':genre', $genre_param);
}

if (!empty($search)) {
    $search_param = "%{$search}%";
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();
$dramas = $stmt->fetchAll();

// Ambil semua genre
$genre_query = "SELECT DISTINCT genre FROM drama ORDER BY genre";
$genre_stmt = $db->query($genre_query);
$genres = $genre_stmt->fetchAll(PDO::FETCH_COLUMN);

// Cek drama yang sudah difavoritkan
$user_id = getUserId();
$fav_query = "SELECT drama_id FROM favorit WHERE user_id = ?";
$fav_stmt = $db->prepare($fav_query);
$fav_stmt->execute([$user_id]);
$favorited = $fav_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Drama - BKDrama</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 5px;
            color: #fff;
            font-size: 14px;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .genre-filter select {
            padding: 12px 20px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 5px;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
        }

        .btn {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #5568d3;
        }

        .drama-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
        }

        .drama-card {
            background: #1a1a1a;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .drama-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .drama-thumbnail {
            width: 100%;
            height: 320px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: rgba(255, 255, 255, 0.3);
        }

        .drama-info {
            padding: 15px;
        }

        .drama-title {
            font-size: 18px;
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
            margin-bottom: 10px;
        }

        .drama-description {
            font-size: 13px;
            color: #aaa;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .drama-actions {
            display: flex;
            gap: 10px;
        }

        .drama-actions a,
        .drama-actions button {
            flex: 1;
            padding: 10px;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-view {
            background: #667eea;
            color: white;
        }

        .btn-view:hover {
            background: #5568d3;
        }

        .btn-favorite {
            background: #2a2a2a;
            color: white;
        }

        .btn-favorite:hover {
            background: #3a3a3a;
        }

        .btn-favorite.favorited {
            background: #ffd700;
            color: #000;
        }

        .btn-trailer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.3s;
        }

        .btn-trailer:hover {
            transform: scale(1.1);
        }

        /* Trailer Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            animation: fadeIn 0.3s;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            position: relative;
            width: 90%;
            max-width: 900px;
            background: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
            animation: slideUp 0.3s;
        }

        .modal-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 20px;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .modal-video {
            width: 100%;
            background: #000;
        }

        .modal-video video {
            width: 100%;
            max-height: 500px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
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

        @media (max-width: 768px) {
            .drama-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
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
            <?php if (hasRole(['superadmin'])): ?>
                <a href="admin/">SuperAdmin Panel</a>
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
        <div class="page-header">
            <h2>Jelajahi Drama Korea</h2>
        </div>

        <form method="GET" class="filters">
            <div class="search-box">
                <input type="text" name="search" placeholder="Cari drama..."
                    value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="genre-filter">
                <select name="genre" onchange="this.form.submit()">
                    <option value="">Semua Genre</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?php echo htmlspecialchars($genre); ?>" <?php echo $genre_filter === $genre ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn">Cari</button>
            <?php if (!empty($search) || !empty($genre_filter)): ?>
                <a href="movies.php" class="btn" style="background: #555;">Reset</a>
            <?php endif; ?>
        </form>

        <?php if (count($dramas) > 0): ?>
            <div class="drama-grid">
                <?php foreach ($dramas as $drama): ?>
                    <div class="drama-card">
                        <div class="drama-thumbnail">
                            <?php if (!empty($drama['thumbnail']) && file_exists($drama['thumbnail'])): ?>
                                <img src="<?php echo htmlspecialchars($drama['thumbnail']); ?>"
                                    alt="<?php echo htmlspecialchars($drama['title']); ?>"
                                    style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                🎬
                            <?php endif; ?>
                        </div>
                        <div class="drama-info">
                            <div class="drama-title"><?php echo htmlspecialchars($drama['title']); ?></div>
                            <div class="drama-meta">
                                <span>⭐ <?php echo number_format($drama['avg_rating'], 1); ?></span>
                                <span>(<?php echo $drama['total_ratings']; ?> rating)</span>
                                <span><?php echo $drama['rilis_tahun']; ?></span>
                                <span><?php echo $drama['episode_count']; ?> Eps</span>
                            </div>
                            <div class="drama-description">
                                <?php echo htmlspecialchars($drama['deskripsi']); ?>
                            </div>
                            <div class="drama-actions">
                                <a href="watchlist.php?id=<?php echo $drama['id']; ?>" class="btn-view">
                                    Lihat
                                </a>
                                <button
                                    class="btn-favorite <?php echo in_array($drama['id'], $favorited) ? 'favorited' : ''; ?>"
                                    onclick="toggleFavorite(<?php echo $drama['id']; ?>, this)">
                                    <?php echo in_array($drama['id'], $favorited) ? '⭐' : '☆'; ?>
                                </button>
                                <?php if (!empty($drama['trailer']) && file_exists($drama['trailer'])): ?>
                                    <button class="btn-trailer"
                                        onclick="showTrailer(<?php echo $drama['id']; ?>, '<?php echo addslashes($drama['title']); ?>', '<?php echo addslashes($drama['trailer']); ?>')"
                                        title="Tonton Trailer">
                                        Trailer
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <h3>😔 Tidak ada drama ditemukan</h3>
                <p>Coba kata kunci atau filter lain</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Trailer Modal -->
    <div id="trailerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="trailerTitle">🎬 Trailer</h2>
                <button class="modal-close" onclick="closeTrailerModal()">×</button>
            </div>
            <div class="modal-video">
                <video id="trailerVideo" controls preload="metadata">
                    <source id="trailerSource" src="" type="video/mp4">
                    Browser Anda tidak mendukung video player.
                </video>
            </div>
        </div>
    </div>

    <script>
        // Trailer Modal Functions
        function showTrailer(dramaId, title, trailerPath) {
            const modal = document.getElementById('trailerModal');
            const video = document.getElementById('trailerVideo');
            const source = document.getElementById('trailerSource');
            const titleEl = document.getElementById('trailerTitle');

            titleEl.textContent = 'Trailer: ' + title;
            source.src = trailerPath;
            video.load();

            modal.classList.add('active');
            video.play();
            document.body.style.overflow = 'hidden';
        }

        function closeTrailerModal() {
            const modal = document.getElementById('trailerModal');
            const video = document.getElementById('trailerVideo');

            modal.classList.remove('active');
            video.pause();
            video.currentTime = 0;
            document.body.style.overflow = 'auto';
        }

        // Close when clicking outside
        document.getElementById('trailerModal')?.addEventListener('click', function (e) {
            if (e.target === this) {
                closeTrailerModal();
            }
        });

        // Close with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeTrailerModal();
            }
        });

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
                        button.textContent = isCurrentlyFavorited ? '☆' : '⭐';
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