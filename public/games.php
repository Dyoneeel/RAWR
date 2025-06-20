<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAWR Casino - Game Lobby</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FFD700;
            --primary-light: #FFDF40;
            --secondary: #FFA500;
            --accent: #FF6B35;
            --dark-bg: #0d0d0d;
            --dark-bg-gradient: linear-gradient(135deg, #1a1a1a 0%, #2d1810 100%);
            --card-bg: rgba(30, 30, 30, 0.6);
            --text-light: #f0f0f0;
            --text-muted: #ccc;
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --glass-bg: rgba(40, 40, 40, 0.25);
            --glass-border: rgba(255, 215, 0, 0.1);
            --glow: 0 0 15px rgba(255, 215, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--dark-bg-gradient);
            color: var(--text-light);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><path d="M20,20 Q40,5 60,20 T100,20 Q85,40 100,60 T100,100 Q60,85 20,100 T0,100 Q5,60 0,20 T20,20 Z" fill="none" stroke="rgba(255,215,0,0.05)" stroke-width="0.5"/></svg>');
            background-size: 300px;
            opacity: 0.3;
            z-index: -1;
        }

        /* Top Navigation */
        .top-nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(15, 15, 15, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        .coin-logo {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, #FFD700, #FFA500);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #FF6B35;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .coin-logo::before {
            content: "🦁";
            font-size: 1.4rem;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
            filter: brightness(0.9) contrast(1.2);
        }

        .coin-logo::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.6) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
        }

        .wallet-balance {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .balance-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            background: rgba(0, 0, 0, 0.3);
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            min-width: auto;
        }

        .balance-icon {
            font-size: 1rem;
            color: var(--primary);
        }

        .balance-label {
            font-size: 0.7rem;
            color: var(--text-light);
            font-weight: 500;
        }

        .balance-value {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary);
            margin-left: 0.25rem;
        }

        .nav-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .menu-toggle {
            display: block;
            background: none;
            border: none;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.3);
        }

        .menu-toggle:hover {
            background: rgba(255, 215, 0, 0.1);
            transform: scale(1.1);
        }

        /* Sidebar Navigation */
        .sidebar {
            position: fixed;
            top: 70px;
            right: 0;
            width: 70%;
            height: calc(100vh - 70px);
            background: rgba(20, 20, 20, 0.95);
            backdrop-filter: blur(10px);
            border-left: 1px solid rgba(255, 215, 0, 0.1);
            padding: 1.5rem 0;
            z-index: 99;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            margin: 0.25rem 0;
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            font-size: 0.9rem;
        }

        .sidebar-item:hover {
            background: rgba(255, 215, 0, 0.05);
            color: var(--primary-light);
        }

        .sidebar-item.active {
            background: rgba(255, 215, 0, 0.1);
            color: var(--primary);
            border-left: 3px solid var(--primary);
        }

        .sidebar-item.active::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--primary);
            box-shadow: 0 0 10px var(--primary);
        }
        
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
        
        /* Featured Games Section */
        .featured-games {
            padding: 2rem 1rem;
            max-width: 1200px;
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
        
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .game-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3), var(--glow);
            border-color: rgba(255, 215, 0, 0.3);
        }
        
        .game-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.2rem;
        }
        
        .game-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--primary);
            flex-shrink: 0;
        }
        
        .game-info {
            flex: 1;
        }
        
        .game-title {
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 0.2rem;
        }
        
        .game-tag {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            background: rgba(255, 107, 53, 0.2);
            color: var(--accent);
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .game-description {
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            color: var(--text-muted);
            line-height: 1.6;
            flex: 1;
        }
        
        /* Game Previews */
        .game-preview {
            height: 160px;
            background: rgba(0, 0, 0, 0.4);
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .preview-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            text-align: center;
            padding: 1rem;
            opacity: 0;
            transition: var(--transition);
            backdrop-filter: blur(2px);
        }
        
        .game-preview:hover .preview-overlay {
            opacity: 1;
        }
        
        /* Game-specific previews */
        .slot-preview {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
        }
        
        .roulette-preview {
            background: linear-gradient(135deg, #0f9b0f, #005c00);
        }
        
        .card-preview {
            background: linear-gradient(135deg, #8e0e00, #1f1c18);
        }
        
        .treetop-preview {
            background: linear-gradient(135deg, #1d976c, #93f9b9);
        }
        
        .panther-preview {
            background: linear-gradient(135deg, #232526, #414345);
        }
        
        .play-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #1a1a1a;
            border: none;
            border-radius: 50px;
            padding: 0.8rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 100%;
            margin-top: auto;
        }
        
        .play-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.4);
        }
        
        /* Footer */
        footer {
            background: rgba(15, 15, 15, 0.9);
            padding: 2rem 1rem;
            text-align: center;
            border-top: 1px solid rgba(255, 215, 0, 0.1);
            margin-top: 3rem;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            text-align: left;
        }
        
        .footer-column h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 0.5rem;
        }
        
        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.8rem;
        }
        
        .footer-links a:hover {
            color: var(--primary);
        }
        
        .social-links {
            display: flex;
            gap: 0.8rem;
            margin-top: 1rem;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 50%;
            color: var(--primary);
            transition: var(--transition);
            font-size: 0.9rem;
        }
        
        .social-links a:hover {
            background: var(--primary);
            color: #1a1a1a;
            transform: translateY(-3px);
        }
        
        .copyright {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        
        /* Animations */
        @keyframes coin-pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .pulse {
            animation: coin-pulse 1.5s infinite;
        }
        
        /* Tablet Styles */
        @media (min-width: 576px) {
            .page-header {
                padding: 100px 1rem 50px;
            }
            
            .page-header h1 {
                font-size: 3rem;
            }
            
            .games-grid {
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            }
        }

        /* Desktop Styles */
        @media (min-width: 768px) {
            .top-nav {
                padding: 1rem 2rem;
            }
            
            .logo {
                font-size: 1.8rem;
            }
            
            .coin-logo {
                width: 45px;
                height: 45px;
            }
            
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
                    <span class="balance-value">1.2537</span>
                </div>
                <div class="balance-item">
                    <i class="fas fa-ticket-alt balance-icon"></i>
                    <span class="balance-label">Tickets:</span>
                    <span class="balance-value">85</span>
                </div>
            </div>
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <a href="#" class="sidebar-item">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-digging"></i>
            <span>Mining ⛏️</span>
        </a>
        <a href="#" class="sidebar-item active">
            <i class="fas fa-dice"></i>
            <span>Lobby 🎰</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Shop 🛍️</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-trophy"></i>
            <span>Leaderboard 🏆</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-gift"></i>
            <span>Daily Rewards 🎁</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-user"></i>
            <span>Profile 👤</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-cog"></i>
            <span>Settings ⚙️</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </aside>
    
    <!-- Page Header -->
    <section class="page-header">
        <h1>RAWR Casino Games</h1>
        <p>Step into the jungle casino and try your luck at our exotic games</p>
    </section>
    
    <!-- Featured Games Section -->
    <section class="featured-games">
        <h2 class="section-title">
            <i class="fas fa-star"></i>
            Featured Casino Games
        </h2>
        
        <div class="games-grid">
            <!-- Slot Machine -->
            <div class="game-card">
                <div class="game-preview slot-preview">
                    <div class="preview-overlay">Spin the reels and match symbols for massive RAWR payouts!</div>
                    <i class="fas fa-dice" style="font-size: 3rem; color: white;"></i>
                </div>
                <div class="game-header">
                    <div class="game-icon">
                        <i class="fas fa-dice"></i>
                    </div>
                    <div class="game-info">
                        <h3 class="game-title">Jungle Slots</h3>
                        <span class="game-tag">Popular</span>
                    </div>
                </div>
                <div class="game-description">
                    Spin the reels filled with exotic jungle symbols. Match 3 or more to win RAWR tokens and unlock bonus rounds with multiplier wilds!
                </div>
                <button class="play-btn">
                    <i class="fas fa-play"></i>
                    Play Now
                </button>
            </div>
            
            <!-- Roulette -->
            <div class="game-card">
                <div class="game-preview roulette-preview">
                    <div class="preview-overlay">Place your bets on numbers, colors, and sections of the wheel!</div>
                    <i class="fas fa-circle" style="font-size: 3rem; color: white;"></i>
                </div>
                <div class="game-header">
                    <div class="game-icon">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div class="game-info">
                        <h3 class="game-title">Safari Roulette</h3>
                        <span class="game-tag">Classic</span>
                    </div>
                </div>
                <div class="game-description">
                    Experience the thrill of European Roulette with a jungle twist. Bet on numbers, colors, or sections for chances to win up to 35x your bet!
                </div>
                <button class="play-btn">
                    <i class="fas fa-play"></i>
                    Play Now
                </button>
            </div>
            
            <!-- Card Flip -->
            <div class="game-card">
                <div class="game-preview card-preview">
                    <div class="preview-overlay">Flip cards to match pairs and uncover hidden RAWR treasures!</div>
                    <i class="fas fa-clover" style="font-size: 3rem; color: white;"></i>
                </div>
                <div class="game-header">
                    <div class="game-icon">
                        <i class="fas fa-clover"></i>
                    </div>
                    <div class="game-info">
                        <h3 class="game-title">Card Flip</h3>
                        <span class="game-tag">Skill Game</span>
                    </div>
                </div>
                <div class="game-description">
                    Test your memory in this jungle-themed card matching game. Flip cards to find matching pairs and win RAWR tokens. Complete levels for bonus prizes!
                </div>
                <button class="play-btn">
                    <i class="fas fa-play"></i>
                    Play Now
                </button>
            </div>
            
            <!-- Treetop Tumble -->
            <div class="game-card">
                <div class="game-preview treetop-preview">
                    <div class="preview-overlay">Navigate falling fruits through jungle obstacles to win!</div>
                    <i class="fas fa-tree" style="font-size: 3rem; color: white;"></i>
                </div>
                <div class="game-header">
                    <div class="game-icon">
                        <i class="fas fa-tree"></i>
                    </div>
                    <div class="game-info">
                        <h3 class="game-title">Treetop Tumble</h3>
                        <span class="game-tag">New</span>
                    </div>
                </div>
                <div class="game-description">
                    Navigate falling jungle fruits through intricate tree branches. Collect special fruits for multipliers and avoid obstacles to win big RAWR prizes!
                </div>
                <button class="play-btn">
                    <i class="fas fa-play"></i>
                    Play Now
                </button>
            </div>
            
            <!-- Panthers Prowl -->
            <div class="game-card">
                <div class="game-preview panther-preview">
                    <div class="preview-overlay">Stalk your prey through the jungle in this stealth-based game!</div>
                    <i class="fas fa-paw" style="font-size: 3rem; color: white;"></i>
                </div>
                <div class="game-header">
                    <div class="game-icon">
                        <i class="fas fa-paw"></i>
                    </div>
                    <div class="game-info">
                        <h3 class="game-title">Panther's Prowl</h3>
                        <span class="game-tag">Adventure</span>
                    </div>
                </div>
                <div class="game-description">
                    Become the jungle's ultimate predator! Stealthily hunt your prey while avoiding dangers. The bigger the catch, the larger your RAWR reward!
                </div>
                <button class="play-btn">
                    <i class="fas fa-play"></i>
                    Play Now
                </button>
            </div>
        </div>
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
        
        // Game card animations
        const gameCards = document.querySelectorAll('.game-card');
        gameCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px)';
                card.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.4), 0 0 20px rgba(255, 215, 0, 0.4)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.2)';
            });
        });
        
        // Play button animations
        const playButtons = document.querySelectorAll('.play-btn');
        playButtons.forEach(button => {
            button.addEventListener('mouseenter', () => {
                button.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            button.addEventListener('mouseleave', () => {
                button.style.transform = 'translateY(0) scale(1)';
            });
            
            button.addEventListener('click', function() {
                // Pulse animation on click
                this.classList.add('pulse');
                setTimeout(() => {
                    this.classList.remove('pulse');
                }, 500);
                
                // Get game title
                const gameTitle = this.closest('.game-card').querySelector('.game-title').textContent;
                
                // Create notification
                const notification = document.createElement('div');
                notification.style.position = 'fixed';
                notification.style.bottom = '20px';
                notification.style.right = '20px';
                notification.style.backgroundColor = 'rgba(30, 30, 30, 0.9)';
                notification.style.color = 'var(--primary)';
                notification.style.padding = '15px 25px';
                notification.style.borderRadius = '8px';
                notification.style.border = '1px solid var(--primary)';
                notification.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.3)';
                notification.style.zIndex = '1000';
                notification.style.transition = 'transform 0.3s ease';
                notification.style.transform = 'translateX(120%)';
                notification.innerHTML = `<i class="fas fa-play-circle"></i> Launching ${gameTitle}...`;
                
                document.body.appendChild(notification);
                
                // Animate in
                setTimeout(() => {
                    notification.style.transform = 'translateX(0)';
                }, 10);
                
                // Remove after 3 seconds
                setTimeout(() => {
                    notification.style.transform = 'translateX(120%)';
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 3000);
            });
        });
    </script>
</body>
</html>
