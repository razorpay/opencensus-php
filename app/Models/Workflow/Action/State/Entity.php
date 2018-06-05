<?php

namespace RZP\Models\Workflow\Action\State;

use RZP\Constants\Table;
use RZP\Models\Workflow\Base;

class Entity extends Base\Entity
{
    const ID             = 'id';
    const ADMIN_ID       = 'admin_id';
    const ACTION_ID      = 'action_id';
    const NAME           = 'name';

    // States of the action
    const APPROVED     = 'approved';
    const REJECTED     = 'rejected';
    const EXECUTED     = 'executed';
    const OPEN         = 'open';
    const CLOSED       = 'closed';
    const FAILED       = 'failed';

    // Action States post which we do not accept any state changes
    const CLOSED_STATES = [
        self::REJECTED,
        self::EXECUTED,
        self::CLOSED,
        self::FAILED,
    ];

    const OPEN_STATES = [
        self::OPEN,
        self::APPROVED,
    ];

    protected static $sign = 'a_state';

    protected $entity = 'action_state';

    protected $generateIdOnCreate = false;

    protected $visible = [
        self::ADMIN_ID,
        self::ACTION_ID,
        self::NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ADMIN_ID,
        self::ACTION_ID,
        self::NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $publicSetters = [
        self::ADMIN_ID,
        self::ACTION_ID,
    ];

    public function setNameAttribute(string $state)
    {
        $this->getValidator()->validateName(self::NAME, $state);

        $this->attributes[self::NAME] = $state;
    }

    // public function action()
    // {
    //     return $this->belongsTo('RZP\Models\Workflow\Action\Entity');
    // }

    // public function admin()
    // {
    //     return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    // }
}
