# Dokumentasi Fitur BKDrama

## Overview

Dokumen ini menjelaskan secara detail semua fitur yang tersedia di platform BKDrama, cara penggunaannya, dan implementasi teknisnya.

---

## Table of Contents

1. [Authentication & Authorization](#1-authentication--authorization)
2. [Drama Management](#2-drama-management)
3. [Episode Management](#3-episode-management)
4. [Rating System](#4-rating-system)
5. [Favorites](#5-favorites)
6. [Watch History & Continue Watching](#6-watch-history--continue-watching)
7. [Video Player](#7-video-player)
8. [Search & Filter](#8-search--filter)
9. [User Management](#9-user-management-superadmin)
10. [Admin Dashboard](#10-admin-dashboard)

---

## 1. Authentication & Authorization

### 1.1 Register

**File:** `register.php`

**Features:**
- Username harus unik
- Password di-hash menggunakan bcrypt (`password_hash()`)
- Default role: User (role_id = 1)
- Validasi input untuk mencegah SQL injection
- Auto-login setelah register berhasil

**Flow:**
```
User mengisi form → Validasi input → Check username duplicate
→ Hash password → Insert ke database → Create session → Redirect ke dashboard
```

**Security:**
- Password minimal 6 karakter
- Username hanya alphanumeric dan underscore
- Prepared statements untuk query
- XSS protection dengan `htmlspecialchars()`

**Usage:**

```php
// Form HTML
<form method="POST" action="register.php">
    <input type="text" name="username" required>
    <input type="password" name="password" required>
    <button type="submit">Register</button>
</form>

// Backend processing
$username = sanitizeInput($_POST['username']);
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);
$role_id = 1; // Default user role
```

---

### 1.2 Login

**File:** `login.php`

**Features:**
- Authentication menggunakan username & password
- Password verification dengan `password_verify()`
- Session management
- Remember user info in session
- Role-based redirect (admin → admin panel, user → dashboard)

**Flow:**
```
User input credentials → Query database → Verify password
→ Create session → Store user info → Redirect based on role
```

**Session Variables:**
```php
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role_id'] = $user['role_id'];
$_SESSION['role_name'] = $user['role_name'];
```

**Security:**
- Brute force protection (optional: implementasi rate limiting)
- Secure password hashing
- Session hijacking prevention dengan session regeneration

---

### 1.3 Authorization

**File:** `includes/auth.php`

**Functions:**

```php
// Check if user logged in
isLoggedIn(): bool

// Get current user ID
getUserId(): int

// Get current user role ID
getUserRole(): int

// Check if user has specific role
hasRole($role): bool

// Require specific role or redirect
requireRole($roles): void
```

**Role Levels:**
1. **User (role_id = 1)**: Browse, watch, rate, favorite
2. **Admin (role_id = 2)**: All user features + manage drama/episodes
3. **Superadmin (role_id = 3)**: All admin features + manage users

**Usage Example:**

```php
// Require login
requireAuth();

// Require admin or superadmin
requireRole(['admin', 'superadmin']);

// Check role manually
if (hasRole('admin')) {
    // Show admin menu
}
```

---

## 2. Drama Management

### 2.1 Browse Drama

**File:** `movies.php`

**Features:**
- Grid view dengan poster thumbnail
- Filter by genre (Romance, Action, Comedy, etc.)
- Search by title
- Sort by: Latest, Rating, Title
- Pagination (optional)
- Show rating dan total episodes
- Quick favorite button

**Display Information:**
- Drama title
- Genre
- Release year
- Average rating (dari user)
- Total ratings
- Episode count
- Poster image

**Implementation:**

```php
// Query dengan join ratings
$query = "SELECT d.*,
          COUNT(DISTINCT e.id) as episode_count,
          COALESCE(AVG(r.rating), 0) as avg_rating,
          COUNT(DISTINCT r.id) as total_ratings
          FROM drama d
          LEFT JOIN episodes e ON d.id = e.id_drama
          LEFT JOIN ratings r ON d.id = r.drama_id
          WHERE 1=1";

// Filter genre
if (!empty($genre_filter)) {
    $query .= " AND d.genre LIKE :genre";
}

// Search
if (!empty($search)) {
    $query .= " AND d.title LIKE :search";
}

$query .= " GROUP BY d.id ORDER BY d.created_at DESC";
```

---

### 2.2 Drama Detail

**File:** `watchlist.php`

**Features:**
- Full drama information
- Episode list dengan thumbnail
- Rating widget (5 stars)
- Favorite button
- Trailer embed (YouTube)
- Continue watching suggestion
- Related drama (same genre)

**Information Displayed:**
- Title, year, genre
- Synopsis
- Average rating & distribution
- User's rating
- Total episodes
- Creator/uploader name
- Trailer video

**Episode List:**
- Episode number & title
- Duration
- Thumbnail preview
- Watch progress indicator
- "Continue" badge jika belum selesai
- "Completed" badge jika sudah selesai

---

### 2.3 Add Drama (Admin)

**File:** `admin/add-movies.php`

**Features:**
- AJAX form submission
- File upload untuk poster
- Drag & drop upload (optional)
- Preview poster before upload
- Year picker
- Genre selection (multiple)
- Trailer URL input
- Rich text editor untuk deskripsi (optional)

**Validation:**
- Title required
- Year must be valid (1900 - current year)
- Genre required
- Description required
- Poster max 5MB
- Supported formats: JPG, PNG, WebP, GIF

**Implementation:**

```php
// File upload
$poster_path = '';
if (!empty($_FILES['poster_file']['name'])) {
    $upload_dir = '../assets/uploads/posters/';
    $file_extension = pathinfo($_FILES['poster_file']['name'], PATHINFO_EXTENSION);
    $new_filename = 'drama_' . time() . '.' . $file_extension;
    $poster_path = $upload_dir . $new_filename;
    move_uploaded_file($_FILES['poster_file']['tmp_name'], $poster_path);
}

// Insert drama
$query = "INSERT INTO drama (title, deskripsi, rilis_tahun, genre,
          thumbnail, trailer, rating, created_by, created_at)
          VALUES (?, ?, ?, ?, ?, ?, 0, ?, NOW())";
```

**Success Flow:**
```
Form submit → Validate input → Upload poster → Insert to DB
→ Return JSON response → Show success message → Redirect to manage drama
```

---

### 2.4 Edit Drama (Admin)

**File:** `admin/edit-movies.php`

**Features:**
- Pre-filled form dengan data existing
- Update poster (optional, keep old if not changed)
- Preview current poster
- Delete poster option
- Update all fields except rating (rating dari user)
- Confirmation before save

**Important:**
- Rating TIDAK bisa diubah oleh admin
- Rating otomatis dihitung dari tabel `ratings`
- Total episodes otomatis update saat add/delete episode

---

### 2.5 Delete Drama (Admin)

**File:** `admin/delete-movies.php`

**Features:**
- Confirmation modal before delete
- Cascade delete:
  - Semua episodes drama ini
  - Semua ratings drama ini
  - Semua favorites drama ini
  - Semua watch history yang terkait
- Delete uploaded poster file
- Cannot be undone warning

**Flow:**
```
Click delete → Show confirmation → User confirm → Delete from DB (cascade)
→ Delete poster file → Redirect with success message
```

---

## 3. Episode Management

### 3.1 View Episodes

**File:** `admin/manage-episodes.php`

**Features:**
- List all episodes untuk drama tertentu
- Show episode info:
  - Episode number
  - Title
  - Duration
  - Thumbnail
  - Upload date
- Sort by episode number
- Quick actions: Edit, Delete
- Add new episode button

**Implementation:**

```php
$query = "SELECT e.* FROM episodes e
          WHERE e.id_drama = :drama_id
          ORDER BY e.eps_number ASC";
```

---

### 3.2 Add Episode (Admin)

**File:** `admin/add-episode.php`

**Features:**
- Auto-generate episode number (next available)
- Manual episode number input
- Upload thumbnail
- Upload video file OR input video URL
- Duration input (menit)
- Episode description
- Progress bar saat upload video
- Duplicate episode number detection

**Validation:**
- Episode number must be unique per drama
- Title required
- Duration must be positive
- Video required (file or URL)
- Thumbnail max 5MB
- Video max 500MB (configurable)

**Video Upload:**

```php
$video_path = '';
if (!empty($_FILES['video_file']['name'])) {
    $upload_dir = "../assets/videos/drama_{$drama_id}/";

    // Create directory if not exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $video_filename = "ep{$eps_number}_" . time() . ".mp4";
    $video_path = $upload_dir . $video_filename;

    move_uploaded_file($_FILES['video_file']['tmp_name'], $video_path);
}
```

**AJAX Upload Progress:**

```javascript
const xhr = new XMLHttpRequest();
xhr.upload.addEventListener('progress', (e) => {
    if (e.lengthComputable) {
        const percentComplete = (e.loaded / e.total) * 100;
        progressBar.style.width = percentComplete + '%';
    }
});
```

---

### 3.3 Edit Episode (Admin)

**File:** `admin/edit-episode.php`

**Features:**
- Pre-filled form
- Update episode number (with duplicate check)
- Update title, description, duration
- Replace video/thumbnail (optional)
- Preview current thumbnail

---

### 3.4 Delete Episode (Admin)

**File:** `admin/delete-episode.php`

**Features:**
- Confirmation before delete
- Delete episode from database
- Delete video file from server
- Delete thumbnail file from server
- Auto-update drama's `total_eps`
- Cascade delete watch history

**Implementation:**

```php
// Get episode info
$episode = getEpisodeById($episode_id);

// Delete video file
if (file_exists($episode['link_video'])) {
    unlink($episode['link_video']);
}

// Delete thumbnail
if (file_exists($episode['thumbnail'])) {
    unlink($episode['thumbnail']);
}

// Delete from database (cascade to users_history)
$query = "DELETE FROM episodes WHERE id = ?";
$stmt->execute([$episode_id]);

// Update drama total_eps
$update_query = "UPDATE drama SET total_eps = total_eps - 1 WHERE id = ?";
```

---

## 4. Rating System

### 4.1 User Rating

**Files:** `watchlist.php`, `api/rate-drama.php`

**Features:**
- 5-star rating system
- Click star to rate (1-5)
- Half-star support (optional)
- Show user's current rating
- Show average rating dari semua user
- Show total users yang rating
- Real-time update setelah submit
- One rating per user per drama
- Update rating jika user rating lagi

**UI Components:**
```html
<div class="rating-widget">
    <div class="stars">
        <span class="star" data-rating="1">★</span>
        <span class="star" data-rating="2">★</span>
        <span class="star" data-rating="3">★</span>
        <span class="star" data-rating="4">★</span>
        <span class="star" data-rating="5">★</span>
    </div>
    <p class="avg-rating">⭐ 4.5 / 5.0 (120 ratings)</p>
    <p class="user-rating">Your rating: ⭐ 5.0</p>
</div>
```

**JavaScript Implementation:**

```javascript
document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.dataset.rating;
        const dramaId = this.closest('.rating-widget').dataset.dramaId;

        fetch('/api/rate-drama.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ drama_id: dramaId, rating: rating }),
            credentials: 'include'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateRatingDisplay(data.avg_rating, data.total_ratings, rating);
            }
        });
    });
});
```

**Database:**

```sql
-- Insert or update rating
INSERT INTO ratings (user_id, drama_id, rating)
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE
    rating = VALUES(rating),
    updated_at = CURRENT_TIMESTAMP;
```

---

### 4.2 Rating Display

**Display Format:**
- Card view: "⭐ 4.5 (120)"
- Detail view: "⭐ 4.5 / 5.0 (120 ratings)"
- Admin view: "⭐ 4.5 (120 users)"

**Color Coding (Optional):**
- 0.0 - 2.0: Red
- 2.1 - 3.5: Orange
- 3.6 - 4.0: Yellow
- 4.1 - 5.0: Green/Gold

**Rating Statistics (Optional Enhancement):**
```
5 ★ ████████████████████ 80 (67%)
4 ★ ████████░░░░░░░░░░░░ 20 (17%)
3 ★ ████░░░░░░░░░░░░░░░░ 10 (8%)
2 ★ ██░░░░░░░░░░░░░░░░░░ 5 (4%)
1 ★ ██░░░░░░░░░░░░░░░░░░ 5 (4%)
```

---

## 5. Favorites

### 5.1 Add to Favorites

**Files:** `watchlist.php`, `api/toggle-favorite.php`

**Features:**
- Click heart icon to add favorite
- Toggle on/off
- Visual feedback (heart filled/empty)
- Toast notification "Added to favorites"
- No page reload

**Implementation:**

```javascript
function toggleFavorite(dramaId) {
    const isFavorited = checkIfFavorited(dramaId);
    const action = isFavorited ? 'remove' : 'add';

    fetch('/api/toggle-favorite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: action, drama_id: dramaId }),
        credentials: 'include'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateHeartIcon(dramaId, action === 'add');
            showToast(data.message);
        }
    });
}
```

---

### 5.2 View Favorites

**File:** `favorites.php`

**Features:**
- Grid view mirip browse drama
- Show all favorited drama
- Quick remove from favorites
- Show rating & episode count
- Sort options: Recent, Title, Rating
- Empty state jika belum ada favorites

**Query:**

```php
$query = "SELECT d.*, f.created_at as favorited_at,
          COUNT(DISTINCT e.id) as episode_count,
          COALESCE(AVG(r.rating), 0) as avg_rating,
          COUNT(DISTINCT r.id) as total_ratings
          FROM favorit f
          JOIN drama d ON f.drama_id = d.id
          LEFT JOIN episodes e ON d.id = e.id_drama
          LEFT JOIN ratings r ON d.id = r.drama_id
          WHERE f.user_id = ?
          GROUP BY d.id
          ORDER BY f.created_at DESC";
```

---

## 6. Watch History & Continue Watching

### 6.1 Auto-Save Watch Progress

**Files:** `watch.php`, `api/watch-history-api.php`

**Features:**
- Auto-save progress setiap 10 detik
- Save progress saat pause
- Save progress saat close browser
- Track watched duration (seconds)
- Mark as completed jika ≥90% ditonton
- Timestamp last watched

**Implementation:**

```javascript
let videoPlayer = document.getElementById('video');
let saveInterval;

videoPlayer.addEventListener('loadedmetadata', function() {
    // Load saved progress
    loadWatchProgress(episodeId).then(data => {
        if (data.watched_duration > 0) {
            videoPlayer.currentTime = data.watched_duration;
            showResumeNotification();
        }
    });

    // Auto-save every 10 seconds
    saveInterval = setInterval(() => {
        saveWatchProgress(
            dramaId,
            episodeId,
            Math.floor(videoPlayer.currentTime),
            Math.floor(videoPlayer.duration)
        );
    }, 10000);
});

videoPlayer.addEventListener('pause', function() {
    saveWatchProgress(dramaId, episodeId,
        Math.floor(videoPlayer.currentTime),
        Math.floor(videoPlayer.duration)
    );
});

// Save before page unload
window.addEventListener('beforeunload', function() {
    clearInterval(saveInterval);
    saveWatchProgress(dramaId, episodeId,
        Math.floor(videoPlayer.currentTime),
        Math.floor(videoPlayer.duration)
    );
});
```

---

### 6.2 Continue Watching

**File:** `continue-watching.php`

**Features:**
- Show episodes yang belum selesai ditonton
- Display progress bar untuk setiap episode
- Show last watched timestamp
- Click to resume from last position
- Sort by most recent first
- Limit: Show last 20 items

**UI Display:**

```html
<div class="continue-watching-item">
    <img src="drama_thumbnail.jpg">
    <div class="info">
        <h3>Drama Title</h3>
        <p>Episode 5: The Conflict</p>
        <div class="progress-bar">
            <div class="progress" style="width: 45%"></div>
        </div>
        <p class="last-watched">Last watched: 2 hours ago</p>
        <a href="watch.php?id=5" class="btn-continue">Continue Watching</a>
    </div>
</div>
```

**Query:**

```php
$query = "SELECT
            uh.eps_id, uh.progress, uh.last_watched,
            e.id_drama, e.eps_number, e.eps_title, e.durasi, e.thumbnail,
            d.title as drama_title, d.thumbnail as drama_poster
          FROM users_history uh
          JOIN episodes e ON uh.eps_id = e.id
          JOIN drama d ON e.id_drama = d.id
          WHERE uh.user_id = ?
            AND uh.completed = 0
            AND uh.progress > 0
          ORDER BY uh.last_watched DESC
          LIMIT 20";
```

---

### 6.3 Watch History

**File:** `dashboard.php` (section)

**Features:**
- Show all watched episodes (completed + in progress)
- Group by drama
- Show completion percentage per drama
- Show total watch time
- Filter: All, Completed, In Progress
- Clear history button

**Statistics:**
```
📊 Total Drama Watched: 15
✅ Completed: 8
⏳ In Progress: 7
⏱️ Total Watch Time: 45 hours 30 minutes
```

---

## 7. Video Player

### 7.1 Player Features

**File:** `watch.php`

**Features:**
- HTML5 video player
- Custom controls (play, pause, volume, fullscreen)
- Seek bar with preview
- Keyboard shortcuts:
  - Space: Play/Pause
  - Arrow Left/Right: Seek -10s/+10s
  - Arrow Up/Down: Volume up/down
  - F: Fullscreen
  - M: Mute
- Auto-resume from last position
- Next episode button
- Episode playlist sidebar
- Quality selector (optional)
- Playback speed control

**Implementation:**

```javascript
class VideoPlayer {
    constructor(videoElement) {
        this.video = videoElement;
        this.initControls();
        this.initKeyboardShortcuts();
        this.loadProgress();
    }

    initControls() {
        // Play/Pause button
        this.playBtn.addEventListener('click', () => {
            if (this.video.paused) {
                this.video.play();
            } else {
                this.video.pause();
            }
        });

        // Seek bar
        this.seekBar.addEventListener('input', (e) => {
            const time = (e.target.value / 100) * this.video.duration;
            this.video.currentTime = time;
        });

        // Update seek bar as video plays
        this.video.addEventListener('timeupdate', () => {
            const value = (100 / this.video.duration) * this.video.currentTime;
            this.seekBar.value = value;
            this.updateTimeDisplay();
        });

        // Fullscreen
        this.fullscreenBtn.addEventListener('click', () => {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                this.video.requestFullscreen();
            }
        });
    }

    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            switch(e.key) {
                case ' ':
                    e.preventDefault();
                    this.video.paused ? this.video.play() : this.video.pause();
                    break;
                case 'ArrowLeft':
                    this.video.currentTime -= 10;
                    break;
                case 'ArrowRight':
                    this.video.currentTime += 10;
                    break;
                case 'ArrowUp':
                    this.video.volume = Math.min(1, this.video.volume + 0.1);
                    break;
                case 'ArrowDown':
                    this.video.volume = Math.max(0, this.video.volume - 0.1);
                    break;
                case 'f':
                    this.video.requestFullscreen();
                    break;
                case 'm':
                    this.video.muted = !this.video.muted;
                    break;
            }
        });
    }

    loadProgress() {
        fetch(`/api/watch-history-api.php?action=get_progress&episode_id=${episodeId}`)
        .then(r => r.json())
        .then(data => {
            if (data.data.watched_duration > 0) {
                this.video.currentTime = data.data.watched_duration;
                this.showResumeNotification();
            }
        });
    }
}

const player = new VideoPlayer(document.getElementById('video'));
```

---

### 7.2 Episode Playlist

**Features:**
- Show all episodes for current drama
- Highlight current episode
- Click to switch episode
- Show watched/unwatched indicator
- Auto-scroll to current episode

---

## 8. Search & Filter

### 8.1 Search

**File:** `movies.php`

**Features:**
- Real-time search (AJAX)
- Search by title
- Search suggestions/autocomplete
- Highlight search term in results
- No results message

**Implementation:**

```javascript
let searchTimeout;
const searchInput = document.getElementById('search');

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        const query = this.value;

        if (query.length >= 2) {
            searchDrama(query);
        } else {
            showAllDrama();
        }
    }, 300); // Debounce 300ms
});

function searchDrama(query) {
    fetch(`/api/search-drama.php?q=${encodeURIComponent(query)}`)
    .then(r => r.json())
    .then(data => {
        displaySearchResults(data.results);
    });
}
```

---

### 8.2 Genre Filter

**Features:**
- Filter by genre (Romance, Action, Comedy, Drama, Fantasy, Thriller, Horror)
- Multiple genre selection
- Show drama count per genre
- Clear filter button

**Implementation:**

```php
// Get unique genres
$genre_query = "SELECT DISTINCT genre FROM drama ORDER BY genre";
$genres = $db->query($genre_query)->fetchAll();

// Filter query
$query = "SELECT * FROM drama WHERE 1=1";
if (!empty($genre_filter)) {
    $query .= " AND genre LIKE :genre";
    $params[':genre'] = "%{$genre_filter}%";
}
```

---

### 8.3 Sort Options

**Features:**
- Sort by: Latest, Rating (High to Low), Title (A-Z), Year (Newest)
- Dropdown selector
- Remember last sort preference (localStorage)

**Implementation:**

```javascript
const sortSelect = document.getElementById('sort');

sortSelect.addEventListener('change', function() {
    const sortBy = this.value;
    localStorage.setItem('drama_sort', sortBy);
    loadDrama(sortBy);
});

// Load saved preference
const savedSort = localStorage.getItem('drama_sort') || 'latest';
sortSelect.value = savedSort;
```

---

## 9. User Management (Superadmin)

**File:** `admin/manage-users.php`

**Features:**
- List all users dengan role
- Search user by username
- Edit user role (promote/demote)
- Delete user
- Create new user
- Reset user password
- Ban/Unban user (optional)

**Capabilities:**
- View user statistics (total watched, total ratings)
- Export user list to CSV
- Bulk actions (delete multiple users)

**Important:**
- Superadmin tidak bisa delete diri sendiri
- Minimal 1 superadmin harus tetap ada

---

## 10. Admin Dashboard

**File:** `admin/index.php`

**Features:**
- Statistics cards:
  - Total drama
  - Total episodes
  - Total users
  - Total ratings
- Recent drama (last 5)
- Recent uploads (last 5 episodes)
- Top rated drama (top 5)
- Most favorited drama (top 5)
- Recent users (last 5)
- Charts (optional):
  - Drama per genre (pie chart)
  - Uploads per month (line chart)
  - User registration trend (line chart)

**Implementation:**

```php
// Statistics
$stats = [
    'total_drama' => $db->query("SELECT COUNT(*) FROM drama")->fetchColumn(),
    'total_episodes' => $db->query("SELECT COUNT(*) FROM episodes")->fetchColumn(),
    'total_users' => $db->query("SELECT COUNT(*) FROM users WHERE role_id = 1")->fetchColumn(),
    'total_ratings' => $db->query("SELECT COUNT(*) FROM ratings")->fetchColumn()
];

// Recent drama
$recent_drama = $db->query("
    SELECT d.*, COALESCE(AVG(r.rating), 0) as avg_rating
    FROM drama d
    LEFT JOIN ratings r ON d.id = r.drama_id
    GROUP BY d.id
    ORDER BY d.created_at DESC
    LIMIT 5
")->fetchAll();
```

---

## Feature Roadmap (Future)

### Phase 2
- [ ] Email verification saat register
- [ ] Forgot password via email
- [ ] Social login (Google, Facebook)
- [ ] Multi-language support
- [ ] Dark mode
- [ ] PWA (Progressive Web App)

### Phase 3
- [ ] Comments & Reviews
- [ ] User profile customization
- [ ] Watchlist (planned to watch)
- [ ] Recommendations based on watch history
- [ ] Notifications (new episodes, new drama)

### Phase 4
- [ ] Subtitle support (.srt, .vtt)
- [ ] Multiple video quality (360p, 480p, 720p, 1080p)
- [ ] Download episode untuk offline viewing
- [ ] Chromecast support
- [ ] Mobile app (React Native)

---

## Accessibility

- Keyboard navigation support
- ARIA labels for screen readers
- High contrast mode
- Focus indicators
- Alt text untuk images

---

## Performance Optimization

- Lazy loading images
- Video thumbnail generation
- Database query optimization
- CDN untuk static assets
- Caching (Redis/Memcached)
- Minify CSS/JS
- Image compression

---

**Last Updated:** 2024-10-31
**Version:** 1.1.0
