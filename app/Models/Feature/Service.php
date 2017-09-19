<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function addFeatures($input)
    {
        $featureParams = $this->buildFeatureParams($input);

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        $featureCore = new Core;

        $featureValidator = new Validator;

        $features = $featureParams->map(function ($item) use ($featureCore, $featureValidator, $shouldSync)
        {
            $featureName = $item[Entity::NAME];

            $featureValidator->validateEditingFeature($featureName, $shouldSync);

            return $featureCore->create($item, $shouldSync);
        });

        return $features->toArray();
    }

    public function getFeatures(string $entityId)
    {
        $response = new Base\Collection;

        $response['assigned_features'] = $this->repo->feature->findByEntityId($entityId);

        // all_features is a list of currently available features in the system
        $response['all_features'] = array_keys(Constants::$featureValueMap);

        return $response;
    }

    public function deleteFeature(string $entityId, string $featureName, array $input)
    {
        $feature = $this->repo->feature->findByEntityIdAndNameOrFail($entityId, $featureName);

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        (new Core)->delete($feature, $shouldSync);

        // We delete the tag also along with feature.
        (new Merchant\Service)->deleteTag($entityId, $feature->getName());

        return $feature->toArrayDeleted();
    }

    public function multiAssignFeature($input)
    {
        $this->trace->info(TraceCode::FEATURE_MULTI_ASSIGN_REQUEST, $input);

        $entityIds = $input[Constants::ENTITY_IDS];

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        $response = new Base\Collection;

        foreach ($entityIds as $entityId)
        {
            $featureParam = [
                Entity::ENTITY_TYPE     => $input[Entity::ENTITY_TYPE],
                Entity::ENTITY_ID       => $entityId,
                Entity::NAME            => $input[Entity::NAME]
            ];

            try
            {
                $feature = (new Core)->create($featureParam, $shouldSync);

                $response->push($feature);
            }
            catch (\Exception $e)
            {
                $this->trace->warn(
                    TraceCode::FEATURE_ASSIGNMENT_EXCEPTION,
                    [
                        'msg' => $e->getMessage()
                    ]);
            }
        }

        return $response->toArray();
    }

    public function multiRemoveFeature($input)
    {
        $this->trace->info(TraceCode::FEATURE_MULTI_REMOVE_REQUEST, $input);

        $entityIds = $input[Constants::ENTITY_IDS];

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        $featureName = $input[Entity::NAME];

        $response = new Base\Collection;

        foreach ($entityIds as $entityId)
        {
            $feature = $this->repo->feature->findByEntityIdAndNameOrFail(
                        $entityId,
                        $featureName);

            if ($feature !== null)
            {
                $response->push($feature);

                (new Core)->delete($feature, $shouldSync);
            }
        }

        return $response->toArray();
    }

    public function getFeaturesForEntity($entity)
    {
        $entityId = $entity->getId();

        $data['features'] = [];

        $enabledFeatures = $entity->features
                                  ->pluck(\RZP\Models\Feature\Entity::NAME)
                                  ->toArray();

        foreach (Constants::$visibleFeaturesMap as $visibleFeature => $featureDetails)
        {
            $feature = $featureDetails['feature'];

            $isEnabled = in_array($feature, $enabledFeatures, true);

            $data['features'][] = [
                'feature'      => $visibleFeature,
                'value'        => $isEnabled,
                'display_name' => $featureDetails['display_name']
            ];
        }

        return $data;
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

