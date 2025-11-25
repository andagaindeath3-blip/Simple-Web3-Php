const connectButton = document.getElementById('connectWallet');
const walletAddressDiv = document.getElementById('walletAddress');
const balanceDiv = document.getElementById('balance');
const createButton = document.getElementById('createToken');
const statusDiv = document.getElementById('status');

let web3;
let userAccount;

// Бэкенд URL (замени на свой PHP-сервер, интегрированный с Simple-Web3-Php)
const API_BASE = 'https://твой-домен.com/api'; // Или localhost для теста

// Подключение MetaMask
async function connectWallet() {
  if (typeof window.ethereum !== 'undefined') {
    try {
      web3 = new Web3(window.ethereum);
      const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
      userAccount = accounts[0];
      
      walletAddressDiv.innerHTML = `<p>🟢 Подключено: <b>\( {userAccount.slice(0,6)}... \){userAccount.slice(-4)}</b></p>`;
      connectButton.textContent = '✅ MetaMask подключён';
      connectButton.disabled = true;
      
      // Загружаем баланс
      updateBalance();
      
      // Загружаем статы (опционально, с бэкенда)
      loadStats();
      
    } catch (error) {
      console.error('Ошибка подключения:', error);
      statusDiv.textContent = 'Ошибка подключения MetaMask!';
    }
  } else {
    alert('Установи MetaMask!');
  }
}

connectButton.addEventListener('click', connectWallet);

// Обновление баланса ETH
async function updateBalance() {
  if (web3 && userAccount) {
    const balance = await web3.eth.getBalance(userAccount);
    const ethBalance = web3.utils.fromWei(balance, 'ether');
    balanceDiv.innerHTML = `<p>Баланс: <b>${ethBalance} ETH</b></p>`;
  }
}

// Загрузка статов (с PHP API)
async function loadStats() {
  try {
    const res = await fetch(`${API_BASE}/stats`);
    const data = await res.json();
    document.getElementById('totalTokens').textContent = data.totalTokens || 0;
    document.getElementById('totalVolume').textContent = `$${data.totalVolume || 0}`;
    document.getElementById('yourEarnings').textContent = `${data.earnings || 0} ETH`;
  } catch (e) {
    console.log('Статы не загружены:', e);
  }
}

// Создание токена (AJAX на PHP, где Simple-Web3-Php deploy'ит контракт)
createButton.addEventListener('click', async () => {
  if (!userAccount) return statusDiv.textContent = 'Сначала подключи MetaMask!';

  const name = document.getElementById('tokenName').value.trim();
  const symbol = document.getElementById('tokenSymbol').value.trim().toUpperCase();
  const supply = document.getElementById('tokenSupply').value;
  const image = document.getElementById('tokenImage').value;
  const desc = document.getElementById('tokenDescription').value;
  const fee = document.getElementById('feePercent').value;

  if (!name || !symbol) {
    return statusDiv.textContent = 'Заполни название и тикер!';
  }

  statusDiv.textContent = '⛓️ Создаём ERC20-токен... (это займёт ~30 сек)';

  // Payload для PHP API (Simple-Web3-Php обработает deploy)
  const payload = {
    name, symbol, supply: Number(supply), image, description: desc,
    feePercent: Number(fee),
    creator: userAccount
  };

  try {
    // Сначала MetaMask подтверждает транзу (gas)
    const gasEstimate = await web3.eth.estimateGas({ from: userAccount });
    await window.ethereum.request({
      method: 'eth_sendTransaction',
      params: [{ from: userAccount, gas: gasEstimate }]
    }); // Это симуляция; в реале подпиши raw tx от PHP

    // AJAX на PHP для deploy
    const res = await fetch(`${API_BASE}/create-token`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const data = await res.json();

    if (data.success) {
      statusDiv.innerHTML = `Токен создан! ⛓️<br>
        <a href="https://etherscan.io/token/\( {data.address}" target="_blank">Адрес: \){data.address.slice(0,10)}...</a><br>
        <a href="https://dexscreener.com/ethereum/${data.address}" target="_blank">Смотреть на DexScreener</a>`;
      
      // Обновляем статы
      loadStats();
    } else {
      statusDiv.textContent = 'Ошибка: ' + data.error;
    }
  } catch (e) {
    statusDiv.textContent = 'Сервер или сеть глючит, пиши в тг — починим 😏';
    console.error(e);
  }
});

// Авто-коннект если MetaMask уже авторизован
window.addEventListener('load', async () => {
  if (window.ethereum) {
    const accounts = await window.ethereum.request({ method: 'eth_accounts' });
    if (accounts.length > 0) {
      connectWallet();
    }
  }
});
