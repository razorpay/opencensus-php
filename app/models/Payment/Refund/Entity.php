<?php

namespace Models\Payment\Refund;

use Models\Base;
use Models\Payment;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const PAYMENT_ID        = 'payment_id';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const TRANSACTION_ID    = 'transaction_id';

    protected $table = \Constants\Table::REFUND;

    protected static $sign = 'rfnd';

    protected $entity = 'refund';

    protected $genereateIdOnCreate = true;

    protected static $generators = array(self::ID, self::AMOUNT, self::CURRENCY);

    protected $fillable = array(
        self::MERCHANT_ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::CURRENCY);

    protected $visible = array(
        self::ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::TRANSACTION_ID,
        self::CREATED_AT,
        self::UPDATED_AT);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::PAYMENT_ID,
        self::CREATED_AT);

    protected $publicSetters = array(
        self::ID, self::ENTITY, self::PAYMENT_ID);

    public function payment()
    {
        return $this->belongsTo('Models\Payment\Entity');
    }

    public function transaction()
    {
        return $this->belongsTo('Models\Transaction\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function build(array $input = array())
    {
        $payment = func_get_arg(1);

        $this->payment()->associate($payment);

        $this->getValidator()->setPayment($payment);

        return parent::build($input);
    }

    protected function generateAmount($input)
    {
        if (isset($input['amount']) === false)
        {
            $this->setAttribute(self::AMOUNT, $this->payment->getAmountUnrefunded());
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

    public function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    public function setPublicPaymentIdAttribute(array & $array)
    {
        $array[self::PAYMENT_ID] =
            Payment\Entity::getIdPrefix() . $this->getAttribute(self::PAYMENT_ID);
    }
}
