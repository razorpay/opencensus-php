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
    const UNIQUE_COUPON_CODES            = 'unique_coupon_codes';
    const LOGO                           = 'logo';
    const ADVERTISER_ID                  = 'advertiser_id';
    const IS_DELETED                     = 'is_deleted';
    const MERCHANT_WEBSITE_REDIRECT_LINK = 'merchant_website_redirect_link';
    const BRAND_NAME                     =  'brand_name';
    const UNIQUE_COUPONS_EXIST           = 'unique_coupons_exist';
    const UNIQUE_COUPONS_EXHAUSTED       = 'unique_coupons_exhausted';
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
        self::UNIQUE_COUPONS_EXIST,
        self::UNIQUE_COUPONS_EXHAUSTED
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
        self::UNIQUE_COUPONS_EXIST,
        self::UNIQUE_COUPONS_EXHAUSTED
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
        self::UNIQUE_COUPONS_EXIST,
        self::UNIQUE_COUPONS_EXHAUSTED
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

        $this->getValidator()->validateGenericOrUniqueCoupons($input);

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

    public function setUniqueCouponsExist($uniqueCouponsExist)
    {
        $this->setAttribute(self::UNIQUE_COUPONS_EXIST, $uniqueCouponsExist);
    }

    public function setUniqueCouponsExhausted($uniqueCouponsExhausted)
    {
        $this->setAttribute(self::UNIQUE_COUPONS_EXHAUSTED, $uniqueCouponsExhausted);
    }

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getAdvertiserId()
    {
        return $this->getAttribute(self::ADVERTISER_ID);
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

    public function getUniqueOrGenericCouponCode(){
        /*
         * First get the uniq exists and exhausted flags
         * If uniq coupons dont exist, send the generic coupon
         * If exist, check if exhausted
         * If coupons exhausted, check if generic coupon present and send it
         * If generic coupon not present, it should not have come to this, so send a mail and coupon to null
         * if not exhausted, fetch and update uniq coupon code and update redis count
         * if redis count < 100, then  mark exhausted in rewards table
         */

        $uniqueCouponsExist = $this->getUniqueCouponsExist();

        $uniqueCouponsExhausted = $this->getUniqueCouponsExhausted();

        //If reward has only a generic coupon code
        if($uniqueCouponsExist === 0)
        {
            $genericCouponCode = $this->getCouponCode();

            return [$genericCouponCode, 'generic'];
        }

        //If reward has unique coupon codes
        //If the unique coupon codes are exhausted
        if($uniqueCouponsExhausted === 1)
        {
            $genericCouponCode = $this->getCouponCode();
            if(isset($genericCouponCode) === false)
            {
                $uniqueCouponCode = (new RewardCoupon\Core())->getUniqueCouponCodeForReward($this);

                if(isset($uniqueCouponCode))
                {
                    return [$uniqueCouponCode, 'unique'];
                }
                else
                {
                    //This should not happen ideally since we already have 100 coupons as buffer
                    //So log an error and send trying to access exhausted coupon codes mail
                    //With this we can identify and adjust the min uniq coupons threshold later
                    (new RewardCoupon\Core())->sendCouponCountThresholdMail($this, 0);

                    return [null, 'unique'];
                }
            }
            else
            {
                return [$genericCouponCode, 'generic'];
            }
        }

        //Remaining case -> unique coupon codes are not exhausted and are present to distribute
        //Get a unique coupon code and return it
        $uniqueCouponCode = (new RewardCoupon\Core())->getUniqueCouponCodeForReward($this);

        return [$uniqueCouponCode, 'unique'];
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

    public function getMerchantWebsiteRedirectLink()
    {
        return $this->getAttribute(self::MERCHANT_WEBSITE_REDIRECT_LINK);
    }

    public function getBrandName()
    {
        return $this->getAttribute(self::BRAND_NAME);
    }

    public function getUniqueCouponsExist()
    {
        return $this->getAttribute(self::UNIQUE_COUPONS_EXIST);
    }

    public function getUniqueCouponsExhausted()
    {
        return $this->getAttribute(self::UNIQUE_COUPONS_EXHAUSTED);
    }
}
