<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\poa;

use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\KycService\BaseResponse;

class POAProcessorResponse extends BaseResponse
{
    public function getResponseData(): array
    {
        $this->validateResponse();

        $data = parent::getResponseData();

        $extractedData = [
            Constants::NAME          => $this->getOcrName(),
            Constants::SUCCESS       => $this->isSuccessResponse(),
            Constants::DOCUMENT_TYPE => $this->requestInput[Constants::DOCUMENT_TYPE] ?? "",
        ];

        return array_merge($data, $extractedData);
    }

    private function getOcrName(): ?string
    {
        $ocrName = $this->responseBody['documents'][0]['detail']['name'] ?? null;

        return $ocrName;
    }
}
