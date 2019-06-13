<?php

namespace RZP\Models\Payment\Refund;

use App;
use ApiResponse;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Currency;
use RZP\Models\Reversal;
use RZP\Models\Transaction;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Models\Base\Traits\NotesTrait;
use Razorpay\Spine\DataTypes\Dictionary;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;
use RZP\Models\Payment\Refund\Metric as RefundMetric;

/**
 * @property Payment\Entity     $payment
 * @property Transaction\Entity $transaction
 * @property Merchant\Entity    $merchant
 */
class Entity extends Base\PublicEntity
{
    use HasBalance;
    use NotesTrait;

    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const PAYMENT_ID             = 'payment_id';
    const AMOUNT                 = 'amount';
    const CURRENCY               = 'currency';
    const BASE_AMOUNT            = 'base_amount';
    const STATUS                 = 'status';
    const ERROR_CODE             = 'error_code';
    const INTERNAL_ERROR_CODE    = 'internal_error_code';
    const ERROR_DESCRIPTION      = 'error_description';
    const NOTES                  = 'notes';
    const FTS_TRANSFER_ID        = 'fts_transfer_id';

    //merchant reference number for refund if provided by merchant
    const RECEIPT                = 'receipt';

    const TRANSACTION_ID         = 'transaction_id';
    const BATCH_FUND_TRANSFER_ID = 'batch_fund_transfer_id';
    const BATCH_ID               = 'batch_id';
    const REVERSAL_ID            = 'reversal_id';

    const GATEWAY                = 'gateway';
    const GATEWAY_REFUNDED       = 'gateway_refunded';
    const REFERENCE1             = 'reference1';
    const REFERENCE2             = 'reference2';
    const REFERENCE3             = 'reference3';
    const REFERENCE4             = 'reference4';
    const REVERSED_AT            = 'reversed_at';
    const REFERENCE9             = 'reference9';
    const BALANCE_ID             = 'balance_id';

    const ATTEMPTS               = 'attempts';
    const LAST_ATTEMPTED_AT      = 'last_attempted_at';
    const PROCESSED_AT           = 'processed_at';

    const ACQUIRER_DATA          = 'acquirer_data';
    const ARN                    = 'arn';
    const REVERSAL               = 'reversal';
    const RRN                    = 'rrn';
    const UTR                    = 'utr';

    const RESPONSE_CODE          = 'code';
    const RESPONSE_BODY          = 'body';

    /**
     * Holds the value of Reference number sent by bank for eg for upi, it contains npci_upi_txn_id
     */
    const BANK_REFERENCE_NO = 'reference_no';

    const BANK_ACCOUNT_ID        = 'bank_account_id';
    const VPA_ID                 = 'vpa_id';
    const SETTLED_BY             = 'settled_by';

    // indicates refund is processed via scrooge service or not.
    const IS_SCROOGE             = 'is_scrooge';

    // Table Attributes created for Instant refunds
    const SPEED_REQUESTED        = 'speed_requested';
    const SPEED_PROCESSED        = 'speed_processed';
    const FEE                    = 'fee';
    const TAX                    = 'tax';

    const MODE                   = 'mode';
    const SPEED                  = 'speed';

    protected static $sign = 'rfnd';

