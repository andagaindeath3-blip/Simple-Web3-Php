<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // Composer: web3p/ethereum-tx, ton/ton-client-php, solana-php

$data = json_decode(file_get_contents('php://input'), true);
$networks = $data['networks'] ?? 'evm';
$addresses = [];

try {
    if ($networks === 'all' || $networks === 'evm') {
        // EVM деплой (Ethereum/BSC/Polygon/Base) через Alchemy + OpenZeppelin
        $web3 = new Web3p\Ethereum\Eth('https://eth-mainnet.alchemyapi.io/v2/' . ALCHEMY_API_KEY);
        $contract = new Web3p\Contract($web3, 'ERC20.json'); // Загрузи OpenZeppelin ABI
        $tx = $contract->deploy([
            'name' => $data['name'],
            'symbol' => $data['symbol'],
            'supply' => $data['supply']
        ], EVM_PRIVATE_KEY);
        $addresses['evm'] = $tx->contractAddress; // Деплой на все EVM через multi-send API 20lab

        // Или используй cURL к 20lab API
        $ch = curl_init('https://api.20lab.app/generate');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $evmData = json_decode($response, true);
        $addresses['evm'] = $evmData['addresses']; // Массив для Ethereum/BSC/etc.
    }

    if ($networks === 'all' || $networks === 'solana') {
        // Solana SPL через QuickNode/Smithii API
        $client = new SolanaPhp\Client(QUICKNODE_URL);
        $mint = $client->createMint(SOLANA_PRIVATE_KEY, $data['supply']);
        $addresses['solana'] = $mint->address;
    }

    if ($networks === 'all' || $networks === 'ton') {
        // TON Jetton через TON API
        $ton = new Ton\TonClient(TON_API_KEY);
        $jetton = $ton->deployJetton(EVM_PRIVATE_KEY, [ // Адаптируй под TON wallet
            'name' => $data['name'],
            'symbol' => $data['symbol'],
            'supply' => $data['supply']
        ]);
        $addresses['ton'] = $jetton->address;
    }

    // Multi-chain бридж (если 'all')
    if ($networks === 'all') {
        $bridgeRes = file_get_contents("https://api.wormhole.com/bridge?token=" . $addresses['evm'][0] . "&to=solana&key=" . WORMHOLE_BRIDGE_KEY);
        $addresses['bridge'] = json_decode($bridgeRes, true);
    }

    echo json_encode(['success' => true, 'addresses' => $addresses]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}