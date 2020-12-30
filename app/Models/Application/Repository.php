<?php

namespace RZP\Models\Application;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'application';

    public function getAppList(array $appIdList)
    {
        $appIdColumn      = $this->dbColumn(Entity::ID);

        return $this->newQuery()
                    ->whereIn($appIdColumn, $appIdList)
                    ->get();
    }

    public function getAppByName(string $appName)
    {
        $appNameColumn      = $this->dbColumn(Entity::NAME);

        return $this->newQuery()
                    ->where($appNameColumn, $appName)
                    ->first();
    }

    public function getAppByTitle(string $appTitle)
    {
        $appTitleColumn      = $this->dbColumn(Entity::TITLE);

        return $this->newQuery()
            ->where($appTitleColumn, $appTitle)
            ->first();
    }

    public function getAllApps()
    {
        return $this->newQuery()
                    ->get()
                    ->toArray();
    }
}
