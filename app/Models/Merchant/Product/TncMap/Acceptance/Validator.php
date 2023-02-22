<?php

namespace RZP\Models\Merchant\Product\TncMap\Acceptance;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Constants::ACCEPTED => 'required|boolean|in:1',
        Constants::IP       => 'sometimes|ip',
    ];

    protected static $createValidators = [
        'tnc_input_check'
    ];

    public function validateTncInputCheck($input)
    {
        $app = App::getFacadeRoot();

        $partnerId = $app['basicauth']->getMerchantId();

        $isExpEnabled = (new Core())->isPartnerExcludedFromProvidingSubmerchantIp($partnerId);

        if($isExpEnabled === false)
        {
            if(isset($input[Constants::IP]) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TNC_ACCEPTANCE_AND_IP_NOT_TOGETHER);
            }
        }
    }
}
