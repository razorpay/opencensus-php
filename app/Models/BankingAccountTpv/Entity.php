<?php

namespace RZP\Models\BankingAccountTpv;

use Razorpay\IFSC\IFSC;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Models\FundAccount\Validation\Repository as FundAccountValidation;

/**
 * @property Balance\Entity         $balance
 */
class Entity extends Base\PublicEntity
{
    use HasBalance;

    const ID                         = 'id';
    const TYPE                       = 'type';
    const STATUS                     = 'status';
    const NOTES                      = 'notes';
    const REMARKS                    = 'remarks';
    const IS_ACTIVE                  = 'is_active';
    const PAYER_IFSC                 = 'payer_ifsc';
    const PAYER_NAME                 = 'payer_name';
    const BALANCE_ID                 = 'balance_id';
    const CREATED_BY                 = 'created_by';
    const MERCHANT_ID                = 'merchant_id';
    const MERCHANT_IDS               = 'merchant_ids';
    const PAYER_ACCOUNT_NUMBER       = 'payer_account_number';
    const FUND_ACCOUNT_VALIDATION_ID = 'fund_account_validation_id';
    const BANK_NAME                  = 'bank_name';
    const FUND_ACCOUNT_VALIDATION    = 'fund_account_validation';
    const ADMIN                      = 'admin';

    protected static $sign = 'batpv';

    protected $primaryKey = self::ID;

    protected $entity = Constants\Entity::BANKING_ACCOUNT_TPV;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::BALANCE_ID,
        self::TYPE,
        self::STATUS,
        self::PAYER_NAME,
        self::PAYER_ACCOUNT_NUMBER,
        self::PAYER_IFSC,
        self::CREATED_BY,
        self::REMARKS,
        self::NOTES,
        self::FUND_ACCOUNT_VALIDATION_ID,
    ];

    protected $visible = [
        self::ID,
        self::TYPE,
        self::NOTES,
        self::REMARKS,
        self::IS_ACTIVE,
        self::PAYER_NAME,
        self::PAYER_IFSC,
        self::BALANCE_ID,
        self::CREATED_BY,
        self::MERCHANT_ID,
        self::PAYER_ACCOUNT_NUMBER,
        self::FUND_ACCOUNT_VALIDATION_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::STATUS,
        self::BANK_NAME,
        self::FUND_ACCOUNT_VALIDATION,
    ];

    protected $public = [
        self::ID,
        self::TYPE,
        self::STATUS,
        self::NOTES,
        self::REMARKS,
        self::IS_ACTIVE,
        self::BANK_NAME,
        self::CREATED_AT,
        self::CREATED_BY,
        self::UPDATED_AT,
        self::PAYER_NAME,
        self::BALANCE_ID,
        self::MERCHANT_ID,
        self::PAYER_IFSC,
        self::PAYER_ACCOUNT_NUMBER,
        self::FUND_ACCOUNT_VALIDATION_ID,
        self::FUND_ACCOUNT_VALIDATION,
    ];

    protected $publicSetters = [
        self::FUND_ACCOUNT_VALIDATION,
        self::BANK_NAME,
    ];

    protected static $generators = [
        self::ID,
    ];

    protected $defaults = [
        self::IS_ACTIVE                  => 0,
        self::TYPE                       => Type::BANK_ACCOUNT,
        self::FUND_ACCOUNT_VALIDATION_ID => null,
        //will be removed after tpv p1 tasks are live
        self::CREATED_BY                 => self::ADMIN,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::IS_ACTIVE => 'bool',
    ];

    // -------------------- Getters -----------------------------

    public function getAccountNumber()
    {
        return $this->getAttribute(self::PAYER_ACCOUNT_NUMBER);
    }

    public function getState()
    {
        return $this->getAttribute(self::STATUS);
    }
    // -------------------- End Getters --------------------------

    // -------------------- Setters -----------------------------

    public function setFundAccountValidationId(string $id)
    {
        $this->setAttribute(self::FUND_ACCOUNT_VALIDATION_ID, $id);
    }

    public function setIsActive(bool $val)
    {
        $this->setAttribute(self::IS_ACTIVE, $val);
    }

    // -------------------- End Setters --------------------------


    // -------------------- Relations -----------------------------

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    // -------------------- End Relations --------------------------

    // -------------------- Public Setters --------------------------

    public function setPublicFundAccountValidationAttribute(array & $attributes)
    {
        if (app('basicauth')->isAdminAuth() === true)
        {
            $attributes[self::FUND_ACCOUNT_VALIDATION] = [];

            $fundAccountValId = $this->getAttribute(self::FUND_ACCOUNT_VALIDATION_ID);

            if (empty($fundAccountValId) === false)
            {
                $favEntity = (new FundAccountValidation())->findOrFail($fundAccountValId);

                $attributes[self::FUND_ACCOUNT_VALIDATION] = $favEntity->toArrayPublic();
            }
        }
    }

    public function setPublicBankNameAttribute(array & $attributes)
    {
        if (app('basicauth')->isProxyAuth() === true)
        {
            $attributes[self::BANK_NAME] = IFSC::getBankName($attributes[Entity::PAYER_IFSC]);
        }
    }

    // -------------------- End Public Setters ----------------------

}
