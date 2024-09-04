<?php

namespace RZP\Models\State;

use RZP\Models\Base;
use RZP\Models\Merchant\Acs\Traits\AsvGetAttribute;
use RZP\Models\User;
use RZP\Models\Merchant;
use RZP\Models\Admin\Admin;
use RZP\Models\Workflow\Action;
use RZP\Constants\Entity as E;
use RZP\Models\State\Reason;

class Entity extends Base\PublicEntity
{
    use AsvGetAttribute;
    const ADMIN_ID             = 'admin_id';
    const USER_ID              = 'user_id';
    const ACTION_ID            = 'action_id';
    const NAME                 = 'name';
    const ENTITY_TYPE          = 'entity_type';
    const ENTITY_ID            = 'entity_id';
    const REJECTION_REASONS    = 'rejection_reasons';
    const UPDATED_BY           = 'updated_by';

    protected static $sign = 'state';

    protected $entity = 'state';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
        self::CREATED_AT,
        self::UPDATED_BY
    ];

    protected $visible = [
        self::ADMIN_ID,
        self::MERCHANT_ID,
        self::USER_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::REJECTION_REASONS,
        self::ACTION_ID,
        self::NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::UPDATED_BY
    ];

    protected $public = [
        self::ADMIN_ID,
        self::MERCHANT_ID,
        self::USER_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::REJECTION_REASONS,
        self::ACTION_ID,
        self::NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::UPDATED_BY
    ];

    protected $publicSetters = [
        self::ADMIN_ID,
        self::ACTION_ID,
    ];

    protected $defaults = [
        self::UPDATED_BY => null
    ];

    protected $casts = [
        self::UPDATED_BY => 'string'
    ];

    public function admin()
    {
        return $this->belongsTo(Admin\Entity::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function user()
    {
        return $this->belongsTo(User\Entity::class);
    }

    public function account()
    {
        return $this->belongsTo(Merchant\Entity::class, Entity::MERCHANT_ID);
    }

    public function entity()
    {
        return $this->morphTo();
    }

    public function rejectionReasons()
    {
        return $this->hasMany('RZP\Models\State\Reason\Entity')
                    ->where(Reason\Entity::REASON_TYPE, Reason\ReasonType::REJECTION);
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

    public function setCreatedAt($createdAt)
    {
        $this->setAttribute(self::CREATED_AT, $createdAt);
    }

    public function setUpdatedBy(string $metadata)
    {
        $this->setAttribute(self::UPDATED_BY, $metadata);
    }

    public function getUpdatedBy()
    {
        $this->getAttribute(self::UPDATED_BY);
    }

}
