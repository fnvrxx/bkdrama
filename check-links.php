<?php
/**
 * Check Links Script
 * Check which PHP files contain "watch.php" links
 * 
 * Upload to root and access: http://localhost/bkdrama/check-links.php
 */

// Security: Only allow access from localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied. Run this script from localhost only.');
}

$files_to_check = [
    'detail.php',
    'dashboard.php',
    'favorit.php',
    'search.php',
    'genre.php',
    'index.php',
    'admin/manage-movies.php',
    'admin/edit-movie.php',
];

$found = [];
$not_found = [];

foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        $not_found[] = $file;
        continue;
    }

    $content = file_get_contents($file);
    $count = substr_count($content, 'watch.php');

    if ($count > 0) {
        // Get line numbers
        $lines = explode("\n", $content);
        $line_numbers = [];

        foreach ($lines as $line_num => $line) {
            if (strpos($line, 'watch.php') !== false) {
                $line_numbers[] = $line_num + 1;
            }
        }

        $found[$file] = [
            'count' => $count,
            'lines' => $line_numbers
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Links - BKDrama</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .file-list {
            margin-top: 20px;
        }

        .file-item {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
        }

        .file-item.ok {
            border-left-color: #28a745;
        }

        .file-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .file-info {
            font-size: 14px;
            color: #666;
        }

        .line-numbers {
            display: inline-block;
            background: #fff;
            padding: 4px 8px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 12px;
            margin-left: 10px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #5568d3;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔍 Check Links Script</h1>
        <p class="subtitle">Checking for "watch.php" links in PHP files...</p>

        <?php if (empty($found)): ?>
            <div class="status status-success">
                <div class="icon">✅</div>
                <strong>All links updated!</strong><br>
                No "watch.php" found in any files. All links are pointing to "watch-episode.php".
            </div>
        <?php else: ?>
            <div class="status status-warning">
                <div class="icon">⚠️</div>
                <strong>Found <?php echo count($found); ?> file(s) with old links!</strong><br>
                Please update these files manually or use the auto-update script.
            </div>

            <div class="file-list">
                <h3>Files Need Update:</h3>
                <?php foreach ($found as $file => $info): ?>
                    <div class="file-item">
                        <div class="file-name">📄 <?php echo $file; ?></div>
                        <div class="file-info">
                            Found <?php echo $info['count']; ?> occurrence(s)
                            <span class="line-numbers">
                                Lines: <?php echo implode(', ', $info['lines']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <a href="update-links.php" class="btn btn-danger">
                🔄 Auto-Update All Files
            </a>
        <?php endif; ?>

        <?php if (!empty($not_found)): ?>
            <div class="file-list" style="margin-top: 30px;">
                <h3>Files Not Found:</h3>
                <?php foreach ($not_found as $file): ?>
                    <div class="file-item ok">
                        <div class="file-name">📄 <?php echo $file; ?></div>
                        <div class="file-info">File does not exist (skip)</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p><strong>Manual Update:</strong></p>
            <ol style="margin: 10px 0 10px 20px; line-height: 1.8;">
                <li>Open each file listed above</li>
                <li>Find: <code style="background: #f8f9fa; padding: 2px 6px;">watch.php</code></li>
                <li>Replace with: <code style="background: #d4edda; padding: 2px 6px;">watch-episode.php</code></li>
                <li>Save file</li>
                <li>Refresh this page to check again</li>
            </ol>
        </div>

        <a href="javascript:location.reload()" class="btn">
            🔄 Check Again
        </a>

        <a href="dashboard.php" class="btn">
            ← Back to Dashboard
        </a>
    </div>
</body>

</html>