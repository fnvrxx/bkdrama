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
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi
    if (empty($username) || empty($email) || empty($password)) {
        $error = "Semua field harus diisi!";
    } elseif (!isValidEmail($email)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak sama!";
    } else {
        $database = new Database();
        $db = $database->getConnection();

        // Cek username sudah ada
        $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':username', $username);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $error = "Username atau email sudah terdaftar!";
        } else {
            // Insert user baru dengan role 'user' (role_id = 1)
            // $hashed_password = hashPassword($password);
            $insert_query = "INSERT INTO users (username, email, password, role_id) 
                            VALUES (:username, :email, :password, 1)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':username', $username);
            $insert_stmt->bindParam(':email', $email);
            $insert_stmt->bindParam(':password', $password);

            if ($insert_stmt->execute()) {
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = "Terjadi kesalahan saat registrasi. Silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Yuk Daftar dulu</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom font */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-800 min-h-screen flex items-center justify-center p-4">

    <!-- Container Utama -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden w-full max-w-6xl flex flex-col md:flex-row">

        <!-- Gambar Hanya untuk Desktop (md ke atas) -->
        <div class="hidden md:block w-full md:w-1/2 bg-gray-200">
            <img src="./assets/register/bg-register.png" alt="Poster Film Descendants of the Sun"
                class="w-full h-auto object-cover">
        </div>

        <!-- Form Register -->
        <div class="w-full md:w-1/2 p-8 flex flex-col justify-center">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Yuk Daftar dulu</h1>
            <p class="text-sm text-gray-600 mb-6">Biar bisa nonton film sepuasnya!</p>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-md text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-md text-sm">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <input type="text" name="nama" placeholder="Nama*"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                </div>
                <div>
                    <input type="email" name="email" placeholder="Alamat email*"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div>
                    <input type="text" name="username" placeholder="Username"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div>
                    <input type="password" name="password" placeholder="Password*"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <input type="password" name="confirm_password" placeholder="Konfirmasi password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit"
                    class="w-full bg-black text-white py-2 rounded-md hover:bg-gray-800 transition duration-200 font-medium">
                    Daftar
                </button>
            </form>

            <p class="mt-6 text-sm text-gray-600 text-center">
                Sudah punya akun? <a href="login.php" class="text-blue-600 hover:underline font-medium">Login di
                    sini</a>
            </p>
        </div>

    </div>

</body>

</html>