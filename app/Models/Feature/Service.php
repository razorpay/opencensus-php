<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
	public function addFeatures($input)
	{
		$featureParams = $this->buildFeatureParams($input);

		$features = $featureParams->map(function ($item) {
			return (new Core)->create($item);
		});

		return $features->toArray();
	}

	public function getFeatures(string $entityId, string $entityType)
	{
		$response = new Base\Collection;

		$response['assigned_features'] = $this->repo->feature->
				findByEntityIdAndType($entityId, $entityType);

		// all_features is a list of currently available features in the system
		$response['all_features'] = Constants::$allFeatures;

		return $response;
	}

	public function deleteFeature(int $id)
	{
		$feature = $this->repo->feature->findOrFailPublic($id);

        $this->trace->info(TraceCode::FEATURE_DELETE_REQUEST, $feature->toArrayPublic());

		$this->repo->deleteOrFail($feature);

		return $feature->toArrayPublic();
	}

	public function migrateMerchantFeatures()
	{
		$response = new Base\Collection;

		$this->repo->merchant->fetchMerchantFeatures(function ($merchantFeatures)
			use ($response)
		{
			foreach ($merchantFeatures as $merchantFeature)
			{
				$featureParam = [
					Entity::ENTITY_ID      => $merchantFeature->getId(),
					Constants::NAMES       => $merchantFeature->features,
					Entity::ENTITY_TYPE	   => \RZP\Constants\Entity::MERCHANT
				];
                try
                {
                    $response->push($this->addFeatures($featureParam));
                    $merchantFeature->features = null;
                    $this->repo->saveOrFail($merchantFeature);
                }
                catch (Exception $e)
                {
                    $this->trace->warn(TraceCode::FEATURE_MIGRATION_EXCEPTION, [
                        Entity::ENTITY_ID   => $merchantFeature->id,
                        'msg'               => $e->getMessage()
                    ]);
                }
            }
        });
        return $response->collapse();
	}

    public function multiAssignFeature($input)
    {
        $entityIds = $input[Constants::ENTITY_IDS];

        $response = new Base\Collection;

        foreach ($entityIds as $entityId) {
            $featureParam = [
                Entity::ENTITY_TYPE     => $input[Entity::ENTITY_TYPE],
                Entity::ENTITY_ID       => $entityId,
                Entity::NAME            => $input[Entity::NAME]
            ];
            try
            {
                $response->push((new Core)->create($featureParam));
            }
            catch (Exception $e)
            {
                $this->trace->warn(TraceCode::FEATURE_ASSIGNMENT_EXCEPTION, [
                    'msg' => $e->getMessage()
                ]);
            }
        }

        return $response->toArray();
    }

    public function multiRemoveFeature($input)
    {
        $entityIds = $input[Constants::ENTITY_IDS];

        $featureName = $input[ENTITY::NAME];

        $response = new Base\Collection;

        foreach ($entityIds as $entityId)
        {
            $feature = $this->repo->feature->findByEntityIdAndName(
                        $entityId,
                        $featureName);

            if ($feature !== null)
            {
                $response->push($feature);

                $this->repo->deleteOrFail($feature);
            }
        }

        return $response->toArray();
    }

	private function buildFeatureParams($input)
	{
		$featureParams = new Base\Collection;

		$entityType = $input[Entity::ENTITY_TYPE];

		$entityId = $input[Entity::ENTITY_ID];

		$featureNames = $input[Constants::NAMES];

		foreach ($featureNames as $featureName)
		{
			$featureParams->push([
				Entity::ENTITY_TYPE     => $entityType,
				Entity::ENTITY_ID       => $entityId,
				Entity::NAME            => $featureName
			]);
		}

		return $featureParams;
	}
}

