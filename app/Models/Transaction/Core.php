<?php

namespace RZP\Models\Transaction;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Dispute;
use RZP\Models\Reversal;
use RZP\Models\Currency;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payout;
use RZP\Models\Payment\Refund;
use RZP\Models\Pricing;
use RZP\Models\Transaction;
use RZP\Models\Adjustment;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Schedule\Library as ScheduleLibrary;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer;
use RZP\Models\Feature;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant\FeeModel;
use RZP\Constants\Entity as E;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

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

    /**
     * This will be called only in case of Non Auth Capture Flow
     * We will create a dummy transaction with no fee split.
     * The actual fee split will be calculated at the time of payment capture
     * @param  Payment\Entity $payment
     * @return array [Transaction\Entity $txn, PublicCollection $feesSplit]
     */
    public function createFromPaymentAuthorized(Payment\Entity $payment)
    {
        $this->trace->info(
            TraceCode::PAYMENT_AUTHORIZE_CREATE_TRANSACTION,
            [
                'payment_id' => $payment->getId()
            ]);

        list($txn, $feesSplit) = $this->txnCreationFromPaymentOperation($payment, false);

        return [$txn, $feesSplit];
    }

    /**
     * Update the corresponding transaction when
     * hold attributes of a Payment are updated
     *
     * @param  Payment\Entity $payment
     *
     * @return Transaction\Entity
     * @throws Exception\BadRequestException
     */
    public function updateOnHoldToggle(Payment\Entity $payment)
    {
        $txn = $payment->transaction;

        if ($txn->isSettled() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_UPDATE_ON_HOLD_ALREADY_SETTLED);
        }

        $txn->setOnHold($payment->getOnHold());

        $this->trace->info(
            TraceCode::PAYMENT_HOLD_TOGGLE_UPDATE_TRANSACTION,
            [
                'payment_id'     => $payment->getId(),
                'transaction_id' => $txn->getId(),
                'on_hold'        => $payment->getOnHold()
            ]
        );

        return $txn;
    }

    public function createOrUpdateFromPaymentCaptured(Payment\Entity $payment)
    {
        list($txn, $feesSplit) = $this->txnCreationFromPaymentOperation($payment);

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_CREATE_TRANSACTION,
            [
                'payment_id'     => $payment->getId(),
                'transaction_id' => $txn->getId()
            ]);

        $this->repo->saveOrFail($txn);

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

    public function markGratisTransactionPostpaid(Entity $txn, Merchant\Entity $merchant)
    {
        $this->repo->transaction(function() use ($txn, $merchant)
        {
            $payment = $txn->source;

            $merchantBalance = $this->repo->balance->getMerchantBalance($merchant);

            $this->merchantBalance = $merchantBalance;

            $feesSplit = new Base\PublicCollection;

            list($credit, $fee, $serviceTax, $feesSplit) = $this->calculatePostpaidFee($payment, $txn, $merchantBalance);

            $txn->setCredit($credit);
            $txn->setDebit(0);
            $txn->setFee($fee);
            $txn->setServiceTax($serviceTax);
            $txn->setFeeModel(FeeModel::POSTPAID);
            $txn->setGratis(false);
            $txn->setCreditType(Transaction\CreditType::DEFAULT);
            $txn->setPricingRule(null);

            $payment->setServiceTax($serviceTax);

            if ($merchant->isFeeBearerCustomer() === false)
            {
                //set and fee values from txn
                $payment->setFee($fee);
            }

            $this->repo->saveOrFail($payment);

            $this->repo->saveOrFail($txn);

            (new PaymentProcessor($merchant))->saveFeeDetails($txn, $feesSplit);
        });
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

    protected function txnCreationFromPaymentOperation(Payment\Entity $payment, bool $updateFees = true)
    {
        $txn = new Transaction\Entity;

        // Case 1: Non AuthCapture flow, a txn already exists with min data.
        // Case 2: Auth-capture flow, we create a txn and associate merchant, payment with it.
        if ($payment->hasTransaction() === true)
        {
            $txn = $this->repo->transaction->fetchByEntityAndAssociateMerchant($payment);
        }
        else
        {
            $txn->generateId();

            $txn->sourceAssociate($payment);

            $txn->merchant()->associate($payment->merchant);
        }

        list($txn, $feesSplit) = $this->fillTxnFeesAndAmount($txn, $payment, $updateFees);

        $txnData = [
            Transaction\Entity::TYPE            => Transaction\Type::PAYMENT,
            Transaction\Entity::CURRENCY        => Currency\Currency::INR,
            Transaction\Entity::CHANNEL         => Transaction\Channel::KOTAK
        ];

        if ($payment->getGateway() === Payment\Gateway::ATOM)
        {
            $this->paymentOnAtomGateway($txnData, $payment, $txn->getFee());
        }

        $txn->fill($txnData);

        $this->trace->info(
            TraceCode::TRANSACTION_CREATED,
            [
                'payment_id'     => $payment->getId(),
                'transaction_id' => $txn->getId(),
            ]);

        return [$txn, $feesSplit];
    }

    protected function fillEmptyTxnFeesAndAmount(Transaction\Entity $txn, Payment\Entity $payment)
    {
        $amount = $payment->getBaseAmount();

        $values = [
            Transaction\Entity::DEBIT               => 0,
            Transaction\Entity::CREDIT              => 0,
            Transaction\Entity::FEE                 => 0,
            Transaction\Entity::TAX                 => 0,
            Transaction\Entity::AMOUNT              => $amount,
        ];

        $txn->fill($values);

        return [$txn, new Base\PublicCollection];
    }

    protected function fillTxnFeesAndAmount(Transaction\Entity $txn, Payment\Entity $payment, bool $updateFees = true)
    {
        if ($updateFees === false)
        {
            return $this->fillEmptyTxnFeesAndAmount($txn, $payment);
        }

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
            $pricingRuleId = (new Pricing\Fee)->getZeroPricingPlanRule($payment)->getId();

            $fee = 0;
            $tax = 0;
            $credit = $amount;

            $txn->setPricingRule($pricingRuleId);
        }
        else if ($merchant->isPrepaid())
        {
            list($credit, $fee, $tax, $feesSplit)
                = $this->calculatePrepaidFee($payment, $txn, $merchantBalance);
        }
        else
        {
            list($credit, $fee, $tax, $feesSplit)
                = $this->calculatePostpaidFee($payment, $txn, $merchantBalance);
        }

        $txn->setAmount($amount);
        $txn->setCredit($credit);
        $txn->setDebit(0);
        $txn->setFee($fee);
        $txn->setTax($tax);

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
        list($amountCredits, $feeCredits) = $this->getMerchantCredits($merchantBalance);

        list($fee, $tax, $feesSplit) = $this->calculateMerchantFees($payment);

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
        list($amountCredits, $feeCredits) = $this->getMerchantCredits($merchantBalance);

        list($fee, $tax, $feesSplit) = $this->calculateMerchantFees($payment);

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

        $pricingRuleId = (new Pricing\Fee)->getZeroPricingPlanRule($payment)->getId();

        $transaction->setPricingRule($pricingRuleId);

        $credit = $amount;
        $fee = 0;
        $tax = 0;

        $transaction->setGratis(true);

        $transaction->setCreditType(Transaction\CreditType::AMOUNT);

        return [$credit, $fee, $tax, new Base\PublicCollection];
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

        list($fee, $tax, $feesSplit) = $this->calculateMerchantFees($payment);

        $credit = $amount;
        $feeCredits = $fee;

        $transaction->setFeeCredits($feeCredits);
        $transaction->setCreditType(Transaction\CreditType::FEE);

        return [$credit, $fee, $tax, $feesSplit];
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

        list($fee, $tax, $feesSplit) = $this->calculateMerchantFees($payment);

        $credit = $amount - $fee;

        $transaction->setCreditType(Transaction\CreditType::DEFAULT);

        return [$credit, $fee, $tax, $feesSplit];
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

        list($fee, $tax, $feesSplit) = $this->calculateMerchantFees($payment);

        $credit = $amount;

        $transaction->setCreditType(Transaction\CreditType::DEFAULT);

        return [$credit, $fee, $tax, $feesSplit];
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
                    'payment_id'      => $payment->getId(),
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

        if ($payment->terminal->isShared() === true)
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
            Transaction\Entity::TAX             => 0,
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

                Payment\Refund\Validator::validateVerifyInternalRefundAllowed($gateway);

                //$this->updateNodalBalance($txn);

                break;
            default:
                throw new Exception\LogicException(
                    'Should not have reached here',
                    null,
                    [
                        'refund_id'         => $refund->getId(),
                        'payment_id'        => $payment->getId(),
                        'status'            => $paymentStatus,
                        'transaction_id'    => $txn->getId(),
                    ]);
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
            Transaction\Entity::TAX             => 0,
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

        list($fee, $tax, $feesSplit) =
            (new Pricing\Fee)->calculateMerchantFees($transfer);

        $settledAt = time();

        //
        // For transfers from a payment, if the source payment is not
        // settled yet, delay the settled_at timestamp to avoid this txn
        // from being picked up for settlement immediately.
        //
        // Without this, the transfer txn would get picked up for settlement
        // before the payment txn, leading to a overall negative settlement
        // that is then skipped.
        //
        if ($transfer->getSourceType() === E::PAYMENT)
        {
            $paymentTxn = $transfer->source->transaction;

            if ($paymentTxn->isSettled() === false)
            {
                $settledAt = $paymentTxn->getSettledAt();
            }
        }

        $amountPlusFees = abs($amount + $fee);

        $values = [
            Transaction\Entity::DEBIT         => $amountPlusFees,
            Transaction\Entity::CREDIT        => 0,
            Transaction\Entity::CURRENCY      => $transfer->getCurrency(),
            Transaction\Entity::GATEWAY_FEE   => 0,
            Transaction\Entity::API_FEE       => $fee,
            Transaction\Entity::RECONCILED_AT => time(),
            Transaction\Entity::SETTLED       => 0,
            Transaction\Entity::SETTLED_AT    => $settledAt,
            Transaction\Entity::FEE           => $fee,
            Transaction\Entity::TAX           => $tax,
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
     * Create transaction and update balances for a reversal
     * with entity=`transfer`
     *
     * @param  Reversal\Entity   $reversal
     * @return Entity
     */
    public function createFromTransferReversal(Reversal\Entity $reversal)
    {
        $txn = new Transaction\Entity;

        $amount = $reversal->getAmount();

        // Compute the `settled_at` timestamp
        $settleTimestamp = $this->getTransferReversalSettledAtTimestamp($reversal);

        $data = [
            Transaction\Entity::DEBIT         => 0,
            Transaction\Entity::CREDIT        => $amount,
            Transaction\Entity::CURRENCY      => Currency\Currency::INR,
            Transaction\Entity::GATEWAY_FEE   => 0,
            Transaction\Entity::API_FEE       => 0,
            Transaction\Entity::RECONCILED_AT => $settleTimestamp,
            Transaction\Entity::SETTLED       => 0,
            Transaction\Entity::SETTLED_AT    => $settleTimestamp,
            Transaction\Entity::FEE           => 0,
            Transaction\Entity::TAX           => 0,
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

    public function createFromDispute(Dispute\Entity $dispute): Entity
    {
        $txn = new Entity;

        $nowTimestamp = Carbon::now()->getTimestamp();

        $data = [
            Entity::DEBIT         => $dispute->getAmountDeducted(),
            Entity::CREDIT        => 0,
            Entity::CURRENCY      => $dispute->getCurrency(),
            Entity::GATEWAY_FEE   => 0,
            Entity::API_FEE       => 0,
            Entity::SETTLED       => 0,
            Entity::SETTLED_AT    => $nowTimestamp,
            Entity::FEE           => 0,
            Entity::TAX           => 0,
            Entity::AMOUNT        => $dispute->getAmountDeducted(),
            Entity::TYPE          => Type::DISPUTE,
            Entity::CHANNEL       => Channel::KOTAK,
        ];

        $txn->fillAndGenerateId($data);

        $txn->merchant()->associate($dispute->merchant);

        $txn->sourceAssociate($dispute);

        $this->updateBalances($txn);

        return $txn;
    }

    public function createFromPayout(Payout\Entity $payout)
    {
        $txn = new Transaction\Entity;

        $amount = $payout->getAmount();

        list($fee, $tax, $feesSplit) =
            (new Pricing\Fee)->calculateMerchantFees($payout, false);

        $settledAt = time();

        $payoutAmount = abs($amount + $fee);

        $values = [
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
            Transaction\Entity::TAX                 => $tax,
            Transaction\Entity::AMOUNT              => $payoutAmount,
            Transaction\Entity::TYPE                => Transaction\Type::PAYOUT,
            Transaction\Entity::CHANNEL             => Transaction\Channel::KOTAK,
        ];

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

        $amountCredits = $this->getMerchantCreditsOfType($merchantBalance, Credits\Type::AMOUNT);

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

        //create a credit transaction for the same
        $this->createCreditTransaction($amount, $txn, Credits\Type::AMOUNT);

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

        $merchantId = $merchantBalance->merchant->getId();

        $feeCredits = $this->getMerchantCreditsOfType($merchantBalance, Credits\Type::FEE);

        if ($feeCredits < $fee)
        {
            throw new Exception\LogicException(
                'FeeCredits should be higher or equal to the fee',
                null,
                [
                    'transaction_id'    => $txn->getId(),
                    'merchant_id'       => $merchantId,
                    'fee_credits'       => $feeCredits,
                    'fee'               => $fee,
                ]);
        }

        // $nodalBalance = $this->getNodalBalanceLockForUpdate($txn->getChannel());

        // $nodalBalance->subtractFeeCredits($fee);

        $merchantBalance->subtractFeeCredits($fee);

        //create a credit transaction for the same
        $this->createCreditTransaction($fee, $txn, Credits\Type::FEE);

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

        $scheduleTask = (new ScheduleTask\Core)->getMerchantSettlementSchedule($merchant, $payment->getMethod());

        // use schedule from pivot schedule_task if defined and use next run from there
        if ($scheduleTask !== null)
        {
            $schedule = $scheduleTask->schedule;

            $nextRunAt = $scheduleTask->getNextRunAt();

            $returnTime = ScheduleLibrary::getNextApplicableTime($capturedAt, $schedule, $nextRunAt);
        }
        else
        {
            $addDays = $merchant->getSettlementSchedule();

            $returnTime = $this->calculateSettledAtTimestamp($capturedAt, $addDays);
        }

        return $returnTime;
    }

    public function calculateSettledAtTimestamp($timestamp, $addDays, $ignoreBankHolidays = false)
    {
        $capturedAt = Carbon::createFromTimestamp($timestamp, Timezone::IST);

        $returnDay = Holidays::getNthWorkingDayFrom($capturedAt, $addDays, $ignoreBankHolidays);

        return $returnDay->getTimestamp();
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

    protected function getMerchantCreditsOfType(Merchant\Balance\Entity $merchantBalance, string $type)
    {
        $merchant = $merchantBalance->merchant;

        $feature = Feature\Constants::OLD_CREDITS_FLOW;

        if ($merchant->isFeatureEnabled($feature) === true)
        {
            if ($type === Credits\Type::FEE)
            {
                $credits = $merchantBalance->getFeeCredits();
            }
            else
            {
                $credits = $merchantBalance->getAmountCredits();
            }
        }
        else
        {
            $merchantId = $merchant->getId();

            $credits = $this->repo->credits->getMerchantCreditsOfType($merchantId, $type);
        }

        return $credits;
    }

    protected function getMerchantCredits(Merchant\Balance\Entity $merchantBalance): array
    {
        $merchant = $merchantBalance->merchant;

        $feature = Feature\Constants::OLD_CREDITS_FLOW;

        if ($merchant->isFeatureEnabled($feature) === true)
        {
            $amountCredits = $merchantBalance->getAmountCredits();

            $feeCredits = $merchantBalance->getFeeCredits();
        }
        else
        {
            $merchantId = $merchantBalance->merchant->getId();

            $credits = $this->repo->credits->getTypeAggregatedMerchantCredits($merchantId);

            $amountCredits =  $credits[Credits\Type::AMOUNT] ?? 0;

            $feeCredits = $credits[Credits\Type::FEE] ?? 0;
        }

        return [$amountCredits, $feeCredits];
    }

    protected function createCreditTransaction(int $amount, Entity $txn, string $creditType)
    {
        try
        {
            (new Credits\Transaction\Core)->create($amount, $txn, $creditType);
        }
        catch (\Throwable $e)
        {
            $data = [
                'credit_amount'  => $amount,
                'transaction_id' => $txn->getId(),
                'credit_type'    => $creditType,
            ];

            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::CREDITS_TRANSACTION_FAILED,
                $data);
        }
    }

    /**
     * Compute and return the settled_at timestamp for a transfer
     * reversal transaction
     *
     * @param Reversal\Entity $reversal
     *
     * @return int
     * @throws Exception\LogicException
     */
    protected function getTransferReversalSettledAtTimestamp(Reversal\Entity $reversal): int
    {
        $scheduleTaskCore = new ScheduleTask\Core;

        $defaultScheduleTask = $scheduleTaskCore->getMerchantSettlementSchedule($reversal->merchant, null);

        //
        // Get the next_run_at for the merchant's default schedule_task
        // (where method = null)
        //
        // TODO: Need to fix this - pick the next_run_at starting tomorrow
        //
        $nextSettlementTime = $defaultScheduleTask->getNextRunAt();

        //
        // `source` will always be `Transfer/Entity` since this flow
        // is invoked only on Transfer Reversal creation
        //
        $transfer = $reversal->entity;

        if ($transfer->isPaymentTransfer() === true)
        {
            //
            // For payment transfers, the transfer txn's `settled_at` is
            // already set to at-least the payment's settlement timestamp,
            // (refer `createFromTransfer()` above) and is hence delayed to
            // after the merchant settlement schedule
            //
            // Therefore: Delay the reversal txn to the max of
            // - Transfer txn settled_at OR
            // - Next available settlement slot as per schedule
            //
            $transferSettledAt = $transfer->transaction->getSettledAt();

            s(Carbon::createFromTimestamp($transferSettledAt, Timezone::IST));

            return max($transferSettledAt, $nextSettlementTime);
        }
        else if ($transfer->isDirectTransfer() === true)
        {
            //
            // For direct transfers, there's no source payment to look at.
            // Hence, we look at the transfer created_at timestamp and add
            // the merchants settlement schedule to it (calling this -
            // `transferDelayTime`)
            //
            // We then set the reversal txn settled_at to the max of either
            // - transferDelayTime OR
            // - Next available settlement slot as per schedule
            //
            $transferCreatedAt = $transfer->getCreatedAt();

            $transferDelayTime = $scheduleTaskCore->getNextApplicableTimeForMerchant(
                $transferCreatedAt,
                $reversal->merchant);

            return max($transferDelayTime, $nextSettlementTime);
        }
        else
        {
            // Invalid case
            throw new Exception\LogicException('Invalid transfer type', null, ['transfer' => $transfer]);
        }
    }
}
