<?php

namespace RZP\Models\Vpa;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Merchant;

/**
 * @property Customer\Entity     $customer
 */
class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const ENTITY_ID         = 'entity_id';
    const ENTITY_TYPE       = 'entity_type';
    const USERNAME          = 'username';
    const HANDLE            = 'handle';
    const MERCHANT_ID       = 'merchant_id';
    const CUSTOMER_ID       = 'customer_id';

    const ADDRESS               = 'address';

    const AROBASE               = '@';

    protected $generateIdOnCreate = true;

    protected $entity = 'vpa';

    protected static $sign = 'vpa';

    protected $fillable = [
        self::USERNAME,
        self::HANDLE,
    ];

    protected $public = [
        self::ID,
        self::ADDRESS,
        self::CUSTOMER_ID,
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

    public function customer()
    {
        return $this->belongsTo(Customer\Entity::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }
}
