<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST');

// Обработка ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверяем POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Только POST-запросы']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['network']) || empty($data['name']) || empty($data['symbol']) || empty($data['owner'])) {
    echo json_encode(['success' => false, 'error' => 'Неверные данные: ' . $input]);
    exit;
}

$network = $data['network'];
$name = $data['name'];
$symbol = $data['symbol'];
$decimals = (int)($data['decimals'] ?? 18);
$supply = $data['supply'];
$owner = $data['owner'];

// ТВОИ ДАННЫЕ: Замени на реальные (для теста используй тестнет-аккаунт с ETH/MATIC)
$SERVER_WALLET = '0xТвойСерверныйАдрес';  // Адрес сервера (с балансом для газа)
$SERVER_PRIVATE_KEY = 'твой_приватный_ключ_без_0x';  // Приватный ключ (ОСТОРОЖНО: храни в секрете!)

if ($SERVER_WALLET === '0xТвойСерверныйАдрес' || $SERVER_PRIVATE_KEY === 'твой_приватный_ключ_без_0x') {
    echo json_encode(['success' => false, 'error' => 'Укажите серверный кошелёк и приватный ключ в create_token.php']);
    exit;
}

// RPC для сетей (публичные, без ключей)
$rpcs = [
    'ethereum'   => ['rpc' => 'https://eth.llamarpc.com', 'chainId' => 1],
    'polygon'    => ['rpc' => 'https://polygon-rpc.com', 'chainId' => 137],
    'bsc'        => ['rpc' => 'https://bsc-dataseed.binance.org', 'chainId' => 56],
    'arbitrum'   => ['rpc' => 'https://arb1.arbitrum.io/rpc', 'chainId' => 42161],
    'optimism'   => ['rpc' => 'https://mainnet.optimism.io', 'chainId' => 10],
    'base'       => ['rpc' => 'https://mainnet.base.org', 'chainId' => 8453],
    'avalanche'  => ['rpc' => 'https://api.avax.network/ext/bc/C/rpc', 'chainId' => 43114],
    'fantom'     => ['rpc' => 'https://rpc.ftm.tools', 'chainId' => 250],
    'zksync'     => ['rpc' => 'https://mainnet.era.zksync.io', 'chainId' => 324],
    'scroll'     => ['rpc' => 'https://rpc.scroll.io', 'chainId' => 534352],
    'linea'      => ['rpc' => 'https://rpc.linea.build', 'chainId' => 59144],
    'mantle'     => ['rpc' => 'https://rpc.mantle.xyz', 'chainId' => 5000],
    'celo'       => ['rpc' => 'https://forno.celo.org', 'chainId' => 42220],
    'moonbeam'   => ['rpc' => 'https://rpc.api.moonbeam.network', 'chainId' => 1284]
];

if (!isset($rpcs[$network])) {
    echo json_encode(['success' => false, 'error' => 'Сеть не поддерживается: ' . $network]);
    exit;
}

$rpc = $rpcs[$network]['rpc'];
$chainId = $rpcs[$network]['chainId'];

// Проверяем Composer (autoload)
if (!file_exists('vendor/autoload.php')) {
    echo json_encode(['success' => false, 'error' => 'Установите Composer: composer require drlecks/simple-web3-php']);
    exit;
}

require 'vendor/autoload.php';
use SWeb3\SWeb3;
use SWeb3\SWeb3_Contract;

// ABI ERC20 (стандартный, без Ownable — для простоты)
$abi = '[{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"_spender","type":"address"},{"name":"_value","type":"uint256"}],"name":"approve","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"totalSupply","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"_from","type":"address"},{"name":"_to","type":"address"},{"name":"_value","type":"uint256"}],"name":"transferFrom","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"decimals","outputs":[{"name":"","type":"uint8"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[{"name":"_owner","type":"address"}],"name":"balanceOf","outputs":[{"name":"balance","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"symbol","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"_to","type":"address"},{"name":"_value","type":"uint256"}],"name":"transfer","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[{"name":"_owner","type":"address"},{"name":"_spender","type":"address"}],"name":"allowance","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"payable":true,"stateMutability":"payable","type":"fallback"},{"anonymous":false,"inputs":[{"indexed":true,"name":"owner","type":"address"},{"indexed":true,"name":"spender","type":"address"},{"indexed":false,"name":"value","type":"uint256"}],"name":"Approval","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"name":"from","type":"address"},{"indexed":true,"name":"to","type":"address"},{"indexed":false,"name":"value","type":"uint256"}],"name":"Transfer","type":"event"}]';

