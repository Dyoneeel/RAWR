<?php
require_once __DIR__ . '/../backend/inc/init.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Dashboard';
include_once __DIR__ . '/partials/header.php';
?>

<div class="dashboard-grid">
    <!-- Balance Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>Your Balances</h2>
            <i class="fas fa-coins"></i>
        </div>
        <div class="balance-card">
            <div class="balance-value" id="rawrBalance">0.00000000</div>
            <div class="balance-label">RAWR Tokens</div>
            
            <div class="balance-value" id="ticketBalance">0</div>
            <div class="balance-label">Tickets</div>
            
            <button class="cta-button" id="convertBtn">Convert to Tickets</button>
        </div>
    </div>
    
    <!-- Mining Status Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>Mining Status</h2>
            <i class="fas fa-digging"></i>
        </div>
        <div class="mining-status-card">
            <div class="mining-info">
                <div class="mining-stat">
                    <span class="stat-label">Boost Level</span>
                    <span class="stat-value" id="boostLevel">1</span>
                </div>
                <div class="mining-stat">
                    <span class="stat-label">Next Reward</span>
                    <span class="stat-value" id="nextReward">0.50</span>
                </div>
            </div>
            
            <div class="progress-container">
                <div class="progress-label">
                    <span>Next mining in:</span>
                    <span id="miningTimer">Ready!</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="miningProgress"></div>
                </div>
            </div>
            
            <button class="cta-button" id="mineButton">Mine Now</button>
        </div>
    </div>
    
    <!-- Referral Stats Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>Referral Program</h2>
            <i class="fas fa-users"></i>
        </div>
        <div class="referral-stats">
            <div class="referral-stat">
                <span class="stat-value" id="referralCount">0</span>
                <span class="stat-label">Friends Referred</span>
            </div>
            <div class="referral-stat">
                <span class="stat-value" id="referralEarnings">0.00</span>
                <span class="stat-label">RAWR Earned</span>
            </div>
            
            <div class="referral-cta">
                <p>Invite friends and earn 50 RAWR for each completed KYC</p>
                <button class="cta-button" id="shareReferralBtn">Share Referral Link</button>
            </div>
        </div>
    </div>
    
    <!-- KYC Status Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>KYC Verification</h2>
            <i class="fas fa-id-card"></i>
        </div>
        <div class="kyc-status" id="kycStatusDisplay">
            <div class="kyc-message">
                <i class="fas fa-exclamation-circle"></i>
                <p>KYC verification required to play games and withdraw</p>
            </div>
            <div class="kyc-progress">
                <div class="progress-step completed">
                    <div class="step-icon">1</div>
                    <span>Account Created</span>
                </div>
                <div class="progress-step active">
                    <div class="step-icon">2</div>
                    <span>Complete KYC</span>
                </div>
                <div class="progress-step">
                    <div class="step-icon">3</div>
                    <span>Get Verified</span>
                </div>
            </div>
            <a href="profile.php" class="cta-button">Complete KYC Now</a>
        </div>
    </div>
</div>

<!-- Recent Activity Section -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>Recent Activity</h2>
        <i class="fas fa-history"></i>
    </div>
    <div class="activity-list" id="recentActivity">
        <div class="activity-item">
            <div class="activity-icon mining">
                <i class="fas fa-digging"></i>
            </div>
            <div class="activity-details">
                <p>Mined 0.50 RAWR</p>
                <span class="activity-time">Just now</span>
            </div>
            <div class="activity-amount positive">
                +0.50 RAWR
            </div>
        </div>
        <div class="activity-item">
            <div class="activity-icon game">
                <i class="fas fa-dice"></i>
            </div>
            <div class="activity-details">
                <p>Played Dice Game</p>
                <span class="activity-time">2 hours ago</span>
            </div>
            <div class="activity-amount negative">
                -10 Tickets
            </div>
        </div>
        <div class="activity-item">
            <div class="activity-icon shop">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="activity-details">
                <p>Purchased Silver Pickaxe</p>
                <span class="activity-time">Yesterday</span>
            </div>
            <div class="activity-amount negative">
                -1200 Tickets
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/partials/footer.php'; ?>