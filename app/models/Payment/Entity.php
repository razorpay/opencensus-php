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
    const NOTES             = 'notes';
    const BANK              = 'bank';
    const CARD_ID           = 'card_id';
    const TRANSACTION_ID    = 'transaction_id';
    const AUTO_CAPTURED     = 'auto_captured';
    const CAPTURED_AT       = 'captured_at';
    const GATEWAY           = 'gateway';
    const TERMINAL_ID       = 'terminal_id';
    const SIGNED            = 'signed';

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
        self::BANK,
        self::CURRENCY,
        self::DESCRIPTION,
        self::EMAIL,
        self::CONTACT,
        self::NOTES);

    protected $visible = array(
        self::ID,
        self::PUBLIC_ID,
        self::METHOD,
        self::AMOUNT,
        self::AMOUNT_AUTHORIZED,
        self::AMOUNT_REFUNDED,
        self::CURRENCY,
        self::STATUS,
        self::REFUND_STATUS,
        self::DESCRIPTION,
        self::BANK,
        self::EMAIL,
        self::CONTACT,
        self::NOTES,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CAPTURED_AT,
        self::GATEWAY,
        self::CARD_ID,
        self::MERCHANT_ID,
        self::TERMINAL_ID,
        self::TRANSACTION_ID,
        self::SIGNED,
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
        self::NOTES,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CREATED_AT);

    protected $guarded = array(self::ID);

    protected $appends = array(self::PUBLIC_ID);

    protected static $modifiers = array(self::CONTACT);

    protected static $generators = array(
        self::STATUS,
        self::ID,
        self::NOTES,
        self::SIGNED,
        self::REFUND_STATUS,
        self::AMOUNT_REFUNDED,
        self::AUTO_CAPTURED);

// --------------------- Generators --------------------------------------------

    public function generateStatus($input)
    {
        $this->setAttribute(self::STATUS, Status::CREATED);
    }

    public function generateRefundStatus($input)
    {
        $this->setAttribute(self::REFUND_STATUS, Refund\Status::NULL);
    }

    public function generateNotes($input)
    {
        if (isset($input['notes']) === false)
        {
            $this->setAttribute(self::NOTES, array());
        }
    }

    protected function generateAmountRefunded()
    {
        $this->setAttribute(self::AMOUNT_REFUNDED, 0);
    }

    protected function generateSigned()
    {
        $this->setAttribute(self::SIGNED, 0);
    }

    protected function generateAutoCaptured()
    {
        $this->setAttribute(self::AUTO_CAPTURED, 0);
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

    public function setGateway($gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }

    public function setError($code, $desc)
    {
        $this->setAttribute(self::ERROR_CODE, $code);
        $this->setAttribute(self::ERROR_DESCRIPTION, $desc);
    }

    public function setCaptureTimestamp()
    {
        $this->setAttribute(self::CAPTURED_AT, time());
    }

    public function setBank($bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }

    public function setSigned($signed = true)
    {
        $this->setAttribute(self::SIGNED, $signed);
    }

    public function setAutoCaptureTrue()
    {
        $this->setAttribute(self::AUTO_CAPTURED, true);
    }

// ----------------------- Setters Ends-----------------------------------------

// ----------------------- Mutator ---------------------------------------------

    public function setAmountAttribute($amount)
    {
        $this->attributes[self::AMOUNT] = (int) $amount;
    }

    public function setNotesAttribute($notes)
    {
        $this->attributes[self::NOTES] = json_encode($notes);
    }

// ----------------------- Mutator Ends ----------------------------------------

// ----------------------- Accessor --------------------------------------------

    public function getNotesAttribute($notes)
    {
        return json_decode($notes, true);
    }

    public function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    public function getAmountAuthorizedAttribute()
    {
        return (int) $this->attributes[self::AMOUNT_AUTHORIZED];
    }

    public function getAmountRefundedAttribute()
    {
        return (int) $this->attributes[self::AMOUNT_REFUNDED];
    }

    public function getAutoCapturedAttribute()
    {
        return (bool) $this->attributes[self::AUTO_CAPTURED];
    }

// ----------------------- Accessor Ends ---------------------------------------

    public function isCreated()
    {
        return ($this->getAttribute(self::STATUS) == Status::CREATED);
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
        return ! ($this->getAttribute(self::REFUND_STATUS) === Refund\Status::NULL);
    }

    public function isFullyRefunded()
    {
        return ($this->getAttribute(self::REFUND_STATUS) === Refund\Status::FULL);
    }

    public function isPartiallyRefunded()
    {
        return ($this->getAttribute(self::REFUND_STATUS) === Refund\Status::PARTIAL);
    }

    public function isFailed()
    {
        return ($this->getAttribute(self::STATUS) === Status::FAILED);
    }

    protected function isStatus($status)
    {
        return ($this->getAttribute(self::STATUS) === $status);
    }

    public function isNetBanking()
    {
        return ($this->getAttribute(self::METHOD) === Payment\Method::NETBANKING);
    }

    public function isGateway($gateway)
    {
        return ($this->getAttribute(self::GATEWAY) === $gateway);
    }

    public function isMethod($method)
    {
        return ($this->getAttribute(self::METHOD) === $method);
    }

    public function isSigned()
    {
        return ((bool)$this->getAttribute(self::SIGNED) === true);
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

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getNotes()
    {
        return $this->getAttribute(self::NOTES);
    }

    public function getBank()
    {
        return $this->getAttribute(self::BANK);
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

    public function toArrayDashboard()
    {
        $data = $this->toArray();

        $data['id'] = $this->getPublicId();
        
        if ($this->getAttribute(self::METHOD) === Payment\Method::CARD)
        {
            $card = $this->card()->firstOrFail();

            $network = $card->getNetwork();

            $data['network'] = $network;
        }

        return $data;
    }

// --------------- Relation to other entities ----------------------------------

    public function card()
    {
        return $this->belongsTo('Models\Card\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function terminal()
    {
        return $this->belongsTo('Models\Terminal\Entity');
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
            self::AUTO_CAPTURED,
            self::ERROR_CODE);

        $relevantData = array_intersect_key($this->attributes, array_flip($fields));

        return $relevantData;
    }
}
