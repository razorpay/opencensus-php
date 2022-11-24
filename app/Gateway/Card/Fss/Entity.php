<?php

namespace RZP\Gateway\Card\Fss;

use RZP\Gateway\Base;
use RZP\Constants;

class Entity extends Base\Entity
{
    const ACQUIRER                  = 'acquirer';
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
        self::UPDATED_AT,
        self::CREATED_AT,
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

    protected $entity = Constants\Entity::CARD_FSS;

    public $incrementing = true;

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity', self::PAYMENT_ID, self::ID);
    }

    public function refund()
    {
        return $this->belongsTo('RZP\Models\Refund\Entity', self::REFUND_ID, self::ID);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getErrorMessage()
    {
        return $this->getAttribute(self::ERROR_MESSAGE);
    }

    public function getAuthCode()
    {
        return $this->getAttribute(self::AUTH);
    }

    public function getGatewayTransactionId()
    {
        return $this->getAttribute(self::GATEWAY_TRANSACTION_ID);
    }

    public function getRef()
    {
        return $this->getAttribute(self::REF);
    }

    public function setAcquirer($acquirer)
    {
        $this->setAttribute(self::ACQUIRER, $acquirer);
    }

    public function setAction($action)
    {
        $this->setAttribute(self::ACTION, $action);
    }

    public function setGatewayPaymentId($paymentId)
    {
        $this->setAttribute(self::GATEWAY_PAYMENT_ID, $paymentId);
    }

    public function setRefundId($refundId)
    {
        $this->setAttribute(self::REFUND_ID, $refundId);
    }

    public function setErrorMessage($errorMessage)
    {
        $this->setAttribute(self::ERROR_MESSAGE, $errorMessage);
    }

    public function setGatewayTransactionId(string $gatewayTransactionId)
    {
        $this->setAttribute(self::GATEWAY_TRANSACTION_ID, $gatewayTransactionId);
    }

    public function setRef(string $ref)
    {
        $this->setAttribute(self::REF, $ref);
    }

    public function setPostDate(string $date)
    {
        $this->setAttribute(self::POST_DATE, $date);
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }
}
