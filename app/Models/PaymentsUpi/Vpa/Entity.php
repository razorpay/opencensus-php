<?php

namespace RZP\Models\PaymentsUpi\Vpa;

use RZP\Models\PaymentsUpi\Base;

class Entity extends Base\Entity
{
    const USERNAME  = 'username';
    const HANDLE    = 'handle';
    const NAME      = 'name';

    const AROBASE = '@';

    protected $entity = 'payments_upi_vpa';

    protected $fillable = [
        self::USERNAME,
        self::HANDLE
    ];

    protected $public = [
        self::ID,
        self::USERNAME,
        self::HANDLE,
        self::NAME,
    ];

    protected $generateIdOnCreate = true;

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
        return $this->getAttribute(self::USERNAME) . self::AROBASE . $this->getAttribute(self::HANDLE);
    }

    public function toArrayToken()
    {
        $attributes = $this->toArrayPublic();

        unset($attributes[self::ID]);

        return $attributes;
    }

    public static function getUsernameAndHandle(string $vpa)
    {
        $addressArray = explode('@', $vpa);

        $vpaInput['username'] = $addressArray[0];

        $vpaInput['handle'] = $addressArray[1];

        return $vpaInput;
    }
}
