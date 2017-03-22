<?php

namespace RZP\Models\Workflow\Action\State;

use RZP\Constants\Table;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
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

    protected static $sign = 'a_state';

    protected $entity = 'action_state';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::ADMIN_ID,
        self::ACTION_ID,
        self::NAME,
    ];

    protected $visible = [
        self::ADMIN_ID,
        self::ACTION_ID,
        self::STATE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ADMIN_ID,
        self::ACTION_ID,
        self::STATE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function setNameAttribute(string $state)
    {
        $this->getValidator()->validateState($state);

        $this->attributes[self::NAME] = $state;
    }

    public function setName(string $state)
    {
        $this->setAttribute(self::NAME, $state);
    }

    public function getName() : string
    {
        return $this->getAttribute(self::NAME);
    }

    public function action()
    {
        return $this->belongsTo('RZP\Models\Workflow\Action\Entity');
    }

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }

    public function isClosedState()
    {
        if (in_array($this->getName(), self::CLOSED_STATES, true) === true)
        {
            return true;
        }

        return false;
    }
}
