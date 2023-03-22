<?php

namespace RZP\Models\Payment\Refund;

use App;
use ApiResponse;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Method;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Currency;
use RZP\Models\Reversal;
use RZP\Models\Transaction;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Models\Base\Traits\NotesTrait;
use Razorpay\Spine\DataTypes\Dictionary;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Payment\Refund\Metric as RefundMetric;
use RZP\Models\Payment\PaymentMeta\MismatchAmountReason;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

/**
 * @property Payment\Entity     $payment
 * @property Transaction\Entity $transaction
 * @property MerchantEntity    $merchant
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
    const GATEWAY_AMOUNT         = 'gateway_amount';
    const GATEWAY_CURRENCY       = 'gateway_currency';
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

    const TERMINAL_ID            = 'terminal_id';
    const OPTIMIZER_PROVIDER     = 'optimizer_provider';

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
    const SPEED_DECISIONED       = 'speed_decisioned';
    const FEE                    = 'fee';
    const TAX                    = 'tax';

    // This is only a virtual attribute, not stored in the refund entity in the DB -
    // only being used for pricing and passing to Scrooge
    const MODE_REQUESTED   = 'mode_requested';
    const MODE             = 'mode';
    const SPEED            = 'speed';
    const PROCESSED_SOURCE = 'processed_source';

    // Relations
    const TRANSACTION           = 'transaction';

    const PUBLIC_STATUS = 'public_status';

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
        self::GATEWAY_AMOUNT,
        self::GATEWAY_CURRENCY,
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
        self::MODE_REQUESTED,
        self::SPEED_DECISIONED,
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
        self::BATCH_ID,
    ];

    protected $reconAppInternal = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::PAYMENT_ID,
    ];

    protected $publicCustomer = [
        self::ID,
        self::AMOUNT,
        self::PAYMENT_ID,
        self::ACQUIRER_DATA,
        self::CREATED_AT,
        self::CURRENCY,
    ];

    protected $hiddenInReport = [self::ACQUIRER_DATA];

    protected $defaults = [
        self::NOTES             => [],
        self::STATUS            => Status::CREATED,
        self::SPEED_REQUESTED   => Speed::NORMAL,
        self::SPEED_DECISIONED  => Speed::NORMAL,
        self::SPEED_PROCESSED   => null,
        self::GATEWAY_REFUNDED  => null,
        self::ATTEMPTS          => null,
        self::LAST_ATTEMPTED_AT => null,
        self::PROCESSED_AT      => null,
        self::IS_SCROOGE        => 0,
        self::RECEIPT           => null,
        self::FEE               => 0,
        self::TAX               => 0,
        self::BATCH_ID          => null,
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
        self::GATEWAY_AMOUNT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::LAST_ATTEMPTED_AT,
        self::PROCESSED_AT,
    ];

    /**
     * Relations to be returned when receiving expand[] query param in fetch
     * (eg. transaction, transaction.settlement with payment fetch)
     *
     * @var array
     */
    protected $expanded = [
        self::TRANSACTION,
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

    /**
     * @param array $response
     * @return mixed
     */
    public function processArrayPublicAndReturn(array $response)
    {
        $app = \App::getFacadeRoot();

        if ($app['basicauth']->isOptimiserDashboardRequest() === true)
        {
            $response[self::SETTLED_BY] = $this->getSettledBy();
            if (isset($response[self::SETTLED_BY]) && $response[self::SETTLED_BY] == 'Razorpay') {
                $response[self::OPTIMIZER_PROVIDER]  = 'Razorpay';
            } else {
                $response[self::OPTIMIZER_PROVIDER] = $this->payment->getTerminalId();
            }
        }


        $refundPublicStatusFeatureEnabled = $this->merchant->isFeatureEnabled(Feature::SHOW_REFUND_PUBLIC_STATUS);
        $refundPendingStatusFeatureEnabled = $this->merchant->isFeatureEnabled(Feature::REFUND_PENDING_STATUS);

        $data = [
            Constants::REFUND_PUBLIC_STATUS_FEATURE_ENABLED => $refundPublicStatusFeatureEnabled,
            Constants::REFUND_PENDING_STATUS_FEATURE_ENABLED => $refundPendingStatusFeatureEnabled,
        ];

        $exposeExtraAttributes = (new Merchant\Core())->isShowRefundTypeParamFeatureEnabled($this->merchant);

        $app['trace']->info(TraceCode::MERCHANT_FEATURE_NOT_EXIST,
            [
                'merchant has extra attributes exposed' => $exposeExtraAttributes,
            ]);

        if ($exposeExtraAttributes === true)
        {
            $response[self::PROCESSED_AT] = $this->getProcessedAt();

            $response[RefundConstants::REFUND_TYPE] = (new Core)->getRefundType($this->getId(), $this->merchant, $this->getBatchId(), $this->isScrooge());
        }

        return $this->getPublicStatus($response, $data);
    }

//    public function getOptimizerProvider(string $id) {
//
//        $app   = App::getFacadeRoot();
//        $trace = $app['trace'];
//
//        try
//        {
//            return $app['scrooge']->getRefundTerminalId($id);
//        }
//        catch(\Throwable $e)
//        {
//            $trace->traceException(
//                $e,
//                Trace::WARNING,
//                TraceCode::SCROOGE_GET_REFUND_TERMINAL_ID_REQUEST_FAILED,
//                [
//                    'refund_id' => $id,
//                ]);
//            return '';
//        }
//    }

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

    public function getGatewayAmount()
    {
        $gatewayAmount = $this->getAttribute(self::GATEWAY_AMOUNT);

        return (($gatewayAmount !== null) and ($gatewayAmount > 0)) ?
            $gatewayAmount : $this->getAmount();
    }

    public function getGatewayCurrency()
    {
        $gatewayCurrency = $this->getAttribute(self::GATEWAY_CURRENCY);

        return ($gatewayCurrency !== null) ? $gatewayCurrency : $this->getCurrency();
    }

    /**
     * Returns base amount + applicable fee
     * In case of a merchant with fee model as postpaid
     * we do not add the fee since it will be collected at the end of the month
     */
    public function getNetAmount()
    {
        $netAmount = $this->getBaseAmount();

        if ($this->merchant->isPostpaid() === false)
        {
            $netAmount += $this->getFee();
        }

        return $netAmount;
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

    // This is only a virtual attribute, not stored in the refund entity in the DB -
    // only being used for pricing and passing to Scrooge
    public function getModeRequested()
    {
        return $this->getAttribute(self::MODE_REQUESTED);
    }

    public function getSpeedDecisioned()
    {
        return $this->getAttribute(self::SPEED_DECISIONED);
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

    public function getReference3()
    {
        return $this->getAttribute(self::REFERENCE3);
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

    public function getOptimiserProvider()
    {
        $app = \App::getFacadeRoot();

        try{
            if($app['basicauth']->isOptimiserDashboardRequest() === true)
            {
                if($this->getSettledBy() == 'Razorpay' )
                {
                    return 'Razorpay';
                }
                else
                {
                    return $this->payment->getTerminalId();
                }
            }
        } catch(\Throwable $e)
        {
            $app['trace']->traceException(
                $e,
                Trace::WARNING,
                TraceCode::OPTIMISER_PROVIDER_FETCH_FAILED,
                [
                    'refund_id' => $this->getId(),
                ]);
        }
        return '';
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

    public function getPaymentAttribute()
    {
        if ($this->relationLoaded('payment') === true)
        {
            return $this->getRelation('payment');
        }

        if (empty($this->payment()->first()) === false)
        {
            return $this->payment()->first();
        }

        $payment = (new Payment\Repository)->findOrFailPublic($this->getPaymentId());

        $this->payment()->associate($payment);

        return $payment;
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

    public function getGatewayRefunded()
    {
        return $this->getAttribute(self::GATEWAY_REFUNDED);
    }

    public function getTerminalId()
    {
        return $this->payment->getTerminalId();
    }

    public function getReversalId()
    {
        return $this->getAttribute(self::REVERSAL_ID);
    }

    protected function getAcquirerDataAttribute()
    {
        $acquirerData = [];

        $payment = $this->payment;

        switch ($payment->getMethod())
        {
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

            default:
                $acquirerData = [
                    self::ARN  => $this->getAttribute(self::REFERENCE1)
                ];
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

    // This is only a virtual attribute, not stored in the refund entity in the DB -
    // only being used for pricing and passing to Scrooge
    public function setModeRequested(string $modeRequested)
    {
        $this->setAttribute(self::MODE_REQUESTED, $modeRequested);
    }

    public function setSpeedDecisioned(string $speedDecisioned)
    {
        $this->setAttribute(self::SPEED_DECISIONED, $speedDecisioned);
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
        //
        //
        // This terminal was deleted due to Yesbank moratorium
        // This particular terminal is not a direct settlement terminal
        // Will be removing this check once the terminal is fixed.
        //
        // Slack thread for reference:
        // https://razorpay.slack.com/archives/CA66F3ACS/p1584100168218900?thread_ts=1584090894.210900&cid=CA66F3ACS
        //
        if ($this->payment->getTerminalId() === 'B2K2t8JD9z98vh')
        {
            return false;
        }

        if (($this->payment->hasTerminal() === true) and
            ($this->payment->terminal->isDirectSettlementWithRefund() === true))
        {
            return true;
        }

        return false;
    }

    public function isDirectSettlementWithoutRefund(): bool
    {
        if (($this->payment->hasTerminal() === true) and
            ($this->payment->terminal->isDirectSettlementWithoutRefund() === true))
        {
            return true;
        }

        return false;
    }

    public function isPos(): bool
    {
        if (($this->payment->hasTerminal() === true) and
            ($this->payment->terminal->isPos() === true))
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

    public function setPaymentId($paymentId)
    {
        $this->setAttribute(self::PAYMENT_ID, $paymentId);
    }

    public function setTax(int $tax)
    {
        assertTrue($tax >= 0);

        $this->setAttribute(self::TAX, $tax);
    }

    public function setGatewayAmountCurrency()
    {
        $this->setGatewayAmount();

        $this->setGatewayCurrency();
    }

    private function setGatewayAmount()
    {
        $gatewayAmount = null;

        if ($this->payment->isDCC() === true)
        {
            $paymentMeta = $this->payment->paymentMeta;

            $forexRate = $paymentMeta->getForexRate();

            $markUpPercent = $paymentMeta->getDccMarkUpPercent();

            $denominationFactorInputCurr = Currency\Currency::DENOMINATION_FACTOR[$this->payment->getCurrency()];

            $denominationFactorMerchantCurrency = Currency\Currency::DENOMINATION_FACTOR[$paymentMeta->getGatewayCurrency()];

            $denominationFactor = $denominationFactorMerchantCurrency / $denominationFactorInputCurr;

            // multiplying with denomination factor is required as now we are supporting 3 decimal currencies.
            // In case base amount is in KWD (denomination 1000) and convert currency is USD (denomination 100)
            // $denominationFactor will be 0.1
            $convertedAmount = $this->getAmount() * $forexRate * $denominationFactor;

            $gatewayAmount = (int) floor($convertedAmount + (($markUpPercent * $convertedAmount) / 100));
        }

        if ($this->payment->isUpiAndAmountMismatched() === true)
        {
            $gatewayAmount = $this->getGatewayAmountForAmountMismatch($this, $this->payment);
        }

        // Temp requirement asked by cred, sending the cash component of the total amount to gateway.
        if ($this->payment->isAppCred() === true)
        {
            $gatewayAmount = $this->getDiscountedRefundAmountIfApplicable();
        }

        // HDFC VAS Surcharge - Direct settlement - Customer fee bearer
        if ($this->payment->isHdfcVasDSCustomerFeeBearerSurcharge() === true)
        {
            /*
             * We refund upto a maximum of the original gateway amount.
             * If the refund amount being requested + amount already refunded > gateway amount, 0 amt will be refunded.
             */
            $maxGatewayAmount = $this->payment->getGatewayAmount() - $this->payment->getAmountRefunded();

            if($maxGatewayAmount < 0)
            {
                $maxGatewayAmount = 0;
            }

            $gatewayAmount = $this->getAmount();

            // This is as per the product requirements.
            // https://razorpay.slack.com/archives/C01D04KGYP8/p1625133817268800?thread_ts=1624255989.087000&cid=C01D04KGYP8
            if($gatewayAmount > $maxGatewayAmount)
            {
                $gatewayAmount = $maxGatewayAmount;
            }
        }

        $this->setAttribute(self::GATEWAY_AMOUNT, $gatewayAmount);
    }

    /**
     * Get gateway amount for amount mismatched payment
     * @param Entity $refund
     * @param Payment\Entity $payment
     * @return mixed
     */
    public function getGatewayAmountForAmountMismatch(Payment\Refund\Entity $refund, Payment\Entity $payment)
    {
        $gatewayAmount = null;

        $paymentMeta = $payment->paymentMeta;

        $mismatchAmount = $paymentMeta->getMismatchAmount();

        $mismatchReason = $paymentMeta->getMismatchAmountReason();

        $refund_amount = $refund->getAmount();

        $balance = $payment->getAmountUnrefunded();

        $gatewayAmount =  $refund_amount;

        if ($mismatchReason === MismatchAmountReason::CREDIT_DEFICIT)
        {
            if ($balance === $refund_amount)
            {
                $gatewayAmount =  $refund_amount - $mismatchAmount;
            }
        }
        else
        {
            if ($balance === $refund_amount)
            {
                $gatewayAmount =  $refund_amount + $mismatchAmount;
            }
        }

        return $gatewayAmount;
    }

    private function setGatewayCurrency()
    {
        $gatewayCurrency = null;

        if ($this->payment->isDCC() === true)
        {
            $gatewayCurrency =  $this->payment->paymentMeta->getGatewayCurrency();
        }

        if ($this->payment->isUpiAndAmountMismatched() === true)
        {
            //as these are UPI payments the currency is always INR
            $gatewayCurrency =  'INR';
        }

        if ($this->payment->isAppCred() === true)
        {
            // since cred is only for INR
            $gatewayCurrency = Currency\Currency::INR;
        }

        if($this->payment->isHdfcVasDSCustomerFeeBearerSurcharge())
        {
            $gatewayCurrency = Currency\Currency::INR;
        }

        $this->setAttribute(self::GATEWAY_CURRENCY, $gatewayCurrency);
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

    // Original setBaseAmount setter does custom calculation. This can be used to avoid that
    public function setRawBaseAmount(int $baseAmount)
    {
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

    public function setReference1(string $value = null)
    {
        $this->setAttribute(self::REFERENCE1, $value);
    }

    public function setReference2(string $value)
    {
        $this->setAttribute(self::REFERENCE2, $value);
    }

    public function setReference3(string $value)
    {
        $this->setAttribute(self::REFERENCE3, $value);
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

    public function setBatchId($value)
    {
        $this->setAttribute(self::BATCH_ID, $value);
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
     * This is required for checking if a refund's requested speed is an instant (that is charged) speed
     *
     * @return bool
     */
    public function isRefundRequestedSpeedInstant(): bool
    {
        return (in_array($this->getSpeedRequested(), Speed::REFUND_INSTANT_SPEEDS, true) === true);
    }

    /**
     * This is required for checking if a refund is being processed with instant (that is charged) speed
     *
     * @return bool
     */
    public function isRefundSpeedInstant(): bool
    {
        return (in_array($this->getSpeedDecisioned(), Speed::REFUND_INSTANT_SPEEDS, true) === true);
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

    public function getReceipt()
    {
        return $this->getAttribute(self::RECEIPT);
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

        if ((new Merchant\Core())->isShowRefundTypeParamFeatureEnabled($this->merchant) === true)
        {
            $data[self::PROCESSED_AT] = $this->getProcessedAt();
            $data['refund_type'] = (new Core)->getRefundType($this->getId(), $this->merchant, $this->getBatchId(), $this->isScrooge());
        }

        return $data;
    }

    public function toArrayPublicCustomer(bool $populateMessages = false): array
    {
        $data = parent::toArrayPublicCustomer();

        $data['merchant_name'] = $this->merchant->getBillingLabel();

        $data[self::STATUS] = (($this->isProcessed() === true) ? Status::PROCESSED : Status::INITIATED);

        if ($populateMessages === true)
        {
            $this->populateMessages($data);
        }

        return $data;
    }

    public function toArrayRecon()
    {
        $attributes = parent::toArrayRecon();

        $attributes[self::MERCHANT_ID] = $this->getMerchantId();

        return $attributes;
    }

    /**
     * @param array $data
     * @return array
     */
    private function populateMessages(array &$data): array
    {
        $transactionTrackerMessages = new TransactionTrackerMessages();

        $createdAtDate = Carbon::createFromTimestamp($this->getCreatedAt(), Timezone::IST);

        $expectedDate = Holidays::getNthWorkingDayFrom($createdAtDate, $transactionTrackerMessages::REFUND_SLA_DAYS, true);

        $currentDate = Carbon::now(Timezone::IST);

        $data[Constants::MERCHANT_ID] = $this->merchant->getPublicId();

        $data[Constants::DAYS] = $expectedDate->diffInDays($currentDate, false);

        $data[Constants::PRIMARY_MESSAGE]   =
            $this->getMessageForTransactionTracker(
                $transactionTrackerMessages,
                $data[Constants::DAYS],
                $expectedDate,
                TransactionTrackerMessages::PRIMARY
            );

        $data[Constants::SECONDARY_MESSAGE] =
            $this->getMessageForTransactionTracker(
                $transactionTrackerMessages,
                $data[Constants::DAYS],
                $expectedDate,
                TransactionTrackerMessages::SECONDARY
            );

        $data[Constants::TERTIARY_MESSAGE]  =
            $this->getMessageForTransactionTracker(
                $transactionTrackerMessages,
                $data[Constants::DAYS],
                $expectedDate,
                TransactionTrackerMessages::TERTIARY
            );

        return $data;
    }

    public function toArrayGateway()
    {
        $data = $this->toArray();

        if ($this->payment->isCard() === true)
        {
            $data[self::AMOUNT]   = $this->getGatewayAmount();
            $data[self::CURRENCY] = $this->getGatewayCurrency();
        }

        if ($this->payment->getGateway() === Payment\Gateway::WALLET_PAYPAL)
        {
            $data[self::AMOUNT]   = $this->getGatewayAmount();
            $data[self::CURRENCY] = $this->getGatewayCurrency();
        }

        if ($this->payment->getGateway() === Payment\Gateway::EMERCHANTPAY)
        {
            $data[self::AMOUNT]   = $this->getGatewayAmount();
            $data[self::CURRENCY] = $this->getGatewayCurrency();
        }

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

    public function wasGatewayRefundNotSupportedAtCreation($refundId): bool
    {
        $app = App::getFacadeRoot();

        $scroogeRefund = $app['scrooge']->getRefund($refundId);

        if ((isset($scroogeRefund[RefundConstants::RESPONSE_BODY]) === true) and
            (isset($scroogeRefund[RefundConstants::RESPONSE_BODY][RefundConstants::META]) === true) and
            (isset($scroogeRefund[RefundConstants::RESPONSE_BODY][RefundConstants::META][RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND]) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * @param TransactionTrackerMessages $transactionTrackerMessages
     * @param int $days
     * @param Carbon $expectedDate
     * @param $messageType
     * @return string
     */
    private function getMessageForTransactionTracker(TransactionTrackerMessages $transactionTrackerMessages, int $days, Carbon $expectedDate, $messageType): string
    {
        $messageLateAuth = null;
        $messageEntity = Constants::REFUND;
        $messageSlaDone = ($days < 0) ? false : true;
        $messageStatus = ($this->isProcessed() === true) ? Status::PROCESSED: Status::INITIATED;
        $messageVoidRefund = !$this->payment->isGatewayCaptured();

        if (($this->isStatusReversed() === true) and
            ($this->getSpeedDecisioned() === Speed::INSTANT) and
            ($this->wasGatewayRefundNotSupportedAtCreation($this->getId()) === true))
        {
            $messageStatus = RefundConstants::FAILED_AGED;

            $messageSlaDone = false;
        }

        $message = $transactionTrackerMessages->getMessage($messageEntity, $messageStatus, $messageType, $messageSlaDone, $messageLateAuth, $messageVoidRefund);

        return $this->populateTransactionTrackerMessages($message, $expectedDate);
    }

    /**
     * @param $message
     * @param Carbon $expectedDate
     * @return mixed
     */
    private function populateTransactionTrackerMessages($message, Carbon $expectedDate)
    {
        $populatedMessage = $message;

        $replacer = [
            TransactionTrackerMessages::MESSAGE_AMOUNT        => $this->getFormattedAmount(),
            TransactionTrackerMessages::MESSAGE_MERCHANT_NAME => $this->merchant->getBillingLabel(),
            TransactionTrackerMessages::MESSAGE_EXPECTED_DATE => $expectedDate->toFormattedDateString(),
        ];

        foreach ($replacer as $key => $value)
        {
            $populatedMessage = str_replace($key, $value, $populatedMessage);
        }

        return $populatedMessage;
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

    protected function getPublicStatus($response, array $data = [])
    {
        $refundPublicStatusFeatureEnabled  = $data[Constants::REFUND_PUBLIC_STATUS_FEATURE_ENABLED] ?? false;
        $refundPendingStatusFeatureEnabled = $data[Constants::REFUND_PENDING_STATUS_FEATURE_ENABLED] ?? false;

        $refundStatus = $this->getStatus();

        $publicStatusMap = [
            Status::PROCESSED => Status::PROCESSED,
            Status::REVERSED  => Status::FAILED,
        ];

        $response[self::STATUS] = $publicStatusMap[$refundStatus] ?? Status::PENDING;

        $callScroogeForSpeed = true;

        // If speed_processed is already populated in the refund entity - we need not call scrooge
        if (empty($this->getSpeedProcessed()) === false)
        {
            $response[self::SPEED_PROCESSED] = $this->getSpeedProcessed();

            $callScroogeForSpeed = false;
        }
        // Populating default values in case scrooge does not return proper response
        else if ($this->isRefundSpeedInstant() === true)
        {
            $response[self::SPEED_PROCESSED] = Speed::INSTANT;
        }
        else
        {
            $response[self::SPEED_PROCESSED] = Speed::NORMAL;
        }

        $response[self::SPEED_REQUESTED] = $this->getSpeedRequested();

        $eligibleForScroogeCall = ($response[self::STATUS] === Status::PENDING) and ($this->isScrooge() === true);

        $callScroogeForStatus = ($refundPublicStatusFeatureEnabled === true);

        if (($eligibleForScroogeCall === true) and
            (($callScroogeForStatus === true) or ($callScroogeForSpeed === true)))
        {
            $app   = App::getFacadeRoot();
            $trace = $app['trace'];

            $queryParams = [
                self::SPEED  => (int) $callScroogeForSpeed,
                self::STATUS => (int) $callScroogeForStatus,
            ];

            try
            {
                $scroogeResponse = $app['scrooge']->getPublicRefund($response[self::ID], $queryParams);

                $scroogeResponseCode = $scroogeResponse[Constants::RESPONSE_CODE];

                if (in_array($scroogeResponseCode, [200, 201, 204], true) === true)
                {
                    $scroogeResponseBody = $scroogeResponse[Constants::RESPONSE_BODY];

                    $scroogeStatus =
                        (empty($scroogeResponseBody[self::STATUS]) === false) ? $scroogeResponseBody[self::STATUS] : '';

                    $scroogeSpeed =
                        (empty($scroogeResponseBody[self::SPEED]) === false) ? $scroogeResponseBody[self::SPEED] : '';

                    if (empty($scroogeStatus) === false)
                    {
                        $response[self::STATUS] = $scroogeStatus;
                    }

                    if (empty($scroogeSpeed) === false)
                    {
                        $response[self::SPEED_PROCESSED] = $scroogeSpeed;
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

        if (($refundPublicStatusFeatureEnabled === false) and
            ($refundPendingStatusFeatureEnabled === false) and
            ($response[self::STATUS] === Status::PENDING) and
            ($response[self::SPEED_PROCESSED] === Speed::NORMAL))
        {
            $response[self::STATUS] = Status::PROCESSED;
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

        return $this->processArrayPublicAndReturn($response);
    }

    /**
     * Overriding this function to get public expanded refund status from scrooge
     *
     * @return array
     */
    public function toArrayPublicWithExpand()
    {
        $response = parent::toArrayPublicWithExpand();

        return $this->processArrayPublicAndReturn($response);
    }

    /**
     * Checks whether a discount should be applied to the original refund amount.
     */
    private function getDiscountedRefundAmountIfApplicable()
    {
        $amount = $this->getBaseAmount();

        $discountRatio = $this->payment->getDiscountRatioIfApplicable();

        return ($amount - (int)(round($amount * $discountRatio)));
    }
}
