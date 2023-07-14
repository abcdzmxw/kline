<?php


namespace App\Services\CoinService\Interfaces;


interface CoinServiceInterface
{
    // 获取账户余额
    public function getBalance($account);

    // 列出所有账户
    public function listAccounts();

    // 获取交易信息
    public function getTransaction($transactionId);

    // 创建账户
    public function newAccount();
}
