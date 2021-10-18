<?php

namespace RZP\Models\Merchant\Balance\SubBalanceMap;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    protected $entity = Constants\Entity::SUB_BALANCE_MAP;

    protected $table  = Table::SUB_BALANCE_MAP;

    protected $generateIdOnCreate = true;

    // Schema Constants
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const PARENT_BALANCE_ID = 'parent_balance_id';
    const CHILD_BALANCE_ID  = 'child_balance_id';

    // generators
    protected static $generators = [
        self::ID,
    ];

    // fillable attributes
    protected $fillable = [
        self::MERCHANT_ID,
        self::PARENT_BALANCE_ID,
        self::CHILD_BALANCE_ID,
    ];

    // visible attributes
    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::PARENT_BALANCE_ID,
        self::CHILD_BALANCE_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    // ============================= GETTERS ===============================

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getParentBalanceId()
    {
        return $this->getAttribute(self::PARENT_BALANCE_ID);
    }

    public function getChildBalanceId()
    {
        return $this->getAttribute(self::CHILD_BALANCE_ID);
    }

    // ============================= END GETTERS ===========================

    // ============================= SETTERS ===============================

    public function setMerchantId(string $merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setParentBalanceId(string $parentBalanceId)
    {
        $this->setAttribute(self::PARENT_BALANCE_ID, $parentBalanceId);
    }

    public function setChildBalanceId(string $childBalanceId)
    {
        $this->setAttribute(self::CHILD_BALANCE_ID, $childBalanceId);
    }

    // ============================= END SETTERS ===========================

}
