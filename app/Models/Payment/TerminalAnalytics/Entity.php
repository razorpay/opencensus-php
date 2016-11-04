<?php

namespace RZP\Models\Payment\TerminalAnalytics;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    const PAYMENT_ID                    = 'payment_id';
    const TERMINAL_ID                   = 'terminal_id';
    const TERMINAL_STATUS               = 'terminal_status';
    const TERMINAL_RESPONSE_TIME        = 'terminal_response_time';
    const TERMINAL_STATUS_CODE          = 'terminal_status_code';
    const TERMINAL_STATUS_MSG           = 'terminal_status_msg';
    const PAYMENT_TYPE                  = 'payment_type';

    protected $entity = 'terminal_analytics';

    protected $fillable = array(
        self::PAYMENT_ID,
        self::TERMINAL_ID,
        self::PAYMENT_TYPE,
        self::TERMINAL_STATUS,
        self::TERMINAL_RESPONSE_TIME,
        self::TERMINAL_STATUS_CODE,
        self::TERMINAL_STATUS_MSG,
    );

    protected $public = array(
        self::ID,
        self::PAYMENT_ID,
        self::TERMINAL_ID,
        self::PAYMENT_TYPE,
        self::TERMINAL_STATUS,
        self::TERMINAL_RESPONSE_TIME,
        self::TERMINAL_STATUS_CODE,
        self::TERMINAL_STATUS_MSG,
        self::CREATED_AT,
        self::UPDATED_AT
    );

    protected $casts = array(
        self::TERMINAL_RESPONSE_TIME => 'int',
    );

    public function getTerminalId()
    {
        return $this->getAttribute(self::TERMINAL_ID);
    }
}
