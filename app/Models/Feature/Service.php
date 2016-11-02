<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;
use RZP\Exception;

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

	public function getFeatures(string $entityType, string $entityId)
	{
		$entityType = EntityMap::SUPPORTED_ENTITIES[$entityType];

		$response = new Base\Collection;

		$response['assigned_features'] = $this->repo->feature->
				getFeaturesByEntityTypeAndId($entityType,
				$entityId);

		// all_features is a list of currently available features in the system
		$response['all_features'] = FeatureName::$allFeatures;

		return $response;
	}

	public function deleteFeature(int $id)
	{
		$feature = $this->repo->feature->findOrFailPublic($id);

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
					'entity_id'			=> $merchantFeature->id,
					'names'				=> $merchantFeature->features,
					'entity_type'		=> 'merchant'
				];

				$response->push($this->addFeatures($featureParam));
			}
		});

		return $response;
	}

	private function buildFeatureParams($input)
	{
		$featureParams = new Base\Collection;

		$entityType = EntityMap::SUPPORTED_ENTITIES[$input['entity_type']];

		$entityId = $input['entity_id'];

		$featureNames = $input['names'];

		foreach ($featureNames as $featureName)
		{
			$featureParams->push([
				"entity_type" 	=> $entityType,
				"entity_id" 	=> $entityId,
				"name"			=> $featureName
			]);
		}

		return $featureParams;
	}
}

