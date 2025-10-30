# Changelog

All notable changes to BKDrama project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2024-10-31

### Added
- **User-driven Rating System**: Users can now rate dramas (0-5 stars)
- New `ratings` table for storing user ratings
- New `v_drama_ratings` view for calculating average ratings
- API endpoint: `POST /api/rate-drama.php` - Submit rating
- API endpoint: `GET /api/get-user-rating.php` - Get user rating
- Rating widget with 5 stars on drama detail page
- Real-time rating update without page reload
- Display average rating and total raters on all drama listings
- Continue watching feature with progress tracking
- Watch history API with multiple actions
- Episode thumbnail support
- Admin rating field removed from add/edit drama forms
- Comprehensive documentation (DATABASE.md, API.md, FEATURES.md, DEVELOPER.md)
- Database diagram (diagram_database.puml) with PlantUML

### Changed
- **BREAKING**: Drama rating system changed from admin-set to user-driven
- `drama.rating` field marked as DEPRECATED (use `ratings` table instead)
- Admin panel now displays average rating from users instead of static rating
- All drama queries updated to include rating information from `ratings` table
- Manage movies page shows "⭐ X.X (Y)" format (average rating + total raters)
- Admin dashboard shows user-driven ratings
- Improved admin panel UI with better navigation
- Enhanced episode management with thumbnail preview

### Fixed
- Episode number duplicate validation
- File upload error handling
- Session security improvements
- SQL injection prevention with prepared statements
- XSS protection with proper output escaping

### Deprecated
- `drama.rating` field (use `ratings` table for dynamic rating)

### Security
- All queries use prepared statements
- Password hashing with bcrypt
- File upload validation
- XSS protection on all outputs
- CSRF token implementation (recommended)

## [1.0.0] - 2024-10-27

### Added
- Initial release
- User authentication (register, login, logout)
- Role-based access control (User, Admin, Superadmin)
- Drama management (CRUD operations)
- Episode management (CRUD operations)
- Video player with HTML5
- Favorites system
- Watch history tracking
- User dashboard
- Admin dashboard
- Browse drama with genre filter
- Search functionality
- Continue watching feature
- File upload system for posters and videos
- Responsive UI design

### Database Schema
- `roles` table
- `users` table
- `drama` table
- `episodes` table
- `favorit` table
- `users_history` table

## [Unreleased]

### Planned Features
- [ ] Comments and reviews system
- [ ] Email verification on register
- [ ] Forgot password functionality
- [ ] Social login (Google, Facebook)
- [ ] Multi-language support
- [ ] Dark mode
- [ ] PWA (Progressive Web App)
- [ ] Subtitle support
- [ ] Multiple video quality options
- [ ] Download for offline viewing
- [ ] Recommendation system
- [ ] Notification system
- [ ] User profile customization
- [ ] Advanced search with filters
- [ ] Watchlist (planned to watch)
- [ ] Export user data
- [ ] API rate limiting
- [ ] Automated testing suite
- [ ] CI/CD pipeline
- [ ] Docker containerization
- [ ] Mobile app (React Native)

---

## Version History

| Version | Date | Description |
|---------|------|-------------|
| 1.1.0 | 2024-10-31 | User-driven rating system, enhanced documentation |
| 1.0.0 | 2024-10-27 | Initial release with core features |

---

## Migration Guide

### Upgrading from 1.0.0 to 1.1.0

#### Database Changes

1. **Create `ratings` table:**

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

2. **Create view:**

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

3. **(Optional) Migrate old ratings:**

```sql
-- Backup old ratings
ALTER TABLE drama ADD COLUMN old_rating DECIMAL(2,1);
UPDATE drama SET old_rating = rating;

-- Convert admin ratings to user ratings (user_id = 2 for admin)
INSERT INTO ratings (user_id, drama_id, rating, created_at)
SELECT 2, id, rating, created_at
FROM drama
WHERE rating > 0
ON DUPLICATE KEY UPDATE rating = VALUES(rating);
```

#### Code Changes

1. Update all drama queries to include rating info:

```php
// Old
$query = "SELECT d.* FROM drama d";

// New
$query = "SELECT d.*,
          COALESCE(AVG(r.rating), 0) as avg_rating,
          COUNT(DISTINCT r.id) as total_ratings
          FROM drama d
          LEFT JOIN ratings r ON d.id = r.drama_id
          GROUP BY d.id";
```

2. Remove rating input from admin forms (add-movies.php, edit-movies.php)

3. Update display format:

```php
// Old
echo "⭐ " . $drama['rating'];

// New
echo "⭐ " . number_format($drama['avg_rating'], 1) . " (" . $drama['total_ratings'] . ")";
```

#### No Downtime Migration

```bash
# 1. Backup database
mysqldump -u root -p new_film > backup_before_upgrade.sql

# 2. Run migrations
mysql -u root -p new_film < database/migrations/v1.1.0.sql

# 3. Update code
git pull origin main

# 4. Test
# Access the site and verify ratings work

# 5. Rollback if needed
# mysql -u root -p new_film < backup_before_upgrade.sql
```

---

## Breaking Changes

### Version 1.1.0

**Rating System Overhaul:**
- `drama.rating` field no longer used by admin forms
- Admin cannot manually set drama rating
- All ratings now come from users through the rating API
- Existing static ratings preserved but not displayed

**Impact:**
- Admin workflow changed: No rating input when adding/editing drama
- API clients must update to use new rating endpoints
- Frontend must update rating display logic

**Migration Path:**
- Follow migration guide above
- Update all custom integrations to use new rating API
- Update UI to show user ratings instead of static ratings

---

## Support

For questions about updates:
- Documentation: [README.md](README.md)
- Issues: https://github.com/bkdrama/issues
- Email: support@bkdrama.com

---

**Maintained by:** BKDrama Team
**Last Updated:** 2024-10-31
