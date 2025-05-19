<?php

namespace RZP\Models\Customer\Account;

use RZP\Models\Customer\ImplicitJoinHelper;

trait CmsGetAttribute
{
    public function getCustomerAttribute()
    {
        if ((new SplitzExperimentEvaluator())->isLazyReadOverrideToCmsEnabled($this->getEntityName()))
        {
            return (new ImplicitJoinHelper())->getCustomerAttributeByCustomerId($this);
        }
        return parent::getRelationValue('customer');
    }
}
