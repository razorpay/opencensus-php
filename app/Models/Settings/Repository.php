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

    public function getEntityIdsAndValueByKeyAndModule(string $module, string $key)
    {
        $entityIdColumn = $this->dbColumn(Entity::ENTITY_ID);
        $valueColumn    = $this->dbColumn(Entity::VALUE);
        $moduleColumn   = $this->dbColumn(Entity::MODULE);
        $keyColumn      = $this->dbColumn(Entity::KEY);

        return $this->newQuery()
                    ->select($entityIdColumn, $valueColumn)
                    ->where($moduleColumn, '=', $module)
                    ->where($keyColumn, '=', $key)
                    ->get();
    }
}
