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
use RZP\Models\Schedule\Library as Schedule;
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

    public function createFromPaymentAuthorized(Payment\Entity $payment, $feesSplit)
    {
        $txn = $this->txnCreationFromPaymentOperation($payment, $feesSplit);

        $this->updateNodalBalance($txn);

        $this->repo->balance->updateBalance($this->merchantBalance);

        return $txn;
    }

    public function updateOnCapture(Payment\Entity $payment)
    {
        $txn = $payment->transaction;

        $settledAt = $this->getSettledAtTimestamp($payment);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $this->updateCredits($txn, $payment);

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

    public function createFromPaymentCaptured(Payment\Entity $payment, $feesSplit)
    {
        $txn = $this->txnCreationFromPaymentOperation($payment, $feesSplit);

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_CREATE_TRANSACTION,
            [
                'payment_id'     => $payment->getId(),
                'transaction_id' => $txn->getId()
            ]);

        $settledAt = $this->getSettledAtTimestamp($payment);

        $txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);

        $this->updateCredits($txn, $payment);

        $this->updateBalances($txn);

        return $txn;
    }

    public function updateReconciliationData(Entity $transaction)
    {
        $reconciled = $transaction->isReconciled();

        if ($reconciled === true)
        {
            return false;
        }

        $transaction->setReconciledAt(time());
        $transaction->setGatewayFee(0);
        $transaction->setGatewayServiceTax(0);

        $this->repo->saveOrFail($transaction);

        return true;
    }

    protected function txnCreationFromPaymentOperation($payment, $feesSplit)
    {
        $txn = new Transaction\Entity;
        $txn->generateId();

        $txn = $this->fillTxnFeesAndAmount($txn, $payment, $feesSplit);

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

    protected function fillTxnFeesAndAmount($txn, $payment, $feesSplit)
    {
        $pricingRuleId = null;

        $merchantBalance = $this->getBalanceLockForUpdate($payment->merchant);

        $amountCredits = $merchantBalance->getAmountCredits();

        $feeCredits = $merchantBalance->getFeeCredits();

        $amount = $payment->getAmount();

        $oldTransaction = $this->checkIfOldPayment($payment);

        if ($oldTransaction === true)
        {
            $pricingRuleId = (new Pricing\Fee)->getZeroPricingPlanRule($payment);

            $fee = 0;
            $serviceTax = 0;
            $credit = $amount;
        }
        else if ($amountCredits > 0)
        {
            $this->trace->info(
                TraceCode::TRANSACTION_FREE_CREDITS,
                [
                    'payment_id' => $payment->getId(),
                    'amount' => $amount,
                    'free_credits' => $amountCredits,
                ]
            );
            $pricingRuleId = (new Pricing\Fee)->getZeroPricingPlanRule($payment);

            $credit = $amount;
            $fee = 0;
            $serviceTax = 0;

            $txn->setGratis(true);
        }
        // If the customer is fee bearer for the merchant
        // use the fees and service tax from both
        else if (isset($this->merchant) and ($this->merchant->isFeeBearerCustomer()))
        {
            $fee                              = $payment->getFee();
            $serviceTax                       = (new Pricing\Fee)->calculateServiceTaxFromFees($payment, $fee, $feesSplit);
            $credit                           = $amount - $fee;
        }
        else
        {
            list($fee, $serviceTax, $pricingRuleId) = $this->calculateMerchantFees($payment, $feesSplit);

            if ($feeCredits >= $fee)
            {
                $credit         = $amount;
                $feeCredits     = $fee;

                $txn->setFeeCredits($feeCredits);
            }
            else
            {
                $credit = $amount - $fee;
            }
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
        if (($payment->getCreatedAt() < self::JULY_FIRST_EPOCH) and
            ($payment->transaction === null) and
            ($payment->isAuthorized() === true))
        {
            $this->trace->info(
                TraceCode::PAYMENT_TRANSACTION_OLD,
                [
                    'payment_id' => $payment->getId(),
                    'payment_created' => Carbon::createFromTimestamp($payment->getCreatedAt())
                                               ->toDateTimeString()
                ]
            );

            return true;
        }

        return false;
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

        assert ($payment->transaction !== null);

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
                // This is a rare case and is here just to fix bugs.
                assert ($payment->getGateway() === Payment\Gateway::HDFC);

                $this->updateNodalBalance($txn);

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

    protected function calculateMerchantFees(Payment\Entity $payment, $feesSplit)
    {
        return (new Pricing\Fee)->calculateMerchantFees($payment, $feesSplit);
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
            $nodalBalance = $this->repo->balance->getNodalBalance($txn->getChannel());

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

    public function updateAmountCredits($txn, $payment)
    {
        assert ($txn->isTypePayment() === true);

        // For transactions being created before july 1st, 2016, we assign the zero pricing plan.
        // These transactions are not using the free credits.
        if (($txn->getFee() !== 0) or
            ($txn->getCredit() !== $txn->getAmount()) or
            ($payment->getCreatedAt() < self::JULY_FIRST_EPOCH))
        {
            return;
        }

        // While filling the txn fees and amount, we have not used amount credits.
        if ($txn->isGratis() === false)
        {
            return;
        }

        $amount = $txn->getAmount();

        $merchantBalance = $this->getBalanceLockForUpdate($txn->merchant);

        $amountCredits = $merchantBalance->getAmountCredits();

        assert($amountCredits > 0);

        //
        // Even if free credits is less than txn amount, we still give full
        // amount as free credits. However, in balance we only go ahead with
        // updating the actual free credits so that it does not go negative.
        //
        if ($amountCredits < $amount)
        {
            $amount = $amountCredits;
        }

        $nodalBalance = $this->getNodalBalanceLockForUpdate($txn->getChannel());

        $nodalBalance->subtractAmountCredits($amount);

        $merchantBalance->subtractAmountCredits($amount);

        // Nodal balance needs to be saved because of amount credit update
        $this->repo->balance->updateBalance($nodalBalance);
    }

    public function updateFeeCredits(Transaction\Entity $txn, Payment\Entity $payment)
    {
        assert ($txn->isTypePayment() === true);

        // While filling the txn fees and amount, we have not used fee credits.
        if ($txn->getFeeCredits() === 0)
        {
            return;
        }

        $fee = $txn->getFee();

        $merchantBalance = $this->getBalanceLockForUpdate($txn->merchant);

        $feeCredits = $merchantBalance->getFeeCredits();

        if ($feeCredits < $fee)
        {
            throw new Exception\LogicException("FeeCredits should be higher or equal to the fee");
        }

        $nodalBalance = $this->getNodalBalanceLockForUpdate($txn->getChannel());

        $nodalBalance->subtractFeeCredits($fee);

        $merchantBalance->subtractFeeCredits($fee);

        // Nodal balance needs to be saved because of amount credit update
        $this->repo->balance->updateBalance($nodalBalance);
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

        $merchant = $payment->merchant;

        $returnTime = null;

        if ($merchant->getSettlementScheduleId() === null)
        {
            $addDays = $merchant->getSettlementSchedule();

            $returnTime = $this->calculateSettledAtTimestamp($capturedAt, $addDays);
        }
        else
        {
            $returnTime = Schedule::getNextApplicableTime($capturedAt, $merchant->schedule);
        }

        return $returnTime;
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

    public function updateCredits(Transaction\Entity $txn, Payment\Entity $payment)
    {
        if ($txn->isGratis() === true)
        {
            return $this->updateAmountCredits($txn, $payment);
        }
        else if ($txn->getFeeCredits() > 0)
        {
            return $this->updateFeeCredits($txn, $payment);
        }
    }
}
