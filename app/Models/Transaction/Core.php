<?php

namespace RZP\Models\Transaction;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Reversal;
use RZP\Models\Currency;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payout;
use RZP\Models\Payment\Refund;
use RZP\Models\Pricing;
use RZP\Models\Terminal;
use RZP\Models\Transaction;
use RZP\Models\Adjustment;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Schedule\Library as Schedule;
use RZP\Trace\TraceCode;
use RZP\Models\Customer;
use RZP\Models\Transfer;

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
        $this->trace->info(
            TraceCode::PAYMENT_AUTHORIZE_CREATE_TRANSACTION,
            [
                'payment_id' => $payment->getId()
            ]);

        list($txn, $feesSplit) = $this->txnCreationFromPaymentOperation($payment);

        // $this->updateNodalBalance($txn);

        $this->repo->balance->updateBalance($this->merchantBalance);

        return [$txn, $feesSplit];
    }

    public function updateOnCapture(Payment\Entity $payment)
    {
        $txn = $this->repo->transaction->fetchByEntityAndAssociateMerchant($payment);

        $settledAt = $this->getSettledAtTimestamp($payment);

        $txn->setSettledAt($settledAt);

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

    /**
     * Update the corresponding transaction when
     * hold attributes of a Payment are updated
     *
     * @param  Payment\Entity       $payment
     * @return Transaction\Entity
     */
    public function updateOnHoldToggle(Payment\Entity $payment)
    {
        $txn = $payment->transaction;

        if ($txn->isSettled() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_UPDATE_ON_HOLD_ALREADY_SETTLED);
        }

        $settledAt = $this->getSettledAtTimestamp($payment);

        // $txn->setAttribute(Entity::SETTLED_AT, $settledAt);

        $txn->setAttribute(Entity::ON_HOLD, $payment->getOnHold());

        $this->trace->info(
            TraceCode::PAYMENT_HOLD_TOGGLE_UPDATE_TRANSACTION,
            [
                'payment_id'     => $payment->getId(),
                'transaction_id' => $txn->getId(),
                'on_hold'        => $payment->getOnHold(),
                'settled_at'     => $settledAt,
            ]
        );

        return $txn;
    }

    public function createFromPaymentCaptured(Payment\Entity $payment)
    {
        list($txn, $feesSplit) = $this->txnCreationFromPaymentOperation($payment);

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

        return [$txn, $feesSplit];
    }

    /**
     * Creates a Transaction record for a Payment transfer credit to account
     * Updates Marketplace balance
     *
     * @param  Payment\Entity $payment
     * @return array
     */
    public function createFromPaymentTransferred(Payment\Entity $payment) : array
    {
        list($txn, $feesSplit) = $this->txnCreationFromPaymentOperation($payment);

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_CREATE_TRANSACTION,
            [
                'type'          => 'linked_account_credit',
                'payment_id'     => $payment->getId(),
                'transaction_id' => $txn->getId()
            ]);

        $settledAt = $this->getSettledAtTimestamp($payment);

        $onHold = $payment->getOnHold() ?? false;

        $txn->setAttribute(Entity::SETTLED_AT, $settledAt);

        $txn->setAttribute(Entity::ON_HOLD, $onHold);

        $this->updateCredits($txn, $payment);

        $this->updateBalances($txn, false);

        return [$txn, $feesSplit];
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

    protected function txnCreationFromPaymentOperation(Payment\Entity $payment)
    {
        $txn = new Transaction\Entity;

        if ($payment->hasTransaction() === true)
        {
            $txn = $this->repo->transaction->fetchByEntityAndAssociateMerchant($payment);
        }
        else
        {
            $txn->generateId();
        }

        list($txn, $feesSplit) = $this->fillTxnFeesAndAmount($txn, $payment);

        $txnData = array(
            Transaction\Entity::TYPE            => Transaction\Type::PAYMENT,
            Transaction\Entity::CURRENCY        => Currency\Currency::INR,
            Transaction\Entity::CHANNEL         => Transaction\Channel::KOTAK);

        if ($payment->getGateway() === Payment\Gateway::ATOM)
        {
            $this->paymentOnAtomGateway($txnData, $payment, $txn->getFee());
        }

        $txn->fill($txnData);

        $txn->sourceAssociate($payment);
        $txn->merchant()->associate($payment->merchant);

        $this->trace->info(
            TraceCode::TRANSACTION_CREATED,
            [
                'payment_id' => $payment->getId(),
                'transaction_id' => $txn->getId(),
            ]);

        return [$txn, $feesSplit];
    }

    protected function fillTxnFeesAndAmount(Transaction\Entity $txn, Payment\Entity $payment)
    {
        $pricingRuleId = null;

        $merchant = $payment->merchant;

        $merchantBalance = $this->getBalanceLockForUpdate($merchant);

        $amount = $payment->getBaseAmount();

        $oldTransaction = $this->checkIfOldPayment($payment);

        $txn->setFeeModel($merchant->getFeeModel());

        $txn->setFeeBearer($merchant->getFeeBearer());

        $feesSplit = new Base\PublicCollection;

        if ($oldTransaction === true)
        {
            $pricingRuleId = (new Pricing\Fee)->getZeroPricingPlanRule($payment);

            $fee = 0;
            $serviceTax = 0;
            $credit = $amount;

            $txn->setPricingRule($pricingRuleId);
        }
        else if ($merchant->isPrepaid())
        {
            list($credit, $fee, $serviceTax, $feesSplit)
                = $this->calculatePrepaidFee($payment, $txn, $merchantBalance);
        }
        else
        {
            list($credit, $fee, $serviceTax, $feesSplit)
                = $this->calculatePostpaidFee($payment, $txn, $merchantBalance);
        }

        $txn->setAmount($amount);
        $txn->setCredit($credit);
        $txn->setDebit(0);
        $txn->setFee($fee);
        $txn->setServiceTax($serviceTax);

        return [$txn, $feesSplit];
    }

    /**
     * Calculate Fee for Prepaid Fee Model
     * Merchant can be a fee_bearer customer or platform
     *
     * @param  Payment\Entity          $payment
     * @param  Transaction\Entity      $transaction
     * @param  Merchant\Balance\Entity $merchantBalance
     * @return [type]
     */
    protected function calculatePrepaidFee(
        Payment\Entity $payment,
        Transaction\Entity $transaction,
        Merchant\Balance\Entity $merchantBalance)
    {
        $amountCredits = $merchantBalance->getAmountCredits();

        $feeCredits = $merchantBalance->getFeeCredits();

        list($fee, $serviceTax, $feesSplit) = $this->calculateMerchantFees($payment);

        switch (true)
        {
            case ($transaction->isFeeBearerCustomer()):
                return $this->calculateFeeForPrepaidDefault($payment, $transaction);

            case ($amountCredits > 0):
                return $this->calculateFeeForAmountCredit($payment, $transaction);

            case ($feeCredits >= $fee):
                return $this->calculateFeeForFeeCredit($payment, $transaction);

            default:
                return $this->calculateFeeForPrepaidDefault($payment, $transaction);
        }
    }

    /**
     * Calculate Fee for Postpaid Fee Model
     * Merchant can only be a fee_bearer platform
     *
     * @param  Payment\Entity          $payment
     * @param  Transaction\Entity      $transaction
     * @param  Merchant\Balance\Entity $merchantBalance
     * @return [type]
     */
    protected function calculatePostpaidFee(
        Payment\Entity $payment,
        Transaction\Entity $transaction,
        Merchant\Balance\Entity $merchantBalance)
    {
        $amountCredits = $merchantBalance->getAmountCredits();

        $feeCredits = $merchantBalance->getFeeCredits();

        list($fee, $serviceTax, $feesSplit) = $this->calculateMerchantFees($payment);

        switch (true)
        {
            case ($amountCredits > 0):
                return $this->calculateFeeForAmountCredit($payment, $transaction);

            case ($feeCredits >= $fee):
                return $this->calculateFeeForFeeCredit($payment, $transaction);

            default:
                return $this->calculateFeeForPostpaidDefault($payment, $transaction);
        }
    }

    /**
     * Calculate Fee for Amount Credit
     * Credit = amount, fee & ST = 0
     * @param  Payment\Entity          $payment         [description]
     * @param  Transaction\Entity      $transaction     [description]
     * @param  Merchant\Balance\Entity $merchantBalance [description]
     * @return [type]                                   [description]
     */
    protected function calculateFeeForAmountCredit(
        Payment\Entity $payment,
        Transaction\Entity $transaction)
    {
        $amount = $payment->getBaseAmount();

        $this->trace->info(
            TraceCode::TRANSACTION_AMOUNT_CREDITS,
            [
                'payment_id'     => $payment->getId(),
                'amount'         => $amount
            ]
        );

        $pricingRuleId = (new Pricing\Fee)->getZeroPricingPlanRule($payment);

        $transaction->setPricingRule($pricingRuleId);

        $credit = $amount;
        $fee = 0;
        $serviceTax = 0;

        $transaction->setGratis(true);

        $transaction->setCreditType(Transaction\CreditType::AMOUNT);

        return [$credit, $fee, $serviceTax, new Base\PublicCollection];
    }

    /**
     * Calculate Fee for Fee Credit
     * credit = amount, fee_credit = fee
     * @param  Payment\Entity     $payment     [description]
     * @param  Transaction\Entity $transaction [description]
     * @return [type]                          [description]
     */
    protected function calculateFeeForFeeCredit(
        Payment\Entity $payment,
        Transaction\Entity $transaction)
    {
        $amount = $payment->getBaseAmount();

        list($fee, $serviceTax, $feesSplit) = $this->calculateMerchantFees($payment);

        $credit = $amount;
        $feeCredits = $fee;

        $transaction->setFeeCredits($feeCredits);
        $transaction->setCreditType(Transaction\CreditType::FEE);

        return [$credit, $fee, $serviceTax, $feesSplit];
    }

    /**
     * Calculate Prepaid Fee for Default credit type
     * credit = amount - fee
     * @param  Payment\Entity     $payment     [description]
     * @param  Transaction\Entity $transaction [description]
     * @return [type]                          [description]
     */
    protected function calculateFeeForPrepaidDefault(
        Payment\Entity $payment,
        Transaction\Entity $transaction)
    {
        $amount = $payment->getBaseAmount();

        list($fee, $serviceTax, $feesSplit) = $this->calculateMerchantFees($payment);

        $credit = $amount - $fee;

        $transaction->setCreditType(Transaction\CreditType::DEFAULT);

        return [$credit, $fee, $serviceTax, $feesSplit];
    }

    /**
     * Calculate Postpaid Fee for Default credit type
     * credit = amount
     * @param  Payment\Entity     $payment     [description]
     * @param  Transaction\Entity $transaction [description]
     * @return [type]                          [description]
     */
    protected function calculateFeeForPostpaidDefault(
        Payment\Entity $payment,
        Transaction\Entity $transaction)
    {
        $amount = $payment->getBaseAmount();

        list($fee, $serviceTax, $feesSplit) = $this->calculateMerchantFees($payment);

        $credit = $amount;

        $transaction->setCreditType(Transaction\CreditType::DEFAULT);

        return [$credit, $fee, $serviceTax, $feesSplit];
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

        assert ($payment->hasTransaction() === true);

        $settledAt = 1;

        $txnData = array(
            Transaction\Entity::AMOUNT          => $refund->getBaseAmount(),
            Transaction\Entity::TYPE            => Transaction\Type::REFUND,
            Transaction\Entity::FEE             => 0,
            Transaction\Entity::SERVICE_TAX     => 0,
            Transaction\Entity::DEBIT           => $refund->getBaseAmount(),
            Transaction\Entity::CREDIT          => 0,
            Transaction\Entity::CURRENCY        => Currency\Currency::INR);

        $gateway = $refund->getGateway();

        if ($gateway === Payment\Gateway::ATOM)
        {
            $txnData[Transaction\Entity::RECONCILED_AT] = time();
        }

        $channel = $payment->transaction->getChannel();

        if ($payment->hasBeenCaptured())
        {
            $paymentTxn = $payment->transaction;

            if ($paymentTxn->isSettled() === true)
            {
                $txnData[Transaction\Entity::SETTLED_AT] = $settledAt;
            }
            else
            {
                $paymentSettledAt = $paymentTxn->getSettledAt();

                $txnData[Transaction\Entity::SETTLED_AT] = $paymentSettledAt;
            }
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
                //$this->updateNodalBalance($txn);

                break;
            case Payment\Status::CAPTURED:
                $this->updateBalances($txn);

                break;
            case Payment\Status::REFUNDED:
                $gateway = $payment->getGateway();

                Payment\Refund\Validator::validateVerifyRefundAllowed($gateway);

                //$this->updateNodalBalance($txn);

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
            Transaction\Entity::CURRENCY        => Currency\Currency::INR,
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

    /**
     * Record and associate a transaction for a payment transfer.
     *
     * @param  Transfer\Entity      $transfer Transfer entity
     * @return Transaction\Entity
     */
    public function createFromTransfer(Transfer\Entity $transfer)
    {
        $txn = new Transaction\Entity;

        $amount = $transfer->getAmount();

        $settledAt = time();

        $values = [
            Transaction\Entity::DEBIT         => $amount,
            Transaction\Entity::CREDIT        => 0,
            Transaction\Entity::CURRENCY      => $transfer->getCurrency(),
            Transaction\Entity::GATEWAY_FEE   => 0,
            Transaction\Entity::API_FEE       => 0,
            Transaction\Entity::RECONCILED_AT => time(),
            Transaction\Entity::SETTLED       => 0,
            Transaction\Entity::SETTLED_AT    => $settledAt,
            Transaction\Entity::FEE           => 0,
            Transaction\Entity::SERVICE_TAX   => 0,
            Transaction\Entity::AMOUNT        => $amount,
            Transaction\Entity::TYPE          => Transaction\Type::TRANSFER,
            Transaction\Entity::CHANNEL       => Transaction\Channel::KOTAK,
        ];

        $txn->fillAndGenerateId($values);

        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_CREATE_TRANSACTION,
            [
                'type'           => 'merchant_debit',
                'transaction_id' => $txn->getId()
            ]);

        $txn->merchant()->associate($transfer->merchant);

        $txn->sourceAssociate($transfer);

        $this->updateBalances($txn, false);

        return $txn;
    }

    /**
     * Create transaction and update balances for a reverse transfer
     * on a Marketplace payment refund
     *
     * @param  Reversal\Entity   $reversal
     * @return Entity
     */
    public function createFromReversal($reversal)
    {
        $txn = new Transaction\Entity;

        $amount = $reversal->getAmount();

        $nowTimestamp = time();

        $data = [
            Transaction\Entity::DEBIT         => 0,
            Transaction\Entity::CREDIT        => $amount,
            Transaction\Entity::CURRENCY      => Currency\Currency::INR,
            Transaction\Entity::GATEWAY_FEE   => 0,
            Transaction\Entity::API_FEE       => 0,
            Transaction\Entity::RECONCILED_AT => $nowTimestamp,
            Transaction\Entity::SETTLED       => 0,
            Transaction\Entity::SETTLED_AT    => $nowTimestamp,
            Transaction\Entity::FEE           => 0,
            Transaction\Entity::SERVICE_TAX   => 0,
            Transaction\Entity::AMOUNT        => $amount,
            Transaction\Entity::TYPE          => Transaction\Type::REVERSAL,
            Transaction\Entity::CHANNEL       => Transaction\Channel::KOTAK,
        ];

        $txn->fillAndGenerateId($data);

        $txn->merchant()->associate($reversal->merchant);

        $txn->sourceAssociate($reversal);

        $this->updateBalances($txn, false);

        return $txn;
    }

    public function createFromPayout(Payout\Entity $payout)
    {
        $txn = new Transaction\Entity;

        $amount = $payout->getAmount();

        list($fee, $serviceTax, $feesSplit) =
            (new Pricing\Fee)->calculateMerchantFees($payout, false);

        $settledAt = time();

        $payoutAmount = abs($amount + $fee);

        $values = array(
            Transaction\Entity::DEBIT               => $payoutAmount,
            Transaction\Entity::CREDIT              => 0,
            Transaction\Entity::CURRENCY            => 'INR',
            Transaction\Entity::GATEWAY_FEE         => 0,
            Transaction\Entity::GATEWAY_SERVICE_TAX => 0,
            Transaction\Entity::API_FEE             => $fee,
            Transaction\Entity::RECONCILED_AT       => time(),
            Transaction\Entity::SETTLED             => 0,
            Transaction\Entity::SETTLED_AT          => $settledAt,
            Transaction\Entity::FEE                 => $fee,
            Transaction\Entity::SERVICE_TAX         => $serviceTax,
            Transaction\Entity::AMOUNT              => $payoutAmount,
            Transaction\Entity::TYPE                => Transaction\Type::PAYOUT,
            Transaction\Entity::CHANNEL             => Transaction\Channel::KOTAK,
        );

        $txn->fillAndGenerateId($values);

        $txn->merchant()->associate($payout->merchant);

        $txn->sourceAssociate($payout);

        return $txn;
    }

    protected function calculateMerchantFees(Payment\Entity $payment)
    {
        return (new Pricing\Fee)->calculateMerchantFees($payment);
    }

    public function updateBalances(Transaction\Entity $txn, $updateNodalBalance = true)
    {
        $txn = $this->updateMerchantBalance($txn);

        // if ($updateNodalBalance === true)
        // {
        //     $txn = $this->updateNodalBalance($txn);
        // }
        // else
        // {
        //     $nodalBalance = $this->repo->balance->getNodalBalance($txn->getChannel());
        //
        //     $txn->setEscrowBalance($nodalBalance->getBalance());
        // }

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

    // public function updateNodalBalance(Transaction\Entity $txn)
    // {
    //     $channel = $txn->getChannel();

    //     $nodalBalance = $this->getNodalBalanceLockForUpdate($channel);

    //     $nodalBalance->updateBalance($txn);
    //     $this->repo->balance->updateBalance($nodalBalance);

    //     $txn->setEscrowBalance($nodalBalance->getBalance());

    //     return $txn;
    // }

    public function updateAmountCredits(Transaction\Entity $txn, Payment\Entity $payment)
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

        // Removing Assert for now, as there is a race condition. if 2 payments
        // are authorized at the same time where we create txn on auth with. both
        // will try to set amount credits to zero and one will throw below assert
        // as both txn were marked as gratis on authorization
        // assert($amountCredits > 0);

        //
        // Even if free credits is less than txn amount, we still give full
        // amount as free credits. However, in balance we only go ahead with
        // updating the actual free credits so that it does not go negative.
        //
        if ($amountCredits < $amount)
        {
            $amount = $amountCredits;
        }

        // $nodalBalance = $this->getNodalBalanceLockForUpdate($txn->getChannel());

        // $nodalBalance->subtractAmountCredits($amount);

        $merchantBalance->subtractAmountCredits($amount);

        // Nodal balance needs to be saved because of amount credit update
        // $this->repo->balance->updateBalance($nodalBalance);
    }

    public function updateFeeCredits(Transaction\Entity $txn)
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

        // $nodalBalance = $this->getNodalBalanceLockForUpdate($txn->getChannel());

        // $nodalBalance->subtractFeeCredits($fee);

        $merchantBalance->subtractFeeCredits($fee);

        // // Nodal balance needs to be saved because of amount credit update
        // $this->repo->balance->updateBalance($nodalBalance);
    }

    protected function getNodalBalanceLockForUpdate($channel)
    {
        // if ($this->nodalBalance !== null)
        // {
        //     return $this->nodalBalance;
        // }

        // $nodalBalance = $this->repo->balance->getNodalBalanceLockForUpdate($channel);

        // $this->nodalBalance = $nodalBalance;

        // return $nodalBalance;
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

        // Implements delayed settlements, commented temporarily
        // $onHoldUntilTime = $payment->getOnHoldUntil();

        // return max($returnTime, $onHoldUntilTime);

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
            $this->updateAmountCredits($txn, $payment);
        }
        else if ($txn->getFeeCredits() > 0)
        {
            $this->updateFeeCredits($txn);
        }
    }

    public function createTransactionForAuthAndCapture(Payment\Entity $payment)
    {
        $txn = new Transaction\Entity;

        $txn->generateId();

        $amount = $payment->getBaseAmount();

        $txnData = [
            Transaction\Entity::TYPE     => Transaction\Type::PAYMENT,
            Transaction\Entity::CURRENCY => Currency\Currency::INR,
            Transaction\Entity::CHANNEL  => Transaction\Channel::KOTAK,
            Transaction\Entity::AMOUNT   => $amount,
        ];

        $txn->fill($txnData);

        $txn->sourceAssociate($payment);

        $txn->merchant()->associate($payment->merchant);

        $this->repo->saveOrFail($txn);

        $this->trace->info(
            TraceCode::TRANSACTION_CREATED_FOR_AUTH_CAPTURE,
            [
                'payment_id'     => $payment->getId(),
                'transaction_id' => $txn->getId(),
            ]);
    }
}
