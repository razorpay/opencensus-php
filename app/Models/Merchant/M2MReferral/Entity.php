<?php


namespace RZP\Models\Merchant\M2MReferral;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessDetailConstant;

/**
 * Class Entity
 *
 * @package RZP\Models\Merchant\M2M_REFERRAL
 */
class Entity extends Base\PublicEntity
{
    const ID              = 'id';
    const REFERRER_ID     = 'referrer_id';
    const MERCHANT_ID     = 'merchant_id';
    const METADATA        = 'metadata';
    const CREATED_AT      = 'created_at';
    const UPDATED_AT      = 'updated_at';
    const STATUS          = 'status';
    const REFERRER_STATUS = 'referrer_status';

    protected $entity             = 'm2m_referral';

    protected $generateIdOnCreate = true;

    protected $fillable           = [
        self::MERCHANT_ID,
        self::REFERRER_ID,
        self::METADATA,
        self::STATUS,
        self::REFERRER_STATUS

    ];

    protected $public             = [
        self::MERCHANT_ID,
        self::REFERRER_ID,
        self::METADATA,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::STATUS,
        self::REFERRER_STATUS
    ];

    protected $casts              = [
        self::METADATA => 'array',
    ];

    protected $defaults           = [
        self::METADATA => [],
        self::STATUS   => Status::SIGN_UP,
        self::REFERRER_ID   => null
    ];

    public function getFriendId()
    {
        return $this->getMerchantId();
    }

    public function getRefereeId()
    {
        return $this->getMerchantId();
    }

    public function getId()
    {
        return $this->getMerchantId();
    }

    public function getAdvocateId()
    {
        return $this->getAttribute(self::REFERRER_ID);
    }

    public function getReferrerId()
    {
        return $this->getAttribute(self::REFERRER_ID);
    }

    public function getMetaData()
    {
        return $this->getAttribute(self::METADATA);
    }

    public function getValueFromMetaData($key){
        $metaData   = $this->getAttribute(self::METADATA);

        $code = null;

        if (empty($metaData) === false
            and array_key_exists($key, $metaData))
        {
            $code = $metaData[$key];
        }

        return $code;
    }
    public function getRefereeStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getReferrerStatus()
    {
        return $this->getAttribute(self::REFERRER_STATUS);
    }

}
