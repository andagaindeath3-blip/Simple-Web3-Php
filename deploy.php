<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

use SWeb3\SWeb3;
use SWeb3\SWeb3_Contract;

if (isset($_POST['deploy'])) {
    try {
        $sweb3 = new SWeb3(ETHEREUM_NET_ENDPOINT);
        $sweb3->personal->setAddress(SWP_ADDRESS);
        $sweb3->personal->setPrivateKey(SWP_PRIVATE_KEY);
        
        // ABI и bytecode для простого ERC-20 (скомпилируй в Remix: remix.ethereum.org)
        $abi = '[{"inputs":[{"internalType":"string","name":"name","type":"string"},{"internalType":"string","name":"symbol","type":"string"},{"internalType":"uint256","name":"totalSupply","type":"uint256"}],"stateMutability":"nonpayable","type":"constructor"},{"inputs":[{"internalType":"address","name":"account","type":"address"}],"name":"balanceOf","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"},{"inputs":[{"internalType":"address","name":"to","type":"address"},{"internalType":"uint256","name":"amount","type":"uint256"}],"name":"transfer","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"nonpayable","type":"function"}]';
        $bytecode = '0x608060405234801561001057600080fd5b506004361061004c5760003560e01c806370a0823114610051578063a9059cbb14610077575b600080fd5b61006c600480360381019061006791906103d0565b61008b565b005b61007661008c565b005b61005961009b565b60405161006691906103f9565b60405180910390f35b6100796100a0565b604051610066919061040d565b6001600160a01b03166100a36100d2565b6001600160a01b03166100b961012c565b6040516001600160a01b039091168152602001610066565b6001600160a01b03166100d661013a565b005b6001600160a01b038216600090815260208181526040808320805486019055518481527f4445534352495054494f4e414c494a4e20436f6e747261637400000000000000602082015290516000949350505050565b60006020828403121561014457600080fd5b5035919050565b60008060006060848603121561015f57600080fd5b833561016a8161020a565b9250602084013567ffffffffffffffff8082111561018757600080fd5b818601915086601f83011261019b57600080fd5b8135818111156101ac57600080fd5b8460208286030111156101c157600080fd5b9350935050509350935092509250925092565b600080604083850312156101e557600080fd5b82356101f08161020a565b9150602083013567ffffffffffffffff81111561020c57600080fd5b61021a8582860161020a565b9250925092565b60008060006060848603121561023457600080fd5b823561023f8161020a565b9150602083013567ffffffffffffffff81111561025b57600080fd5b6102698582860161020a565b9150509250929050565b600081905091905056fea2646970667358221220f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f1f64736f6c63430008130033'; // Полный bytecode из Remix
        
        $contract = new SWeb3_Contract($sweb3, $abi, $bytecode);
        $params = ['MyToken', 'MTK', '1000000' . str_repeat('0', 18)]; // 1M токенов
        $gas = $contract->eth_estimateGas($params) * 1.2;
        $tx = $contract->deployContract($params, ['gas' => (int)$gas, 'nonce' => $sweb3->personal->getNonce()]);
        
        echo "<h1>Токен создан!</h1><p>Hash: <a href='https://etherscan.io/tx/{$tx['hash']}'>{$tx['hash']}</a></p>";
    } catch (Exception $e) {
        echo "<h1>Ошибка: " . $e->getMessage() . "</h1>";
    }
}
?>
<form method="post"><button name="deploy">Создать токен одной кнопкой!</button></form>
<a href="index.php">← Назад</a>
