<?php

namespace RZP\Models\Payment\TerminalAnalytics;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Terminal;

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

    public $incrementing = true;

    protected $fillable = [
        self::PAYMENT_TYPE,
        self::TERMINAL_STATUS,
        self::TERMINAL_RESPONSE_TIME,
        self::TERMINAL_STATUS_CODE,
        self::TERMINAL_STATUS_MSG,
    ];

    protected $public = [
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
    ];

    protected $casts = [
        self::TERMINAL_RESPONSE_TIME => 'int',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment\Entity::class);
    }

    public function terminal()
    {
        return $this->belongsTo(Terminal\Entity::class);
    }

    public function getTerminalId()
    {
        return $this->getAttribute(self::TERMINAL_ID);
    }
}
