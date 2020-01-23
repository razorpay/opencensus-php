<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\kycDetails;

use RZP\Http\RequestHeader;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\KycService\BaseProcessor;

class KycDetailsProcessor extends BaseProcessor
{
    /**
     * @return Response
     * @throws \RZP\Exception\IntegrationException
     */
    public function process(): Response
    {
        $kyc_id = $this->input[Constants::KYC_ID];

        $request = [
            'url'     => $this->config['url'] . $kyc_id,
            'method'  => 'GET',
            'headers' => [
                RequestHeader::CONTENT_TYPE => 'application/json',
            ]
        ];

        [$response, $responseMetaData] = $this->createAndSendRequest($request);

        return new KycDetailProcessorResponse($response, $responseMetaData);
    }
}
