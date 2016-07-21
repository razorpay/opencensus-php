<?php

namespace RZP\Models\Terminal\AuditLog;

use Crypt;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                            = 'id';
    const PAYMENT_ID                    = 'payment_id';
    const TERMINAL_ID                   = 'terminal_id';
    const STATUS                        = 'status';
    const RESPONSE_TIME                 = 'response_time';
    const STATUS_CODE                   = 'status_code';
    const STATUS_MSG                    = 'status_msg';
    const PAYMENT_TYPE                  = 'payment_type';
    const CREATED_AT                    = 'created_at';

    protected $fillable = array(
        self::PAYMENT_ID,
        self::TERMINAL_ID,
        self::STATUS,
        self::RESPONSE_TIME,
        self::STATUS_CODE,
        self::STATUS_MSG,
        self::PAYMENT_TYPE,
    );

    protected $public = array(
        self::ID,
        self::PAYMENT_ID,
        self::TERMINAL_ID,
        self::STATUS,
        self::RESPONSE_TIME,
        self::STATUS_CODE,
        self::STATUS_MSG,
        self::PAYMENT_TYPE,
        self::CREATED_AT
    );

    protected $table = \RZP\Constants\Table::TERMNINAL_AUDIT_LOGS;

    protected $generateIdOnCreate = true;

    protected $entity = 'terminal_auditlog';

    protected static $sign = '';

    protected static $delimiter = '';

    protected static $generators = array(self::ID, self::TERMINAL_ID, self::PAYMENT_ID,
                                         self::STATUS, self::RESPONSE_TIME, self::STATUS_CODE,
                                         self::STATUS_MSG, self::PAYMENT_TYPE, self::CREATED_AT
                                        );

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

    public function getResponseTime()
    {
        return $this->attributes[self::RESPONSE_TIME];
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
