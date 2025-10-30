<?php
// test_connection.php
// File ini untuk mengecek koneksi database dan struktur tabel

require_once '../config/database.php';
require_once '../includes/auth.php';

// Require superadmin access
requireRole(['superadmin']);

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Database Connection</h2>";

// Test 1: Koneksi Database
echo "<h3>1. Testing Connection...</h3>";
try {
    $conn = new PDO("mysql:host=localhost;dbname=film", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ <strong style='color:green'>Koneksi Database Berhasil!</strong><br><br>";
} catch (PDOException $e) {
    echo "❌ <strong style='color:red'>Koneksi Gagal: " . $e->getMessage() . "</strong><br>";
    echo "<p>Pastikan:</p>";
    echo "<ul>";
    echo "<li>Database 'film' sudah dibuat</li>";
    echo "<li>MySQL/MariaDB sudah running</li>";
    echo "<li>Username dan password benar (default: root/kosong)</li>";
    echo "</ul>";
    die();
}

// Test 2: Cek Tabel Roles
echo "<h3>2. Checking Table: roles</h3>";
try {
    $stmt = $conn->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ <strong style='color:green'>Tabel 'roles' ditemukan!</strong><br>";
    echo "Data roles:<br>";
    echo "<pre>";
    print_r($roles);
    echo "</pre>";
} catch (PDOException $e) {
    echo "❌ <strong style='color:red'>Error: " . $e->getMessage() . "</strong><br>";
    echo "Tabel 'roles' belum dibuat atau ada error struktur.<br><br>";
}

// Test 3: Cek Tabel Users
echo "<h3>3. Checking Table: users</h3>";
try {
    $stmt = $conn->query("DESCRIBE users");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ <strong style='color:green'>Tabel 'users' ditemukan!</strong><br>";
    echo "Struktur tabel:<br>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($structure as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";

    // Cek jumlah user
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total users di database: <strong>{$result['total']}</strong><br><br>";

    // Tampilkan semua user (tanpa password)
    $stmt = $conn->query("SELECT id, username, email, role_id, created_at FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($users) > 0) {
        echo "Daftar users:<br>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role ID</th><th>Created At</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role_id']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (PDOException $e) {
    echo "❌ <strong style='color:red'>Error: " . $e->getMessage() . "</strong><br>";
    echo "Tabel 'users' belum dibuat atau ada error struktur.<br><br>";
}

// Test 4: Test Insert Manual
echo "<h3>4. Testing Manual Insert</h3>";
try {
    $test_username = "testuser_" . time();
    $test_email = "test_" . time() . "@example.com";
    $test_password = password_hash("password123", PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, 1)");
    $stmt->execute([$test_username, $test_email, $test_password]);

    echo "✅ <strong style='color:green'>Insert berhasil!</strong><br>";
    echo "Test user created: <strong>{$test_username}</strong><br>";
    echo "Test email: <strong>{$test_email}</strong><br><br>";

    // Hapus test user
    $conn->exec("DELETE FROM users WHERE username LIKE 'testuser_%'");
    echo "Test user sudah dihapus (cleanup).<br>";

} catch (PDOException $e) {
    echo "❌ <strong style='color:red'>Insert Gagal: " . $e->getMessage() . "</strong><br><br>";
}

echo "<hr>";
echo "<h3>Kesimpulan:</h3>";
echo "<p>Jika semua test di atas berhasil (✅), maka database sudah siap digunakan.</p>";
echo "<p><a href='register.php'>Kembali ke Register</a> | <a href='login.php'>Ke Login</a></p>";
?>