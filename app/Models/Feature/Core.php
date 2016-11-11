<?php

namespace RZP\Models\Feature;

use Illuminate\Database\QueryException;
use RZP\Models\Base;
use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

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
            $this->trace->info(TraceCode::MERCHANT_FEATURE_EDIT,
                array('merchant_id'  => $feature->getEntityId(),
                      'old_features' => $assignedFeatureNames,
                      'new_feature'  => $feature->getName()));

            $this->repo->saveOrFail($feature);

            return $feature;
        }

        return null;
    }
}
