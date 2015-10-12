<?php

namespace Models\Transaction;

use Carbon\Carbon;
use Models\Base;
use Models\Card;
use Models\Merchant;
use Models\Payment;
use Models\Payment\Refund;
use Models\Pricing;
use Models\Terminal;
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

    public function createFromPaymentAuthorized(Payment\Entity $payment)
    {
        list($fee, $pricingRuleId) = $this->calculateMerchantFees($payment);

        $amount = $payment->getAmount();
        $credit = $amount - $fee;

        $txnData = array(
            Transaction\Entity::AMOUNT          => $amount,
            Transaction\Entity::TYPE            => Transaction\Type::PAYMENT,
            Transaction\Entity::FEE             => $fee,
            Transaction\Entity::CREDIT          => $credit,
            Transaction\Entity::DEBIT           => 0,
            Transaction\Entity::CURRENCY        => 'INR',
            Transaction\Entity::CHANNEL         => Transaction\Channel::KOTAK,
            Transaction\Entity::PRICING_RULE_ID => $pricingRuleId);

        if ($payment->getGateway() === Payment\Gateway::ATOM)
        {
            $this->paymentOnAtomGateway($txnData, $payment, $fee);
        }

        $txn = new Transaction\Entity($txnData);
        $txn->generateId();

        $txn->entity()->associate($payment);
        $txn->merchant()->associate($payment->merchant);
        $payment->transaction()->associate($txn);

        $this->updateEscrowBalance($txn);

        return $txn;
    }

    public function updateOnCapture(Payment\Entity $payment)
    {
        $txn = $payment->transaction;

        $capturedAt = $payment->getAttribute(Payment\Entity::CAPTURED_AT);
        $settledAt = $this->getSettledAtTimestamp($capturedAt, 3);

        $txnData = array(Transaction\Entity::SETTLED_AT => $settledAt);
        $txn->fill($txnData);

        $this->updateMerchantBalance($txn);

        return $txn;
    }

    public function createFromPaymentCaptured(Payment\Entity $payment)
    {
        list($fee, $pricingRuleId) = $this->calculateMerchantFees($payment);

        $capturedAt = $payment->getAttribute(Payment\Entity::CAPTURED_AT);
        $settledAt = $this->getSettledAtTimestamp($capturedAt, 3);

        $amount = $payment->getAmount();
        $credit = $amount - $fee;

        $txnData = array(
            Transaction\Entity::AMOUNT          => $amount,
            Transaction\Entity::TYPE            => Transaction\Type::PAYMENT,
            Transaction\Entity::FEE             => $fee,
            Transaction\Entity::CREDIT          => $credit,
            Transaction\Entity::DEBIT           => 0,
            Transaction\Entity::CURRENCY        => 'INR',
            Transaction\Entity::SETTLED_AT      => $settledAt,
            Transaction\Entity::CHANNEL         => Transaction\Channel::KOTAK,
            Transaction\Entity::PRICING_RULE_ID => $pricingRuleId);

        if ($payment->getGateway() === Payment\Gateway::ATOM)
        {
            $this->paymentOnAtomGateway($txnData, $payment, $fee);
        }

        $txn = new Transaction\Entity($txnData);
        $txn->generateId();

        $txn->entity()->associate($payment);
        $txn->merchant()->associate($payment->merchant);
        $payment->transaction()->associate($txn);

        $this->updateBalances($txn);

        return $txn;
    }

    protected function paymentOnAtomGateway(array & $txnData, $payment, $fee)
    {
        $txnData[Transaction\Entity::RECONCILED_AT] = time();
        $txnData[Transaction\Entity::GATEWAY_FEE] = $fee;
        $txnData[Transaction\Entity::API_FEE] = 0;

        $channel = Transaction\Channel::ATOM;

        if (Terminal\Shared::isPaymentOnSharedTerminal($payment))
        {
            $channel = Transaction\Channel::KOTAK;

            $gatewayFee = (new Pricing\Fee)->getGatewayFeeForAtomSharedTerminal($payment);
            $txnData[Transaction\Entity::GATEWAY_FEE] = $gatewayFee;
            $txnData[Transaction\Entity::API_FEE] = $fee - $gatewayFee;
        }

        $txnData[Transaction\Entity::CHANNEL] = $channel;
    }

    public function createFromRefund(Refund\Entity $refund)
    {
        $payment = $refund->payment;

        $createdAt = $refund->getAttribute(Refund\Entity::CREATED_AT);

        $settledAt = $createdAt + 1;

        $txnData = array(
            Transaction\Entity::AMOUNT      => $refund->getAmount(),
            Transaction\Entity::TYPE        => Transaction\Type::REFUND,
            Transaction\Entity::FEE         => 0,
            Transaction\Entity::DEBIT       => $refund->getAmount(),
            Transaction\Entity::CREDIT      => 0,
            Transaction\Entity::CURRENCY    => 'INR');

        $gateway = $refund->getGateway();

        if ($gateway === Payment\Gateway::ATOM)
        {
            $txnData[Transaction\Entity::RECONCILED_AT] = time();
        }

        $channel = $payment->transaction->getChannel();

        $txnData[Transaction\Entity::SETTLED_AT] = $settledAt;
        $txnData[Transaction\Entity::CHANNEL] = $channel;

        $txn = new Transaction\Entity($txnData);
        $txn->generateId();

        $txn->entity()->associate($refund);
        $txn->merchant()->associate($refund->merchant);
        $refund->transaction()->associate($txn);

        if ($payment->isAuthorized())
        {
            // When refunding authorized payments, we do not charge merchants
            $this->updateEscrowBalance($txn);
        }
        else if ($payment->isCaptured())
        {
            $this->updateBalances($txn);
        }
        else
        {
            ; // throw exception
        }

        return $txn;
    }

    public function createFromAdjustment(Adjustment\Entity $adj, $updateEscrow = true)
    {
        $txn = new Transaction\Entity;

        $amount = $adj->getAmount();

        $debit = $credit = 0;

        if ($amount > 0)
            $credit = $amount;

        if ($amount < 0)
            $debit = -1 * $amount;

        $settledAt = time();

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
            Transaction\Entity::CHANNEL         => Transaction\Channel::KOTAK,
        );

        $txn->fillAndGenerateId($values);

        $txn->merchant()->associate($adj->merchant);

        $txn->entity()->associate($adj);

        $adj->transaction()->associate($txn);

        $this->updateBalances($txn, $updateEscrow);

        return $txn;
    }

    protected function calculateMerchantFees(Payment\Entity $payment)
    {
        return (new Pricing\Fee)->calculateMerchantFees($payment);
    }

    public function updateBalances(Transaction\Entity $txn, $updateEscrowBalance = true)
    {
        $channel = $txn->getChannel();

        $nodalBalance = $this->merchantRepo->getEscrowBalanceLockForUpdate($channel);

        $merchantBalance = $this->merchantRepo->getBalanceLockForUpdate(
                                                    $txn->merchant->getKey());

        $merchantBalance->updateBalance($txn);
        $this->merchantRepo->updateBalance($merchantBalance);

        if ($updateEscrowBalance === true)
        {
            $nodalBalance->updateBalance($txn);
            $this->merchantRepo->updateBalance($nodalBalance);
        }

        $attributes = array(
            Transaction\Entity::BALANCE => $merchantBalance->getBalance(),
            Transaction\Entity::ESCROW_BALANCE => $nodalBalance->getBalance());

        $txn->fill($attributes);

        return $txn;
    }

    public function updateMerchantBalance(Transaction\Entity $txn)
    {
        $merchantBalance = $this->merchantRepo->getBalanceLockForUpdate(
                                                    $txn->merchant->getKey());

        $merchantBalance->updateBalance($txn);
        $this->merchantRepo->updateBalance($merchantBalance);

        $attributes = array(
            Transaction\Entity::BALANCE => $merchantBalance->getBalance(),
        );

        $txn->fill($attributes);

        return $txn;
    }

    public function updateEscrowBalance(Transaction\Entity $txn)
    {
        $channel = $txn->getChannel();

        $nodalBalance = $this->merchantRepo->getEscrowBalanceLockForUpdate($channel);

        $nodalBalance->updateBalance($txn);
        $this->merchantRepo->updateBalance($nodalBalance);

        $attributes = array(
            Transaction\Entity::ESCROW_BALANCE => $nodalBalance->getBalance());

        $txn->fill($attributes);

        return $txn;
    }

    public function getSettledAtTimestamp($timestamp, $addDays)
    {
        $timestamp = Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata');
        $day = (int) $timestamp->format('w');

        // if payment is on Sunday, add 1 extra
        if ($day === 0)
            $addDays += 1;

        $day = $day + $addDays;

        if ($day >= 6)
        {
            $addDays += 2;
        }

        $settledAt = $timestamp->startOfDay()
                                ->addDays($addDays)
                                ->timestamp;

        return $settledAt;
    }
}