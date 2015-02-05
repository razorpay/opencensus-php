<?php

namespace Models\Transaction;

use Carbon\Carbon;
use Models\Base;
use Models\Card;
use Models\Merchant;
use Models\Payment;
use Models\Payment\Refund;
use Models\Pricing;
use Models\Transaction;
use Models\Adjustment;

class Core extends Base\Core
{
    protected $entities = array();

    protected $record;

    public function __construct()
    {
        $this->merchant = \BasicAuth::getMerchant();
        $this->merchantRepo = new Merchant\Repository;
    }

    public function createFromPayment(Payment\Entity $payment)
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
            Transaction\Entity::AMOUNT      => $payment->getAmount(),
            Transaction\Entity::TYPE        => Transaction\Type::PAYMENT,
            Transaction\Entity::FEE         => $fee,
            Transaction\Entity::CREDIT      => $credit,
            Transaction\Entity::DEBIT       => 0,
            Transaction\Entity::CURRENCY    => 'INR',
            Transaction\Entity::SETTLED_AT  => $settledAt,
            Transaction\Entity::PRICING_RULE_ID => $pricingRuleId);

        $txn = new Transaction\Entity($txnData);
        $txn->generateId();

        $txn->entity()->associate($payment);
        $txn->merchant()->associate($payment->merchant);
        $payment->transaction()->associate($txn);

        $this->updateBalances($txn);

        return $txn;
    }

    public function createFromRefund(Refund\Entity $refund)
    {
        $settledAt = Carbon::today('Asia/Kolkata')
                           ->addDays(2)
                           ->timestamp;

        $txnData = array(
            Transaction\Entity::AMOUNT      => $refund->getAmount(),
            Transaction\Entity::TYPE        => Transaction\Type::REFUND,
            Transaction\Entity::FEE         => 0,
            Transaction\Entity::DEBIT       => $refund->getAmount(),
            Transaction\Entity::CREDIT      => 0,
            Transaction\Entity::CURRENCY    => 'INR',
            Transaction\Entity::SETTLED_AT  => $settledAt);

        $txn = new Transaction\Entity($txnData);
        $txn->generateId();

        $txn->entity()->associate($refund);
        $txn->merchant()->associate($refund->merchant);
        $refund->transaction()->associate($txn);

        $this->updateBalances($txn);

        return $txn;
    }

    public function createFromAdjustment(Adjustment\Entity $adj)
    {
        $txn = new Transaction\Entity;

        $amount = $adj->getAmount();

        $debit = $credit = 0;

        if ($amount > 0)
            $credit = $amount;

        if ($amount < 0)
            $debit = -1 * $amount;

        $settledAt = Carbon::tomorrow('Asia/Kolkata')->timestamp;

        $values = array(
            Transaction\Entity::DEBIT           => $debit,
            Transaction\Entity::CREDIT          => $credit,
            Transaction\Entity::CURRENCY        => 'INR',
            Transaction\Entity::GATEWAY_FEE     => 0,
            Transaction\Entity::API_FEE         => 0,
            Transaction\Entity::RECONCILED_AT   => time(),
            Transaction\Entity::SETTLED         => 0,
            Transaction\Entity::SETTLED_AT      => $settledAt,
            Transaction\Entity::FEE             => 0,
            Transaction\Entity::AMOUNT          => abs($amount),
            Transaction\Entity::TYPE            => Transaction\Type::ADJUSTMENT,
        );

        $txn->fillAndGenerateId($values);

        $txn->merchant()->associate($adj->merchant);

        $txn->entity()->associate($adj);

        $adj->transaction()->associate($txn);

        $this->updateBalances($txn);

        return $txn;
    }

    protected function calculateMerchantFees(Payment\Entity $payment)
    {
        return (new Pricing\Fee)->calculateMerchantFees($payment);
    }

    public function updateBalances(Transaction\Entity $txn)
    {
        $channel = $txn->getChannel();

        $nodalBalance = $this->merchantRepo->getEscrowBalanceLockForUpdate($channel);

        $merchantBalance = $this->merchantRepo->getBalanceLockForUpdate(
                                                    $txn->merchant->getKey());

        $merchantBalance->updateBalance($txn);
        $nodalBalance->updateBalance($txn);

        $this->merchantRepo->updateBalance($nodalBalance);
        $this->merchantRepo->updateBalance($merchantBalance);

        $attributes = array(
            Transaction\Entity::BALANCE => $merchantBalance->getBalance(),
            Transaction\Entity::ESCROW_BALANCE => $nodalBalance->getBalance());

        $txn->fill($attributes);

        return $txn;
    }
}