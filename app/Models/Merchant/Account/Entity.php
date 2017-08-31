<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;

class Entity extends Merchant\Entity
{
    protected static $sign = 'acc';

    protected static $delimiter = '_';

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::EMAIL,
        self::ACTIVATED,
        self::ACTIVATED_AT,
        self::LIVE,
        self::HOLD_FUNDS,
        self::CREATED_AT,
     ];

    public function scopeMerchantId($query, $merchantId)
    {
        $merchantIdColumn = $this->dbColumn(Entity::PARENT_ID);

        $query->where($merchantIdColumn, '=', $merchantId);
    }
}
