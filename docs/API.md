# API Documentation - BKDrama

## Overview

BKDrama menyediakan REST API untuk berbagai operasi seperti rating drama, mengelola favorites, dan tracking watch history. Semua API endpoint menggunakan JSON sebagai format data.

## Base URL

```
http://localhost/Bkdrama/api/
```

## Authentication

Semua API endpoint memerlukan authentication kecuali dinyatakan sebaliknya. Authentication menggunakan PHP Session.

**Requirements:**

- User harus login terlebih dahulu
- Session cookie harus disertakan dalam request (masih default)

**Response jika Unauthorized:**

```json
{
  "success": false,
  "message": "Unauthorized"
}
```

HTTP Status: `401 Unauthorized`

---

## Table of Contents

1. [Rating API](#rating-api)
   - [Submit Rating](#1-submit-rating)
   - [Get User Rating](#2-get-user-rating)
2. [Favorites API](#favorites-api)
   - [Toggle Favorite](#3-toggle-favorite)
3. [Watch History API](#watch-history-api)
   - [Save Watch Progress](#4-save-watch-progress)
   - [Get Watch Progress](#5-get-watch-progress)
   - [Get Continue Watching](#6-get-continue-watching)
   - [Get Drama History](#7-get-drama-history)
   - [Delete Watch History](#8-delete-watch-history)

---

## Rating API

### 1. Submit Rating

Submit atau update rating untuk drama.

**Endpoint:** `POST /api/rate-drama.php`

**Authentication:** Required

**Parameters:**

| Parameter  | Type    | Required | Description                 |
| ---------- | ------- | -------- | --------------------------- |
| `drama_id` | integer | Yes      | ID drama yang akan dirating |
| `rating`   | float   | Yes      | Rating 0.0 - 5.0            |

**Request Example:**

```bash
curl -X POST http://localhost/Bkdrama/api/rate-drama.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "drama_id=1&rating=4.5" \
  --cookie "PHPSESSID=your_session_id"
```

**JavaScript Example:**

```javascript
fetch("/api/rate-drama.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/x-www-form-urlencoded",
  },
  body: new URLSearchParams({
    drama_id: 1,
    rating: 4.5,
  }),
  credentials: "include", // Include session cookie
})
  .then((response) => response.json())
  .then((data) => {
    console.log("Rating saved:", data);
  })
  .catch((error) => console.error("Error:", error));
```

**Success Response:**

HTTP Status: `200 OK`

```json
{
  "success": true,
  "message": "Rating berhasil disimpan",
  "avg_rating": 4.5,
  "total_ratings": 10
}
```

**Error Responses:**

```json
// Invalid drama ID
{
  "success": false,
  "message": "Invalid drama ID"
}

// Rating out of range
{
  "success": false,
  "message": "Rating must be between 0 and 5"
}

// Drama not found
{
  "success": false,
  "message": "Drama not found"
}

// Database error
{
  "success": false,
  "message": "Database error: ..."
}
```

**Business Logic:**

- Jika user sudah pernah rating drama ini, rating akan di-UPDATE
- Jika user belum pernah rating, akan di-INSERT baru
- Response mengembalikan rata-rata rating terbaru dan total user yang rating

---

### 2. Get User Rating

Mendapatkan rating user untuk drama tertentu.

**Endpoint:** `GET /api/get-user-rating.php`

**Authentication:** Required

**Parameters:**

| Parameter  | Type    | Required | Description |
| ---------- | ------- | -------- | ----------- |
| `drama_id` | integer | Yes      | ID drama    |

**Request Example:**

```bash
curl -X GET "http://localhost/Bkdrama/api/get-user-rating.php?drama_id=1" \
  --cookie "PHPSESSID=your_session_id"
```

**JavaScript Example:**

```javascript
fetch("/api/get-user-rating.php?drama_id=1", {
  method: "GET",
  credentials: "include",
})
  .then((response) => response.json())
  .then((data) => {
    console.log("User rating:", data.user_rating);
    console.log("Average rating:", data.avg_rating);
    console.log("Total ratings:", data.total_ratings);
  });
```

**Success Response:**

HTTP Status: `200 OK`

```json
{
  "success": true,
  "user_rating": 5.0,
  "avg_rating": 4.5,
  "total_ratings": 10
}
```

**Notes:**

- `user_rating` akan `null` jika user belum pernah rating drama ini
- `avg_rating` adalah rata-rata rating dari semua user
- `total_ratings` adalah jumlah user yang sudah rating

**Example Response (belum rating):**

```json
{
  "success": true,
  "user_rating": null,
  "avg_rating": 4.5,
  "total_ratings": 10
}
```

---

## Favorites API

### 3. Toggle Favorite

Menambahkan atau menghapus drama dari favorites.

**Endpoint:** `POST /api/toggle-favorite.php`

**Authentication:** Required

**Parameters:**

| Parameter  | Type    | Required | Description         |
| ---------- | ------- | -------- | ------------------- |
| `action`   | string  | Yes      | `add` atau `remove` |
| `drama_id` | integer | Yes      | ID drama            |

**Request Example (Add):**

```bash
curl -X POST http://localhost/Bkdrama/api/toggle-favorite.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=add&drama_id=1" \
  --cookie "PHPSESSID=your_session_id"
```

**Request Example (Remove):**

```bash
curl -X POST http://localhost/Bkdrama/api/toggle-favorite.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=remove&drama_id=1" \
  --cookie "PHPSESSID=your_session_id"
```

**JavaScript Example:**

```javascript
// Add to favorites
function addToFavorite(dramaId) {
  fetch("/api/toggle-favorite.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "add",
      drama_id: dramaId,
    }),
    credentials: "include",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert(data.message);
      }
    });
}

// Remove from favorites
function removeFromFavorite(dramaId) {
  fetch("/api/toggle-favorite.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "remove",
      drama_id: dramaId,
    }),
    credentials: "include",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert(data.message);
      }
    });
}
```

**Success Response (Add):**

```json
{
  "success": true,
  "message": "Ditambahkan ke favorit"
}
```

**Success Response (Remove):**

```json
{
  "success": true,
  "message": "Dihapus dari favorit"
}
```

**Error Responses:**

```json
// Invalid parameters
{
  "success": false,
  "message": "Invalid parameters"
}

// Invalid action
{
  "success": false,
  "message": "Invalid action"
}

// Already in favorites (duplicate)
{
  "success": false,
  "message": "Drama sudah ada di favorit"
}
```

---

## Watch History API

### 4. Save Watch Progress

Menyimpan progress tontonan episode.

**Endpoint:** `POST /api/watch-history-api.php?action=save_progress`

**Authentication:** Required

**Parameters:**

| Parameter          | Type    | Required | Description                        |
| ------------------ | ------- | -------- | ---------------------------------- |
| `action`           | string  | Yes      | `save_progress`                    |
| `drama_id`         | integer | Yes      | ID drama (untuk referensi)         |
| `episode_id`       | integer | Yes      | ID episode                         |
| `watched_duration` | integer | Yes      | Durasi yang sudah ditonton (detik) |
| `total_duration`   | integer | Yes      | Total durasi video (detik)         |

**Request Example:**

```bash
curl -X POST "http://localhost/Bkdrama/api/watch-history-api.php?action=save_progress" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "drama_id=1&episode_id=1&watched_duration=1800&total_duration=3600" \
  --cookie "PHPSESSID=your_session_id"
```

**JavaScript Example:**

```javascript
// Save progress every 10 seconds
let videoPlayer = document.getElementById("video");

setInterval(() => {
  let watchedDuration = Math.floor(videoPlayer.currentTime);
  let totalDuration = Math.floor(videoPlayer.duration);

  fetch("/api/watch-history-api.php?action=save_progress", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      drama_id: 1,
      episode_id: 1,
      watched_duration: watchedDuration,
      total_duration: totalDuration,
    }),
    credentials: "include",
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Progress saved:", data.data.progress_percentage + "%");
    });
}, 10000); // Save every 10 seconds
```

**Success Response:**

```json
{
  "success": true,
  "message": "Progress saved",
  "data": {
    "progress_seconds": 1800,
    "progress_percentage": 50.0,
    "is_completed": 0,
    "saved_at": "2024-10-31 12:00:00"
  }
}
```

**Business Logic:**

- Episode ditandai `completed = 1` jika:
  - `watched_duration >= total_duration`, ATAU
  - `watched_duration >= 90% of total_duration`
- Progress di-UPDATE jika sudah ada record sebelumnya
- `last_watched` timestamp otomatis update

**Error Responses:**

```json
// Invalid episode ID
{
  "success": false,
  "message": "Invalid episode ID"
}

// Invalid duration
{
  "success": false,
  "message": "Invalid duration"
}
```

---

### 5. Get Watch Progress

Mendapatkan progress tontonan untuk episode tertentu.

**Endpoint:** `GET /api/watch-history-api.php?action=get_progress&episode_id={id}`

**Authentication:** Required

**Parameters:**

| Parameter    | Type    | Required | Description    |
| ------------ | ------- | -------- | -------------- |
| `action`     | string  | Yes      | `get_progress` |
| `episode_id` | integer | Yes      | ID episode     |

**Request Example:**

```bash
curl -X GET "http://localhost/Bkdrama/api/watch-history-api.php?action=get_progress&episode_id=1" \
  --cookie "PHPSESSID=your_session_id"
```

**JavaScript Example:**

```javascript
fetch("/api/watch-history-api.php?action=get_progress&episode_id=1", {
  method: "GET",
  credentials: "include",
})
  .then((response) => response.json())
  .then((data) => {
    if (data.success && data.data.watched_duration > 0) {
      // Resume from last position
      videoPlayer.currentTime = data.data.watched_duration;
    }
  });
```

**Success Response (has progress):**

```json
{
  "success": true,
  "data": {
    "watched_duration": 1800,
    "is_completed": 0,
    "last_watched": "2024-10-31 12:00:00"
  }
}
```

**Success Response (no progress):**

```json
{
  "success": true,
  "data": {
    "watched_duration": 0,
    "is_completed": false,
    "last_watched": null
  }
}
```

---

### 6. Get Continue Watching

Mendapatkan daftar episode yang belum selesai ditonton (untuk fitur Continue Watching).

**Endpoint:** `GET /api/watch-history-api.php?action=continue_watching&limit={limit}`

**Authentication:** Required

**Parameters:**

| Parameter | Type    | Required | Default | Description                     |
| --------- | ------- | -------- | ------- | ------------------------------- |
| `action`  | string  | Yes      | -       | `continue_watching`             |
| `limit`   | integer | No       | 10      | Jumlah maksimal hasil (max: 50) |

**Request Example:**

```bash
curl -X GET "http://localhost/Bkdrama/api/watch-history-api.php?action=continue_watching&limit=10" \
  --cookie "PHPSESSID=your_session_id"
```

**JavaScript Example:**

```javascript
fetch("/api/watch-history-api.php?action=continue_watching&limit=10", {
  method: "GET",
  credentials: "include",
})
  .then((response) => response.json())
  .then((data) => {
    console.log("Continue watching:", data.data);

    // Display continue watching list
    data.data.forEach((item) => {
      console.log(`${item.drama_title} - Episode ${item.episode_number}`);
      console.log(`Progress: ${item.watched_duration} seconds`);
    });
  });
```

**Success Response:**

```json
{
  "success": true,
  "count": 3,
  "data": [
    {
      "episode_id": 1,
      "watched_duration": 1800,
      "last_watched": "2024-10-31 12:00:00",
      "drama_id": 1,
      "episode_title": "Episode 1: The Beginning",
      "episode_number": 1,
      "video_url": "assets/videos/drama1/ep1.mp4",
      "drama_title": "Crash Landing on You",
      "drama_thumbnail": "assets/uploads/posters/cloy.jpg",
      "genre": "Romance, Comedy, Drama"
    },
    {
      "episode_id": 5,
      "watched_duration": 2400,
      "last_watched": "2024-10-30 18:30:00",
      "drama_id": 2,
      "episode_title": "Episode 5: The Conflict",
      "episode_number": 5,
      "video_url": "assets/videos/drama2/ep5.mp4",
      "drama_title": "Goblin",
      "drama_thumbnail": "assets/uploads/posters/goblin.jpg",
      "genre": "Fantasy, Romance"
    }
  ]
}
```

**Business Logic:**

- Hanya menampilkan episode yang:
  - `completed = 0` (belum selesai ditonton)
  - `progress > 0` (sudah pernah ditonton)
- Diurutkan berdasarkan `last_watched` DESC (terbaru dulu)

---

### 7. Get Drama History

Mendapatkan riwayat tontonan untuk drama tertentu.

**Endpoint:** `GET /api/watch-history-api.php?action=drama_history&drama_id={id}`

**Authentication:** Required

**Parameters:**

| Parameter  | Type    | Required | Description     |
| ---------- | ------- | -------- | --------------- |
| `action`   | string  | Yes      | `drama_history` |
| `drama_id` | integer | Yes      | ID drama        |

**Request Example:**

```bash
curl -X GET "http://localhost/Bkdrama/api/watch-history-api.php?action=drama_history&drama_id=1" \
  --cookie "PHPSESSID=your_session_id"
```

**JavaScript Example:**

```javascript
fetch("/api/watch-history-api.php?action=drama_history&drama_id=1", {
  method: "GET",
  credentials: "include",
})
  .then((response) => response.json())
  .then((data) => {
    console.log("Statistics:", data.statistics);
    console.log("Episodes watched:", data.data);

    // Calculate progress
    const progressPercentage =
      (data.statistics.completed_episodes /
        data.statistics.total_episodes_watched) *
      100;
    console.log(`Progress: ${progressPercentage.toFixed(1)}%`);
  });
```

**Success Response:**

```json
{
  "success": true,
  "statistics": {
    "total_episodes_watched": 5,
    "completed_episodes": 3
  },
  "data": [
    {
      "episode_id": 1,
      "watched_duration": 3600,
      "is_completed": 1,
      "episode_number": 1,
      "episode_title": "Episode 1: The Beginning"
    },
    {
      "episode_id": 2,
      "watched_duration": 3600,
      "is_completed": 1,
      "episode_number": 2,
      "episode_title": "Episode 2: The Journey"
    },
    {
      "episode_id": 3,
      "watched_duration": 1800,
      "is_completed": 0,
      "episode_number": 3,
      "episode_title": "Episode 3: The Challenge"
    }
  ]
}
```

**Use Cases:**

- Menampilkan progress bar drama (berapa episode sudah selesai)
- Menampilkan checklist episode yang sudah ditonton
- Menghitung persentase completion

---

### 8. Delete Watch History

Menghapus riwayat tontonan untuk episode tertentu.

**Endpoint:** `POST /api/watch-history-api.php?action=delete_progress`

**Authentication:** Required

**Parameters:**

| Parameter    | Type    | Required | Description       |
| ------------ | ------- | -------- | ----------------- |
| `action`     | string  | Yes      | `delete_progress` |
| `episode_id` | integer | Yes      | ID episode        |

**Request Example:**

```bash
curl -X POST "http://localhost/Bkdrama/api/watch-history-api.php?action=delete_progress" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "episode_id=1" \
  --cookie "PHPSESSID=your_session_id"
```

**JavaScript Example:**

```javascript
function deleteWatchHistory(episodeId) {
  if (confirm("Hapus riwayat tontonan untuk episode ini?")) {
    fetch("/api/watch-history-api.php?action=delete_progress", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        episode_id: episodeId,
      }),
      credentials: "include",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Riwayat tontonan berhasil dihapus");
          location.reload();
        }
      });
  }
}
```

**Success Response:**

```json
{
  "success": true,
  "message": "Watch history deleted"
}
```

---

## Error Handling

### Standard Error Response Format

```json
{
  "success": false,
  "message": "Error description here"
}
```

### Common HTTP Status Codes

| Status Code | Description                      |
| ----------- | -------------------------------- |
| 200         | Success                          |
| 400         | Bad Request (invalid parameters) |
| 401         | Unauthorized (not logged in)     |
| 404         | Not Found                        |
| 405         | Method Not Allowed               |
| 500         | Internal Server Error            |

### Error Types

**1. Authentication Errors**

```json
{
  "success": false,
  "message": "Unauthorized"
}
```

**2. Validation Errors**

```json
{
  "success": false,
  "message": "Invalid drama ID"
}
```

**3. Database Errors**

```json
{
  "success": false,
  "message": "Database error: ..."
}
```

**4. Method Not Allowed**

```json
{
  "success": false,
  "message": "Method not allowed"
}
```

---

## Rate Limiting

Saat ini tidak ada rate limiting. **Rekomendasi untuk production:**

1. Implementasi rate limiting per user/IP
2. Contoh: Max 60 requests per minute
3. Response jika terkena rate limit:

```json
{
  "success": false,
  "message": "Too many requests. Please try again later.",
  "retry_after": 60
}
```

HTTP Status: `429 Too Many Requests`

---

## CORS Configuration

Saat ini CORS hanya diaktifkan di `watch-history-api.php`:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
```

**Rekomendasi untuk production:**

- Ganti `*` dengan domain spesifik
- Gunakan whitelist domain
- Implementasi CORS preflight handling

---

## Testing

### Using cURL

```bash
# Login first
curl -c cookies.txt -X POST http://localhost/Bkdrama/login.php \
  -d "username=user&password=user123"

# Test API with session
curl -b cookies.txt -X POST http://localhost/Bkdrama/api/rate-drama.php \
  -d "drama_id=1&rating=5.0"
```

### Using Postman

1. Import collection dari `docs/postman_collection.json`
2. Set base URL di environment variables
3. Login untuk mendapat session cookie
4. Test semua endpoints

### Using JavaScript (Browser Console)

```javascript
// Test rating API
fetch("/api/rate-drama.php", {
  method: "POST",
  headers: { "Content-Type": "application/x-www-form-urlencoded" },
  body: new URLSearchParams({ drama_id: 1, rating: 5.0 }),
  credentials: "include",
})
  .then((r) => r.json())
  .then(console.log);
```

---

## Sample Integration

### Complete Rating Widget

```html
<div class="rating-widget">
  <div class="stars">
    <span class="star" data-rating="1">★</span>
    <span class="star" data-rating="2">★</span>
    <span class="star" data-rating="3">★</span>
    <span class="star" data-rating="4">★</span>
    <span class="star" data-rating="5">★</span>
  </div>
  <p class="rating-info">
    <span id="avg-rating">0.0</span> / 5 (<span id="total-ratings">0</span>
    ratings)
  </p>
  <p class="your-rating">Your rating: <span id="user-rating">-</span></p>
</div>

<script>
  const dramaId = 1; // From PHP

  // Load existing rating
  fetch(`/api/get-user-rating.php?drama_id=${dramaId}`, {
    credentials: "include",
  })
    .then((r) => r.json())
    .then((data) => {
      document.getElementById("avg-rating").textContent = data.avg_rating;
      document.getElementById("total-ratings").textContent = data.total_ratings;
      document.getElementById("user-rating").textContent =
        data.user_rating || "-";

      if (data.user_rating) {
        highlightStars(data.user_rating);
      }
    });

  // Handle star click
  document.querySelectorAll(".star").forEach((star) => {
    star.addEventListener("click", function () {
      const rating = parseFloat(this.dataset.rating);

      fetch("/api/rate-drama.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ drama_id: dramaId, rating: rating }),
        credentials: "include",
      })
        .then((r) => r.json())
        .then((data) => {
          if (data.success) {
            document.getElementById("avg-rating").textContent = data.avg_rating;
            document.getElementById("total-ratings").textContent =
              data.total_ratings;
            document.getElementById("user-rating").textContent = rating;
            highlightStars(rating);
            alert("Rating berhasil disimpan!");
          }
        });
    });
  });

  function highlightStars(rating) {
    document.querySelectorAll(".star").forEach((star, index) => {
      if (index < rating) {
        star.classList.add("active");
      } else {
        star.classList.remove("active");
      }
    });
  }
</script>

<style>
  .star {
    cursor: pointer;
    font-size: 2rem;
    color: #ddd;
  }
  .star.active,
  .star:hover {
    color: #ffd700;
  }
</style>
```

---

## Changelog

### Version 1.1.0 (Current)

- Added rating API endpoints
- Added watch history API with progress tracking
- Added continue watching feature
- Improved error handling

### Version 1.0.0 (Initial)

- Basic favorites API
- Authentication via session

---

## Support

Jika ada pertanyaan atau menemukan bug, silakan:

- Email: api-support@bkdrama.com
- GitHub Issues: https://github.com/bkdrama/issues
- Documentation: https://docs.bkdrama.com/api

---

**Last Updated:** 2024-10-31
**API Version:** 1.1.0
