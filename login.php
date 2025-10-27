<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        $database = new Database();
        $db = $database->getConnection();

        $query = "SELECT u.*, r.name as role_name 
                  FROM users u 
                  JOIN roles r ON u.role_id = r.id 
                  WHERE u.username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();

            // Plain text password comparison
            if ($password === $user['password']) {
                setUserSession($user['id'], $user['username'], $user['role_name'], $user['email']);
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
    }
}

// Get success message from URL if any
if (isset($_GET['registered'])) {
    $success = "Registrasi berhasil! Silakan login.";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Selamat Datang!</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Just+Me+Again+Down+Here&family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fef3c7;
        }

        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 1200px;
            display: flex;
            flex-direction: row;
        }

        .image-section {
            width: 50%;
            background-color: #e5e7eb;
        }

        .image-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .form-section {
            width: 50%;
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.5rem;
            font-family: 'Just Me Again Down Here', cursive;
            font-size: 5rem;
        }

        .subtitle {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
        }

        .alert {
            padding: 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.625rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-submit {
            width: 100%;
            background-color: #000;
            color: white;
            padding: 0.625rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-submit:hover {
            background-color: #1f2937;
        }

        .register-link {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .register-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            body {
                background-color: white;
                padding: 0;
            }

            .container {
                max-width: 100%;
                flex-direction: column;
                border-radius: 0;
                box-shadow: none;
            }

            .image-section {
                display: none;
            }

            .form-section {
                width: 100%;
                padding: 2rem 1.5rem;
            }

            h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>

    <!-- Container Utama -->
    <div class="container">

        <!-- Gambar Kiri (Desktop only) -->
        <div class="image-section">
            <img src="./assets/login/bg-login.png" alt="Poster Drama Korea">
        </div>

        <!-- Form Login -->
        <div class="form-section">
            <h1>Selamat Datang!</h1>
            <p class="subtitle">Silahkan Login</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn-submit">
                    Login
                </button>
            </form>

            <p class="register-link">
                Belum punya akun? Silahkan <a href="./register.php">daftar di sini</a>
            </p>
        </div>

    </div>

</body>

</html>