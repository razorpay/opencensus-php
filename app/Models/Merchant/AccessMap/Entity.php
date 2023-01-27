<?php

namespace RZP\Models\Merchant\AccessMap;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ENTITY_TYPE = 'entity_type';

    const ENTITY_ID   = 'entity_id';

    const APPLICATION = 'application';

    const APPLICATION_ID = 'application_id';

    // to store owner of the entity mapped to the merchant
    const ENTITY_OWNER_ID = 'entity_owner_id';

    const HAS_KYC_ACCESS = 'has_kyc_access';

    protected $entity = Constants\Entity::MERCHANT_ACCESS_MAP;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        /**
         * Entity id and type are fillable due to
         * application entries that come from external services
         */
        self::ENTITY_ID,
        self::ENTITY_TYPE,
    ];

    protected $defaults = [
        self::HAS_KYC_ACCESS => 0,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::ENTITY_OWNER_ID,
        self::HAS_KYC_ACCESS,
    ];

    protected $casts = [
        self::HAS_KYC_ACCESS => 'bool',
    ];

    protected $public = [
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::ENTITY_OWNER_ID,
        self::CREATED_AT,
        self::HAS_KYC_ACCESS,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    /**
     * {@inheritDoc}
     */
    protected $dispatchesEvents = [
        // Event 'saved' fires on insert and update both.
        'saved'   => EventSaved::class,
        'deleted' => EventDeleted::class,
    ];

    // --------------- Relation to other entities ------------------------------

    public function entity()
    {
        return $this->morphTo();
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function entityOwner()
    {
        return $this->belongsTo(Merchant\Entity::class, self::ENTITY_OWNER_ID);
    }

    public function getEntityOwnerId()
    {
        return $this->getAttribute(self::ENTITY_OWNER_ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function hasKycAccess(): bool
    {
        return $this->getAttribute(self::HAS_KYC_ACCESS);
    }

    public function setHasKycAccess()
    {
        $this->setAttribute(self::HAS_KYC_ACCESS, true);
    }

    public function removeKycAccess()
    {
        $this->setAttribute(self::HAS_KYC_ACCESS, false);
    }

    public function setEntityId(string $entityId)
    {
        $this->setAttribute(self::ENTITY_ID, $entityId);
    }

    public function getDeletedAt()
    {
        return $this->getAttribute(self::DELETED_AT);
    }
}
