<?php

namespace RZP\Models\Merchant\Referral;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const  ID                 = 'id';
    const  MERCHANT_ID        = 'merchant_id';
    const  REF_CODE           = 'ref_code';
    const  URL                = 'url';
    const  PRODUCT            = 'product';

    protected $entity = 'referrals';

    protected $generateIdOnCreate = true;

    protected static $generators = [self::ID];

    protected $fillable = [
        self::MERCHANT_ID,
        self::REF_CODE,
        self::URL,
        self::PRODUCT,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::REF_CODE,
        self::URL,
        self::PRODUCT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];


    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    /**
     * @return mixed
     */
    public function getReferralCode()
    {
        return $this->getAttribute(self::REF_CODE);
    }

    /**
     * @return mixed
     */
    public function getReferralLink()
    {
        return $this->getAttribute(self::URL);
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {
        return $this->getAttribute(self::PRODUCT);
    }
}
