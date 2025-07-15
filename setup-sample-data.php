<?php
// Sample Data Setup Script for Categories and Brands
require_once 'inc/database.php';

echo "<h1>Sample Data Setup</h1>";

try {
    // Create categories table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(50),
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        show_brands BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Add missing columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE categories ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
    } catch (Exception $e) {
        // Column probably already exists, ignore the error
    }
    
    try {
        $pdo->exec("ALTER TABLE categories ADD COLUMN show_brands BOOLEAN DEFAULT TRUE");
    } catch (Exception $e) {
        // Column probably already exists, ignore the error
    }
    
    // Create brands table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS brands (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        description TEXT,
        website_url VARCHAR(255),
        image_url VARCHAR(500),
        is_featured BOOLEAN DEFAULT FALSE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Add image_url column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE brands ADD COLUMN image_url VARCHAR(500) AFTER website_url");
    } catch (Exception $e) {
        // Column probably already exists, ignore the error
    }
    
    // Create brand_categories table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS brand_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        brand_id INT NOT NULL,
        category_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        UNIQUE KEY unique_brand_category (brand_id, category_id)
    )");
    
    echo "<p style='color: green;'>Tables created/verified ✓</p>";
    
    // Insert sample categories
    $categories = [
        ['Electronics', 'fa-mobile-alt', 'Latest gadgets and electronic devices'],
        ['Gaming', 'fa-gamepad', 'Gaming consoles, accessories, and games'],
        ['Fashion', 'fa-tshirt', 'Clothing, shoes, and fashion accessories'],
        ['Home & Living', 'fa-home', 'Home decor, furniture, and appliances'],
        ['Sports', 'fa-futbol', 'Sports equipment and outdoor gear'],
        ['Beauty', 'fa-spa', 'Beauty products and personal care']
    ];
    
    foreach ($categories as $category) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, icon, description, is_active, show_brands) VALUES (?, ?, ?, 1, 1)");
        $stmt->execute($category);
    }
    echo "<p style='color: green;'>Sample categories inserted ✓</p>";
    
    // Insert sample brands
    $brands = [
        ['Apple', 'apple', 'Premium technology and electronics', 'https://apple.com', true, 1],
        ['Samsung', 'samsung', 'Innovative electronics and mobile devices', 'https://samsung.com', true, 2],
        ['Sony', 'sony', 'Entertainment and electronics', 'https://sony.com', false, 3],
        ['Nike', 'nike', 'Athletic footwear and apparel', 'https://nike.com', true, 4],
        ['Adidas', 'adidas', 'Sports clothing and accessories', 'https://adidas.com', false, 5],
        ['Microsoft', 'microsoft', 'Software and gaming technology', 'https://microsoft.com', true, 6],
        ['PlayStation', 'playstation', 'Gaming consoles and accessories', 'https://playstation.com', false, 7],
        ['Xbox', 'xbox', 'Gaming consoles and accessories', 'https://xbox.com', false, 8],
        ['Nintendo', 'nintendo', 'Gaming consoles and accessories', 'https://nintendo.com', false, 9],
        ['Dyson', 'dyson', 'Home appliances and technology', 'https://dyson.com', false, 10],
        ['IKEA', 'ikea', 'Furniture and home accessories', 'https://ikea.com', false, 11],
        ['L\'Oreal', 'loreal', 'Beauty and personal care products', 'https://loreal.com', false, 12],
        ['MAC', 'mac', 'Professional makeup and cosmetics', 'https://maccosmetics.com', false, 13],
        ['Under Armour', 'under-armour', 'Athletic apparel and footwear', 'https://underarmour.com', false, 14],
        ['Puma', 'puma', 'Sports and lifestyle footwear', 'https://puma.com', false, 15]
    ];
    
    foreach ($brands as $brand) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO brands (name, slug, description, website_url, is_featured, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($brand);
    }
    echo "<p style='color: green;'>Sample brands inserted ✓</p>";
    
    // Create brand-category relationships
    $brand_categories = [
        // Electronics
        ['Apple', 'Electronics'],
        ['Samsung', 'Electronics'],
        ['Sony', 'Electronics'],
        ['Microsoft', 'Electronics'],
        ['Dyson', 'Electronics'],
        
        // Gaming
        ['Microsoft', 'Gaming'],
        ['PlayStation', 'Gaming'],
        ['Xbox', 'Gaming'],
        ['Nintendo', 'Gaming'],
        ['Sony', 'Gaming'],
        
        // Fashion
        ['Nike', 'Fashion'],
        ['Adidas', 'Fashion'],
        ['Under Armour', 'Fashion'],
        ['Puma', 'Fashion'],
        
        // Home & Living
        ['IKEA', 'Home & Living'],
        ['Dyson', 'Home & Living'],
        
        // Sports
        ['Nike', 'Sports'],
        ['Adidas', 'Sports'],
        ['Under Armour', 'Sports'],
        ['Puma', 'Sports'],
        
        // Beauty
        ['L\'Oreal', 'Beauty'],
        ['MAC', 'Beauty']
    ];
    
    foreach ($brand_categories as $relationship) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO brand_categories (brand_id, category_id) 
            SELECT b.id, c.id 
            FROM brands b, categories c 
            WHERE b.name = ? AND c.name = ?
        ");
        $stmt->execute($relationship);
    }
    echo "<p style='color: green;'>Brand-category relationships created ✓</p>";
    
    // Display summary
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
    $category_count = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM brands");
    $brand_count = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM brand_categories");
    $relationship_count = $stmt->fetch()['count'];
    
    echo "<h2 style='color: green;'>Sample data setup completed! 🎉</h2>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>Categories: $category_count</li>";
    echo "<li>Brands: $brand_count</li>";
    echo "<li>Brand-Category Relationships: $relationship_count</li>";
    echo "</ul>";
    
    echo "<p><a href='admin/raffles.php'>Go to Raffles Management</a></p>";
    echo "<p><a href='admin/brands.php'>Go to Brands Management</a></p>";
    echo "<p><a href='admin/categories.php'>Go to Categories Management</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection settings in inc/database.php</p>";
}
?> 