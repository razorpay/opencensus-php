<?php

namespace RZP\Models\Dispute\Reason;

use RZP\Models\Base;
use RZP\Models\Card\NetworkName;
use RZP\Models\Dispute\Reason\Constants as ReasonConstants;

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

    public function getReasonAndNetworkCode(string $reasonCode, string $network)
    {
        $code = $reasonCode;

        $newCode = '';

        $reason = [];

        if ($network == NetworkName::VISA)
        {
            if (array_key_exists($reasonCode, ReasonConstants::VISA_REASON_CODE_VS_CURRENT_REASON_CODE))
            {
                $newCode = ReasonConstants::VISA_REASON_CODE_VS_CURRENT_REASON_CODE[$reasonCode];

                $code = $newCode;
            }
            else
            {
                $network = 'RZP';

                $code = 'RZP00';
            }
        }

        try
        {
            $disputeReason = $this->getReasonByNetworkAndGatewayCode($network, $code);

            $reason['network_code'] = $network . '-' . $code;

            $reason['reason_code'] = $disputeReason->getCode();

            return $reason;
        }
        catch (\Exception $e)
        {
            $disputeReason = $this->getReasonByNetworkAndGatewayCode('RZP', 'RZP00');

            $reason['network_code'] = 'RZP-RZP00';

            $reason['reason_code'] = $disputeReason->getCode();

            return $reason;
        }
    }
}
