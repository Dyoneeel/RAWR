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
    
    // Get user mining data
    $miningData = $db->fetchOne("SELECT * FROM mining_data WHERE user_id = ?", [$userId]);
    
    if (!$miningData) {
        // Initialize mining data if not exists
        $db->insert('mining_data', [
            'user_id' => $userId,
            'boost_level' => 1
        ]);
        $miningData = ['boost_level' => 1, 'last_mined_at' => null];
    }
    
    // Calculate cooldown
    $cooldownRemaining = 0;
    $nextReward = getMiningReward($miningData['boost_level']);
    
    if ($miningData['last_mined_at']) {
        $lastMined = strtotime($miningData['last_mined_at']);
        $elapsed = time() - $lastMined;
        $cooldownRemaining = max(0, MINING_COOLDOWN - $elapsed);
    }
    
    // Get total mined
    $totalMined = $db->fetchOne("SELECT total_mined FROM mining_data WHERE user_id = ?", [$userId]);
    $totalMined = $totalMined ? (float)$totalMined['total_mined'] : 0.0;
    
    echo json_encode([
        'success' => true,
        'boost_level' => (int)$miningData['boost_level'],
        'boost_multiplier' => 1 + ($miningData['boost_level'] * 0.2),
        'next_reward' => $nextReward,
        'cooldown_remaining' => $cooldownRemaining,
        'total_mined' => $totalMined
    ]);
} catch (Exception $e) {
    error_log("Mining status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}