<?php
/**
 * Test Watch History API
 * Upload to root and access: http://localhost/bkdrama/test-api.php
 */

require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    die('Please login first! <a href="login.php">Login</a>');
}

$database = new Database();
$db = $database->getConnection();
$user_id = getUserId();

// Test data
$test_drama_id = 1;
$test_episode_id = 1;
$test_watched = 120; // 2 minutes
$test_duration = 300; // 5 minutes
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Watch History API</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .test-section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .test-section h3 {
            margin-top: 0;
            color: #333;
        }

        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }

        button:hover {
            background: #5568d3;
        }

        button.danger {
            background: #dc3545;
        }

        button.danger:hover {
            background: #c82333;
        }

        .result {
            margin-top: 15px;
            padding: 15px;
            background: white;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }

        .result.success {
            border: 2px solid #28a745;
            color: #155724;
        }

        .result.error {
            border: 2px solid #dc3545;
            color: #721c24;
        }

        .info-box {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th,
        table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background: #667eea;
            color: white;
        }

        .input-group {
            margin: 10px 0;
        }

        .input-group label {
            display: inline-block;
            width: 150px;
            font-weight: 600;
        }

        .input-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 200px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🧪 Test Watch History API</h1>

        <div class="info-box">
            <strong>Current User:</strong> <?php echo getUserName() ?? 'Unknown'; ?> (ID: <?php echo $user_id; ?>)<br>
            <strong>Test Episode:</strong> Episode <?php echo $test_episode_id; ?> of Drama
            <?php echo $test_drama_id; ?><br>
            <strong>API Endpoint:</strong> api/watch-history-api.php
        </div>

        <!-- Test 1: Check API -->
        <div class="test-section">
            <h3>Test 1: Check API Connection</h3>
            <p>Test if API endpoint is accessible</p>
            <button onclick="testAPI()">🔍 Test API</button>
            <div id="result1" class="result" style="display:none;"></div>
        </div>

        <!-- Test 2: Save Progress -->
        <div class="test-section">
            <h3>Test 2: Save Progress</h3>
            <p>Save watch progress to database</p>

            <div class="input-group">
                <label>Drama ID:</label>
                <input type="number" id="drama_id" value="<?php echo $test_drama_id; ?>">
            </div>

            <div class="input-group">
                <label>Episode ID:</label>
                <input type="number" id="episode_id" value="<?php echo $test_episode_id; ?>">
            </div>

            <div class="input-group">
                <label>Watched (seconds):</label>
                <input type="number" id="watched" value="<?php echo $test_watched; ?>">
            </div>

            <div class="input-group">
                <label>Duration (seconds):</label>
                <input type="number" id="duration" value="<?php echo $test_duration; ?>">
            </div>

            <button onclick="saveProgress()">💾 Save Progress</button>
            <div id="result2" class="result" style="display:none;"></div>
        </div>

        <!-- Test 3: Get Progress -->
        <div class="test-section">
            <h3>Test 3: Get Progress</h3>
            <p>Retrieve saved progress from database</p>
            <button onclick="getProgress()">📊 Get Progress</button>
            <div id="result3" class="result" style="display:none;"></div>
        </div>

        <!-- Test 4: Continue Watching -->
        <div class="test-section">
            <h3>Test 4: Continue Watching List</h3>
            <p>Get list of unfinished episodes</p>
            <button onclick="getContinueWatching()">📺 Get Continue Watching</button>
            <div id="result4" class="result" style="display:none;"></div>
        </div>

        <!-- Test 5: Check Database -->
        <div class="test-section">
            <h3>Test 5: Check Database</h3>
            <p>Check users_history table directly</p>
            <button onclick="checkDatabase()">🗄️ Check Database</button>
            <div id="result5" class="result" style="display:none;"></div>
        </div>
    </div>

    <script>
        function showResult(elementId, data, isSuccess = true) {
            const element = document.getElementById(elementId);
            element.style.display = 'block';
            element.className = 'result ' + (isSuccess ? 'success' : 'error');
            element.textContent = JSON.stringify(data, null, 2);
        }

        // Test 1: Check API
        function testAPI() {
            console.log('Testing API connection...');

            fetch('api/watch-history-api.php?action=test')
                .then(response => response.json())
                .then(data => {
                    console.log('API Test:', data);
                    showResult('result1', data, data.success);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showResult('result1', { error: error.message }, false);
                });
        }

        // Test 2: Save Progress
        function saveProgress() {
            const drama_id = document.getElementById('id_drama').value;
            const episode_id = document.getElementById('episode_id').value;
            const watched = document.getElementById('watched').value;
            const duration = document.getElementById('progress').value;

            console.log('Saving progress:', { drama_id, episode_id, watched, duration });

            const formData = new FormData();
            formData.append('action', 'save_progress');
            formData.append('id_drama', drama_id);
            formData.append('episode_id', episode_id);
            formData.append('watched_duration', watched);
            formData.append('total_duration', duration);

            fetch('api/watch-history-api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Save response:', data);
                    showResult('result2', data, data.success);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showResult('result2', { error: error.message }, false);
                });
        }

        // Test 3: Get Progress
        function getProgress() {
            const episode_id = document.getElementById('episode_id').value;

            console.log('Getting progress for episode:', episode_id);

            fetch(`api/watch-history-api.php?action=get_progress&episode_id=${episode_id}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Get progress:', data);
                    showResult('result3', data, data.success);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showResult('result3', { error: error.message }, false);
                });
        }

        // Test 4: Continue Watching
        function getContinueWatching() {
            console.log('Getting continue watching list...');

            fetch('api/watch-history-api.php?action=continue_watching&limit=10')
                .then(response => response.json())
                .then(data => {
                    console.log('Continue watching:', data);
                    showResult('result4', data, data.success);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showResult('result4', { error: error.message }, false);
                });
        }

        // Test 5: Check Database
        function checkDatabase() {
            console.log('Checking database...');

            fetch('admin/check-database.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Database check:', data);
                    showResult('result5', data, data.success);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showResult('result5', { error: error.message }, false);
                });
        }
    </script>
</body>

</html>