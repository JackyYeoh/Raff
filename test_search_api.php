<?php
// Test script to simulate web request to search API
echo "<h1>Search API Web Test</h1>";

// Simulate the web request
$_GET['q'] = 'PS5';
$_GET['limit'] = '10';

// Include the search API
ob_start();
include 'api/search.php';
$output = ob_get_clean();

echo "<h2>Raw Output:</h2>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

echo "<h2>JSON Decoded:</h2>";
$data = json_decode($output, true);
if ($data) {
    echo "<pre>" . print_r($data, true) . "</pre>";
} else {
    echo "Failed to decode JSON";
}
?> 