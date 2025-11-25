<?php
require_once 'vendor/autoload.php';

use Web3\Web3;
use Web3\Contract;
use Web3\Utils;
use kornrunner\Keccak;
use Ethereum\SolidityCompiler;

// Настройки (потом вынесешь в .env)
$rpcUrl = "https://sepolia.infura.io/v3/ТВОЙ_INFURA_KEY"; // ← поменяй на свой или используй Alchemy
$privateKey = "0xТВОЙ_ПРИВАТНЫЙ_КЛЮЧ_ИЗ_METAMASK"; // ← очень секретно! Лучше брать из формы

// Данные токена (можно передавать через POST)
$tokenName   = $_POST['name']   ?? "MyAwesomeCoin";
$tokenSymbol = $_POST['symbol'] ?? "MAC";
$totalSupply = $_POST['supply'] ?? "1000000000000000000000000"; // 1 млн токенов с 18 decimals

// Шаблон ERC-20 контракта (OpenZeppelin-style, упрощённый)
$solidityCode = "
pragma solidity ^0.8.20;

contract $tokenName {
    string public name = \"$tokenName\";
    string public symbol = \"$tokenSymbol\";
    uint8 public decimals = 18;
    uint256 public totalSupply = $totalSupply;

    mapping(address => uint256) public balanceOf;
    mapping(address => mapping(address => uint256)) public allowance;

    event Transfer(address indexed from, address indexed to, uint256 value);
    event Approval(address indexed owner, address indexed spender, uint256 value);

    constructor() {
        balanceOf[msg.sender] = totalSupply;
        emit Transfer(address(0), msg.sender, totalSupply);
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