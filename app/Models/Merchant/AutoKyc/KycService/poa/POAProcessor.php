<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\poa;

use RZP\Http\RequestHeader;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\AutoKyc\KycService\BaseProcessor;


class POAProcessor extends BaseProcessor
{
    /**
     * @return Response
     * @throws \RZP\Exception\IntegrationException
     */
    public function process(): Response
    {
        $content = [
            'customer_id'      => $this->input[Constants::ENTITY_ID],
            'document_type'    => $this->input[Constants::DOCUMENT_TYPE],
            'document_file_id' => $this->input[Constants::DOCUMENT_FILE_ID],
        ];

        $requestDetails = $this->getApplicableRequestDetails($this->input[Constants::DOCUMENT_TYPE]);

        $requestDetails = [
            'url'     => $this->config['url'] . $this->input[Constants::KYC_ID] . '/' . Constants::KYC_API_TYPES["OCR"] . '?sync=true',
            'method'  => $requestDetails[Constants::METHOD],
            'content' => $content,
            'headers' => [
                RequestHeader::CONTENT_TYPE => 'application/json',
            ]
        ];

        [$response, $responseMetaData] = $this->createAndSendRequest($requestDetails);

        return new POAProcessorResponse($response, $responseMetaData, $this->input);
    }
}
