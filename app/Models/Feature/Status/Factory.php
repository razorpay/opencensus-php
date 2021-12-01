<?php

namespace RZP\Models\Feature\Status;


use RZP\Models\Feature\Constants;
use RZP\Models\Feature\Entity as FeatureEntity;

class Factory
{
    /**
     * Returns status instance for featureName
     *
     * @param $entity
     *
     * @return BaseFeatureStatus
     */

    public function getStatusInstance($entity): BaseFeatureStatus
    {
        if ($entity === null)
        {
            return new DefaultFeatureStatus($entity);
        }

        switch ($entity->getName())
        {
            case Constants::M2M_REFERRAL:
                return new M2MReferralFeatureStatus($entity);
            default :
                return new DefaultFeatureStatus($entity);

        }
    }
}
