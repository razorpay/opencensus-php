<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Base\BuilderEx;
use RZP\Models\P2p\Device;

trait HasDevice
{
    protected static function bootHasDevice()
    {
        self::$doesEntityHasDevice = true;
    }

    public function scopeDevice(BuilderEx $query, Device\Entity $device)
    {
        $query->where(self::DEVICE_ID, $device->getId());
    }

    public function setDeviceId(string $id)
    {
        $this->setAttribute(self::DEVICE_ID, $id);
    }

}
