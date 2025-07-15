<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../inc/database.php';
require_once '../inc/user_auth.php';
require_once '../inc/payment_gateway.php';

$auth = new UserAuth();
$paymentGateway = new PaymentGateway();

$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

try {
    switch ($action) {
        case 'create_payment':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            if (!$auth->isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }
            
            $userId = $_SESSION['user_id'];
            $amount = floatval($_POST['amount'] ?? 0);
            $paymentMethod = $_POST['payment_method'] ?? 'touchngo';
            $type = $_POST['type'] ?? 'wallet_topup'; // wallet_topup or ticket_purchase
            
            if ($amount <= 0) {
                throw new Exception('Invalid amount');
            }
            
            if (!in_array($paymentMethod, ['touchngo', 'googlepay'])) {
                throw new Exception('Invalid payment method');
            }
            
            $result = null;
            
            if ($type === 'wallet_topup') {
                $result = $paymentGateway->processWalletTopup($userId, $amount, $paymentMethod);
            } elseif ($type === 'ticket_purchase') {
                $raffleId = intval($_POST['raffle_id'] ?? 0);
                $quantity = intval($_POST['quantity'] ?? 1);
                
                if ($raffleId <= 0 || $quantity <= 0) {
                    throw new Exception('Invalid raffle ID or quantity');
                }
                
                $result = $paymentGateway->processTicketPurchase($userId, $raffleId, $quantity, $paymentMethod);
            } else {
                throw new Exception('Invalid payment type');
            }
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'payment_data' => $result['payment_data'],
                    'message' => $result['message']
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $result['error']
                ]);
            }
            break;
            
        case 'wallet_payment':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            if (!$auth->isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }
            
            $userId = $_SESSION['user_id'];
            $raffleId = intval($_POST['raffle_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if ($raffleId <= 0 || $quantity <= 0) {
                throw new Exception('Invalid raffle ID or quantity');
            }
            
            $result = processWalletPayment($userId, $raffleId, $quantity);
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            break;
            
        case 'payment_status':
            $paymentId = $_GET['payment_id'] ?? '';
            
            if (empty($paymentId)) {
                throw new Exception('Payment ID required');
            }
            
            $payment = $paymentGateway->getPaymentStatus($paymentId);
            
            if ($payment) {
                echo json_encode([
                    'success' => true,
                    'payment' => [
                        'id' => $payment['payment_id'],
                        'amount' => $payment['amount'],
                        'currency' => $payment['currency'],
                        'status' => $payment['status'],
                        'payment_method' => $payment['payment_method'],
                        'created_at' => $payment['created_at'],
                        'updated_at' => $payment['updated_at']
                    ]
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Payment not found'
                ]);
            }
            break;
            
        case 'payment_history':
            if (!$auth->isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }
            
            $userId = $_SESSION['user_id'];
            $limit = intval($_GET['limit'] ?? 20);
            
            $history = $paymentGateway->getUserPaymentHistory($userId, $limit);
            
            echo json_encode([
                'success' => true,
                'payments' => array_map(function($payment) {
                    return [
                        'id' => $payment['payment_id'],
                        'amount' => $payment['amount'],
                        'currency' => $payment['currency'],
                        'description' => $payment['description'],
                        'status' => $payment['status'],
                        'payment_method' => $payment['payment_method'],
                        'created_at' => $payment['created_at']
                    ];
                }, $history)
            ]);
            break;
            
        case 'webhook':
            // Handle payment webhooks from Touch 'n Go, Google Pay, etc.
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $webhookData = json_decode(file_get_contents('php://input'), true);
            $source = $_GET['source'] ?? 'unknown';
            
            $result = handlePaymentWebhook($webhookData, $source);
            
            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Webhook processed']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $result['error']]);
            }
            break;
            
        case 'simulate_payment':
            // For demo purposes - simulate payment completion
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $paymentId = $_POST['payment_id'] ?? '';
            $status = $_POST['status'] ?? 'completed';
            
            if (empty($paymentId)) {
                throw new Exception('Payment ID required');
            }
            
            // In demo mode, allow simulation
            $result = $paymentGateway->handlePaymentCallback($paymentId, $status, 'DEMO_TXN_' . time());
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment simulation completed',
                    'payment_id' => $paymentId,
                    'status' => $status
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $result['error']
                ]);
            }
            break;
            
        case 'get_payment_methods':
            echo json_encode([
                'success' => true,
                'methods' => [
                    [
                        'id' => 'touchngo',
                        'name' => 'Touch \'n Go eWallet',
                        'icon' => 'fa-mobile-alt',
                        'description' => 'Pay with Touch \'n Go eWallet',
                        'enabled' => true,
                        'fees' => 0,
                        'min_amount' => 1.00,
                        'max_amount' => 5000.00
                    ],
                    [
                        'id' => 'googlepay',
                        'name' => 'Google Pay',
                        'icon' => 'fa-google-pay',
                        'description' => 'Pay with Google Pay',
                        'enabled' => true,
                        'fees' => 0,
                        'min_amount' => 1.00,
                        'max_amount' => 10000.00
                    ],
                    [
                        'id' => 'wallet',
                        'name' => 'RaffLah! Wallet',
                        'icon' => 'fa-wallet',
                        'description' => 'Pay from your wallet balance',
                        'enabled' => true,
                        'fees' => 0,
                        'min_amount' => 0.01,
                        'max_amount' => 999999.99
                    ]
                ]
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Process wallet payment for raffle tickets
 */
function processWalletPayment($userId, $raffleId, $quantity) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get user wallet balance
        $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Get raffle details
        $stmt = $pdo->prepare("SELECT * FROM raffles WHERE id = ? AND status = 'active'");
        $stmt->execute([$raffleId]);
        $raffle = $stmt->fetch();
        
        if (!$raffle) {
            throw new Exception('Raffle not found or inactive');
        }
        
        // Check ticket availability
        $remaining = $raffle['total_tickets'] - $raffle['sold_tickets'];
        if ($remaining < $quantity) {
            throw new Exception('Not enough tickets available');
        }
        
        $totalAmount = $raffle['ticket_price'] * $quantity;
        
        // Check wallet balance
        if ($user['wallet_balance'] < $totalAmount) {
            throw new Exception('Insufficient wallet balance');
        }
        
        // Generate payment ID for wallet transaction
        $paymentId = 'WALLET_' . strtoupper(uniqid()) . '_' . time();
        
        // Create wallet transaction
        $newBalance = $user['wallet_balance'] - $totalAmount;
        
        $stmt = $pdo->prepare("
            INSERT INTO wallet_transactions 
            (user_id, transaction_type, amount, balance_before, balance_after, status, payment_id, reference_type, reference_id, description)
            VALUES (?, 'spend', ?, ?, ?, 'completed', ?, 'raffle_ticket', ?, ?)
        ");
        $stmt->execute([
            $userId, 
            $totalAmount, 
            $user['wallet_balance'], 
            $newBalance, 
            $paymentId, 
            $raffleId, 
            "Raffle tickets purchase - {$raffle['title']}"
        ]);
        
        // Update user wallet balance
        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
        $stmt->execute([$newBalance, $userId]);
        
        // Generate tickets
        $ticketNumbers = [];
        for ($i = 0; $i < $quantity; $i++) {
            $ticketNumber = 'TKT_' . $raffleId . '_' . strtoupper(uniqid());
            $ticketNumbers[] = $ticketNumber;
            
            $stmt = $pdo->prepare("
                INSERT INTO tickets 
                (raffle_id, user_id, ticket_number, original_price, final_price, payment_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $raffleId, 
                $userId, 
                $ticketNumber, 
                $raffle['ticket_price'], 
                $raffle['ticket_price'], 
                $paymentId
            ]);
        }
        
        // Update raffle sold tickets
        $stmt = $pdo->prepare("UPDATE raffles SET sold_tickets = sold_tickets + ? WHERE id = ?");
        $stmt->execute([$quantity, $raffleId]);
        
        // Award loyalty points (1 point per RM)
        $loyaltyPoints = floor($totalAmount);
        if ($loyaltyPoints > 0) {
            $stmt = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
            $stmt->execute([$loyaltyPoints, $userId]);
            
            // Record loyalty transaction
            $stmt = $pdo->prepare("
                INSERT INTO loyalty_transactions 
                (user_id, transaction_type, points_change, balance_after, source_type, description, created_at)
                VALUES (?, 'earned', ?, (SELECT loyalty_points FROM users WHERE id = ?), 'purchase', 'Raffle ticket purchase bonus', NOW())
            ");
            $stmt->execute([$userId, $loyaltyPoints, $userId]);
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Tickets purchased successfully!',
            'payment_id' => $paymentId,
            'tickets' => $ticketNumbers,
            'total_amount' => $totalAmount,
            'new_wallet_balance' => $newBalance,
            'loyalty_points_earned' => $loyaltyPoints
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Handle payment webhooks
 */
function handlePaymentWebhook($webhookData, $source) {
    global $pdo, $paymentGateway;
    
    try {
        // Store webhook data
        $stmt = $pdo->prepare("
            INSERT INTO payment_webhooks 
            (payment_id, webhook_source, webhook_event, webhook_data, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $paymentId = $webhookData['payment_id'] ?? $webhookData['id'] ?? '';
        $event = $webhookData['event'] ?? $webhookData['type'] ?? 'unknown';
        
        $stmt->execute([
            $paymentId,
            $source,
            $event,
            json_encode($webhookData)
        ]);
        
        // Process webhook based on source and event
        switch ($source) {
            case 'touchngo':
                return processTouchNGoWebhook($webhookData);
                
            case 'googlepay':
                return processGooglePayWebhook($webhookData);
                
            default:
                return ['success' => true, 'message' => 'Webhook stored'];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Process Touch 'n Go webhook
 */
function processTouchNGoWebhook($webhookData) {
    global $paymentGateway;
    
    $paymentId = $webhookData['payment_id'] ?? '';
    $status = $webhookData['status'] ?? '';
    $transactionId = $webhookData['transaction_id'] ?? '';
    
    if (empty($paymentId) || empty($status)) {
        throw new Exception('Invalid webhook data');
    }
    
    // Map Touch 'n Go status to our status
    $statusMap = [
        'SUCCESS' => 'completed',
        'FAILED' => 'failed',
        'CANCELLED' => 'cancelled',
        'PENDING' => 'pending'
    ];
    
    $mappedStatus = $statusMap[$status] ?? 'failed';
    
    return $paymentGateway->handlePaymentCallback($paymentId, $mappedStatus, $transactionId);
}

/**
 * Process Google Pay webhook
 */
function processGooglePayWebhook($webhookData) {
    global $paymentGateway;
    
    $paymentId = $webhookData['payment_id'] ?? '';
    $status = $webhookData['status'] ?? '';
    $transactionId = $webhookData['transaction_id'] ?? '';
    
    if (empty($paymentId) || empty($status)) {
        throw new Exception('Invalid webhook data');
    }
    
    // Map Google Pay status to our status
    $statusMap = [
        'COMPLETED' => 'completed',
        'FAILED' => 'failed',
        'CANCELLED' => 'cancelled',
        'PENDING' => 'pending'
    ];
    
    $mappedStatus = $statusMap[$status] ?? 'failed';
    
    return $paymentGateway->handlePaymentCallback($paymentId, $mappedStatus, $transactionId);
}
?> 