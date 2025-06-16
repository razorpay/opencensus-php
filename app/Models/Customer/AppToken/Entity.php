<?php

namespace RZP\Models\Customer\AppToken;

use RZP\Models\Base;
use RZP\Constants\Entity as ConstantsEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Customer\Account\CmsGetAttribute;
use RZP\Models\Merchant\Acs\ImplicitJoinHelper;
use RZP\Models\Customer\Entity as CustomerEntity;
use RZP\Models\Merchant\Acs\Traits\AsvGetAttribute;

/**
 * @property-read CustomerEntity $customer
 */
class Entity extends Base\PublicEntity
{
    use SoftDeletes, AsvGetAttribute, CmsGetAttribute;

    const MERCHANT_ID           = 'merchant_id';
    const CUSTOMER_ID           = 'customer_id';
    const DEVICE_TOKEN          = 'device_token';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';

    protected static $sign      = 'capp';

    protected $entity           = 'app_token';

    protected $generateIdOnCreate = true;

    /**
     * Relations to ignore while checking existence of associated entities
     * while saving current entity.
     *
     * @var array
     */
    protected $ignoredRelations = [
        // Required as customer entity will be created via CMS and may not be present in API DB
        ConstantsEntity::CUSTOMER,
    ];

    protected $fillable = [
        self::ID,
        self::DEVICE_TOKEN,
    ];

    protected $visible = [
        self::ID,
        self::DEVICE_TOKEN,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
    ];

    protected $public = [
        self::DEVICE_TOKEN,
        self::CUSTOMER_ID,
    ];

    protected static $generators = [
        self::DEVICE_TOKEN,
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function getDeviceToken()
    {
        return $this->getAttribute(self::DEVICE_TOKEN);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function generateDeviceToken()
    {
        if ($this->getDeviceToken() === null)
        {
            $deviceToken = self::generateUniqueId();

            $this->setAttribute(self::DEVICE_TOKEN, $deviceToken);
        }
    }

    public function getMerchantAttribute()
    {
        return (new ImplicitJoinHelper\ImplicitJoinHelper())->getMerchantAttributeByMerchantId($this, $this->entity);
    }
}
