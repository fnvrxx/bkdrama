<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole('superadmin'); // Only superadmin

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $new_role_id = intval($_POST['role_id'] ?? 0);
    
    if ($user_id > 0 && $new_role_id > 0) {
        // Prevent changing own role
        if ($user_id == getUserId()) {
            $error = "Tidak bisa mengubah role sendiri!";
        } else {
            $update_query = "UPDATE users SET role_id = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            
            if ($update_stmt->execute([$new_role_id, $user_id])) {
                $success = "Role berhasil diupdate!";
            } else {
                $error = "Gagal mengupdate role!";
            }
        }
    }
}

// Get all users with their roles
$query = "SELECT u.*, r.name as role_name 
          FROM users u 
          JOIN roles r ON u.role_id = r.id 
          ORDER BY u.created_at DESC";
$stmt = $db->query($query);
$users = $stmt->fetchAll();

// Get all roles
$roles_query = "SELECT * FROM roles ORDER BY id";
$roles_stmt = $db->query($roles_query);
$roles = $roles_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users - SuperAdmin BKDrama</title>
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            background: rgba(255,255,255,0.2);
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

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .users-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .role-user {
            background: #d4edda;
            color: #155724;
        }

        .role-admin {
            background: #fff3cd;
            color: #856404;
        }

        .role-superadmin {
            background: #f8d7da;
            color: #721c24;
        }

        .role-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .current-user {
            background: #e7f3ff !important;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .info-box {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .info-box strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>👥 Kelola Users</h1>
        <div class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="manage-movies.php">Kelola Drama</a>
            <a href="../dashboard.php">← Kembali ke Site</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>👥 Manajemen Users</h2>
            <p>Kelola role dan akses user (SuperAdmin Only)</p>
        </div>

        <div class="info-box">
            <strong>ℹ️ Info:</strong> Hanya SuperAdmin yang bisa mengubah role user. 
            Anda tidak bisa mengubah role Anda sendiri untuk keamanan.
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="users-table">
            <?php if (count($users) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role Saat Ini</th>
                        <th>Ubah Role</th>
                        <th>Terdaftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="<?php echo $user['id'] == getUserId() ? 'current-user' : ''; ?>">
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            <?php if ($user['id'] == getUserId()): ?>
                                <span style="color: #667eea; font-size: 12px;"> (Anda)</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo $user['role_name']; ?>">
                                <?php echo strtoupper($user['role_name']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['id'] != getUserId()): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <select name="role_id" class="role-select" onchange="this.form.submit()">
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role['id']; ?>"
                                                <?php echo $role['id'] == $user['role_id'] ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($role['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                            <?php else: ?>
                                <span style="color: #999; font-size: 13px;">Tidak bisa diubah</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <a href="../watch.php" class="btn btn-primary" target="_blank">👁️ View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <p>Tidak ada user ditemukan</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>