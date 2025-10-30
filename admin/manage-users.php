<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole('superadmin'); // Only superadmin

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle ADD USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = intval($_POST['role_id'] ?? 2); // Default: user

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Semua field harus diisi!";
    } else {
        // Check if username or email exists
        $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$username, $email]);

        if ($check_stmt->rowCount() > 0) {
            $error = "Username atau email sudah digunakan!";
        } else {
            // Hash password
            // $hashed_password = password_hash(password: $password, PASSWORD_DEFAULT);

            $insert_query = "INSERT INTO users (username, email, password, role_id, created_at) 
                           VALUES (?, ?, ?, ?, NOW())";
            $insert_stmt = $db->prepare($insert_query);

            if ($insert_stmt->execute([$username, $email, $password, $role_id])) {
                $success = "User berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan user!";
            }
        }
    }
}

// Handle EDIT USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role_id = intval($_POST['role_id'] ?? 2);
    $new_password = $_POST['new_password'] ?? '';

    if ($user_id > 0 && !empty($username) && !empty($email)) {
        // Check if username or email exists for other users
        $check_query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$username, $email, $user_id]);

        if ($check_stmt->rowCount() > 0) {
            $error = "Username atau email sudah digunakan oleh user lain!";
        } else {
            // Update without password
            if (empty($new_password)) {
                $update_query = "UPDATE users SET username = ?, email = ?, role_id = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $result = $update_stmt->execute([$username, $email, $role_id, $user_id]);
            } else {
                // Update with new password
                // $hashed_password = password_hash(password: $new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET username = ?, email = ?, password = ?, role_id = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $result = $update_stmt->execute([$username, $email, $password, $role_id, $user_id]);
            }

            if ($result) {
                $success = "User berhasil diupdate!";
            } else {
                $error = "Gagal mengupdate user!";
            }
        }
    } else {
        $error = "Data tidak valid!";
    }
}

// Handle DELETE USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id = intval($_POST['user_id'] ?? 0);

    // Prevent deleting own account
    if ($user_id == getUserId()) {
        $error = "Tidak bisa menghapus akun sendiri!";
    } elseif ($user_id > 0) {
        // Delete related favorites first
        $delete_favorites = "DELETE FROM favorit WHERE user_id = ?";
        $db->prepare($delete_favorites)->execute([$user_id]);

        // Delete user
        $delete_query = "DELETE FROM users WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);

        if ($delete_stmt->execute([$user_id])) {
            $success = "User berhasil dihapus!";
        } else {
            $error = "Gagal menghapus user!";
        }
    }
}

// Handle QUICK ROLE CHANGE
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

// Get user stats
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN role_id = 1 THEN 1 ELSE 0 END) as user_count,
    SUM(CASE WHEN role_id = 2 THEN 1 ELSE 0 END) as admin_count,
    SUM(CASE WHEN role_id = 3 THEN 1 ELSE 0 END) as superadmin_count
FROM users";
$stats_stmt = $db->query($stats_query);
$user_stats = $stats_stmt->fetch();
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h2 {
            font-size: 28px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #666;
            font-size: 14px;
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
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
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

        .current-user {
            background: #e7f3ff !important;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            animation: slideDown 0.3s;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            font-size: 20px;
            color: #333;
        }

        .close-modal {
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            background: none;
            border: none;
        }

        .close-modal:hover {
            color: #000;
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
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus,
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

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .actions {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
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
            <div>
                <h2>👥 Manajemen Users</h2>
                <p>Kelola user dan role (SuperAdmin Only)</p>
            </div>
            <button class="btn btn-success" onclick="openAddModal()">
                ➕ Tambah User Baru
            </button>
        </div>

        <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
                <div class="alert alert-danger">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- User Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $user_stats['total']; ?></h3>
                <p>👥 Total Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $user_stats['superadmin_count']; ?></h3>
                <p>👑 SuperAdmin</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $user_stats['admin_count']; ?></h3>
                <p>🛡️ Admin</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $user_stats['user_count']; ?></h3>
                <p>👤 Users</p>
            </div>
        </div>

        <!-- Users Table -->
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Terdaftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr class="<?php echo $user['id'] == getUserId() ? 'current-user' : ''; ?>">
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
                            <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="actions">
                                    <?php if ($user['id'] != getUserId()): ?>
                                            <button class="btn btn-warning btn-sm" 
                                                    onclick='openEditModal(<?php echo json_encode($user); ?>)'>
                                                ✏️ Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm" 
                                                    onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                🗑️ Hapus
                                            </button>
                                    <?php else: ?>
                                            <span style="color: #999; font-size: 13px;">Akun Anda</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Add User -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Tambah User Baru</h3>
                <button class="close-modal" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-group">
                    <label for="add_username">Username *</label>
                    <input type="text" id="add_username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="add_email">Email *</label>
                    <input type="email" id="add_email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="add_password">Password *</label>
                    <input type="password" id="add_password" name="password" required>
                    <small>Minimal 6 karakter</small>
                </div>

                <div class="form-group">
                    <label for="add_role">Role *</label>
                    <select id="add_role" name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" 
                                        <?php echo $role['name'] == 'user' ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($role['name']); ?>
                                </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeAddModal()">Batal</button>
                    <button type="submit" class="btn btn-success">💾 Simpan User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit User -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Edit User</h3>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" id="edit_user_id" name="user_id">
                
                <div class="form-group">
                    <label for="edit_username">Username *</label>
                    <input type="text" id="edit_username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="edit_email">Email *</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="edit_role">Role *</label>
                    <select id="edit_role" name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>">
                                    <?php echo ucfirst($role['name']); ?>
                                </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_password">Password Baru</label>
                    <input type="password" id="edit_password" name="new_password">
                    <small>Kosongkan jika tidak ingin mengubah password</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">💾 Update User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form Delete User (Hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_user">
        <input type="hidden" id="delete_user_id" name="user_id">
    </form>

    <script>
        // Add User Modal
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        // Edit User Modal
        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role_id;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Delete User
        function deleteUser(userId, username) {
            if (confirm(`Apakah Anda yakin ingin menghapus user "${username}"?\n\nSemua data favorit user ini juga akan dihapus!`)) {
                document.getElementById('delete_user_id').value = userId;
                document.getElementById('deleteForm').submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target == addModal) {
                closeAddModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }

        // Auto close alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>