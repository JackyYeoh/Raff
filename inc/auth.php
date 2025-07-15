<?php
session_start();
if (!defined('BASE_URL')) {
    define('BASE_URL', '/raffle-demo');
}
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . BASE_URL . '/admin/admin-login.php');
    exit;
} 