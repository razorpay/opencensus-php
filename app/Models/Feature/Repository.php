<?php

namespace RZP\Models\Feature;

use DB;

use RZP\Constants\Table;
use RZP\Models\Base;

class Repository extends Base\Repository
{
	use Base\RepositoryFetch;

	protected $entity = 'feature';

	public function getFeaturesByToggleableTypeAndId(string $toggleable_type, string $toggleable_id)
	{
		return $this->newQuery()
				->where(Entity::TOGGLEABLE_TYPE, '=', $toggleable_type)
				->where(Entity::TOGGLEABLE_ID, '=', $toggleable_id)
				->get();
	}
}