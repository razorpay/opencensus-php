<?php

namespace RZP\Models\Merchant\Product;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Product\Util\Constants;

class Validator extends Base\Validator
{
    protected static $createRules = [
        'product_name' => 'required|string|custom',
        'tnc_accepted' => 'sometimes|boolean|in:1',
        'ip'           => 'sometimes|ip',
    ];

    protected static $createValidators = [
        'tnc_input_check'
        ];

    public function __construct($entity = null)
    {
        parent::__construct($entity);

        $app = App::getFacadeRoot();

        $this->merchant = $app['basicauth']->getMerchant();
    }

    public function validateProductName($attribute, $value)
    {
        $validProductName = (in_array($value, Name::ENABLED, true) === true);

        if ($validProductName === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PRODUCT_NAME, null, ['valid_product_names' => Name::ENABLED]);
        }
    }

    public function validateTncInputCheck($input)
    {
        if($this->merchant->isNoDocOnboardingEnabled() === true)
        {
            //both ip and tnc_accepted fields are required in payload during other one's presence in case of no-doc merchant
            if((isset($input[Constants::IP]) === true and isset($input[Constants::TNC_ACCEPTED]) === false) or (isset($input[Constants::IP]) === false and isset($input[Constants::TNC_ACCEPTED]) === true))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TNC_ACCEPTANCE_FOR_NO_DOC);
            }
        }
        else
        {
            if(isset($input[Constants::IP]) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'ip is/are not required and should not be sent');
            }
        }
    }
}
