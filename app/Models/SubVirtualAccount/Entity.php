<?php

namespace RZP\Models\SubVirtualAccount;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Acs\ImplicitJoinHelper;

/**
 * @property Merchant\Entity $masterMerchant
 * @property Balance\Entity $balance
 * @property Merchant\Entity $subMerchant
 */
class Entity extends Base\PublicEntity
{
    const NAME                      = 'name';
    const MASTER_ACCOUNT_NUMBER     = 'master_account_number';
    const SUB_ACCOUNT_NUMBER        = 'sub_account_number';
    const MASTER_MERCHANT_ID        = 'master_merchant_id';
    const MASTER_BALANCE_ID         = 'master_balance_id';
    const SUB_MERCHANT_ID           = 'sub_merchant_id';
    const ACTIVE                    = 'active';
    const SUB_ACCOUNT_TYPE          = 'sub_account_type';
    const SUB_ACCOUNT_BALANCE       = 'sub_account_balance';

    const DESCRIPTION               = 'description';
    const AMOUNT                    = 'amount';
    const CURRENCY                  = 'currency';

    const INPUT                     = 'input';
    const MASTER_ADJUSTMENT_ID      = 'master_adjustment_id';
    const SUB_ADJUSTMENT_ID         = 'sub_adjustment_id';
    const MASTER_ADJUSTMENT_ENTITY  = 'master_adjustment_entity';

    protected $generateIdOnCreate   = true;

    protected $entity               = 'sub_virtual_account';

    protected static $sign          = 'subva';

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $fillable = [
        self::NAME,
        self::MASTER_ACCOUNT_NUMBER,
        self::SUB_ACCOUNT_NUMBER,
        self::SUB_ACCOUNT_TYPE,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::ENTITY,
        self::MASTER_ACCOUNT_NUMBER,
        self::SUB_ACCOUNT_NUMBER,
        self::MASTER_MERCHANT_ID,
        self::SUB_MERCHANT_ID,
        self::MASTER_BALANCE_ID,
        self::ACTIVE,
        self::SUB_ACCOUNT_TYPE,
        self::SUB_ACCOUNT_BALANCE,
        self::CREATED_AT,
    ];

    protected $visible = [
        self::ID,
        self::MASTER_MERCHANT_ID,
        self::SUB_MERCHANT_ID,
        self::MASTER_ACCOUNT_NUMBER,
        self::SUB_ACCOUNT_NUMBER,
        self::MASTER_BALANCE_ID,
        self::NAME,
        self::ACTIVE,
        self::SUB_ACCOUNT_TYPE,
        self::SUB_ACCOUNT_BALANCE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::ACTIVE           => true,
        self::SUB_ACCOUNT_TYPE => Type::DEFAULT,
    ];

    protected $casts = [
        self::ACTIVE => 'bool',
    ];

    // --------------- Getters ---------------

    public function getMasterMerchantId()
    {
        return $this->getAttribute(self::MASTER_MERCHANT_ID);
    }

    public function getSubMerchantId()
    {
        return $this->getAttribute(self::SUB_MERCHANT_ID);
    }

    public function getMasterAccountNumber()
    {
        return $this->getAttribute(self::MASTER_ACCOUNT_NUMBER);
    }

    public function getSubAccountNumber()
    {
        return $this->getAttribute(self::SUB_ACCOUNT_NUMBER);
    }

    public function getActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    public function getMasterBalanceId()
    {
        return $this->getAttribute(self::MASTER_BALANCE_ID);
    }

    public function getSubAccountType()
    {
        return $this->getAttribute(self::SUB_ACCOUNT_TYPE);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    // ------------- End Getters -------------

    // --------------- Setters ---------------

    public function setActive(bool $active)
    {
        return $this->setAttribute(self::ACTIVE, $active);
    }

    public function setSubAccountType($subAccountType)
    {
        return $this->setAttribute(self::SUB_ACCOUNT_TYPE, $subAccountType);
    }

    public function setClosingBalance($closingBalance)
    {
        return $this->setAttribute(self::SUB_ACCOUNT_BALANCE, $closingBalance);
    }

    public function setName($name)
    {
        return $this->setAttribute(self::NAME, $name);
    }

    // ------------- End Setters -------------

    // -------------- Relations --------------

    public function masterMerchant()
    {
        return $this->belongsTo(Merchant\Entity::class, self::MASTER_MERCHANT_ID);
    }

    public function subMerchant()
    {
        return $this->belongsTo(Merchant\Entity::class, self::SUB_MERCHANT_ID);
    }

    public function balance()
    {
        return $this->belongsTo(Balance\Entity::class, self::MASTER_BALANCE_ID);
    }

    // ------------ End Relations ------------

    // --------------- Helpers ---------------

    public function isActive(): bool
    {
        return ($this->getActive() === true);
    }

    public function isSubDirectAccount(): bool
    {
        return ($this->getSubAccountType() === Type::SUB_DIRECT_ACCOUNT);
    }

    public function getMasterMerchantAttribute()
    {
        return (new ImplicitJoinHelper\ImplicitJoinHelper())->getMerchantAttributeByMerchantId($this, $this->entity, "masterMerchant", 'getMasterMerchantId');
    }

    public function getSubMerchantAttribute()
    {
        return (new ImplicitJoinHelper\ImplicitJoinHelper())->getMerchantAttributeByMerchantId($this, $this->entity, "subMerchant", 'getSubMerchantId');
    }



    // ------------- End Helpers -------------
}
