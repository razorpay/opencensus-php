<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\poi;

use App;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\KycService\BaseResponse;

class POIProcessorResponse extends BaseResponse
{
    public function getResponseData(): array
    {
        $this->validateResponse();

        $data = parent::getResponseData();

        $extractedData = [
            Constants::PAN_NAME_FROM_NSDL => $this->responseBody['documents'][0]['detail']['name'] ?? null,
            Constants::SUCCESS            => $this->isSuccessResponse(),
            Constants::DOCUMENT_TYPE      => Constants::PROMOTER_PAN,
        ];

        return array_merge($data, $extractedData);
    }

    public function validateResponse()
    {
        parent::validateResponse();

        $result = $this->responseBody['documents'][0]['detail']['name'] ?? null;

        if ($this->isSuccessResponse())
        {
            if (empty($result) === true or $this->responseBody['documents'][0][Constants::DOCUMENT_TYPE] !== Constants::DOCUMENT_TYPES[Constants::PERSONAL_PAN])
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_KYC_INTEGRATION, null, $this->responseBody);
            }
        }
    }
}
