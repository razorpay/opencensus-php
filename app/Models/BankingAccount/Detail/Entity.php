<?php

namespace RZP\Models\BankingAccount\Detail;

use RZP\Models\BankingAccount;
use RZP\Models\Base\PublicEntity;

class Entity extends PublicEntity
{
    const GATEWAY_KEY           = 'gateway_key';
    const GATEWAY_VALUE         = 'gateway_value';
    const BANKING_ACCOUNT_ID    = 'banking_account_id';
    const BANKING_ACCOUNT       = 'banking_account';

    const DETAILS               = 'details';

    protected $generateIdOnCreate = true;

    protected $entity = 'banking_account_detail';

    protected $visible = [
        self::GATEWAY_KEY,
        self::GATEWAY_VALUE,
    ];

    protected $public = [
        self::GATEWAY_VALUE,
        self::GATEWAY_KEY,
    ];

    public function setGatewayKey(string $key)
    {
        $this->setAttribute(self::GATEWAY_KEY, $key);
    }

    public function setGatewayValue(string $value)
    {
        $this->setAttribute(self::GATEWAY_VALUE, $value);
    }

    public function bankingAccount()
    {
        return $this->belongsTo(BankingAccount\Entity::class);
    }
}
