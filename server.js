// backend/server.js
require('dotenv').config();
const express = require('express');
const bodyParser = require('body-parser');
const fs = require('fs');
const path = require('path');
const { ethers } = require('ethers');

const app = express();
app.use(bodyParser.json());

const RPC_URL = process.env.RPC_URL;
const DEPLOYER_PRIVATE_KEY = process.env.DEPLOYER_PRIVATE_KEY;

if (!RPC_URL || !DEPLOYER_PRIVATE_KEY) {
  console.warn("RPC_URL or DEPLOYER_PRIVATE_KEY not set in .env — server will not work until set.");
}

const provider = new ethers.JsonRpcProvider(RPC_URL);
const wallet = new ethers.Wallet(DEPLOYER_PRIVATE_KEY, provider);

// read compiled artifact from hardhat artifacts (assumes you ran npx hardhat compile)
const artifactPath = path.join(__dirname, '..', 'artifacts', 'contracts', 'Token.sol', 'SimpleERC20.json');

let artifact;
try {
  artifact = JSON.parse(fs.readFileSync(artifactPath, 'utf8'));
} catch (e) {
  console.error('Cannot read artifact. Run `npx hardhat compile` first. Expected path:', artifactPath);
}

app.post('/deploy', async (req, res) => {
  try {
    if (!artifact) return res.status(500).json({ error: 'Contract artifact not found. Compile first.' });

    const { name, symbol, supply, decimals = 18 } = req.body;
    if (!name || !symbol || !supply) return res.status(400).json({ error: 'name, symbol, supply required' });

    const totalSupply = ethers.parseUnits(String(supply), Number(decimals));

    const factory = new ethers.ContractFactory(artifact.abi, artifact.bytecode, wallet);
    const contract = await factory.deploy(name, symbol, totalSupply, Number(decimals));
    await contract.waitForDeployment();

    return res.json({ address: contract.target, txHash: contract.deploymentTransaction()?.hash });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ error: err.message || String(err) });
  }
});

const PORT = process.env.PORT || 4000;
app.listen(PORT, () => console.log(`Backend listening on ${PORT}`));
