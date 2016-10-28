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

	public function getFeatures($toggleableType, $toggleableId)
	{
		$toggleableType = EntityMap::TOGGLEABLE_ENTITIES[$toggleableType];

		$response = collect();

		$response['assigned_features'] = $this->repo->feature->getFeatures($toggleableType,
			   $toggleableId);

		//all_features is a list of currently available features in the system
		$response['all_features'] = Core::$allFeatures;

		return $response;
	}

	public function deleteFeature($id)
	{
		$feature = $this->repo->feature->findOrFailPublic($id);

		$this->repo->deleteOrFail($feature);

		return $feature->toArrayPublic();
	}

	private function buildFeatureParams($input)
	{
		$featureParams = collect();

		$toggleableType = EntityMap::TOGGLEABLE_ENTITIES[$input['toggleable_type']];

		$toggleableId = $input['toggleable_id'];

		$featureNames = $input['names'];

		foreach ($featureNames as $featureName) {
			$featureParams->push([
				"toggleable_type" 	=> $toggleableType,
				"toggleable_id" 	=> $toggleableId,
				"name"				=> $featureName
			]);
		}

		return $featureParams;
	}
}

