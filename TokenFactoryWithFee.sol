// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

import "@openzeppelin/contracts/token/ERC20/ERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";

contract ERC20Token is ERC20, Ownable {
    constructor(
        string memory name,
        string memory symbol,
        uint256 initialSupply,
        uint8 decimals_,
        address creator
    ) ERC20(name, symbol) Ownable(creator) {
        _mint(creator, initialSupply * 10 ** decimals_);
    }
}

contract TokenFactoryWithFee is Ownable {
    // === НАСТРОЙКИ КОМИССИИ ===
    uint256 public creationFee = 0.02 ether; // 0.02 ETH по умолчанию
    address payable public feeReceiver;      // Куда отправляется комиссия

    event TokenCreated(
        address indexed tokenAddress,
        address indexed creator,
        string name,
        string symbol,
        uint256 supply
    );

    event FeeUpdated(uint256 newFee);
    event FeeReceiverUpdated(address newReceiver);

    constructor(address payable _feeReceiver) Ownable(msg.sender) {
        feeReceiver = _feeReceiver;
    }

    // Основная функция — создать токен с комиссией
    function createToken(
        string memory name,
        string memory symbol,
        uint256 initialSupply,
        uint8 decimals
    ) external payable returns (address) {
        require(msg.value >= creationFee, "Not enough fee paid");

        // Отправляем комиссию владельцу
        if (msg.value > 0) {
            feeReceiver.transfer(msg.value);
        }

        ERC20Token newToken = new ERC20Token(
            name,
            symbol,
            initialSupply,
            decimals,
            msg.sender
        );

        emit TokenCreated(
            address(newToken),
            msg.sender,
            name,
            symbol,
            initialSupply
        );

        return address(newToken);
    }

    // === Функции только для владельца (вы) ===
    function setCreationFee(uint256 _newFee) external onlyOwner {
        creationFee = _newFee;
        emit FeeUpdated(_newFee);
    }

    function setFeeReceiver(address payable _newReceiver) external onlyOwner {
        require(_newReceiver != address(0), "0x1F51415288f00e50161882A7702D8511208B3Dd8");
        feeReceiver = _newReceiver;
        emit FeeReceiverUpdated(_newReceiver);
    }

    // Получить текущую комиссию в wei
    function getCreationFee() external view returns (uint256) {
        return creationFee;
    }
}