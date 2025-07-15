<?php
// Simple test script to debug search functionality
require_once 'inc/database.php';

echo "<h1>Search API Test</h1>";

try {
    // Test database connection
    echo "<h2>Database Connection Test</h2>";
    $pdo->query("SELECT 1");
    echo "✅ Database connection successful<br>";
    
    // Check if tables exist
    echo "<h2>Table Check</h2>";
    
    $tables = ['raffles', 'categories', 'brands'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' does not exist<br>";
        }
    }
    
    // Check raffles table structure
    echo "<h2>Raffles Table Structure</h2>";
    try {
        $stmt = $pdo->query("DESCRIBE raffles");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "❌ Error checking raffles table: " . $e->getMessage() . "<br>";
    }
    
    // Check if there are any raffles
    echo "<h2>Raffles Count</h2>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM raffles");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "Total raffles: $count<br>";
        
        if ($count > 0) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM raffles WHERE status = 'active'");
            $active_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "Active raffles: $active_count<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error counting raffles: " . $e->getMessage() . "<br>";
    }
    
    // Test search API directly
    echo "<h2>Search API Test</h2>";
    $test_query = "Mario";
    $url = "api/search.php?q=" . urlencode($test_query) . "&limit=5";
    
    echo "Testing URL: $url<br>";
    
    $response = file_get_contents($url);
    if ($response === false) {
        echo "❌ Failed to get response from search API<br>";
    } else {
        $data = json_decode($response, true);
        if ($data === null) {
            echo "❌ Failed to decode JSON response<br>";
            echo "Raw response: " . htmlspecialchars($response) . "<br>";
        } else {
            echo "✅ Search API response received<br>";
            echo "Success: " . ($data['success'] ? 'true' : 'false') . "<br>";
            if (isset($data['error'])) {
                echo "Error: " . htmlspecialchars($data['error']) . "<br>";
            }
            if (isset($data['data']['total_results'])) {
                echo "Total results: " . $data['data']['total_results'] . "<br>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?> 