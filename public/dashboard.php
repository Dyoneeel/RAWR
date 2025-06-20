<?php
require_once __DIR__ . '/../backend/inc/init.php';
userOnly();

$userId = $_SESSION['user_id'];
$db = Database::getInstance();
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

// Initialize reward popup variables
$showRewardPopup = false;
$rewardPopupData = ['rawr' => 0, 'tickets' => 0];

// Daily rewards
$dailyRewards = [
    ['rawr' => 5, 'tickets' => 1],
    ['rawr' => 10, 'tickets' => 2],
    ['rawr' => 15, 'tickets' => 3],
    ['rawr' => 20, 'tickets' => 4],
    ['rawr' => 30, 'tickets' => 5],
    ['rawr' => 50, 'tickets' => 7],
    ['rawr' => 100, 'tickets' => 10],
];


// Get streak info
$loginStreak = $db->fetchOne(
    "SELECT current_streak, longest_streak, last_login_date FROM login_streaks WHERE user_id = ?",
    [$userId]
);

$currentStreak = $loginStreak ? (int)$loginStreak['current_streak'] : 0;
$longestStreak = $loginStreak ? (int)$loginStreak['longest_streak'] : 0;
$lastLoginDate = $loginStreak ? $loginStreak['last_login_date'] : null;

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$claimedToday = ($lastLoginDate === $today);
$cycleDay = $currentStreak % 7;

// Handle daily check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['daily_checkin'])) {
    if (!$claimedToday) {
        $claimDay = $currentStreak % 7;
        $reward = $dailyRewards[$claimDay];
        try {
            $db->beginTransaction();
            $db->executeQuery(
                "UPDATE users SET rawr_balance = rawr_balance + ?, ticket_balance = ticket_balance + ? WHERE id = ?",
                [$reward['rawr'], $reward['tickets'], $userId]
            );
            $newStreak = 1;
            if ($lastLoginDate === $yesterday) {
                $newStreak = $currentStreak + 1;
            }
            $newLongest = max($longestStreak, $newStreak);
            if ($loginStreak) {
                $db->executeQuery(
                    "UPDATE login_streaks SET current_streak = ?, longest_streak = ?, last_login_date = ? WHERE user_id = ?",
                    [$newStreak, $newLongest, $today, $userId]
                );
            } else {
                $db->executeQuery(
                    "INSERT INTO login_streaks (user_id, current_streak, longest_streak, last_login_date) VALUES (?, ?, ?, ?)",
                    [$userId, $newStreak, $newLongest, $today]
                );
            }
            $db->commit();
            
            // Set reward popup data
            $showRewardPopup = true;
            $rewardPopupData = $reward;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Check-in failed: " . $e->getMessage());
        }
    }
}

// Fetch challenges
$challenges = $db->fetchAll("
    SELECT 
        ct.id,
        ct.name,
        ct.description,
        ct.reward_type,
        ct.reward_value,
        ct.target_value,
        COALESCE(cp.progress, 0) AS progress,
        cp.completed_at,
        cp.reward_claimed
    FROM challenge_types ct
    LEFT JOIN challenge_progress cp
        ON cp.challenge_id = ct.id AND cp.user_id = ?
", [$userId]);
if (!$challenges) $challenges = [];

// Handle challenge claims
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_challenge'])) {
    $challengeId = (int)$_POST['claim_challenge'];
    foreach ($challenges as $challenge) {
        if ($challenge['id'] == $challengeId && 
            $challenge['completed_at'] && 
            !$challenge['reward_claimed']) {
            try {
                $db->beginTransaction();
                $db->executeQuery(
                    "UPDATE challenge_progress SET reward_claimed = 1 WHERE user_id = ? AND challenge_id = ?",
                    [$userId, $challengeId]
                );
                $rewardType = $challenge['reward_type'];
                $rewardValue = $challenge['reward_value'];
                if ($rewardType === 'rawr') {
                    $db->executeQuery(
                        "UPDATE users SET rawr_balance = rawr_balance + ? WHERE id = ?",
                        [$rewardValue, $userId]
                    );
                } elseif ($rewardType === 'tickets') {
                    $db->executeQuery(
                        "UPDATE users SET ticket_balance = ticket_balance + ? WHERE id = ?",
                        [$rewardValue, $userId]
                    );
                }
                $db->commit();
                
                // Set reward popup data
                $showRewardPopup = true;
                $rewardPopupData = [
                    'rawr' => ($rewardType === 'rawr') ? $rewardValue : 0,
                    'tickets' => ($rewardType === 'tickets') ? $rewardValue : 0
                ];
            } catch (Exception $e) {
                $db->rollBack();
                error_log("Challenge claim failed: " . $e->getMessage());
            }
        }
    }
}

