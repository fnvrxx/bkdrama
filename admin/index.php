<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole(['admin', 'superadmin']);

$database = new Database();
$db = $database->getConnection();

// Statistik
$stats = [
    'total_users' => 0,
    'total_drama' => 0,
    'total_episodes' => 0,
    'total_favorites' => 0
];

// Total users
$user_query = "SELECT COUNT(*) as total FROM users";
$user_stmt = $db->query($user_query);
$stats['total_users'] = $user_stmt->fetch()['total'];

// Total drama
$drama_query = "SELECT COUNT(*) as total FROM drama";
$drama_stmt = $db->query($drama_query);
$stats['total_drama'] = $drama_stmt->fetch()['total'];

// Total episodes
$episode_query = "SELECT COUNT(*) as total FROM episodes";
$episode_stmt = $db->query($episode_query);
$stats['total_episodes'] = $episode_stmt->fetch()['total'];

// Total favorites
$fav_query = "SELECT COUNT(*) as total FROM favorit";
$fav_stmt = $db->query($fav_query);
$stats['total_favorites'] = $fav_stmt->fetch()['total'];

// Drama terbaru
$recent_drama_query = "SELECT * FROM drama ORDER BY created_at DESC LIMIT 5";
$recent_drama_stmt = $db->query($recent_drama_query);
$recent_dramas = $recent_drama_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (hasRole(['admin'])): ?>
        <title>Admin Panel - BKDrama</title>
    <?php endif; ?>
    <?php if (hasRole(['superadmin'])): ?>
        <title>superadmin Panel - BKDrama</title>
    <?php endif; ?>
    <!-- <title>Admin Panel - BKDrama</title> -->
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
            max-width: 1400px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            font-size: 36px;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #666;
            font-size: 14px;
        }

        .quick-actions {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .quick-actions h3 {
            margin-bottom: 20px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .btn {
            padding: 15px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s;
            display: block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .recent-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .recent-section h3 {
            margin-bottom: 20px;
        }

        .recent-table {
            width: 100%;
            border-collapse: collapse;
        }

        .recent-table th {
            text-align: left;
            padding: 12px;
            background: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        .recent-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }

        .recent-table tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <h1>🎬 BKDrama Admin</h1>
        <div class="nav-links">
            <a href="../dashboard.php">← Kembali ke Site</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <?php if (hasRole(['admin'])): ?>
                <h2>Dashboard Admin</h2>
            <?php endif; ?>
            <?php if (hasRole(['superadmin'])): ?>
                <h2>Dashboard SuperAdmin</h2>
            <?php endif; ?>
            <p>Selamat datang, <?php echo getUsername(); ?> (<?php echo strtoupper(getRole()); ?>)</p>
        </div>

        <div class="stats-grid">
            <?php if (hasRole(['superadmin'])): ?>
                <div class="stat-card">
                    <h3><?php echo $stats['total_users']; ?></h3>
                    <p>Total Users</p>
                </div>
            <?php endif; ?>
            <div class="stat-card">
                <h3><?php echo $stats['total_drama']; ?></h3>
                <p>Total Drama</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_episodes']; ?></h3>
                <p>Total Episodes</p>
            </div>
        </div>

        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-buttons">
                <a href="add-movies.php" class="btn btn-primary">Tambah Drama Baru</a>
                <a href="manage-movies.php" class="btn btn-success">Kelola Drama</a>
                <?php if (hasRole('superadmin')): ?>
                    <a href="manage-users.php" class="btn btn-warning">Kelola Users</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="recent-section">
            <h3>Drama Terbaru</h3>
            <?php if (count($recent_dramas) > 0): ?>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Genre</th>
                            <th>Tahun</th>
                            <th>Rating</th>
                            <th>Total Eps</th>
                            <th>Tanggal Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_dramas as $drama): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($drama['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($drama['genre']); ?></td>
                                <td><?php echo $drama['rilis_tahun']; ?></td>
                                <td>⭐ <?php echo $drama['rating']; ?></td>
                                <td><?php echo $drama['total_eps']; ?> eps</td>
                                <td><?php echo date('d M Y', strtotime($drama['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 20px;">Belum ada drama</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>