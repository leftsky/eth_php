<?php
/**
 * Author: Left.Sky
 * Date: 2019/12/20
 * Version: 0.0.1
 */

require_once __DIR__ . "/../vendor/autoload.php";

use leftsky\eth\Wallet;


// 创建钱包
$wallet = new Wallet();
// 主动设置钱包地址
//$wallet->address = '';
// 导入钱包
//$wallet = new Wallet($prv_key);
// 打印钱包地址
echo "钱包地址：{$wallet->address}\n";
// 打印钱包私钥
echo "钱包私钥：{$wallet->prv_key}\n";
// 打印以太坊余额
echo "ETH 余额(etherescan)：{$wallet->balance()}\n";
echo "ETH 余额(infura)：{$wallet->balance(null, 'infura')}\n";
//// 打印代币余额
//echo "代币 余额：{$wallet->balance($tm_contract)}\n";
//// 发送以太坊
//$hash = $wallet->send($to_address, 0.000000000000000001);
//echo "发送以太坊 hash: $hash \n";
//// 发送代币
//$hash = $wallet->send($to_address, 1, $tm_contract, 8);
//echo "发送代币 hash: $hash \n";
