<?php

namespace RZP\Models\D2cBureauDetail;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Settings;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    protected $generateIdOnCreate = true;

    protected static $sign = 'd2cbd';

    protected $entity = 'd2c_bureau_detail';

    const ID_LENGTH = 14;

    const SETTING_MAX_LOAN_AMOUNT_KEY = 'max_loan_amount';

    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const USER_ID           = 'user_id';
    const FIRST_NAME        = 'first_name';
    const LAST_NAME         = 'last_name';
    const DATE_OF_BIRTH     = 'date_of_birth';
    const GENDER            = 'gender';
    const CONTACT_MOBILE    = 'contact_mobile';
    const EMAIL             = 'email';
    const ADDRESS           = 'address';
    const CITY              = 'city';
    const STATE             = 'state';
    const PINCODE           = 'pincode';
    const PAN               = 'pan';
    const STATUS            = 'status';
    const VERIFIED_AT       = 'verified_at';
    const CREATED_AT        = 'created_at';
    const UPDATED_AT        = 'updated_at';

    protected $public = [
        self::ID,
        self::FIRST_NAME,
        self::LAST_NAME,
        self::DATE_OF_BIRTH,
        self::GENDER,
        self::CONTACT_MOBILE,
        self::EMAIL,
        self::ADDRESS,
        self::CITY,
        self::STATE,
        self::PINCODE,
        self::PAN,
        self::CREATED_AT,
    ];

    protected $fillable = [
        self::FIRST_NAME,
        self::LAST_NAME,
        self::DATE_OF_BIRTH,
        self::GENDER,
        self::CONTACT_MOBILE,
        self::EMAIL,
        self::ADDRESS,
        self::CITY,
        self::STATE,
        self::PINCODE,
        self::PAN,
        self::STATUS,
        self::VERIFIED_AT,
    ];

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    public function user()
    {
        return $this->belongsTo(\RZP\Models\User\Entity::class);
    }

    public function B2cBureauReports()
    {
        return $this->hasMany(\RZP\Models\D2cBureauReport\Entity::class);
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setVerifiedAt(int $verifiedAt)
    {
        $this->setAttribute(self::VERIFIED_AT, $verifiedAt);
    }

    public function setVerifiedAtNull()
    {
        $this->setAttribute(self::VERIFIED_AT, null);
    }

    public function getContactMobile()
    {
        return $this->getAttribute(self::CONTACT_MOBILE);
    }

    public function getUserId(): string
    {
        return $this->getAttribute(self::USER_ID);
    }

    protected function getSettingsAccessor(): Settings\Accessor
    {
        return Settings\Accessor::for($this->merchant, Settings\Module::D2C_BUREAU_CAMPAIGN);
    }

    public function getMaxLoanAmount()
    {
        return $this->getSettingsAccessor()->get(self::SETTING_MAX_LOAN_AMOUNT_KEY);
    }
}
