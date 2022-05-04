<?php

namespace RZP\Models\Merchant\InternationalIntegration;

use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;
    use Base\Traits\NotesTrait;

    const ID                                = "id";
    const MERCHANT_ID                       = "merchant_id";
    const INTEGRATION_ENTITY                = "integration_entity";
    const INTEGRATION_KEY                   = "integration_key";
    const NOTES                             = "notes";
    const CREATED_AT                        = "created_at";
    const UPDATED_AT                        = "updated_at";
    const DELETED_AT                        = "deleted_at";

    protected $entity      = 'merchant_international_integrations';

    protected $primaryKey  = self::ID;

    protected $fillable    = [
        self::MERCHANT_ID,
        self::INTEGRATION_ENTITY,
        self::INTEGRATION_KEY,
        self::NOTES,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public      = [
        self::ID,
        self::MERCHANT_ID,
        self::INTEGRATION_ENTITY,
        self::INTEGRATION_KEY,
        self::NOTES,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $dates        = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $defaults     = [
        self::NOTES           => [],
        self::UPDATED_AT      => null,
        self::DELETED_AT      => null,
    ];

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getIntegrationEntity()
    {
        return $this->getAttribute(self::INTEGRATION_ENTITY);
    }

    public function getIntegrationKey()
    {
        return $this->getAttribute(self::INTEGRATION_KEY);
    }
}
