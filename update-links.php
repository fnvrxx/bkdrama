<?php
/**
 * Auto Update Links Script
 * Automatically replace "watch.php" with "watch-episode.php" in all PHP files
 * 
 * ⚠️ WARNING: This will modify your files!
 * Make sure to BACKUP first!
 * 
 * Upload to root and access: http://localhost/bkdrama/update-links.php
 */

// Security: Only allow access from localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied. Run this script from localhost only.');
}

$action = $_GET['action'] ?? '';
$confirmed = $action === 'confirm';

$files_to_update = [
    'detail.php',
    'dashboard.php',
    'favorit.php',
    'search.php',
    'genre.php',
    'index.php',
];

if ($confirmed) {
    // Perform update
    $results = [];
    $total_replacements = 0;

    foreach ($files_to_update as $file) {
        if (!file_exists($file)) {
            $results[$file] = [
                'status' => 'not_found',
                'message' => 'File not found'
            ];
            continue;
        }

        // Read file
        $content = file_get_contents($file);
        $original_content = $content;

        // Count occurrences
        $count = substr_count($content, 'watch.php');

        if ($count > 0) {
            // Replace
            $new_content = str_replace('watch.php', 'watch-episode.php', $content);

            // Backup original file
            $backup_file = $file . '.backup_' . date('YmdHis');
            file_put_contents($backup_file, $original_content);

            // Write new content
            file_put_contents($file, $new_content);

            $results[$file] = [
                'status' => 'updated',
                'count' => $count,
                'backup' => $backup_file
            ];

            $total_replacements += $count;
        } else {
            $results[$file] = [
                'status' => 'no_change',
                'message' => 'No "watch.php" found'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Update Links - BKDrama</title>
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

        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
        }

        .warning-box ul {
            margin-left: 20px;
            color: #856404;
            line-height: 1.8;
        }

        .file-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .file-list h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .file-item {
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .result-box {
            background: #d4edda;
            border: 2px solid #28a745;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .result-box h2 {
            color: #155724;
            margin-bottom: 15px;
        }

        .result-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #28a745;
        }

        .result-item.error {
            border-left-color: #dc3545;
        }

        .result-item.warning {
            border-left-color: #ffc107;
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

        .backup-info {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php if (!$confirmed): ?>
            <!-- Confirmation Page -->
            <h1>⚠️ Auto Update Links</h1>
            <p class="subtitle">Replace all "watch.php" with "watch-episode.php"</p>

            <div class="warning-box">
                <h3>⚠️ WARNING: This will modify your files!</h3>
                <ul>
                    <li><strong>Make a backup first!</strong> This script will modify your PHP files.</li>
                    <li>A backup of each file will be created automatically (.backup_* files)</li>
                    <li>Only run this from localhost for security</li>
                    <li>Review the list of files below before proceeding</li>
                </ul>
            </div>

            <div class="file-list">
                <h3>📁 Files to Update:</h3>
                <?php foreach ($files_to_update as $file): ?>
                    <div class="file-item">
                        📄 <?php echo $file; ?>
                        <?php if (!file_exists($file)): ?>
                            <span style="color: #999;">(not found, will skip)</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 30px;">
                <p style="margin-bottom: 15px;"><strong>What will happen:</strong></p>
                <ol style="margin-left: 20px; line-height: 1.8;">
                    <li>Search for "watch.php" in each file</li>
                    <li>Create backup file (.backup_* extension)</li>
                    <li>Replace "watch.php" with "watch-episode.php"</li>
                    <li>Save updated file</li>
                    <li>Show results</li>
                </ol>
            </div>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                <a href="?action=confirm" class="btn btn-danger"
                    onclick="return confirm('Are you sure? This will modify your files!')">
                    🔄 Yes, Update All Files
                </a>

                <a href="check-links.php" class="btn btn-secondary">
                    ← Back to Check Links
                </a>
            </div>

        <?php else: ?>
            <!-- Results Page -->
            <h1>✅ Update Complete!</h1>
            <p class="subtitle">Results of link update operation</p>

            <div class="result-box">
                <h2>📊 Summary</h2>
                <p style="font-size: 18px; color: #155724;">
                    <strong>Total replacements: <?php echo $total_replacements; ?></strong>
                </p>
                <p style="margin-top: 10px; color: #666;">
                    Updated
                    <?php echo count(array_filter($results, function ($r) {
                        return $r['status'] === 'updated'; })); ?>
                    file(s)
                </p>
            </div>

            <div class="file-list">
                <h3>📄 Detailed Results:</h3>
                <?php foreach ($results as $file => $result): ?>
                    <div class="result-item <?php echo $result['status'] === 'updated' ? '' : 'warning'; ?>">
                        <div class="file-name">
                            <?php
                            switch ($result['status']) {
                                case 'updated':
                                    echo '✅ ';
                                    break;
                                case 'not_found':
                                    echo '⚠️ ';
                                    break;
                                case 'no_change':
                                    echo 'ℹ️ ';
                                    break;
                            }
                            echo $file;
                            ?>
                        </div>
                        <div class="file-info">
                            <?php
                            switch ($result['status']) {
                                case 'updated':
                                    echo "Updated: {$result['count']} replacement(s)";
                                    break;
                                case 'not_found':
                                    echo $result['message'];
                                    break;
                                case 'no_change':
                                    echo $result['message'];
                                    break;
                            }
                            ?>
                        </div>
                        <?php if ($result['status'] === 'updated'): ?>
                            <div class="backup-info">
                                💾 Backup created: <?php echo $result['backup']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-radius: 8px;">
                <h3 style="margin-bottom: 10px;">🎉 Next Steps:</h3>
                <ol style="margin-left: 20px; line-height: 1.8;">
                    <li>Test your website: Click "Tonton" on any episode</li>
                    <li>Verify URL is now "watch-episode.php"</li>
                    <li>Check that video player loads correctly</li>
                    <li>If everything works, delete .backup_* files</li>
                    <li>If something broke, restore from .backup_* files</li>
                </ol>
            </div>

            <div style="margin-top: 30px;">
                <a href="check-links.php" class="btn btn-success">
                    ✅ Verify Updates
                </a>

                <a href="dashboard.php" class="btn btn-secondary">
                    ← Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>