<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>RAWR - The Lion's Game | Next-Gen Crypto Casino</title>
    <meta name="description" content="The wildest crypto casino on the blockchain. Play, mine, and earn RAWR tokens with our provably fair games.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FFD700;
            --primary-dark: #FFA500;
            --secondary: #FF6B35;
            --bg-dark: #1a1a1a;
            --bg-darker: #121212;
            --bg-gradient: linear-gradient(135deg, #1a1a1a 0%, #2d1810 50%, #1a1a1a 100%);
            --text-light: #f0f0f0;
            --text-muted: #ccc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-light);
            overflow-x: hidden;
            line-height: 1.6;
        }

        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            padding: 2rem;
            background: radial-gradient(ellipse at center, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800"><defs><radialGradient id="stars"><stop offset="0%" stop-color="%23FFD700" stop-opacity="1"/><stop offset="100%" stop-color="%23FFD700" stop-opacity="0"/></radialGradient></defs><circle cx="100" cy="100" r="2" fill="url(%23stars)"/><circle cx="300" cy="200" r="1.5" fill="url(%23stars)"/><circle cx="500" cy="150" r="1" fill="url(%23stars)"/><circle cx="700" cy="300" r="2" fill="url(%23stars)"/><circle cx="900" cy="250" r="1.5" fill="url(%23stars)"/><circle cx="1100" cy="400" r="1" fill="url(%23stars)"/></svg>') repeat;
            opacity: 0.6;
            animation: twinkle 3s ease-in-out infinite alternate;
        }

        @keyframes twinkle {
            0% { opacity: 0.6; }
            100% { opacity: 1; }
        }

        .logo {
            font-size: 6rem;
            font-weight: 900;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(255, 215, 0, 0.5);
            margin-bottom: 1rem;
            animation: roar 2s ease-in-out infinite;
            cursor: pointer;
            position: relative;
            z-index: 10;
        }

        @keyframes roar {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .tagline {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 2rem;
            text-align: center;
            opacity: 0.9;
            max-width: 800px;
            line-height: 1.4;
            position: relative;
            z-index: 10;
        }

        .lion-emoji {
            font-size: 8rem;
            margin-bottom: 2rem;
            animation: bounce 2s ease-in-out infinite;
            cursor: pointer;
            transition: transform 0.3s ease;
            position: relative;
            z-index: 10;
        }

        .lion-emoji:hover {
            transform: scale(1.2) rotate(10deg);
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }

        .cta-buttons {
            display: flex;
            gap: 2rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
            justify-content: center;
            position: relative;
            z-index: 10;
        }

        .btn {
            padding: 1rem 2rem;
            font-size: 1.2rem;
            font-weight: bold;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-align: center;
            min-width: 200px;
            min-height: 50px;
            position: relative;
            overflow: hidden;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.1) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(30deg);
            transition: all 0.3s ease;
            opacity: 0;
        }

        .btn:hover::after {
            animation: shine 1.5s ease;
        }

        @keyframes shine {
            0% { transform: rotate(30deg) translate(-20%, -20%); opacity: 0; }
            20% { opacity: 1; }
            100% { transform: rotate(30deg) translate(100%, 100%); opacity: 0; }
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: var(--bg-darker);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(255, 215, 0, 0.6);
        }

        .btn-secondary {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            backdrop-filter: blur(5px);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: var(--bg-darker);
            transform: translateY(-3px);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
            padding: 2rem;
            max-width: 1200px;
            position: relative;
            z-index: 10;
        }

        .feature-card {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(255, 215, 0, 0.2);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .feature-title {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .feature-desc {
            color: var(--text-muted);
            line-height: 1.6;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            margin-top: 3rem;
            flex-wrap: wrap;
            gap: 2rem;
            position: relative;
            z-index: 10;
        }

        .stat {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 215, 0, 0.05);
            border-radius: 15px;
            min-width: 180px;
            border: 1px solid rgba(255, 215, 0, 0.1);
            backdrop-filter: blur(5px);
            transition: transform 0.3s ease;
        }

        .stat:hover {
            transform: scale(1.05);
            background: rgba(255, 215, 0, 0.1);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 900;
            color: var(--primary);
            display: block;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 1rem;
            font-weight: 600;
        }

        .wallet-connect {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: rgba(255, 215, 0, 0.2);
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            z-index: 100;
        }

        .wallet-connect:hover {
            background: var(--primary);
            color: var(--bg-darker);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        .mining-preview {
            background: rgba(255, 215, 0, 0.1);
            border-radius: 20px;
            padding: 2rem;
            margin-top: 3rem;
            text-align: center;
            border: 1px solid rgba(255, 215, 0, 0.3);
            max-width: 500px;
            width: 100%;
            backdrop-filter: blur(5px);
            position: relative;
            z-index: 10;
        }

        .mining-progress {
            width: 100%;
            height: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            overflow: hidden;
            margin: 1rem 0;
            position: relative;
        }

        .mining-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            width: 0%;
            animation: mining 3s ease-in-out infinite;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }

        .mining-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            animation: miningShine 3s ease-in-out infinite;
        }

        @keyframes mining {
            0% { width: 0%; }
            50% { width: 75%; }
            100% { width: 100%; }
        }

        @keyframes miningShine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .notification {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: rgba(255, 215, 0, 0.9);
            color: var(--bg-darker);
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }

        .lion-paws {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 0;
        }

        .paw {
            position: absolute;
            font-size: 2rem;
            opacity: 0.1;
            animation: pawFloat 15s linear infinite;
        }

        @keyframes pawFloat {
            0% { transform: translateY(0) translateX(0) rotate(0deg); opacity: 0; }
            10% { opacity: 0.1; }
            90% { opacity: 0.1; }
            100% { transform: translateY(-100vh) translateX(20vw) rotate(360deg); opacity: 0; }
        }

        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: rgba(0, 0, 0, 0.8);
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 0.5rem;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.9rem;
            font-weight: normal;
            backdrop-filter: blur(5px);
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: var(--bg-darker);
            padding: 2rem;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            border: 1px solid var(--primary);
            position: relative;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: var(--primary);
        }

        .modal-title {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }

        .modal-text {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        /* Mobile Responsiveness Improvements */
        @media (max-width: 768px) {
            .logo { font-size: 4rem; }
            .lion-emoji { font-size: 6rem; }
            .tagline { font-size: 1.2rem; }
            .cta-buttons { flex-direction: column; align-items: center; }
            .features { grid-template-columns: 1fr; }
            .wallet-connect { 
                position: fixed;
                bottom: 1rem;
                top: auto;
                right: 1rem;
                left: 1rem;
                text-align: center;
                justify-content: center;
                width: calc(100% - 2rem);
            }
            .stat {
                min-width: 120px;
                padding: 1rem;
            }
            .stat-number {
                font-size: 2rem;
            }
            .hero {
                padding-top: 4rem;
                padding-bottom: 4rem;
            }
        }

        @media (max-width: 480px) {
            .logo {
                font-size: 3.5rem;
            }
            .lion-emoji {
                font-size: 5rem;
            }
            .tagline {
                font-size: 1.1rem;
                padding: 0 1rem;
            }
            .mining-preview {
                padding: 1.5rem;
                margin: 1rem;
            }
            .feature-card {
                padding: 1.5rem;
            }
            .stat {
                min-width: 100px;
                padding: 0.8rem;
            }
            .stat-number {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 360px) {
            .logo {
                font-size: 3rem;
            }
            .lion-emoji {
                font-size: 4rem;
            }
            .tagline {
                font-size: 1rem;
            }
            .btn {
                min-width: 160px;
                padding: 0.8rem 1rem;
                font-size: 1rem;
                min-height: 44px;
            }
            .feature-title {
                font-size: 1.3rem;
            }
            .feature-desc {
                font-size: 0.9rem;
            }
        }

        /* Mobile view specific classes */
        .mobile-view .hero {
            padding-top: 4rem;
            padding-bottom: 4rem;
        }

        .mobile-view .wallet-connect {
            bottom: 1rem;
            top: auto;
            right: 1rem;
            left: 1rem;
            text-align: center;
            justify-content: center;
            width: calc(100% - 2rem);
        }

        /* Shake animation */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        @keyframes fadeUpOut {
            0% { opacity: 1; transform: translate(-50%, -50%); }
            100% { opacity: 0; transform: translate(-50%, -70%); }
        }
    </style>
</head>
<body>
    <div class="hero-bg"></div>
    <div class="lion-paws" id="lionPaws"></div>

    <div class="hero">
        
        <div class="lion-emoji tooltip" onclick="roarSound()">
            🦁
            <span class="tooltiptext">Click to hear the lion's roar!</span>
        </div>
        
        <h1 class="logo" onclick="roarSound()">RAWR</h1>
        
        <p class="tagline">Unleash the King of Crypto! 👑 The most thrilling provably fair casino on the blockchain</p>
        
        <div class="cta-buttons">
            <a href="/RAWR/public/login.php" class="btn btn-primary">
                🎰 Start Roaring → Play Now!
            </a>
            <a href="/RAWR/public/login.php" class="btn btn-secondary">
                ⛏️ Start Mining RAWR
            </a>
        </div>

        <div class="mining-preview tooltip">
            <h3 style="color: var(--primary); margin-bottom: 1rem;">⛏️ Mining Preview</h3>
            <p style="color: var(--text-muted);">Earn 1 RAWR every 10 minutes</p>
            <div class="mining-progress">
                <div class="mining-bar"></div>
            </div>
            <p style="color: var(--primary); font-weight: bold;">Next reward: <span id="nextReward">0.75</span> RAWR</p>
            <span class="tooltiptext">Upgrade your mining power to earn more!</span>
        </div>
        
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">🎲</div>
                <h3 class="feature-title">Casino Games</h3>
                <p class="feature-desc">Dice, slots, card flip, and our exclusive Jungle Jackpot game! All provably fair.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">⛏️</div>
                <h3 class="feature-title">Passive Mining</h3>
                <p class="feature-desc">Earn RAWR tokens automatically while you sleep. Upgrade your mining power!</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🏆</div>
                <h3 class="feature-title">Leaderboards</h3>
                <p class="feature-desc">Compete with other lions and claim your spot as the Alpha! Weekly prizes.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <h3 class="feature-title">Web3 Secure</h3>
                <p class="feature-desc">Your wallet, your coins. Fully decentralized and transparent smart contracts.</p>
            </div>
        </div>
    </div>

    <div class="notification" id="notification">
        <span class="notification-icon">🦁</span>
        <span class="notification-text">Welcome to RAWR Casino!</span>
    </div>

    <div class="modal" id="comingSoonModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h3 class="modal-title">Coming Soon! 🚀</h3>
            <p class="modal-text">This feature is currently under development. Join our community to get updates on the launch!</p>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal()">Close</button>
                <button class="btn btn-primary" onclick="joinCommunity()">Join Community</button>
            </div>
        </div>
    </div>

    <script>
        // Improved variables with localStorage persistence
        let miningActive = localStorage.getItem('rawr_miningActive') === 'true';
        let miningInterval;
        let rewardInterval;
        let nextReward = 0.75;

        // DOM elements
        const notification = document.getElementById('notification');
        const comingSoonModal = document.getElementById('comingSoonModal');
        const nextRewardEl = document.getElementById('nextReward');
        const playersCountEl = document.getElementById('playersCount');
        const tokensMinedEl = document.getElementById('tokensMined');
        const winRateEl = document.getElementById('winRate');

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            // Show welcome notification
            showNotification('Welcome to RAWR Casino! 🦁');
            
            // Create floating paw prints
            createPawPrints();
            
            // Animate stats
            animateStats();
            
            // Update mining preview if active
            if (miningActive) {
                startMining(false);
            }
            
            // Check mobile view
            checkMobile();
        });

        // Check mobile view
        function checkMobile() {
            if (window.innerWidth <= 768) {
                document.body.classList.add('mobile-view');
            } else {
                document.body.classList.remove('mobile-view');
            }
        }

        window.addEventListener('resize', checkMobile);

        function roarSound() {
            // Create a visual roar effect
            const lion = document.querySelector('.lion-emoji');
            lion.style.animation = 'none';
            setTimeout(() => {
                lion.style.animation = 'bounce 2s ease-in-out infinite';
            }, 100);
            
            // Add screen shake effect
            document.body.style.animation = 'shake 0.5s ease-in-out';
            setTimeout(() => {
                document.body.style.animation = '';
            }, 500);
            
            // Show floating RAWR text
            showFloatingText('RAWR! 🦁');
            
            // Play sound if available
            if (typeof Audio !== 'undefined') {
                const roar = new Audio('https://assets.mixkit.co/sfx/preview/mixkit-angry-wild-animal-roar-11.mp3');
                roar.volume = 0.3;
                roar.play().catch(e => console.log('Audio play failed:', e));
            }
        }

        function showFloatingText(text) {
            const floatingText = document.createElement('div');
            floatingText.textContent = text;
            floatingText.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 3rem;
                font-weight: bold;
                color: var(--primary);
                pointer-events: none;
                z-index: 1000;
                animation: fadeUpOut 2s ease-out forwards;
                text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            `;
            
            document.body.appendChild(floatingText);
            
            setTimeout(() => {
                floatingText.remove();
            }, 2000);
        }

        function showNotification(text) {
            const notification = document.getElementById('notification');
            notification.querySelector('.notification-text').textContent = text;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        function startPlaying() {
            window.location.href = 'login.php';
        }

        function startMining() {
            window.location.href = 'login.php';
        }

        function stopMining() {
            miningActive = false;
            localStorage.setItem('rawr_miningActive', 'false');
            clearInterval(miningInterval);
            clearInterval(rewardInterval);
            showNotification('Mining Stopped');
        }

        function showModal() {
            comingSoonModal.style.display = 'flex';
        }

        function closeModal() {
            comingSoonModal.style.display = 'none';
        }

        function joinCommunity() {
            closeModal();
            showNotification('Redirecting to community...');
            setTimeout(() => {
                window.open('https://example.com/community', '_blank');
            }, 1000);
        }

        function createPawPrints() {
            const pawContainer = document.getElementById('lionPaws');
            const pawCount = 20;
            
            for (let i = 0; i < pawCount; i++) {
                const paw = document.createElement('div');
                paw.className = 'paw';
                paw.textContent = '🐾';
                paw.style.left = Math.random() * 100 + 'vw';
                paw.style.top = Math.random() * 100 + 'vh';
                paw.style.animationDelay = Math.random() * 15 + 's';
                paw.style.animationDuration = 10 + Math.random() * 20 + 's';
                pawContainer.appendChild(paw);
            }
        }

        function animateStats() {
            // Animate players count
            let players = 1247;
            setInterval(() => {
                players += Math.floor(Math.random() * 3);
                playersCountEl.textContent = players.toLocaleString();
            }, 5000);
            
            // Animate win rate (small fluctuations)
            let winRate = 98.7;
            setInterval(() => {
                winRate += (Math.random() - 0.5) * 0.2;
                winRate = Math.max(97, Math.min(99.5, winRate));
                winRateEl.textContent = winRate.toFixed(1) + '%';
            }, 8000);
        }

        // Add shake animation
        const shakeCSS = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
            @keyframes fadeUpOut {
                0% { opacity: 1; transform: translate(-50%, -50%); }
                100% { opacity: 0; transform: translate(-50%, -70%); }
            }
        `;
        
        const style = document.createElement('style');
        style.textContent = shakeCSS;
        document.head.appendChild(style);
    </script>
</body>
</html>