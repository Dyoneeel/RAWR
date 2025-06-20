document.addEventListener('DOMContentLoaded', function() {
    // Menu toggle for mobile
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
    
    // Copy referral code
    const copyReferralBtn = document.getElementById('copyReferralBtn');
    if (copyReferralBtn) {
        copyReferralBtn.addEventListener('click', function() {
            const referralCode = document.getElementById('referralCodeDisplay').textContent;
            navigator.clipboard.writeText(referralCode)
                .then(() => {
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy: ', err);
                });
        });
    }
    
    // Fetch and update balances
    function fetchBalances() {
        fetch('/backend/mining/balances.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('rawrBalance').textContent = data.rawr_balance.toFixed(8);
                    document.getElementById('ticketBalance').textContent = data.ticket_balance;
                    document.getElementById('headerRawrBalance').textContent = data.rawr_balance.toFixed(3) + '...';
                    document.getElementById('headerTicketBalance').textContent = data.ticket_balance;
                }
            });
    }
    
    // Fetch mining status
    function fetchMiningStatus() {
        fetch('/backend/mining/status.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('boostLevel').textContent = data.boost_level;
                    document.getElementById('nextReward').textContent = data.next_reward.toFixed(2);
                    
                    if (data.cooldown_remaining > 0) {
                        startCooldown(data.cooldown_remaining);
                    } else {
                        document.getElementById('miningTimer').textContent = 'Ready to mine!';
                        document.getElementById('miningProgress').style.width = '100%';
                        document.getElementById('mineButton').disabled = false;
                    }
                }
            });
    }
    
    // Fetch referral stats
    function fetchReferralStats() {
        fetch('/backend/referral/stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('referralCount').textContent = data.referral_count;
                    document.getElementById('referralEarnings').textContent = data.referral_earnings.toFixed(2);
                }
            });
    }
    
    // Mine button functionality
    const mineButton = document.getElementById('mineButton');
    if (mineButton) {
        mineButton.addEventListener('click', function() {
            mineButton.disabled = true;
            mineButton.textContent = 'Mining...';
            
            fetch('/backend/mining/mine.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    document.getElementById('rawrBalance').textContent = data.new_balance.toFixed(8);
                    document.getElementById('headerRawrBalance').textContent = data.new_balance.toFixed(3) + '...';
                    
                    // Start cooldown timer
                    startCooldown(data.cooldown);
                    
                    // Add to activity list
                    addActivity('Mined ' + data.reward.toFixed(2) + ' RAWR', 'mining', '+' + data.reward.toFixed(2) + ' RAWR');
                } else {
                    mineButton.disabled = false;
                    mineButton.textContent = 'Mine Now';
                    alert('Mining failed: ' + data.message);
                }
            });
        });
    }
    
    // Convert to tickets button
    const convertBtn = document.getElementById('convertBtn');
    if (convertBtn) {
        convertBtn.addEventListener('click', function() {
            const amount = parseFloat(prompt('How many RAWR tokens do you want to convert to tickets?', '100'));
            
            if (amount && amount > 0) {
                fetch('/backend/mining/convert.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ amount: amount })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        document.getElementById('rawrBalance').textContent = data.new_rawr_balance.toFixed(8);
                        document.getElementById('ticketBalance').textContent = data.new_ticket_balance;
                        document.getElementById('headerRawrBalance').textContent = data.new_rawr_balance.toFixed(3) + '...';
                        document.getElementById('headerTicketBalance').textContent = data.new_ticket_balance;
                        
                        // Add to activity list
                        addActivity('Converted ' + amount + ' RAWR to tickets', 'convert', '+' + data.tickets_received + ' Tickets');
                    } else {
                        alert('Conversion failed: ' + data.message);
                    }
                });
            }
        });
    }
    
    // Cooldown timer
    function startCooldown(seconds) {
        const miningTimer = document.getElementById('miningTimer');
        const miningProgress = document.getElementById('miningProgress');
        const mineButton = document.getElementById('mineButton');
        
        mineButton.disabled = true;
        mineButton.textContent = 'Mining...';
        
        let remaining = seconds;
        const interval = setInterval(() => {
            remaining--;
            
            if (remaining <= 0) {
                clearInterval(interval);
                miningTimer.textContent = 'Ready to mine!';
                miningProgress.style.width = '100%';
                mineButton.disabled = false;
                mineButton.textContent = 'Mine Now';
            } else {
                const minutes = Math.floor(remaining / 60);
                const secs = remaining % 60;
                miningTimer.textContent = `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
                miningProgress.style.width = `${100 - (remaining / 300 * 100)}%`;
            }
        }, 1000);
    }
    
    // Add activity to list
    function addActivity(text, type, amount) {
        const activityList = document.getElementById('recentActivity');
        const icons = {
            mining: 'fas fa-digging',
            game: 'fas fa-dice',
            shop: 'fas fa-shopping-cart',
            convert: 'fas fa-exchange-alt'
        };
        
        const activityItem = document.createElement('div');
        activityItem.className = 'activity-item';
        activityItem.innerHTML = `
            <div class="activity-icon ${type}">
                <i class="${icons[type] || 'fas fa-info-circle'}"></i>
            </div>
            <div class="activity-details">
                <p>${text}</p>
                <span class="activity-time">Just now</span>
            </div>
            <div class="activity-amount ${amount.startsWith('+') ? 'positive' : 'negative'}">
                ${amount}
            </div>
        `;
        
        activityList.insertBefore(activityItem, activityList.firstChild);
        
        // Limit to 10 items
        if (activityList.children.length > 10) {
            activityList.removeChild(activityList.lastChild);
        }
    }
    
    // Initial data fetch
    fetchBalances();
    fetchMiningStatus();
    fetchReferralStats();
    
    // Refresh data every 30 seconds
    setInterval(() => {
        fetchBalances();
        fetchMiningStatus();
    }, 30000);
});