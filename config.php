<?php
// Загрузи из .env: composer require vlucas/phpdotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// API ключи (получи бесплатно)
define('ALCHEMY_API_KEY', $_ENV['ALCHEMY_API_KEY'] ?? 'your-alchemy-key'); // Для EVM
define('QUICKNODE_URL', $_ENV['QUICKNODE_URL'] ?? 'https://your-quicknode.solana'); // Для Solana
define('TON_API_KEY', $_ENV['TON_API_KEY'] ?? 'your-ton-key'); // Для TON
define('WORMHOLE_BRIDGE_KEY', $_ENV['WORMHOLE_KEY'] ?? 'your-wormhole'); // Для multi-chain

// Wallet приватные ключи (НЕ ХРАНИ В КОДЕ! Используй для сервера)
define('EVM_PRIVATE_KEY', $_ENV['EVM_PK'] ?? '0x...');
define('SOLANA_PRIVATE_KEY', $_ENV['SOL_PK'] ?? '...');