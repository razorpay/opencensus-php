<?php

namespace RZP\Models\Feature;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;

class Service extends Base\Service
{
    public function addFeatures(array $input)
    {
        $featureParams = $this->buildFeatureParams($input);

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        $featureCore = new Core;

        $features = $featureParams->map(function ($item) use ($featureCore, $shouldSync)
        {
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

    /**
     * Returns all the questions required for onboarding features
     *
     * @param  array $input
     *
     * @return array
     */
    public function getOnboardingDetails(array $input): array
    {
        $response['questions'] = $this->getOnboardingQuestions($input);

        $response['submissions'] = $this->getOnboardingSubmissions();

        return $response;
    }

    /**
     * Returns all the questions required for onboarding features
     *
     * @param  array $input
     *
     * @return array
     */
    public function getOnboardingQuestions(array $input): array
    {
        $features = $input[Constants::FEATURES];

        $response = [];

        foreach ($features as $feature)
        {
            $questionMap = Constants::getFeatureQuestions($feature);

            if (count($questionMap) > 0)
            {
                $response[$feature] = $questionMap;
            }
        }

        return $response;
    }

    /**
     * Saves the merchant responses to the onboarding questions
     *
     * @param array  $input
     * @param string $feature
     *
     * @return bool
     * @throws Exception\BadRequestException
     */
    public function postOnboardingSubmissions(array $input, string $feature): bool
    {
        $status = (new Core)->postOnboardingSubmissions($this->merchant, $input, $feature);

        return $status;
    }

    /**
     * Updates the merchant responses to the onboarding questions
     *
     * @param array  $input
     * @param string $feature
     *
     * @return bool
     */
    public function updateOnboardingSubmissions(array $input, string $feature): bool
    {
        $merchantId = $input['merchant_id'];

        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        unset($input['merchant_id']);

        $data[$feature] = $input;

        $status = (new Core)->processOnboardingResponses(Constants::UPDATE, $data, $merchant);

        return $status;
    }

    /**
     * @param string|null $feature
     *
     * @return array
     */
    public function getOnboardingSubmissions(string $feature = null)
    {
        $settings = (new Core)->getOnboardingSubmissions($this->merchant, $feature);

        return $settings;
    }

    protected function buildFeatureParams($input)
    {
        $featureParams = new Base\Collection;

        $entityType = $input[Entity::ENTITY_TYPE];

        $entityId = $input[Entity::ENTITY_ID];

        $featureNames = $input[Constants::NAMES];

        foreach ($featureNames as $featureName)
        {
            $featureParams->push([
                Entity::ENTITY_TYPE => $entityType,
                Entity::ENTITY_ID   => $entityId,
                Entity::NAME        => $featureName
            ]);
        }

        return $featureParams;
    }

    /**
     * Returns the feature activation requests based on the status
     *
     * @param array $input
     *
     * @return mixed
     */
    public function getFeatureOnboardingRequests(array $input)
    {
        $status = $input['status'];

        $merchantDetails = $this->repo->merchant_detail->getFeatureOnboardingRequestsByStatus($status);

        return $merchantDetails;
    }

    /**
     * @param string $featureName
     * @param array  $input
     *
     * @return array
     */
    public function updateFeatureActivationStatus(string $featureName, array $input): array
    {
        $status = $input['status'];

        $merchantId = $input['merchant_id'];

        $response = (new Core)->updateFeatureActivationStatus($merchantId, $featureName, $status);

        return $response;
    }

    /**
     * @param string $featureName
     * @param array  $input
     *
     * @return array
     */
    public function getFeatureActivationStatus(string $featureName, array $input)
    {
        $merchantId = $input['merchant_id'];

        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        $status =  $this->repo->merchant_detail->getFeatureActivationStatus(
            $merchant,
            $featureName
        );

        $response['status'] = $status;

        return $response;
    }
}

