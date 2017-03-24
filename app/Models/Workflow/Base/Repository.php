<?php

namespace RZP\Models\Workflow\Base;

use RZP\Base\Repository as BaseRepository;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends BaseRepository
{
    use RepositoryUpdateTestAndLive;

    public function findOrFailPublicWithRelations(
        string $id,
        array $relations = [])
    {
        return $this->newQuery()
                    ->with($relations)
                    ->findOrFailPublic($id);
    }
}
