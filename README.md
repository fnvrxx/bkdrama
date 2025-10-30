# BKDrama - Platform Streaming Drama Korea

![BKDrama](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql)
![License](https://img.shields.io/badge/license-MIT-green.svg)

Platform streaming drama Korea dengan sistem manajemen pengguna, rating dinamis, watchlist, dan fitur favorites. Dibangun dengan PHP native dan MySQL.

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Teknologi](#-teknologi)
- [Persyaratan Sistem](#-persyaratan-sistem)
- [Instalasi](#-instalasi)
- [Konfigurasi](#-konfigurasi)
- [Struktur Proyek](#-struktur-proyek)
- [User Roles](#-user-roles)
- [Dokumentasi](#-dokumentasi)
- [Screenshot](#-screenshot)
- [Kontribusi](#-kontribusi)
- [License](#-license)

## ✨ Fitur Utama

### Untuk User
- **Authentication & Authorization**: Login, register, dan role-based access control
- **Browse Drama**: Melihat katalog drama dengan filter genre dan pencarian
- **Watch Episodes**: Streaming episode dengan video player
- **Rating System**: Memberikan rating 0-5 untuk drama (user-driven rating)
- **Favorites**: Menambahkan drama ke daftar favorit
- **Continue Watching**: Melanjutkan tontonan dari histori terakhir
- **Watch History**: Tracking riwayat tontonan otomatis

### Untuk Admin
- **Manajemen Drama**: CRUD drama (Create, Read, Update, Delete)
- **Manajemen Episode**: Upload dan kelola episode per drama
- **Upload System**: Upload thumbnail drama dan episode
- **Manajemen User**: Kelola user dan role (khusus superadmin)
- **Analytics**: Dashboard dengan statistik drama dan user
- **Rating Analytics**: Melihat rating rata-rata dari user

### Untuk Superadmin
- Semua fitur admin
- Manajemen user (promote/demote admin)
- Reset database
- System configuration

## 🛠 Teknologi

### Backend
- **PHP 7.4+**: Server-side programming
- **MySQL 5.7+**: Database relasional
- **PDO**: Database abstraction layer dengan prepared statements

### Frontend
- **HTML5 & CSS3**: Markup dan styling
- **JavaScript (Vanilla)**: Client-side interactivity
- **AJAX**: Asynchronous data loading

### Infrastructure
- **XAMPP/Apache**: Web server
- **Git**: Version control

## 💻 Persyaratan Sistem

- PHP >= 7.4
- MySQL >= 5.7 atau MariaDB >= 10.2
- Apache Web Server dengan mod_rewrite enabled
- Ekstensi PHP:
  - PDO
  - PDO_MySQL
  - GD (untuk image processing)
  - JSON
  - mbstring

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/yourusername/bkdrama.git
cd bkdrama
```

### 2. Setup Database

```bash
# Login ke MySQL
mysql -u root -p

# Buat database
CREATE DATABASE new_film CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE new_film;

# Import schema
source database/schema.sql;

# (Optional) Import sample data
source database/seed.sql;
```

### 3. Konfigurasi

Edit file `config/database.php`:

```php
private $host = "localhost";
private $db_name = "new_film";
private $username = "root";
private $password = "your_password";
```

### 4. Set Permissions

```bash
# Buat direktori untuk uploads
mkdir -p assets/uploads/posters
mkdir -p assets/uploads/thumbnails
mkdir -p assets/videos

# Set permissions
chmod -R 755 assets/
chmod -R 777 assets/uploads/
chmod -R 777 assets/videos/
```

### 5. Setup Web Server

**Untuk XAMPP:**
- Copy folder project ke `C:\xampp\htdocs\` (Windows) atau `/Applications/XAMPP/xamppfiles/htdocs/` (Mac)
- Akses: `http://localhost/bkdrama/`

**Untuk Apache:**
```apache
<VirtualHost *:80>
    ServerName bkdrama.local
    DocumentRoot "/path/to/bkdrama"

    <Directory "/path/to/bkdrama">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 6. Default Credentials

Setelah instalasi, gunakan credentials berikut:

**Superadmin:**
- Username: `superadmin`
- Password: `superadmin123`

**Admin:**
- Username: `admin`
- Password: `admin123`

**User:**
- Username: `user`
- Password: `user123`

⚠️ **PENTING**: Ubah password default setelah login pertama!

## ⚙️ Konfigurasi

### Database Configuration

File: `config/database.php`

```php
class Database {
    private $host = "localhost";
    private $db_name = "new_film";
    private $username = "root";
    private $password = "";
    public $conn;
}
```

### Upload Configuration

File: `includes/upload.php`

```php
// Maksimal ukuran file
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Allowed file types
$allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
```

### Session Configuration

Session timeout: 24 jam (default PHP setting)

## 📁 Struktur Proyek

```
Bkdrama/
├── admin/                      # Admin panel
│   ├── index.php              # Dashboard admin
│   ├── add-movies.php         # Tambah drama baru
│   ├── edit-movies.php        # Edit drama
│   ├── delete-movies.php      # Hapus drama
│   ├── manage-movies.php      # Kelola drama
│   ├── add-episode.php        # Tambah episode
│   ├── edit-episode.php       # Edit episode
│   ├── delete-episode.php     # Hapus episode
│   ├── manage-episodes.php    # Kelola episode
│   └── manage-users.php       # Kelola user (superadmin)
├── api/                        # REST API endpoints
│   ├── rate-drama.php         # Submit rating
│   ├── get-user-rating.php    # Get user rating
│   ├── toggle-favorite.php    # Toggle favorite
│   └── watch-history-api.php  # Save watch history
├── assets/                     # Static assets
│   ├── uploads/               # User uploads
│   │   ├── posters/          # Drama posters
│   │   └── thumbnails/       # Episode thumbnails
│   ├── videos/                # Video files
│   ├── login/                 # Login page assets
│   └── register/              # Register page assets
├── config/                     # Configuration files
│   └── database.php           # Database connection
├── includes/                   # Reusable PHP modules
│   ├── auth.php               # Authentication & authorization
│   └── upload.php             # File upload handler
├── database/                   # Database files
│   ├── schema.sql             # Database schema
│   └── seed.sql               # Sample data
├── docs/                       # Documentation
│   ├── API.md                 # API documentation
│   ├── DATABASE.md            # Database documentation
│   └── FEATURES.md            # Features documentation
├── index.php                   # Landing page
├── login.php                   # Login page
├── register.php                # Register page
├── dashboard.php               # User dashboard
├── movies.php                  # Browse drama
├── watchlist.php               # Drama detail & episodes
├── watch.php                   # Video player
├── favorites.php               # User favorites
├── continue-watching.php       # Continue watching
├── logout.php                  # Logout handler
├── diagram_database.puml       # Database diagram (PlantUML)
├── README.md                   # This file
├── RATING_SYSTEM_README.md     # Rating system docs
└── ADMIN_RATING_CHANGES.md     # Admin changes docs
```

## 👥 User Roles

### 1. User (Role ID: 1)
**Capabilities:**
- Browse dan search drama
- Watch episodes
- Rate drama (0-5)
- Add/remove favorites
- View watch history
- Continue watching

**Restrictions:**
- Tidak bisa akses admin panel
- Tidak bisa upload/edit/delete drama

### 2. Admin (Role ID: 2)
**Capabilities:**
- Semua capabilities user
- Akses admin panel
- CRUD drama
- CRUD episodes
- Upload files (posters, thumbnails, videos)
- View analytics

**Restrictions:**
- Tidak bisa manage users
- Tidak bisa promote/demote admin
- Tidak bisa reset database

### 3. Superadmin (Role ID: 3)
**Capabilities:**
- Semua capabilities admin
- Manage users (create, edit, delete)
- Promote user to admin
- Demote admin to user
- Reset database
- System configuration

## 📚 Dokumentasi

### Detail Dokumentasi

- **[DATABASE.md](docs/DATABASE.md)**: Dokumentasi lengkap struktur database
- **[API.md](docs/API.md)**: Dokumentasi API endpoints
- **[FEATURES.md](docs/FEATURES.md)**: Dokumentasi fitur-fitur sistem
- **[DEVELOPER.md](docs/DEVELOPER.md)**: Panduan untuk developer
- **[RATING_SYSTEM_README.md](RATING_SYSTEM_README.md)**: Sistem rating user-driven
- **[ADMIN_RATING_CHANGES.md](ADMIN_RATING_CHANGES.md)**: Perubahan rating di admin panel

### Database Schema

Lihat file [diagram_database.puml](diagram_database.puml) untuk diagram lengkap.

**Tabel Utama:**
- `roles`: User roles
- `users`: Data user
- `drama`: Data drama
- `episodes`: Episode per drama
- `ratings`: Rating user (baru!)
- `favorit`: Favorites user
- `users_history`: Watch history

**Views:**
- `v_drama_ratings`: Rata-rata rating per drama

## 📸 Screenshot

### User Interface
- **Landing Page**: Browse drama dengan filter genre
- **Drama Detail**: Info lengkap drama + list episode
- **Video Player**: Streaming episode
- **Favorites**: Daftar drama favorit

### Admin Panel
- **Dashboard**: Statistics dan recent drama
- **Manage Drama**: Table dengan CRUD operations
- **Add Drama**: Form upload drama baru
- **Manage Episodes**: List episode per drama

## 🔧 Development

### Setup Development Environment

```bash
# Clone repository
git clone https://github.com/yourusername/bkdrama.git
cd bkdrama

# Install database
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql

# Start development server
php -S localhost:8000
```

### Coding Standards

- Follow PSR-12 PHP coding standard
- Use prepared statements untuk semua database queries
- Sanitize semua user input
- Use meaningful variable names
- Comment complex logic

### Git Workflow

```bash
# Create feature branch
git checkout -b feature/nama-fitur

# Commit changes
git add .
git commit -m "feat: deskripsi fitur"

# Push to remote
git push origin feature/nama-fitur

# Create pull request
```

### Testing

```bash
# Test database connection
php test_conn.php

# Test API endpoints
php test-api.php

# Check file paths
php debug-path.php
```

## 🤝 Kontribusi

Kontribusi sangat diterima! Silakan ikuti langkah berikut:

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'feat: Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

### Commit Message Convention

```
feat: menambah fitur baru
fix: memperbaiki bug
docs: update dokumentasi
style: perubahan formatting
refactor: refactoring code
test: menambah test
chore: maintenance
```

## 🐛 Bug Report

Jika menemukan bug, silakan buat issue di GitHub dengan informasi:
- Deskripsi bug
- Steps to reproduce
- Expected behavior
- Screenshots (jika ada)
- Environment (OS, PHP version, MySQL version)

## 📝 Todo / Roadmap

- [ ] Implement search autocomplete
- [ ] Add video quality selection (360p, 480p, 720p, 1080p)
- [ ] Implement notification system
- [ ] Add social sharing
- [ ] Mobile responsive improvements
- [ ] Progressive Web App (PWA)
- [ ] Email verification
- [ ] Password reset via email
- [ ] Advanced analytics dashboard
- [ ] Export data to CSV/PDF

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**BKDrama Team**
- Website: [bkdrama.com](https://bkdrama.com)
- Email: info@bkdrama.com
- GitHub: [@bkdrama](https://github.com/bkdrama)

## 🙏 Acknowledgments

- Terima kasih kepada semua kontributor
- Icons by [Font Awesome](https://fontawesome.com)
- Video player inspired by [Plyr](https://plyr.io)
- UI design inspired by Netflix & Viu

## 📞 Support

Butuh bantuan? Hubungi kami:
- Email: support@bkdrama.com
- Discord: [BKDrama Community](https://discord.gg/bkdrama)
- Documentation: [docs.bkdrama.com](https://docs.bkdrama.com)

---

Made with ❤️ by BKDrama Team
