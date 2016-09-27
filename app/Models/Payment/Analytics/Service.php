<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Http\RequestHeader;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment\Analytics;
use RZP\Models\Payment\Analytics\Metadata;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function getAuditsForPayment($id)
    {
        $audits = $this->repo->payment_analytics->findForPayment($id);

        return $audits->toArrayPublic();
    }
}