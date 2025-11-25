<?php
// Пример бриджинга через Wormhole API
header('Content-Type: application/json');
$tokenAddr = $_POST['token'] ?? '';
$fromChain = $_POST['from'] ?? 'ethereum';
$toChain = $_POST['to'] ?? 'solana';

$ch = curl_init('https://api.wormhole.com/transfer');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'token' => $tokenAddr,
    'from' => $fromChain,
    'to' => $toChain,
    'amount' => 1000000 // Пример
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
echo $response; // { "txHash": "...", "newAddr": "..." }