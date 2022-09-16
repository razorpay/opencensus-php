<?php

namespace RZP\Models\Settings;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'settings';

    public function getSettingsIfKeyPresent(string $module, string $key, $value, $skip, $limit)
    {
        return $this->newQuery()
                    ->where(Entity::MODULE, $module)
                    ->where(Entity::KEY, $key)
                    ->where(Entity::VALUE, $value)
                    ->skip($skip)
                    ->take($limit)
                    ->get();
    }

    public function getSettings(string $entityId, $module, $key)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::MODULE, $module)
                    ->where(Entity::KEY, $key)
                    ->first();
    }

}
