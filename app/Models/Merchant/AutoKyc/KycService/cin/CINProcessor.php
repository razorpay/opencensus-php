<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\cin;

use RZP\Http\RequestHeader;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\KycService\BaseProcessor;

class CINProcessor extends BaseProcessor
{

    protected $timeout = 15;

    public function process(): Response
    {

        $requestDetails = $this->getApplicableRequestDetails(Constants::DOCUMENT_TYPES[Constants::CIN]);

        $kyc_id = $this->input[Constants::KYC_ID];

        $content = [
            'cin'         => $this->input[Constants::CIN],
            'customer_id' => $this->input[Constants::ENTITY_ID],
        ];

        $request = [
            'url'     => $this->config['url'] . $kyc_id . '/auth/' . Constants::DOCUMENT_TYPES[Constants::CIN] . '?sync=true',
            'method'  => $requestDetails[Constants::METHOD],
            'content' => $content,
            'headers' => [
                RequestHeader::CONTENT_TYPE => 'application/json',
            ]
        ];

        [$response, $responseMetaData] = $this->createAndSendRequest($request);

        return new CINProcessorResponse($response, $responseMetaData);
    }
}
