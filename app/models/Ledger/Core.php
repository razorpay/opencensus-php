<?php

namespace Models\Ledger;

use Models\Base;
use Models\Ledger;
use Models\Merchant;
use Models\Transaction;

class Core extends Base\UniqueIdEntity
{
    protected $merchant = null;

    public function __construct()
    {
        $this->merchant = \BasicAuth::getMerchant();
    }

    public function recordCapture(Transaction\Entity $txn)
    {
        $merchantId = $txn->getMerchantId();

        $balance = (new Merchant\Repository)->findBalanceLockForUpdate($merchantId);

        $amount = $txn->getAmount();

        $fee = 0;

        $credit = $amount - $fee;

        $balance->addAmount($credit);

        $balanceAmount = $balance->getBalance();

        $attr = array(
            Ledger\Entity::ENTITY_ID => $txn->getKey(),
            Ledger\Entity::ENTITY_TYPE => 'transaction',
            Ledger\Entity::MERCHANT_ID => $merchantId,
            Ledger\Entity::AMOUNT => $amount,
            Ledger\Entity::CREDIT => $credit,
            Ledger\Entity::FEE => $fee,
            Ledger\Entity::BALANCE => $balanceAmount);

        $ledger = (new Ledger\Repository)->createOrFail($attr);

        $balance->saveOrFail();

        $txn->ledger()->associate($ledger);
    }

    public function recordRefund(Transaction\Entity $txn)
    {
        $merchantId = $txn->getMerchantId();

        $balance = (new Merchant\Repository)->findBalanceLockForUpdate($merchantId);

        $amount = $txn->getAmount();

        $fee = 0;

        $debit = $amount;

        $balance->subtractAmount($debit);

        $balanceAmount = $balance->getBalance();

        $attr = array(
            Ledger\Entity::ENTITY_ID => $txn->getKey(),
            Ledger\Entity::ENTITY_TYPE => 'refund',
            Ledger\Entity::MERCHANT_ID => $merchantId,
            Ledger\Entity::AMOUNT => $amount,
            Ledger\Entity::DEBIT => $debit,
            Ledger\Entity::FEE => $fee,
            Ledger\Entity::BALANCE => $balanceAmount);

        $ledger = (new Ledger\Repository)->createOrFail($attr);

        $balance->saveOrFail();

        // $txn->setLedgerId($ledger->getKey());
    }
}