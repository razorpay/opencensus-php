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

    const ID                           = 'id';
    const TYPE                         = 'type';
    const STATUS                       = 'status';
    const NOTES                        = 'notes';
    const REMARKS                      = 'remarks';
    const IS_ACTIVE                    = 'is_active';
    const PAYER_IFSC                   = 'payer_ifsc';
    const PAYER_NAME                   = 'payer_name';
    const BALANCE_ID                   = 'balance_id';
    const CREATED_BY                   = 'created_by';
    const MERCHANT_ID                  = 'merchant_id';
    const MERCHANT_IDS                 = 'merchant_ids';
    const PAYER_ACCOUNT_NUMBER         = 'payer_account_number';
    const TRIMMED_PAYER_ACCOUNT_NUMBER = 'trimmed_payer_account_number';
    const FUND_ACCOUNT_VALIDATION_ID   = 'fund_account_validation_id';
    const BANK_NAME                    = 'bank_name';
    const FUND_ACCOUNT_VALIDATION      = 'fund_account_validation';
    const ADMIN                        = 'admin';

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
        self::TRIMMED_PAYER_ACCOUNT_NUMBER,
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
        self::TRIMMED_PAYER_ACCOUNT_NUMBER,
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
        self::IS_ACTIVE                    => 0,
        self::TYPE                         => Type::BANK_ACCOUNT,
        self::FUND_ACCOUNT_VALIDATION_ID   => null,
        //will be removed after tpv p1 tasks are live
        self::CREATED_BY                   => self::ADMIN,
        // TODO: remove after migration for existing records is done for this column.
        self::TRIMMED_PAYER_ACCOUNT_NUMBER => null,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::IS_ACTIVE => 'bool',
    ];

    // -------------------- Getters -----------------------------

    public function getPayerAccountNumber()
    {
        return $this->getAttribute(self::PAYER_ACCOUNT_NUMBER);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getTrimmedPayerAccountNumber()
    {
        return $this->getAttribute(self::TRIMMED_PAYER_ACCOUNT_NUMBER);
    }
    // -------------------- End Getters --------------------------

    // -------------------- Setters -----------------------------

    public function setFundAccountValidationId(string $fundAccountValidationId)
    {
        $this->setAttribute(self::FUND_ACCOUNT_VALIDATION_ID, $fundAccountValidationId);
    }

    public function setIsActive(bool $isActive)
    {
        $this->setAttribute(self::IS_ACTIVE, $isActive);
    }

    public function setTrimmedPayerAccountNumber(string $trimmedPayerAccountNumber)
    {
        $this->setAttribute(self::TRIMMED_PAYER_ACCOUNT_NUMBER, $trimmedPayerAccountNumber);
    }

    // -------------------- End Setters --------------------------


    // -------------------- Relations -----------------------------

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    // -------------------- End Relations --------------------------

    // -------------------- Helpers -----------------------------

    public function trimPayerAccountNumber()
    {
        $payerAccountNumber = (string) $this->getPayerAccountNumber();

        $trimmedPayerAccountNumber = ltrim($payerAccountNumber, '0');

        $this->setTrimmedPayerAccountNumber($trimmedPayerAccountNumber);
    }

    // -------------------- End Helpers --------------------------

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

            //Fund account validation id is not required to expose at X dashboard
            unset($attributes[self::FUND_ACCOUNT_VALIDATION_ID]);
        }
    }

    // -------------------- End Public Setters ----------------------

}
