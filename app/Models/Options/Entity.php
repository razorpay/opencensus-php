<?php

namespace RZP\Models\Options;

use App;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Merchant\Entity as MerchantEntity;

class Entity extends Base\PublicEntity
{

    use SoftDeletes;

    // ------------------ Entity Keys --------------------------------

    const MERCHANT_ID     = 'merchant_id';
    const NAMESPACE       = 'namespace';
    const REFERENCE_ID    = 'reference_id';
    const SERVICE_TYPE    = 'service_type';
    const OPTIONS_JSON    = 'options_json';
    const SCOPE           = 'scope';
    const CREATED_AT      = 'created_at';
    const UPDATED_AT      = 'updated_at';
    const DELETED_AT      = 'deleted_at';

    protected static $sign = 'opt';

    protected $entity = 'options';

    protected $generateIdOnCreate = true;

    // Request input keys used in various other endpoint calls.
    const NAMESPACE_KEY             = 'namespace';
    const SERVICE_TYPE_KEY          = 'service_type';
    const REFERENCE_ID_KEY          = 'reference_id';
    const SCOPE_KEY                 = 'scope';
    const OPTIONS                   = 'options';
    const ORDER                     = 'order';

    // The request payload
    const REQUEST                = [
        self::NAMESPACE_KEY,
        self::SERVICE_TYPE,
        self::REFERENCE_ID_KEY,
        self::SCOPE_KEY,
        self::OPTIONS
    ];

    protected $fillable = [
        self::MERCHANT_ID,
        self::NAMESPACE,
        self::REFERENCE_ID,
        self::SERVICE_TYPE,
        self::OPTIONS_JSON,
        self::SCOPE,
        self::CREATED_AT
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::NAMESPACE,
        self::REFERENCE_ID,
        self::SERVICE_TYPE,
        self::SCOPE,
        self::OPTIONS_JSON,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT
    ];

    protected $hosted = [
        self::OPTIONS_JSON
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT
    ];

    protected $defaults = [
        // defaulted as this feature is only being developed for PL currently
        self::NAMESPACE                => Constants::NAMESPACE_PAYMENT_LINKS,
        self::REFERENCE_ID             => null,
        self::SERVICE_TYPE             => Constants::SERVICE_PAYMENT_LINKS,
        self::SCOPE                    => Constants::SCOPE_GLOBAL
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(MerchantEntity::class, self::MERCHANT_ID, MerchantEntity::ID);
    }

    public function setMerchantId(string $merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setOptionsJson(array $optionsJson)
    {
        $this->setAttribute(self::OPTIONS_JSON, $this->asJson($optionsJson));
    }

    public function setScope(string $scope)
    {
        $this->setAttribute(self::SCOPE, $scope);
    }

    public function setNamespace(string $namespace)
    {
        $this->setAttribute(self::NAMESPACE, $namespace);
    }

    public function setServiceType(string $serviceType)
    {
        $this->setAttribute(self::SERVICE_TYPE, $serviceType);
    }

    public function setReferenceId(string $referenceId)
    {
        $this->setAttribute(self::REFERENCE_ID, $referenceId);
    }
}
