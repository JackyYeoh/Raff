<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../inc/database.php';
require_once '../inc/user_auth.php';
require_once '../inc/loyalty_system.php';

$auth = new UserAuth();
$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

try {
    switch ($action) {
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $name = $_POST['name'] ?? '';
            $phone = $_POST['phone'] ?? null;
            
            if (empty($email) || empty($password) || empty($name)) {
                throw new Exception('Email, password, and name are required');
            }
            
            $result = $auth->register($email, $password, $name, $phone);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'],
                    'user_id' => $result['user_id']
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $result['error']
                ]);
            }
            break;
            
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                throw new Exception('Email and password are required');
            }
            
            $result = $auth->login($email, $password);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'],
                    'user' => $result['user'],
                    'redirect' => '/raffle-demo/dashboard.php'
                ]);
            } else {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => $result['error']
                ]);
            }
            break;
            
        case 'logout':
            $result = $auth->logout();
            echo json_encode([
                'success' => true,
                'message' => $result['message'],
                'redirect' => '/raffle-demo/index.php'
            ]);
            break;
            
        case 'status':
            if ($auth->isLoggedIn()) {
                $user = $auth->getCurrentUser();
                echo json_encode([
                    'success' => true,
                    'logged_in' => true,
                    'user' => $user
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'logged_in' => false,
                    'user' => null
                ]);
            }
            break;
            
        case 'profile':
            if (!$auth->isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Not logged in']);
                break;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $user = $auth->getCurrentUser();
                echo json_encode([
                    'success' => true,
                    'user' => $user
                ]);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = [
                    'name' => $_POST['name'] ?? null,
                    'phone' => $_POST['phone'] ?? null
                ];
                
                $result = $auth->updateProfile($_SESSION['user_id'], $data);
                
                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => $result['message'],
                        'user' => $auth->getCurrentUser()
                    ]);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => $result['error']
                    ]);
                }
            }
            break;
            
        case 'change_password':
            if (!$auth->isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Not logged in']);
                break;
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword)) {
                throw new Exception('Current and new passwords are required');
            }
            
            $result = $auth->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
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
            
        case 'check_email':
            $email = $_GET['email'] ?? '';
            if (empty($email)) {
                throw new Exception('Email parameter required');
            }
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $exists = $stmt->fetchColumn() > 0;
            
            echo json_encode([
                'success' => true,
                'exists' => $exists
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
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 