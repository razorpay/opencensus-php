<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\companyPan;

use RZP\Http\RequestHeader;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\KycService\BaseProcessor;

class CompanyPanProcessor extends BaseProcessor
{
    /**
     * Company pan verification api timeout in seconds
     *
     * @var int
     */
    protected $timeout = 12;

    public function process(): Response
    {
        $requestDetails = $this->getApplicableRequestDetails(Constants::DOCUMENT_TYPES[Constants::BUSINESS_PAN]);

        $kyc_id = $this->input[Constants::KYC_ID];

        $content = [
            'pan_number'  => $this->input[Constants::PAN_NUMBER],
            'customer_id' => $this->input[Constants::ENTITY_ID],
        ];

        $request = [
            'url'     => $this->config['url'] . $kyc_id . '/auth/' . Constants::DOCUMENT_TYPES[Constants::BUSINESS_PAN] . '?sync=true',
            'method'  => $requestDetails[Constants::METHOD],
            'content' => $content,
            'headers' => [
                RequestHeader::CONTENT_TYPE => 'application/json',
            ]
        ];

        [$response, $responseMetaData] = $this->createAndSendRequest($request);

        return new CompanyPanProcessorResponse($response, $responseMetaData);
    }
}
