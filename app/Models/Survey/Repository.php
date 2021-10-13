<?php

namespace RZP\Models\Survey;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'survey';

    public function get(string $type)
    {
        $surveyTypeColumn = $this->dbColumn(Entity::TYPE);

        return $this->newQuery()
                    ->where($surveyTypeColumn, $type)
                    ->first();
    }
}
