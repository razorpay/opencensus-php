<?php

namespace Models\Payment;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Payment;
use Models\Payment\Refund;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const AMOUNT            = 'amount';
    const AMOUNT_AUTHORIZED = 'amount_authorized';
    const AMOUNT_REFUNDED   = 'amount_refunded';
    const STATUS            = 'status';
    const METHOD            = 'method';
    const REFUND_STATUS     = 'refund_status';
    const CURRENCY          = 'currency';
    const DESCRIPTION       = 'description';
    const ERROR_CODE        = 'error_code';
    const ERROR_DESCRIPTION = 'error_description';
    const EMAIL             = 'email';
    const CONTACT           = 'contact';
    const UDF               = 'udf';
    const CARD_ID           = 'card_id';
    const TRANSACTION_ID         = 'transaction_id';
    const CAPTURED_AT       = 'captured_at';
    const GATEWAY           = 'gateway';

    const CURRENCY_LENGTH   = 3;

    const MIN_PAYMENT_AMOUNT = 100;

    protected $table = \Constants\Table::PAYMENT;

    protected static $sign = 'pay';

    protected $entity = 'payment';

    protected $genereateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::METHOD,
        self::CURRENCY,
        self::DESCRIPTION,
        self::EMAIL,
        self::CONTACT,
        self::UDF);

    protected $visible = array(
        self::ID,
        self::METHOD,
        self::AMOUNT,
        self::AMOUNT_AUTHORIZED,
        self::AMOUNT_REFUNDED,
        self::CURRENCY,
        self::STATUS,
        self::REFUND_STATUS,
        self::DESCRIPTION,
        self::EMAIL,
        self::CONTACT,
        self::UDF,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CAPTURED_AT,
        self::CREATED_AT,
        self::UPDATED_AT);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::AMOUNT_REFUNDED,
        self::REFUND_STATUS,
        self::DESCRIPTION,
        self::EMAIL,
        self::CONTACT,
        self::UDF,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CREATED_AT);

    protected $guarded = array(self::ID);

    protected static $modifiers = array(self::CONTACT, self::UDF);

    protected static $generators = array(
        self::METHOD,
        self::STATUS,
        self::ID,
        self::UDF,
        self::REFUND_STATUS,
        self::AMOUNT_REFUNDED,
        self::GATEWAY);

// --------------------- Generators --------------------------------------------

    public function generateStatus($input)
    {
        $this->setAttribute(self::STATUS, Status::OPEN);
    }

    public function generateRefundStatus($input)
    {
        $this->setAttribute(self::REFUND_STATUS, Refund\Status::NONE);
    }

    public function generateUdf($input)
    {
        if (isset($input['udf']) === false)
        {
            $this->setAttribute(self::UDF, array());
        }
    }

    protected function generateMethod($input)
    {
        if (isset($input['method']) === false)
        {
            $this->setAttribute(self::METHOD, Payment\Method::CARD);
        }
    }

    public function generateGateway()
    {
        $this->setAttribute(self::GATEWAY, Payment\Gateway::HDFC);
    }

    protected function generateAmountRefunded()
    {
        $this->setAttribute(self::AMOUNT_REFUNDED, 0);
    }

// --------------------- Generators Ends ---------------------------------------

// --------------------- Modifiers ---------------------------------------------

    protected function modifyContact(& $input)
    {
        if (isset($input['contact']) === false)
            return;

        $contact = & $input['contact'];

        if (is_string($contact) === false)
        {
            return;
        }

        $contact = str_replace(' ', '', $contact);
        $contact = str_replace('-', '', $contact);
        $contact = str_replace('(', '', $contact);
        $contact = str_replace(')', '', $contact);

        return $contact;
    }

    protected function modifyUdf(& $input)
    {
        if (isset($input['udf']) === false)
        {
            $input['udf'] = array();
        }
    }

// --------------------- Modifiers Ends ----------------------------------------

