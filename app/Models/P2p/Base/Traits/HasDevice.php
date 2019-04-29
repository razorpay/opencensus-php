<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Base\BuilderEx;
use RZP\Models\P2p\Device;

/**
 * @property Device\Entity $device
 *
 * Trait HasDevice
 * @package RZP\Models\P2p\Base\Traits
 */
trait HasDevice
{
    public function hasDevice(): bool
    {
        return true;
    }

    public function associateDevice(Device\Entity $device)
    {
        return $this->device()->associate($device);
    }

    public function scopeDevice(BuilderEx $query, Device\Entity $device)
    {
        return $query->where(self::DEVICE_ID, $device->getId());
    }

    public function device()
    {
        return $this->belongsTo(Device\Entity::class);
    }

    public function withoutDevice()
    {
        $this->setAttribute(self::DEVICE_ID, null);
    }
}
