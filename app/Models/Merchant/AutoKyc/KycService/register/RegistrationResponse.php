<?php

namespace RZP\Models\Merchant\AutoKyc\KycService\register;

use App;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\AutoKyc\KycService\BaseResponse;

class RegistrationResponse extends BaseResponse
{

    public function validateResponse()
    {
        parent::validateResponse();

        if (empty($this->getKycId()) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_KYC_ID_MISSING, Constants::KYC_ID, $this->responseBody);
        }

    }
}
