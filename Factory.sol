// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

import "@openzeppelin/contracts/proxy/Clones.sol";
import "@openzeppelin/contracts/access/Ownable.sol";

contract TokenFactory is Ownable {
    address public immutable implementation;
    address payable public feeWallet;

    // chainId → комиссия в нативном токене (wei)
    mapping(uint256 => uint256) public feeByChain;

    event TokenCreated(address indexed token, address indexed creator, string name, string symbol);

    constructor(address _implementation, address payable _feeWallet) {
        implementation = _implementation;
        feeWallet = _feeWallet;
    }

    function createToken(
        string calldata name,
        string calldata symbol,
        uint256 totalSupply,
        uint8 decimals
    ) external payable returns (address) {
        uint256 chain = block.chainid;
        uint256 fee = feeByChain[chain];
        require(fee > 0, "Chain not supported");
        require(msg.value >= fee, "Insufficient fee");

        // Твоя комиссия
        feeWallet.transfer(fee);
        if (msg.value > fee) payable(msg.sender).transfer(msg.value - fee);

        address clone = Clones.clone(implementation);
        Token(clone).initialize(name, symbol, totalSupply, decimals, msg.sender);

        emit TokenCreated(clone, msg.sender, name, symbol);
        return clone;
    }

    function setFee(uint256 chainId, uint256 fee) external onlyOwner {
        feeByChain[chainId] = fee;
    }

    function setFeeWallet(address payable newWallet) external onlyOwner {
        feeWallet = newWallet;
    }
}