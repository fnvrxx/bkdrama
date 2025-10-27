<?php
/**
 * DEBUG UPLOAD - Test Upload Functionality
 * Akses: http://localhost/bkdrama/admin/debug-upload.php
 */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/upload.php';

requireRole(['admin', 'superadmin']);

$result = '';
$phpInfo = [];

// Get PHP Upload Settings
$phpInfo['upload_max_filesize'] = ini_get('upload_max_filesize');
$phpInfo['post_max_size'] = ini_get('post_max_size');
$phpInfo['max_execution_time'] = ini_get('max_execution_time');
$phpInfo['memory_limit'] = ini_get('memory_limit');
$phpInfo['file_uploads'] = ini_get('file_uploads') ? 'ON' : 'OFF';

// Test upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    $result = '<h3>📊 Upload Test Result:</h3>';

    // Display $_FILES content
    $result .= '<pre style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
    $result .= '<strong>$_FILES data:</strong>' . "\n";
    $result .= print_r($_FILES['test_file'], true);
    $result .= '</pre>';

    // Check if file uploaded
    if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        // Try upload
        $uploadResult = uploadFile(
            $_FILES['test_file'],
            '../uploads/videos',
            [], // Allow all types for testing
            524288000 // 500MB
        );

        $result .= '<pre style="background: ' . ($uploadResult['success'] ? '#d4edda' : '#f8d7da') . '; padding: 15px; border-radius: 5px;">';
        $result .= '<strong>Upload Result:</strong>' . "\n";
        $result .= print_r($uploadResult, true);
        $result .= '</pre>';

        // Check if file exists
        if ($uploadResult['success']) {
            $filePath = '../uploads/' . $uploadResult['filename'];
            $result .= '<p style="color: green;">✅ File exists: ' . (file_exists($filePath) ? 'YES' : 'NO') . '</p>';
            $result .= '<p>📁 Path: ' . $filePath . '</p>';
            $result .= '<p>📊 Size: ' . formatFileSize(filesize($filePath)) . '</p>';
        }
    } else {
        $result .= '<p style="color: red;">❌ Upload Error Code: ' . $_FILES['test_file']['error'] . '</p>';
        $result .= '<p>Error Messages:</p><ul>';
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $result .= '<li>' . ($errorMessages[$_FILES['test_file']['error']] ?? 'Unknown error') . '</li>';
        $result .= '</ul>';
    }
}

// Test folder writable
$folders = [
    '../uploads',
    '../uploads/videos',
    '../uploads/thumbnails',
    '../uploads/trailers',
    '../uploads/posters'
];

$folderStatus = '<h3>📁 Folder Status:</h3><ul>';
foreach ($folders as $folder) {
    $exists = is_dir($folder);
    $writable = $exists ? is_writable($folder) : false;
    $folderStatus .= '<li>';
    $folderStatus .= '<strong>' . $folder . '</strong>: ';
    $folderStatus .= $exists ? '✅ Exists' : '❌ Not Found';
    $folderStatus .= ' | ';
    $folderStatus .= $writable ? '✅ Writable' : '❌ Not Writable';
    $folderStatus .= '</li>';
}
$folderStatus .= '</ul>';

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Upload - BKDrama</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            max-width: 1000px;
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

        .form-group {
            margin: 20px 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .btn {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            background: #5568d3;
        }

        pre {
            font-size: 12px;
            overflow-x: auto;
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

        <h1>🔧 Debug Upload System</h1>

        <h3>⚙️ PHP Configuration:</h3>
        <table>
            <tr>
                <th>Setting</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>file_uploads</td>
                <td><?php echo $phpInfo['file_uploads']; ?></td>
                <td><?php echo $phpInfo['file_uploads'] === 'ON' ? '✅' : '❌'; ?></td>
            </tr>
            <tr>
                <td>upload_max_filesize</td>
                <td><?php echo $phpInfo['upload_max_filesize']; ?></td>
                <td><?php echo (int) $phpInfo['upload_max_filesize'] >= 500 ? '✅' : '⚠️'; ?></td>
            </tr>
            <tr>
                <td>post_max_size</td>
                <td><?php echo $phpInfo['post_max_size']; ?></td>
                <td><?php echo (int) $phpInfo['post_max_size'] >= 500 ? '✅' : '⚠️'; ?></td>
            </tr>
            <tr>
                <td>max_execution_time</td>
                <td><?php echo $phpInfo['max_execution_time']; ?>s</td>
                <td><?php echo (int) $phpInfo['max_execution_time'] >= 300 ? '✅' : '⚠️'; ?></td>
            </tr>
            <tr>
                <td>memory_limit</td>
                <td><?php echo $phpInfo['memory_limit']; ?></td>
                <td>ℹ️</td>
            </tr>
        </table>

        <?php echo $folderStatus; ?>

        <h3>🧪 Test Upload File:</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="test_file">Select File (Any type for testing):</label>
                <input type="file" id="test_file" name="test_file" required>
            </div>
            <button type="submit" class="btn">🚀 Test Upload</button>
        </form>

        <?php if ($result): ?>
            <div style="margin-top: 30px;">
                <?php echo $result; ?>
            </div>
        <?php endif; ?>

        <h3>📝 How to Fix Common Issues:</h3>
        <ol>
            <li><strong>Folder not writable:</strong>
                <pre>chmod 755 uploads/ -R  # Linux/Mac
Right-click → Properties → Security  # Windows</pre>
            </li>
            <li><strong>Upload size too small:</strong>
                <pre>Edit php.ini:
upload_max_filesize = 500M
post_max_size = 500M

Then restart Apache!</pre>
            </li>
            <li><strong>Folder not exists:</strong>
                <pre>mkdir uploads/videos uploads/thumbnails uploads/trailers uploads/posters</pre>
            </li>
        </ol>
    </div>
</body>

</html>