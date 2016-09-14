<?php

namespace RZP\Models\Payment\TerminalAnalytics;

use RZP\Models\Base;
use RZP\Models\Payment;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const PAYMENT_ID                    = 'payment_id';
    const TERMINAL_ID                   = 'terminal_id';
    const TERMINAL_STATUS               = 'terminal_status';
    const TERMINAL_RESPONSE_TIME        = 'terminal_response_time';
    const TERMINAL_STATUS_CODE          = 'terminal_status_code';
    const TERMINAL_STATUS_MSG           = 'terminal_status_msg';
    const PAYMENT_TYPE                  = 'payment_type';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';

    // window in secs, used to fetch payments with same checkout id
    const PAYMENT_WINDOW                = 1800;

    protected $table = \RZP\Constants\Table::TERMINAL_ANALYTICS;

    protected $entity = 'terminal_analytics';

    protected static $sign = '';

    protected static $delimiter = '';

    protected $fillable = array(
        self::PAYMENT_ID,
        self::TERMINAL_ID,
        self::TERMINAL_STATUS,
        self::TERMINAL_RESPONSE_TIME,
        self::TERMINAL_STATUS_CODE,
        self::TERMINAL_STATUS_MSG,
    );

    protected $public = array(
        self::ID,
        self::PAYMENT_ID,
        self::TERMINAL_ID,
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

    // ----------------------- Getters ---------------------------------------------

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getTerminalId()
    {
        return $this->getAttribute(self::TERMINAL_ID);
    }

    public function getTerminalStatus()
    {
        return $this->getAttribute(self::TERMINAL_STATUS);
    }

    public function getTerminalResponseTime()
    {
        return $this->getAttribute(self::TERMINAL_RESPONSE_TIME);
    }

    public function getTerminalStatusCode()
    {
        return $this->getAttribute(self::TERMINAL_STATUS_CODE);
    }

    public function getTerminalStatusMsg()
    {
        return $this->getAttribute(self::TERMINAL_STATUS_MSG);
    }
}
