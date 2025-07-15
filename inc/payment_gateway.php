<?php
require_once 'database.php';

class PaymentGateway {
    private $pdo;
    private $config;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->config = [
            'touchngo' => [
                'merchant_id' => $_ENV['TOUCHNGO_MERCHANT_ID'] ?? 'demo_merchant_id',
                'api_key' => $_ENV['TOUCHNGO_API_KEY'] ?? 'demo_api_key',
                'secret_key' => $_ENV['TOUCHNGO_SECRET_KEY'] ?? 'demo_secret_key',
                'sandbox' => $_ENV['TOUCHNGO_SANDBOX'] ?? true,
                'base_url' => $_ENV['TOUCHNGO_SANDBOX'] ? 'https://sandbox-api.touchngo.com.my' : 'https://api.touchngo.com.my'
            ],
            'googlepay' => [
                'merchant_id' => $_ENV['GOOGLEPAY_MERCHANT_ID'] ?? 'demo_merchant_id',
                'api_key' => $_ENV['GOOGLEPAY_API_KEY'] ?? 'demo_api_key',
                'environment' => $_ENV['GOOGLEPAY_ENVIRONMENT'] ?? 'TEST', // TEST or PRODUCTION
                'gateway' => 'stripe' // Using Stripe as processor for Google Pay
            ]
        ];
    }
    
    /**
     * Create a payment intent for Touch 'n Go
     */
    public function createTouchNGoPayment($amount, $currency = 'MYR', $description = '', $metadata = []) {
        try {
            $paymentId = $this->generatePaymentId();
            
            // For demo purposes, we'll simulate the Touch 'n Go API
            // In production, you would integrate with actual Touch 'n Go API
            $paymentData = [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'status' => 'pending',
                'payment_method' => 'touchngo',
                'metadata' => json_encode($metadata),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Store payment record
            $stmt = $this->pdo->prepare("
                INSERT INTO payment_transactions 
                (payment_id, amount, currency, description, status, payment_method, metadata, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $paymentData['payment_id'],
                $paymentData['amount'],
                $paymentData['currency'],
                $paymentData['description'],
                $paymentData['status'],
                $paymentData['payment_method'],
                $paymentData['metadata'],
                $paymentData['created_at']
            ]);
            
            // Generate Touch 'n Go payment URL (demo)
            $paymentUrl = $this->generateTouchNGoPaymentUrl($paymentData);
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'payment_url' => $paymentUrl,
                'qr_code' => $this->generateQRCode($paymentUrl),
                'amount' => $amount,
                'currency' => $currency,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+15 minutes'))
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a payment intent for Google Pay
     */
    public function createGooglePayPayment($amount, $currency = 'MYR', $description = '', $metadata = []) {
        try {
            $paymentId = $this->generatePaymentId();
            
            // Store payment record
            $stmt = $this->pdo->prepare("
                INSERT INTO payment_transactions 
                (payment_id, amount, currency, description, status, payment_method, metadata, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $paymentId,
                $amount,
                $currency,
                $description,
                'pending',
                'googlepay',
                json_encode($metadata),
                date('Y-m-d H:i:s')
            ]);
            
            // Generate Google Pay payment token
            $paymentToken = $this->generateGooglePayToken($paymentId, $amount, $currency);
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'payment_token' => $paymentToken,
                'amount' => $amount,
                'currency' => $currency,
                'merchant_info' => [
                    'merchant_name' => 'RaffLah!',
                    'merchant_id' => $this->config['googlepay']['merchant_id']
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process wallet top-up
     */
    public function processWalletTopup($userId, $amount, $paymentMethod = 'touchngo') {
        try {
            $this->pdo->beginTransaction();
            
            // Create payment intent
            $paymentResult = $paymentMethod === 'googlepay' 
                ? $this->createGooglePayPayment($amount, 'MYR', 'Wallet Top-up', ['user_id' => $userId, 'type' => 'wallet_topup'])
                : $this->createTouchNGoPayment($amount, 'MYR', 'Wallet Top-up', ['user_id' => $userId, 'type' => 'wallet_topup']);
            
            if (!$paymentResult['success']) {
                throw new Exception($paymentResult['error']);
            }
            
            // Create wallet transaction record
            $stmt = $this->pdo->prepare("
                INSERT INTO wallet_transactions 
                (user_id, transaction_type, amount, status, payment_id, description, created_at)
                VALUES (?, 'topup', ?, 'pending', ?, 'Wallet top-up via {$paymentMethod}', NOW())
            ");
            $stmt->execute([$userId, $amount, $paymentResult['payment_id']]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'payment_data' => $paymentResult,
                'message' => 'Payment initiated successfully'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process raffle ticket purchase
     */
    public function processTicketPurchase($userId, $raffleId, $quantity, $paymentMethod = 'touchngo') {
        try {
            $this->pdo->beginTransaction();
            
            // Get raffle details
            $stmt = $this->pdo->prepare("SELECT * FROM raffles WHERE id = ? AND status = 'active'");
            $stmt->execute([$raffleId]);
            $raffle = $stmt->fetch();
            
            if (!$raffle) {
                throw new Exception('Raffle not found or inactive');
            }
            
            // Check availability
            $remaining = $raffle['total_tickets'] - $raffle['sold_tickets'];
            if ($remaining < $quantity) {
                throw new Exception('Not enough tickets available');
            }
            
            $totalAmount = $raffle['ticket_price'] * $quantity;
            
            // Create payment intent
            $paymentResult = $paymentMethod === 'googlepay' 
                ? $this->createGooglePayPayment($totalAmount, 'MYR', "Raffle Tickets - {$raffle['title']}", [
                    'user_id' => $userId, 
                    'raffle_id' => $raffleId, 
                    'quantity' => $quantity,
                    'type' => 'ticket_purchase'
                ])
                : $this->createTouchNGoPayment($totalAmount, 'MYR', "Raffle Tickets - {$raffle['title']}", [
                    'user_id' => $userId, 
                    'raffle_id' => $raffleId, 
                    'quantity' => $quantity,
                    'type' => 'ticket_purchase'
                ]);
            
            if (!$paymentResult['success']) {
                throw new Exception($paymentResult['error']);
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'payment_data' => $paymentResult,
                'total_amount' => $totalAmount,
                'quantity' => $quantity,
                'message' => 'Payment initiated successfully'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle payment webhook/callback
     */
    public function handlePaymentCallback($paymentId, $status, $transactionId = null) {
        try {
            $this->pdo->beginTransaction();
            
            // Get payment record
            $stmt = $this->pdo->prepare("SELECT * FROM payment_transactions WHERE payment_id = ?");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();
            
            if (!$payment) {
                throw new Exception('Payment not found');
            }
            
            // Update payment status
            $stmt = $this->pdo->prepare("
                UPDATE payment_transactions 
                SET status = ?, transaction_id = ?, updated_at = NOW() 
                WHERE payment_id = ?
            ");
            $stmt->execute([$status, $transactionId, $paymentId]);
            
            if ($status === 'completed') {
                $this->processSuccessfulPayment($payment);
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Payment status updated successfully'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process successful payment
     */
    private function processSuccessfulPayment($payment) {
        $metadata = json_decode($payment['metadata'], true);
        
        switch ($metadata['type']) {
            case 'wallet_topup':
                $this->processWalletTopupSuccess($metadata['user_id'], $payment['amount'], $payment['payment_id']);
                break;
                
            case 'ticket_purchase':
                $this->processTicketPurchaseSuccess(
                    $metadata['user_id'], 
                    $metadata['raffle_id'], 
                    $metadata['quantity'], 
                    $payment['amount'],
                    $payment['payment_id']
                );
                break;
        }
    }
    
    /**
     * Process successful wallet top-up
     */
    private function processWalletTopupSuccess($userId, $amount, $paymentId) {
        // Update user wallet balance
        $stmt = $this->pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->execute([$amount, $userId]);
        
        // Update wallet transaction status
        $stmt = $this->pdo->prepare("UPDATE wallet_transactions SET status = 'completed' WHERE payment_id = ?");
        $stmt->execute([$paymentId]);
        
        // Record loyalty points for wallet top-up (1 point per RM)
        $loyaltyPoints = floor($amount);
        if ($loyaltyPoints > 0) {
            $stmt = $this->pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
            $stmt->execute([$loyaltyPoints, $userId]);
            
            // Record loyalty transaction
            $stmt = $this->pdo->prepare("
                INSERT INTO loyalty_transactions 
                (user_id, transaction_type, points_change, balance_after, source_type, description, created_at)
                VALUES (?, 'earned', ?, (SELECT loyalty_points FROM users WHERE id = ?), 'purchase', 'Wallet top-up bonus', NOW())
            ");
            $stmt->execute([$userId, $loyaltyPoints, $userId]);
        }
    }
    
    /**
     * Process successful ticket purchase
     */
    private function processTicketPurchaseSuccess($userId, $raffleId, $quantity, $amount, $paymentId) {
        // Generate ticket numbers
        for ($i = 0; $i < $quantity; $i++) {
            $ticketNumber = $this->generateTicketNumber($raffleId);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO tickets 
                (raffle_id, user_id, ticket_number, original_price, final_price, payment_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $raffleId, 
                $userId, 
                $ticketNumber, 
                $amount / $quantity, 
                $amount / $quantity, 
                $paymentId
            ]);
        }
        
        // Update raffle sold tickets count
        $stmt = $this->pdo->prepare("UPDATE raffles SET sold_tickets = sold_tickets + ? WHERE id = ?");
        $stmt->execute([$quantity, $raffleId]);
        
        // Award loyalty points for purchase (1 point per RM)
        $loyaltyPoints = floor($amount);
        if ($loyaltyPoints > 0) {
            $stmt = $this->pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
            $stmt->execute([$loyaltyPoints, $userId]);
            
            // Record loyalty transaction
            $stmt = $this->pdo->prepare("
                INSERT INTO loyalty_transactions 
                (user_id, transaction_type, points_change, balance_after, source_type, description, created_at)
                VALUES (?, 'earned', ?, (SELECT loyalty_points FROM users WHERE id = ?), 'purchase', 'Ticket purchase bonus', NOW())
            ");
            $stmt->execute([$userId, $loyaltyPoints, $userId]);
        }
    }
    
    /**
     * Get payment status
     */
    public function getPaymentStatus($paymentId) {
        $stmt = $this->pdo->prepare("SELECT * FROM payment_transactions WHERE payment_id = ?");
        $stmt->execute([$paymentId]);
        return $stmt->fetch();
    }
    
    /**
     * Get user payment history
     */
    public function getUserPaymentHistory($userId, $limit = 20) {
        $stmt = $this->pdo->prepare("
            SELECT pt.*, wt.transaction_type as wallet_type
            FROM payment_transactions pt
            LEFT JOIN wallet_transactions wt ON pt.payment_id = wt.payment_id
            WHERE JSON_EXTRACT(pt.metadata, '$.user_id') = ?
            ORDER BY pt.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    // Helper methods
    private function generatePaymentId() {
        return 'PAY_' . strtoupper(uniqid()) . '_' . time();
    }
    
    private function generateTicketNumber($raffleId) {
        return 'TKT_' . $raffleId . '_' . strtoupper(uniqid());
    }
    
    private function generateTouchNGoPaymentUrl($paymentData) {
        // In production, this would be the actual Touch 'n Go payment URL
        return "https://payment.touchngo.com.my/pay?id={$paymentData['payment_id']}&amount={$paymentData['amount']}";
    }
    
    private function generateQRCode($url) {
        // In production, you would generate an actual QR code
        // For demo, return a placeholder QR code data URL
        return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==";
    }
    
    private function generateGooglePayToken($paymentId, $amount, $currency) {
        // In production, this would integrate with Google Pay API
        return base64_encode(json_encode([
            'payment_id' => $paymentId,
            'amount' => $amount,
            'currency' => $currency,
            'timestamp' => time()
        ]));
    }
}
?> 