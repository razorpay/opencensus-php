<?php

namespace RZP\Models\Workflow\Action\Timeline;

use RZP\Constants\Table;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID             = 'id';
    const ADMIN_ID       = 'admin_id';
    const ACTION_ID      = 'action_id';
    const STATE          = 'state';

    protected static $sign = 'a_time';

    protected $entity = 'action_timeline';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::ADMIN_ID,
        self::ACTION_ID,
        self::STATE,
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

    public function setStateAttribute(string $state)
    {
        $this->getValidator()->validateState($state);

        $this->attributes[self::STATE] = $state;
    }

    public function setState(string $state)
    {
        $this->setAttribute(self::STATE, $state);
    }

    public function getState() : string
    {
        return $this->getAttribute(self::STATE);
    }

    public function action()
    {
        return $this->belongsTo('RZP\Models\Workflow\Action\Entity');
    }

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }
}
