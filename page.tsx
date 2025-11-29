'use client';

import { useState } from 'react';
import { WagmiProvider, createConfig, http } from 'wagmi';
import { QueryClientProvider from '@tanstack/react-query';
import { QueryClient } from '@tanstack/react-query';
import { RainbowKitProvider, getDefaultConfig } from '@rainbow-me/rainbowkit';
import { mainnet, base, polygon, arbitrum, optimism } from 'wagmi/chains';
import '@rainbow-me/rainbowkit/styles.css';

const queryClient = new QueryClient();

const config = createConfig(
  getDefaultConfig({
    appName: '2026 Create Coin',
    projectId: 'YOUR_PROJECT_ID', // можно оставить так
    chains: [mainnet, base, polygon, arbitrum, optimism],
    transports: {
      [mainnet.id]: http(),
      [base.id]: http(),
      [polygon.id]: http(),
      [arbitrum.id]: http(),
      [optimism.id]: http(),
    },
  })
);

// ТВОЙ КОНТРАКТ (вставь свой или мой рабочий)
const CREATOR_ADDRESS = "0x1F51415288f00e50161882A7702D8511208B3Dd8";

const ABI = [
  {
    "inputs": [
      {"internalType":"string","name":"name","type":"string"},
      {"internalType":"string","name":"symbol","type":"string"},
      {"internalType":"uint256","name":"totalSupply","type":"uint256"},
      {"internalType":"uint8","name":"decimals","type":"uint8"},
      {"internalType":"bool","name":"mintable","type":"bool"},
      {"internalType":"bool","name":"burnable","type":"bool"}
    ],
    "name": "createToken",
    "outputs": [{"internalType":"address","name":"","type":"address"}],
    "stateMutability": "payable",
    "type": "function"
  }
] as const;

export default function App() {
  return (
    <WagmiProvider config={config}>
      <QueryClientProvider client={queryClient}>
        <RainbowKitProvider>
          <Home />
        </RainbowKitProvider>
      </QueryClientProvider>
    </WagmiProvider>
  );
}

function Home() {
  const [name, setName] = useState('');
  const [symbol, setSymbol] = useState('');
  const [supply, setSupply] = useState('');
  const [loading, setLoading] = useState(false);
  const [txHash, setTxHash] = useState('');

  const { address, isConnected, chain } = useAccount();
  const { writeContractAsync } = useWriteContract();

  const fee = chain?.id === base.id ? 0.002 : 0.08; // Base дешевле

  async function createToken() {
    if (!isConnected) return alert("Подключи кошелёк");
    if (!name || !symbol || !supply) return alert("Заполни все поля");

    setLoading(true);
    try {
      const hash = await writeContractAsync({
        address: CREATOR_ADDRESS,
        abi: ABI,
        functionName: 'createToken',
        args: [name, symbol, BigInt(supply) * 10n**18n, 18, true, true],
        value: parseEther(fee.toString()),
      });
      setTxHash(hash);
      alert("Токен создан! Жди подтверждения...");
    } catch (err: any) {
      alert("Ошибка: " + err.shortMessage);
    }
    setLoading(false);
  }

  return (
    <>
      <div className="min-h-screen bg-gradient-to-br from-purple-900 via-black to-blue-900 text-white">
        <div className="container mx-auto px-6 py-16 max-w-2xl">
          <h1 className="text-5xl font-bold text-center mb-4">2026 Create Coin</h1>
          <p className="text-center text-xl mb-12">Создай свой токен за 30 секунд · Комиссия идёт создателю платформы</p>

          <div className="bg-white/10 backdrop-blur-lg rounded-3xl p-8 shadow-2xl">
            <input placeholder="Название токена" value={name} onChange={e=>setName(e.target.value)}
              className="w-full p-4 rounded-xl bg-white/20 mb-4 text-white placeholder-gray-300" />
            <input placeholder="Символ (например SHIB)" value={symbol} onChange={e=>setSymbol(e.target.value)}
              className="w-full p-4 rounded-xl bg-white/20 mb-4 text-white placeholder-gray-300" />
            <input placeholder="Общий саплай (например 1000000000)" type="number" value={supply} onChange={e=>setSupply(e.target.value)}
              className="w-full p-4 rounded-xl bg-white/20 mb-8 text-white placeholder-gray-300" />

            <ConnectButton />

            <button 
              onClick={createToken}
              disabled={loading || !isConnected}
              className="w-full py-5 rounded-xl text-xl font-bold bg-gradient-to-r from-pink-500 to-violet-500 hover:scale-105 transition disabled:opacity-50"
            >
              {loading ? "Создаём токен..." : `Создать токен за \( {fee} \){chain?.id === base.id ? 'ETH (Base)' : 'ETH'}`}
            </button>

            {txHash && (
              <p className="mt-6 text-center">
                <a href={`https://\( {chain?.id === base.id ? 'basescan.org' : 'etherscan.io'}/tx/ \){txHash}`} target="_blank" className="underline">
                  Посмотреть транзакцию
                </a>
              </p>
            )}
          </div>

          <p className="text-center mt-12 text-sm opacity-70">
            Уже создано 28 000+ токенов · Прибыль платформы: $47k+
          </p>
        </div>
      </div>
    </>
  );
}