// Bytecode простого ERC20 (мигрируй supply на owner)
$bytecode = '0x608060405234801561001057600080fd5b50604051610d938061011d83398101806040528101906100329291906103e0565b8073ffffffffffffffffffffffffffffffffffffffff16600054604051602001604080515f81526020016100a79150506040516100a59191906105c0565b60006040516020016100c091906105c0565b81548183558152602083019150602083016000820152604080822060ff1916600116815290517f8be0079c531659141344cd1fd0a4f28419497f9722a3daafe3b4186f6b6457e060405160405180910390a450505050506040513d6020811015610158576101576106b0565b81019080805190602001909291905050508060000160009073ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060008573ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020819055508273ffffffffffffffffffffffffffffffffffffffff163373ffffffffffffffffffffffffffffffffffffffff167f8c5be1e5ebec7d5bd14f71427d1e84f3dd0314c0f7b2291e5b200ac8c7c3b92584604051808281526020016101e7565b60405180910390a36001905092915050565b60005481565b600080600260008673ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060003373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002054905082600160008773ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002054101580156102a45750828110155b15156102af57600080fd5b82600160008673ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000206000828254019250508190555082600160008773ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020600082825403925050819055507fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff81101561033c5782600260008773ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060003373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020600082825403925050819055505b8373ffffffffffffffffffffffffffffffffffffffff168573ffffffffffffffffffffffffffffffffffffffff167fddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef85604051808281526020016101e7565b60405180910390a360019150509392505050565b60016020528060005260406000206000915090505481565b600460009054906101000a900460ff1681565b6002602052816000526040600020602052806000526040600020600091509150505481565b6000600160008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020549050919050565b60058054600181600116156101000203166002900480601f016020809104026020016040519081016040528092919081815260200182805460018160011615610100020316600290048015610a265780601f106109fb57610100808354040283529160200191610a26565b820191906000526020600020905b815481529060010190602001808311610a0957829003601f168201915b505050505081565b600081600160003373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000205410151515610a7e57600080fd5b81600160003373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff1681526020019081526020016000206000828254039250508190555081600160008573ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020600082825401925050819055508273ffffffffffffffffffffffffffffffffffffffff163373ffffffffffffffffffffffffffffffffffffffff167fddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef84604051808281526020016101e7565b60405180910390a36001905092915050565b6000600260008473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16815260200190815260200160002060008373ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff168152602001908152602001600020549050929150505600a165627a7a72305820df254047bc8f2904ad3e966b6db116d703bebd40efadadb5e738c836ffc8f58a0029';

// Рассчитываем initialSupply (supply * 10^decimals)
$initialSupply = gmp_mul($supply, gmp_pow(10, $decimals));
$initialSupply = gmp_strval($initialSupply);

