<?php

namespace RZP\Models\Application\ApplicationTags;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'application_mapping';

    public function getAppMapping(string $tag, string $appId)
    {
        $tagColumn             = $this->dbColumn(Entity::TAG);
        $appIdColumn           = $this->dbColumn(Entity::APP_ID);

        return $this->newQuery()
            ->where($tagColumn, '=', $tag)
            ->where($appIdColumn, '=', $appId)
            ->first();
    }

    public function getAppMappingByTag(string $tag, bool $toArray = false, $columns = ['*'])
    {
        $tagColumn             = $this->dbColumn(Entity::TAG);

        if ($toArray === true)
        {
            return $this->newQuery()
                ->select($columns)
                ->where($tagColumn, '=', $tag)
                ->get()
                ->toArray();
        }
        else
        {
            return $this->newQuery()
                ->select($columns)
                ->where($tagColumn, '=', $tag)
                ->get();
        }
    }
}
