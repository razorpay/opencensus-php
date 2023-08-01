<?php

namespace RZP\Models\Transaction\Processor;

use Mail;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Feature;
use RZP\Models\Pricing;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant\Balance;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Base as BaseCollection;
use RZP\Mail\Merchant\FeeCreditsAlert;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Processor\Capture;
use RZP\Mail\Merchant\AmountCreditsAlert;
use RZP\Mail\Merchant\RefundCreditsAlert;
use RZP\Models\Base\Entity as BaseEntity;
use RZP\Mail\Merchant\BalanceThresholdAlert;

abstract class Base extends BaseCore
{
    use Capture;

    protected $source;

    /** @var Transaction\Entity */
    protected $txn;

    /** @var Merchant\Balance\Entity */
    protected $merchantBalance;

    protected $feesSplit;

    protected $tax;

    protected $fees;

    protected $debit;

    protected $credit;

    protected $amountCredits;

    protected $feeCredits;

    protected $rewardFeeCredits;


    public function __construct(BaseEntity $source)
    {
        parent::__construct();

        $this->setSource($source);

        $this->tax = 0;

        $this->fees = 0;

        $this->credit = 0;

        $this->debit = 0;

        $this->feesSplit = new BaseCollection\PublicCollection;
    }

    public function setSource(BaseEntity $source)
    {
        $this->source = $source;
    }

    public function setTransaction(Transaction\Entity $txn)
    {
        $this->txn = $txn;
    }

    protected function setTransactionForSource($txnId = null)
    {
        $txn = null;

        if ($this->source->hasTransaction() === true)
        {
            $txn = $this->repo->transaction->fetchByEntityAndAssociateMerchant($this->source);
        }
        else
        {
            $txn = $this->createNewTransaction($txnId);
        }

        $this->setTransaction($txn);
    }

    /**
     * This is used to create transaction entity in api with journal id and
     * balance information from ledger system
     *
     * @param string $txnId
     * @param int $balance
     *
     * @return array
     */
    public function createTransactionWithIdAndLedgerBalance(string $txnId, int $balance)
    {
        $existingTxn = $this->repo->transaction->find($txnId);

        if ($existingTxn !== null)
        {
            $this->trace->info(
                TraceCode::TRANSACTION_ALREADY_EXISTS
            );

            // returning fee split as null, as a fee breakup shall already be created
            return [$existingTxn, null];
        }

        // Creates new or fetches existing transaction entity for the source entity
        $this->setTransactionForSource($txnId);
        // set transaction attributes from the source entity
        $this->setSourceDefaults();
        // fills the transaction attributes from the merchant attributes
        $this->fillDetails();

        $this->setCreditDebitDetails($this);
        // updates entity specific attributes in transaction
        $this->updateTransaction();

        $negativeLimit = (new Balance\Core)->getNegativeLimit($this->txn);
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->updateCredits($negativeLimit);

        // update balances from ledger
        $this->txn->setBalance($balance);
        // update balance entity's balance

        // set merchant balance
        $this->merchantBalance->setBalance($balance);
        $this->repo->balance->updateBalance($this->merchantBalance);

        $this->txn->accountBalance()->associate($this->merchantBalance);

        return [$this->txn, $this->feesSplit];
    }

