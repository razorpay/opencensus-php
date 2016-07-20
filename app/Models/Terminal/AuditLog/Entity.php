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

    protected $table = 'terminal_audit_log';

    protected $generateIdOnCreate = true;

    protected $entity = 'TerminalAuditLog';

    protected static $sign = '';

    protected static $delimiter = '';

    protected static $generators = array(self::ID, self::TERMINAL_ID, self::PAYMENT_ID,
                                         self::STATUS, self::RESPONSE_TIME, self::STATUS_CODE,
                                         self::STATUS_MSG, self::PAYMENT_TYPE, self::CREATED_AT
                                        );

}
