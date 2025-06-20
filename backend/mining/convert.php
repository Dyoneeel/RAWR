<?php
require_once __DIR__ . '/../../inc/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$amount = isset($data['amount']) ? (float)$data['amount'] : 0;

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    // Get user balance
    $user = $db->fetchOne("SELECT rawr_balance, ticket_balance FROM users WHERE id = ?", [$userId]);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    if ($user['rawr_balance'] < $amount) {
        echo json_encode(['success' => false, 'message' => 'Insufficient RAWR balance']);
        exit;
    }
    
    // Calculate tickets
    $tickets = calculateConversion($amount);
    
    // Update balances
    $db->beginTransaction();
    
    // Deduct RAWR, add tickets
    $db->executeQuery(
        "UPDATE users SET rawr_balance = rawr_balance - ?, ticket_balance = ticket_balance + ? WHERE id = ?",
        [$amount, $tickets, $userId]
    );
    
    // Log conversion
    $db->insert('conversion_logs', [
        'user_id' => $userId,
        'rawr_amount' => $amount,
        'tickets_received' => $tickets
    ]);
    
    // Get new balances
    $newBalances = $db->fetchOne("SELECT rawr_balance, ticket_balance FROM users WHERE id = ?", [$userId]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'tickets_received' => $tickets,
        'new_rawr_balance' => (float)$newBalances['rawr_balance'],
        'new_ticket_balance' => (int)$newBalances['ticket_balance']
    ]);
} catch (Exception $e) {
    $db->rollBack();
    error_log("Conversion error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}