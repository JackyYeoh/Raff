<?php
require_once 'inc/database.php';

echo "<h2>🔍 Checking Raffle Tags System Setup</h2>";

// Check if tables exist
$tables = ['raffle_tags', 'popular_tags', 'user_tag_preferences'];
$existingTables = [];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table <strong>$table</strong> exists<br>";
            $existingTables[] = $table;
        } else {
            echo "❌ Table <strong>$table</strong> does not exist<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error checking table <strong>$table</strong>: " . $e->getMessage() . "<br>";
    }
}

echo "<br>";

// Check if tags column exists in raffles table
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM raffles LIKE 'tags'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tags column exists in raffles table<br>";
    } else {
        echo "❌ Tags column does not exist in raffles table<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking raffles table: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Check if there are any raffles
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM raffles");
    $raffleCount = $stmt->fetch()['count'];
    echo "📊 Total raffles in database: <strong>$raffleCount</strong><br>";
} catch (Exception $e) {
    echo "❌ Error counting raffles: " . $e->getMessage() . "<br>";
}

// Check if there are any tags
if (in_array('raffle_tags', $existingTables)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM raffle_tags");
        $tagCount = $stmt->fetch()['count'];
        echo "🏷️ Total tags in database: <strong>$tagCount</strong><br>";
    } catch (Exception $e) {
        echo "❌ Error counting tags: " . $e->getMessage() . "<br>";
    }
}

// Check if there are popular tags
if (in_array('popular_tags', $existingTables)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM popular_tags");
        $popularTagCount = $stmt->fetch()['count'];
        echo "🔥 Popular tags available: <strong>$popularTagCount</strong><br>";
    } catch (Exception $e) {
        echo "❌ Error counting popular tags: " . $e->getMessage() . "<br>";
    }
}

echo "<br>";

if (count($existingTables) === 3) {
    echo "🎉 <strong>Tags system is properly set up!</strong><br>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li>Go to <a href='admin/raffles.php'>Admin → Raffles</a></li>";
    echo "<li>Click 'Edit' on any raffle</li>";
    echo "<li>Scroll down to 'Tags & Recommendations' section</li>";
    echo "<li>Add tags to improve discoverability</li>";
    echo "</ul>";
} else {
    echo "⚠️ <strong>Tags system needs setup!</strong><br>";
    echo "<p>Please run: <a href='setup-raffle-tags.php'>setup-raffle-tags.php</a></p>";
}

echo "<br><a href='test-tags.php'>🧪 Test Tags System</a>";
?> 