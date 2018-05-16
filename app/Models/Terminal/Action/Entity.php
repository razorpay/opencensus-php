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

    // We would ideally want only one time field called timestamp.
    // However, laravel implementation dictates having created_at and
    // updated at.
    const CREATED_AT                    = 'created_at';

    const UPDATED_AT                    = 'updated_at';

    const ACTION_STATES = ['ACTIVATED','SUSPENDED','PRIORITY_CHANGE'];

    protected $fillable = [
        self::ACTION,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::TERMINAL_ID,
        self::ACTION,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $entity = 'terminal_action';

    public function getTerminalId()
    {
        return $this->getAttribute(self::TERMINAL_ID);
    }

    public function getAction()
    {
        return $this->getAttribute(self::ACTION);
    }
}
