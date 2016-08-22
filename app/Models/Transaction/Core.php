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
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    // July 1st, 2016 00:00:00 IST
    const JULY_FIRST_EPOCH = '1467311400';

    protected $merchantBalance = null;

    protected $nodalBalance = null;

    protected $merchant;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    public function createFromPaymentAuthorized(Payment\Entity $payment)
    {
        $txn = $this->txnCreationFromPaymentOperation($payment);

        $this->updateFreeCredits($txn, $payment);

        $this->updateNodalBalance($txn);

        $this->repo->balance->updateBalance($this->merchantBalance);

        return $txn;
    }

    public function updateOnCapture(Payment\Entity $payment)
    {
        $txn = $payment->transaction;

        $settledAt = $this->getSettledAtTimestamp($payment);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $this->updateMerchantBalance($txn);

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_UPDATE_TRANSACTION,
            [
                'payment_id'     => $payment->getId(),
                'transaction_id' => $txn->getId(),
            ]
        );

        return $txn;
    }

    public function createFromPaymentCaptured(Payment\Entity $payment)
    {
        $txn = $this->txnCreationFromPaymentOperation($payment);

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_CREATE_TRANSACTION,
            [
                'payment_id'     => $payment->getId(),
                'transaction_id' => $txn->getId()
            ]);

        $settledAt = $this->getSettledAtTimestamp($payment);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $this->updateFreeCredits($txn, $payment);

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

        $oldTransaction = $this->checkIfOldPayment($payment);

        if ($oldTransaction === true)
        {
            $pricingRuleId = (new Pricing\Fee)->getZeroPricingPlanRule($payment);

            $fee = 0;
            $serviceTax = 0;
            $credit = $amount;
        }
        else if ($freeCredits > 0)
        {
            $this->trace->info(
                TraceCode::TRANSACTION_FREE_CREDITS,
                [
                    'payment_id' => $payment->getId(),
                    'amount' => $amount,
                    'free_credits' => $freeCredits,
                ]
            );
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

    protected function checkIfOldPayment($payment)
    {
        if (($payment->getCreatedTimestamp() < self::JULY_FIRST_EPOCH) and
            ($payment->transaction === null) and
            ($payment->isAuthorized() === true))
        {
            $this->trace->info(
                TraceCode::PAYMENT_TRANSACTION_OLD,
                [
                    'payment_id' => $payment->getId(),
                    'payment_created' => Carbon::createFromTimestamp($payment->getCreatedTimestamp())
                                               ->toDateTimeString()
                ]
            );

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
        
        $paymentStatus = $payment->getStatus();
        
        switch($paymentStatus)
        {
            case Payment\Status::AUTHORIZED:
                // When refunding authorized payments, we do not charge merchants
                $this->updateNodalBalance($txn);
                break;
            case Payment\Status::CAPTURED:
                $this->updateBalances($txn);
                break;
            case Payment\Status::REFUNDED:
                // Exceptional case where we have to perform a refund. That is acceptable.
                assert($payment->getGateway() === Payment\Gateway::HDFC);
                break;
            default:
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

        $txn->sourceAssociate($adj);

        $adj->transaction()->associate($txn);

        $this->updateBalances($txn, $updateEscrow);

        return $txn;
    }

    protected function calculateMerchantFees(Payment\Entity $payment)
    {
        return (new Pricing\Fee)->calculateMerchantFees($payment);
    }

    public function updateBalances(Transaction\Entity $txn, $updateNodalBalance = true)
    {
        $txn = $this->updateMerchantBalance($txn);

        if ($updateNodalBalance === true)
        {
            $txn = $this->updateNodalBalance($txn);
        }
        else
        {
            $nodalBalance = $this->getNodalBalanceLockForUpdate($txn->getChannel());

            $txn->setEscrowBalance($nodalBalance->getBalance());
        }

        return $txn;
    }

    public function updateMerchantBalance(Transaction\Entity $txn)
    {
        $merchantBalance = $this->getBalanceLockForUpdate($txn->merchant);

        $merchantBalance->updateBalance($txn);
        $this->repo->balance->updateBalance($merchantBalance);

        $txn->setBalance($merchantBalance->getBalance());

        return $txn;
    }

    public function updateNodalBalance(Transaction\Entity $txn)
    {
        $channel = $txn->getChannel();

        $nodalBalance = $this->getNodalBalanceLockForUpdate($channel);

        $nodalBalance->updateBalance($txn);
        $this->repo->balance->updateBalance($nodalBalance);

        $txn->setEscrowBalance($nodalBalance->getBalance());

        return $txn;
    }

    public function updateFreeCredits($txn, $payment)
    {
        assert ($txn->isTypePayment() === true);

        // For transactions being created before july 1st, 2016, we assign the zero pricing plan.
        // These transactions are not using the free credits.
        if (($txn->getFee() !== 0) or
            ($txn->getCredit() !== $txn->getAmount()) or
            ($payment->getCreatedTimestamp() < self::JULY_FIRST_EPOCH))
        {
            return;
        }

        $amount = $txn->getAmount();

        $merchantBalance = $this->getBalanceLockForUpdate($txn->merchant);

        $freeCredits = $merchantBalance->getCredits();

        assert($freeCredits > 0);

        if ($freeCredits < $amount)
        {
            $amount = $freeCredits;
        }

        $nodalBalance = $this->getNodalBalanceLockForUpdate($txn->getChannel());

        $nodalBalance->subtractCredits($amount);

        $merchantBalance->subtractCredits($amount);
    }

    protected function getNodalBalanceLockForUpdate($channel)
    {
        if ($this->nodalBalance !== null)
        {
            return $this->nodalBalance;
        }

        $nodalBalance = $this->repo->balance->getNodalBalanceLockForUpdate($channel);

        $this->nodalBalance = $nodalBalance;

        return $nodalBalance;
    }

    protected function getBalanceLockForUpdate(Merchant\Entity $merchant)
    {
        if ($this->merchantBalance !== null)
        {
            return $this->merchantBalance;
        }

        $merchantBalance = $this->repo->balance->getBalanceLockForUpdate($merchant->getId());

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
