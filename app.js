// =========================================================
// ⚠️ 1. НАСТРОЙКА: ЗАМЕНИТЕ ЭТИ ЗНАЧЕНИЯ НА ВАШИ РЕАЛЬНЫЕ!
// =========================================================

// 🔴 1.1. ABI (Application Binary Interface) вашего контракта ERC-20.
// Получается после компиляции контракта Solidity.
const TOKEN_ABI = [ 
    // Пример конструктора:
    "constructor(string name, string symbol, uint256 initialSupply, address owner)",
    // Добавьте все функции (name, symbol, totalSupply, transfer и т.д.) сюда.
    // ⚠️ Вставьте свой полный ABI! Этот пример сокращен.
]; 

// 🔴 1.2. BYTECODE (Скомпилированный код) вашего контракта.
// Начинается с '0x'.
const TOKEN_BYTECODE = "0x..."; // ⚠️ Вставьте свой полный Bytecode!

// 🔴 1.3. Ваш Project ID от Infura или Alchemy.
// Нужен для работы WalletConnect.
const INFURA_PROJECT_ID = "ВАШ_INFURA_PROJECT_ID"; // ⚠️ Вставьте свой ID!

// Сеть для деплоя (5 = Goerli Testnet, 1 = Ethereum Mainnet)
// 💡 Рекомендуется начинать с тестовой сети (5)
const TARGET_CHAIN_ID = 5; 

// =========================================================
// 2. ИНИЦИАЛИЗАЦИЯ И ЭЛЕМЕНТЫ DOM
// =========================================================

const connectBtn = document.getElementById('connect-btn');
const deployBtn = document.getElementById('deploy-btn');
const statusArea = document.getElementById('status-area');
const walletStatusDiv = document.getElementById('wallet-status');
const tokenForm = document.getElementById('token-form');

let web3Provider = null;
let signer = null;
let walletAddress = null;

// =========================================================
// 3. ФУНКЦИИ УТИЛИТ
// =========================================================

function updateStatus(message, isError = false) {
    statusArea.innerHTML = message;
    statusArea.style.borderLeftColor = isError ? '#c0392b' : '#627EEA';
}

function updateWalletStatus(isConnected, address = '') {
    if (isConnected) {
        const displayAddress = `${address.substring(0, 6)}...${address.substring(address.length - 4)}`;
        walletStatusDiv.innerHTML = `✅ Кошелек подключен: <strong>${displayAddress}</strong>`;
        walletStatusDiv.classList.remove('disconnected');
        deployBtn.disabled = false;
        connectBtn.textContent = "Кошелек подключен";
        connectBtn.disabled = true;
    } else {
        walletStatusDiv.innerHTML = "🔴 Кошелек не подключен.";
        walletStatusDiv.classList.add('disconnected');
        deployBtn.disabled = true;
        connectBtn.textContent = "Подключить кошелек";
        connectBtn.disabled = false;
    }
}

// =========================================================
// 4. ФУНКЦИИ WALLETCONNECT/METAMASK
// =========================================================

async function connectWallet() {
    try {
        updateStatus("Подключение...");
        
        let providerInstance;

        // Попытка использовать MetaMask (EIP-1193)
        if (window.ethereum) {
            providerInstance = window.ethereum;
            await providerInstance.request({ method: 'eth_requestAccounts' });
            
            // Если сеть не совпадает, Ethers.js попытается отправить запрос на смену сети
            await window.ethereum.request({
                method: 'wallet_switchEthereumChain',
                params: [{ chainId: `0x${TARGET_CHAIN_ID.toString(16)}` }],
            });

        } else {
            // Использование WalletConnect (для мобильных и других кошельков)
            if (INFURA_PROJECT_ID === "ВАШ_INFURA_PROJECT_ID") {
                throw new Error("Необходимо указать INFURA_PROJECT_ID для WalletConnect.");
            }
            providerInstance = new WalletConnectProvider.default({
                infuraId: INFURA_PROJECT_ID,
                chainId: TARGET_CHAIN_ID,
                rpc: {
                    1: `https://mainnet.infura.io/v3/${INFURA_PROJECT_ID}`, 
                    5: `https://goerli.infura.io/v3/${INFURA_PROJECT_ID}`, // Тестовая сеть Goerli
                },
                qrcode: true,
            });
            await providerInstance.enable();
        }

        web3Provider = new ethers.providers.Web3Provider(providerInstance);
        signer = web3Provider.getSigner();

        const accounts = await web3Provider.listAccounts();
        walletAddress = accounts[0];

        // Проверка текущей сети
        const network = await web3Provider.getNetwork();
        if (network.chainId !== TARGET_CHAIN_ID) {
            const chainName = (TARGET_CHAIN_ID === 5) ? 'Goerli Testnet' : 'Mainnet';
            updateStatus(`🚫 Подключена неверная сеть (${network.chainId}). Переключитесь на ${chainName}.`, true);
            updateWalletStatus(false);
            return;
        }

        updateWalletStatus(true, walletAddress);
        updateStatus("Кошелек успешно подключен и готов к деплою.");

    } catch (error) {
        console.error("Ошибка подключения кошелька:", error);
        updateStatus(`🚫 Ошибка подключения: ${error.message || "Отказано в доступе или неверная сеть."}`, true);
        updateWalletStatus(false);
    }
}

