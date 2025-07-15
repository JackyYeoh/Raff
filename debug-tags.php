<?php
require_once 'inc/database.php';

echo "<h1>🔍 Debug Tags System</h1>";

// Test database connection
try {
    echo "<h2>Database Connection</h2>";
    echo "✅ Database connected successfully<br>";
    
    // Check if tables exist
    echo "<h2>Table Check</h2>";
    $tables = ['raffle_tags', 'popular_tags', 'user_tag_preferences'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table <strong>$table</strong> exists<br>";
        } else {
            echo "❌ Table <strong>$table</strong> does not exist<br>";
        }
    }
    
    // Check if there are any raffles
    echo "<h2>Raffles Check</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM raffles");
    $raffleCount = $stmt->fetch()['count'];
    echo "📊 Total raffles: <strong>$raffleCount</strong><br>";
    
    if ($raffleCount > 0) {
        // Get first raffle
        $stmt = $pdo->query("SELECT id, title FROM raffles LIMIT 1");
        $raffle = $stmt->fetch();
        echo "🎯 Sample raffle: <strong>{$raffle['title']}</strong> (ID: {$raffle['id']})<br>";
        
        // Test API endpoint
        echo "<h2>API Test</h2>";
        $apiUrl = "api/tags.php?action=get_raffle_tags&raffle_id={$raffle['id']}";
        echo "Testing API: <code>$apiUrl</code><br>";
        
        // Make a simple API call
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json'
            ]
        ]);
        
        $response = file_get_contents($apiUrl, false, $context);
        if ($response !== false) {
            $data = json_decode($response, true);
            echo "API Response: <pre>" . print_r($data, true) . "</pre>";
        } else {
            echo "❌ API call failed<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='admin/raffles.php'>🔧 Go to Admin Raffles</a>";
echo "<br><a href='check-tags-setup.php'>🔍 Check Setup</a>";
echo "<br><a href='test-tags.php'>�� Test Tags</a>";
?> 