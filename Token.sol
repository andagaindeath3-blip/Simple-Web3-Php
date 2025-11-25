// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

import "@openzeppelin/contracts-upgradeable/token/ERC20/ERC20Upgradeable.sol";
import "@openzeppelin/contracts-upgradeable/access/OwnableUpgradeable.sol";
import "@openzeppelin/contracts-upgradeable/proxy/utils/Initializable.sol";

contract Token is Initializable, ERC20Upgradeable, OwnableUpgradeable {
    function initialize(
        string memory name_,
        string memory symbol_,
        uint256 totalSupply_,
        uint8 decimals_,
        address owner_
    ) external initializer {
        __ERC20_init(name_, symbol_);
        __Ownable_init(owner_);
        _mint(owner_, totalSupply_ * 10 ** decimals_);
    }
}