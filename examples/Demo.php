<?php
/**
 * Author: Left.Sky
 * Date: 2019/12/28
 * Version: 0.0.2
 */

require_once __DIR__ . "/../vendor/autoload.php";

use leftsky\eth\Wallet;

// 样例私钥
$prv_key = "your prv key";

$to_address = "0xb1445B8a31FaebB275f013bb7548185cD3db19ce";
$contract = "contract address";
$contract_decimals = 8;

if (true) {
    // 创建钱包
    $wallet = new Wallet();
}

if (false) {
    // 导入钱包私钥
    $wallet = new Wallet($prv_key);
}

if (false) {
    // 主动设置钱包地址
    $wallet->address = "your address";
}

if (true) {
    // 打印钱包地址
    echo "钱包地址：{$wallet->address}\n";
    // 打印钱包私钥
    echo "钱包私钥：{$wallet->prv_key}\n";
}

if (true) {
    // 打印以太坊余额
    echo "ETH 余额(etherescan)：{$wallet->balance()}\n";
    if (false) {
        echo "ETH 余额(infura)：{$wallet->balance(null, 'infura')}\n";
    }
    if (false) {
        // 打印代币余额
        echo "代币 余额：{$wallet->balance($contract)}\n";
    }
}

if (false) {
    // 发送以太坊
    $hash = $wallet->send($to_address, 0.000000000000000001);
    echo "发送以太坊 hash: $hash \n";
    // 发送代币
    $hash = $wallet->send($to_address, 1, $contract, 8);
    echo "发送代币 hash: $hash \n";
}

