<?php

namespace RZP\Models\Comment;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Admin;
use RZP\Constants\Entity as E;
use RZP\Models\Workflow\Action;

class Entity extends Base\PublicEntity
{
    const ADMIN_ID       = 'admin_id';
    const COMMENT        = 'comment';
    const ENTITY_TYPE    = 'entity_type';
    const ENTITY_ID      = 'entity_id';
    const ACTION_ID      = 'action_id';

    // Relations
    const ADMIN          = 'admin';
    const MERCHANT       = 'merchant';

    protected static $sign = 'cmnt';

    protected $entity = 'comment';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::COMMENT,
    ];

    protected $visible = [
        self::ID,
        self::ADMIN_ID,
        self::MERCHANT_ID,
        self::ACTION_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::COMMENT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ADMIN_ID,
        self::ADMIN,
        self::MERCHANT_ID,
        self::MERCHANT,
        self::ACTION_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::COMMENT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $publicSetters = [
        self::ADMIN_ID,
        self::ACTION_ID,
    ];

    public function admin()
    {
        return $this->belongsTo(Admin\Entity::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function entity()
    {
        return $this->morphTo();
    }

    public function setPublicAdminIdAttribute(array & $array)
    {
        $adminId = $this->getAttribute(self::ADMIN_ID);

        $array[self::ADMIN_ID] = Admin\Entity::getSignedIdOrNull($adminId);
    }

    /**
     * TODO: Remove this once action_id attribute has been dropped
     *
     * @param array $array
     */
    public function setPublicActionIdAttribute(array & $array)
    {
        if ($this->getAttribute(self::ENTITY_TYPE) === E::WORKFLOW_ACTION)
        {
            $entityId = $this->getAttribute(self::ENTITY_ID);

            $array[self::ACTION_ID] = Action\Entity::getSignedIdOrNull($entityId);
        }
    }
}
