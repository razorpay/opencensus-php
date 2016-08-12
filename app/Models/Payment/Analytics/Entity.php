<?php

namespace RZP\Models\Payment\Analytics;

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

    protected $fillable = array(
        self::PAYMENT_ID,
        self::TERMINAL_ID,
        self::TERMINAL_STATUS,
        self::TERMINAL_RESPONSE_TIME,
        self::TERMINAL_STATUS_CODE,
        self::TERMINAL_STATUS_MSG,
        self::PAYMENT_TYPE,
    );

    protected $public = array(
        self::ID,
        self::PAYMENT_ID,
        self::TERMINAL_ID,
        self::TERMINAL_STATUS,
        self::TERMINAL_RESPONSE_TIME,
        self::TERMINAL_STATUS_CODE,
        self::TERMINAL_STATUS_MSG,
        self::PAYMENT_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT
    );

    protected $table = \RZP\Constants\Table::PAYMENT_ANALYTICS;

    protected $entity = 'payment_analytics';

    protected static $sign = '';

    protected static $delimiter = '';

    public function getPaymentId()
    {
        return $this->getAttributes(self::PAYMENT_ID);
    }

    public function getTerminalId()
    {
        return $this->getAttributes(self::TERMINAL_ID);
    }

    public function getTerminalStatus()
    {
        return $this->getAttributes(self::TERMINAL_STATUS);
    }

    public function getTerminalResponseTime()
    {
        return $this->getAttributes(self::TERMINAL_RESPONSE_TIME);
    }

    public function getTerminalStatusCode()
    {
        return $this->getAttributes(self::TERMINAL_STATUS_CODE);
    }

    public function getTerminalStatusMsg()
    {
        return $this->getAttributes(self::TERMINAL_STATUS_MSG);
    }

    public function getPaymentType()
    {
        return $this->getAttributes(self::PAYMENT_TYPE);
    }

}
