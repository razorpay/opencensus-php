<?php

namespace RZP\Models\Payment\Analytics;

use Crypt;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const PAYMENT_ID                    = 'payment_id';
    const TERMINAL_ID                   = 'terminal_id';
    const STATUS                        = 'status';
    const TERMINAL_RESPONSE_TIME        = 'terminal_response_time';
    const STATUS_CODE                   = 'status_code';
    const STATUS_MSG                    = 'status_msg';
    const PAYMENT_TYPE                  = 'payment_type';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';

    protected $fillable = array(
        self::PAYMENT_ID,
        self::TERMINAL_ID,
        self::STATUS,
        self::TERMINAL_RESPONSE_TIME,
        self::STATUS_CODE,
        self::STATUS_MSG,
        self::PAYMENT_TYPE,
    );

    protected $public = array(
        self::ID,
        self::PAYMENT_ID,
        self::TERMINAL_ID,
        self::STATUS,
        self::TERMINAL_RESPONSE_TIME,
        self::STATUS_CODE,
        self::STATUS_MSG,
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
        return $this->attributes[self::PAYMENT_ID];
    }

    public function getTerminalId()
    {
        return $this->attributes[self::TERMINAL_ID];
    }

    public function getStatus()
    {
        return $this->attributes[self::STATUS];
    }

    public function getTerminalResponseTime()
    {
        return $this->attributes[self::TERMINAL_RESPONSE_TIME];
    }

    public function getStatusCode()
    {
        return $this->attributes[self::STATUS_CODE];
    }

    public function getStatusMsg()
    {
        return $this->attributes[self::STATUS_MSG];
    }

    public function getPaymentType()
    {
        return $this->attributes[self::PAYMENT_TYPE];
    }

}
