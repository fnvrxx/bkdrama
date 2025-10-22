<?php
/**
 * DEBUG PATHS - Check uploaded files accessibility
 * Akses: http://localhost/bkdrama/debug-paths.php
 */

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get sample episodes with videos
$episodes_query = "SELECT e.id, e.eps_title, e.link_video, e.thumbnail, d.title as drama_title 
                   FROM episodes e 
                   JOIN drama d ON e.id_drama = d.id 
                   LIMIT 5";
$episodes_stmt = $db->query($episodes_query);
$episodes = $episodes_stmt->fetchAll();

// Get sample dramas with posters
$drama_query = "SELECT id, title, thumbnail, trailer FROM drama LIMIT 5";
$drama_stmt = $db->query($drama_query);
$dramas = $drama_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Paths - BKDrama</title>
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
            font-size: 13px;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #667eea;
            color: white;
        }

        .success {
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }

        .warning {
            color: orange;
            font-weight: bold;
        }

        img,
        video {
            max-width: 200px;
            max-height: 150px;
            border: 2px solid #ddd;
            border-radius: 5px;
        }

        .code {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔍 Debug File Paths</h1>

        <div class="code">
            <strong>Current Directory:</strong> <?php echo getcwd(); ?><br>
            <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?><br>
            <strong>Script Path:</strong> <?php echo __FILE__; ?>
        </div>

        <h3>📺 Episodes - Video Files</h3>
        <?php if (count($episodes) > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Episode</th>
                    <th>Path dari DB</th>
                    <th>File Exists?</th>
                    <th>Accessible?</th>
                    <th>Preview</th>
                </tr>
                <?php foreach ($episodes as $ep): ?>
                    <tr>
                        <td><?php echo $ep['id']; ?></td>
                        <td><?php echo htmlspecialchars($ep['drama_title']); ?><br>
                            <small><?php echo htmlspecialchars($ep['eps_title']); ?></small>
                        </td>
                        <td class="code"><?php echo htmlspecialchars($ep['link_video'] ?? 'NULL'); ?></td>
                        <td>
                            <?php
                            if ($ep['link_video']) {
                                $exists = file_exists($ep['link_video']);
                                echo $exists ? '<span class="success">✅ YES</span>' : '<span class="error">❌ NO</span>';
                                if ($exists) {
                                    echo '<br><small>' . number_format(filesize($ep['link_video']) / 1048576, 2) . ' MB</small>';
                                }
                            } else {
                                echo '<span class="warning">⚠️ NULL</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($ep['link_video'] && file_exists($ep['link_video'])): ?>
                                <a href="<?php echo $ep['link_video']; ?>" target="_blank">🔗 Open</a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ep['link_video'] && file_exists($ep['link_video'])): ?>
                                <video controls style="max-width: 200px;">
                                    <source src="<?php echo $ep['link_video']; ?>" type="video/mp4">
                                </video>
                            <?php else: ?>
                                No preview
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="warning">⚠️ No episodes found</p>
        <?php endif; ?>

        <h3>🖼️ Episodes - Thumbnails</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Episode</th>
                <th>Thumbnail Path</th>
                <th>Exists?</th>
                <th>Preview</th>
            </tr>
            <?php foreach ($episodes as $ep): ?>
                <tr>
                    <td><?php echo $ep['id']; ?></td>
                    <td><?php echo htmlspecialchars($ep['eps_title']); ?></td>
                    <td class="code"><?php echo htmlspecialchars($ep['thumbnail'] ?? 'NULL'); ?></td>
                    <td>
                        <?php
                        if ($ep['thumbnail']) {
                            $exists = file_exists($ep['thumbnail']);
                            echo $exists ? '<span class="success">✅ YES</span>' : '<span class="error">❌ NO</span>';
                        } else {
                            echo '<span class="warning">⚠️ NULL</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php if ($ep['thumbnail'] && file_exists($ep['thumbnail'])): ?>
                            <img src="<?php echo $ep['thumbnail']; ?>" alt="Thumbnail">
                        <?php else: ?>
                            No image
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h3>🎭 Drama - Posters & Trailers</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Poster Path</th>
                <th>Exists?</th>
                <th>Trailer Path</th>
                <th>Exists?</th>
                <th>Preview</th>
            </tr>
            <?php foreach ($dramas as $drama): ?>
                <tr>
                    <td><?php echo $drama['id']; ?></td>
                    <td><?php echo htmlspecialchars($drama['title']); ?></td>
                    <td class="code"><?php echo htmlspecialchars($drama['thumbnail'] ?? 'NULL'); ?></td>
                    <td>
                        <?php
                        if ($drama['thumbnail']) {
                            echo file_exists($drama['thumbnail']) ? '<span class="success">✅</span>' : '<span class="error">❌</span>';
                        } else {
                            echo '<span class="warning">⚠️</span>';
                        }
                        ?>
                    </td>
                    <td class="code"><?php echo htmlspecialchars($drama['trailer'] ?? 'NULL'); ?></td>
                    <td>
                        <?php
                        if ($drama['trailer']) {
                            echo file_exists($drama['trailer']) ? '<span class="success">✅</span>' : '<span class="error">❌</span>';
                        } else {
                            echo '<span class="warning">⚠️</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php if ($drama['thumbnail'] && file_exists($drama['thumbnail'])): ?>
                            <img src="<?php echo $drama['thumbnail']; ?>" alt="Poster">
                        <?php else: ?>
                            No poster
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h3>📝 Kesimpulan & Solusi</h3>
        <div style="background: #e7f3ff; padding: 15px; border-radius: 5px; border-left: 4px solid #667eea;">
            <strong>Jika File Exists = ❌ NO:</strong>
            <ul>
                <li>Path di database salah</li>
                <li>File tidak ada di folder uploads/</li>
                <li>Cek di <code>uploads/videos/</code> apakah file ada</li>
            </ul>

            <strong>Jika File Exists = ✅ YES tapi Preview tidak muncul:</strong>
            <ul>
                <li>Browser block file (MIME type issue)</li>
                <li>Format video tidak supported</li>
                <li>Coba akses langsung: <code>http://localhost/bkdrama/uploads/videos/filename.mp4</code></li>
            </ul>

            <strong>Fix Path Issue:</strong>
            <pre style="background: white; padding: 10px; border-radius: 3px;">
Path di database harus: uploads/videos/filename.mp4
BUKAN: ../uploads/videos/filename.mp4
BUKAN: /full/path/to/uploads/videos/filename.mp4
            </pre>
        </div>
    </div>
</body>

</html>