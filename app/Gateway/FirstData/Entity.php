<?php

namespace RZP\Gateway\FirstData;

use RZP\Gateway\Base;
use RZP\Constants;

class Entity extends Base\Entity
{
    const ID                      = 'id';
    const AMOUNT                  = 'amount';
    const GATEWAY_PAYMENT_ID      = 'gateway_payment_id';
    const TDATE                   = 'tdate';
    const STATUS                  = 'status';
    const TRANSACTION_RESULT      = 'transaction_result';
    const GATEWAY_TRANSACTION_ID  = 'gateway_transaction_id';
    const ENDPOINT_TRANSACTION_ID = 'endpoint_transaction_id';
    const GATEWAY_TERMINAL_ID     = 'gateway_terminal_id';
    const AUTH_CODE               = 'auth_code';
    const APPROVAL_CODE           = 'approval_code';
    const ERROR_MESSAGE           = 'error_message';

    protected $fillable = [
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::RECEIVED,
        self::ACTION,
        self::AMOUNT,
        self::GATEWAY_PAYMENT_ID,
        self::TDATE,
        self::STATUS,
        self::TRANSACTION_RESULT,
        self::GATEWAY_TRANSACTION_ID,
        self::ENDPOINT_TRANSACTION_ID,
        self::GATEWAY_TERMINAL_ID,
        self::AUTH_CODE,
        self::APPROVAL_CODE,
        self::ERROR_MESSAGE,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected $primaryKey = self::ID;

    protected $entity = Constants\Entity::FIRST_DATA;

    public $incrementing = true;

    // ----------------------- Getters ---------------------------------------------

    public function getApprovalCode()
    {
        return $this->getAttribute(self::APPROVAL_CODE);
    }

    public function getAuthCode()
    {
        return $this->getAttribute(self::AUTH_CODE);
    }

    // ----------------------- Setters ---------------------------------------------

    public function setTdate($tdate)
    {
        return $this->setAttribute(self::TDATE, $tdate);
    }

    public function setGatewayPaymentId($gatewayPaymentId)
    {
        return $this->setAttribute(self::GATEWAY_PAYMENT_ID, $gatewayPaymentId);
    }

    public function setStatus($status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }
}
