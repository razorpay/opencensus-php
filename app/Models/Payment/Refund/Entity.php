<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const PAYMENT_ID        = 'payment_id';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const TRANSACTION_ID    = 'transaction_id';
    const NOTES             = 'notes';
    const BATCH_REFUND_ID   = 'batch_refund_id';

    protected $table = \RZP\Constants\Table::REFUND;

    protected static $sign = 'rfnd';

    protected $entity = 'refund';

    protected $generateIdOnCreate = true;

    protected static $generators = array(self::ID, self::AMOUNT, self::CURRENCY);

    protected $fillable = array(
        self::MERCHANT_ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES
    );

    protected $visible = array(
        self::ID,
        self::MERCHANT_ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::TRANSACTION_ID,
        self::NOTES,
        self::BATCH_REFUND_ID,
        self::CREATED_AT,
        self::UPDATED_AT
    );

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::PAYMENT_ID,
        self::NOTES,
        self::CREATED_AT
    );

    protected $defaults = array(
        self::NOTES      => []
    );

    protected $publicSetters = array(
        self::ID, self::ENTITY, self::PAYMENT_ID
    );

    protected $amounts = array(
        self::AMOUNT,
    );

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

    public function batchRefund()
    {
        return $this->belongsTo('RZP\Models\Payment\BatchRefund\Entity', self::BATCH_REFUND_ID);
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

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
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

    public function getGateway()
    {
        return $this->relations['payment']->getGateway();
    }

    public function getBatchRefundId()
    {
        return $this->getAttribute(self::BATCH_REFUND_ID);
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
}