// Fetch referral stats
$referralStats = [
    'total' => 0,
    'rawr' => 0,
    'tickets' => 0
];
$row = $db->fetchOne("SELECT COUNT(*) AS total FROM referrals WHERE referrer_id = ?", [$userId]);
$referralStats['total'] = $row ? (int)$row['total'] : 0;

// Fetch recent activity (mining, games, purchases)
$recentActivity = $db->fetchAll("
    (SELECT 'mining' AS type, CONCAT('Mined ', amount, ' RAWR') AS description, 
            created_at AS time, CONCAT('+', amount) AS amount
     FROM mining_logs 
     WHERE user_id = ? 
     ORDER BY created_at DESC 
     LIMIT 5)
    UNION
    (SELECT 'game' AS type, 
            CONCAT('Won ', payout, ' tickets (', game_types.name, ')') AS description, 
            played_at AS time, 
            CONCAT('+', payout) AS amount
     FROM game_results 
     JOIN game_types ON game_types.id = game_results.game_type_id
     WHERE user_id = ? AND outcome = 'win'
     ORDER BY played_at DESC 
     LIMIT 5)
    ORDER BY time DESC 
    LIMIT 10
", [$userId, $userId]);
if (!$recentActivity) $recentActivity = [];

$pageTitle = "Dashboard - RAWR Casino";

// Refresh user data
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/RAWR/public/css/style.css">
    <style>
        
        /* Reward Popup Styles */
        .reward-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(20, 20, 20, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 215, 0, 0.1);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            z-index: 200;
            text-align: center;
            display: none;
        }

        .reward-popup h3 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .reward-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .reward-amount div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .reward-message {
            font-size: 1rem;
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        #closePopup {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #1a1a1a;
            border: none;
            border-radius: 8px;
            padding: 0.8rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        #closePopup:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        /* Overlay for Popup */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 100;
            display: none;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="logo">
            <div class="coin-logo"></div>
            <span>RAWR</span>
        </div>
        
        <div class="nav-actions">
            <div class="wallet-balance">
                <div class="balance-item">
                    <i class="fas fa-coins balance-icon"></i>
                    <span class="balance-label">RAWR:</span>
                    <span class="balance-value"><?= number_format($user['rawr_balance'], 2) ?></span>
                </div>
                <div class="balance-item">
                    <i class="fas fa-ticket-alt balance-icon"></i>
                    <span class="balance-label">Tickets:</span>
                    <span class="balance-value"><?= $user['ticket_balance'] ?></span>
                </div>
            </div>
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    </nav>
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="sidebar-item active">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="/RAWR/public/mining.php" class="sidebar-item">
            <i class="fas fa-digging"></i>
            <span>Mining ⛏️</span>
        </a>
        <a href="/RAWR/public/games.php" class="sidebar-item">
            <i class="fas fa-dice"></i>
            <span>Lobby 🎰</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Shop 🛍️</span>
        </a>
        <a href="/RAWR/public/leaderboard.php" class="sidebar-item">
            <i class="fas fa-trophy"></i>
            <span>Leaderboard 🏆</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-gift"></i>
            <span>Daily Rewards 🎁</span>
        </a>
        <a href="/RAWR/public/profile.php" class="sidebar-item">
            <i class="fas fa-user"></i>
            <span>Profile 👤</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-cog"></i>
            <span>Settings ⚙️</span>
        </a>
        <a href="logout.php" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </aside>
    
    <!-- Hero Section -->
    <section class="hero">
        <h1>Welcome to the Jungle, <?= htmlspecialchars($user['username']) ?>!</h1>
        <p>Your kingdom awaits. Mine RAWR tokens, play exciting casino games, and dominate the leaderboards. The pride of lions is counting on you!</p>
        
        <div class="coin-animation">
            <div class="coin">
                <div class="coin-face coin-front">
                    <div class="coin-lion">🦁</div>
                </div>
                <div class="coin-face coin-back">
                    <div class="coin-text">RAWR</div>
                </div>
                <div class="coin-edge"></div>
            </div>
        </div>
        
        <div class="hero-buttons">
            <button class="hero-btn pulse" id="mineNowBtn">
                <i class="fas fa-digging"></i>
                Start Mining
            </button>
            <button class="hero-btn secondary" id="playCasinoBtn">
                <i class="fas fa-dice"></i>
                Play Casino
            </button>
        </div>
    </section>
    
    <!-- Daily Check-in Section -->
    <section class="checkin">
        <h2 class="section-title">
            <i class="fas fa-calendar-check"></i>
            Daily Check-in
        </h2>
        <div class="checkin-grid" id="checkinGrid">
            <?php for ($i = 0; $i < 7; $i++): 
                $isClaimed = ($i < $currentStreak) || ($i == $currentStreak && $claimedToday);
                $isAvailable = ($i == $currentStreak && !$claimedToday);
                $isLocked = !$isClaimed && !$isAvailable;
                $reward = $dailyRewards[$i];
            ?>
            <div class="checkin-day <?= $isClaimed ? 'claimed' : '' ?> <?= $isAvailable ? 'available' : '' ?> <?= $isLocked ? 'locked' : '' ?>">
                <div class="day-number">Day <?= $i+1 ?></div>
                <div class="day-reward">
                    <div class="reward-amount"><?= $reward['rawr'] ?> RAWR</div>
                    + <?= $reward['tickets'] ?> Tickets
                </div>
                <?php if ($isClaimed): ?>
                    <div class="checkmark">✓</div>
                <?php endif; ?>
                <?php if ($isAvailable): ?>
                    <form method="post" style="width:100%;">
                        <input type="hidden" name="daily_checkin" value="1">
                        <button type="submit" class="checkin-btn">Claim</button>
                    </form>
                <?php else: ?>
                    <button class="checkin-btn" disabled>
                        <?= $isClaimed ? 'Claimed' : 'Locked' ?>
                    </button>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features">
        <h2 class="section-title">
            <i class="fas fa-crown"></i>
            King's Features
        </h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <h3 class="feature-title">Mine Precious RAWR</h3>
                <p class="feature-desc">Unearth valuable RAWR tokens with your mining tools. Upgrade your equipment to increase your earnings.</p>
                <button class="feature-btn">Start Mining</button>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-dice"></i>
                </div>
                <h3 class="feature-title">Jungle Casino</h3>
                <p class="feature-desc">Test your luck in our exciting casino games. Slots, poker, and more - all with amazing rewards.</p>
                <button class="feature-btn">Play Games</button>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <h3 class="feature-title">Leaderboards</h3>
                <p class="feature-desc">Compete with other players and climb the leaderboards. Top players earn special rewards each week.</p>
                <button class="feature-btn">View Rankings</button>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <h3 class="feature-title">Wallet Integration</h3>
                <p class="feature-desc">Securely connect your crypto wallet to manage your RAWR tokens and game tickets in one place.</p>
                <button class="feature-btn">Setup Wallet</button>
            </div>
        </div>
    </section>
    
    <!-- Daily Challenges -->
    <section class="challenges">
        <h2 class="section-title">
            <i class="fas fa-tasks"></i>
            Daily Challenges
        </h2>
        <div class="challenges-grid">
            <?php foreach ($challenges as $challenge): ?>
            <div class="challenge-card">
                <div class="challenge-header">
                    <div class="challenge-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3 class="challenge-title"><?= htmlspecialchars($challenge['name']) ?></h3>
                </div>
                <p class="challenge-desc"><?= htmlspecialchars($challenge['description']) ?></p>
                <div class="progress-container">
                    <?php
                        $progress = 0;
                        if ($challenge['completed_at']) {
                            $progress = 100;
                        } elseif ($challenge['progress'] > 0) {
                            $progress = min(100, round($challenge['progress'] / $challenge['target_value'] * 100));
                        }
                    ?>
                    <div class="progress-bar" style="width: <?= $progress ?>%"></div>
                </div>
                <div class="challenge-reward">
                    Reward: <?= (int)$challenge['reward_value'] . ' ' . htmlspecialchars(ucfirst($challenge['reward_type'])) ?>
                </div>
                <form method="post">
                    <input type="hidden" name="claim_challenge" value="<?= $challenge['id'] ?>">
                    <button type="submit" class="challenge-btn"
                        <?= ($challenge['completed_at'] && !$challenge['reward_claimed']) ? '' : 'disabled' ?>>
                        <?= $challenge['completed_at'] ? 
                            ($challenge['reward_claimed'] ? 'Completed' : 'Claim Reward') : 
                            'Continue' 
                        ?>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    
   <!-- Referral Stats Section -->
<section class="referral-section">
    <div class="referral-card">
        <div class="referral-header">
            <h2 class="referral-title">
                <i class="fas fa-users"></i>
                Referral Stats
            </h2>
        </div>
        
        <div class="referral-stats-grid">
            <div class="referral-stat">
                <div class="referral-number"><?= $referralStats['total'] ?></div>
                <div class="referral-label">Total Referrals</div>
            </div>
            
            <div class="referral-stat">
                <div class="referral-number"><?= $referralStats['rawr'] ?></div>
                <div class="referral-label">RAWR Earned</div>
            </div>
            
            <div class="referral-stat">
                <div class="referral-number"><?= $referralStats['tickets'] ?></div>
                <div class="referral-label">Tickets Earned</div>
            </div>
        </div>
        
        <div>
            <span class="stat-label">Your Referral Code:</span>
            <div class="referral-link-container">
                <input type="text" class="referral-input" id="referral-input" value="<?= $user['referral_code'] ?>" readonly>
                <button class="copy-btn" id="copy-referral">Copy</button>
                <span id="copyMsg" style="color: var(--primary); font-size: 0.9em; margin-left: 0.5rem; display:none;">Copied!</span>
            </div>
        </div>
    </div>
</section>
    
    <!-- Recent Activity -->
    <section class="activity-section">
        <div class="activity-card">
            <div class="activity-header">
                <h2 class="activity-title">
                    <i class="fas fa-history"></i>
                    Recent Activity
                </h2>
            </div>
            <div class="activity-list">
                <?php foreach ($recentActivity as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?= 
                            $activity['type'] === 'mining' ? '⛏️' : 
                            ($activity['type'] === 'game' ? '🎰' : '🛍️')
                        ?>
                    </div>
                    <div class="activity-details">
                        <div class="activity-description"><?= htmlspecialchars($activity['description']) ?></div>
                        <div class="activity-time"><?= date('Y-m-d H:i', strtotime($activity['time'])) ?></div>
                    </div>
                    <div class="activity-amount">
                        <?= htmlspecialchars($activity['amount']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>RAWR Casino</h3>
                <p>The ultimate play-to-earn experience in the jungle. Mine, play, and earn your way to the top!</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-discord"></i></a>
                    <a href="#"><i class="fab fa-telegram"></i></a>
                    <a href="#"><i class="fab fa-reddit"></i></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Mining</a></li>
                    <li><a href="#">Casino</a></li>
                    <li><a href="#">Leaderboard</a></li>
                    <li><a href="#">Shop</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Resources</h3>
                <ul class="footer-links">
                    <li><a href="#">FAQs</a></li>
                    <li><a href="#">Tutorials</a></li>
                    <li><a href="#">Whitepaper</a></li>
                    <li><a href="#">Tokenomics</a></li>
                    <li><a href="#">Support</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Legal</h3>
                <ul class="footer-links">
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Disclaimer</a></li>
                    <li><a href="#">AML Policy</a></li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            &copy; <?= date('Y') ?> RAWR Casino. All rights reserved. The jungle is yours to conquer!
        </div>
    </footer>
    
    <!-- Reward Popup -->
    <div class="overlay" id="overlay"></div>
    <div class="reward-popup" id="rewardPopup">
        <h3>Reward Claimed!</h3>
        <div class="reward-amount" id="rewardAmount">
            <?php if ($rewardPopupData['rawr'] > 0): ?>
                <div><i class="fas fa-coins"></i> +<?= $rewardPopupData['rawr'] ?> RAWR</div>
            <?php endif; ?>
            <?php if ($rewardPopupData['tickets'] > 0): ?>
                <div><i class="fas fa-ticket-alt"></i> +<?= $rewardPopupData['tickets'] ?> Tickets</div>
            <?php endif; ?>
        </div>
        <p class="reward-message">Your reward has been added to your balance</p>
        <button class="feature-btn" id="closePopup">Close</button>
    </div>
    
    <script>
        // DOM Elements
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const rewardPopup = document.getElementById('rewardPopup');
        const closePopupBtn = document.getElementById('closePopup');
        
        // Menu Toggle for Mobile with X icon
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('active') && 
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });
        
        // Copy Referral Link using modern Clipboard API
        document.getElementById('copy-referral')?.addEventListener('click', function() {
            const code = document.getElementById('referral-input').value;
            navigator.clipboard.writeText(code).then(function() {
                const msg = document.getElementById('copyMsg');
                msg.style.display = 'inline';
                setTimeout(() => { msg.style.display = 'none'; }, 1500);
            });
        });
        
        // Mine Now Button
        document.getElementById('mineNowBtn')?.addEventListener('click', function() {
            window.location.href = '/RAWR/public/mining.php';
        });
        
        // Play Casino Button
        document.getElementById('playCasinoBtn')?.addEventListener('click', function() {
            window.location.href = '/RAWR/public/games.php';
        });
        
        // Reward Popup Handling
        <?php if ($showRewardPopup): ?>
            document.addEventListener('DOMContentLoaded', function() {
                overlay.style.display = 'block';
                rewardPopup.style.display = 'block';
            });
        <?php endif; ?>
        
        // Close popup button
        closePopupBtn?.addEventListener('click', function() {
            overlay.style.display = 'none';
            rewardPopup.style.display = 'none';
        });
        
        // Close popup when clicking on overlay
        overlay?.addEventListener('click', function() {
            overlay.style.display = 'none';
            rewardPopup.style.display = 'none';
        });
    </script>
</body>
</html>