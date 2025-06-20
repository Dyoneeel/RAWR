document.addEventListener('DOMContentLoaded', function() {
    // Mining functionality
    const mineButton = document.getElementById('mineButton');
    const miningTimer = document.getElementById('miningTimer');
    const miningProgress = document.getElementById('miningProgressCircle');
    const miningRewardDisplay = document.getElementById('miningReward');
    
    let cooldown = 0;
    let miningInterval;
    
    // Fetch mining status
    function fetchMiningStatus() {
        fetch('/backend/mining/status.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('currentBoost').textContent = 'x' + data.boost_multiplier.toFixed(1);
                    document.getElementById('totalMined').textContent = data.total_mined.toFixed(8);
                    document.getElementById('miningSpeed').textContent = (data.boost_multiplier * 6).toFixed(1) + ' RAWR/h';
                    miningRewardDisplay.textContent = data.next_reward.toFixed(2);
                    
                    if (data.cooldown_remaining > 0) {
                        startCooldown(data.cooldown_remaining);
                    } else {
                        miningTimer.textContent = 'Ready to mine!';
                        mineButton.disabled = false;
                        miningProgress.style.background = 'conic-gradient(var(--gold-primary) 0%, var(--jungle-dark) 0%)';
                    }
                }
            });
    }
    
    // Mine button functionality
    mineButton.addEventListener('click', function() {
        if (cooldown > 0) return;
        
        mineButton.disabled = true;
        mineButton.textContent = 'Mining...';
        
        // Start mining animation
        miningProgress.style.background = 'conic-gradient(var(--gold-primary) 0%, var(--jungle-dark) 0%)';
        let progress = 0;
        const animationInterval = setInterval(() => {
            progress += 2;
            miningProgress.style.background = `conic-gradient(var(--gold-primary) ${progress}%, var(--jungle-dark) 0%)`;
            
            if (progress >= 100) {
                clearInterval(animationInterval);
                completeMining();
            }
        }, 50);
    });
    
    // Complete mining process
    function completeMining() {
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
                document.getElementById('totalMined').textContent = data.total_mined.toFixed(8);
                miningRewardDisplay.textContent = data.next_reward.toFixed(2);
                
                // Start cooldown timer
                startCooldown(data.cooldown);
                
                // Show mining effect
                showMiningEffect(data.reward);
            } else {
                mineButton.disabled = false;
                mineButton.textContent = 'MINE RAWR';
                miningProgress.style.background = 'conic-gradient(var(--gold-primary) 0%, var(--jungle-dark) 0%)';
                alert('Mining failed: ' + data.message);
            }
        });
    }
    
    // Cooldown timer
    function startCooldown(seconds) {
        cooldown = seconds;
        mineButton.disabled = true;
        mineButton.textContent = 'Cooldown';
        
        miningInterval = setInterval(() => {
            cooldown--;
            
            if (cooldown <= 0) {
                clearInterval(miningInterval);
                miningTimer.textContent = 'Ready to mine!';
                mineButton.disabled = false;
                mineButton.textContent = 'MINE RAWR';
                miningProgress.style.background = 'conic-gradient(var(--gold-primary) 0%, var(--jungle-dark) 0%)';
            } else {
                const minutes = Math.floor(cooldown / 60);
                const secs = cooldown % 60;
                miningTimer.textContent = `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
                
                const progress = 100 - (cooldown / 300 * 100);
                miningProgress.style.background = `conic-gradient(var(--gold-primary) ${progress}%, var(--jungle-dark) 0%)`;
            }
        }, 1000);
    }
    
    // Show mining reward effect
    function showMiningEffect(amount) {
        const effect = document.createElement('div');
        effect.className = 'mining-effect';
        effect.textContent = '+' + amount.toFixed(2) + ' RAWR';
        effect.style.position = 'absolute';
        effect.style.color = 'var(--gold-secondary)';
        effect.style.fontSize = '1.5rem';
        effect.style.fontWeight = 'bold';
        effect.style.textShadow = '0 0 10px rgba(212, 175, 55, 0.8)';
        effect.style.animation = 'floatUp 2s forwards';
        
        document.querySelector('.mining-area').appendChild(effect);
        
        setTimeout(() => {
            effect.remove();
        }, 2000);
    }
    
    // Initial data fetch
    fetchMiningStatus();
    
    // Add CSS for animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes floatUp {
            0% { transform: translateY(0); opacity: 1; }
            100% { transform: translateY(-100px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
});