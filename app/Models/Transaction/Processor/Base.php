<?php

namespace RZP\Models\Transaction\Processor;

use Mail;
use Carbon\Carbon;

use Razorpay\Trace\Logger;
use RZP\Exception;
use RZP\Models\Feature;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Models\Currency;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Jobs\Settlement\Bucket;
use RZP\Models\Merchant\Credits;
use RZP\Models\Merchant\Balance;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Base as BaseCollection;
use RZP\Mail\Merchant\FeeCreditsAlert;
use RZP\Models\Base\Entity as BaseEntity;

abstract class Base extends BaseCore
{
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

    protected function setTransactionForSource()
    {
        $txn = null;

        if ($this->source->hasTransaction() === true)
        {
           $txn = $this->repo->transaction->fetchByEntityAndAssociateMerchant($this->source);
        }
        else
        {
            $txn = $this->createNewTransaction();
        }

        $this->setTransaction($txn);
    }

    public function createTransaction()
    {
        // Creates new or fetches existing transaction entity for the source entity
        $this->setTransactionForSource();

        // set transaction attributes from the source entity
        $this->setSourceDefaults();

        // fills the transaction attributes from the merchant attributes
        $this->fillDetails();

        // fetches credits, balance and calculates fees and taxes
        $this->setFeeDefaults();

        // calculates fee sources and calculates credit and debit amounts
        $this->calculateFees();

        // update credit and debit amounts, fees and taxes in transaction
        $this->setOtherDetails();

        // updates entity specific attributes in transaction
        $this->updateTransaction();

        if ($this->shouldUpdateBalance() === true)
        {
            // update merchant credits an balances
            $this->setMerchantBalanceLockForUpdate();

            $this->updateCredits();

            $this->updateBalances();
        }

        return [$this->txn, $this->feesSplit];
    }

    protected function shouldUpdateBalance()
    {
        return true;
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
    }

    public function fillDetails()
    {
        $merchant = $this->source->merchant;

        $this->txn->setFeeModel($merchant->getFeeModel());

        $this->txn->setFeeBearer($merchant->getFeeBearer());

        $amount = $this->source->getBaseAmount();

        $this->txn->setAmount($amount);
    }

    abstract function updateTransaction();

    abstract function calculateFees();

    public function setSourceDefaults()
    {
        $txnData = [
            Transaction\Entity::TYPE            => $this->source->getEntity(),
            Transaction\Entity::CURRENCY        => Currency\Currency::INR,
            Transaction\Entity::CHANNEL         => $this->source->merchant->getChannel(),
        ];

        $this->txn->fill($txnData);
    }

    public function setFeeDefaults()
    {
        $this->setMerchantCredits();

        $this->setMerchantFeeDefaults();
    }

    public function setMerchantFeeDefaults()
    {
        list($this->fees, $this->tax, $this->feesSplit) = (new Pricing\Fee)->calculateMerchantFees($this->source);
    }

    protected function createNewTransaction()
    {
        $txn = new Transaction\Entity;

        $txn->generateId();

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
            $merchantId = $this->merchantBalance->merchant->getId();

            $credits = $this->repo->credits->getTypeAggregatedMerchantCredits($merchantId);

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

    public function calculateSettledAtTimestamp($timestamp, $addDays, $ignoreBankHolidays = false)
    {
        $capturedAt = Carbon::createFromTimestamp($timestamp, Timezone::IST);

        $returnDay = Holidays::getNthWorkingDayFrom($capturedAt, $addDays, $ignoreBankHolidays);

        return $returnDay->getTimestamp();
    }

    public function updateCredits()
    {
        if ($this->txn->isGratis() === true)
        {
            $this->updateAmountCredits();
        }
        else if ($this->txn->isFeeCredits() === true)
        {
            $this->updateFeeCredits();
        }
        else if ($this->txn->isRefundCredits() === true)
        {
            $this->updateRefundCredits();
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
            (($this->txn->isTypePayment() === true) and ($this->txn->getCredit() === $this->txn->getAmount())) or
            (($this->txn->isTypeTransfer() === true) and ($this->txn->getDebit() === $this->txn->getAmount())));
        assertTrue($this->txn->isTypeFundAccountValidation() === false);

        $amount = $this->txn->getAmount();

        $amountCredits = $this->getMerchantCreditsOfType(Credits\Type::AMOUNT);

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

        $this->merchantBalance->subtractAmountCredits($amount);

        //create a credit transaction for the same
        $this->createCreditTransaction($amount, Credits\Type::AMOUNT);

        // Nodal balance needs to be saved because of amount credit update
        // $this->repo->balance->updateBalance($nodalBalance);
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
                    ->createCreditReversalTransaction($amount, $this->txn, $refundTransactionId);
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

    public function updateRefundCredits()
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

        $refundCredits = $this->getMerchantCreditsOfType(Credits\Type::REFUND);

        if ($refundCredits < $amount)
        {
            throw new Exception\LogicException(
                'Refund Credits should be higher or equal to the refund amount',
                null,
                [
                    'transaction_id'    => $this->txn->getId(),
                    'merchant_id'       => $merchantId,
                    'refund_credits'    => $refundCredits,
                    'amount'            => $amount,
                ]);
        }

        $this->merchantBalance->subtractRefundCredits($amount);

        //create a credit transaction for the same
        $this->createCreditTransaction($amount, Credits\Type::REFUND);
    }

    public function updateBalances()
    {
        $this->txn->accountBalance()->associate($this->merchantBalance);

        $this->updateMerchantBalance();
    }

    public function updateMerchantBalance()
    {
        $this->merchantBalance->updateBalance($this->txn);

        $this->repo->balance->updateBalance($this->merchantBalance);

        $this->txn->setBalance($this->merchantBalance->getBalance());
    }

    /**
     * It'll dispatch the job to update settlement bucket for merchant
     * This will also suppress the any error occurred at this stage
     * if settled at is null then it wont dispatch the job
     *
     * @param Transaction\Entity $txn
     * @param null               $settledAt
     */
    public function dispatchForSettlementBucketing(Transaction\Entity $txn, $settledAt = null)
    {
        //
        // in case the transaction is not eligible for settlement then
        // settled_at will have some number else it will be null
        //
        if (($settledAt === null) or
            ($txn->isOnHold() === true))
        {
            return;
        }

        try
        {
            Bucket::dispatch($this->mode, $txn->getId(), $txn->getMerchantId(), $settledAt);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FAILED_TO_ENQUEUE_MERCHANT_FOR_SETTLEMENT
            );
        }
    }
}
