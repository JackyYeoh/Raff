<?php
// Simple Database Setup Script
require_once 'inc/database.php';

echo "<h1>Database Setup</h1>";

try {
    // Check if tables exist and create them if they don't
    $tables_to_check = [
        'raffles' => "CREATE TABLE IF NOT EXISTS raffles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(500),
            ticket_price DECIMAL(10,2) NOT NULL,
            retail_value DECIMAL(10,2) NOT NULL,
            total_tickets INT NOT NULL,
            sold_tickets INT DEFAULT 0,
            status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
            draw_date DATETIME NOT NULL,
            winner_id INT NULL,
            category_id INT,
            brand_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            loyalty_points INT DEFAULT 0,
            total_points_earned INT DEFAULT 0,
            current_streak INT DEFAULT 0,
            longest_streak INT DEFAULT 0,
            last_checkin_date DATE NULL,
            vip_tier ENUM('bronze', 'silver', 'gold', 'diamond') DEFAULT 'bronze',
            vip_points INT DEFAULT 0,
            wallet_balance DECIMAL(10,2) DEFAULT 0.00,
            last_login DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        'tickets' => "CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            raffle_id INT NOT NULL,
            user_id INT NOT NULL,
            ticket_number VARCHAR(50) UNIQUE NOT NULL,
            purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'winner', 'refunded') DEFAULT 'active',
            points_discount INT DEFAULT 0,
            original_price DECIMAL(10,2) NOT NULL,
            final_price DECIMAL(10,2) NOT NULL
        )",
        
        'winners' => "CREATE TABLE IF NOT EXISTS winners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            raffle_id INT NOT NULL,
            user_id INT NOT NULL,
            ticket_id INT NOT NULL,
            win_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            claimed BOOLEAN DEFAULT FALSE,
            claim_date TIMESTAMP NULL
        )",
        
        'platform_stats' => "CREATE TABLE IF NOT EXISTS platform_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            total_raffles INT DEFAULT 0,
            total_winners INT DEFAULT 0,
            total_prizes_awarded DECIMAL(15,2) DEFAULT 0,
            total_points_distributed INT DEFAULT 0,
            total_checkins INT DEFAULT 0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        'brand_categories' => "CREATE TABLE IF NOT EXISTS brand_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            brand_id INT NOT NULL,
            category_id INT NOT NULL,
            category_sort_order INT DEFAULT 0
        )",
        
        'brands' => "CREATE TABLE IF NOT EXISTS brands (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            description TEXT,
            website_url VARCHAR(255),
            image_url VARCHAR(500),
            is_featured BOOLEAN DEFAULT FALSE,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables_to_check as $table_name => $create_sql) {
        echo "<p>Checking table: <strong>$table_name</strong> ... ";
        
        // Check if table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table_name]);
        
        if ($stmt->rowCount() > 0) {
            echo "<span style='color: green;'>EXISTS ✓</span></p>";
        } else {
            echo "<span style='color: orange;'>MISSING - Creating...</span>";
            $pdo->exec($create_sql);
            echo "<span style='color: green;'> CREATED ✓</span></p>";
        }
    }
    
    // Insert default stats if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM platform_stats");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        echo "<p>Inserting default platform stats...</p>";
        $pdo->exec("INSERT INTO platform_stats (total_raffles, total_winners, total_prizes_awarded) VALUES (0, 0, 0)");
        echo "<p style='color: green;'>Default stats inserted ✓</p>";
    }
    
    // Insert a sample user if none exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        echo "<p>Creating sample user...</p>";
        $password_hash = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, loyalty_points, vip_tier) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['john@example.com', $password_hash, 'John Doe', 500, 'bronze']);
        echo "<p style='color: green;'>Sample user created: john@example.com / password ✓</p>";
    }
    
    // Check if brand_categories table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'brand_categories'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Check if category_sort_order column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM brand_categories LIKE 'category_sort_order'");
        $columnExists = $stmt->rowCount() > 0;
        
        if (!$columnExists) {
            // Add category_sort_order column
            $pdo->exec("ALTER TABLE brand_categories ADD COLUMN category_sort_order INT DEFAULT 0 AFTER category_id");
            echo "✅ Added category_sort_order column to brand_categories table<br>";
            
            // Initialize category_sort_order with current global sort_order values
            $stmt = $pdo->query("
                UPDATE brand_categories bc 
                INNER JOIN brands b ON bc.brand_id = b.id 
                SET bc.category_sort_order = b.sort_order 
                WHERE bc.category_sort_order = 0
            ");
            echo "✅ Initialized category_sort_order with global sort_order values<br>";
        } else {
            echo "✅ category_sort_order column already exists<br>";
        }
    } else {
        echo "ℹ️ brand_categories table doesn't exist yet (will be created with new schema)<br>";
    }
    
    // Check if raffles table has brand_id column
    $stmt = $pdo->query("SHOW COLUMNS FROM raffles LIKE 'brand_id'");
    $brandIdExists = $stmt->rowCount() > 0;
    
    if (!$brandIdExists) {
        // Add brand_id column to raffles table
        $pdo->exec("ALTER TABLE raffles ADD COLUMN brand_id INT AFTER category_id");
        $pdo->exec("ALTER TABLE raffles ADD FOREIGN KEY (brand_id) REFERENCES brands(id)");
        echo "✅ Added brand_id column to raffles table<br>";
    } else {
        echo "✅ brand_id column already exists in raffles table<br>";
    }
    
    // Check if brands table exists
    $brands_table_exists = false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'brands'");
        $brands_table_exists = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        $brands_table_exists = false;
    }

    if ($brands_table_exists) {
        // Check if image_url column exists in brands table
        $stmt = $pdo->query("SHOW COLUMNS FROM brands LIKE 'image_url'");
        $imageUrlExists = $stmt->rowCount() > 0;
        
        if (!$imageUrlExists) {
            // Add image_url column to brands table
            $pdo->exec("ALTER TABLE brands ADD COLUMN image_url VARCHAR(500) AFTER website_url");
            echo "✅ Added image_url column to brands table<br>";
        } else {
            echo "✅ image_url column already exists in brands table<br>";
        }
        
        // Check if slug column exists in brands table
        $stmt = $pdo->query("SHOW COLUMNS FROM brands LIKE 'slug'");
        $slugExists = $stmt->rowCount() > 0;
        
        if (!$slugExists) {
            // Add slug column to brands table
            $pdo->exec("ALTER TABLE brands ADD COLUMN slug VARCHAR(255) UNIQUE AFTER name");
            echo "✅ Added slug column to brands table<br>";
            
            // Generate slugs for existing brands
            $stmt = $pdo->query("SELECT id, name FROM brands WHERE slug IS NULL OR slug = ''");
            $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($brands as $brand) {
                $slug = strtolower(str_replace(' ', '-', $brand['name']));
                $stmt = $pdo->prepare("UPDATE brands SET slug = ? WHERE id = ?");
                $stmt->execute([$slug, $brand['id']]);
            }
            echo "✅ Generated slugs for existing brands<br>";
        } else {
            echo "✅ slug column already exists in brands table<br>";
        }
        
        // Check if description column exists in brands table
        $stmt = $pdo->query("SHOW COLUMNS FROM brands LIKE 'description'");
        $descriptionExists = $stmt->rowCount() > 0;
        
        if (!$descriptionExists) {
            // Add description column to brands table
            $pdo->exec("ALTER TABLE brands ADD COLUMN description TEXT AFTER slug");
            echo "✅ Added description column to brands table<br>";
        } else {
            echo "✅ description column already exists in brands table<br>";
        }
        
        // Check if website_url column exists in brands table
        $stmt = $pdo->query("SHOW COLUMNS FROM brands LIKE 'website_url'");
        $websiteUrlExists = $stmt->rowCount() > 0;
        
        if (!$websiteUrlExists) {
            // Add website_url column to brands table
            $pdo->exec("ALTER TABLE brands ADD COLUMN website_url VARCHAR(255) AFTER description");
            echo "✅ Added website_url column to brands table<br>";
        } else {
            echo "✅ website_url column already exists in brands table<br>";
        }
        
        // Check if is_featured column exists in brands table
        $stmt = $pdo->query("SHOW COLUMNS FROM brands LIKE 'is_featured'");
        $isFeaturedExists = $stmt->rowCount() > 0;
        
        if (!$isFeaturedExists) {
            // Add is_featured column to brands table
            $pdo->exec("ALTER TABLE brands ADD COLUMN is_featured BOOLEAN DEFAULT FALSE AFTER image_url");
            echo "✅ Added is_featured column to brands table<br>";
        } else {
            echo "✅ is_featured column already exists in brands table<br>";
        }
        
        // Check if created_at column exists in brands table
        $stmt = $pdo->query("SHOW COLUMNS FROM brands LIKE 'created_at'");
        $createdAtExists = $stmt->rowCount() > 0;
        
        if (!$createdAtExists) {
            // Add created_at column to brands table
            $pdo->exec("ALTER TABLE brands ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER sort_order");
            echo "✅ Added created_at column to brands table<br>";
        } else {
            echo "✅ created_at column already exists in brands table<br>";
        }
    } else {
        echo "ℹ️ brands table doesn't exist yet (will be created with new schema)<br>";
    }
    
    echo "<h2 style='color: green;'>Database setup completed successfully! 🎉</h2>";
    echo "<p><a href='admin-login.php'>Go to Admin Login</a></p>";
    echo "<p><a href='index.php'>Go to Main Site</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection settings in inc/database.php</p>";
}
?> 