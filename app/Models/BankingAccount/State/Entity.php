<?php

namespace RZP\Models\BankingAccount\State;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Admin;
use RZP\Models\BankingAccount;

class Entity extends Base\PublicEntity
{
    const BANKING_ACCOUNT = 'banking_account';

    const STATUS = 'status';

    const BANK_STATUS = 'bank_status';

    const ADMIN_ID = 'admin_id';

    const BANKING_ACCOUNT_ID = 'banking_account_id';

    protected $entity = 'banking_account_state';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::STATUS,
        self::BANK_STATUS,
        self::MERCHANT_ID,
        self::ADMIN_ID,
        self::BANKING_ACCOUNT_ID,
    ];

    protected $visible = [
        self::ID,
        self::STATUS,
        self::BANK_STATUS,
        self::MERCHANT_ID,
        self::ADMIN_ID,
        self::BANKING_ACCOUNT_ID,
        self::CREATED_AT,
    ];

    public $public = [
        self::ID,
        self::STATUS,
        self::BANK_STATUS,
        self::MERCHANT_ID,
        self::ADMIN_ID,
        self::BANKING_ACCOUNT_ID,
        self::CREATED_AT,
    ];

    public function bankingAccount()
    {
        return $this->belongsTo(BankingAccount\Entity::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin\Entity::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }
}
