<?php

namespace RZP\Models\BankingAccountTpv;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\Traits\HasBalance;

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
    const PAYER_ACCOUNT_NUMBER       = 'payer_account_number';
    const FUND_ACCOUNT_VALIDATION_ID = 'fund_account_validation_id';

    protected static $sign = 'batpv';

    protected $primaryKey = self::ID;

    protected $entity = Constants\Entity::BANKING_ACCOUNT_TPV;

    protected $generateIdOnCreate = true;

    protected $fillable = [
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
    ];

    protected static $generators = [
        self::ID,
    ];

    protected $defaults = [
        self::IS_ACTIVE                  => 0,
        self::TYPE                       => Type::BANK_ACCOUNT,
        self::FUND_ACCOUNT_VALIDATION_ID => null,
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

    // -------------------- End Getters --------------------------

    // -------------------- Relations -----------------------------

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    // -------------------- End Relations --------------------------
}
