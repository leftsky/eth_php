<?php
/**
 * Author: Left.Sky
 * Date: 2019/12/20
 * Version: 0.0.1
 */

require_once __DIR__ . "/../vendor/autoload.php";

use leftsky\eth\Wallet;

$to_address = "0xb1445B8a31FaebB275f013bb7548185cD3db19ce";

$wallet = new Wallet($wallet['prv_key']);
var_dump($wallet->balance());
$wallet->send($to_address, 0.000000000000000001);
//var_dump($wallet);
