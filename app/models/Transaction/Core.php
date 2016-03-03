<?php

namespace Models\Transaction;

use Carbon\Carbon;
use EE\Exception;
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
    protected $merchantBalance = null;

    protected $nodalBalance = null;

    public function __construct()
    {
        $this->merchant = \BasicAuth::getMerchant();
        $this->merchantRepo = new Merchant\Repository;
        $this->balanceRepo = new Merchant\Balance\Repository;
    }

    public function createFromPaymentAuthorized(Payment\Entity $payment)
    {
        $txn = new Transaction\Entity;
        $txn->generateId();

        $this->fillTxnFeesAndAmount($txn, $payment);

        $this->txnCreationFromPaymentOperation($txn, $payment);

        $this->updateFreeCredits($txn);

        $this->updateEscrowBalance($txn);

        $this->balanceRepo->updateBalance($this->merchantBalance);

        return $txn;
    }

    public function updateOnCapture(Payment\Entity $payment)
    {
        $txn = $payment->transaction;

        $settledAt = $this->getSettledAtTimestamp($payment);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $this->updateMerchantBalance($txn);

        return $txn;
    }

    public function createFromPaymentCaptured(Payment\Entity $payment)
    {
        $txn = new Transaction\Entity;
        $txn->generateId();

        $this->fillTxnFeesAndAmount($txn, $payment);

        $this->txnCreationFromPaymentOperation($txn, $payment);

        $settledAt = $this->getSettledAtTimestamp($payment);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $this->updateFreeCredits($txn);

        $this->updateBalances($txn);

        return $txn;
    }

    protected function txnCreationFromPaymentOperation($txn, $payment)
    {
        $txnData = array(
            Transaction\Entity::TYPE            => Transaction\Type::PAYMENT,
            Transaction\Entity::CURRENCY        => 'INR',
            Transaction\Entity::CHANNEL         => Transaction\Channel::KOTAK);

        if ($payment->getGateway() === Payment\Gateway::ATOM)
        {
            $this->paymentOnAtomGateway($txnData, $payment, $txn->getFee());
        }

        $txn->fill($txnData);

        $txn->entity()->associate($payment);
        $txn->merchant()->associate($payment->merchant);
        $payment->transaction()->associate($txn);
    }

    protected function fillTxnFeesAndAmount($txn, $payment)
    {
        $credit = $fee = $serviceTax = 0;
        $pricingRuleId = null;

        $merchantBalance = $this->getBalanceLockForUpdate($payment->merchant);

        $freeCredits = $merchantBalance->getCredits();

        $amount = $payment->getAmount();

        if ($freeCredits > 0)
        {
            $pricingRuleId = (new Pricing\Fee)->getZeroPricingPlanRule($payment);

            $credit = $amount;
            $fee = 0;
            $serviceTax = 0;

            $txn->setGratis(true);
        }
        else
        {
            list($fee, $serviceTax, $pricingRuleId) = $this->calculateMerchantFees($payment);
            $credit = $amount - $fee;
        }

        //If the merchant is tdrClient
        //use the fees and service tax from both
        if ($txn->merchant()->isTdrClient())
        {
            $fee        = $payment->getFee();
            $serviceTax = $payment->getServiceTax();
            $credit     = $amount + $fee; // To adjust changes from previous
        }

        $txn->setPricingRule($pricingRuleId);
        $txn->setAmount($amount);
        $txn->setCredit($credit);
        $txn->setDebit(0);
        $txn->setFee($fee);
        $txn->setServiceTax($serviceTax);

        return $txn;
    }

    public function fillServiceTax($txn, $payment)
    {
        if ($txn->isGratis())
        {
            $txn->setServiceTax(0);
        }
        else
        {
            $serviceTax = (new Pricing\Fee)->calculateServiceTax($txn, $payment);

            $txn->setServiceTax($serviceTax);
        }
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

        $settledAt = 1;

        $txnData = array(
            Transaction\Entity::AMOUNT          => $refund->getAmount(),
            Transaction\Entity::TYPE            => Transaction\Type::REFUND,
            Transaction\Entity::FEE             => 0,
            Transaction\Entity::SERVICE_TAX     => 0,
            Transaction\Entity::DEBIT           => $refund->getAmount(),
            Transaction\Entity::CREDIT          => 0,
            Transaction\Entity::CURRENCY        => 'INR');

        $gateway = $refund->getGateway();

        if ($gateway === Payment\Gateway::ATOM)
        {
            $txnData[Transaction\Entity::RECONCILED_AT] = time();
        }

        $channel = $payment->transaction->getChannel();

        if ($payment->hasBeenCaptured())
        {
            $txnData[Transaction\Entity::SETTLED_AT] = $settledAt;
        }

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
            throw new Exception\LogicException('Should not have reached here');
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
            Transaction\Entity::SERVICE_TAX     => 0,
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
        $txn = $this->updateMerchantBalance($txn);

        if ($updateEscrowBalance === true)
        {
            $txn = $this->updateEscrowBalance($txn);
        }
        else
        {
            $nodalBalance = $this->getEscrowBalanceLockForUpdate($txn->getChannel());

            $txn->setEscrowBalance($nodalBalance->getBalance());
        }

        return $txn;
    }

    public function updateMerchantBalance(Transaction\Entity $txn)
    {
        $merchantBalance = $this->getBalanceLockForUpdate($txn->merchant);

        $merchantBalance->updateBalance($txn);
        $this->balanceRepo->updateBalance($merchantBalance);

        $txn->setBalance($merchantBalance->getBalance());

        return $txn;
    }

    public function updateEscrowBalance(Transaction\Entity $txn)
    {
        $channel = $txn->getChannel();

        $nodalBalance = $this->getEscrowBalanceLockForUpdate($channel);

        $nodalBalance->updateBalance($txn);
        $this->balanceRepo->updateBalance($nodalBalance);

        $txn->setEscrowBalance($nodalBalance->getBalance());

        return $txn;
    }

    public function updateFreeCredits($txn)
    {
        assert ($txn->isTypePayment() === true);

        if (($txn->getFee() !== 0) or
            ($txn->getCredit() !== $txn->getAmount()))
        {
            return;
        }

        $credits = $txn->getAmount();

        $merchantBalance = $this->getBalanceLockForUpdate($txn->merchant);

        $freeCredits = $merchantBalance->getCredits();

        assert($freeCredits > 0);

        if ($freeCredits < $credits)
        {
            $credits = $freeCredits;
        }

        $nodalBalance = $this->getEscrowBalanceLockForUpdate($txn->getChannel());

        $nodalBalance->subtractCredits($credits);

        $merchantBalance->subtractCredits($credits);
    }

    protected function getEscrowBalanceLockForUpdate($channel)
    {
        if ($this->nodalBalance !== null)
        {
            return $this->nodalBalance;
        }

        $nodalBalance = $this->balanceRepo->getEscrowBalanceLockForUpdate($channel);

        $this->nodalBalance = $nodalBalance;

        return $nodalBalance;
    }

    protected function getBalanceLockForUpdate(Merchant\Entity $merchant)
    {
        if ($this->merchantBalance !== null)
        {
            return $this->merchantBalance;
        }

        $merchantBalance = $this->balanceRepo->getBalanceLockForUpdate($merchant->getId());

        $this->merchantBalance = $merchantBalance;

        return $merchantBalance;
    }

    protected function getSettledAtTimestamp($payment)
    {
        $capturedAt = $payment->getAttribute(Payment\Entity::CAPTURED_AT);

        $addDays = $payment->merchant->getSettlementSchedule();

        return $this->calculateSettledAtTimestamp($capturedAt, $addDays);
    }

    public function calculateSettledAtTimestamp($timestamp, $addDays)
    {
        assert ($addDays >= 1);

        $timestamp = Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata');

        $addDays = $this->getActualNumberOfDaysToAdd($timestamp, $addDays);

        $settledAt = $timestamp->startOfDay()
                                ->addDays($addDays)
                                ->timestamp;

        return $settledAt;
    }

    protected function getSettlementSchedule($payment)
    {
        return $payment->merchant->getSettlementSchedule();
    }

    protected function getActualNumberOfDaysToAdd($timestamp, $addDays)
    {
        $currentDay = $timestamp->dayOfWeek;

        $daysToSettle = $currentDay + $addDays;

        if (($daysToSettle % Carbon::DAYS_PER_WEEK === Carbon::SATURDAY) or
            ($daysToSettle % Carbon::DAYS_PER_WEEK === Carbon::SUNDAY) or
             ($daysToSettle > Carbon::DAYS_PER_WEEK))
        {
            // Adding a two day weekend
            $addDays += 2;
        }

        // transaction was on saturday
        // we added two day weekend
        // remove one day for that
        if ($currentDay === Carbon::SATURDAY)
        {
            $addDays -= 1;
        }

        return $addDays;
    }
}
