<?php

namespace RZP\Models\Dispute\Reason;

use RZP\Models\Base;

class Service extends Base\Service
{
    /**
     * Fetch all reasonIds for the given attributes
     *
     * @param string $network
     * @param string $gatewayCode
     * @param string $code
     *
     * @return array
     */
    public function getReasonIdFromAttributes(string $network, string $gatewayCode, string $code) : array
    {
        $reasonIds = $this->repo
                          ->dispute_reason
                          ->getReasonIdFromAttributes($network, $gatewayCode, $code)
                          ->toArray();

        return $reasonIds;
    }
}
