<?php

namespace RZP\Models\Merchant\Product;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Acs\Traits\AsvGetAttribute;
use RZP\Models\Merchant\Acs\Traits\AsvLoad;

class Entity extends Base\PublicEntity
{
    use AsvGetAttribute, AsvLoad;

    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const PRODUCT_NAME          = 'product_name';
    const ACTIVATION_STATUS     = 'activation_status';

    protected $entity = 'merchant_product';

    protected $generateIdOnCreate = true;

    protected $primaryKey  = self::ID;

    protected static $sign = 'acc_prd';

    //protected static $generators = [self::ID];

    protected $fillable = [
        self::MERCHANT_ID,
        self::PRODUCT_NAME,
        self::ACTIVATION_STATUS,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::PRODUCT_NAME,
        self::ACTIVATION_STATUS,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    const ASV_RELATIONS = [
        'merchant'
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity', self::MERCHANT_ID, self::ID);
    }

    public function getProduct()
    {
        return $this->getAttribute(self::PRODUCT_NAME);
    }

    public function  getStatus()
    {
        return $this->getAttribute(self::ACTIVATION_STATUS);
    }

    public function setActivationStatus($activationStatus)
    {
        $this->setAttribute(self::ACTIVATION_STATUS, $activationStatus);
    }
}
