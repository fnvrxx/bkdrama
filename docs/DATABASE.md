# Dokumentasi Database BKDrama

## Overview

Database BKDrama menggunakan MySQL/MariaDB dengan engine InnoDB untuk mendukung transaksi dan foreign key constraints. Database ini dirancang untuk sistem streaming drama dengan fitur rating dinamis, favorites, dan watch history.

## Database Information

- **Nama Database**: `new_film`
- **Character Set**: `utf8mb4`
- **Collation**: `utf8mb4_unicode_ci`
- **Engine**: InnoDB
- **Total Tables**: 7
- **Total Views**: 1

## Table of Contents

1. [Tabel-tabel](#tabel-tabel)
   - [roles](#1-roles)
   - [users](#2-users)
   - [drama](#3-drama)
   - [episodes](#4-episodes)
   - [ratings](#5-ratings-new)
   - [favorit](#6-favorit)
   - [users_history](#7-users_history)
2. [Views](#views)
   - [v_drama_ratings](#v_drama_ratings)
3. [Relationships](#relationships)
4. [Indexes](#indexes)
5. [Constraints](#constraints)
6. [Triggers](#triggers-optional)
7. [Sample Queries](#sample-queries)

---

## Tabel-tabel

### 1. `roles`

Menyimpan role/peran user dalam sistem.

**Schema:**

```sql
CREATE TABLE roles (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Columns:**

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik role |
| `name` | VARCHAR(50) | NOT NULL, UNIQUE | Nama role |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Waktu pembuatan |

**Default Values:**

```sql
INSERT INTO roles (name) VALUES
('user'),        -- ID: 1
('admin'),       -- ID: 2
('superadmin');  -- ID: 3
```

**Notes:**
- Role name harus unik
- Tidak boleh dihapus jika masih ada user dengan role tersebut

---

### 2. `users`

Menyimpan data user/pengguna sistem.

**Schema:**

```sql
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Columns:**

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik user |
| `username` | VARCHAR(50) | NOT NULL, UNIQUE | Username untuk login |
| `password` | VARCHAR(255) | NOT NULL | Password (hashed dengan bcrypt) |
| `role_id` | INT(11) | NOT NULL, FK to roles.id | Role user |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Waktu registrasi |

**Relationships:**
- `role_id` → `roles.id` (ON DELETE RESTRICT)

**Indexes:**
- PRIMARY KEY: `id`
- UNIQUE KEY: `username`
- FOREIGN KEY: `role_id`

**Security Notes:**
- Password HARUS di-hash menggunakan `password_hash()` dengan bcrypt
- Jangan pernah simpan password plaintext
- Username case-sensitive

**Sample Data:**

```sql
-- Password harus di-hash di aplikasi
INSERT INTO users (username, password, role_id) VALUES
('superadmin', '$2y$10$...', 3),
('admin', '$2y$10$...', 2),
('user', '$2y$10$...', 1);
```

---

### 3. `drama`

Menyimpan data drama Korea.

**Schema:**

```sql
CREATE TABLE drama (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    deskripsi TEXT NOT NULL,
    rilis_tahun YEAR NOT NULL,
    genre VARCHAR(100) NOT NULL,
    total_eps INT(11) NOT NULL DEFAULT 0,
    rating DECIMAL(2,1) DEFAULT 0 COMMENT 'DEPRECATED - gunakan tabel ratings',
    thumbnail VARCHAR(255) DEFAULT NULL,
    trailer VARCHAR(255) DEFAULT NULL,
    created_by INT(11) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Columns:**

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik drama |
| `title` | VARCHAR(255) | NOT NULL | Judul drama |
| `deskripsi` | TEXT | NOT NULL | Sinopsis/deskripsi drama |
| `rilis_tahun` | YEAR | NOT NULL | Tahun rilis (YYYY) |
| `genre` | VARCHAR(100) | NOT NULL | Genre drama (Romance, Action, dll) |
| `total_eps` | INT(11) | NOT NULL, DEFAULT 0 | Total episode |
| `rating` | DECIMAL(2,1) | DEFAULT 0 | **DEPRECATED** - pakai `ratings` table |
| `thumbnail` | VARCHAR(255) | NULLABLE | Path ke poster/thumbnail |
| `trailer` | VARCHAR(255) | NULLABLE | URL trailer (YouTube, dll) |
| `created_by` | INT(11) | NULLABLE, FK to users.id | Admin yang upload |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Waktu upload |

**Relationships:**
- `created_by` → `users.id` (ON DELETE SET NULL)

**Indexes:**
- PRIMARY KEY: `id`
- INDEX: `title` (untuk pencarian)
- INDEX: `genre` (untuk filter)
- INDEX: `rilis_tahun` (untuk sort)
- FOREIGN KEY: `created_by`

**Important Notes:**
- Field `rating` sudah DEPRECATED, gunakan tabel `ratings` untuk rating dinamis
- `thumbnail` path relatif dari root: `assets/uploads/posters/filename.jpg`
- `genre` bisa multiple genre dipisah koma: `Romance, Comedy, Drama`
- `total_eps` akan di-update otomatis saat episode ditambah/dihapus

**Sample Data:**

```sql
INSERT INTO drama (title, deskripsi, rilis_tahun, genre, total_eps, thumbnail, trailer, created_by) VALUES
('Crash Landing on You', 'A South Korean heiress crash lands in North Korea...', 2019, 'Romance, Comedy, Drama', 16, 'assets/uploads/posters/cloy.jpg', 'https://youtube.com/...', 2);
```

---

### 4. `episodes`

Menyimpan data episode per drama.

**Schema:**

```sql
CREATE TABLE episodes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_drama INT(11) NOT NULL,
    eps_number INT(11) NOT NULL,
    eps_title VARCHAR(255) NOT NULL,
    deskripsi TEXT DEFAULT NULL,
    durasi INT(11) NOT NULL COMMENT 'Durasi dalam menit',
    link_video VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_drama) REFERENCES drama(id) ON DELETE CASCADE,
    UNIQUE KEY unique_episode (id_drama, eps_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Columns:**

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik episode |
| `id_drama` | INT(11) | NOT NULL, FK to drama.id | Drama parent |
| `eps_number` | INT(11) | NOT NULL | Nomor episode (1, 2, 3, ...) |
| `eps_title` | VARCHAR(255) | NOT NULL | Judul episode |
| `deskripsi` | TEXT | NULLABLE | Sinopsis episode |
| `durasi` | INT(11) | NOT NULL | Durasi dalam menit |
| `link_video` | VARCHAR(255) | NOT NULL | Path ke file video |
| `thumbnail` | VARCHAR(255) | NULLABLE | Thumbnail episode |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Waktu upload |

**Relationships:**
- `id_drama` → `drama.id` (ON DELETE CASCADE)

**Indexes:**
- PRIMARY KEY: `id`
- UNIQUE KEY: `(id_drama, eps_number)` - mencegah duplicate episode number
- FOREIGN KEY: `id_drama`
- INDEX: `eps_number` (untuk sort)

**Important Notes:**
- Episode number harus unik per drama
- `link_video` path relatif: `assets/videos/drama-1/episode-1.mp4`
- `durasi` dalam menit, contoh: 60 untuk 1 jam
- Jika episode dihapus, `users_history` yang referensi ke episode ini akan di-cascade delete

**Sample Data:**

```sql
INSERT INTO episodes (id_drama, eps_number, eps_title, deskripsi, durasi, link_video, thumbnail) VALUES
(1, 1, 'Episode 1: The Crash', 'Yoon Se-ri accidentally paraglides into North Korea...', 70, 'assets/videos/cloy/ep1.mp4', 'assets/uploads/thumbnails/cloy-ep1.jpg');
```

---

### 5. `ratings` (NEW!)

Menyimpan rating yang diberikan user ke drama. Ini adalah fitur baru yang menggantikan rating statis di tabel `drama`.

**Schema:**

```sql
CREATE TABLE ratings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    drama_id INT(11) NOT NULL,
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 0 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (drama_id) REFERENCES drama(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_drama (user_id, drama_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Columns:**

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik rating |
| `user_id` | INT(11) | NOT NULL, FK to users.id | User yang memberi rating |
| `drama_id` | INT(11) | NOT NULL, FK to drama.id | Drama yang dirating |
| `rating` | DECIMAL(2,1) | NOT NULL, CHECK 0-5 | Nilai rating (0.0 - 5.0) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Waktu rating dibuat |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Waktu rating diupdate |

**Relationships:**
- `user_id` → `users.id` (ON DELETE CASCADE)
- `drama_id` → `drama.id` (ON DELETE CASCADE)

**Indexes:**
- PRIMARY KEY: `id`
- UNIQUE KEY: `(user_id, drama_id)` - 1 user hanya bisa beri 1 rating per drama
- FOREIGN KEY: `user_id`, `drama_id`
- INDEX: `drama_id` (untuk aggregate queries)

**Important Notes:**
- Rating range: 0.0 sampai 5.0 (dengan 1 desimal)
- Setiap user hanya bisa memberikan 1 rating per drama
- Jika user submit rating lagi, akan UPDATE rating yang sudah ada
- `updated_at` otomatis update saat rating diubah
- Gunakan view `v_drama_ratings` untuk mendapat rata-rata rating

**Business Rules:**
1. User harus login untuk bisa rating
2. Rating tidak bisa negatif atau > 5
3. Rating 0 = belum ditonton/tidak suka
4. Rating 5 = sangat bagus

**Sample Data:**

```sql
INSERT INTO ratings (user_id, drama_id, rating) VALUES
(1, 1, 5.0),  -- user 1 kasih rating 5.0 ke drama 1
(2, 1, 4.5),  -- user 2 kasih rating 4.5 ke drama 1
(1, 2, 3.0);  -- user 1 kasih rating 3.0 ke drama 2

-- Update rating
INSERT INTO ratings (user_id, drama_id, rating) VALUES (1, 1, 4.0)
ON DUPLICATE KEY UPDATE rating = VALUES(rating), updated_at = CURRENT_TIMESTAMP;
```

---

### 6. `favorit`

Menyimpan daftar drama favorit user.

**Schema:**

```sql
CREATE TABLE favorit (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    drama_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (drama_id) REFERENCES drama(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_drama (user_id, drama_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Columns:**

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik favorit |
| `user_id` | INT(11) | NOT NULL, FK to users.id | User yang favorit |
| `drama_id` | INT(11) | NOT NULL, FK to drama.id | Drama yang difavoritkan |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Waktu ditambah ke favorit |

**Relationships:**
- `user_id` → `users.id` (ON DELETE CASCADE)
- `drama_id` → `drama.id` (ON DELETE CASCADE)

**Indexes:**
- PRIMARY KEY: `id`
- UNIQUE KEY: `(user_id, drama_id)` - mencegah duplicate favorit
- FOREIGN KEY: `user_id`, `drama_id`

**Important Notes:**
- Satu drama hanya bisa difavoritkan 1x per user
- Toggle favorite: INSERT jika belum ada, DELETE jika sudah ada
- Jika drama dihapus, otomatis remove dari favorit semua user

**Sample Queries:**

```sql
-- Add to favorite
INSERT IGNORE INTO favorit (user_id, drama_id) VALUES (1, 1);

-- Remove from favorite
DELETE FROM favorit WHERE user_id = 1 AND drama_id = 1;

-- Toggle favorite
INSERT INTO favorit (user_id, drama_id) VALUES (1, 1)
ON DUPLICATE KEY UPDATE id = id;

-- Check if favorited
SELECT EXISTS(SELECT 1 FROM favorit WHERE user_id = 1 AND drama_id = 1) as is_favorited;

-- Get user's favorites
SELECT d.* FROM drama d
JOIN favorit f ON d.id = f.drama_id
WHERE f.user_id = 1
ORDER BY f.created_at DESC;
```

---

### 7. `users_history`

Menyimpan riwayat tontonan user (watch history).

**Schema:**

```sql
CREATE TABLE users_history (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    eps_id INT(11) NOT NULL,
    watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    progress INT(11) DEFAULT 0 COMMENT 'Progress dalam detik',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (eps_id) REFERENCES episodes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_episode (user_id, eps_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Columns:**

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik history |
| `user_id` | INT(11) | NOT NULL, FK to users.id | User yang nonton |
| `eps_id` | INT(11) | NOT NULL, FK to episodes.id | Episode yang ditonton |
| `watched_at` | TIMESTAMP | AUTO UPDATE | Waktu terakhir nonton |
| `progress` | INT(11) | DEFAULT 0 | Progress tontonan (detik) |

**Relationships:**
- `user_id` → `users.id` (ON DELETE CASCADE)
- `eps_id` → `episodes.id` (ON DELETE CASCADE)

**Indexes:**
- PRIMARY KEY: `id`
- UNIQUE KEY: `(user_id, eps_id)`
- FOREIGN KEY: `user_id`, `eps_id`
- INDEX: `watched_at` (untuk sort recent)

**Important Notes:**
- `progress` dalam detik, contoh: 1800 = 30 menit
- `watched_at` otomatis update setiap kali user nonton lagi
- UNIQUE constraint memastikan 1 user 1 record per episode
- Gunakan untuk fitur "Continue Watching"

**Sample Queries:**

```sql
-- Save watch history
INSERT INTO users_history (user_id, eps_id, progress) VALUES (1, 1, 1800)
ON DUPLICATE KEY UPDATE progress = VALUES(progress), watched_at = CURRENT_TIMESTAMP;

-- Get continue watching
SELECT e.*, d.title as drama_title, h.progress, h.watched_at
FROM users_history h
JOIN episodes e ON h.eps_id = e.id
JOIN drama d ON e.id_drama = d.id
WHERE h.user_id = 1
ORDER BY h.watched_at DESC
LIMIT 10;

-- Get watch history for specific drama
SELECT e.*, h.watched_at, h.progress
FROM users_history h
JOIN episodes e ON h.eps_id = e.id
WHERE h.user_id = 1 AND e.id_drama = 1
ORDER BY e.eps_number;
```

---

## Views

### `v_drama_ratings`

View untuk menghitung rata-rata rating dan total ratings per drama.

**Schema:**

```sql
CREATE OR REPLACE VIEW v_drama_ratings AS
SELECT
    d.id as drama_id,
    COALESCE(AVG(r.rating), 0) as avg_rating,
    COUNT(r.id) as total_ratings
FROM drama d
LEFT JOIN ratings r ON d.id = r.drama_id
GROUP BY d.id;
```

**Columns:**

| Column | Type | Description |
|--------|------|-------------|
| `drama_id` | INT(11) | ID drama |
| `avg_rating` | DECIMAL | Rata-rata rating (0.0 jika belum ada rating) |
| `total_ratings` | INT | Jumlah user yang kasih rating |

**Usage:**

```sql
-- Get drama with ratings
SELECT d.*, v.avg_rating, v.total_ratings
FROM drama d
LEFT JOIN v_drama_ratings v ON d.id = v.drama_id
ORDER BY v.avg_rating DESC;

-- Get top rated dramas
SELECT d.title, v.avg_rating, v.total_ratings
FROM drama d
JOIN v_drama_ratings v ON d.id = v.drama_id
WHERE v.total_ratings >= 5  -- minimal 5 ratings
ORDER BY v.avg_rating DESC
LIMIT 10;
```

---

## Relationships

### Entity Relationship Diagram

```
roles (1) ──< (many) users
users (1) ──< (many) drama [created_by]
drama (1) ──< (many) episodes
drama (1) ──< (many) ratings
drama (1) ──< (many) favorit
users (1) ──< (many) ratings
users (1) ──< (many) favorit
users (1) ──< (many) users_history
episodes (1) ──< (many) users_history
```

### Foreign Key Constraints

| Child Table | Column | Parent Table | Parent Column | ON DELETE |
|-------------|--------|--------------|---------------|-----------|
| users | role_id | roles | id | RESTRICT |
| drama | created_by | users | id | SET NULL |
| episodes | id_drama | drama | id | CASCADE |
| ratings | user_id | users | id | CASCADE |
| ratings | drama_id | drama | id | CASCADE |
| favorit | user_id | users | id | CASCADE |
| favorit | drama_id | drama | id | CASCADE |
| users_history | user_id | users | id | CASCADE |
| users_history | eps_id | episodes | id | CASCADE |

---

## Indexes

### Primary Keys
- Semua tabel memiliki PRIMARY KEY pada kolom `id`

### Unique Keys
- `roles.name`: Nama role unik
- `users.username`: Username unik
- `(episodes.id_drama, episodes.eps_number)`: Episode number unik per drama
- `(ratings.user_id, ratings.drama_id)`: 1 user 1 rating per drama
- `(favorit.user_id, favorit.drama_id)`: 1 drama 1 favorit per user
- `(users_history.user_id, users_history.eps_id)`: 1 record per episode per user

### Foreign Keys
- Semua foreign key sudah dibuat index otomatis oleh MySQL

### Additional Indexes (Recommended)

```sql
-- Speed up search
CREATE INDEX idx_drama_title ON drama(title);
CREATE INDEX idx_drama_genre ON drama(genre);
CREATE INDEX idx_drama_year ON drama(rilis_tahun);

-- Speed up sort
CREATE INDEX idx_episodes_number ON episodes(eps_number);
CREATE INDEX idx_history_watched ON users_history(watched_at);

-- Speed up aggregate
CREATE INDEX idx_ratings_drama ON ratings(drama_id);
```

---

## Constraints

### Check Constraints

```sql
-- Rating must be between 0 and 5
ALTER TABLE ratings ADD CONSTRAINT chk_rating_range
CHECK (rating >= 0 AND rating <= 5);

-- Episode number must be positive
ALTER TABLE episodes ADD CONSTRAINT chk_eps_number_positive
CHECK (eps_number > 0);

-- Total episodes must be non-negative
ALTER TABLE drama ADD CONSTRAINT chk_total_eps_non_negative
CHECK (total_eps >= 0);

-- Duration must be positive
ALTER TABLE episodes ADD CONSTRAINT chk_durasi_positive
CHECK (durasi > 0);
```

---

## Triggers (Optional)

### Auto-update `drama.total_eps`

```sql
-- Trigger when episode inserted
DELIMITER $$
CREATE TRIGGER after_episode_insert
AFTER INSERT ON episodes
FOR EACH ROW
BEGIN
    UPDATE drama
    SET total_eps = (SELECT COUNT(*) FROM episodes WHERE id_drama = NEW.id_drama)
    WHERE id = NEW.id_drama;
END$$

-- Trigger when episode deleted
CREATE TRIGGER after_episode_delete
AFTER DELETE ON episodes
FOR EACH ROW
BEGIN
    UPDATE drama
    SET total_eps = (SELECT COUNT(*) FROM episodes WHERE id_drama = OLD.id_drama)
    WHERE id = OLD.id_drama;
END$$
DELIMITER ;
```

---

## Sample Queries

### 1. Get Drama with Full Info

```sql
SELECT
    d.*,
    u.username as creator_name,
    COUNT(DISTINCT e.id) as episode_count,
    COALESCE(AVG(r.rating), 0) as avg_rating,
    COUNT(DISTINCT r.id) as total_ratings,
    COUNT(DISTINCT f.id) as total_favorites
FROM drama d
LEFT JOIN users u ON d.created_by = u.id
LEFT JOIN episodes e ON d.id = e.id_drama
LEFT JOIN ratings r ON d.id = r.drama_id
LEFT JOIN favorit f ON d.id = f.drama_id
WHERE d.id = 1
GROUP BY d.id;
```

### 2. Search Drama by Title and Genre

```sql
SELECT d.*,
       COALESCE(AVG(r.rating), 0) as avg_rating,
       COUNT(DISTINCT r.id) as total_ratings
FROM drama d
LEFT JOIN ratings r ON d.id = r.drama_id
WHERE d.title LIKE '%love%'
  AND d.genre LIKE '%Romance%'
GROUP BY d.id
ORDER BY avg_rating DESC;
```

### 3. Get User's Watch History with Drama Info

```sql
SELECT
    h.watched_at,
    h.progress,
    e.eps_number,
    e.eps_title,
    e.durasi,
    d.title as drama_title,
    d.thumbnail as drama_thumbnail
FROM users_history h
JOIN episodes e ON h.eps_id = e.id
JOIN drama d ON e.id_drama = d.id
WHERE h.user_id = 1
ORDER BY h.watched_at DESC
LIMIT 20;
```

### 4. Get Top Rated Dramas

```sql
SELECT
    d.title,
    d.rilis_tahun,
    d.genre,
    ROUND(AVG(r.rating), 1) as avg_rating,
    COUNT(r.id) as total_ratings
FROM drama d
JOIN ratings r ON d.id = r.drama_id
GROUP BY d.id
HAVING total_ratings >= 5  -- minimal 5 ratings
ORDER BY avg_rating DESC, total_ratings DESC
LIMIT 10;
```

### 5. Get User's Favorites with Ratings

```sql
SELECT
    d.*,
    f.created_at as favorited_at,
    COALESCE(AVG(r.rating), 0) as avg_rating,
    COUNT(DISTINCT r.id) as total_ratings,
    ur.rating as my_rating
FROM favorit f
JOIN drama d ON f.drama_id = d.id
LEFT JOIN ratings r ON d.id = r.drama_id
LEFT JOIN ratings ur ON d.id = ur.drama_id AND ur.user_id = f.user_id
WHERE f.user_id = 1
GROUP BY d.id
ORDER BY f.created_at DESC;
```

### 6. Get Drama Episodes with Watch Progress

```sql
SELECT
    e.*,
    h.watched_at,
    h.progress,
    CASE
        WHEN h.progress IS NOT NULL THEN 1
        ELSE 0
    END as is_watched,
    ROUND(h.progress / (e.durasi * 60) * 100, 2) as watch_percentage
FROM episodes e
LEFT JOIN users_history h ON e.id = h.eps_id AND h.user_id = 1
WHERE e.id_drama = 1
ORDER BY e.eps_number;
```

### 7. Get Statistics

```sql
-- Total statistics
SELECT
    (SELECT COUNT(*) FROM users WHERE role_id = 1) as total_users,
    (SELECT COUNT(*) FROM drama) as total_dramas,
    (SELECT COUNT(*) FROM episodes) as total_episodes,
    (SELECT COUNT(*) FROM ratings) as total_ratings,
    (SELECT ROUND(AVG(rating), 2) FROM ratings) as overall_avg_rating;

-- User activity statistics
SELECT
    u.username,
    COUNT(DISTINCT h.eps_id) as episodes_watched,
    COUNT(DISTINCT f.drama_id) as dramas_favorited,
    COUNT(DISTINCT r.drama_id) as dramas_rated,
    ROUND(AVG(r.rating), 2) as avg_rating_given
FROM users u
LEFT JOIN users_history h ON u.id = h.user_id
LEFT JOIN favorit f ON u.id = f.user_id
LEFT JOIN ratings r ON u.id = r.user_id
WHERE u.role_id = 1
GROUP BY u.id
ORDER BY episodes_watched DESC;
```

---

## Backup & Restore

### Backup Database

```bash
# Full backup
mysqldump -u root -p new_film > backup_$(date +%Y%m%d).sql

# Backup structure only
mysqldump -u root -p --no-data new_film > schema.sql

# Backup data only
mysqldump -u root -p --no-create-info new_film > data.sql
```

### Restore Database

```bash
# Restore from backup
mysql -u root -p new_film < backup_20231027.sql
```

---

## Performance Optimization

### 1. Add Indexes

```sql
-- For search
CREATE INDEX idx_drama_search ON drama(title, genre);

-- For sorting
CREATE INDEX idx_drama_year_rating ON drama(rilis_tahun, rating);

-- For filtering
CREATE INDEX idx_episodes_drama_number ON episodes(id_drama, eps_number);
```

### 2. Optimize Queries

- Use EXPLAIN untuk analyze query performance
- Hindari SELECT * jika tidak perlu semua column
- Gunakan LIMIT untuk pagination
- Cache hasil query yang sering diakses

### 3. Regular Maintenance

```sql
-- Optimize tables
OPTIMIZE TABLE drama, episodes, ratings, favorit, users_history;

-- Analyze tables
ANALYZE TABLE drama, episodes, ratings, favorit, users_history;

-- Check table status
SHOW TABLE STATUS LIKE 'drama';
```

---

## Migration Notes

### From Old Rating System to New Rating System

Jika sebelumnya menggunakan field `drama.rating` yang diisi admin:

```sql
-- Backup old ratings
ALTER TABLE drama ADD COLUMN old_rating DECIMAL(2,1);
UPDATE drama SET old_rating = rating;

-- Migrate to new system (optional)
-- Anggap rating lama = rating dari admin (user_id = 2)
INSERT INTO ratings (user_id, drama_id, rating, created_at)
SELECT 2, id, rating, created_at
FROM drama
WHERE rating > 0
ON DUPLICATE KEY UPDATE rating = VALUES(rating);

-- Update drama.rating to 0 (deprecated)
UPDATE drama SET rating = 0;
```

---

## Troubleshooting

### Common Issues

**1. Duplicate Entry Error**

```
Error: Duplicate entry 'xxx' for key 'unique_user_drama'
```

**Solution:** Use `INSERT ... ON DUPLICATE KEY UPDATE` atau `INSERT IGNORE`

**2. Foreign Key Constraint Fails**

```
Error: Cannot add or update a child row: a foreign key constraint fails
```

**Solution:** Pastikan parent record ada sebelum insert child record

**3. View Not Updating**

**Solution:** Drop dan recreate view

```sql
DROP VIEW IF EXISTS v_drama_ratings;
CREATE OR REPLACE VIEW v_drama_ratings AS ...;
```

---

## Changelog

### Version 1.1.0 (Current)
- Added `ratings` table for user-driven rating system
- Added `v_drama_ratings` view
- Deprecated `drama.rating` field
- Added `drama.created_by` field
- Added `episodes.thumbnail` field
- Added unique constraints for better data integrity

### Version 1.0.0 (Initial)
- Initial database schema
- Basic tables: roles, users, drama, episodes, favorit, users_history

---

**Last Updated:** 2024-10-31
**Database Version:** 1.1.0
