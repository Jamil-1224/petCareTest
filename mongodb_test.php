<?php
// MongoDB Connection Diagnostic Tool
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>MongoDB Connection Diagnostics</h1>";

echo "<h2>1. PHP Environment</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "OpenSSL Version: " . OPENSSL_VERSION_TEXT . "<br><br>";

echo "<h2>2. MongoDB Extension</h2>";
if (extension_loaded('mongodb')) {
    echo "✓ MongoDB extension is loaded<br>";
    $version = phpversion('mongodb');
    echo "MongoDB Driver Version: $version<br><br>";
} else {
    echo "✗ MongoDB extension not loaded<br><br>";
}

echo "<h2>3. Environment Variable</h2>";
$uri = getenv('MONGODB_URI');
if ($uri) {
    echo "✓ MONGODB_URI is set<br>";
    // Hide password for security
    $masked = preg_replace('/\/\/([^:]+):([^@]+)@/', '//***:***@', $uri);
    echo "Connection String: $masked<br><br>";
} else {
    echo "✗ MONGODB_URI not set<br><br>";
    die("Cannot proceed without MONGODB_URI");
}

echo "<h2>4. Connection Test</h2>";
require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;

try {
    echo "Attempting connection...<br>";
    
    // Try with minimal options first
    $options = [
        'serverSelectionTimeoutMS' => 5000,
    ];
    
    $client = new Client($uri, $options);
    echo "✓ Client created<br>";
    
    // Try to list databases
    $databases = $client->listDatabases();
    echo "✓ Successfully connected to MongoDB!<br>";
    
    echo "<br><h3>Available Databases:</h3>";
    foreach ($databases as $db) {
        echo "- " . $db->getName() . "<br>";
    }
    
} catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
    echo "✗ Connection Timeout<br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<br>Possible causes:<br>";
    echo "- Network firewall blocking MongoDB Atlas (port 27017)<br>";
    echo "- IP whitelist restrictions on MongoDB Atlas<br>";
    echo "- MongoDB cluster is down<br>";
    
} catch (\MongoDB\Driver\Exception\SSLConnectionException $e) {
    echo "✗ SSL/TLS Error<br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<br>Possible causes:<br>";
    echo "- OpenSSL version incompatibility<br>";
    echo "- Certificate validation issues<br>";
    echo "- TLS version mismatch<br>";
    
} catch (Exception $e) {
    echo "✗ Connection Failed<br>";
    echo "Error Type: " . get_class($e) . "<br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<br>Stack Trace:<br><pre>";
    echo $e->getTraceAsString();
    echo "</pre>";
}

echo "<br><h2>5. System Information</h2>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";

echo "<br><h2>6. Network Test</h2>";
$host = 'cluster0.xankcjj.mongodb.net';
echo "Testing DNS resolution for $host...<br>";
$ip = gethostbyname($host);
if ($ip !== $host) {
    echo "✓ DNS resolved to: $ip<br>";
} else {
    echo "✗ DNS resolution failed<br>";
}
?>
