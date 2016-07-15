<?php

namespace RZP\Models\Transaction;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund;
use RZP\Models\Pricing;
use RZP\Models\Terminal;
use RZP\Models\Transaction;
use RZP\Models\Adjustment;
use RZP\Models\Settlement\Holidays;

class Core extends Base\Core
{
    // July 1st, 2016 00:00:00 IST
    const JULY_FIRST_EPOCH = '1467311400';

    protected $merchantBalance = null;

    protected $nodalBalance = null;

    protected $merchant;

    protected $merchantRepo;

    protected $balanceRepo;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = \BasicAuth::getMerchant();
        $this->merchantRepo = $this->repo->merchant;
        $this->balanceRepo = $this->repo->balance;
    }

    public function createFromPaymentAuthorized(Payment\Entity $payment)
    {
        $txn = $this->txnCreationFromPaymentOperation($payment);

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
        $txn = $this->txnCreationFromPaymentOperation($payment);

        $settledAt = $this->getSettledAtTimestamp($payment);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $this->updateFreeCredits($txn);

        $this->updateBalances($txn);

        return $txn;
    }

    protected function txnCreationFromPaymentOperation($payment)
    {
        $txn = new Transaction\Entity;
        $txn->generateId();

        $this->fillTxnFeesAndAmount($txn, $payment);

        $txnData = array(
            Transaction\Entity::TYPE            => Transaction\Type::PAYMENT,
            Transaction\Entity::CURRENCY        => 'INR',
            Transaction\Entity::CHANNEL         => Transaction\Channel::KOTAK);

        if ($payment->getGateway() === Payment\Gateway::ATOM)
        {
            $this->paymentOnAtomGateway($txnData, $payment, $txn->getFee());
        }

        $txn->fill($txnData);

        $txn->sourceAssociate($payment);
        $txn->merchant()->associate($payment->merchant);

        return $txn;
    }

    protected function fillTxnFeesAndAmount($txn, $payment)
    {
        $pricingRuleId = null;

        $merchantBalance = $this->getBalanceLockForUpdate($payment->merchant);

        $freeCredits = $merchantBalance->getCredits();

        $amount = $payment->getAmount();

        $oldTransaction = $this->checkIfOldTransaction($payment);

        if ($oldTransaction === true)
        {
            $pricingRuleId = (new Pricing\Fee)->getZeroPricingPlanRule($payment);
            $fee = 0;
            $serviceTax = 0;
            $credit = $amount;
        }
        else if ($freeCredits > 0)
        {
            $pricingRuleId = (new Pricing\Fee)->getZeroPricingPlanRule($payment);

            $credit = $amount;
            $fee = 0;
            $serviceTax = 0;

            $txn->setGratis(true);
        }
        //If the merchant is tdrClient
        //use the fees and service tax from both
        else if (isset($this->merchant) and ($this->merchant->isFeeBearerCustomer()))
        {
            $fee            = $payment->getFee();
            $serviceTax     = (new Pricing\Fee)->calculateServiceTaxFromFees($fee);
            $credit         = $amount - $fee;
        }
        else
        {
            list($fee, $serviceTax, $pricingRuleId) = $this->calculateMerchantFees($payment);
            $credit = $amount - $fee;
        }



        $txn->setPricingRule($pricingRuleId);
        $txn->setAmount($amount);
        $txn->setCredit($credit);
        $txn->setDebit(0);
        $txn->setFee($fee);
        $txn->setServiceTax($serviceTax);

        return $txn;
    }

    protected function checkIfOldTransaction($payment)
    {
        if (($payment->getCreatedTimestamp() < self::JULY_FIRST_EPOCH) and
            ($payment->transaction === null) and
            ($payment->isAuthorized() === true))
        {
            return true;
        }

        return false;
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

        $txn->sourceAssociate($refund);
        $txn->merchant()->associate($refund->merchant);

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
            // Exceptional case where we have to perform a refund.
            // that is acceptable.
            if (($payment->getGateway() === Payment\Gateway::HDFC) and
                ($payment->getStatus() === Payment\Status::REFUNDED))
            {
                ;
            }
            else
            {
                throw new Exception\LogicException('Should not have reached here');
            }
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

        $txn->sourceAssociate($adj);

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

    public function calculateSettledAtTimestamp($timestamp, $addDays, $ignoreBankHolidays = false)
    {
        $capturedAt = Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata');

        $returnDay = Holidays::getNthWorkingDayFrom($capturedAt, $addDays, $ignoreBankHolidays);

        return $returnDay->timestamp;
    }

    protected function getSettlementSchedule($payment)
    {
        return $payment->merchant->getSettlementSchedule();
    }
}
