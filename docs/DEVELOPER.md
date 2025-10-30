# Developer Guide - BKDrama

## Overview

Panduan ini ditujukan untuk developer yang ingin berkontribusi atau mengembangkan BKDrama lebih lanjut.

---

## Table of Contents

1. [Setup Development Environment](#setup-development-environment)
2. [Project Structure](#project-structure)
3. [Coding Standards](#coding-standards)
4. [Database Development](#database-development)
5. [API Development](#api-development)
6. [Frontend Development](#frontend-development)
7. [Testing](#testing)
8. [Deployment](#deployment)
9. [Troubleshooting](#troubleshooting)
10. [Contributing](#contributing)

---

## Setup Development Environment

### Prerequisites

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau MariaDB 10.2 atau lebih tinggi
- Apache Web Server dengan mod_rewrite enabled
- Git
- Composer (optional, untuk package management)
- Node.js & npm (optional, untuk frontend build tools)

### Installation Steps

#### 1. Clone Repository

```bash
git clone https://github.com/yourusername/bkdrama.git
cd bkdrama
```

#### 2. Setup Database

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE new_film CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Use database
USE new_film;

# Import schema
source database/schema.sql;

# (Optional) Import sample data
source database/seed.sql;
```

#### 3. Configure Database Connection

Edit `config/database.php`:

```php
<?php
class Database {
    private $host = "localhost";
    private $db_name = "new_film";
    private $username = "root";
    private $password = "your_password"; // Ganti dengan password MySQL Anda
    public $conn;

    // ...
}
```

#### 4. Setup File Permissions

```bash
# Create upload directories
mkdir -p assets/uploads/posters
mkdir -p assets/uploads/thumbnails
mkdir -p assets/videos

# Set permissions (Linux/Mac)
chmod -R 755 assets/
chmod -R 777 assets/uploads/
chmod -R 777 assets/videos/

# For Windows, right-click → Properties → Security → Edit permissions
```

#### 5. Configure Virtual Host (Optional)

**Apache (Linux/Mac):**

Edit `/etc/apache2/sites-available/bkdrama.conf`:

```apache
<VirtualHost *:80>
    ServerName bkdrama.local
    ServerAlias www.bkdrama.local
    DocumentRoot /path/to/bkdrama

    <Directory /path/to/bkdrama>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/bkdrama_error.log
    CustomLog ${APACHE_LOG_DIR}/bkdrama_access.log combined
</VirtualHost>
```

Enable site:
```bash
sudo a2ensite bkdrama.conf
sudo systemctl reload apache2
```

Add to `/etc/hosts`:
```
127.0.0.1 bkdrama.local
```

#### 6. PHP Configuration

Edit `php.ini`:

```ini
# Upload limits
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 300
memory_limit = 256M

# Error reporting (development)
display_errors = On
error_reporting = E_ALL

# Session settings
session.cookie_httponly = 1
session.use_strict_mode = 1
```

Restart Apache after changes.

---

## Project Structure

```
Bkdrama/
├── admin/                      # Admin panel pages
│   ├── index.php              # Dashboard
│   ├── add-movies.php         # Add drama
│   ├── edit-movies.php        # Edit drama
│   ├── delete-movies.php      # Delete drama
│   ├── manage-movies.php      # Manage all drama
│   ├── add-episode.php        # Add episode
│   ├── edit-episode.php       # Edit episode
│   ├── delete-episode.php     # Delete episode
│   ├── manage-episodes.php    # Manage episodes
│   └── manage-users.php       # Manage users (superadmin)
│
├── api/                        # REST API endpoints
│   ├── rate-drama.php         # Rating API
│   ├── get-user-rating.php    # Get user rating
│   ├── toggle-favorite.php    # Favorite toggle
│   └── watch-history-api.php  # Watch history API
│
├── assets/                     # Static assets
│   ├── uploads/               # User uploads
│   │   ├── posters/          # Drama posters
│   │   └── thumbnails/       # Episode thumbnails
│   ├── videos/                # Video files
│   ├── login/                 # Login assets
│   └── register/              # Register assets
│
├── config/                     # Configuration
│   └── database.php           # Database connection class
│
├── includes/                   # Reusable modules
│   ├── auth.php               # Authentication functions
│   └── upload.php             # File upload handler
│
├── docs/                       # Documentation
│   ├── API.md                 # API documentation
│   ├── DATABASE.md            # Database documentation
│   ├── FEATURES.md            # Features documentation
│   └── DEVELOPER.md           # This file
│
├── database/                   # Database files
│   ├── schema.sql             # Database schema
│   └── seed.sql               # Sample data
│
├── index.php                   # Landing page
├── login.php                   # Login page
├── register.php                # Register page
├── dashboard.php               # User dashboard
├── movies.php                  # Browse drama
├── watchlist.php               # Drama detail
├── watch.php                   # Video player
├── favorites.php               # User favorites
├── continue-watching.php       # Continue watching
├── logout.php                  # Logout handler
│
├── .gitignore                  # Git ignore file
├── README.md                   # Project readme
└── diagram_database.puml       # Database diagram
```

### Key Directories

**`admin/`**: Halaman admin untuk CRUD drama, episode, dan user management.

**`api/`**: REST API endpoints untuk AJAX operations.

**`config/`**: File konfigurasi (database, constants).

**`includes/`**: Helper functions dan reusable code.

**`assets/`**: Static files (images, videos, CSS, JS).

**`docs/`**: Documentation files.

---

## Coding Standards

### PHP Coding Standards

Follow **PSR-12** coding style:

```php
<?php
// Use strict types
declare(strict_types=1);

// Namespace (if using autoloader)
namespace BKDrama\Controllers;

// Use statements
use PDO;
use PDOException;

class DramaController
{
    // Properties
    private $db;
    private $table_name = "drama";

    // Constructor
    public function __construct($db)
    {
        $this->db = $db;
    }

    // Methods
    public function getAllDrama(): array
    {
        $query = "SELECT * FROM {$this->table_name} ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Private methods
    private function validateInput(array $data): bool
    {
        // Validation logic
        return true;
    }
}
```

### Naming Conventions

**Variables:**
```php
$user_id = 1;               // Snake case
$dramaTitle = "Title";      // Camel case (acceptable)
$is_logged_in = true;       // Boolean prefix with is_, has_, can_
```

**Functions:**
```php
function getUserById($id) { }           // Camel case
function get_user_by_id($id) { }       // Snake case (legacy)
```

**Classes:**
```php
class DramaController { }   // Pascal case
class DatabaseConnection { }
```

**Constants:**
```php
define('MAX_FILE_SIZE', 5242880);  // All caps
const UPLOAD_DIR = '/uploads/';
```

### Security Best Practices

#### 1. SQL Injection Prevention

**ALWAYS use prepared statements:**

```php
// ✅ GOOD - Prepared statement
$query = "SELECT * FROM users WHERE username = :username";
$stmt = $db->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->execute();

// ❌ BAD - String concatenation
$query = "SELECT * FROM users WHERE username = '$username'";
$result = $db->query($query);
```

#### 2. XSS Prevention

**Escape output:**

```php
// ✅ GOOD
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// ❌ BAD
echo $user_input;
```

#### 3. Password Hashing

```php
// ✅ GOOD - Use bcrypt
$hashed_password = password_hash($password, PASSWORD_BCRYPT);
$is_valid = password_verify($input_password, $hashed_password);

// ❌ BAD - MD5/SHA1
$hashed_password = md5($password);
```

#### 4. File Upload Security

```php
// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
$file_type = $_FILES['file']['type'];

if (!in_array($file_type, $allowed_types)) {
    die('Invalid file type');
}

// Validate file size
$max_size = 5 * 1024 * 1024; // 5MB
if ($_FILES['file']['size'] > $max_size) {
    die('File too large');
}

// Generate safe filename
$extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
$safe_filename = uniqid() . '_' . time() . '.' . $extension;
```

#### 5. CSRF Protection

```php
// Generate token
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// In form
echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';

// Validate token
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF token validation failed');
}
```

---

## Database Development

### Creating Migrations

Saat menambah fitur baru yang memerlukan perubahan database:

```sql
-- migration_2024_10_31_add_comments_table.sql

CREATE TABLE comments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    drama_id INT(11) NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (drama_id) REFERENCES drama(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create index for faster queries
CREATE INDEX idx_drama_comments ON comments(drama_id, created_at DESC);
```

### Query Optimization

**Use EXPLAIN untuk analyze queries:**

```sql
EXPLAIN SELECT d.*, AVG(r.rating) as avg_rating
FROM drama d
LEFT JOIN ratings r ON d.id = r.drama_id
GROUP BY d.id;
```

**Add indexes untuk frequently queried columns:**

```sql
-- Index untuk search
CREATE INDEX idx_drama_title ON drama(title);

-- Composite index untuk multiple columns
CREATE INDEX idx_drama_genre_year ON drama(genre, rilis_tahun);

-- Full-text index untuk search
CREATE FULLTEXT INDEX idx_drama_fulltext ON drama(title, deskripsi);
```

### Database Best Practices

1. **Always use transactions untuk multiple operations:**

```php
try {
    $db->beginTransaction();

    // Insert drama
    $stmt1 = $db->prepare("INSERT INTO drama (title, ...) VALUES (?, ...)");
    $stmt1->execute([$title, ...]);
    $drama_id = $db->lastInsertId();

    // Insert first episode
    $stmt2 = $db->prepare("INSERT INTO episodes (id_drama, ...) VALUES (?, ...)");
    $stmt2->execute([$drama_id, ...]);

    $db->commit();
} catch (PDOException $e) {
    $db->rollBack();
    throw $e;
}
```

2. **Use views untuk complex queries:**

```sql
CREATE OR REPLACE VIEW v_drama_full_info AS
SELECT
    d.*,
    COUNT(DISTINCT e.id) as episode_count,
    COALESCE(AVG(r.rating), 0) as avg_rating,
    COUNT(DISTINCT r.id) as total_ratings,
    COUNT(DISTINCT f.id) as total_favorites
FROM drama d
LEFT JOIN episodes e ON d.id = e.id_drama
LEFT JOIN ratings r ON d.id = r.drama_id
LEFT JOIN favorit f ON d.id = f.drama_id
GROUP BY d.id;
```

---

## API Development

### Creating New API Endpoint

**File:** `api/new-endpoint.php`

```php
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Enable CORS (if needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

// Validate HTTP method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Get and validate parameters
$param = $_POST['param'] ?? null;

if (empty($param)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parameter required'
    ]);
    exit;
}

// Database operation
$database = new Database();
$db = $database->getConnection();

try {
    // Your logic here
    $query = "SELECT * FROM table WHERE column = :param";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':param', $param);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Success response
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);

    // Log error (don't expose to client)
    error_log('API Error: ' . $e->getMessage());
}
```

### API Testing

```bash
# Using cURL
curl -X POST http://localhost/Bkdrama/api/new-endpoint.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "param=value" \
  -b cookies.txt

# Using HTTPie
http POST localhost/Bkdrama/api/new-endpoint.php \
  param=value \
  --session=user

# Using Postman
# Import the Postman collection from docs/postman_collection.json
```

---

## Frontend Development

### HTML Structure

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Title - BKDrama</title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="Description here">
    <meta name="keywords" content="drama korea, kdrama">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Main Content -->
    <main>
        <h1>Page Heading</h1>
        <!-- Content here -->
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="/assets/js/main.js"></script>
</body>
</html>
```

### JavaScript Best Practices

```javascript
// Use strict mode
'use strict';

// Modern JavaScript (ES6+)
const API_BASE_URL = '/api';

// Async/await untuk API calls
async function fetchDrama(dramaId) {
    try {
        const response = await fetch(`${API_BASE_URL}/get-drama.php?id=${dramaId}`, {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        return data;

    } catch (error) {
        console.error('Error fetching drama:', error);
        showErrorMessage('Failed to load drama');
    }
}

// Event delegation
document.addEventListener('click', (e) => {
    if (e.target.matches('.favorite-btn')) {
        toggleFavorite(e.target.dataset.dramaId);
    }
});

// Debounce untuk search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

const searchInput = document.getElementById('search');
searchInput.addEventListener('input', debounce((e) => {
    performSearch(e.target.value);
}, 300));
```

### CSS Organization

```css
/* Variables */
:root {
    --primary-color: #e50914;
    --secondary-color: #564d4d;
    --text-color: #333;
    --bg-color: #f5f5f5;
    --font-family: 'Segoe UI', Tahoma, sans-serif;
}

/* Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Typography */
body {
    font-family: var(--font-family);
    color: var(--text-color);
    background: var(--bg-color);
}

/* Layout */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Components */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 0 15px;
    }
}
```

---

## Testing

### Manual Testing Checklist

**Authentication:**
- [ ] Register dengan username baru
- [ ] Register dengan username duplicate (should fail)
- [ ] Login dengan credentials benar
- [ ] Login dengan credentials salah
- [ ] Logout
- [ ] Access protected page tanpa login (should redirect)

**Drama Management (Admin):**
- [ ] Add drama dengan semua field
- [ ] Add drama tanpa required field (should fail)
- [ ] Upload poster > 5MB (should fail)
- [ ] Edit drama
- [ ] Delete drama
- [ ] Check cascade delete (episodes, ratings, favorites)

**Rating System:**
- [ ] Rate drama (1-5 stars)
- [ ] Update rating
- [ ] Check average rating calculation
- [ ] Check rating without login (should fail)

**Video Player:**
- [ ] Play video
- [ ] Pause video
- [ ] Seek forward/backward
- [ ] Fullscreen mode
- [ ] Volume control
- [ ] Resume from last position

### Automated Testing (Future)

```php
// PHPUnit test example
class DramaTest extends TestCase
{
    public function testCreateDrama()
    {
        $drama = new Drama($this->db);
        $result = $drama->create([
            'title' => 'Test Drama',
            'genre' => 'Romance',
            'rilis_tahun' => 2024
        ]);

        $this->assertTrue($result);
    }
}
```

---

## Deployment

### Production Checklist

**1. Security:**
- [ ] Change default credentials
- [ ] Set `display_errors = Off` in php.ini
- [ ] Enable HTTPS
- [ ] Set secure session settings
- [ ] Implement rate limiting
- [ ] Add CSRF protection
- [ ] Sanitize all inputs
- [ ] Validate all uploads

**2. Performance:**
- [ ] Enable gzip compression
- [ ] Minify CSS/JS
- [ ] Optimize images
- [ ] Enable caching
- [ ] Setup CDN (optional)
- [ ] Optimize database queries
- [ ] Add database indexes

**3. Monitoring:**
- [ ] Setup error logging
- [ ] Setup access logging
- [ ] Monitor disk space
- [ ] Monitor database size
- [ ] Setup backups

### Deployment to Production Server

```bash
# 1. Pull latest code
git pull origin main

# 2. Set permissions
chmod -R 755 .
chmod -R 777 assets/uploads/
chmod -R 777 assets/videos/

# 3. Update database
mysql -u root -p new_film < database/migrations/latest.sql

# 4. Clear cache (if using cache)
rm -rf cache/*

# 5. Restart web server
sudo systemctl restart apache2
```

### Environment Configuration

Create `.env` file (don't commit to git):

```env
DB_HOST=localhost
DB_NAME=new_film
DB_USER=root
DB_PASS=secret_password

APP_ENV=production
APP_DEBUG=false
APP_URL=https://bkdrama.com

UPLOAD_MAX_SIZE=5242880
VIDEO_MAX_SIZE=524288000
```

Load in `config/database.php`:

```php
<?php
// Load .env
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

class Database {
    private $host = $_ENV['DB_HOST'] ?? 'localhost';
    private $db_name = $_ENV['DB_NAME'] ?? 'new_film';
    private $username = $_ENV['DB_USER'] ?? 'root';
    private $password = $_ENV['DB_PASS'] ?? '';
    // ...
}
```

---

## Troubleshooting

### Common Issues

**1. Database Connection Error**

```
Error: SQLSTATE[HY000] [2002] Connection refused
```

**Solution:**
- Check MySQL service running: `sudo systemctl status mysql`
- Check credentials in `config/database.php`
- Check firewall settings

**2. Upload Failed**

```
Error: Failed to move uploaded file
```

**Solution:**
- Check directory permissions: `chmod 777 assets/uploads/`
- Check `upload_max_filesize` in php.ini
- Check `post_max_size` in php.ini

**3. Session Not Working**

**Solution:**
- Check `session.save_path` in php.ini
- Ensure directory is writable
- Clear browser cookies

**4. Blank Page (White Screen)**

**Solution:**
- Enable error display: `ini_set('display_errors', 1);`
- Check PHP error log: `tail -f /var/log/apache2/error.log`
- Check file permissions

---

## Contributing

### Git Workflow

```bash
# 1. Create feature branch
git checkout -b feature/nama-fitur

# 2. Make changes
# Edit files...

# 3. Commit with meaningful message
git add .
git commit -m "feat: add comment feature"

# 4. Push to remote
git push origin feature/nama-fitur

# 5. Create Pull Request on GitHub
```

### Commit Message Convention

Format: `<type>: <description>`

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation update
- `style`: Code formatting
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Maintenance tasks

**Examples:**
```
feat: add rating system
fix: resolve video player bug
docs: update API documentation
style: format code with PSR-12
refactor: optimize database queries
test: add unit tests for Drama class
chore: update dependencies
```

### Code Review Checklist

Before submitting PR:
- [ ] Code follows PSR-12 standards
- [ ] No SQL injection vulnerabilities
- [ ] All user inputs sanitized
- [ ] Passwords properly hashed
- [ ] Error handling implemented
- [ ] Comments added for complex logic
- [ ] No hardcoded credentials
- [ ] Files properly organized
- [ ] Documentation updated
- [ ] Tested locally

---

## Resources

### Documentation
- [PHP Manual](https://www.php.net/manual/en/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [PSR-12 Coding Style](https://www.php-fig.org/psr/psr-12/)

### Tools
- [PHPStorm](https://www.jetbrains.com/phpstorm/) - IDE
- [VS Code](https://code.visualstudio.com/) - Editor
- [Postman](https://www.postman.com/) - API testing
- [phpMyAdmin](https://www.phpmyadmin.net/) - Database management

### Libraries (Optional)
- [PHPUnit](https://phpunit.de/) - Testing framework
- [Composer](https://getcomposer.org/) - Dependency manager
- [Guzzle](https://docs.guzzlephp.org/) - HTTP client
- [Symfony Console](https://symfony.com/doc/current/components/console.html) - CLI tools

---

## Contact

Untuk pertanyaan development:
- Email: dev@bkdrama.com
- Slack: bkdrama-dev.slack.com
- GitHub: github.com/bkdrama/bkdrama

---

**Last Updated:** 2024-10-31
**Version:** 1.1.0
