<?php

namespace RZP\Models\Workflow\Action;

use RZP\Constants\Table;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID             = 'id';
    const WORKFLOW_ID    = 'workflow_id';
    const ADMIN_ID       = 'admin_id';
    const PAYLOAD_ID     = 'payload_id';

    protected static $sign = 'w_action';

    protected $entity = 'workflow_action';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::WORKFLOW_ID,
        self::ADMIN_ID,
        self::PAYLOAD_ID,
    ];

    protected $visible = [
        self::WORKFLOW_ID,
        self::ADMIN_ID,
        self::PAYLOAD_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::WORKFLOW_ID,
        self::ADMIN_ID,
        self::PAYLOAD_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function workflow()
    {
        return $this->belongsTo('RZP\Models\Workflow\Entity');
    }

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }
}
