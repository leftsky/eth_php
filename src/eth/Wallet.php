<?php
/**
 * Author: Left.Sky
 * Date: 2019/12/20
 * Version: 0.1.0
 */

namespace leftsky\eth;

use Elliptic\EC;
use kornrunner\Keccak;
use Web3\Eth;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Utils;
use Web3\Web3;
use Web3p\EthereumTx\Transaction;
use xtype\Ethereum\Client;

class Wallet
{
    public $prv_key;
    private $pub_key;
    public $address;
    private $result;
    private $infura_endpoint = 'https://mainnet.infura.io/v3/6a68fac988fb4d899aeeb8d62e037d59';
    private $infura_secret = '84277eb271c348c98a7c88000c0fccbc';
    private $etherscan_domain;
    private $etherscan_domain_cn = 'http://api-cn.etherscan.com/api?';
    private $etherscan_domain_en = 'https://api.etherscan.io/api?';
    private $etherscan_key = 'yourapikey';

    /**
     * 创建 or 导入一个钱包
     * Wallet constructor.
     * @param string $prv_key 钱包私钥，如果不传将自动创建一个新钱包
     */
    public function __construct($prv_key = null)
    {
        if (!$prv_key) {
            $ec = new EC('secp256k1');
            $keyPair = $ec->genKeyPair();
        } else {
            $ec = new EC('secp256k1');
            $keyPair = $ec->keyFromPrivate($prv_key);
        }
        $this->prv_key = $keyPair->getPrivate()->toString(16, 2);
        $this->pub_key = $keyPair->getPublic()->encode('hex');
        $this->address = '0x' . substr(Keccak::hash(substr(hex2bin($this->pub_key), 1), 256), 24);
        $this->etherscan_domain = $this->etherscan_domain_en;
        return $this;
    }

    /**
     * 配置第三方参数
     * etherscan_domain etherescan 的接口域名
     * etherscan_key etherescan 的接口密钥
     * infura_endpoint infura的接入点
     * infura_secret infura的接入密钥
     * @param $config
     */
    public function config($config)
    {
        $this->etherscan_domain = isset($config['etherscan_domain']) ?
            $config['etherscan_domain'] : $this->etherscan_domain;
        $this->etherscan_key = isset($config['etherscan_key']) ?
            $config['etherscan_key'] : $this->etherscan_domain;
        $this->infura_endpoint = isset($config['infura_endpoint']) ?
            $config['infura_endpoint'] : $this->etherscan_domain;
        $this->infura_secret = isset($config['infura_secret']) ?
            $config['infura_secret'] : $this->etherscan_domain;
        return $this;
    }

    /**
     * etherscan 查询 ETH 余额
     * @param string $address 需要查询的地址
     * @return int ETH 余额 （单位wei)
     */
    private function balance_etherscan($address)
    {
        $url = $this->etherscan_domain
            . "module=account&action=balance&address=$address&tag=latest&apikey=$this->etherscan_key";
        $result = file_get_contents($url);
        $result = json_decode($result, true);
        return $result['result'] ?? 0;
    }

    /**
     * infura 查询 ETH 余额
     * @param string $address 需要查询的地址
     * @return int ETH 余额 （单位wei)
     */
    private function balance_infura($address)
    {
        $eth = new ETH(new HttpProvider(new HttpRequestManager($this->infura_endpoint, 10)));
        $eth->getBalance($address, $this);
        return $this->result->toString();
    }

    /**
     * etherscan 查询 ETH 余额
     * @param string $address 需要查询的地址
     * @param string $contract 代币合约地址
     * @return int 代币余额
     */
    private function balance_erc20_etherscan($address, $contract)
    {
        $url = $this->etherscan_domain
            . "module=account&action=tokenbalance&contractaddress=$contract&address=$address&tag=latest&apikey=$this->etherscan_key";
        $result = file_get_contents($url);
        $result = json_decode($result, true);
        return $result['result'] ?? 0;
    }

