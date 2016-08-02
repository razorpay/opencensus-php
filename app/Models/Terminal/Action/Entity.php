<?php

namespace RZP\Models\Terminal\Action;

use Crypt;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const ID                            = 'id';

    const TERMINAL_ID                   = 'terminal_id';

    const ACTION                        = 'action';

    //TODO: we would ideally want only one time field called timestamp.
    // However, laravel implementation dictates having created_at and
    // updated at. Need to find a way to change this.
    const CREATED_AT                    = 'created_at';

    const UPDATED_AT                    = 'updated_at';

    const ACTION_STATES = array('ACTIVATED','SUSPENDED','PRIORITY_CHANGE');

    protected $fillable = array(
        self::TERMINAL_ID,
        self::ACTION,
        self::CREATED_AT,
        self::UPDATED_AT
    );

    protected $public = array(
        self::ID,
        self::TERMINAL_ID,
        self::ACTION,
        self::CREATED_AT,
        self::UPDATED_AT
    );

    protected $table = \RZP\Constants\Table::TERMINAL_ACTION;

    protected $generateIdOnCreate = true;

    protected $entity = 'terminal_action';

    protected static $sign = '';

    protected static $delimiter = '';

    //protected static $generators = array(self::ID, self::TERMINAL_ID, self::ACTION);

    public function getTerminalId()
    {
        return $this->attributes[self::TERMINAL_ID];
    }

    public function getAction()
    {
        return $this->attributes[self::ACTION];
    }

}
