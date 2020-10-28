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
    public function getReasonFromAttributes(string $network, string $gatewayCode, string $code) : array
    {
        $reasons = $this->repo
                        ->dispute_reason
                        ->getReasonFromAttributes($network, $gatewayCode, $code)
                        ->toArray();

        return $reasons;
    }

    public function getReasonByNetworkAndGatewayCode(string $network, string $gatewayCode)
    {
        return $this->repo
            ->dispute_reason
            ->getReasonByNetworkAndGatewayCode($network, $gatewayCode);
    }
}
