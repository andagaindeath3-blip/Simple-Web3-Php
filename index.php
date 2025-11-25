<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoCreator PHP — Создай токен с Simple-Web3-Php</title>
    <script src="https://cdn.jsdelivr.net/npm/web3@latest/dist/web3.min.js"></script>
    <script src="https://cdn.ethers.io/lib/ethers-6.13.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Rajdhani:wght@600&family=Exo+2:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00f2ff;
            --secondary: #ff00aa;
            --bg: #0a0a1f;
            --card: rgba(20, 25, 50, 0.8);
            --glow: 0 0 30px rgba(0, 242, 255, 0.6);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--bg) url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><radialGradient id="a" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="%2300f2ff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ff00aa" stop-opacity="0.05"/></radialGradient></defs><circle cx="20" cy="20" r="1" fill="url(%23a)"/><circle cx="80" cy="80" r="1" fill="url(%23a)"/><circle cx="50" cy="10" r="0.5" fill="url(%23a)"/></svg>') fixed;
            color: white;
            font-family: 'Exo 2', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        header {
            text-align: center; padding: 60px 20px;
            background: linear-gradient(135deg, #1a1a3a, #0f0f2a);
            border-bottom: 2px solid var(--primary); position: relative;
        }
        header::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 2px; background: linear-gradient(90deg, transparent, var(--primary), transparent); }
        h1 {
            font-family: 'Orbitron', sans-serif; font-size: 4rem;
            background: linear-gradient(90deg, #00f2ff, #ff00aa);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            text-shadow: var(--glow); animation: glow 2s ease-in-out infinite alternate;
        }
        @keyframes glow { from { text-shadow: var(--glow); } to { text-shadow: 0 0 40px rgba(0,242,255,0.8); } }
        .subtitle { font-size: 1.5rem; margin-top: 15px; opacity: 0.9; }
        
        .card {
            background: var(--card); border-radius: 20px; padding: 40px;
            margin: 40px auto; backdrop-filter: blur(10px);
            border: 1px solid rgba(0,242,255,0.3); box-shadow: var(--glow);
            position: relative; overflow: hidden;
        }
        .card::before {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: conic-gradient(from 0deg, transparent, var(--primary), transparent);
            animation: rotate 8s linear infinite; opacity: 0.1;
        }
        @keyframes rotate { to { transform: rotate(360deg); } }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }

        input, select {
            width: 100%; padding: 16px; border-radius: 12px;
            border: 2px solid rgba(0,242,255,0.3); background: rgba(10,15,40,0.8);
            color: white; font-size: 1.1rem; transition: all 0.3s;
        }
        input:focus, select:focus { outline: none; border-color: var(--primary); box-shadow: var(--glow); }

        .connect-wallet {
            background: linear-gradient(45deg, #ff00aa, #00f2ff); color: white; border: none;
            padding: 18px 40px; font-size: 1.3rem; border-radius: 50px; cursor: pointer;
            font-weight: bold; transition: 0.4s; box-shadow: var(--glow); width: 100%; margin-bottom: 20px;
        }
        .connect-wallet:hover { transform: scale(1.05); }

        .create-btn {
            width: 100%; padding: 20px; font-size: 1.8rem; font-weight: bold;
            background: linear-gradient(45deg, #00f2ff, #00ffaa); border: none; border-radius: 50px;
            color: #000; cursor: pointer; margin-top: 20px; box-shadow: 0 10px 40px rgba(0,242,255,0.5);
            transition: all 0.4s;
        }
        .create-btn:hover { transform: translateY(-5px); box-shadow: 0 20px 60px rgba(0,242,255,0.7); }
        .create-btn:disabled { background: #555; cursor: not-allowed; transform: none; }

        .result {
            margin-top: 30px; padding: 25px; background: rgba(0,255,100,0.1); border-radius: 15px;
            border: 1px solid #00ffaa; text-align: center; font-size: 1.3rem; display: none;
        }
        .result.error { background: rgba(255,0,0,0.1); border-color: #ff0066; }
        .network-badge {
            position: absolute; top: 15px; right: 15px; background: #00ff00; color: black;
            padding: 8px 16px; border-radius: 20px; font-weight: bold; font-size: 0.9rem;
        }
        .loader { display: none; text-align: center; margin: 20px 0; }
        .loader::after { content: ''; display: inline-block; width: 20px; height: 20px; border: 2px solid var(--primary); border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>CRYPTO CREATOR PHP</h1>
            <p class="subtitle">Создай ERC-20 токен за 30 сек · Simple-Web3-Php · Любой кошелёк</p>
        </header>

        <div class="card">
            <div class="network-badge" id="network">Подключи кошелёк</div>

            <button class="connect-wallet" id="connectBtn">Подключить кошелёк (MetaMask / Trust / WalletConnect)</button>

            <div class="form-grid">
                <input type="text" id="tokenName" placeholder="Название (MyCoin)" value="MyToken" required>
                <input type="text" id="tokenSymbol" placeholder="Символ (MTK)" value="MTK" maxlength="8" required>
                <input type="number" id="tokenSupply" placeholder="Supply (1000000)" value="1000000" required min="1">
                <select id="decimals">
                    <option value="18">18 decimals (стандарт)</option>
                    <option value="6">6 decimals (stablecoin)</option>
                    <option value="9">9 decimals</option>
                </select>
            </div>

            <div class="loader" id="loader">Создаём токен...</div>
            <button class="create-btn" id="createBtn" disabled>🚀 Создать криптовалюту!</button>

            <div class="result" id="result"></div>
        </div>
    </div>

    <script>
        let web3, account, chainId;

        // Подключение кошелька
        document.getElementById('connectBtn').onclick = async () => {
            if (typeof window.ethereum !== 'undefined') {
                try {
                    web3 = new Web3(window.ethereum);
                    await window.ethereum.request({ method: 'eth_requestAccounts' });
                    account = (await web3.eth.getAccounts())[0];
                    chainId = await web3.eth.getChainId();

                    const networks = {
                        1: 'Ethereum Mainnet', 11155111: 'Sepolia', 137: 'Polygon', 56: 'BNB Chain'
                    };
                    const netName = networks[chainId] || 'Unknown';
                    document.getElementById('network').textContent = netName;
                    document.getElementById('connectBtn').textContent = `Подключено: \( {account.slice(0,6)}... \){account.slice(-4)}`;
                    document.getElementById('createBtn').disabled = false;
                } catch (err) {
                    alert('Ошибка подключения: ' + err.message);
                }
            } else {
                alert('Установи MetaMask или другой Web3-кошелёк!');
            }
        };

        // Создание токена (AJAX на backend)
        document.getElementById('createBtn').onclick = async () => {
            if (!account) return alert('Подключи кошелёк!');

            const btn = document.getElementById('createBtn');
            const loader = document.getElementById('loader');
            const result = document.getElementById('result');
            btn.disabled = true;
            loader.style.display = 'block';
            result.style.display = 'none';

            const data = {
                account: account,
                chainId: chainId,
                name: document.getElementById('tokenName').value,
                symbol: document.getElementById('tokenSymbol').value,
                supply: document.getElementById('tokenSupply').value,
                decimals: document.getElementById('decimals').value
            };

            try {
                const res = await fetch('deploy_token.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();

                loader.style.display = 'none';
                if (json.success) {
                    result.innerHTML = `
                        <h2>✅ Токен создан!</h2>
                        <strong>\( {data.name} ( \){data.symbol})</strong><br><br>
                        Адрес: <a href="https://sepolia.etherscan.io/address/\( {json.contract}" target="_blank"> \){json.contract}</a><br>
                        Tx: <a href="https://sepolia.etherscan.io/tx/\( {json.tx}" target="_blank"> \){json.tx}</a><br><br>
                        <a href="https://dexscreener.com/sepolia/${json.contract}" target="_blank">DexScreener</a>
                    `;
                } else {
                    result.innerHTML = `<h2>❌ Ошибка:</h2> ${json.error}`;
                    result.classList.add('error');
                }
                result.style.display = 'block';
            } catch (err) {
                loader.style.display = 'none';
                result.innerHTML = `<h2>❌ Ошибка сети:</h2> ${err.message}`;
                result.classList.add('error');
                result.style.display = 'block';
            }

            btn.disabled = false;
        };

        // Автоподключение при загрузке
        if (window.ethereum) window.ethereum.on('accountsChanged', () => location.reload());
    </script>
</body>
</html>