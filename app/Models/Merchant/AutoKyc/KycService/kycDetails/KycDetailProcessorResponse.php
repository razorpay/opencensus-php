<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\kycDetails;

use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\KycService\BaseResponse;

class KycDetailProcessorResponse extends BaseResponse
{
    protected $allowedStatusCode = [400, 200];

    protected $successStatusCode = [200];

    public function getResponseData(): array
    {
        $this->validateResponse();

        $data = parent::getResponseData();

        $extractedData = [
            Constants::DOCUMENTS => $this->getUploadedDocuments()
        ];

        return array_merge($data, $extractedData);
    }

    protected function getUploadedDocuments()
    {
        $responseBody = $this->responseBody;

        $documents = $responseBody[Constants::DOCUMENTS] ?? [];

        $documentList = [];

        foreach ($documents as $document)
        {
            if (empty($document[Constants::DOCUMENT_TYPE]) === false)
            {
                array_push($documentList, $document[Constants::DOCUMENT_TYPE]);
            }
        }

        return array_unique($documentList, SORT_STRING);
    }

}
