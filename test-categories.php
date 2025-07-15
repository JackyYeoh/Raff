<?php
require_once 'inc/database.php';

echo "<h1>Categories Test</h1>";

try {
    // Test database connection
    echo "<p>Testing database connection...</p>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
    $result = $stmt->fetch();
    echo "<p>Categories count: " . $result['count'] . "</p>";
    
    // Test table structure
    echo "<p>Testing table structure...</p>";
    $stmt = $pdo->query("DESCRIBE categories");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Table columns:</p><ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>";
    }
    echo "</ul>";
    
    // Test sample data
    echo "<p>Testing sample data...</p>";
    $stmt = $pdo->query("SELECT * FROM categories LIMIT 5");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Sample categories:</p><ul>";
    foreach ($categories as $category) {
        echo "<li>" . $category['name'] . " (ID: " . $category['id'] . ")</li>";
    }
    echo "</ul>";
    
    echo "<p style='color: green;'>All tests passed! ✓</p>";
    echo "<p><a href='admin/categories.php'>Go to Categories Management</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p><a href='setup-sample-data.php'>Run Sample Data Setup</a></p>";
}
?> 