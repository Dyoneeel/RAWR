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
    
    // Check if user can mine
    $miningData = $db->fetchOne("SELECT * FROM mining_data WHERE user_id = ?", [$userId]);
    
    if (!$miningData) {
        echo json_encode(['success' => false, 'message' => 'Mining data not found']);
        exit;
    }
    
    $cooldownRemaining = 0;
    if ($miningData['last_mined_at']) {
        $lastMined = strtotime($miningData['last_mined_at']);
        $elapsed = time() - $lastMined;
        $cooldownRemaining = max(0, MINING_COOLDOWN - $elapsed);
    }
    
    if ($cooldownRemaining > 0) {
        echo json_encode(['success' => false, 'message' => 'Cooldown active']);
        exit;
    }
    
    // Calculate reward
    $reward = getMiningReward($miningData['boost_level']);
    
    // Update balances
    $db->beginTransaction();
    
    // Update user balance
    $db->executeQuery(
        "UPDATE users SET rawr_balance = rawr_balance + ? WHERE id = ?",
        [$reward, $userId]
    );
    
    // Update mining data
    $db->executeQuery(
        "UPDATE mining_data SET last_mined_at = NOW(), total_mined = total_mined + ? WHERE user_id = ?",
        [$reward, $userId]
    );
    
    // Get new balance
    $newBalance = $db->fetchOne("SELECT rawr_balance FROM users WHERE id = ?", [$userId]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'reward' => $reward,
        'new_balance' => (float)$newBalance['rawr_balance'],
        'cooldown' => MINING_COOLDOWN,
        'total_mined' => $miningData['total_mined'] + $reward
    ]);
} catch (Exception $e) {
    $db->rollBack();
    error_log("Mining error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}