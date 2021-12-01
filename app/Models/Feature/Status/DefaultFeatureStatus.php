<?php

namespace RZP\Models\Feature\Status;

use RZP\Models\Feature\Entity as FeatureEntity;

/**
 * Default status implementation for a feature type, ideally class name
 * should be default but default is a keyword in php , so using DefaultFeatureStatus
 *
 * Class DefaultFeatureStatus
 *
 * @package RZP\Models\Feature\Status
 */
class DefaultFeatureStatus extends BaseFeatureStatus
{

    /**
     * DefaultFeatureStatus constructor.
     *
     * @param $entity
     */
    public function __construct($entity)
    {
        parent::__construct($entity);
    }

    public function getFeatureStatus(): bool
    {
        return $this->feature !== null;
    }
}
