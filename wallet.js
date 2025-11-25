async function connectWallet(networks) {
    if (networks === 'solana') {
        if (window.solana && window.solana.isPhantom) {
            await window.solana.connect();
            return window.solana.publicKey.toString();
        }
        alert('Установи Phantom Wallet');
        return false;
    } else if (networks === 'ton') {
        // TON Connect (2025 SDK)
        if (typeof TonConnectUI !== 'undefined') {
            await tonConnectUI.connect();
            return true;
        }
        alert('Установи TON Wallet');
        return false;
    } else {
        // EVM: MetaMask
        if (window.ethereum) {
            await window.ethereum.request({method: 'eth_requestAccounts'});
            return await window.ethereum.request({method: 'eth_accounts'})[0];
        }
        alert('Установи MetaMask');
        return false;
    }
}