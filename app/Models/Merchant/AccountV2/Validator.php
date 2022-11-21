<?php

namespace RZP\Models\Merchant\AccountV2;

use RZP\Constants\IndianStates;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Stakeholder;
use RZP\Models\Merchant\Account\Constants;
use RZP\Models\Merchant\Detail\ValidationFields;
use RZP\Models\Merchant\Detail\NeedsClarification;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Merchant\Validator
{
    protected static $createAccountRules = [
        Constants::REFERENCE_ID                    => 'sometimes',
        Constants::EMAIL                           => 'required|email',
        Constants::PHONE                           => 'required|regex:/^\+?[1-9]{1}[0-9]{7,14}$/u',
        Constants::CONTACT_NAME                    =>  array ('sometimes','max:255','regex:/^[\p{L} ,@#-.%\/]{1,255}$/u'),
        Constants::LEGAL_BUSINESS_NAME             => 'required|string',
        Constants::CUSTOMER_FACING_BUSINESS_NAME   => 'filled|string',
        Constants::BUSINESS_TYPE                   => 'required|string',
        Constants::PROFILE                         => 'required|array',
        Constants::LEGAL_INFO                      => 'sometimes|array',
        Constants::CONTACT_INFO                    => 'sometimes|array',
        Constants::APPS                            => 'sometimes|array',
        Constants::BRAND                           => 'sometimes|array',
        Constants::TOS_ACCEPTANCE                  => 'sometimes|array',
        Constants::NOTES                           => 'sometimes|notes',
        Constants::NO_DOC_ONBOARDING               => 'sometimes|bool',
        Constants::TYPE                            => 'sometimes|in:route'
    ];

    protected static $editAccountRules = [
        Constants::PHONE                           => 'filled|regex:/^\+?[1-9]{1}[0-9]{7,14}$/u',
        Constants::CONTACT_NAME                    =>  array ('sometimes','max:255','regex:/^[\p{L} ,@#-.%\/]{1,255}$/u'),
        Constants::LEGAL_BUSINESS_NAME             => 'sometimes|string',
        Constants::CUSTOMER_FACING_BUSINESS_NAME   => 'filled|string',
        Constants::PROFILE                         => 'sometimes|array',
        Constants::LEGAL_INFO                      => 'sometimes|array',
        Constants::CONTACT_INFO                    => 'sometimes|array',
        Constants::APPS                            => 'sometimes|array',
        Constants::BRAND                           => 'sometimes|array',
        Constants::TOS_ACCEPTANCE                  => 'sometimes|array',
        Constants::NOTES                           => 'sometimes|notes',
    ];

    protected static $profileRules = [
        Constants::ADDRESSES         => 'required|array|max:2',
        Constants::ADDRESSES . '.*'  => 'filled|array',
        Constants::CATEGORY          => 'required|string',
        Constants::SUBCATEGORY       => 'required|string',
        Constants::DESCRIPTION       =>  array ('sometimes','regex:/^[\p{L} ,@#-.%\/]{1,255}$/u'),
        Constants::BUSINESS_MODEL    => 'sometimes|string',
    ];

    protected static $editProfileRules = [
        Constants::ADDRESSES         => 'sometimes|array|max:2',
        Constants::ADDRESSES . '.*'  => 'filled|array',
        Constants::CATEGORY          => 'sometimes|string',
        Constants::SUBCATEGORY       => 'sometimes|string',
        Constants::DESCRIPTION       =>  array ('sometimes','regex:/^[\p{L} ,@#-.%\/]{1,255}$/u'),
        Constants::BUSINESS_MODEL    => 'sometimes|string'
    ];

    protected static $accountAddressRules = [
        Constants::STREET1     => 'required|string|max:100',
        Constants::STREET2     => 'required|string|max:100',
        Constants::CITY        => 'required|string',
        Constants::STATE       => 'required|string|custom',
        Constants::POSTAL_CODE => 'required|integer',
        Constants::COUNTRY     => 'required|string',
    ];

    protected static $editAccountAddressRules = [
        Constants::STREET1     => 'filled|string|max:100',
        Constants::STREET2     => 'filled|string|max:100',
        Constants::CITY        => 'filled|string',
        Constants::STATE       => 'filled|string|custom',
        Constants::POSTAL_CODE => 'filled|integer',
        Constants::COUNTRY     => 'filled|string',
    ];

    protected static $addressTypeRules = [
        Constants::OPERATION  => 'sometimes|array',
        Constants::REGISTERED => 'required|array',
    ];

    protected static $editAddressTypeRules = [
        Constants::OPERATION  => 'sometimes|array',
        Constants::REGISTERED => 'sometimes|array',
    ];

    protected static $legalInfoRules = [
        Constants::PAN => 'sometimes|companyPan',
        Constants::GST => 'sometimes|gstin',
        Constants::CIN => 'sometimes|string',
    ];

    protected static $brandRules = [
        Constants::COLOR => 'required|regex:(^[0-9a-fA-F]{6}$)',
    ];

    protected static $tosAcceptanceRules = [
        Constants::DATE       => 'sometimes',
        Constants::IP         => 'sometimes',
        Constants::USER_AGENT => 'sometimes',
    ];

    protected static $contactInfoRules = [
        Constants::EMAIL      => 'required|string',
        Constants::PHONE      => 'sometimes|numeric|digits:10',
        Constants::POLICY_URL => 'sometimes|string|nullable',
    ];

    protected static $appsRules = [
        Constants::WEBSITES       => 'sometimes|array|min:1',
        Constants::ANDROID        => 'sometimes|array|min:1',
        Constants::ANDROID . '.*' => 'filled|array',
        Constants::IOS            => 'sometimes|array|min:1',
        Constants::IOS . '.*'     => 'filled|array',
    ];

    protected static $appsAndroidRules = [
        Constants::URL  => 'required|string',
        Constants::NAME => 'required|string',
    ];

    protected static $appsIosRules     = [
        Constants::URL  => 'required|string',
        Constants::NAME => 'required|string',
    ];

    protected static $createAccountValidators = [
        'profile_input',
        'legal_info',
        'brand',
        'tos_acceptance',
        'contact_info',
        'apps',
    ];

    protected static $editAccountValidators = [
        'edit_profile_input',
        'legal_info',
        'brand',
        'tos_acceptance',
        'contact_info',
        'apps',
    ];

    protected static $addressTypeValidators = [
        'address_check'
    ];

    protected static $editAddressTypeValidators = [
        'edit_address_check'
    ];

    protected function validateAddressCheck(array $input)
    {
        foreach ($input as $address)
        {
            $this->validateInput('AccountAddress', $address);
        }
    }

    protected function validateEditAddressCheck(array $input)
    {
        foreach ($input as $address)
        {
            $this->validateInput('EditAccountAddress', $address);
        }
    }

    protected function validateAddresses(array $profileInput, string $action = '')
    {
        $addresses = $profileInput[Constants::ADDRESSES];

        $this->validateInput($action. 'AddressType', $addresses);
    }

    protected function validateProfileInput(array $input)
    {
        $profileInput = $input[Constants::PROFILE];

        $this->validateInput('profile', $profileInput);

        $this->validateAddresses($profileInput);
    }

    protected function validateEditProfileInput(array $input)
    {
        if (isset($input[Constants::PROFILE]) === false)
        {
            return;
        }

        $profileInput = $input[Constants::PROFILE];

        $this->validateInput('edit_profile', $profileInput);

        if (isset($profileInput[Constants::ADDRESSES]) === true)
        {
            $this->validateAddresses($profileInput, 'edit');
        }
    }

    protected function validateLegalInfo(array $input)
    {
        if (isset($input[Constants::LEGAL_INFO]) === false)
        {
            return;
        }

        $legalInfo = $input[Constants::LEGAL_INFO];

        $this->validateInput('legalInfo', $legalInfo);
    }

    protected function validateBrand(array $input)
    {
        if (isset($input[Constants::BRAND]) === false)
        {
            return;
        }

        $brand = $input[Constants::BRAND];

        $this->validateInput('brand', $brand);
    }

    protected function validateTosAcceptance(array $input)
    {
        if (isset($input[Constants::TOS_ACCEPTANCE]) === false)
        {
            return;
        }

        $tosAcceptance = $input[Constants::TOS_ACCEPTANCE];

        $this->validateInput('tosAcceptance', $tosAcceptance);
    }

    protected function validateContactInfo(array $input, string $action = '')
    {
        if (isset($input[Constants::CONTACT_INFO]) === false)
        {
            return;
        }

        $fieldNames = [
            Constants::SUPPORT,
            Constants::CHARGEBACK,
            Constants::REFUND,
            Constants::DISPUTE,
        ];

        $contactInfo = $input[Constants::CONTACT_INFO];

        foreach ($fieldNames as $fieldName)
        {
            if (isset($contactInfo[$fieldName]) === true)
            {
                $this->validateInput($action . 'contactInfo', $contactInfo[$fieldName]);
            }
        }
    }

    protected function validateApps(array $input, string $action = '')
    {
        if (isset($input[Constants::APPS]) === false)
        {
            return;
        }

        $appsInput = $input[Constants::APPS];

        $this->validateInput('apps', $appsInput);

        $this->validateAndroid($appsInput);

        $this->validateIos($appsInput);
    }

    protected function validateAndroid(array $appsInput)
    {
        if (isset($appsInput[Constants::ANDROID]) === false)
        {
            return;
        }

        $androidInput = $appsInput[Constants::ANDROID];

        foreach ($androidInput as $android)
        {
            $this->validateInput('appsAndroid', $android);
        }
    }

    protected function validateIos(array $appsInput)
    {
        if (isset($appsInput[Constants::IOS]) === false)
        {
            return;
        }

        $iosInput = $appsInput[Constants::IOS];

        foreach ($iosInput as $android)
        {
            $this->validateInput('appsIos', $android);
        }
    }

    public function validateNeedsClarificationRespondedIfApplicable(Merchant\Entity $merchant, array $input)
    {
        $merchantDetails = $merchant->merchantDetail;

        if (empty($merchantDetails) === true || $merchantDetails->getActivationStatus() !== Detail\Status::NEEDS_CLARIFICATION)
        {
            return;
        }

        $clarificationReasons = (new NeedsClarification\Core)->getNonAcknowledgedNCFields($merchant, $merchantDetails);

        //If all the NC fields are acknowledged, form gets auto-submitted. All the new field update will be rejected by form lock validation.
        //So silently skip validation here
        if($clarificationReasons[Merchant\Constants::COUNT] === 0)
        {
            return;
        }

        $ncFields = $clarificationReasons['fields'] ?? [];

        $extraFields = array_diff_key($input, $ncFields);

        if($merchant->isNoDocOnboardingEnabled() === true and ((new Merchant\AccountV2\Core())->isNoDocOnboardingGmvLimitExhausted($merchant) === false))
        {
            $noDocValidationFields = ValidationFields::getOptionalFieldsForNoDocOnboarding($merchantDetails->getBusinessType());

            $extraFields = array_diff_key($extraFields, array_flip($noDocValidationFields));

            if (count($extraFields) > 0)
            {
                $tracePayload = [
                    'provided_fields'          => $input,
                    'accepted_fields'          => array_keys($ncFields),
                    'accepted_optional_fields' => $noDocValidationFields
                ];

                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ONLY_NEEDS_CLARIFICATION_FIELDS_ARE_ALLOWED, null, $tracePayload);
            }
        }
        else if (count($extraFields) > 0)
        {
            $tracePayload = [
                'provided_fields' => $input,
                'accepted_fields' => array_keys($ncFields)
            ];

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ONLY_NEEDS_CLARIFICATION_FIELDS_ARE_ALLOWED, null, $tracePayload);
        }

        $merchantDetailInput = array_diff_key($input, Stakeholder\Constants::MERCHANT_DETAILS_STAKEHOLDER_MAPPING);

        if (empty($merchantDetailInput) === false)
        {
            //validate the merchant details NC fields as per merchant details edit rules
            $merchantDetails->getValidator()->validateInput('edit', $merchantDetailInput);
        }
    }

    /**
     * This function restricts merchant to provide only specific optional fields while in 'activated_kyc_pending' state.
     * Once a merchant reaches this state, we will only allow specific optional fields to be submitted via Onboarding APIs.
     * Note: This validation currently exists within onboarding APIs itself. We currently don't have checks to restrict a merchant on other platforms like dashboard, apps etc.
     *
     * @param Merchant\Entity $merchant
     * @param array $input
     * @return void
     *
     * @throws Exception\BadRequestException
     */
    public function validateOptionalFieldSubmissionInActivatedKycPendingState(Merchant\Entity $merchant, array $input)
    {
        $merchantDetails = $merchant->merchantDetail;

        if (empty($merchantDetails) === true || $merchantDetails->getActivationStatus() !== Detail\Status::ACTIVATED_KYC_PENDING)
        {
            return;
        }

        $noDocValidationFields = ValidationFields::getOptionalFieldsForNoDocOnboarding($merchantDetails->getBusinessType());

        $extraFields = array_diff_key($input, array_flip($noDocValidationFields));

        if (count($extraFields) > 0)
        {
            $tracePayload = [
                'provided_fields'          => $input,
                'accepted_optional_fields' => $noDocValidationFields
            ];

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ONLY_REMAINING_KYC_FIELDS_ARE_ALLOWED, null, $tracePayload);
        }
    }

    protected function validateState(string $attribute, string $value)
    {
        //check if a valid state code exists for the input
        if(IndianStates::getStateCode($value) === null)
        {
            throw new Exception\BadRequestValidationFailureException('State name entered is incorrect. Please provide correct state name.', Constants::STATE);
        }
    }
}