    public function createTransaction($txnId = null)
    {
        // Creates new or fetches existing transaction entity for the source entity
        $this->setTransactionForSource($txnId);

        // set transaction attributes from the source entity
        $this->setSourceDefaults();

        // fills the transaction attributes from the merchant attributes
        $this->fillDetails();

        if ($this->shouldMoveTxnFillToAsync())
        {
            $values = [
                Transaction\Entity::DEBIT               => 0,
                Transaction\Entity::CREDIT              => 0,
                Transaction\Entity::FEE                 => 0,
                Transaction\Entity::TAX                 => 0,
            ];

            $this->txn->fill($values);
            // updates entity specific attributes in transaction
            $this->updateTransaction();
        }
        else
        {
            $this->setCreditDebitDetails($this);

            // For dynamic fee bearer and postpaid model, we need to update fee, tax,
            // with the amounts borne only by merchant and add a debit of equal to customer fee + customer fee GST,
            // to settle the right amount to mx, all of this is under a feature flag.
            $this->setCustomerFeeAndTaxForPostPaidDfb();

            // updates entity specific attributes in transaction
            $this->updateTransaction();

            $negativeLimit = (new Balance\Core)->getNegativeLimit($this->txn);

            if ($this->shouldUpdateBalance() === true)
            {
                $startTime = microtime(true);

                try
                {
                    $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_LOCK_INIT);

                    $lockStartTime = microtime(true);

                    // update merchant credits an balances
                    $this->setMerchantBalanceLockForUpdate();

                    $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_LOCK_TIME_TAKEN,
                        [
                            'lock_start_time' => (microtime(true) - $lockStartTime) * 1000
                        ]
                    );

                    $this->decideBalanceSource();

                    $this->updateCredits($negativeLimit);

                    $this->updateBalances($negativeLimit);
                }
                finally
                {
                    $this->trace->info(TraceCode::MERCHANT_BALANCE_UPDATE_TIME_TAKEN,
                        [
                            'txn_type' => $this->txn->getType(),
                            'async_update' => false,
                            'balance_update_time' => (microtime(true) - $startTime) * 1000
                        ]
                    );
                }
            }
        }

        //
        // payment type transaction are dispatched on capture explicitly
        // there are cases where transaction is create before payment capture
        // such transaction shouldn't be settled
        //
        // and all the source migrated to respective changes
        // those will be added to the `DO_NOT_DISPATCH_FOR_SETTLEMENT` list
        //
        if (in_array($this->txn->getType(), Constants::DO_NOT_DISPATCH_FOR_SETTLEMENT, true) === false)
        {
            (new Transaction\Core)->dispatchForSettlementBucketing($this->txn);
        }

        return [$this->txn, $this->feesSplit];
    }

    protected function shouldUpdateBalance()
    {
        return true;
    }

    protected function shouldMoveTxnFillToAsync(): bool
    {
        return false;
    }

    public function setCustomerFeeAndTaxForPostPaidDfb()
    {
        if ($this->txn->isTypePayment() === true and $this->merchant !== null and
            $this->featureFlagCheckForMerchantPostPaidCustomerFeeNotSettled($this->merchant) === true)
        {
            $payment = $this->source;

            $values = $this->getValuesForCustomerFeeAndTaxInTransaction($payment);

            $this->txn->fill($values);
        }
    }

    public function featureFlagCheckForMerchantPostPaidCustomerFeeNotSettled($merchant): bool
    {
        return ($merchant->isPostpaid() === true and $merchant->isFeeBearerCustomerOrDynamic() === true and
                $merchant->isFeatureEnabled(Feature\Constants::CUSTOMER_FEE_DONT_SETTLE) === true);
    }

    public function setOtherDetails()
    {
        $this->txn->setCredit(0);

        $this->txn->setDebit(0);

        $this->txn->setFee($this->fees);

        $this->txn->setTax($this->tax);

        if ($this->credit > 0)
        {
            $this->txn->setCredit($this->credit);
        }

        $this->txn->setDebit($this->debit);

        $this->trace->debug(TraceCode::SETTING_TRANSACTION_CREDITS,
            [
                'transaction_id'        => $this->txn->getId(),
                'transaction_credit'    => $this->txn->getCredit(),
                'transaction_debit'     => $this->txn->getDebit(),
                'transaction_amount'    => $this->txn->getAmount(),
                'transaction_fee'       => $this->txn->getFee()
            ]
        );
    }

    public function fillDetails()
    {
        $merchant = $this->source->merchant;

        $this->txn->setFeeModel($merchant->getFeeModel());

        $this->txn->setFeeBearer($merchant->getFeeBearer());

        $amount = $this->source->getBaseAmount();

        $this->txn->setAmount($amount);

        $this->trace->debug(TraceCode::FILLED_TRANSACTION_DETAILS,
            [
                'transaction_id'            => $this->txn->getId(),
                'transaction_credit'        => $this->txn->getCredit(),
                'transaction_debit'         => $this->txn->getDebit(),
                'transaction_amount'        => $this->txn->getAmount(),
                'transaction_fee_model'     => $this->txn->getFeeModel(),
                'transaction_fee_bearer'    => $this->txn->getFeeBearer(),
            ]
        );
    }

    abstract function updateTransaction();

    abstract function calculateFees();

    // Currently settlement with merchant is done in the currency of a merchant, Hence
    // all the fields for credit, debit and fee should be in merchant's currency only
    public function setSourceDefaults()
    {
        $txnData = [
            Transaction\Entity::TYPE            => $this->source->getEntity(),
            Transaction\Entity::CURRENCY        => $this->txn->merchant->getCurrency(),
            Transaction\Entity::CHANNEL         => $this->source->merchant->getChannel(),
        ];

        $this->txn->fill($txnData);
    }

    public function setFeeDefaults()
    {
        $this->setMerchantCredits();

        $this->setMerchantFeeDefaults();
    }

    public function setCreditDebitDetails($processor)
    {
        // fetches credits, balance and calculates fees and taxes
        $processor->setFeeDefaults();

        // calculates fee sources and calculates credit and debit amounts
        $processor->calculateFees();

        // update credit and debit amounts, fees and taxes in transaction
        $processor->setOtherDetails();
    }

    public function setMerchantFeeDefaults()
    {
        list($this->fees, $this->tax, $this->feesSplit) = (new Pricing\Fee)->calculateMerchantFees($this->source);
    }

    protected function createNewTransaction($txnId = null)
    {
        $txn = new Transaction\Entity;

        $txnId != null ? $txn->setId($txnId) : $txn->generateId();

        //
        // Ideally we should have used build() here but not doing to avoid
        // unexpected & silent bugs/issues because we are in hurry to release x.
        //
        // Call to build() will set defaults in the entity object and hence are accessible in
        // toArrayPublic() like methods. Also, mostly defaults of code are same as of database.
        //
        // Needed the following attribute to exist in entity object during creation because immediately
        // after creation of payout's txn we serialize payout with transaction relation. And without this
        // line former will fail at setPublicSettlementIdAttribute().
        //
        $txn->setSettled(false);

        $txn->sourceAssociate($this->source);

        $txn->merchant()->associate($this->source->merchant);

        return $txn;
    }

    protected function setMerchantBalance()
    {
        if ($this->merchantBalance !== null)
        {
            return;
        }

        $this->merchantBalance = $this->repo->balance->getMerchantBalance($this->txn->merchant);
    }

    public function setMerchantBalanceLockForUpdate()
    {
        $merchantId = $this->txn->getMerchantId();

        $this->merchantBalance = $this->repo->balance->getBalanceLockForUpdate($merchantId);
    }

    protected function setMerchantCredits()
    {
        // TODO: There's no lock being taken here for balance!

        $this->setMerchantBalance();

        $merchant = $this->merchantBalance->merchant;

        $feature = Feature\Constants::OLD_CREDITS_FLOW;

        if ($merchant->isFeatureEnabled($feature) === true)
        {
            $amountCredits = $this->merchantBalance->getAmountCredits();

            $feeCredits = $this->merchantBalance->getFeeCredits();
        }
        else
        {
            $credits = $this->repo->credits->getTypeAggregatedNonRefundMerchantCredits($this->merchantBalance->merchant);

            $amountCredits =  $credits[Credits\Type::AMOUNT] ?? 0;

            $feeCredits = $credits[Credits\Type::FEE] ?? 0;
        }

        $this->amountCredits = $amountCredits;
        $this->feeCredits = $feeCredits;
    }

    protected function calculateFeeDefault()
    {
        $this->txn->setCreditType(Transaction\CreditType::DEFAULT);
    }

    protected function calculateFeeForAmountCredit()
    {
        $amount = $this->txn->getAmount();

        $source = $this->source;

        $this->trace->info(
            TraceCode::TRANSACTION_AMOUNT_CREDITS,
            [
                'source_type'    => $this->txn->getType(),
                'source_id'      => $source->getId(),
                'amount'         => $amount,
            ]
        );

        $pricingRuleId = (new Pricing\Fee)->getZeroPricingPlanRule($source)->getId();

        $this->txn->setPricingRule($pricingRuleId);

        $this->fees = 0;
        $this->tax = 0;

        $this->txn->setGratis(true);

        $this->txn->setCreditType(Transaction\CreditType::AMOUNT);

        $this->feesSplit = new BaseCollection\PublicCollection;
    }

    protected function calculateFeeForFeeCredit()
    {
        $feeCredits = $this->fees;

        $this->txn->setCredits($feeCredits);

        $this->txn->setCreditType(Transaction\CreditType::FEE);
    }

    protected function calculateFeeForRewardFeeCredit()
    {
        $rewardFeeCredits = $this->fees;

        $this->txn->setCredits($rewardFeeCredits);

        $this->txn->setCreditType(Transaction\CreditType::REWARD_FEE);
    }

    public function calculateSettledAtTimestamp($timestamp, $addDays, $ignoreBankHolidays = false)
    {
        $capturedAt = Carbon::createFromTimestamp($timestamp, Timezone::IST);

        $returnDay = Holidays::getNthWorkingDayFrom($capturedAt, $addDays, $ignoreBankHolidays);

        return $returnDay->getTimestamp();
    }

    public function updateCredits(int $negativeLimit = 0)
    {
        if($this->txn->isGratis() === true)
        {
            $this->updateAmountCredits();
        }
        else if ($this->txn->isFeeCredits() === true)
        {
            $this->updateFeeCredits();
        }
        else if ($this->txn->isRefundCredits() === true)
        {
            $this->updateRefundCredits($negativeLimit);
        }
    }

    public function updateAmountCredits()
    {
        //
        // Few asserts:
        // 1. This flow should be called only for gratis txn.
        // 2. Fee is not calculated in case it was gratis(amount credits flow)
        //    and so we expect it to be set to 0 always.
        // 3. For txn of type other than transfers(where txn.credit = 0) expectation
        //    is that the credit amount is same as txn amount (as fee is 0).
        // 4. Amount Credits cannot be used for Fund Account Validation.
        //
        assertTrue($this->txn->isGratis() === true);
        assertTrue($this->txn->getFee() === 0);
        assertTrue(
            ($this->isValidPaymentToUseAmountCredit() === true) or
            (($this->txn->isTypeTransfer() === true) and ($this->txn->getDebit() === $this->txn->getAmount())));
        assertTrue($this->txn->isTypeFundAccountValidation() === false);

        $amount = $this->txn->getAmount();

        $amountCreditsThreshold = $this->merchantBalance->merchant->getAmountCreditsThreshold();

        $amountCredits = $this->getMerchantCreditsOfType(Credits\Type::AMOUNT);

        // Removing Assert for now, as there is a race condition. if 2 payments
        // are authorized at the same time where we create txn on auth with. both
        // will try to set amount credits to zero and one will throw below assert
        // as both txn were marked as gratis on authorization
        // assert($amountCredits > 0);

        $merchantId = $this->txn->getMerchantId();

        $mode = $this->app['rzp.mode'] ?? 'live';

        $result = $this->app->razorx->getTreatment(
            $merchantId, RazorxTreatment::DISABLE_AMOUNT_CREDITS_FOR_GREATER_THAN_TXN_AMOUNT, $mode);

        $this->trace->info(
            TraceCode::AMOUNT_CREDITS_RAZORX_VARIANT,
            [
                'result'        => $result,
                'mode'          => $mode,
                'merchant_id'   => $merchantId,
            ]);

        // This is for safe guarding the GMV loss
        // Product Doc - https://docs.google.com/document/d/1ja5NzsZJWCDOjn5T_TgzcBNkL6Ubtp5YJwXuxeUSzw4/edit
        if (($amountCredits < $amount) and (strtolower($result) === RazorxTreatment::RAZORX_VARIANT_ON)
            and ($this->txn->isTypePayment() === true))
        {
            // Error code is not added to make the flow in sync with fee credits.
            throw new Exception\LogicException(
                'AmountCredit should be higher or equal to the payment amount',
                null,
                [
                    'transaction_id'            => $this->txn->getId(),
                    'merchant_id'               => $merchantId,
                    'amount_credits'            => $amountCredits,
                    'transaction_amount'        => $amount,
                ]);
        }
        else if ($amountCredits < $amount)
        {
            // Even if free credits is less than txn amount, we still give full
            // amount as free credits. However, in balance we only go ahead with
            // updating the actual free credits so that it does not go negative.
            $amount = $amountCredits;
        }


        // $nodalBalance = $this->getNodalBalanceLockForUpdate($txn->getChannel());

        // $nodalBalance->subtractAmountCredits($amount);

        $this->merchantBalance->subtractAmountCredits($amount);

        //create a credit transaction for the same
        $this->createCreditTransaction($amount, Credits\Type::AMOUNT);

        // Nodal balance needs to be saved because of amount credit update
        // $this->repo->balance->updateBalance($nodalBalance);

        // only check for credit threshold if it's not null
        if ($amountCreditsThreshold !== null)
        {
            $this->sendAmountCreditAlertIfNeeded(
                $amount, $amountCredits, $amountCreditsThreshold, $this->merchantBalance->merchant);
        }
    }

    private function sendAmountCreditAlertIfNeeded(
        int $amount,
        int $amountCredits,
        int $amountCreditsThreshold,
        Merchant\Entity $merchant)
    {
        // $alertRatios should be sorted array always
        $alertRatios = [0.1, 0.25, 0.5, 0.75, 1];

        foreach ($alertRatios as $alertRatio)
        {
            if (($amountCredits >= ($alertRatio * $amountCreditsThreshold)) and
                (($amountCredits - $amount) < ($alertRatio * $amountCreditsThreshold)))
            {
                $data = [
                    'email'             => $merchant->getTransactionReportEmail(),
                    'merchant_id'       => $merchant->getId(),
                    'merchant_dba'      => $merchant->getBillingLabel(),
                    'amount_credits'    => '₹ '.(($amountCredits - $amount) / 100),
                    'org_hostname'      => $merchant->org->getPrimaryHostName(),
                    'timestamp'         => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
                ];

                $this->trace->info(TraceCode::AMOUNT_CREDITS_THRESHOLD_ALERT, $data);

                $createAlertMail = new AmountCreditsAlert($data);

                Mail::queue($createAlertMail);

                break;
            }
        }
    }

    /**
     * checks if payment data matches the required condition to use amount credit
     * 1. If the payment is made via direct settlement terminal
     *    then can use the amount credit
     * 2. If the payment is not made via direct settlement terminal
     *    then amount should match the credit field of transaction
     *
     * @return bool
     */
    private function isValidPaymentToUseAmountCredit(): bool
    {
        if ($this->txn->isTypePayment() === false)
        {
            return false;
        }

        if ($this->source->terminal->isDirectSettlement() === true)
        {
            return true;
        }

        // For cred, amount doesn't matches with credits because of the discount.
        if ($this->source->isAppCred() === true)
        {
            return true;
        }

        if ($this->source->isCardlessEmiWalnut369() === true)
        {
            return true;
        }

        return ($this->txn->getCredit() === $this->txn->getAmount());
    }

    protected function getMerchantCreditsOfType(string $type)
    {
        $merchant = $this->merchantBalance->merchant;

        $feature = Feature\Constants::OLD_CREDITS_FLOW;

        if ($merchant->isFeatureEnabled($feature) === true)
        {
            if ($type === Credits\Type::FEE)
            {
                $credits = $this->merchantBalance->getFeeCredits();
            }
            else if ($type === Credits\Type::REFUND)
            {
                $credits = $this->merchantBalance->getRefundCredits();
            }
            else
            {
                $credits = $this->merchantBalance->getAmountCredits();
            }
        }
        else
        {
            $merchantId = $merchant->getId();

            $credits = $this->repo->credits->getMerchantCreditsOfType($merchantId, $type);
        }

        return $credits;
    }

    protected function createCreditTransaction(int $amount, string $creditType)
    {
        try
        {
            // We are doing reversal only for Refund credits
            if (($this->txn->isTypeReversal() === true) and ($this->txn->isRefundCredits()))
            {
                $refundTransactionId = $this->source->entity->getTransactionId();

                (new Credits\Transaction\Core)
                    ->createCreditReversalTransaction($amount, $this->txn, $refundTransactionId, $creditType);
            }
            else
            {
                (new Credits\Transaction\Core)->createCreditTransaction($amount, $this->txn, $creditType);
            }
        }
        catch (\Throwable $e)
        {
            $data = [
                'credit_amount'  => $amount,
                'transaction_id' => $this->txn->getId(),
                'credit_type'    => $creditType,
            ];

            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::CREDITS_TRANSACTION_FAILED,
                $data);

            /**
             * Throwing this exception only in case of refunds so if credit transaction creation fails, we can rollback the credits that got deducted and stop refund creation.
             * Also, We are having a message check check so that in case of fee only reversal events of postpaid merchants, we would not want to throw that exception
             */

            if(($this->txn->isTypeRefund() === true) && ($creditType === Credits\Type::REFUND)){

                throw $e;
            }
        }
    }

    public function updateFeeCredits()
    {
        // While filling the txn fees and amount, we have not used fee credits.
        if (($this->txn->isFeeCredits() === false) or
            ($this->txn->getCredits() === 0))
        {
            return;
        }

        $fee = $this->txn->getFee();

        $merchantId = $this->merchantBalance->merchant->getId();

        $feeCreditsThreshold = $this->merchantBalance->merchant->getFeeCreditsThreshold();

        $feeCredits = $this->getMerchantCreditsOfType(Credits\Type::FEE);

        if ($feeCredits < $fee)
        {
            throw new Exception\LogicException(
                'FeeCredits should be higher or equal to the fee',
                null,
                [
                    'transaction_id'    => $this->txn->getId(),
                    'merchant_id'       => $merchantId,
                    'fee_credits'       => $feeCredits,
                    'fee'               => $fee,
                ]);
        }

        // $nodalBalance = $this->getNodalBalanceLockForUpdate($txn->getChannel());

        // $nodalBalance->subtractFeeCredits($fee);

        $this->merchantBalance->subtractFeeCredits($fee);

        //create a credit transaction for the same
        $this->createCreditTransaction($fee, Credits\Type::FEE);

        // // Nodal balance needs to be saved because of amount credit update
        // $this->repo->balance->updateBalance($nodalBalance);

        if ($feeCreditsThreshold !== null)
        {
            $this->sendFeeCreditAlertIfNeeded(
                $fee, $feeCredits, $feeCreditsThreshold, $this->merchantBalance->merchant);
        }
    }

    private function sendFeeCreditAlertIfNeeded(
        int $fee,
        int $feeCredits,
        int $feeCreditsThreshold,
        Merchant\Entity $merchant)
    {
        $alertRatios = [1, 0.75, 0.5, 0.25, 0.1];

        sort($alertRatios);

        foreach ($alertRatios as $alertRatio)
        {
            if (($feeCredits >= ($alertRatio * $feeCreditsThreshold)) and
                (($feeCredits - $fee) < ($alertRatio * $feeCreditsThreshold)))
            {
                $data = [
                    'alert_ratio'  => $alertRatio,
                    'email'        => $merchant->getTransactionReportEmail(),
                    'merchant_id'  => $merchant->getId(),
                    'merchant_dba'  => $merchant->getBillingLabel(),
                    'fee_credits'  => '₹ '.(($feeCredits - $fee) / 100),
                    'org_hostname' => $merchant->org->getPrimaryHostName(),
                    'timestamp'    => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
                ];

                $this->trace->info(TraceCode::FEE_CREDITS_THRESHOLD_ALERT, $data);

                $createAlertMail = new FeeCreditsAlert($data);

                Mail::queue($createAlertMail);

                break;
            }
        }
    }

    public function updateRefundCredits(int $negativeLimit = 0)
    {
        // While filling the txn fees and amount, we have not used fee credits.
        if ((($this->txn->isTypeRefund() === false) and
                ($this->txn->isTypeReversal() === false)) or
            ($this->txn->isRefundCredits() === false))
        {
            return;
        }

        $amount = $this->txn->getCredits();

        $merchantId = $this->merchantBalance->merchant->getId();

        $refundCreditsThreshold = $this->merchantBalance->merchant->getRefundCreditsThreshold();

        $mode = $this->app['rzp.mode'] ?? 'live';

        $result = $this->app->razorx->getTreatment(
            $merchantId, RazorxTreatment::REFUND_CREDITS_WITH_LOCK, $mode);

        $this->trace->info(
            TraceCode::SCROOGE_FETCH_REFUND_CREDITS_WITH_LOCK,
            [
                'result' => $result,
                'mode' => $mode,
                'merchant_id' => $merchantId,
            ]);

        if(strtolower($result) === RazorxTreatment::RAZORX_VARIANT_ON) {

            $refundCredits = $this->getMerchantCreditsOfType(Credits\Type::REFUND);
        }
        else
        {
            $refundCredits = $this->merchantBalance->getRefundCredits();
        }

        $data = [
            'transaction_id'    => $this->txn->getId(),
            'merchant_id'       => $merchantId,
            'refund_credits'    => $refundCredits,
            'amount'            => $amount,
        ];

        if (($negativeLimit === 0) and
            ($refundCredits < $amount))
        {
            throw new Exception\LogicException(
                'Refund Credits should be higher or equal to the refund amount',
                null,
                $data
                );
        }
        else if (($refundCredits - $amount) < $negativeLimit)
        {
            $data['message'] = TraceCode::getMessage(TraceCode::NEGATIVE_BALANCE_BREACHED);

            $data['negative_limit'] = $negativeLimit;

            throw new BadRequestException(ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED, abs($amount),
                $data);
        }

        $this->merchantBalance->subtractRefundCredits($amount, $negativeLimit);

        $newCredits = $this->merchantBalance->getRefundCredits();

        (new Balance\NegativeReserveBalanceMailers())->sendNegativeBalanceMailIfApplicable(
                                                        $this->merchantBalance->merchant,
                                                        $refundCredits,
                                                        $newCredits,
                                                        $negativeLimit,
                                                        'refund credits',
                                                        $this->txn->getType());
        //create a credit transaction for the same
        $this->createCreditTransaction($amount, Credits\Type::REFUND);

        // only check for credit threshold if it's not null
        if ($refundCreditsThreshold !== null)
        {
            $this->sendRefundCreditAlertIfNeeded(
                $amount, $refundCredits, $refundCreditsThreshold, $this->merchantBalance->merchant);
        }
    }

    private function sendRefundCreditAlertIfNeeded(
        int $amount,
        int $refundCredits,
        int $refundCreditsThreshold,
        Merchant\Entity $merchant)
    {
        // $alertRatios should be sorted array always
        $alertRatios = [0.1, 0.25, 0.5, 0.75, 1];

        foreach ($alertRatios as $alertRatio)
        {
            if (($refundCredits >= ($alertRatio * $refundCreditsThreshold)) and
                (($refundCredits - $amount) < ($alertRatio * $refundCreditsThreshold)))
            {
                $data = [
                    'email'        => $merchant->getTransactionReportEmail(),
                    'merchant_id'  => $merchant->getId(),
                    'merchant_dba'  => $merchant->getBillingLabel(),
                    'refund_credits'  => '₹ '.(($refundCredits - $amount) / 100),
                    'org_hostname' => $merchant->org->getPrimaryHostName(),
                    'timestamp'    => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
                ];

                $this->trace->info(TraceCode::REFUND_CREDITS_THRESHOLD_ALERT, $data);

                $createAlertMail = new RefundCreditsAlert($data);

                Mail::queue($createAlertMail);

                break;
            }
        }
    }

    public function updateBalances(int $negativeLimit = 0)
    {
        $this->txn->accountBalance()->associate($this->merchantBalance);

        $this->updateMerchantBalance($negativeLimit);
    }

    public function updateMerchantBalance(int $negativeLimit = 0)
    {
        $merchantBalance = $this->merchantBalance;

        $oldBalance = $merchantBalance->getBalance();

        $merchantBalance->updateBalance($this->txn, $negativeLimit);

        $newBalance = $this->merchantBalance->getBalance();

        $this->trace->info(TraceCode::MERCHANT_BALANCE_DATA,
            [
                'merchant_id' => $this->txn->getMerchantId(),
                'new_balance' => $newBalance,
                'old_balance' => $oldBalance,
                'method'      => 'updateMerchantBalance',
            ]);

        $this->repo->balance->updateBalance($this->merchantBalance);

        $checkNegativeLimit = $oldBalance > $newBalance;

        if ($this->txn->shouldNegativeBalanceCheckSkipped() === true)
        {
            $checkNegativeLimit = false;
        }

        $this->txn->setBalance($this->merchantBalance->getBalance(), $negativeLimit, $checkNegativeLimit);

        $balanceThreshold = $this->merchantBalance->merchant->getBalanceThreshold();
        if($balanceThreshold !== null and
           $merchantBalance->getType() === Balance\Type::PRIMARY)
        {
            $this->sendBalanceThresholdAlertIfNeeded(
                $oldBalance, $newBalance, $balanceThreshold, $this->merchantBalance->merchant, $this->merchantBalance->getRefundCredits()
            );
        }

        (new Balance\Core)->postProcessingForNegativeBalance($oldBalance, Balance\Entity::BALANCE,
                                                            $this->txn->getType(), $merchantBalance);
    }

    private function sendBalanceThresholdAlertIfNeeded(
        $oldBalance,
        $newBalance,
        $balanceThreshold,
        $merchant,
        $refundCredits
    )
    {
        // $alertRatios should be sorted array always
        $alertRatios = [0.25, 0.5, 1];

        foreach ($alertRatios as $alertRatio)
        {
            if (($oldBalance >= ($alertRatio * $balanceThreshold)) and
                ($newBalance < ($alertRatio * $balanceThreshold)))
            {
                $data = [
                    'email'             => $merchant->getTransactionReportEmail(),
                    'merchant_id'       => $merchant->getId(),
                    'merchant_dba'      => $merchant->getBillingLabel(),
                    'balance'           => '₹ '.(($newBalance) / 100),
                    'org_hostname'      => $merchant->org->getPrimaryHostName(),
                    'timestamp'         => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
                    'refund_credit'     => '₹ '.(($refundCredits) / 100)
                ];

                $this->trace->info(TraceCode::BALANCE_THRESHOLD_ALERT, $data);

                $createAlertMail = new BalanceThresholdAlert($data);

                Mail::queue($createAlertMail);

                break;
            }
        }
    }

    public function updatePostedDate(int $postedDate = null)
    {
        $postedAt = Carbon::now(Timezone::IST)->getTimestamp();

        $postedDate = $postedDate ? $postedDate : $postedAt;

        $this->txn->setPostedDate($postedDate);
    }

    // For cases where the actual debit/credit amount can be different from the actual transaction amount
    // Eg - While doing cred payments a user can burn some % of total amount using cred coins, therefore
    // that amount should be deducted from the transaction amount.
    // Eg - Another usecase is cardlessemi walnut369, the net settlement amount depends on discount applied during credit
    protected function getDiscountIfApplicable(Payment\Entity $payment)
    {
        if (($payment->isCardlessEmiWalnut369() === true) and ($payment->merchant->isFeatureEnabled(Feature\Constants::SOURCED_BY_WALNUT369) === true))
        {
            if ($payment->getBaseAmount() !== $this->txn->getAmount())
            {
                // no discount applicable if partial payment if merchant is sourced by walnut
                return 0;
            }
        }

        $discountRatio = $payment->getDiscountRatioIfApplicable();

        return (int) round($discountRatio * $this->txn->getAmount());
    }

    public function decideBalanceSource(){

    }
}
