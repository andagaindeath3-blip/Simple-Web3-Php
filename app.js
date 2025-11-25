https://2026createcoin.vercel.app/// app.js — полностью на WalletConnect v2 + Web3Modal + wagmi
import { createWeb3Modal, defaultWagmiConfig } from '@web3modal/wagmi';
import { mainnet, sepolia, polygon, base, arbitrum } from 'wagmi/chains';
import { reconnect } from '@wagmi/core';

// Твой projectId с https://cloud.walletconnect.com (бесплатно, 2 минуты)
const projectId = '0366b8e4-9a07-4421-9372-5e0872fe4e2c'; // ← ОБЯЗАТЕЛЬНО замени!

const chains = [mainnet, sepolia, polygon, base, arbitrum];
const metadata = {
  name: 'EtherPad',
  description: 'Запусти свой ERC20 за 30 сек',
  url: 'https://твой-домен.com',
  icons: ['https://твой-домен.com/favicon.ico']
};

const wagmiConfig = defaultWagmiConfig({
  chains,
  projectId,
  metadata,
  enableWalletConnect: true,
  enableInjected: true,
  enableCoinbase: true,
});

reconnect(wagmiConfig);

// Создаём модалку (самая красивая в 2025)
const modal = createWeb3Modal({
  wagmiConfig,
  projectId,
  themeMode: 'dark',
  themeVariables: {
    '--w3m-accent-color': '#6366f1',
    '--w3m-border-radius-master': '20px'
  },
  chainImages: {
    1: 'https://icons.llamao.fi/icons/chains/rsz_ethereum.jpg',
    11155111: 'https://icons.llamao.fi/icons/chains/rsz_sepolia.jpg',
  }
});

// Элементы DOM
const connectBtn = document.getElementById('walletConnectButton');
const walletSection = document.getElementById('walletSection');
const walletInfo = document.getElementById('walletInfo');
const addressSpan = document.getElementById('walletAddress');
const balanceSpan = document.getElementById('balance');

// Подписываемся на изменения
modal.subscribeEvents(async (event) => {
  if (event.type === 'CONNECT_SUCCESS') {
    const address = modal.getAddress();
    const chainId = modal.getChainId();
    
    walletSection.style.display = 'none';
    walletInfo.style.display = 'block';
    addressSpan.textContent = `\( {address.slice(0,6)}... \){address.slice(-4)}`;
    
    await updateBalance(address);
    loadStats();
  }

  if (event.type === 'DISCONNECT') {
    walletSection.style.display = 'block';
    walletInfo.style.display = 'none';
  }
});

// Кнопка подключения
connectBtn.addEventListener('click', () => {
  modal.open();
});

// Отключение
document.getElementById('disconnectWallet').addEventListener('click', () => {
  modal.disconnect();
});

// Обновление баланса
async function updateBalance(address) {
  const provider = modal.getWalletProvider();
  const web3 = new Web3(provider);
  const balance = await web3.eth.getBalance(address);
  balanceSpan.textContent = `${parseFloat(web3.utils.fromWei(balance, 'ether')).toFixed(4)} ETH`;
}

// Создание токена — теперь с правильным провайдером
document.getElementById('createToken').addEventListener('click', async () => {
  if (!modal.getAddress()) {
    return document.getElementById('status').textContent = 'Сначала подключи кошелёк!';
  }

  const provider = modal.getWalletProvider();
  const web3 = new Web3(provider);
  const userAccount = modal.getAddress();

  // Твой AJAX на PHP остаётся тем же
  const payload = {
    name: document.getElementById('tokenName').value.trim(),
    symbol: document.getElementById('tokenSymbol').value.trim().toUpperCase(),
    supply: Number(document.getElementById('tokenSupply').value),
    creator: userAccount,
    feePercent: Number(document.getElementById('feePercent').value)
  };

  document.getElementById('status').textContent = 'Создаём токен через WalletConnect...';

  const res = await fetch('https://твой-домен.com/api/create-token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

  const data = await res.json();
  if (data.success) {
    document.getElementById('status').innerHTML = `Токен создан!<br>
      <a href="https://etherscan.io/token/\( {data.address}" target="_blank"> \){data.address}</a>`;
  } else {
    document.getElementById('status').textContent = 'Ошибка: ' + data.error;
  }
});

// Авто-коннект при загрузке
if (modal.getAddress()) {
  modal.subscribeEvents(); // уже подключён
}

