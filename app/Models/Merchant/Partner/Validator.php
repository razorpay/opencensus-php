<?php

namespace RZP\Models\Merchant\Partner;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;

class Validator extends Merchant\Validator
{
    /**
     * @param $partnerType
     *
     * @throws Exception\BadRequestException
     */
    public function validatePartnerType($partnerType)
    {
        if (in_array($partnerType, Constants::$partnerTypes, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INCORRECT_PARTNER_TYPE);
        }
    }
}
