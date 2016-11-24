<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment\Analytics;

class Service extends Base\Service
{
    public function getAuditsForPayment($id)
    {
        $audits = $this->repo->payment_analytics->findForPayment($id);

        return $audits->toArrayPublic();
    }

    // set the payment request as s2s for analytics
    public function setMetadataForS2SPayment($input)
    {
        $input['_'] = isset($input['_']) ? $input['_'] : [];

        if (isset($input['_'][Entity::LIBRARY]) === false)
        {
            $input['_'][Entity::LIBRARY] = Metadata::DIRECT;
        }

        return $input;
    }
}
