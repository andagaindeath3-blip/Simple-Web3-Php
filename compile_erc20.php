<?php
// Компиляция ERC20.sol в ABI/Bytecode (запускай локально; требует solc)
$solidityCode = file_get_contents('Examples/swp_contract.sol');  // Или стандартный ERC20.sol

// Используй exec('solc --abi --bin ERC20.sol') для генерации
// Затем вставь в config.php
echo "Сгенерируй ABI/Bytecode в Remix IDE и вставь в config.php";
?>
