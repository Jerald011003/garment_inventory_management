<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHP Test Page</h1>";
echo "<p>If you can see this, PHP is working.</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<h2>Server Information: awdadwawdawd</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "</pre>";
?> 