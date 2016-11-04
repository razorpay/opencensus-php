<?php

namespace RZP\Models\Feature;

use Illuminate\Database\QueryException;
use RZP\Models\Base;
use RZP\Exception;

class Core extends Base\Core
{
	public function create($input)
	{
		$feature = (new Entity)->build($input);

        $assignedFeatureNames = $this->repo
            ->feature
            ->findByEntityId($feature->getEntityId())
            ->pluck(Entity::NAME)
            ->toArray();

        if (in_array($feature->getName(), $assignedFeatureNames) === false)
        {
            $this->repo->saveOrFail($feature);

            return $feature;
        }

        return null;
	}
}
