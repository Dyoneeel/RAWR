<?php
require_once __DIR__ . '/../backend/inc/init.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Mining';
include_once __DIR__ . '/partials/header.php';
?>

<div class="mining-container">
    <div class="mining-header">
        <h1>Mine RAWR Tokens</h1>
        <p>Click the Mine button to earn RAWR tokens. Upgrade your mining equipment to increase your rewards!</p>
    </div>
    
    <div class="mining-stats">
        <div class="stat-card">
            <div class="stat-label">Current Boost</div>
            <div class="stat-value" id="currentBoost">x1.0</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Total Mined</div>
            <div class="stat-value" id="totalMined">0.00000000</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Mining Speed</div>
            <div class="stat-value" id="miningSpeed">0.5 RAWR/h</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Next Reward</div>
            <div class="stat-value" id="miningReward">0.50</div>
        </div>
    </div>
    
    <div class="mining-area">
        <div class="mining-animation">
            <div class="mining-circle">
                <div class="mining-progress" id="miningProgressCircle"></div>
                <div class="mining-core">
                    <img src="/assets/images/mining-core.png" alt="Mining Core">
                </div>
            </div>
        </div>
        
        <div class="mining-timer" id="miningTimer">Ready to mine!</div>
        <button class="mining-button" id="mineButton">MINE RAWR</button>
    </div>
    
    <div class="boost-section">
        <h2>Mining Boosts</h2>
        <p>Upgrade your mining equipment to increase your rewards and reduce cooldown time</p>
        
        <div class="boost-grid">
            <div class="boost-card">
                <div class="boost-icon">
                    <i class="fas fa-hammer"></i>
                </div>
                <h3>Bronze Pickaxe</h3>
                <div class="boost-stats">
                    <p>Boost: <strong>1.1x</strong></p>
                    <p>Reward: <strong>+0.05 RAWR</strong></p>
                </div>
                <div class="boost-price">500 Tickets</div>
                <button class="cta-button">Purchase</button>
            </div>
            
            <div class="boost-card">
                <div class="boost-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <h3>Silver Pickaxe</h3>
                <div class="boost-stats">
                    <p>Boost: <strong>1.25x</strong></p>
                    <p>Reward: <strong>+0.125 RAWR</strong></p>
                </div>
                <div class="boost-price">1200 Tickets</div>
                <button class="cta-button">Purchase</button>
            </div>
            
            <div class="boost-card">
                <div class="boost-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <h3>Golden Pickaxe</h3>
                <div class="boost-stats">
                    <p>Boost: <strong>1.5x</strong></p>
                    <p>Reward: <strong>+0.25 RAWR</strong></p>
                </div>
                <div class="boost-price">2500 Tickets</div>
                <button class="cta-button">Purchase</button>
            </div>
            
            <div class="boost-card">
                <div class="boost-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3>Power Drill</h3>
                <div class="boost-stats">
                    <p>Cooldown: <strong>-30s</strong></p>
                    <p>Reward: <strong>+0.1 RAWR</strong></p>
                </div>
                <div class="boost-price">800 Tickets</div>
                <button class="cta-button">Purchase</button>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/partials/footer.php'; ?>