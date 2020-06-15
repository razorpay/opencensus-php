<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\gstin;

use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\KycService\BaseResponse;

class GSTINProcessorResponse extends BaseResponse
{
    public function getResponseData(): array
    {
        $this->validateResponse();

        $data = parent::getResponseData();

        $detail = $this->responseBody['documents'][0]['detail'] ?? [];

        $extractedData = [
            Constants::LEGAL_NAME    => $detail['legal_name'] ?? null,
            Constants::TRADE_NAME    => $detail['trade_name'] ?? null,
            Constants::MEMBERS       => $this->getMembers($detail['signatory_names'] ?? ''),
            Constants::ADDRESS       => $detail['address'] ?? null,
            Constants::SUCCESS       => $this->isSuccessResponse(),
            Constants::DOCUMENT_TYPE => Constants::GSTIN,
        ];

        return array_merge($data, $extractedData);
    }

    protected function getMembers(string $members)
    {
        return explode(',',$members);
    }
}
