// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

import "@openzeppelin/contracts/proxy/Clones.sol";
import "@openzeppelin/contracts/access/Ownable.sol";

contract TokenFactory is Ownable {
    address public implementation;
    address payable public feeWallet;
    mapping(uint256 => uint256) public feeByChain; // chainId => fee in wei

    event TokenCreated(address token, address creator, string name, string symbol, uint256 chainId);

    constructor(address _implementation, address payable _feeWallet) {
        implementation = _implementation;
        feeWallet = _feeWallet;
    }

    function createToken(
        string memory name,
        string memory symbol,
        uint256 totalSupply,
        uint8 decimals
    ) external payable returns (address) {
        uint256 chainId = block.chainid;
        uint256 requiredFee = feeByChain[chainId];
        require(requiredFee > 0, "Chain not supported");
        require(msg.value >= requiredFee, "Insufficient fee");

        // Комиссия тебе
        feeWallet.transfer(requiredFee);
        if (msg.value > requiredFee) {
            payable(msg.sender).transfer(msg.value - requiredFee);
        }

        address clone = Clones.clone(implementation