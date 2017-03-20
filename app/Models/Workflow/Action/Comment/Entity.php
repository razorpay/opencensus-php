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

    protected static $sign = 'a_comment';

    protected $entity = 'action_comment';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::COMMENT,
        self::ADMIN_ID,
        self::ACTION_ID,
        self::ADMIN_ID,
        self::COMMENT,
    ];

    protected $visible = [
        self::ID,
        self::COMMENT,
        self::ADMIN_ID,
        self::ACTION_ID,
        self::COMMENT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::COMMENT,
        self::ADMIN_ID,
        self::ACTION_ID,
        self::COMMENT,
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

    public function setCommentAttribute(string $comment)
    {
        // TODO Sanitize the comment
        $this->attributes[self::COMMENT] = $comment;
    }

    public function setComment(string $comment)
    {
        $this->setAttribute(self::COMMENT);
    }

    public function getComment() : string
    {
        return $this->getAttribute(self::COMMENT);
    }
}
