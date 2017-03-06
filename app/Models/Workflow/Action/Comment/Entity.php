<?php

namespace RZP\Models\Workflow\Action\Comment;

use RZP\Constants\Table;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID             = 'id';
    const ACTION_ID      = 'action_id';
    const ADMIN_ID       = 'admin_id';
    const COMMENT        = 'comment';
    const PAYLOAD_ID     = 'payload_id';

    protected $entity = 'action_comment';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::ADMIN_ID,
        self::PAYLOAD_ID,
    ];

    protected $visible = [
        self::ADMIN_ID,
        self::PAYLOAD_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ADMIN_ID,
        self::PAYLOAD_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }

    public function action()
    {
        return $this->belongsTo('RZP\Models\Workflow\Action\Entity');
    }
}
