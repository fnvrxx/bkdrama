<?php
/**
 * Authentication & Authorization Functions
 * Fungsi untuk mengelola session, login, dan role-based access
 */

// Start session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Redirect ke login jika belum login
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: /bkdrama/login.php");
        exit();
    }
}

/**
 * Cek apakah user memiliki role tertentu
 * @param string|array $roles - role name atau array of role names
 */
function hasRole($roles)
{
    if (!isLoggedIn()) {
        return false;
    }

    if (is_array($roles)) {
        return in_array($_SESSION['role'], $roles);
    }

    return $_SESSION['role'] === $roles;
}

/**
 * Require role tertentu, redirect jika tidak punya akses
 * @param string|array $roles - role name atau array of role names
 */
function requireRole($roles)
{
    requireLogin();

    if (!hasRole($roles)) {
        header("Location: /bkdrama/dashboard.php?error=access_denied");
        exit();
    }
}

/**
 * Get user ID dari session
 */
function getUserId()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get username dari session
 */
function getUsername()
{
    return $_SESSION['username'] ?? null;
}

/**
 * Get role dari session
 */
function getRole()
{
    return $_SESSION['role'] ?? null;
}

/**
 * Logout user
 */
function logout()
{
    session_unset();
    session_destroy();
    header("Location: /bkdrama/login.php");
    exit();
}

/**
 * Set session setelah login berhasil
 */
function setUserSession($user_id, $username, $role, $email = '')
{
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['email'] = $email;
    $_SESSION['login_time'] = time();
}

/**
 * Sanitize input untuk keamanan
 */
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validasi email format
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>