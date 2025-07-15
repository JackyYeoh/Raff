<?php
// Payment System Database Setup Script
require_once 'inc/database.php';

echo "<h1>Payment System Database Setup</h1>";

try {
    // Create payment tables
    $paymentTables = [
        'payment_transactions' => "CREATE TABLE IF NOT EXISTS payment_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_id VARCHAR(255) UNIQUE NOT NULL,
            user_id INT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'MYR',
            description TEXT,
            status ENUM('pending', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
            payment_method ENUM('touchngo', 'googlepay', 'wallet') NOT NULL,
            transaction_id VARCHAR(255) NULL,
            metadata JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            INDEX idx_payment_id (payment_id),
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_payment_method (payment_method),
            INDEX idx_created_at (created_at)
        )",
        
        'wallet_transactions' => "CREATE TABLE IF NOT EXISTS wallet_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            transaction_type ENUM('topup', 'spend', 'refund') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            balance_before DECIMAL(10,2) NOT NULL DEFAULT 0,
            balance_after DECIMAL(10,2) NOT NULL DEFAULT 0,
            status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
            payment_id VARCHAR(255) NULL,
            reference_type ENUM('raffle_ticket', 'loyalty_store', 'topup', 'refund') NULL,
            reference_id INT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            INDEX idx_user_id (user_id),
            INDEX idx_transaction_type (transaction_type),
            INDEX idx_status (status),
            INDEX idx_payment_id (payment_id),
            INDEX idx_created_at (created_at)
        )",
        
        'payment_methods' => "CREATE TABLE IF NOT EXISTS payment_methods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            method_type ENUM('touchngo', 'googlepay', 'card') NOT NULL,
            provider_id VARCHAR(255) NOT NULL,
            is_default BOOLEAN DEFAULT FALSE,
            metadata JSON NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            INDEX idx_user_id (user_id),
            INDEX idx_method_type (method_type),
            INDEX idx_is_default (is_default)
        )",
        
        'payment_webhooks' => "CREATE TABLE IF NOT EXISTS payment_webhooks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_id VARCHAR(255) NOT NULL,
            webhook_source ENUM('touchngo', 'googlepay', 'stripe') NOT NULL,
            webhook_event VARCHAR(100) NOT NULL,
            webhook_data JSON NOT NULL,
            processed BOOLEAN DEFAULT FALSE,
            processed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_payment_id (payment_id),
            INDEX idx_webhook_source (webhook_source),
            INDEX idx_processed (processed),
            INDEX idx_created_at (created_at)
        )"
    ];
    
    // Create tables
    foreach ($paymentTables as $tableName => $sql) {
        echo "<h3>Creating table: $tableName</h3>";
        $pdo->exec($sql);
        echo "<p style='color: green;'>✓ Table '$tableName' created successfully</p>";
    }
    
    // Add payment_id column to existing tickets table if it doesn't exist
    echo "<h3>Updating existing tables</h3>";
    try {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN payment_id VARCHAR(255) NULL AFTER final_price");
        echo "<p style='color: green;'>✓ Added payment_id column to tickets table</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: blue;'>ℹ payment_id column already exists in tickets table</p>";
        } else {
            throw $e;
        }
    }
    
    // Add index for payment_id in tickets table
    try {
        $pdo->exec("CREATE INDEX idx_tickets_payment_id ON tickets(payment_id)");
        echo "<p style='color: green;'>✓ Added index for payment_id in tickets table</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p style='color: blue;'>ℹ Index for payment_id already exists in tickets table</p>";
        } else {
            throw $e;
        }
    }
    
    // Insert sample payment configuration
    echo "<h3>Setting up payment configuration</h3>";
    
    $configTable = "CREATE TABLE IF NOT EXISTS payment_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider ENUM('touchngo', 'googlepay', 'stripe') NOT NULL,
        config_key VARCHAR(100) NOT NULL,
        config_value TEXT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_provider_key (provider, config_key)
    )";
    
    $pdo->exec($configTable);
    echo "<p style='color: green;'>✓ Payment configuration table created</p>";
    
    // Insert default configurations
    $defaultConfigs = [
        ['touchngo', 'merchant_id', 'DEMO_MERCHANT_TNG'],
        ['touchngo', 'api_key', 'demo_api_key_touchngo'],
        ['touchngo', 'secret_key', 'demo_secret_key_touchngo'],
        ['touchngo', 'sandbox_mode', '1'],
        ['touchngo', 'currency', 'MYR'],
        ['touchngo', 'timeout_minutes', '15'],
        
        ['googlepay', 'merchant_id', 'DEMO_MERCHANT_GPAY'],
        ['googlepay', 'api_key', 'demo_api_key_googlepay'],
        ['googlepay', 'environment', 'TEST'],
        ['googlepay', 'currency', 'MYR'],
        ['googlepay', 'gateway', 'stripe'],
        
        ['stripe', 'publishable_key', 'pk_test_demo_key'],
        ['stripe', 'secret_key', 'sk_test_demo_key'],
        ['stripe', 'webhook_secret', 'whsec_demo_secret']
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO payment_config (provider, config_key, config_value) 
        VALUES (?, ?, ?)
    ");
    
    foreach ($defaultConfigs as $config) {
        $stmt->execute($config);
    }
    
    echo "<p style='color: green;'>✓ Default payment configurations inserted</p>";
    
    // Create payment statistics view
    echo "<h3>Creating payment statistics view</h3>";
    
    $viewSql = "CREATE OR REPLACE VIEW payment_statistics AS
        SELECT 
            DATE(created_at) as payment_date,
            payment_method,
            COUNT(*) as transaction_count,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END) as failed_amount,
            AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END) as avg_transaction_amount
        FROM payment_transactions 
        GROUP BY DATE(created_at), payment_method
        ORDER BY payment_date DESC, payment_method";
    
    $pdo->exec($viewSql);
    echo "<p style='color: green;'>✓ Payment statistics view created</p>";
    
    // Create wallet balance trigger to maintain consistency
    echo "<h3>Creating wallet balance triggers</h3>";
    
    $triggerSql = "
        CREATE TRIGGER IF NOT EXISTS update_wallet_balance_after_transaction
        AFTER INSERT ON wallet_transactions
        FOR EACH ROW
        BEGIN
            IF NEW.status = 'completed' THEN
                UPDATE users 
                SET wallet_balance = NEW.balance_after 
                WHERE id = NEW.user_id;
            END IF;
        END;
    ";
    
    $pdo->exec($triggerSql);
    echo "<p style='color: green;'>✓ Wallet balance trigger created</p>";
    
    echo "<h2 style='color: green;'>🎉 Payment System Setup Complete!</h2>";
    echo "<div style='background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Configure your actual Touch 'n Go API credentials in the payment_config table</li>";
    echo "<li>Set up Google Pay merchant account and update credentials</li>";
    echo "<li>Configure webhook endpoints for payment notifications</li>";
    echo "<li>Test the payment integration with sandbox/test modes</li>";
    echo "<li>Set up SSL certificates for secure payment processing</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>⚠️ Important Security Notes:</h3>";
    echo "<ul>";
    echo "<li>Never store actual API keys in plain text in production</li>";
    echo "<li>Use environment variables or secure key management systems</li>";
    echo "<li>Implement proper webhook signature verification</li>";
    echo "<li>Enable HTTPS for all payment-related endpoints</li>";
    echo "<li>Regularly audit payment transactions for anomalies</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?> 