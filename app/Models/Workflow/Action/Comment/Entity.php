<?php

namespace RZP\Models\Workflow\Action\Comment;

use RZP\Models\Workflow\Base;

class Entity extends Base\Entity
{
    const ID             = 'id';
    const ACTION_ID      = 'action_id';
    const ADMIN_ID       = 'admin_id';
    const COMMENT        = 'comment';

    // Relations
    const ADMIN         = 'admin';

    protected static $sign = 'a_comment';

    protected $entity = 'action_comment';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::COMMENT,
    ];

    protected $visible = [
        self::ID,
        self::ADMIN_ID,
        self::ADMIN,
        self::ACTION_ID,
        self::COMMENT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ADMIN_ID,
        self::ADMIN,
        self::ACTION_ID,
        self::COMMENT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ADMIN_ID,
        self::ACTION_ID,
    ];

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }

    public function action()
    {
        return $this->belongsTo('RZP\Models\Workflow\Action\Entity');
    }

    public function setCommentAttribute(string $comment)
    {
        // TODO Sanitize the comment
        $this->attributes[self::COMMENT] = $comment;
    }
}
