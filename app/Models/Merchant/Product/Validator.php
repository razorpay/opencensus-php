<?php

namespace RZP\Models\Merchant\Product;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Product\TncMap;
use RZP\Models\Merchant\Product\Util\Constants;
use RZP\Models\Merchant\Account\Constants as AccountConstants;

class Validator extends Base\Validator
{
    protected static $createRules = [
        'product_name' => 'required|string|custom',
        'tnc_accepted' => 'sometimes|boolean|in:1',
        'ip'           => 'sometimes|ip',
    ];

    protected static $createProductRules = [
        'product_name' => 'required|string|custom',
    ];

    protected static $createLocRules = [
        'product_name' => 'required|string',
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
        $app = App::getFacadeRoot();

        $partner = $app['basicauth']->getPartnerMerchant();

        $validProductName = (in_array($value, Name::ENABLED, true) === true);

        if ($validProductName === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PRODUCT_NAME, null, ['valid_product_names' => Name::ENABLED]);
        }

        if (($this->merchant->isLinkedAccount() === true) and
            ($value !== Name::ROUTE))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PRODUCT_NAME);
        }


        if($partner !== null                                            // Check for partner type, only if $partner is set
            && ($this->merchant->isLinkedAccount() === false)           // Ignore check for linked accounts since we are checking for 'route' product above
            && (($value === Name::LINE_OF_CREDIT && $partner->isResellerPartner() === false)
                or ($partner->isResellerPartner() === true && $value !== Name::LINE_OF_CREDIT)))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PRODUCT_NAME);
        }
    }

    public function validateTncInputCheck($input)
    {
        $app = App::getFacadeRoot();

        $partnerId = $app['basicauth']->getPartnerMerchantId();

        $isExpEnabled = (new TncMap\Acceptance\Core())->isPartnerExcludedFromProvidingSubmerchantIp($partnerId);

        if($input[Constants::PRODUCT_NAME] === Name::LINE_OF_CREDIT)
        {
            // No other field except 'product_name' is allowed when LOC product is requested.
            $this->validateInput('create_loc', $input);
        }// for no doc merchants and for (non-LinkedAccount (route) and non whitelisted partner's submerchants) ip and tnc are required to be passed together
        else if($this->merchant->isNoDocOnboardingEnabled() === true or ($this->merchant->isLinkedAccount() === false and $isExpEnabled === false))
        {
            if((isset($input[Constants::IP]) === true and isset($input[Constants::TNC_ACCEPTED]) === false) or (isset($input[Constants::IP]) === false and isset($input[Constants::TNC_ACCEPTED]) === true))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TNC_ACCEPTANCE_AND_IP_NOT_TOGETHER);
            }
        }
        else if($this->merchant->isLinkedAccount() === false and $isExpEnabled === true)
        {
            if(isset($input[Constants::IP]) === true and isset($input[Constants::TNC_ACCEPTED]) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TNC_ACCEPTANCE_AND_IP_NOT_TOGETHER);
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

    public function validateCreateProductRequest(array $input)
    {
        $isPhantomPrefillEnabled = \Request::all()[AccountConstants::PHANTOM_PREFILL_ENABLED] ?? false;

        if ($isPhantomPrefillEnabled)
        {
            $this->validateInput('create_product', $input);
        }
        else
        {
            $this->validateInput('create', $input);
        }
    }
}
