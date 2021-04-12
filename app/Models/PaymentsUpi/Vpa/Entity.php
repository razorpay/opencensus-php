<?php

namespace RZP\Models\PaymentsUpi\Vpa;

use RZP\Models\PaymentsUpi\Base;

class Entity extends Base\Entity
{
    const USERNAME      = 'username';
    const HANDLE        = 'handle';
    const NAME          = 'name';
    const STATUS        = 'status';
    const RECEIVED_AT   = 'received_at';

    const VPA           = 'vpa';
    const AROBASE       = '@';

    // Increasing to 180 days
    const VPA_EXPIRY    = 15552000;

    protected $entity = 'payments_upi_vpa';

    protected $fillable = [
        self::USERNAME,
        self::HANDLE,
        self::NAME,
        self::STATUS,
        self::RECEIVED_AT,
    ];

    protected $public = [
        self::ID,
        self::USERNAME,
        self::HANDLE,
        self::NAME,
        self::STATUS,
        self::RECEIVED_AT,
    ];

    protected $generateIdOnCreate = true;

    public function setStatus($status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    public function setReceivedAt($receivedAt)
    {
        return $this->setAttribute(self::RECEIVED_AT, $receivedAt);
    }

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

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getReceivedAt()
    {
        return $this->getAttribute(self::RECEIVED_AT);
    }

    public function isExpired(): bool
    {
        $receivedAt = $this->getReceivedAt();

        $diff = ($this->freshTimestamp() - (int) $receivedAt);

        return ($diff > self::VPA_EXPIRY);
    }

    public function isValid(): bool
    {
        return ($this->getStatus() === Status::VALID);
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

        $vpaInput[self::USERNAME]   = $addressArray[0];
        $vpaInput[self::HANDLE]     = $addressArray[1] ?? null;

        return $vpaInput;
    }
}
