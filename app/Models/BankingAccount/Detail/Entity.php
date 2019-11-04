<?php

namespace RZP\Models\BankingAccount\Detail;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Merchant;
use RZP\Models\BankingAccount;
use RZP\Models\Base\PublicEntity;

class Entity extends PublicEntity
{
    use SoftDeletes;

    const GATEWAY_KEY           = 'gateway_key';
    const GATEWAY_VALUE         = 'gateway_value';
    const BANKING_ACCOUNT_ID    = 'banking_account_id';

    protected $generateIdOnCreate = true;

    protected $entity = 'banking_account_detail';

    //
    // TODO: Need to add capability to hide some fields
    // https://razorpay.atlassian.net/browse/RX-610
    //
    protected $visible = [
        self::GATEWAY_KEY,
        self::GATEWAY_VALUE,
        self::MERCHANT_ID,
        self::BANKING_ACCOUNT_ID,
    ];

    protected $public = [
        self::GATEWAY_VALUE,
        self::GATEWAY_KEY,
        self::MERCHANT_ID,
        self::BANKING_ACCOUNT_ID,
    ];

    // --------------------------- Relations ---------------------------------- //

    public function bankingAccount()
    {
        return $this->belongsTo(BankingAccount\Entity::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    // ---------------------------- Setters ----------------------------------- //

    public function setGatewayKey(string $key)
    {
        $this->setAttribute(self::GATEWAY_KEY, $key);
    }

    public function setGatewayValue(string $value)
    {
        $this->setAttribute(self::GATEWAY_VALUE, $value);
    }
}
