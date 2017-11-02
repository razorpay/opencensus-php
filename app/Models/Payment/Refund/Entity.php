<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Currency;
use RZP\Models\Transaction\Channel;
use RZP\Models\Base\Traits\NotesTrait;
use Razorpay\Spine\DataTypes\Dictionary;

/**
 * @property Payment\Entity $payment
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const PAYMENT_ID             = 'payment_id';
    const AMOUNT                 = 'amount';
    const CURRENCY               = 'currency';
    const BASE_AMOUNT            = 'base_amount';
    const STATUS                 = 'status';
    const NOTES                  = 'notes';

    //merchant reference number for refund if provided by merchant
    const RECEIPT                = 'receipt';

    const TRANSACTION_ID         = 'transaction_id';
    const BATCH_FUND_TRANSFER_ID = 'batch_fund_transfer_id';
    const BATCH_ID               = 'batch_id';

    const GATEWAY_REFUNDED       = 'gateway_refunded';
    const REFERENCE1             = 'reference1';
    const REFERENCE2             = 'reference2';
    const ATTEMPTS               = 'attempts';
    const LAST_ATTEMPTED_AT      = 'last_attempted_at';

    const ACQUIRER_DATA          = 'acquirer_data';
    const ARN                    = 'arn';


    protected static $sign = 'rfnd';

    protected $entity = 'refund';

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID,
        self::AMOUNT,
        self::CURRENCY
    ];

    protected $fillable = [
        self::MERCHANT_ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::RECEIPT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::BASE_AMOUNT,
        self::STATUS,
        self::GATEWAY_REFUNDED,
        self::NOTES,
        self::RECEIPT,
        self::TRANSACTION_ID,
        self::BATCH_ID,
        self::ARN,
        self::ACQUIRER_DATA,
        self::ATTEMPTS,
        self::LAST_ATTEMPTED_AT,
        self::CREATED_AT,
        self::UPDATED_AT
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
        self::CREATED_AT
    ];

    protected $hiddenInReport = [self::ACQUIRER_DATA];

    protected $defaults = [
        self::NOTES             => [],
        self::STATUS            => Status::CREATED,
        self::GATEWAY_REFUNDED  => null,
        self::ATTEMPTS          => null,
        self::LAST_ATTEMPTED_AT => null,
        self::RECEIPT           => null,
    ];

    protected $casts = [
        self::AMOUNT           => 'int',
        self::BASE_AMOUNT      => 'int',
        self::GATEWAY_REFUNDED => 'bool',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_ID,
        self::ARN,
        self::ACQUIRER_DATA
    ];

    protected $amounts = [
        self::AMOUNT,
        self::BASE_AMOUNT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::LAST_ATTEMPTED_AT,
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

    public function billdesk()
    {
        return $this->hasOne('RZP\Gateway\Billdesk\Entity');
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

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getBaseAmount()
    {
        return $this->getAttribute(self::BASE_AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function isGatewayRefunded()
    {
        return ($this->getAttribute(self::GATEWAY_REFUNDED) === true);
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

    public function getAttempts()
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function getReference1()
    {
        return $this->getAttribute(self::REFERENCE1);
    }

    public function getAcquirerData()
    {
        return $this->getAttribute(self::ACQUIRER_DATA);
    }

    public function getChannel()
    {
        return Channel::KOTAK;
    }

    public function getFees()
    {
        return 0;
    }

    public function getTax()
    {
        return 0;
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
        }

        return (new Dictionary($acquirerData));
    }

    public function setGatewayRefunded($gatewayRefunded)
    {
        $this->setAttribute(self::GATEWAY_REFUNDED, $gatewayRefunded);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setStatusProcessed()
    {
        $this->setAttribute(self::STATUS, Status::PROCESSED);
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
        //
        // 'test merchant', 'ABOF', 'Nykaa',
        // '1mg', 'Playo', 'Nestaway',
        // 'RailYatri', 'Treebo', 'Goibibo',
        // 'Goeventz', 'RentoMojo', 'Voonik',
        // 'Zomato', 'Swiggy', 'Yatra'
        // 'Mr Button'

        $merchantIds = [
            '10000000000000', '6gn7Xc2gqK40c9', '4uObL8AHBqFNnP',
            '6e9vU1F6c16Wgy', '6LCgLZgRjTI8ws', '4IAipsLXQZ8HfL',
            '5yvFZKqbBjEBsr', '3d2EGdZF6CAYVc', '6ZLE5BE57SExGF',
            '6B94xSUfS76yht', '4bnk7yysqr5Wx5', '4zGGr9ZwCTH1gh',
            '6H7N6hlcv29OMG', '8S0i1kWYyF2woQ', '87qTXzFTBLFN7i',
            '5PKFA3s9dpIwPn'
        ];

        $currentMerchantId = $this->getMerchantId();

        // We are hardcoding the merchant ids for now.
        // Will move this to feature flag.
        if (in_array($currentMerchantId, $merchantIds, true) === true)
        {
            $array[self::ACQUIRER_DATA] = $this->getAttribute(self::ACQUIRER_DATA);
        }
    }

    public function setPublicArnAttribute(array & $array)
    {
        $array[self::ARN] = $this->getAttribute(self::REFERENCE1);
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

    public function setRemarks(string $value)
    {
        $this->setAttribute(self::REFERENCE2, $value);
    }

    public function isStatusFailed()
    {
        return ($this->getStatus() === Status::FAILED);
    }

    public function getGateway()
    {
        return $this->relations['payment']->getGateway();
    }

    public function getBatchId()
    {
        return $this->getAttribute(self::BATCH_ID);
    }

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
}
