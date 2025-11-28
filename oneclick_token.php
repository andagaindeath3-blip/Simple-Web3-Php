<?php require_once 'config.php'; require_once 'vendor/autoload.php';
use SWeb3\SWeb3; use SWeb3\SWeb3_Contract;

session_start();
if (!isset($_SESSION['auth'])) { header('Location: index.php'); die(); }

if (isset($_POST['create'])) {
    $adj = ['Moon','Hyper','Quantum','Alpha','Diamond','Rocket','Golden','Neon','Crypto','Titan'];
    $noun = ['Coin','Token','Gem','Wolf','Shark','Star','Dragon','Cash','Beast','Lord'];
    $name = $adj[array_rand($adj)].' '.$noun[array_rand($noun)];
    $symbol = strtoupper(substr($adj[array_rand($adj)],0,3).substr($noun[array_rand($noun)],0,2));

    try {
        $web3 = new SWeb3(RPC_URL);
        $web3->personal->setAddress(WALLET_ADDRESS);
        $web3->personal->setPrivateKey(PRIVATE_KEY);

        $abi = json_decode(file_get_contents('https://raw.githubusercontent.com/OpenZeppelin/openzeppelin-contracts/master/build/contracts/ERC20.json'))->abi;
        $bytecode = '0x608060405234801561001057600080fd5b506104e3806100206000396000f3fe60806040526004361061003f57'; // тот же

        $contract = new SWeb3_Contract($web3, $abi, $bytecode);
        $gas = $contract->eth_estimateGas([$name, $symbol]) * 1.3;

        $tx = $contract->deployContract([$name, $symbol], ['gas' => (int)$gas]);

        $exp = IS_MAINNET ? 'etherscan.io' : 'sepolia.etherscan.io';
        echo "<h1 style='color:lime;text-align:center;margin-top:100px;'>ГОТОВО!</h1>";
        echo "<h2 style='text-align:center;'>Твой токен: $name ($symbol)</h2>";
        echo "<p style='text-align:center;font-size:22px;'>
                Хэш: <a href='https://$exp/tx/{$tx['hash']}' target='_blank'>{$tx['hash']}</a>
              </p>";
        echo "<p style='text-align:center;'><a href='oneclick_token.php'>Создать ещё один</a></p>";
    } catch(Exception $e) {
        echo "<h2 style='color:red;text-align:center;'>Ошибка: ".$e->getMessage()."</h2>";
    }
    die();
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Токен одной кнопкой</title>
<style>body{background:#000;color:#0f0;text-align:center;padding-top:10%;font-family:Arial;}</style></head><body>
<h1>Создай свою криптовалюту<br>за 30 секунд</h1>
<h2>Нажми кнопку — получишь настоящий токен <?= IS_MAINNET?'на MAINNET':'' ?></h2>
<form method="post">
  <button name="create" style="padding:40px 100px;font-size:32px;background:lime;color:#000;border:none;border-radius:20px;cursor:pointer;">
    СОЗДАТЬ ТОКЕН СЕЙЧАС
  </button>
</form>
<br><a href="index.php">На главную</a>
</body></html>
