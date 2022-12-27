<?php

namespace RZP\Services\Mock;
use Razorpay\Dcs\Kv\V1\ApiException;
use RZP\Constants\Mode;
use RZP\Models\Feature\Entity;

class DcsServiceClient
{
    /**
     * throws Server exception in case of request failures
     *
     * @param Entity $entity
     * @param string $variant
     * @param bool $isAssignment
     * @param string $mode
     * @return void
     * @throws \Exception
     */
    public function editFeature(Entity $entity, string $variant, bool $isAssignment, $mode = Mode::TEST)
    {

    }

    /**
     * throws Server exception in case of request failures
     *
     * @param string $entityId
     * @param string $featureName
     * @param string $mode
     * @return array
     * @throws ApiException
     */
    public function fetchByEntityIdAndName(string $entityId, string $featureName, $mode = Mode::TEST)
    {
        return [];
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param string $entityId
     * @param array $featureNames
     * @param string $mode
     * @return array
     * @throws ApiException
     */
    public function fetchByEntityIdAndFeatureNames(string $entityId, array $featureNames, $mode = Mode::TEST)
    {
        return [];
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param array $entityIds
     * @param string $featureName
     * @param string $mode
     * @return array
     * @throws ApiException
     */
    public function fetchByEntityIdsAndName(array $entityIds, string $featureName, $mode = Mode::TEST)
    {
      return [];
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param string $entityType
     * @param string $entityId
     * @param string $mode
     * @return array
     * @throws \Exception
     */
    public function fetchByEntityIdAndEntityType(string $entityType, string $entityId, $mode = Mode::TEST)
    {
        return [];
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param string $featureName
     * @param string $mode
     * @return array
     */
    public function fetchByFeatureName(string $featureName, $mode = Mode::TEST)
    {
        return [];
    }

    public static function isDcsFeature($featureName)
    {
        return false;
    }

    public static function isDcsNewFeature($featureName)
    {
        return false;
    }

    public function handleDcsFeatures(Entity $entity, $value , $mode = Mode::TEST)
    {
       return [];
    }
}
