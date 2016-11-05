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

        $feature = $feature->generateId();

        $existingFeatures = $this->repo->feature->findByEntityId($feature->getEntityId());

        $assignedFeatureNames = $existingFeatures->pluck(Entity::NAME)->toArray();

        if (in_array($feature->getName(), $assignedFeatureNames, true) === false)
        {
            $this->repo->saveOrFail($feature);

            return $feature;
        }

        return null;
    }
}
