<?php

namespace RZP\Gateway\Fss;

use RZP\Gateway\Base;
use RZP\Constants;

class Entity extends Base\Entity
{
    const AMOUNT                    = 'amount';
    const CURRENCY                  = 'currency';
    const STATUS                    = 'status';
    const GATEWAY_PAYMENT_ID        = 'gateway_payment_id';
    const GATEWAY_TRANSACTION_ID    = 'tranid';
    const REF                       = 'ref';
    const AUTH                      = 'auth';
    const POST_DATE                 = 'postdate';
    const ERROR_MESSAGE             = 'error_message';
    const AUTH_RES_CODE             = 'auth_res_code';

    protected $fields = [
        self::ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PAYMENT_ID,
        self::ACTION,
        self::REFUND_ID,
        self::RECEIVED,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::GATEWAY_PAYMENT_ID,
        self::GATEWAY_TRANSACTION_ID,
        self::REF,
        self::AUTH,
        self::POST_DATE,
        self::ERROR_MESSAGE,
        self::AUTH_RES_CODE,
    ];

    protected $fillable = [
        self::RECEIVED,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::GATEWAY_PAYMENT_ID,
        self::GATEWAY_TRANSACTION_ID,
        self::REF,
        self::AUTH,
        self::POST_DATE,
        self::ERROR_MESSAGE,
        self::AUTH_RES_CODE,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected $entity = Constants\Entity::FSS;

    public $incrementing = true;

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity', self::PAYMENT_ID, self::ID);
    }

    public function refund()
    {
        return $this->belongsTo('RZP\Models\Refund\Entity', self::REFUND_ID, self::ID);
    }

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setAction($action)
    {
        $this->setAttribute(self::ACTION, $action);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setCurrency($currency)
    {
        $this->setAttribute(self::CURRENCY, $currency);
    }

    public function setGatewayPaymentId($paymentId)
    {
        $this->setAttribute(self::GATEWAY_PAYMENT_ID, $paymentId);
    }

    public function setErrorMessage($errorMessage)
    {
        $this->setAttribute(self::ERROR_MESSAGE, $errorMessage);
    }
}