// =========================================================
// 5. ФУНКЦИЯ ДЕПЛОЯ ТОКЕНА
// =========================================================

async function deployToken(event) {
    event.preventDefault();

    if (!signer) {
        updateStatus("Сначала подключите кошелек!", true);
        return;
    }
    if (TOKEN_BYTECODE === "0x..." || TOKEN_ABI.length === 0) {
        updateStatus("⚠️ Ошибка: Bytecode или ABI не заполнены в app.js.", true);
        return;
    }
    
    // Получение данных формы
    const tokenName = document.getElementById('name').value;
    const tokenSymbol = document.getElementById('symbol').value;
    const rawSupply = document.getElementById('supply').value;
    const tokenDecimals = document.getElementById('decimals').value;
    const ownerAddress = walletAddress; // Делаем владельцем того, кто деплоит

    try {
        updateStatus("Подготовка параметров токена...");
        
        // 1. Конвертация общего предложения в формат BigNumber (с учетом decimals)
        const initialSupply = ethers.utils.parseUnits(rawSupply, parseInt(tokenDecimals));

        // 2. Создание Factory контракта
        const TokenFactory = new ethers.ContractFactory(TOKEN_ABI, TOKEN_BYTECODE, signer);

        // 3. Аргументы конструктора: (name, symbol, initialSupply, owner)
        // ⚠️ Убедитесь, что аргументы совпадают с конструктором вашего Solidity контракта!
        const constructorArgs = [tokenName, tokenSymbol, initialSupply, ownerAddress];

        updateStatus("Отправка транзакции деплоя. Подтвердите в кошельке...");
        
        // 4. Деплой (отправка транзакции)
        const deployedContract = await TokenFactory.deploy(...constructorArgs);

        updateStatus(`Транзакция отправлена! Хэш: ${deployedContract.deployTransaction.hash}`);

        // 5. Ожидание подтверждения
        updateStatus("Ожидание подтверждения транзакции (может занять 1-2 минуты)...");
        await deployedContract.deployed();

        // 6. Успех
        const explorerUrl = (TARGET_CHAIN_ID === 5) ? 'https://goerli.etherscan.io' : 'https://etherscan.io';

        updateStatus(`
            🎉 **Токен успешно создан!** 🎉<br>
            Адрес контракта: 
            <a href="${explorerUrl}/address/${deployedContract.address}" target="_blank">
                ${deployedContract.address}
            </a><br>
            Хэш транзакции: 
            <a href="${explorerUrl}/tx/${deployedContract.deployTransaction.hash}" target="_blank">
                ${deployedContract.deployTransaction.hash}
            </a><br>
            <strong>Сеть: ${TARGET_CHAIN_ID === 5 ? 'Goerli Testnet' : 'Mainnet'}</strong>
        `);

    } catch (error) {
        console.error("Ошибка при деплое контракта:", error);
        // Обычно ошибки от кошелька (отмена пользователем, недостаток газа)
        updateStatus(`🚫 Ошибка деплоя: ${error.reason || "Проверьте консоль для деталей или убедитесь, что у вас достаточно ETH для газа."}`, true);
    }
}

// =========================================================
// 6. ОБРАБОТЧИКИ СОБЫТИЙ
// =========================================================

connectBtn.addEventListener('click', connectWallet);
tokenForm.addEventListener('submit', deployToken);

// Инициализация статуса при загрузке
updateWalletStatus(false);
updateStatus("Нажмите 'Подключить кошелек' для начала.");
