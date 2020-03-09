<?php

namespace RZP\Models\Merchant\LegalEntity;

use RZP\Models\Base;
use RZP\Models\Merchant\Detail\BusinessType;

class Entity extends Base\PublicEntity
{
    // this is the same as category in merchants table
    const MCC                  = 'mcc';

    const EXTERNAL_ID          = 'external_id';
    const BUSINESS_TYPE        = 'business_type';
    const BUSINESS_CATEGORY    = 'business_category';
    const BUSINESS_SUBCATEGORY = 'business_subcategory';

    protected $entity = 'legal_entity';

    protected $fillable = [
        self::BUSINESS_TYPE,
        self::MCC,
        self::BUSINESS_CATEGORY,
        self::BUSINESS_SUBCATEGORY,
        self::EXTERNAL_ID,
    ];

    protected $public = [
        self::BUSINESS_TYPE,
        self::MCC,
        self::BUSINESS_CATEGORY,
        self::BUSINESS_SUBCATEGORY,
        self::EXTERNAL_ID,
    ];

    protected $casts = [
        self::BUSINESS_TYPE => 'int',
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function getBusinessType()
    {
        return BusinessType::getKeyFromIndex($this->getBusinessTypeValue());
    }

    public function getBusinessTypeValue()
    {
        return $this->getAttribute(self::BUSINESS_TYPE);
    }

    public function getBusinessCategory()
    {
        return $this->getAttribute(self::BUSINESS_CATEGORY);
    }

    public function getBusinessSubcategory()
    {
        return $this->getAttribute(self::BUSINESS_SUBCATEGORY);
    }

    public function getExternalId()
    {
        return $this->getAttribute(self::EXTERNAL_ID);
    }

    public function getMcc()
    {
        return $this->getAttribute(self::MCC);
    }

    public function merchants()
    {
        return $this->hasMany('RZP\Models\Merchant\Entity');
    }
}
