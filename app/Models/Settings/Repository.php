<?php

namespace RZP\Models\Settings;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'settings';

    public function getSettingsIfKeyPresent(string $module, string $key)
    {
        return $this->newQuery()
                    ->where(Entity::MODULE, $module)
                    ->where(Entity::KEY, $key)
                    ->get();
    }
}
