// create-token.js — Реальное создание SPL-токена + Metaplex Metadata
const MY_WALLET = "9K8XuHFqGx2w2Jk76xyYrUwj9HY4iLiQP9wEdrWhCenF";
const FEE_DOLLARS = 5; // Комиссия $5

async function createRealToken() {
    if (!wallet) return alert("Подключи кошелёк!");

    const name = document.getElementById('tokenName').value.trim();
    const symbol = document.getElementById('tokenSymbol').value.trim().toUpperCase();
    const desc = document.getElementById('tokenDesc').value.trim();
    const file = document.getElementById('tokenImage').files[0];
    const supply = parseInt(document.getElementById('tokenSupply').value);

    if (!name || !symbol || !file || supply < 1000000) {
        return alert("Заполни все поля правильно!");
    }

    const btn = document.getElementById('createBtn');
    btn.disabled = true;
    btn.textContent = "Создаём токен...";

    try {
        const connection = new solanaWeb3.Connection("https://api.mainnet-beta.solana.com", "confirmed");

        // 1. Получаем актуальную цену SOL
        const priceRes = await fetch('https://api.coingecko.com/api/v3/simple/price?ids=solana&vs_currencies=usd');
        const priceData = await priceRes.json();
        const solPrice = priceData.solana.usd;
        const solAmount = FEE_DOLLARS / solPrice;

        // 2. Берём комиссию $5 → 100% тебе
        const transferIx = solanaWeb3.SystemProgram.transfer({
            fromPubkey: wallet.publicKey,
            toPubkey: new solanaWeb3.PublicKey(MY_WALLET),
            lamports: Math.floor(solAmount * solanaWeb3.LAMPORTS_PER_SOL)
        });

        // 3. Создаём Mint (SPL Token 2022)
        const mint = solanaWeb3.Keypair.generate();
        const mintRent = await connection.getMinimumBalanceForRentExemption(82);
        
        const createMintIx = solanaWeb3.SystemProgram.createAccount({
            fromPubkey: wallet.publicKey,
            newAccountPubkey: mint.publicKey,
            space: 82,
            lamports: mintRent,
            programId: new solanaWeb3.PublicKey("TokenzQdBNbLqP5VEhdkAS6EPFLC1PHnBqCXEpPxuEb")
        });

        const initMintIx = Token.createInitMintInstruction(
            new solanaWeb3.PublicKey("TokenzQdBNbLqP5VEhdkAS6EPFLC1PHnBqCXEpPxuEb"),
            mint.publicKey,
            9,
            wallet.publicKey,
            null
        );

        // 4. Минтим токены
        const associatedAccount = await Token.getAssociatedTokenAddress(
            Token.ASSOCIATED_TOKEN_PROGRAM_ID,
            Token.TOKEN_PROGRAM_ID,
            mint.publicKey,
            wallet.publicKey
        );

        const createATAIx = Token.createAssociatedTokenAccountInstruction(
            Token.ASSOCIATED_TOKEN_PROGRAM_ID,
            Token.TOKEN_PROGRAM_ID,
            mint.publicKey,
            associatedAccount,
            wallet.publicKey,
            wallet.publicKey
        );

        const mintToIx = Token.createMintToInstruction(
            Token.TOKEN_PROGRAM_ID,
            mint.publicKey,
            associatedAccount,
            wallet.publicKey,
            [],
            supply
        );

        // 5. Загружаем метаданные через Metaplex
        const imageFile = new File([file], "logo.png", { type: file.type });
        const { uri: metadataUri } = await metaplex.nfts().uploadMetadata({
            name, symbol, description: desc || "Создано на RayClone Pro", image: imageFile
        });

        // 6. Создаём Metaplex Metadata
        const { nft } = await metaplex.nfts().create({
            uri: metadataUri,
            name,
            symbol,
            sellerFeeBasisPoints: 500, // 5%
            creators: [{ address: wallet.publicKey, share: 100 }],
            isMutable: true
        });

        // Собираем транзакцию
        const tx = new solanaWeb3.Transaction();
        tx.add(transferIx, createMintIx, initMintIx, createATAIx, mintToIx);

        tx.feePayer = wallet.publicKey;
        tx.recentBlockhash = (await connection.getLatestBlockhash()).blockhash;
        tx.partialSign(mint);

        const signed = await wallet.signTransaction(tx);
        const sig = await connection.sendRawTransaction(signed.serialize());

        // Обновляем баланс
        addCommission(solAmount, "sol");

        alert(`ТОКЕН УСПЕШНО СОЗДАН!\n\nMint: ${mint.publicKey.toBase58()}\nТы получил: $${solAmount.toFixed(2)}\nTx: \( {sig}\n\nСсылка: https://solscan.io/token/ \){mint.publicKey.toBase58()}`);

    } catch (err) {
        console.error(err);
        alert("Ошибка создания токена: " + err.message);
    }

    btn.disabled = false;
    btn.textContent = "Создать токен за $5";
}

// Подключаем к кнопке
document.getElementById('createBtn').onclick = createRealToken;
