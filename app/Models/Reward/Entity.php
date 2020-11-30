<?php


namespace RZP\Models\Reward;

use RZP\Models\Base;


class Entity extends Base\PublicEntity
{
    const NAME                = 'name';
    const PERCENT_RATE        = 'percent_rate';
    const MIN_AMOUNT          = 'min_amount';
    const MAX_CASHBACK        = 'max_cashback';
    const FLAT_CASHBACK       = 'flat_cashback';
    const STARTS_AT           = 'starts_at';
    const ENDS_AT             = 'ends_at';
    const DISPLAY_TEXT        = 'display_text';
    const TERMS               = 'terms';
    const COUPON_CODE         = 'coupon_code';
    const LOGO                = 'logo';
    const ADVERTISER_ID       = 'advertiser_id';
    const IS_DELETED          = 'is_deleted';
    //Attribute lengths
    const NAME_LENGTH         = 50;
    const DISPLAY_TEXT_LENGTH = 255;


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
        self::TERMS
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
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::IS_DELETED => false,
    ];

    public function getStartsAt()
    {
        return $this->getAttribute(self::STARTS_AT);
    }

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
}
