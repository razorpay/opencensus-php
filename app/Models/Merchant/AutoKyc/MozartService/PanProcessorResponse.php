<?php

namespace RZP\Models\Merchant\AutoKyc\MozartService;

use App;

use RZP\Models\Merchant\Detail\Constants;

class PanProcessorResponse  extends BaseResponse
{

    public function getResponseData(): array
    {
        $this->validateResponse();

        $data = parent::getResponseData();

        $extractedData = [
            Constants::PAN_NAME_FROM_NSDL => $this->responseBody['data']['content']['response']['result']['name'] ?? null,
            Constants::SUCCESS            => true,
            Constants::DOCUMENT_TYPE      => Constants::PROMOTER_PAN,
        ];

        return array_merge($data, $extractedData);
    }
}
