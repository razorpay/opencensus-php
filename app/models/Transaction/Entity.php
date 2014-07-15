<?php

namespace Models\Transaction;

use Models\Base;
use EE\Exception;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const AUTH_AMOUNT       = 'auth_amount';
    const AMOUNT            = 'amount';
    const STATUS            = 'status';
    const CURRENCY          = 'currency';
    const DESCRIPTION       = 'description';
    const TOKEN_ID          = 'token_id';
    const ERROR_CODE        = 'error_code';
    const ERROR_DESCRIPTION = 'error_description';
    const EMAIL             = 'email';
    const CONTACT           = 'contact';
    const UDF               = 'udf';
    const LEDGER_ID         = 'ledger_id';

    const CURRENCY_LENGTH   = 3;

    const MIN_TXN_AMOUNT = 100;

    protected $table = \Constants\Table::TRANSACTION;

    protected static $sign = 'txn';

    protected $entity = 'transaction';

    protected $fillable = array(
        self::ID,
        self::MERCHANT_ID,
        self::TOKEN_ID,
        self::STATUS,
        self::AUTH_AMOUNT,
        self::AMOUNT,
        self::CURRENCY,
        self::DESCRIPTION,
        self::EMAIL,
        self::CONTACT,
        self::UDF);

    protected $visible = array(
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::DESCRIPTION,
        self::EMAIL,
        self::CONTACT,
        self::UDF,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::DESCRIPTION,
        self::EMAIL,
        self::CONTACT,
        self::UDF,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT);

    protected $guarded = array(self::ID);

    protected static $modifiers = array('contact');

    protected static $generators = array('status', 'id', 'udf');

    public function build(array $input = array())
    {
        try
        {
            parent::build($input);
        }
        catch (Exception\ValidationFailureException $e)
        {
            throw new Exception\BadRequestException($e->getMessageBag(), 0, $e);
        }

        return $this;
    }

// --------------------- Generators --------------------------------------

    public function generateStatus($input)
    {
        $this->setAttribute(self::STATUS, Status::OPEN);
    }

    public function generateUdf($input)
    {
        if (isset($input['udf']) === false)
        {
            ;//$this->setAttribute(self::UDF, array());
        }
    }

// --------------------- Generators Ends ------------------------------------

    protected function modifyContact($contact)
    {
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

// ----------------------- Setters -----------------------------------------

    public function setCaptureAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setAuthAmount()
    {
        $authAmount = $this->getAttribute(self::AMOUNT);

        $this->setAttribute(self::AUTH_AMOUNT, $authAmount);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setStatusAndSave($status)
    {
        $this->setAttribute(self::STATUS, $status);
        $this->save();
    }

    public function setError($code, $desc)
    {
        $this->setAttribute(self::ERROR_CODE, $code);
        $this->setAttribute(self::ERROR_DESCRIPTION, $desc);
    }

// ----------------------- Setters Ends-------------------------------------

// ----------------------- Mutator -----------------------------------------

    public function setAmountAttribute($amount)
    {
        $this->attributes[self::AMOUNT] = (int) $amount;
    }

// ----------------------- Mutator Ends ------------------------------------

    public function isAuthorised()
    {
        return ($this->getAttribute(self::STATUS) == Status::AUTH);
    }

    public function isCaptured()
    {
        return ($this->getAttribute(self::STATUS) == Status::CAPTURED);
    }

    public function isRefunded()
    {
        return ($this->getAttribute(self::STATUS) == Status::REFUNDED);
    }

    public function isFailed()
    {
        return ($this->getAttribute(self::STATUS) == Status::FAILED);
    }

    protected function isStatus($status)
    {
        return ($this->getAttribute(self::STATUS) == $status);
    }

// ----------------------- Getters -----------------------------------------

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }

// ----------------------- Getters Ends-------------------------------------

    public function toArrayWithCard()
    {
        $data = $this->getAttributes();

        $token = $this->token()->first();

        if ($token === null)
        {
            throw new Exception\LogicException(ErrorCode::SERVER_ERROR_ASSOCIATED_TOKEN_NOT_FOUND);
        }

        $card = $token->card()->first();

        if ($card === null)
        {
            throw new Exception\LogicException(ErrorCode::SERVER_ERROR_ASSOCIATED_CARD_NOT_FOUND);
        }

        $cardData = $card->getAttributes();

        $data['card'] = $cardData;

        return $data;
    }

// --------------- Relation to other entities ----------------------

    public function token()
    {
        return $this->belongsTo(
            'Models\Token\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function ledger()
    {
        return $this->belongsTo('Models\Ledger\Entity');
    }

    public function hdfc()
    {
        return $this->hasOne('hdfc', 'trackid', 'id');
    }

// --------------- Relation to other entity section ends ------------

    public function scopeMerchantId($query, $merchantId)
    {
        return $query->where(self::MERCHANT_ID,'=',$merchantId);
    }

    public function toArrayTraceRelevant()
    {
        $data = $this->attributes;

        $relevantData = array();

        $fields = array(
            self::ID,
            self::MERCHANT_ID,
            self::TOKEN_ID,
            self::STATUS,
            self::AMOUNT,
            self::ERROR_CODE);

        $relevantData = array_intersect_key($data, array_flip($fields));

        return $relevantData;
    }
}
