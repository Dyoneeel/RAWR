<?php
require_once __DIR__ . '/../../inc/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    $user = $db->fetchOne("SELECT rawr_balance, ticket_balance FROM users WHERE id = ?", [$userId]);
    
    if ($user) {
        echo json_encode([
            'success' => true,
            'rawr_balance' => (float)$user['rawr_balance'],
            'ticket_balance' => (int)$user['ticket_balance']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (Exception $e) {
    error_log("Balance fetch error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}