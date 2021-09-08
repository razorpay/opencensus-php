<?php


namespace RZP\Models\VirtualAccountTpv;

use RZP\Constants;
use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const VIRTUAL_ACCOUNT_ID            = 'virtual_account_id';
    const ENTITY_TYPE                   = 'entity_type';
    const ENTITY_ID                     = 'entity_id';
    const IS_ACTIVE                     = 'is_active';
    const DEACTIVATED_AT                = 'deactivated_at';
    const TYPE                          = 'type';

    protected static $sign = 'vatpv';

    protected $entity = Constants\Entity::VIRTUAL_ACCOUNT_TPV;

    protected $primaryKey = self::ID;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::VIRTUAL_ACCOUNT_ID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::IS_ACTIVE,
        self::DEACTIVATED_AT,
    ];

    protected $visible = [
        self::ID,
        self::VIRTUAL_ACCOUNT_ID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::IS_ACTIVE,
        self::DEACTIVATED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::IS_ACTIVE         => 'bool',
    ];

    // -------------------- Associations --------------------

    public function virtualAccount()
    {
        return $this->belongsTo('RZP\Models\VirtualAccount\Entity');
    }

    public function entity()
    {
        return $this->morphTo()->withTrashed();
    }

    // -------------------- End Associations --------------------

    // -------------------- Getters --------------------

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    // -------------------- End Getters --------------------

    // -------------------- Setters --------------------

    public function setIsActive(bool $isActive)
    {
        $this->setAttribute(self::IS_ACTIVE, $isActive);
    }

    public function setDeactivatedAt(string $deactivatedAt)
    {
        $this->setAttribute(self::DEACTIVATED_AT, $deactivatedAt);
    }

    // -------------------- End Setters --------------------

    public function deactivate(string $deactivatedAt)
    {
        $this->setIsActive(false);

        $this->setDeactivatedAt($deactivatedAt);
    }

    public function getAllowedPayerDetails()
    {
        $tpvEntity = $this->entity()->first();

        if ($tpvEntity === null)
        {
            return [];
        }

        $allowedPayer[self::TYPE]             = $this->getEntityType();
        $allowedPayer[self::ID]               = $tpvEntity->getPublicId();
        $allowedPayer[$this->getEntityType()] = $tpvEntity->getVirtualAccountTpvData();

        return $allowedPayer;
    }

    public function isDuplicate($allowedPayer)
    {
        $type = $allowedPayer[self::TYPE];

        if ($type !== $this->getEntityType())
        {
            return false;
        }
        $existingPayer = $this->entity()->first()->getVirtualAccountTpvData();

        if (empty(array_diff($allowedPayer[$type], $existingPayer)) === true)
        {
            return true;
        }

        return false;
    }
}
