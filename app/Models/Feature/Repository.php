<?php

namespace RZP\Models\Feature;

use DB;

use RZP\Constants\Table;
use RZP\Models\Base;

class Repository extends Base\Repository
{
	use Base\RepositoryFetch;

	protected $entity = 'feature';

	public function getFeaturesByEntityTypeAndId(string $entityType, string $entityId)
	{
		return $this->newQuery()
				->where(Entity::ENTITY_TYPE, '=', $entityType)
				->where(Entity::ENTITY_ID, '=', $entityId)
				->get();
	}
}