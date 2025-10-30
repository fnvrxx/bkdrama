<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getUserId();

// Ambil semua drama favorit user dengan ratings
$query = "SELECT d.*, f.created_at as favorited_at,
          COUNT(DISTINCT e.id) as episode_count,
          COALESCE(AVG(r.rating), 0) as avg_rating,
          COUNT(DISTINCT r.id) as total_ratings
          FROM favorit f
          JOIN drama d ON f.drama_id = d.id
          LEFT JOIN episodes e ON d.id = e.id_drama
          LEFT JOIN ratings r ON d.id = r.drama_id
          WHERE f.user_id = :user_id
          GROUP BY d.id
          ORDER BY f.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$favorites = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drama Favorit - BKDrama</title>
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

        .page-header p {
            color: #aaa;
        }

        .favorites-list {
            display: grid;
            gap: 20px;
        }

        .favorite-item {
            background: #1a1a1a;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            gap: 20px;
            transition: background 0.3s;
        }

        .favorite-item:hover {
            background: #252525;
        }

        .favorite-thumbnail {
            width: 150px;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .favorite-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .favorite-info {
            flex: 1;
        }

        .favorite-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .favorite-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #aaa;
        }

        .favorite-description {
            color: #ccc;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .favorite-actions {
            display: flex;
            gap: 10px;
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

        .btn-view {
            background: #667eea;
            color: white;
        }

        .btn-view:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .btn-watch {
            background: #28a745;
            color: white;
        }

        .btn-watch:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-remove {
            background: #dc3545;
            color: white;
        }

        .btn-remove:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #888;
        }

        .empty-state a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .empty-state a:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .favorite-item {
                flex-direction: column;
            }

            .favorite-thumbnail {
                width: 100%;
                height: 250px;
            }

            .favorite-actions {
                flex-direction: column;
            }

            .btn {
                text-align: center;
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
    </div>

    <div class="container">
        <div class="page-header">
            <h2>Favorit Saya</h2>
            <p><?php echo count($favorites); ?> drama dalam daftar favorit</p>
        </div>

        <?php if (count($favorites) > 0): ?>
            <div class="favorites-list">
                <?php foreach ($favorites as $drama): ?>
                    <div class="favorite-item">
                        <div class="favorite-thumbnail">
                            <?php if (!empty($drama['thumbnail']) && file_exists($drama['thumbnail'])): ?>
                                <img src="<?php echo htmlspecialchars($drama['thumbnail']); ?>"
                                    alt="<?php echo htmlspecialchars($drama['title']); ?>">
                            <?php else: ?>
                                🎬
                            <?php endif; ?>
                        </div>
                        <div class="favorite-info">
                            <div class="favorite-title"><?php echo htmlspecialchars($drama['title']); ?></div>
                            <div class="favorite-meta">
                                <span>⭐ <?php echo number_format($drama['avg_rating'], 1); ?></span>
                                <span>(<?php echo $drama['total_ratings']; ?> rating)</span>
                                <span><?php echo $drama['rilis_tahun']; ?></span>
                                <span><?php echo $drama['episode_count']; ?> Episode</span>
                            </div>
                            <div class="favorite-description">
                                <?php echo htmlspecialchars($drama['deskripsi']); ?>
                            </div>
                            <div class="favorite-actions">
                                <!-- Link ke halaman watchlist untuk lihat detail drama -->
                                <a href="watchlist.php?id=<?php echo $drama['id']; ?>" class="btn btn-view">
                                    Lihat Detail
                                </a>

                                <!-- Jika ada episode, tampilkan tombol tonton -->
                                <?php if ($drama['episode_count'] > 0): ?>
                                    <?php
                                    // Get first episode
                                    $episodeQuery = "SELECT id FROM episodes WHERE id_drama = :drama_id ORDER BY eps_number LIMIT 1";
                                    $episodeStmt = $db->prepare($episodeQuery);
                                    $episodeStmt->bindParam(':drama_id', $drama['id']);
                                    $episodeStmt->execute();
                                    $firstEpisode = $episodeStmt->fetch();
                                    ?>
                                <?php endif; ?>

                                <button class="btn btn-remove" onclick="removeFavorite(<?php echo $drama['id']; ?>, this)">
                                    Hapus dari Favorit
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">OH NO</div>
                <h3>Anda Belum ada drama favorit</h3>
                <p>Mulai tambahkan drama favorit Anda dari halaman drama</p>
                <a href="movies.php">Jelajahi Drama</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function removeFavorite(dramaId, button) {
            if (!confirm('Hapus drama ini dari favorit?')) {
                return;
            }

            // Disable button sementara
            button.disabled = true;
            button.textContent = '⏳ Menghapus...';

            fetch('api/toggle-favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove&drama_id=${dramaId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Animasi hapus
                        const item = button.closest('.favorite-item');
                        item.style.opacity = '0';
                        item.style.transform = 'translateX(-20px)';

                        setTimeout(() => {
                            item.remove();

                            // Cek apakah masih ada favorit
                            const favoritesList = document.querySelector('.favorites-list');
                            if (favoritesList && favoritesList.children.length === 0) {
                                location.reload();
                            } else {
                                // Update counter
                                const counter = document.querySelector('.page-header p');
                                if (counter) {
                                    const currentCount = parseInt(counter.textContent);
                                    counter.textContent = `${currentCount - 1} drama dalam daftar favorit`;
                                }
                            }
                        }, 300);
                    } else {
                        alert(data.message || 'Terjadi kesalahan');
                        button.disabled = false;
                        button.textContent = '🗑️ Hapus dari Favorit';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus favorit');
                    button.disabled = false;
                    button.textContent = '🗑️ Hapus dari Favorit';
                });
        }
    </script>
</body>

</html>