    protected $entity = 'refund';

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::GATEWAY,
        self::SETTLED_BY,
    ];

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::RECEIPT,
        self::STATUS,
        self::FEE,
        self::TAX,
        self::REFERENCE1,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::BASE_AMOUNT,
        self::STATUS,
        self::ERROR_CODE,
        self::INTERNAL_ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::GATEWAY,
        self::GATEWAY_REFUNDED,
        self::NOTES,
        self::RECEIPT,
        self::TRANSACTION_ID,
        self::BATCH_FUND_TRANSFER_ID,
        self::BATCH_ID,
        self::ACQUIRER_DATA,
        self::ATTEMPTS,
        self::SPEED_REQUESTED,
        self::SPEED_PROCESSED,
        self::FEE,
        self::TAX,
        self::LAST_ATTEMPTED_AT,
        self::PROCESSED_AT,
        self::BALANCE_ID,
        self::IS_SCROOGE,
        self::REFERENCE1,
        self::BANK_ACCOUNT_ID,
        self::VPA_ID,
        self::SETTLED_BY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::PAYMENT_ID,
        self::NOTES,
        self::RECEIPT,
        self::ACQUIRER_DATA,
        self::REVERSAL,
        self::CREATED_AT,
    ];

    protected $publicCustomer = [
        self::ID,
        self::AMOUNT,
        self::PAYMENT_ID,
        self::ACQUIRER_DATA,
        self::CREATED_AT,
    ];

    protected $hiddenInReport = [self::ACQUIRER_DATA];

    protected $defaults = [
        self::NOTES             => [],
        self::STATUS            => Status::CREATED,
        self::SPEED_REQUESTED   => Speed::NORMAL,
        self::SPEED_PROCESSED   => null,
        self::GATEWAY_REFUNDED  => null,
        self::ATTEMPTS          => null,
        self::LAST_ATTEMPTED_AT => null,
        self::PROCESSED_AT      => null,
        self::IS_SCROOGE        => 0,
        self::RECEIPT           => null,
        self::FEE               => 0,
        self::TAX               => 0,
    ];

    protected $casts = [
        self::AMOUNT           => 'int',
        self::BASE_AMOUNT      => 'int',
        self::GATEWAY_REFUNDED => 'bool',
        self::FEE              => 'int',
        self::TAX              => 'int',
        self::IS_SCROOGE       => 'bool',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_ID,
        self::ACQUIRER_DATA,
    ];

    protected $amounts = [
        self::AMOUNT,
        self::BASE_AMOUNT,
        self::FEE,
        self::TAX,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::LAST_ATTEMPTED_AT,
        self::PROCESSED_AT,
    ];

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity');
    }

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function batch()
    {
        return $this->belongsTo('RZP\Models\Batch\Entity', self::BATCH_ID);
    }

    public function fundTransferAttempts()
    {
        return $this->morphMany('RZP\Models\FundTransfer\Attempt\Entity', 'source')
                    ->orderBy(self::CREATED_AT);
    }

    public function batchFundTransfer()
    {
        return $this->belongsTo('RZP\Models\FundTransfer\Batch\Entity');
    }

    public function netbanking()
    {
        return $this->hasOne('RZP\Gateway\Netbanking\Base\Entity');
    }

    public function bankAccount()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity');
    }

    public function vpa()
    {
        return $this->belongsTo('RZP\Models\Vpa\Entity');
    }

    public function billdesk()
    {
        return $this->hasOne('RZP\Gateway\Billdesk\Entity');
    }

    public function reversal()
    {
        return $this->belongsTo(Reversal\Entity::class, self::REVERSAL_ID);
    }

    public function build(array $input = [])
    {
        $payment = func_get_arg(1);

        $this->payment()->associate($payment);

        $this->getValidator()->setPayment($payment);

        return parent::build($input);
    }

    protected function generateAmount($input)
    {
        if (empty($input['amount']))
        {
            $this->setAttribute(
                self::AMOUNT,
                $this->payment->getAmountUnrefunded());
        }
    }

    protected function generateCurrency($input)
    {
        $this->setAttribute(self::CURRENCY, $this->payment->getCurrency());
    }

    protected function generateGateway($input)
    {
        $this->setAttribute(self::GATEWAY, $this->payment->getGateway());
    }

    protected function generateSettledBy($input)
    {
        // If terminal has support for direct settlement for refunds
        if ($this->isDirectSettlementRefund() === true)
        {
            $this->setAttribute(self::SETTLED_BY, $this->payment->getSettledBy());
        }
        else
        {
            $this->setAttribute(self::SETTLED_BY, 'Razorpay');
        }
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getBaseAmount()
    {
        return $this->getAttribute(self::BASE_AMOUNT);
    }

    /**
     * Returns base amount + applicable fee
     */
    public function getNetAmount()
    {
        return $this->getBaseAmount() + $this->getFee();
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function hasBankAccount()
    {
        return ($this->isAttributeNotNull(self::BANK_ACCOUNT_ID));
    }

    public function hasVpa()
    {
        return ($this->isAttributeNotNull(self::VPA_ID));
    }

    public function isGatewayRefunded()
    {
        return ($this->getAttribute(self::GATEWAY_REFUNDED) === true);
    }

    public function isCreated()
    {
        return ($this->getAttribute(self::STATUS) === Status::CREATED);
    }

    public function isInitiated()
    {
        return ($this->getAttribute(self::STATUS) === Status::INITIATED);
    }

    public function isBatch(): bool
    {
        return ($this->getBatchId() !== null);
    }

    public function isProcessed()
    {
        return ($this->getAttribute(self::STATUS) === Status::PROCESSED);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getBatchFundTransferId()
    {
        return $this->getAttribute(self::BATCH_FUND_TRANSFER_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    public function getInternalErrorCode()
    {
        return $this->getAttribute(self::INTERNAL_ERROR_CODE);
    }

    public function getErrorDescription()
    {
        return $this->getAttribute(self::ERROR_DESCRIPTION);
    }

    public function getAttempts()
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function getSpeedRequested()
    {
        return $this->getAttribute(self::SPEED_REQUESTED);
    }

    public function getSpeedProcessed()
    {
        return $this->getAttribute(self::SPEED_PROCESSED);
    }

    public function getReference1()
    {
        return $this->getAttribute(self::REFERENCE1);
    }

    public function getReference2()
    {
        return $this->getAttribute(self::REFERENCE2);
    }

    public function getSettledBy()
    {
        $settledBy = $this->getAttribute(self::SETTLED_BY);

        if ($settledBy === null)
        {
            $settledBy = "Razorpay";
        }

        return $settledBy;
    }

    public function getAcquirerData()
    {
        return $this->getAttribute(self::ACQUIRER_DATA);
    }

    public function getLastAttemptedAt()
    {
        return $this->getAttribute(self::LAST_ATTEMPTED_AT);
    }

    public function getProcessedAt()
    {
        return $this->getAttribute(self::PROCESSED_AT);
    }

    public function getChannel()
    {
        return $this->merchant->getChannel();
    }

    public function getFees()
    {
        return $this->getFee();
    }

    public function getFee()
    {
        return $this->getAttribute(self::FEE);
    }

    public function getTax()
    {
        return $this->getAttribute(self::TAX);
    }

    public function getPricingFeatures()
    {
        return [];
    }

    public function getMethod()
    {
        return $this->payment->getMethod();
    }

    protected function getAcquirerDataAttribute()
    {
        $acquirerData = [];

        $payment = $this->payment;

        switch ($payment->getMethod())
        {
            case Payment\Method::CARD:
                $acquirerData = [
                    self::ARN   => $this->getAttribute(self::REFERENCE1)
                ];
                break;

            case Payment\Method::UPI:
                $acquirerData = [
                    self::RRN   => $this->getAttribute(self::REFERENCE1)
                ];
                break;

            case Payment\Method::EMANDATE:
                $acquirerData = [
                    self::UTR   => $this->getAttribute(self::REFERENCE1)
                ];
                break;
        }

        return (new Dictionary($acquirerData));
    }

    public function getFTSTransferId()
    {
        return $this->getAttribute(self::FTS_TRANSFER_ID);
    }

    /**
     * Returns true for refunds which are processed by Scrooge Service
     *
     * @return bool
     */
    public function isScrooge()
    {
        return ($this->getAttribute(self::IS_SCROOGE) === true);
    }

    /**
     * Used by FTA reconciliation
     */
    public function setFailureReason($failureReason)
    {
        return;
    }

    public function setGatewayRefunded($gatewayRefunded)
    {
        $this->setAttribute(self::GATEWAY_REFUNDED, $gatewayRefunded);
    }

    public function setGateway($gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }

    public function setStatus($status)
    {
        $this->pushStatusChangeMetrics($status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setSpeedRequested(string $speedRequested)
    {
        $this->setAttribute(self::SPEED_REQUESTED, $speedRequested);
    }

    public function setSpeedProcessed(string $speedProcessed)
    {
        $this->setAttribute(self::SPEED_PROCESSED, $speedProcessed);
    }

    public function setSettledBy($settledBy)
    {
        $this->setAttribute(self::SETTLED_BY, $settledBy);
    }

    public function isDirectSettlementRefund(): bool
    {
        if (($this->payment->hasTerminal() === true) and
            ($this->payment->terminal->isDirectSettlementWithRefund() === true))
        {
            return true;
        }

        return false;
    }

    public function setFTSTransferId($ftsTransferId)
    {
        $this->setAttribute(self::FTS_TRANSFER_ID, $ftsTransferId);
    }

    public function pushStatusChangeMetrics($statusToChange)
    {
        if (Status::isStatusTrackedForMetrics($statusToChange) === false)
        {
            return;
        }

        $dimensions = RefundMetric::getDimensions($this);

        switch ($statusToChange)
        {
            case Status::PROCESSED:

                $this->pushMetricsForProcessedStatusChange($dimensions);

                break;

            case Status::FAILED:

                $this->pushMetricsForFailedStatusChange($dimensions);

                break;
        }
    }

    public function setError($errorCode, $errorDesc, $internalErrorCode)
    {
        $this->setAttribute(self::ERROR_CODE, $errorCode);
        $this->setAttribute(self::ERROR_DESCRIPTION, $errorDesc);
        $this->setAttribute(self::INTERNAL_ERROR_CODE, $internalErrorCode);
    }

    public function setErrorNull()
    {
        $this->setAttribute(self::ERROR_CODE, null);
        $this->setAttribute(self::INTERNAL_ERROR_CODE, null);
        $this->setAttribute(self::ERROR_DESCRIPTION, null);
    }

    public function setStatusProcessed()
    {
        $this->setStatus(Status::PROCESSED);

        if ($this->getProcessedAt() === null)
        {
            $timestamp = time();
            $this->setProcessedAt($timestamp);
        }

        $this->setErrorNull();
    }

    public function setProcessedAt($timestamp)
    {
        $this->setAttribute(self::PROCESSED_AT, $timestamp);
    }

    public function setFee(int $fee)
    {
        assertTrue($fee >= 0);

        $this->setAttribute(self::FEE, $fee);
    }

    public function setTax(int $tax)
    {
        assertTrue($tax >= 0);

        $this->setAttribute(self::TAX, $tax);
    }

    public function setBaseAmount()
    {
        $amount = $this->getAttribute(self::AMOUNT);

        $unrefundedAmount = $this->payment->getAmountUnrefunded();

        if ($amount === $unrefundedAmount)
        {
            $baseAmount = $this->payment->getBaseAmountUnrefunded();
        }
        else
        {
            $conversionRate = $this->payment->getCurrencyConversionRate();

            $baseAmount = $amount * $conversionRate;

            $baseAmount = (int) floor($baseAmount);
        }

        $this->setAttribute(self::BASE_AMOUNT, $baseAmount);
    }

    public function incrementAttempts()
    {
        $attempts = $this->getAttribute(self::ATTEMPTS);

        $this->setAttribute(self::ATTEMPTS, $attempts + 1);

        $this->setAttribute(self::LAST_ATTEMPTED_AT, $this->freshTimestamp());
    }

    public function setLastAttemptedAt()
    {
        $this->setAttribute(self::LAST_ATTEMPTED_AT, $this->freshTimestamp());
    }

    public function setPublicPaymentIdAttribute(array & $array)
    {
        $array[self::PAYMENT_ID] =
            Payment\Entity::getIdPrefix() . $this->getAttribute(self::PAYMENT_ID);
    }

    public function setPublicAcquirerDataAttribute(array & $array)
    {
        $array[self::ACQUIRER_DATA] = $this->getAttribute(self::ACQUIRER_DATA);
    }

    public function setReference1(string $value)
    {
        $this->setAttribute(self::REFERENCE1, $value);
    }

    public function setReference2(string $value)
    {
        $this->setAttribute(self::REFERENCE2, $value);
    }

    public function setReceipt(string $value)
    {
        $this->setAttribute(self::RECEIPT, $value);
    }

    public function setUtr(string $value = null)
    {
        $this->setAttribute(self::REFERENCE1, $value);
    }

    public function setRemarks(string $value = null)
    {
        $this->setAttribute(self::REFERENCE2, $value);
    }

    public function setBatchFundTransferId($value)
    {
        $this->setAttribute(self::BATCH_FUND_TRANSFER_ID, $value);
    }

    /**
     * Setting is_scrooge attribute of refund entity to true or false.
     * True means refund is processed by Scrooge Service.
     *
     * @param bool $isScrooge
     */
    public function setIsScrooge($isScrooge = false)
    {
        $this->setAttribute(self::IS_SCROOGE, $isScrooge);
    }

    /**
     * This is required for the FTA module.
     * FTA requires the sources to implement `isStatusFailed`
     * function, to send out summary emails and stuff in bulkRecon.
     *
     * @return bool
     */
    public function isStatusFailed()
    {
        return ($this->getStatus() === Status::FAILED);
    }

    /**
     * This is required for the Refund reversal module -
     * Flipkart changes
     *
     * @return bool
     */
    public function isStatusReversed()
    {
        return ($this->getStatus() === Status::REVERSED);
    }

    /**
     * This is required for checking if a refund is being processed with instant (that is charged) speed
     *
     * @return bool
     */
    public function isRefundSpeedInstant(): bool
    {
        return (in_array($this->getSpeedRequested(), Speed::REFUND_INSTANT_SPEEDS) === true);
    }

    public function getGateway()
    {
        $gateway = $this->getAttribute(self::GATEWAY);

        if ($gateway === null)
        {
            return $this->relations['payment']->getGateway();
        }

        return $gateway;
    }

    public function getBatchId()
    {
        return $this->getAttribute(self::BATCH_ID);
    }

    public function setAttempts($value)
    {
        $this->setAttribute(self::ATTEMPTS, $value);
    }

    // ----------------------- Mutator ---------------------------------------------

    protected function setReference1Attribute($reference1)
    {
        $trimmedReference1 = (blank($reference1) === true) ? null : trim($reference1);

        $this->attributes[self::REFERENCE1] =  $trimmedReference1;
    }

    protected function setReference2Attribute($reference2)
    {
        $trimmedReference2 = (blank($reference2) === true) ? null : trim($reference2);

        $this->attributes[self::REFERENCE2] =  $trimmedReference2;
    }

    // ----------------------- Mutator Ends ----------------------------------------

    /**
     * Adds the contact, email fields to the reports
     */
    public function toArrayReport()
    {
        $data = parent::toArrayReport();

        $data[Payment\Entity::CONTACT] = $this->payment->getContact();
        $data[Payment\Entity::EMAIL]   = $this->payment->getEmail();

        return $data;
    }

    public function toArrayPublicCustomer(): array
    {
        $data = parent::toArrayPublicCustomer();

        $data['merchant_name'] = $this->merchant->getBillingLabel();

        $data[self::STATUS] = (($this->isProcessed() === true) ? Status::PROCESSED : Status::INITIATED);

        return $data;
    }

    public function toArrayGateway()
    {
        $data = $this->toArray();

        if (($this->payment->isCard() === true) and
            ($this->payment->getConvertCurrency() === true))
        {
            $data[self::AMOUNT]   = $this->getBaseAmount();
            $data[self::CURRENCY] = Currency\Currency::INR;
        }

        return $data;
    }

    public function getTimeFromCreatedInMinutes(): int
    {
        return intval(($this->freshTimestamp() - $this->getCreatedAt()) / 60);
    }

    public function getLastAttemptToProcessedTimeInMinutes(): int
    {
        return intval(($this->freshTimestamp() - $this->getLastAttemptedAt()) / 60);
    }

    public function getCapturedToCreateTimeInMinutes(): int
    {
        return intval(($this->getCreatedAt() - $this->payment->getCapturedAt()) / 60);
    }

    public function getAuthorizedToCreateTimeInMinutes(): int
    {
        return intval(($this->getCreatedAt() - $this->payment->getAuthorizeTimestamp()) / 60);
    }

    protected function pushMetricsForProcessedStatusChange(array $dimensions)
    {
        if ($this->isProcessed() === false)
        {
            app('trace')->histogram(
                RefundMetric::REFUND_PROCESSED_FROM_CREATED_MINUTES,
                $this->getTimeFromCreatedInMinutes(),
                $dimensions
            );
        }
        else if ($this->isStatusFailed() === true)
        {
            app('trace')->histogram(
                RefundMetric::REFUND_PROCESSED_FROM_LAST_FAILED_ATTEMPT_MINUTES,
                $this->getLastAttemptToProcessedTimeInMinutes(),
                $dimensions
            );
        }
    }

    protected function pushMetricsForFailedStatusChange(array $dimensions)
    {
        if ($this->isStatusFailed() === false)
        {
            app('trace')->count(RefundMetric::REFUND_FAILED_TOTAL, $dimensions);
        }
    }

    protected function getPublicStatus($response, $publicStatusFeatureEnabled = false)
    {
        $refundStatus = $this->getStatus();

        $publicStatusMap = [
            Status::PROCESSED => Status::PROCESSED,
            Status::REVERSED  => Status::FAILED,
        ];

        $response[self::STATUS] = $publicStatusMap[$refundStatus] ?? Status::PENDING;

        $isScrooge = Payment\Gateway::isScroogeGatewayAndMerchant($this->getGateway());

        if (($response[self::STATUS] === Status::PENDING) and
            ($isScrooge === true) and
            ((Payment\Refund\Core::fetchPublicStatusFromScrooge($this->getMerchantId()) === true) or
             ($publicStatusFeatureEnabled === true)))
        {
            $app   = App::getFacadeRoot();
            $trace = $app['trace'];

            try
            {
                $scroogeResponse = $app['scrooge']->getPublicRefund($response[self::ID]);

                $scroogeResponseCode = $scroogeResponse[self::RESPONSE_CODE];

                if (in_array($scroogeResponseCode, [200, 201, 204], true) === true)
                {
                    $scroogeStatus = $scroogeResponse[self::RESPONSE_BODY]->status;

                    if (empty($scroogeStatus) === false)
                    {
                        $response[self::STATUS] = $scroogeStatus;
                    }
                }
            }
            catch(\Throwable $e)
            {
                $trace->traceException(
                    $e,
                    Trace::WARNING,
                    TraceCode::SCROOGE_GET_REFUND_STATUS_REQUEST_FAILED,
                    [
                        'refund_id' => $response[self::ID],
                    ]);
            }
        }

        return $response;
    }

    /**
     * Overriding this function to get public refund status from scrooge
     *
     * @return array
     */
    public function toArrayPublic()
    {
        $response = parent::toArrayPublic();

        $displayRefundPublicStatus = Payment\Refund\Core::isRefundsPublicStatusMerchant($this->getMerchantId());

        $publicStatusFeatureEnabled = $this->merchant->isFeatureEnabled(Feature::SHOW_REFUND_PUBLIC_STATUS);
        $cardTransferRefundFeatureEnabled = $this->merchant->isFeatureEnabled(Feature::CARD_TRANSFER_REFUND);

        if (($displayRefundPublicStatus === true) or
            ($publicStatusFeatureEnabled === true) or
            ($cardTransferRefundFeatureEnabled === true))
        {
            $scroogeResponse = $this->getPublicStatus($response, $publicStatusFeatureEnabled);

            return $scroogeResponse;
        }

        return $response;
    }
}
