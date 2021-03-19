<?php


namespace RZP\Models\Reward;

use RZP\Models\Base;


class Entity extends Base\PublicEntity
{
    const NAME                           = 'name';
    const PERCENT_RATE                   = 'percent_rate';
    const MIN_AMOUNT                     = 'min_amount';
    const MAX_CASHBACK                   = 'max_cashback';
    const FLAT_CASHBACK                  = 'flat_cashback';
    const STARTS_AT                      = 'starts_at';
    const ENDS_AT                        = 'ends_at';
    const DISPLAY_TEXT                   = 'display_text';
    const TERMS                          = 'terms';
    const COUPON_CODE                    = 'coupon_code';
    const LOGO                           = 'logo';
    const ADVERTISER_ID                  = 'advertiser_id';
    const IS_DELETED                     = 'is_deleted';
    const MERCHANT_WEBSITE_REDIRECT_LINK = 'merchant_website_redirect_link';
    const BRAND_NAME                     =  'brand_name';
    //Attribute lengths
    const NAME_LENGTH         = 50;
    const DISPLAY_TEXT_LENGTH = 255;
    CONST BRAND_NAME_LENGTH   = 26;

    protected $entity      = 'reward';

    protected static $sign = 'reward';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
        self::DISPLAY_TEXT,
        self::PERCENT_RATE,
        self::COUPON_CODE,
        self::LOGO,
        self::ADVERTISER_ID,
        self::ENDS_AT,
        self::STARTS_AT,
        self::FLAT_CASHBACK,
        self::MAX_CASHBACK,
        self::MIN_AMOUNT,
        self::TERMS,
        self::MERCHANT_WEBSITE_REDIRECT_LINK,
        self::BRAND_NAME,
    ];

    protected $visible = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::DISPLAY_TEXT,
        self::PERCENT_RATE,
        self::COUPON_CODE,
        self::LOGO,
        self::ADVERTISER_ID,
        self::ENDS_AT,
        self::STARTS_AT,
        self::FLAT_CASHBACK,
        self::MAX_CASHBACK,
        self::MIN_AMOUNT,
        self::TERMS,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::IS_DELETED,
        self::MERCHANT_WEBSITE_REDIRECT_LINK,
        self::BRAND_NAME,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::DISPLAY_TEXT,
        self::PERCENT_RATE,
        self::COUPON_CODE,
        self::LOGO,
        self::ADVERTISER_ID,
        self::ENDS_AT,
        self::STARTS_AT,
        self::FLAT_CASHBACK,
        self::MAX_CASHBACK,
        self::MIN_AMOUNT,
        self::TERMS,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::IS_DELETED,
        self::MERCHANT_WEBSITE_REDIRECT_LINK,
        self::BRAND_NAME,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::IS_DELETED => false,
    ];

    public function build(array $input = [], string $operation = 'create_reward')
    {
        $this->getValidator()->validateInput($operation, $input);

        $this->getValidator()->validateRewardPeriod($input);

        $this->fillAndGenerateId($input);

        return $this;
    }

    public function setIsDeleted($isDeleted)
    {
        $this->setAttribute(self::IS_DELETED, $isDeleted);
    }

    public function setStartsAt($startsAt)
    {
        $this->setAttribute(self::STARTS_AT, $startsAt);
    }

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getLogo()
    {
        return $this->getAttribute(self::LOGO);
    }

    public function getEndsAt()
    {
        return $this->getAttribute(self::ENDS_AT);
    }

    public function getStartsAt()
    {
        return $this->getAttribute(self::STARTS_AT);
    }

    public function getCouponCode()
    {
        return $this->getAttribute(self::COUPON_CODE);
    }

    public function getTerms()
    {
        return $this->getAttribute(self::TERMS);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getDisplayText()
    {
        return $this->getAttribute(self::DISPLAY_TEXT);
    }

    public function getPercentRate()
    {
        return $this->getAttribute(self::PERCENT_RATE);
    }

    public function getFlatCashback()
    {
        return $this->getAttribute(self::FLAT_CASHBACK);
    }

    public function getMaxCashback()
    {
        return $this->getAttribute(self::MAX_CASHBACK);
    }

    public function getMinAmount()
    {
        return $this->getAttribute(self::MIN_AMOUNT);
    }

    public function getIsDeleted()
    {
        return $this->getAttribute(self::IS_DELETED);
    }

}
