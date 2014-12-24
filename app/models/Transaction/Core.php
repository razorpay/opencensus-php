<?php

namespace Models\Transaction;

use Carbon\Carbon;
use Models\Base;
use Models\Card;
use Models\Transaction;
use Models\Merchant;
use Models\Pricing;
use Models\Payment;

class Core extends Base\Core
{
    protected $entities = array();

    protected $record;

    public function __construct()
    {
        $this->merchant = \BasicAuth::getMerchant();
    }

    public function createFromPayment($payment)
    {
        list($fee, $pricingRuleId) = $this->calculateMerchantFees($payment);

        //
        // Gets settled_at timestamp from captured_at which is T+2
        //
        $capturedAt = $payment->getAttribute(Payment\Entity::CAPTURED_AT);
        $settledAt = Carbon::createFromTimestamp($capturedAt, 'Asia/Kolkata')
                           ->startOfDay()
                           ->addDays(2)
                           ->timestamp;

        $credit = $payment->getAmount() - $fee;

        $txnData = array(
            Transaction\Entity::AMOUNT => $payment->getAmount(),
            Transaction\Entity::MERCHANT_ID => $payment->merchant->getKey(),
            Transaction\Entity::ENTITY_ID => $payment->getKey(),
            Transaction\Entity::TYPE => Transaction\Type::PAYMENT,
            Transaction\Entity::FEE => $fee,
            Transaction\Entity::CREDIT => $credit,
            Transaction\Entity::DEBIT => 0,
            Transaction\Entity::CURRENCY => 'INR',
            Transaction\Entity::PRICING_RULE_ID => $pricingRuleId,
            Transaction\Entity::SETTLED_AT => $settledAt);

        $txn = new Transaction\Entity($txnData);
        $txn->generateId();

        $txn->entity()->associate($payment);
        $payment->transaction()->associate($txn);

        return $txn;
    }

    public function createFromRefund($refund)
    {
        $settledAt = Carbon::today('Asia/Kolkata')
                           ->addDays(2)
                           ->timestamp;

        $txnData = array(
            Transaction\Entity::AMOUNT => $refund->getAmount(),
            Transaction\Entity::MERCHANT_ID => $refund->merchant->getKey(),
            Transaction\Entity::ENTITY_ID => $refund->getKey(),
            Transaction\Entity::TYPE => Transaction\Type::REFUND,
            Transaction\Entity::FEE => 0,
            Transaction\Entity::DEBIT => $refund->getAmount(),
            Transaction\Entity::CREDIT => 0,
            Transaction\Entity::CURRENCY => 'INR',
            Transaction\Entity::SETTLED_AT => $settledAt);

        $txn = new Transaction\Entity($txnData);
        $txn->generateId();

        $txn->entity()->associate($refund);
        $refund->transaction()->associate($txn);

        return $txn;
    }

    protected function calculateMerchantFees($payment)
    {
        return (new Pricing\Fee)->calculateMerchantFees(
                    $payment->merchant,
                    $payment->card,
                    $payment->getAmount());
    }

}

