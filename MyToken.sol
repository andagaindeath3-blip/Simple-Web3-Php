// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

import "@openzeppelin/contracts-upgradeable/token/ERC20/ERC20Upgradeable.sol";
import "@openzeppelin/contracts-upgradeable/access/OwnableUpgradeable.sol";

contract MyToken is ERC20Upgradeable, OwnableUpgradeable {
    function initialize(string memory name, string memory symbol, uint256 supply, uint8 decimals_, address owner) external initializer {
        __ERC20_init(name, symbol);
        __Ownable_init(owner);
        _mint(owner, supply * 10 ** decimals_);
    }
}