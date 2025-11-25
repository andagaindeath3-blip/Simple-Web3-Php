<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Для AJAX
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require 'vendor/autoload.php';
use SWeb3\SWeb3;
use SWeb3\Utils;
use SWeb3\SWeb3_Contract;
use phpseclib3\Math\BigInteger as BigNumber;

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Нет данных']);
    exit;
}

// Настройки (поменяйте на свои! Для теста — Sepolia Infura)
$rpcUrl = 'https://sepolia.infura.io/v3/YOUR_INFURA_PROJECT_ID'; // Получите на infura.io
$chainId = $data['chainId'] ?? '11155111'; // Sepolia по умолчанию
$fromAddress = $data['account'];
$privateKey = ''; // Здесь пользователь должен ввести приватный ключ! (Добавьте форму ввода в index.php для безопасности)
// ВАЖНО: В проде используйте сессии или шифрование для privateKey. Для примера — hardcoded или из POST.
if (empty($privateKey)) {
    echo json_encode(['success' => false, 'error' => 'Введите приватный ключ в форме!']);
    exit;
}
if (substr($privateKey, 0, 2) !== '0x') $privateKey = '0x' . $privateKey;

// Параметры токена
$name = $data['name'];
$symbol = $data['symbol'];
$decimals = (int)$data['decimals'];
$supply = (int)$data['supply'];
$totalSupply = Utils::toWei((string)$supply, 'ether'); // Масштабируем под decimals позже в ABI

try {
    $sweb3 = new SWeb3($rpcUrl);
    $sweb3->setPersonalData($fromAddress, $privateKey);
    $sweb3->chainId = $chainId;

    // ABI и Bytecode (вставьте скомпилированные из Remix! Пример сокращён)
    $abi = '[{"inputs":[{"internalType":"string","name":"_name","type":"string"},{"internalType":"string","name":"_symbol","type":"string"},{"internalType":"uint8","name":"_decimals","type":"uint8"},{"internalType":"uint256","name":"_totalSupply","type":"uint256"}],"stateMutability":"nonpayable","type":"constructor"},{"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"from","type":"address"},{"indexed":true,"internalType":"address","name":"to","type":"address"},{"indexed":false,"internalType":"uint256","name":"value","type":"uint256"}],"name":"Transfer","type":"event"},{"inputs":[{"internalType":"address","name":"spender","type":"address"},{"internalType":"uint256","name":"value","type":"uint256"}],"name":"approve","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"address","name":"from","type":"address"},{"internalType":"address","name":"to","type":"address"},{"internalType":"uint256","name":"value","type":"uint256"}],"name":"transferFrom","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"address","name":"to","type":"address"},{"internalType":"uint256","name":"value","type":"uint256"}],"name":"transfer","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"address","name":"","type":"address"}],"name":"balanceOf","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"name","outputs":[{"internalType":"string","name":"","type":"string"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"symbol","outputs":[{"internalType":"string","name":"","type":"string"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"decimals","outputs":[{"internalType":"uint8","name":"","type":"uint8"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"totalSupply","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"},{"inputs":[{"internalType":"address","name":"","type":"address"},{"internalType":"internalType":"address","name":"owner","type":"address"}],"name":"allowance","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"} ]'; // Полный ABI из Remix

    $bytecode = '0x608060405234801561001057600080fd5b5060405161084e38038061084e8339810160409081019150505b600080fd5b6182368180556001600160a01b03811691526001600160a01b03831660039055506040819091526001600160a01b0383166001600160a01b03198116918291556040805160208101835260016020526020820152604081018390556040808201526001600160a01b0383166024820152604482018390528152606084019150608482015160005b818110156102b857600080fd5b505050505050565b6000806003604084860312156102d557600080fd5b600091505b6102e5868286016102a8565b92505060206102f4868286016102a8565b9150509250925092565b60005b8381101561031857808201555b5050509156fe'; // Полный bytecode из Remix (замените!)

    // Кодируем constructor params (ABIv2)
    $constructorParams = [$name, $symbol, $decimals, $totalSupply];
    $encodedParams = \SWeb3\ABI::EncodeParameters_External(['string', 'string', 'uint8', 'uint256'], $constructorParams);

    // Создаём контракт
    $contract = new SWeb3_Contract($sweb3, '', $abi);
    $contract->setBytecode($bytecode);

    // Деплой
    $extraData = ['nonce' => $sweb3->personal->getNonce()];
    $gasLimit = 2000000; // Оцените через eth_estimateGas
    $result = $contract->deployContract($constructorParams, $extraData + ['gasLimit' => $gasLimit]);

    // Ждём receipt (опционально, через polling)
    sleep(10); // Простой wait, в проде — poll eth_getTransactionReceipt
    $receipt = $sweb3->call('eth_getTransactionReceipt', [$result->result]);

    if ($receipt->result && $receipt->result->status === '0x1') {
        echo json_encode([
            'success' => true,
            'contract' => $receipt->result->contractAddress,
            'tx' => $result->result
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Транзакция провалилась']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>