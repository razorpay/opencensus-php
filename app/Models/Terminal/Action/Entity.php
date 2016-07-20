<?php

namespace RZP\Models\Terminal\Action;

use Crypt;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                            = 'id';
    const TERMINAL_ID                   = 'terminal_id';
    const ACTION                        = 'action';
    const CREATED_AT                    = 'timestamp';

    const ACTION_STATES = array('ACTIVATED','SUSPENDED','PRIORITY_CHANGE');

    protected $fillable = array(
        self::TERMINAL_ID,
        self::ACTION,
        self::CREATED_AT
    );

    protected $public = array(
        self::ID,
        self::TERMINAL_ID,
        self::ACTION,
        self::CREATED_AT
    );

    protected $table = 'terminal_action_log';

    protected $generateIdOnCreate = true;

    protected $entity = 'TerminalActionLog';

    protected static $sign = '';

    protected static $delimiter = '';

    protected static $generators = array(self::ID, self::TERMINAL_ID, self::ACTION);

}
