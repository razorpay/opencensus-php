<?php

namespace RZP\Models\State;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Admin;
use RZP\Models\Workflow\Action;
use RZP\Constants\Entity as E;

class Entity extends Base\PublicEntity
{
    const ADMIN_ID             = 'admin_id';
    const ACTION_ID            = 'action_id';
    const NAME                 = 'name';
    const ENTITY_TYPE          = 'entity_type';
    const ENTITY_ID            = 'entity_id';

    protected static $sign = 'state';

    protected $entity = 'state';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
    ];

    protected $visible = [
        self::ADMIN_ID,
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::ACTION_ID,
        self::NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ADMIN_ID,
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::ACTION_ID,
        self::NAME,
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

    public function setPublicAdminIdAttribute(array & $attributes)
    {
        $adminId = $this->getAttribute(self::ADMIN_ID);

        $attributes[self::ADMIN_ID] = Admin\Entity::getSignedIdOrNull($adminId);
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
