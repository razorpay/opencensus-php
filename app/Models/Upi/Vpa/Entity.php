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

    const ADDRESS               = 'address';

    const AROBASE               = '@';

    protected $generateIdOnCreate = true;

    protected $entity = 'vpa';

    protected static $sign = 'vpa';

    protected $fillable = [
        self::USERNAME,
        self::HANDLE,
        self::FREQUENCY,
        // self::BANK_ACCOUNT_ID,
        // self::CUSTOMER_ID,
    ];

    protected $public = [
        self::ID,
        self::ADDRESS,
        self::USERNAME,
        self::HANDLE,
        self::CUSTOMER_ID,
        self::BANK_ACCOUNT_ID,
    ];

    protected $appends = [
        self::ADDRESS,
    ];

    protected static $generators = [
        'user_name_and_handle',
    ];

    protected static $unsetCreateInput = [
        self::ADDRESS,
    ];

    protected $defaults = array(
        self::FREQUENCY => Frequency::MULTIPLE,
    );

    // ----------------------- Generators ------------------

    protected function generateUserNameAndHandle($input)
    {
        $addressArray = explode(self::AROBASE, $input[self::ADDRESS]);

        $this->setAttribute(self::USERNAME, strtolower($addressArray[0]));
        $this->setAttribute(self::HANDLE, $addressArray[1]);
    }

    // ----------------------- Getters -----------------------

    public function getUsername()
    {
        return $this->getAttribute(self::USERNAME);
    }

    public function getHandle()
    {
        return $this->getAttribute(self::HANDLE);
    }

    public function getAddress()
    {
        return $this->getAttribute(self::ADDRESS);
    }

    // ----------------------- Setters -----------------------

    public function setHandle($handle)
    {
        return $this->setAttribute(self::HANDLE, $handle);
    }

    // ----------------------- Accessor ----------------------

    protected function getAddressAttribute()
    {
        return $this->getUsername() . self::AROBASE . $this->getHandle();
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

    public function merchant()
    {
        return $this->customer->merchant();
    }
}