try {
    $sweb3 = new SWeb3($rpc);
    $sweb3->chainId = $chainId;
    $sweb3->setPersonalData($SERVER_WALLET, $SERVER_PRIVATE_KEY);

    $contract = new SWeb3_Contract($sweb3, '', $abi);
    $contract->setBytecode($bytecode);

    $nonce = $sweb3->personal->getNonce();
    $gasPrice = $sweb3->call('eth_gasPrice')['data'];

    $extra_params = [
        'gas' => '0x' . dechex(3000000), // Лимит газа
        'gasPrice' => $gasPrice,
        'nonce' => $nonce
    ];

    $deploy_result = $contract->deployContract([$name, $symbol, $decimals, $initialSupply], $extra_params);

    if (!isset($deploy_result['result'])) {
        throw new Exception('Ошибка развёртывания: ' . json_encode($deploy_result));
    }

    $tx_hash = $deploy_result['result'];

    // Ждём подтверждения
    $receipt = null;
    for ($i = 0; $i < 60; $i++) { // До 2 минут
        $receipt_result = $sweb3->call('eth_getTransactionReceipt', [$tx_hash]);
        if (isset($receipt_result['data']) && $receipt_result['data']) {
            $receipt = $receipt_result['data'];
            if (hexdec($receipt['status']) == 1) {
                break;
            } else {
                throw new Exception('Транзакция провалилась');
            }
        }
        sleep(2);
    }

    if (!$receipt) {
        throw new Exception('Таймаут: receipt не получен');
    }

    $contractAddress = $receipt['contractAddress'];

    // Минтим supply владельцу (transfer от сервера к owner)
    $contract = new SWeb3_Contract($sweb3, $contractAddress, $abi);
    $nonce2 = $sweb3->personal->getNonce();
    $extra_params2 = [
        'gas' => '0x' . dechex(100000),
        'gasPrice' => $gasPrice,
        'nonce' => $nonce2
    ];

    $transfer_result = $contract->send('transfer', [$owner, $initialSupply], $extra_params2);

    if (!isset($transfer_result['result'])) {
        throw new Exception('Ошибка трансфера: ' . json_encode($transfer_result));
    }

    echo json_encode([
        'success' => true,
        'contractAddress' => $contractAddress,
        'txHash' => $tx_hash
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>        emit Transfer(address(0), msg.sender, totalSupply);
    }

    function transfer(address to, uint256 value) public returns (bool) {
        require(balanceOf[msg.sender] >= value);
        balanceOf[msg.sender] -= value;
        balanceOf[to] += value;
        emit Transfer(msg.sender, to, value);
        return true;
    }

    function approve(address spender, uint256 value) public returns (bool) {
        allowance[msg.sender][spender] = value;
        emit Approval(msg.sender, spender, value);
        return true;
    }

    function transferFrom(address from, address to, uint256 value) public returns (bool) {
        require(balanceOf[from] >= value);
        require(allowance[from][msg.sender] >= value);
        balanceOf[from] -= value;
        balanceOf[to] += value;
        allowance[from][msg.sender] -= value;
        emit Transfer(from, to, value);
        return true;
    }
}";

// Компиляция контракта (через онлайн-компилятор или локальный solc)
function compileContract($code, $contractName) {
    $tempFile = tempnam(sys_get_temp_dir(), 'sol');
    file_put_contents($tempFile . '.sol', $code);

    $output = shell_exec("solc --combined-json abi,bin $tempFile.sol");
    $json = json_decode($output, true);

    unlink($tempFile . '.sol');

    $key = "contracts:" . basename($tempFile) . ".sol:$contractName";
    return [
        'abi' => $json['contracts'][$key]['abi'],
        'bytecode' => '0x' . $json['contracts'][$key]['bin']
    ];
}

// Основная логика деплоя
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Компилируем контракт
        $compiled = compileContract($solidityCode, $tokenName);
        $abi = $compiled['abi'];
        $bytecode = $compiled['bytecode'];

        // 2. Подключаемся к блокчейну
        $web3 = new Web3($rpcUrl);
        $eth = $web3->eth;

        // 3. Получаем адрес из приватного ключа
        $privateKey = $privateKey;
        $account = \Web3\Utils::privateKeyToAddress($privateKey);

        // 4. Создаём транзакцию
        $tx = [
            'from' => $account,
            'data' => $bytecode,
            'gas' => '0x' . dechex(5000000),
            'gasPrice' => '0x' . dechex(20000000000), // 20 gwei
        ];

        // 5. Подписываем и отправляем
        $signedTx = null;
        $eth->accounts->signTransaction($tx, $privateKey, function ($err, $signed) use (&$signedTx) {
            if ($err) throw new Exception($err->getMessage());
            $signedTx = $signed;
        });

        $txHash = null;
        $eth->sendRawTransaction('0x' . $signedTx->raw, function ($err, $hash) use (&$txHash) {
            if ($err) throw new Exception($err->getMessage());
            $txHash = $hash;
        });

        echo json_encode([
            'success' => true,
            'txHash' => $txHash,
            'contractAddress' => null, // появится через ~15 сек
            'message' => "Токен $tokenName ($tokenSymbol) успешно запущен!",
            'explorer' => "https://sepolia.etherscan.io/tx/$txHash"
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Token Creator</title>
    <style>
        body { font-family: Arial; background: #000; color: #0f0; padding: 50px; text-align: center; }
        input, button { padding: 15px; margin: 10px; font-size: 1.2em; width: 300px; }
        button { background: #0f0; color: #000; border: none; cursor: pointer; }
        .result { margin-top: 30px; padding: 20px; background: #111; border: 1px solid #0f0; }
    </style>
</head>
<body>
    <h1>PHP Token Creator 2025</h1>
    <p>Создай свой ERC-20 токен за 30 секунд</p>

    <form method="POST" id="createForm">
        <input type="text" name="name" placeholder="Название (например, GrokCoin)" required><br>
        <input type="text" name="symbol" placeholder="Символ (GROK)" maxlength="10" required><br>
        <input type="number" name="supply" placeholder="Кол-во токенов (в млн)" value="1" required><br>
        <button type="submit">ЗАПУСТИТЬ ТОКЕН</button>
    </form>

    <div class="result" id="result"></div>

    <script>
    document.getElementById('createForm').onsubmit = async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.set('supply', formData.get('supply') + '000000000000000000'); // 18 decimals

        const res = await fetch('', { method: 'POST', body: formData });
        const json = await res.json();

        document.getElementById('result').innerHTML = json.success
            ? `<b>УСПЕХ!</b><br>Транзакция: <a href="\( {json.explorer}" target="_blank"> \){json.txHash}</a><br>Дождись подтверждения ~15 сек`
            : `<b>Ошибка:</b> ${json.error}`;
    };
    </script>
</body>

</html>
