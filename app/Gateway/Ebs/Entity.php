<?php

namespace RZP\Gateway\Ebs;

use RZP\Constants;
use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const ACTION                = 'action';
    const AMOUNT                = 'amount';
    const CURRENCY              = 'currency';
    const RECEIVED              = 'received';
    const REFUND_ID             = 'refund_id';
    const REQUEST_ID            = 'request_id';
    const PAYMENT_ID            = 'payment_id';
    const ERROR_CODE            = 'error_code';
    const IS_FLAGGED            = 'is_flagged';
    const GATEWAY_PAYMENT_ID    = 'gateway_payment_id';
    const TRANSACTION_ID        = 'transaction_id';
    const ERROR_DESCRIPTION     = 'error_description';

    protected $fields = [
        self::AMOUNT,
        self::RECEIVED,
        self::REFUND_ID,
        self::ERROR_CODE,
        self::IS_FLAGGED,
        self::REQUEST_ID,
        self::PAYMENT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::TRANSACTION_ID,
        self::ERROR_DESCRIPTION,
    ];

    protected $fillable = [
        self::AMOUNT,
        self::RECEIVED,
        self::REFUND_ID,
        self::ERROR_CODE,
        self::IS_FLAGGED,
        self::REQUEST_ID,
        self::PAYMENT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::TRANSACTION_ID,
        self::ERROR_DESCRIPTION,
    ];

    protected $casts = [
        self::AMOUNT        => 'int',
        self::IS_FLAGGED    => 'boolean',
    ];

    protected $entity = Constants\Entity::EBS;

    public function getGatewayPaymentId()
    {
        return $this->getAttribute(self::GATEWAY_PAYMENT_ID);
    }

    public function getGatewayTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function setGatewayTransactionId($gatewayTransactionId)
    {
        $this->setAttribute(self::TRANSACTION_ID, $gatewayTransactionId);
    }

    public function setGatewayPaymentId($gatewayPaymentId)
    {
        $this->setAttribute(self::GATEWAY_PAYMENT_ID, $gatewayPaymentId);
    }
}
