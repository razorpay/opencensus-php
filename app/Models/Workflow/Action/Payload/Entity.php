<?php

namespace RZP\Models\Workflow\Action\Payload;

use RZP\Constants\Table;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID             = 'id';
    const REQUEST        = 'request';
    const ACTION_ID      = 'action_id';
    const ENCRYPTED      = 'encrypted';

    protected $entity = 'action_payload';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::WORKFLOW_ID,
        self::ACTION_ID,
        self::ENCRYPTED,
    ];

    protected $visible = [
        self::ACTION_ID,
        self::ENCRYPTED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ACTION_ID,
        self::ENCRYPTED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function action()
    {
        return $this->belongsTo('RZP\Models\Workflow\Action\Payload\Entity');
    }
}
