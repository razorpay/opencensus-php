<?php

namespace RZP\Models\Upi\Vpa;

use RZP\Models\Base;
use RZP\Models\BankAccount;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                    = 'id';
    const USERNAME              = 'username';
    const HANDLE                = 'handle';
    const FREQUENCY             = 'frequency';
    const BANK_ACCOUNT_ID       = 'bank_account_id';
    const CUSTOMER_ID           = 'customer_id';

    const DELETED_AT            = 'deleted_at';

    protected $generateIdOnCreate = true;

    protected $entity = 'vpa';

    protected $fillable = [
        self::USERNAME,
        self::HANDLE,
        self::FREQUENCY,
        self::BANK_ACCOUNT_ID,
        self::CUSTOMER_ID,
    ];

    // ----------------------- Getters -----------------------

    public function getUsername()
    {
        return $this->getAttribute(self::USERNAME);
    }

    // ----------------------- Relations -----------------------

    public function bankAccount()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity', self::BANK_ACCOUNT_ID, BankAccount\Entity::ID);
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }
}
