<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\cin;

use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\KycService\BaseResponse;

class CINProcessorResponse extends BaseResponse
{
    public function getResponseData(): array
    {
        $this->validateResponse();

        $data = parent::getResponseData();

        $detail = $this->responseBody['documents'][0]['detail'] ?? [];

        $extractedData = [
            Constants::COMPANY_NAME      => $detail['company_name'] ?? null,
            Constants::SIGNATORY_DETAILS => $this->getSignatoryDetails($detail['signatory_details'] ?? ''),
            Constants::ADDRESS           => $detail['registered_address'] ?? null,
            Constants::SUCCESS           => $this->isSuccessResponse(),
            Constants::DOCUMENT_TYPE     => Constants::CIN,
        ];

        return array_merge($data, $extractedData);
    }

    protected function getSignatoryDetails(string $signatoryDetails)
    {
        if (empty($signatoryDetails) === false)
        {
            return json_decode(stripslashes($signatoryDetails), true);
        }

        return [];
    }
}
