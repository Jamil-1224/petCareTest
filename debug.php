<?php
// Debug page to identify 500 errors
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<h1>Debug Information</h1>";
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br><br>";

echo "<h2>2. Testing vendor/autoload.php</h2>";
$autoload_path = __DIR__ . '/vendor/autoload.php';
echo "Autoload path: $autoload_path<br>";
echo "File exists: " . (file_exists($autoload_path) ? 'YES' : 'NO') . "<br>";
echo "Is readable: " . (is_readable($autoload_path) ? 'YES' : 'NO') . "<br><br>";

if (file_exists($autoload_path)) {
    try {
        require_once $autoload_path;
        echo "✓ Autoload loaded successfully<br><br>";
    } catch (Exception $e) {
        echo "✗ Error loading autoload: " . $e->getMessage() . "<br><br>";
    }
}

echo "<h2>3. Testing db_connect.php</h2>";
$db_path = __DIR__ . '/db_connect.php';
echo "DB connect path: $db_path<br>";
echo "File exists: " . (file_exists($db_path) ? 'YES' : 'NO') . "<br>";
echo "Is readable: " . (is_readable($db_path) ? 'YES' : 'NO') . "<br><br>";

if (file_exists($db_path)) {
    try {
        require_once $db_path;
        echo "✓ db_connect.php loaded successfully<br><br>";
    } catch (Exception $e) {
        echo "✗ Error loading db_connect: " . $e->getMessage() . "<br>";
        echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre><br>";
    }
}

echo "<h2>4. Testing functions.php</h2>";
$functions_path = __DIR__ . '/functions.php';
echo "Functions path: $functions_path<br>";
echo "File exists: " . (file_exists($functions_path) ? 'YES' : 'NO') . "<br>";
echo "Is readable: " . (is_readable($functions_path) ? 'YES' : 'NO') . "<br><br>";

if (file_exists($functions_path)) {
    try {
        require_once $functions_path;
        echo "✓ functions.php loaded successfully<br><br>";
    } catch (Exception $e) {
        echo "✗ Error loading functions: " . $e->getMessage() . "<br>";
        echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre><br>";
    }
}

echo "<h2>5. MongoDB Connection Test</h2>";
if (isset($client) && isset($db)) {
    try {
        $client->listDatabases();
        echo "✓ MongoDB connection successful<br>";
        echo "Database name: $DB_NAME<br><br>";
    } catch (Exception $e) {
        echo "✗ MongoDB connection failed: " . $e->getMessage() . "<br><br>";
    }
} else {
    echo "MongoDB client not initialized<br><br>";
}

echo "<h2>6. Session Test</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "✓ Session started<br>";
} else {
    echo "✓ Session already active<br>";
}
echo "Session ID: " . session_id() . "<br><br>";

echo "<h2>7. Directory Permissions</h2>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Is writable: " . (is_writable(__DIR__) ? 'YES' : 'NO') . "<br><br>";

echo "<h2>8. Environment Variables</h2>";
$mongodb_uri = getenv('MONGODB_URI');
echo "MONGODB_URI set: " . ($mongodb_uri ? 'YES (hidden for security)' : 'NO') . "<br><br>";

echo "<h2>Success!</h2>";
echo "If you see this message, PHP is working correctly.<br>";
echo "Now try accessing your main pages (login.php, index.php, etc.)<br>";
?>
