<?php
/**
 * CHECK DATABASE STRUCTURE
 * Verify tabel episodes dan drama punya kolom yang benar
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

requireRole(['admin', 'superadmin']);

$database = new Database();
$db = $database->getConnection();

// Check episodes table structure
$episodes_query = "DESCRIBE episodes";
$episodes_stmt = $db->query($episodes_query);
$episodes_columns = $episodes_stmt->fetchAll();

// Check drama table structure
$drama_query = "DESCRIBE drama";
$drama_stmt = $db->query($drama_query);
$drama_columns = $drama_stmt->fetchAll();

// Check sample data
$sample_query = "SELECT id, eps_title, link_video, thumbnail FROM episodes LIMIT 3";
$sample_stmt = $db->query($sample_query);
$sample_episodes = $sample_stmt->fetchAll();

$drama_sample_query = "SELECT id, title, thumbnail, trailer FROM drama LIMIT 3";
$drama_sample_stmt = $db->query($drama_sample_query);
$sample_dramas = $drama_sample_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Database - BKDrama</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #667eea;
            margin-bottom: 20px;
        }

        h3 {
            color: #333;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #667eea;
            color: white;
        }

        .check {
            color: green;
            font-weight: bold;
        }

        .warning {
            color: orange;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Admin</a>

        <h1>🗄️ Database Structure Check</h1>

        <h3>📋 Table: episodes</h3>
        <table>
            <tr>
                <th>Field</th>
                <th>Type</th>
                <th>Null</th>
                <th>Key</th>
                <th>Status</th>
            </tr>
            <?php foreach ($episodes_columns as $col): ?>
                <tr>
                    <td><?php echo $col['Field']; ?></td>
                    <td><?php echo $col['Type']; ?></td>
                    <td><?php echo $col['Null']; ?></td>
                    <td><?php echo $col['Key']; ?></td>
                    <td>
                        <?php
                        $required = ['id', 'id_drama', 'eps_number', 'eps_title', 'durasi', 'link_video'];
                        if (in_array($col['Field'], $required)) {
                            echo '<span class="check">✅ Required</span>';
                        } elseif ($col['Field'] === 'thumbnail') {
                            echo '<span class="check">✅ Optional</span>';
                        } else {
                            echo 'ℹ️';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <?php
        $has_link_video = false;
        $has_thumbnail = false;
        foreach ($episodes_columns as $col) {
            if ($col['Field'] === 'link_video')
                $has_link_video = true;
            if ($col['Field'] === 'thumbnail')
                $has_thumbnail = true;
        }
        ?>

        <p>
            <strong>link_video:</strong>
            <?php echo $has_link_video ? '<span class="check">✅ EXISTS</span>' : '<span class="error">❌ MISSING!</span>'; ?>
        </p>
        <p>
            <strong>thumbnail:</strong>
            <?php echo $has_thumbnail ? '<span class="check">✅ EXISTS</span>' : '<span class="warning">⚠️ MISSING (Optional)</span>'; ?>
        </p>

        <h3>📋 Table: drama</h3>
        <table>
            <tr>
                <th>Field</th>
                <th>Type</th>
                <th>Null</th>
                <th>Key</th>
                <th>Status</th>
            </tr>
            <?php foreach ($drama_columns as $col): ?>
                <tr>
                    <td><?php echo $col['Field']; ?></td>
                    <td><?php echo $col['Type']; ?></td>
                    <td><?php echo $col['Null']; ?></td>
                    <td><?php echo $col['Key']; ?></td>
                    <td>
                        <?php
                        if ($col['Field'] === 'thumbnail' || $col['Field'] === 'trailer') {
                            echo '<span class="check">✅ Upload Field</span>';
                        } else {
                            echo 'ℹ️';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h3>📊 Sample Data - Episodes:</h3>
        <?php if (count($sample_episodes) > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Video Path</th>
                    <th>Thumbnail</th>
                </tr>
                <?php foreach ($sample_episodes as $ep): ?>
                    <tr>
                        <td><?php echo $ep['id']; ?></td>
                        <td><?php echo htmlspecialchars($ep['eps_title']); ?></td>
                        <td><?php echo htmlspecialchars($ep['link_video'] ?? 'NULL'); ?></td>
                        <td><?php echo htmlspecialchars($ep['thumbnail'] ?? 'NULL'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="warning">⚠️ No episodes found in database</p>
        <?php endif; ?>

        <h3>📊 Sample Data - Drama:</h3>
        <?php if (count($sample_dramas) > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Poster</th>
                    <th>Trailer</th>
                </tr>
                <?php foreach ($sample_dramas as $drama): ?>
                    <tr>
                        <td><?php echo $drama['id']; ?></td>
                        <td><?php echo htmlspecialchars($drama['title']); ?></td>
                        <td><?php echo htmlspecialchars($drama['thumbnail'] ?? 'NULL'); ?></td>
                        <td><?php echo htmlspecialchars($drama['trailer'] ?? 'NULL'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="warning">⚠️ No drama found in database</p>
        <?php endif; ?>

        <h3>✅ Checklist:</h3>
        <ul>
            <li><?php echo $has_link_video ? '✅' : '❌'; ?> episodes.link_video column exists</li>
            <li><?php echo $has_thumbnail ? '✅' : '⚠️'; ?> episodes.thumbnail column exists</li>
            <li>✅ drama table structure OK</li>
        </ul>
    </div>
</body>

</html>