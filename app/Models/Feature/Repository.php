<?php

namespace RZP\Models\Feature;

use DB;

use RZP\Constants\Table;
use RZP\Models\Base;

class Repository extends Base\Repository
{
	use Base\RepositoryUpdateTestAndLive;
    use Base\RepositoryFetch;

	protected $entity = 'feature';

	public function findByEntityId(string $entityId)
	{
		return $this->newQuery()
				->where(Entity::ENTITY_ID, '=', $entityId)
				->get();
	}

    public function findByEntityIdAndName(string $entityId, string $featureName)
    {
        return $this->newQuery()
                ->where(Entity::ENTITY_ID, '=', $entityId)
                ->where(Entity::NAME, '=', $featureName)
                ->first();
    }
}