    public function balance($contact = null, $platform = 'etherscan')
    {
        if ($contact) {
            return $this->balance_erc20_etherscan($this->address, $contact);
        } else {
            return $platform == 'etherscan'
                ? $this->balance_etherscan($this->address)
                : $this->balance_infura($this->address);
        }
    }

    /**
     * 发送 ETH 方法
     * @param string $address 收款钱包地址
     * @param double $amount 转账的 ETH 数量。单位 ETH，精度小数点后18位
     * @param string $water_mark 交易备注
     * @return string 交易hash
     */
    private function send_eth($address, $amount, $water_mark = 'leftsky')
    {
        $amount = $amount * pow(10, 18);
        $address = str_replace("0x", "", $address);
        $web3 = new Web3(new HttpProvider(new HttpRequestManager($this->infura_endpoint, 10)));
        $web3->eth->getTransactionCount($this->address, 'latest', $this);
        $nonce = $this->result;
        $gasLimit = dechex(23000);
        $raw = [
            'nonce' => Utils::toHex($nonce, true),
            'gasPrice' => '0x' . Utils::toWei('20', 'gwei')->toHex(),
            'gasLimit' => '0x' . $gasLimit,
            'to' => hex2bin($address),
            'value' => Utils::toHex($amount, true),
            'data' => bin2hex($water_mark),
            'chainId' => 1
        ];
        $signed = $this->signTransaction($raw);

        $web3->eth->sendRawTransaction($signed, $this);
        return $this->result;
    }

    /**
     * 发送 ERC-20 代币方法
     * @param string $address 收款地址
     * @param double $amount 转账的金额
     * @param string $contact 代币地址
     * @param int $decimals 小数点位数
     * @param string $water_mark 备注
     * @return mixed
     */
    private function send_erc20($address, $amount, $contact, $decimals, $water_mark = 'leftsky')
    {
        // 将汇率转换成字符串
        $decimals_str = "";
        while (strlen($decimals_str) < $decimals) {
            $decimals_str .= "0";
        }

        // 处理收款地址字符串
        $to_address = str_replace("0x", "", $address);

        // 链接以太坊节点
        $client = new Client([
            'base_uri' => $this->infura_endpoint,
            'timeout' => 30,
        ]);

        // 导入钱包私钥
        $client->addPrivateKeys([$this->prv_key]);

        // 创建自己的交易
        $trans = [
            "from" => $this->address,
            "to" => $contact,
            "value" => "0x0",
        ];
        // 写入收款地址
        $trans['data'] = "0x" . "a9059cbb"
            . "000000000000000000000000$to_address";

        // 写入金额
        $num = Utils::toHex($amount . $decimals_str);
        while (strlen($num) != 64) {
            $num = "0" . $num;
        }
        $trans['data'] .= $num;

        // 设定你的手续费
        $gasPrice = $client->eth_gasPrice();
        $trans['gasPrice'] = $gasPrice;
        $gas = 3000000000000000 / hexdec($gasPrice);
        $trans['gas'] = dechex($gas);
        $trans['nonce'] = $client->eth_getTransactionCount($this->address, 'pending');
        $txid = $client->sendTransaction($trans);

        $receipt = $client->eth_getTransactionReceipt($txid);
        return $txid;
    }

    /**
     * 转账接口
     * @param string $address 收款地址
     * @param double $amount 金额
     * @param null $contact 合约地址，为 null 或 不填 时为 ETH
     * @param int $decimals 代币的小数点数
     * @param string $water_mark 交易备注
     * @return string
     */
    public function send($address, $amount, $contact = null, $decimals = 0, $water_mark = 'leftsky')
    {
        if (!$contact) {
            return $this->send_eth($address, $amount, $water_mark);
        } else {
            return $this->send_erc20($address, $amount, $contact, $decimals, $water_mark);
        }
    }

    public function signTransaction($raw)
    {
        $txreq = new Transaction($raw);
        $privateKey = $this->prv_key;
        $signed = '0x' . $txreq->sign($privateKey);
        return $signed;
    }

    function __invoke($error, $result)
    {
        if ($error) throw $error;
        $this->result = $result;
    }
}
