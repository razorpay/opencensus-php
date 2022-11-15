<?php

namespace RZP\Services\Mock;

use RZP\Constants\Mode;
use RZP\Models\Feature\Entity;

class DcsServiceClient
{

    /**
     *
     * @param Entity $entity
     * @param string $mode
     * @return void
     */
    public function assignFeature(Entity $entity, $mode = Mode::TEST)
    {
    }

    /**
     *
     * @param Entity $entity
     * @param string $mode
     * @return void
     */
    public function removeFeature(Entity $entity, $mode = Mode::TEST)
    {
    }

    /**
     *
     * @param string $entityId
     * @param string $featureName
     * @param string $mode
     * @return array
     */
    public function fetchByEntityIdAndName(string $entityId, string $featureName, $mode = Mode::TEST)
    {
        return [];
    }

    /**
     *
     * @param array $entityIds
     * @param string $featureName
     * @param string $mode
     * @return array
     */
    public function fetchByEntityIdsAndName(array $entityIds, string $featureName, $mode = Mode::TEST)
    {
        return [];
    }

    /**
     *
     * @param string $entityType
     * @param string $entityId
     * @param string $mode
     * @return array
     */
    public function fetchByEntityIdAndEntityType(string $entityType, string $entityId, $mode = Mode::TEST)
    {
        return [];
    }

    /**
     *
     * @param string $featureName
     * @param string $mode
     * @return array
     */
    public function fetchByFeatureName(string $featureName,  $mode = Mode::TEST)
    {
        return [];
    }

    public function isDcsEnabled($functionName, $mode = Mode::TEST){
        return false;
    }

    public function isDCSNewUseCaseEnabled($serviceName, $mode = Mode::TEST){
        return false;
    }

    public static function isDCSNewFeature($featureName){
        return false;
    }

    public static function isDcsFeature($featureName){
        return false;
    }
}
