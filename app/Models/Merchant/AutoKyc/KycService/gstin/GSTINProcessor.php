<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\gstin;

use RZP\Http\RequestHeader;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\KycService\BaseProcessor;

class GSTINProcessor extends BaseProcessor
{
    public function process(): Response
    {
        $requestDetails = $this->getApplicableRequestDetails(Constants::DOCUMENT_TYPES[Constants::GSTIN]);

        $kyc_id = $this->input[Constants::KYC_ID];

        $content = [
            'gstin'       => $this->input[Constants::GSTIN],
            'customer_id' => $this->input[Constants::ENTITY_ID],
        ];

        $request = [
            'url'     => $this->config['url'] . $kyc_id . '/auth/' . Constants::DOCUMENT_TYPES[Constants::GSTIN] . '?sync=true',
            'method'  => $requestDetails[Constants::METHOD],
            'content' => $content,
            'header'  => [
                RequestHeader::CONTENT_TYPE => 'application/json',
            ],
        ];

        [$response, $responseMetaData] = $this->createAndSendRequest($request);

        return new GSTINProcessorResponse($response, $responseMetaData);
    }
}
