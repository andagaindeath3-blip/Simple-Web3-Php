// Factory.sol — задеплой один раз на каждую сеть
// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

contract TokenFactory {
    address public owner;
    uint256 public creationFee = 0.03 ether; // меняй под сеть

    event TokenCreated(address token, address creator, string name, string symbol);

    constructor() {
        owner = msg.sender;
    }

    function createToken(string memory name, string memory symbol, uint256 totalSupply) external payable returns (address) {
        require(msg.value >= creationFee, "Low fee");
        
        // 3% тебе, остальное возвращается (или можно не возвращать)
        uint256 fee = msg.value / 33; // ~3%
        payable(owner).transfer(fee);

        SimpleToken newToken = new SimpleToken(name, symbol, totalSupply, msg.sender);
        emit TokenCreated(address(newToken), msg.sender, name, symbol);
        return address(newToken);
    }

    function setFee(uint256 newFee) external {
        require(msg.sender == owner);
        creationFee = newFee;
    }
}

contract SimpleToken {
    string public name;
    string public symbol;
    uint8 public decimals = 18;
    uint256 public totalSupply;
    mapping(address => uint256) public balanceOf;
    mapping(address => mapping(address => uint256)) public allowance;

    event Transfer(address indexed from, address indexed to, uint256 value);

    constructor(string memory _name, string memory _symbol, uint256 _supply, address creator) {
        name = _name;
        symbol = _symbol;
        totalSupply = _supply * 10**decimals;
        balanceOf[creator] = totalSupply;
        emit Transfer(address(0), creator, totalSupply);
    }

    function transfer(address to, uint256 value) public returns (bool) {
        require(balanceOf[msg.sender] >= value);
        balanceOf[msg.sender] -= value;
        balanceOf[to] += value;
        emit Transfer(msg.sender, to, value);
        return true;
    }
}