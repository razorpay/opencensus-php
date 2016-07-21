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

    protected $table = \RZP\Constants\Table::TERMINAL_ACTION;

    protected $generateIdOnCreate = true;

    protected $entity = 'terminal_action';

    protected static $sign = '';

    protected static $delimiter = '';

    protected static $generators = array(self::ID, self::TERMINAL_ID, self::ACTION);

    public function getTerminalId()
    {
        return $this->attributes[self::TERMINAL_ID];
    }

    public function getAction()
    {
        return $this->attributes[self::ACTION];
    }

}
