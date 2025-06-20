<?php
// Start session and include backend files
session_start();
require_once __DIR__ . '/../backend/inc/init.php';

// Get current user data if logged in
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT rawr_balance, ticket_balance FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch leaderboard data
$tokenLeaderboard = [];
$ticketLeaderboard = [];

try {
    $db = Database::getInstance();
    
    // Tokens leaderboard
    $stmt = $db->prepare("
        SELECT id, username, rawr_balance AS tokens, ticket_balance AS tickets
        FROM users 
        ORDER BY rawr_balance DESC 
        LIMIT 30
    ");
    $stmt->execute();
    $tokenLeaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tickets leaderboard
    $stmt = $db->prepare("
        SELECT id, username, rawr_balance AS tokens, ticket_balance AS tickets
        FROM users 
        ORDER BY ticket_balance DESC 
        LIMIT 30
    ");
    $stmt->execute();
    $ticketLeaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    // Handle error - show message to user
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAWR Casino - Leaderboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/RAWR/public/css/style.css">
    <style>
        /* Page Header */
        .page-header {
            padding: 100px 1rem 40px;
            text-align: center;
            background: rgba(0, 0, 0, 0.3);
            position: relative;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
        }
        
        .page-header p {
            font-size: 1rem;
            max-width: 600px;
            margin: 0 auto;
            color: var(--text-muted);
        }
        
        /* Leaderboard Section */
        .leaderboard-section {
            padding: 2rem 1rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .section-title {
            font-size: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .tabs {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .tab-btn {
            background: var(--card-bg);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 0.7rem 1.5rem;
            color: var(--text-muted);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #1a1a1a;
            border-color: var(--secondary);
            font-weight: 600;
        }
        
        .tab-btn:not(.active):hover {
            background: rgba(255, 215, 0, 0.1);
            color: var(--primary);
        }
        
        .leaderboard-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            margin-bottom: 2rem;
        }
        
        .leaderboard-header {
            display: grid;
            grid-template-columns: 50px 1fr 150px 150px;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            font-weight: 600;
            color: var(--primary);
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
        }
        
        .leaderboard-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .leaderboard-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .leaderboard-list::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        
        .leaderboard-list::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }
        
        .leaderboard-item {
            display: grid;
            grid-template-columns: 50px 1fr 150px 150px;
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 215, 0, 0.05);
            transition: var(--transition);
        }
        
        .leaderboard-item:hover {
            background: rgba(255, 215, 0, 0.05);
        }
        
        .leaderboard-rank {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .rank-1, .rank-2, .rank-3 {
            position: relative;
        }
        
        .rank-1::before {
            content: "🥇";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
        }
        
        .rank-2::before {
            content: "🥈";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
        }
        
        .rank-3::before {
            content: "🥉";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
        }
        
        .rank-1 .rank-number,
        .rank-2 .rank-number,
        .rank-3 .rank-number {
            margin-left: 25px;
        }
        
        .leaderboard-user {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            overflow: hidden;
        }
        
        .user-name {
            font-weight: 500;
        }
        
        .user-stats {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .stat-value {
            font-weight: 600;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .load-more {
            display: block;
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            padding: 0.8rem;
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
            text-align: center;
        }
        
        .load-more:hover {
            background: rgba(255, 215, 0, 0.1);
            transform: translateY(-3px);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        /* Desktop Styles */
        @media (min-width: 768px) {
            
            .wallet-balance {
                gap: 1rem;
            }
            
            .balance-item {
                padding: 0.5rem 1rem;
            }
            
            .balance-label, .balance-value {
                font-size: 0.9rem;
            }
            
            .page-header {
                padding: 120px 2rem 60px;
            }
            
            .page-header h1 {
                font-size: 3.5rem;
            }
            
            .page-header p {
                font-size: 1.1rem;
            }
            
            .leaderboard-section {
                padding: 2rem;
            }
        }
        
        @media (max-width: 600px) {
            .leaderboard-header {
                grid-template-columns: 40px 1fr 100px;
            }
            
            .leaderboard-item {
                grid-template-columns: 40px 1fr 100px;
            }
            
            .leaderboard-header div:nth-child(4),
            .leaderboard-item div:nth-child(4) {
                display: none;
            }
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
        <span class="balance-value">
            <?= $currentUser ? number_format($currentUser['rawr_balance'], 2) : '0.00' ?>
        </span>
    </div>
    <div class="balance-item">
        <i class="fas fa-ticket-alt balance-icon"></i>
        <span class="balance-label">Tickets:</span>
        <span class="balance-value">
            <?= $currentUser ? $currentUser['ticket_balance'] : '0' ?>
        </span>
    </div>
</div>
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="sidebar-item">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="mining.php" class="sidebar-item">
            <i class="fas fa-digging"></i>
            <span>Mining ⛏️</span>
        </a>
        <a href="games.php" class="sidebar-item">
            <i class="fas fa-dice"></i>
            <span>Lobby 🎰</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Shop 🛍️</span>
        </a>
        <a href="leaderboard.php" class="sidebar-item active">
            <i class="fas fa-trophy"></i>
            <span>Leaderboard 🏆</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-gift"></i>
            <span>Daily Rewards 🎁</span>
        </a>
        <a href="profile.php" class="sidebar-item">
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
    
    <!-- Page Header -->
    <section class="page-header">
        <h1>RAWR Leaderboard</h1>
        <p>See who rules the jungle with the most RAWR tokens and tickets!</p>
    </section>
    
    <!-- Leaderboard Section -->
    <section class="leaderboard-section">
        <h2 class="section-title">
            <i class="fas fa-crown"></i>
            Top Jungle Players
        </h2>
        
        <div class="tabs">
            <button class="tab-btn active" data-tab="tokens">RAWR Tokens</button>
            <button class="tab-btn" data-tab="tickets">Tickets</button>
        </div>
        
        <div class="leaderboard-card">
            <div class="leaderboard-header">
                <div>Rank</div>
                <div>Player</div>
                <div>RAWR Tokens</div>
                <div>Tickets</div>
            </div>
            
            <div class="leaderboard-list" id="leaderboardList">
                <!-- Top 15 players will be generated here -->
            </div>
        </div>
        
        <button class="load-more" id="loadMoreBtn">
            <i class="fas fa-chevron-down"></i> Show More Players
        </button>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>RAWR Casino</h3>
                <p>The ultimate play-to-earn experience in the jungle. Play, win, and earn your way to the top!</p>
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
            &copy; 2023 RAWR Casino. All rights reserved. The jungle is yours to conquer!
        </div>
    </footer>
    
    <script>
        // Menu Toggle for Mobile
        document.getElementById('menuToggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            
            if (sidebar.classList.contains('active') && 
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
        
        // Tab functionality
        const tabBtns = document.querySelectorAll('.tab-btn');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Remove active class from all buttons
                tabBtns.forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                btn.classList.add('active');
                
                // Sort leaderboard based on selected tab
                sortLeaderboard(btn.dataset.tab);
            });
        });
        
         const playerData = {
        tokens: <?= json_encode($tokenLeaderboard) ?>,
        tickets: <?= json_encode($ticketLeaderboard) ?>
    };

    // Add avatar emojis to player data
    const emojiList = ['🦁', '🐯', '🐅', '🐆', '🐘', '🦏', '🦒', '🦓', '🦌', '🐃', 
                      '🐂', '🐄', '🐎', '🐖', '🐏', '🐐', '🦙', '🦘', '🦥', '🦨', 
                      '🦡', '🐿️', '🦔', '🐇', '🦃', '🦚', '🦜', '🦢', '🦩', '🦝'];
    
    playerData.tokens = playerData.tokens.map(player => ({
        ...player,
        avatar: emojiList[player.id % emojiList.length]
    }));
    
    playerData.tickets = playerData.tickets.map(player => ({
        ...player,
        avatar: emojiList[player.id % emojiList.length]
    }));
    
    // Format numbers properly
    playerData.tokens = playerData.tokens.map(player => ({
        ...player,
        tokens: parseFloat(player.tokens),
        tickets: parseInt(player.tickets)
    }));
    
    playerData.tickets = playerData.tickets.map(player => ({
        ...player,
        tokens: parseFloat(player.tokens),
        tickets: parseInt(player.tickets)
    }));
        
        // Sort types
        let currentSort = 'tokens';
        let displayedPlayers = 15;
        
        // Function to sort player data
        function sortLeaderboard(sortType) {
            currentSort = sortType;
            playerData.sort((a, b) => b[sortType] - a[sortType]);
            renderLeaderboard();
        }
        
  function renderLeaderboard() {
    const leaderboardList = document.getElementById('leaderboardList');
    leaderboardList.innerHTML = '';
    
    for (let i = 0; i < Math.min(displayedPlayers, currentData.length); i++) {
        const player = currentData[i];
        const rank = i + 1;
        
        const playerEl = document.createElement('div');
        playerEl.classList.add('leaderboard-item', 'fade-in');
        playerEl.style.animationDelay = `${i * 0.05}s`;
        
        let rankClass = '';
        if (rank === 1) rankClass = 'rank-1';
        else if (rank === 2) rankClass = 'rank-2';
        else if (rank === 3) rankClass = 'rank-3';
        
        playerEl.innerHTML = `
            <div class="leaderboard-rank ${rankClass}">
                <span class="rank-number">${rank}</span>
            </div>
            <div class="leaderboard-user">
                <div class="user-avatar">${player.avatar}</div>
                <div class="user-name">${player.username}</div>
            </div>
            <div class="user-stats">
                <div class="stat-value">${player.tokens.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })}</div>
                <div class="stat-label">RAWR</div>
            </div>
            <div class="user-stats">
                <div class="stat-value">${player.tickets.toLocaleString()}</div>
                <div class="stat-label">Tickets</div>
            </div>
        `;
        
        leaderboardList.appendChild(playerEl);
    }
}
        // Load more players
        document.getElementById('loadMoreBtn').addEventListener('click', () => {
            displayedPlayers += 15;
            
            if (displayedPlayers >= playerData.length) {
                document.getElementById('loadMoreBtn').textContent = "All Players Loaded";
                document.getElementById('loadMoreBtn').disabled = true;
            }
            
            renderLeaderboard();
        });
        
        // Initialize
        sortLeaderboard('tokens');
    </script>
</body>
</html>