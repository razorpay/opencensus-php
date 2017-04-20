<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\Base;
use RZP\Models\Currency;
use RZP\Models\Payment;
use RZP\Models\Batch;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const PAYMENT_ID        = 'payment_id';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const BASE_AMOUNT       = 'base_amount';
    const TRANSACTION_ID    = 'transaction_id';
    const NOTES             = 'notes';
    const BATCH_ID          = 'batch_id';
    const GATEWAY_REFUNDED  = 'gateway_refunded';
    const RRN               = 'rrn';

    const RRN_LENGTH        = 50;

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
        self::RRN
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::BASE_AMOUNT,
        self::TRANSACTION_ID,
        self::NOTES,
        self::BATCH_ID,
        self::GATEWAY_REFUNDED,
        self::RRN,
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
        self::RRN,
        self::CREATED_AT
    ];

    protected $defaults = [
        self::NOTES             => [],
        self::GATEWAY_REFUNDED  => null,
    ];

    protected $casts = [
        self::GATEWAY_REFUNDED => 'bool',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_ID
    ];

    protected $amounts = [
        self::AMOUNT,
        self::BASE_AMOUNT,
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

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    public function setGatewayRefunded($gatewayRefunded)
    {
        $this->setAttribute(self::GATEWAY_REFUNDED, $gatewayRefunded);
    }

    public function setBaseAmount()
    {
        $amount = $this->getAttribute(self::AMOUNT);

        $currency = $this->getAttribute(self::CURRENCY);

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

    public function setPublicPaymentIdAttribute(array & $array)
    {
        $array[self::PAYMENT_ID] =
            Payment\Entity::getIdPrefix() . $this->getAttribute(self::PAYMENT_ID);
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
        $data[Payment\Entity::EMAIL] = $this->payment->getEmail();

        return $data;
    }

    public function toArrayGateway()
    {
        $data = $this->toArray();

        if (($this->payment->isCard()) and
            ($this->payment->getConvertCurrency() === true))
        {
            $data['amount'] = $this->getBaseAmount();
            $data['currency'] = Currency\Currency::INR;
        }

        return $data;
    }
}
