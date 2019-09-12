<?php

namespace RZP\Models\Gateway\File;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_file';

    protected $entityFetchParamRules = [
        Entity::TARGET => 'filled|string|max:50',
        Entity::TYPE   => 'filled|string|max:20',
        Entity::STATUS => 'filled|string|max:20',
    ];

    public function fetchFileSentCountFromStart($type, $target, $start)
    {
        return $this->newQuery()
                    ->where(Entity::TYPE, '=', $type)
                    ->where(Entity::TARGET, '=', $target)
                    ->where(Entity::STATUS, Status::FILE_SENT)
                    ->where(Entity::SENT_AT, '>', $start)
                    ->count();
    }
}
