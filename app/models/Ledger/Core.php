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
        $this->merchant = \Models\Service\BasicAuth::getInstance()->getMerchant();
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

        // $txn->setLedgerId($ledger->getKey());
    }

    public static function updateRecords($txn)
    {
        $merchantId = $txn->merchant_id;

        $ledger = null;

        \DB::transaction( function() use ($txn, &$ledger)
        {
            $merchantId = $txn->merchant_id;

            $merchant = Merchant::find($merchantId);

            $fee = $txn->amount * 3 / 100;

            $merchant->amount += ($txn->amount - $fee);

            $data = array(
                'ref'           => $txn->id,
                self::MERCHANT_ID   => $merchant->id,
//                self::ACTION        => Transaction\Action::CAPTURE,
                self::FEE           => $fee,
                self::BALANCE       => $merchant->amount);

            $ledger = static::createOrFail($data);
        });

        return $ledger;
    }
}