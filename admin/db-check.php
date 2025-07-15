<?php
// Database Diagnostic Tool
require_once '../inc/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { background: #007aff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔍 Database Diagnostic Tool</h1>";

try {
    // Test database connection
    echo "<h2>Database Connection</h2>";
    if ($pdo) {
        echo "<p class='success'>✅ Database connection successful</p>";
    } else {
        echo "<p class='error'>❌ Database connection failed</p>";
        exit;
    }

    // Check required tables
    echo "<h2>Required Tables Check</h2>";
    $required_tables = ['raffles', 'users', 'tickets', 'winners', 'platform_stats'];
    $existing_tables = [];
    
    $tables_result = $pdo->query("SHOW TABLES");
    while ($row = $tables_result->fetch(PDO::FETCH_NUM)) {
        $existing_tables[] = $row[0];
    }
    
    echo "<table>";
    echo "<tr><th>Table Name</th><th>Status</th><th>Action</th></tr>";
    
    foreach ($required_tables as $table) {
        echo "<tr>";
        echo "<td>$table</td>";
        if (in_array($table, $existing_tables)) {
            echo "<td class='success'>✅ EXISTS</td>";
            echo "<td>-</td>";
        } else {
            echo "<td class='error'>❌ MISSING</td>";
            echo "<td><a href='../setup-database.php' class='btn'>Run Setup</a></td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Check raffles table structure if it exists
    if (in_array('raffles', $existing_tables)) {
        echo "<h2>Raffles Table Structure</h2>";
        $columns_result = $pdo->query("DESCRIBE raffles");
        $columns = $columns_result->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Column</th><th>Type</th><th>Key</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if we have the required columns
        $required_columns = ['id', 'title', 'retail_value', 'status'];
        $existing_columns = array_column($columns, 'Field');
        
        echo "<h3>Column Check</h3>";
        foreach ($required_columns as $req_col) {
            if (in_array($req_col, $existing_columns)) {
                echo "<p class='success'>✅ Column '$req_col' exists</p>";
            } else {
                echo "<p class='error'>❌ Column '$req_col' missing</p>";
            }
        }
    }
    
    // Check winners table structure if it exists
    if (in_array('winners', $existing_tables)) {
        echo "<h2>Winners Table Structure</h2>";
        $columns_result = $pdo->query("DESCRIBE winners");
        $columns = $columns_result->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Column</th><th>Type</th><th>Key</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test problematic queries
    echo "<h2>Query Tests</h2>";
    
    // Test raffles count
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM raffles");
        $count = $stmt->fetchColumn();
        echo "<p class='success'>✅ Raffles count query: $count records</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Raffles count query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test winners join query
    try {
        $stmt = $pdo->query("SELECT w.*, r.title as raffle_title, u.name as winner_name FROM winners w JOIN raffles r ON w.raffle_id = r.id JOIN users u ON w.user_id = u.id LIMIT 1");
        $result = $stmt->fetchAll();
        echo "<p class='success'>✅ Winners join query: Works (" . count($result) . " records)</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Winners join query failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Actions</h2>";
    echo "<a href='../setup-database.php' class='btn'>🔧 Run Database Setup</a>";
    echo "<a href='dashboard.php' class='btn'>📊 Go to Dashboard</a>";
    echo "<a href='admin-login.php' class='btn'>🔐 Back to Login</a>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Database Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?> 