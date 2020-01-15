<?php

namespace RZP\Models\Offline\Device;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'offline_device';

    public function countBySerialNumberManufacturerAndType(string $sn, string $manufacturer, string $type)
    {
        return $this->newQuery()
                    ->where(Entity::TYPE, '=', $type)
                    ->where(Entity::MANUFACTURER, '=', $manufacturer)
                    ->where(Entity::SERIAL_NUMBER, '=', $sn)
                    ->count();
    }

    public function findByActivationToken($activationToken)
    {
        return $this->newQuery()
                    ->whereNotNull(Entity::ACTIVATION_TOKEN)
                    ->where(Entity::ACTIVATION_TOKEN, '=', $activationToken)
                    ->first();
    }

    public function findBySerialNumber($serialNumber)
    {
        return $this->newQuery()
                    ->where(Entity::SERIAL_NUMBER, '=', $serialNumber)
                    ->first();
    }
}