// ----------------------- Setters ---------------------------------------------

    public function setCaptureAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setAmountAuthorized()
    {
        $authAmount = $this->getAttribute(self::AMOUNT);

        $this->setAttribute(self::AMOUNT_AUTHORIZED, $authAmount);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setRefundStatus($status)
    {
        $this->setAttribute(self::REFUND_STATUS, $status);
    }

    public function setAmountRefunded($amount)
    {
        $this->setAttribute(self::AMOUNT_REFUNDED, $amount);
    }

    public function setError($code, $desc)
    {
        $this->setAttribute(self::ERROR_CODE, $code);
        $this->setAttribute(self::ERROR_DESCRIPTION, $desc);
    }

    public function setCaptureTimestamp()
    {
        $this->setAttribute(self::CAPTURED_AT, time('now'));
    }

// ----------------------- Setters Ends-----------------------------------------

// ----------------------- Mutator ---------------------------------------------

    public function setAmountAttribute($amount)
    {
        $this->attributes[self::AMOUNT] = (int) $amount;
    }

    public function setUdfAttribute($udf)
    {
        $this->attributes[self::UDF] = serialize($udf);
    }

// ----------------------- Mutator Ends ----------------------------------------

// ----------------------- Accessor --------------------------------------------

    public function getUdfAttribute($udf)
    {
        return unserialize($udf);
    }

// ----------------------- Accessor Ends ---------------------------------------

    public function isOpen()
    {
        return ($this->getAttribute(self::STATUS) == Status::OPEN);
    }

    public function isAuthorized()
    {
        return ($this->getAttribute(self::STATUS) === Status::AUTHORIZED);
    }

    public function isCaptured()
    {
        return ($this->getAttribute(self::STATUS) === Status::CAPTURED);
    }

    public function isPartiallyOrFullyRefunded()
    {
        return ! ($this->getAttribute(self::STATUS) === Refund\Status::NONE);
    }

    public function isFullyRefunded()
    {
        return ($this->getAttribute(self::REFUND_STATUS) === Refund\Status::FULL);
    }

    public function isPartisallyRefunded()
    {
        return ($this->getAttribute(self::REFUND_STATUS) === Refund\Status::PARTIAL);
    }

    public function isFailed()
    {
        return ($this->getAttribute(self::STATUS) == Status::FAILED);
    }

    protected function isStatus($status)
    {
        return ($this->getAttribute(self::STATUS) == $status);
    }

// ----------------------- Getters ---------------------------------------------

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }

    public function getAmountRefunded()
    {
        return (int) $this->getAttribute(self::AMOUNT_REFUNDED);
    }

    public function getAmountUnrefunded()
    {
        return (int) $this->getAmount() - $this->getAmountRefunded();
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

// ----------------------- Getters Ends-----------------------------------------

    public function toArrayWithCard()
    {
        $data = $this->getAttributes();

        $card = $this->card()->first();

        if ($card === null)
        {
            throw new Exception\LogicException(
                'Associated card not found for the current payment entity');
        }

        $cardData = $card->getAttributes();

        $data['card'] = $cardData;

        return $data;
    }

// --------------- Relation to other entities ----------------------------------

    public function card()
    {
        return $this->belongsTo(
            'Models\Card\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function refunds()
    {
        return $this->hasMany('Models\Payment\Refund\Entity');
    }

    public function transaction()
    {
        return $this->belongsTo('Models\Transaction\Entity');
    }

    public function hdfc()
    {
        return $this->hasOne('hdfc', 'trackid', 'id');
    }

// --------------- Relation to other entity section ends -----------------------

    public function refundAmount($amount)
    {
        if (ctype_digit($amount) === false)
        {
            throw new Exception\InvalidArgumentException('amount should only have digits.');
        }

        $amount = (int) $amount;

        $amountUnrefunded = $this->getAmountUnrefunded();

        if ($amount < $amountUnrefunded)
        {
            $this->setRefundStatus(Refund\Status::PARTIAL);
        }
        else if ($amount === $amountUnrefunded)
        {
            $this->setRefundStatus(Refund\Status::FULL);
        }
        else
        {
            throw new Exception\LogicException(
                'Refund amount should be less than or equal to amount unrefunded');
        }

        $amountRefunded = $this->getAmountRefunded() + $amount;

        $this->setAttribute(self::AMOUNT_REFUNDED, $amountRefunded);
    }

    public function scopeMerchantId($query, $merchantId)
    {
        return $query->where(self::MERCHANT_ID,'=',$merchantId);
    }

    public function toArrayTraceRelevant()
    {
        $fields = array(
            self::ID,
            self::MERCHANT_ID,
            self::CARD_ID,
            self::STATUS,
            self::AMOUNT,
            self::ERROR_CODE);

        $relevantData = array_intersect_key($this->attributes, array_flip($fields));

        return $relevantData;
    }
}
