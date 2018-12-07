<?php

namespace RZP\Gateway\Paysecure;

use RZP\Gateway\Base;
use RZP\Constants;

class Entity extends Base\Entity
{
    const ID                      = 'id';
    const STATUS                  = 'status';
    const GATEWAY_TRANSACTION_ID  = 'gateway_transaction_id';
    const ERROR_CODE              = 'error_code';
    const ERROR_MESSAGE           = 'error_message';
    const FLOW                    = 'flow';
    const HKEY                    = 'hkey';
    const AUTH_NOT_REQUIRED       = 'auth_not_required';
    const APPRCODE                = 'apprcode';

    protected $fillable = [
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::RECEIVED,
        self::ACTION,
        self::STATUS,
        self::GATEWAY_TRANSACTION_ID,
        self::ERROR_MESSAGE,
        self::ERROR_CODE,
        self::FLOW,
        self::HKEY,
        self::AUTH_NOT_REQUIRED,
        self::APPRCODE
    ];

    protected $primaryKey = self::ID;

    protected $entity = Constants\Entity::PAYSECURE;

    public $incrementing = true;

    public function setFlow(string $flow)
    {
        $this->setAttribute(self::FLOW, $flow);
    }
}
