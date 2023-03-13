<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Constants\Country;
use RZP\Constants\IndianStates;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::EMAIL                     => 'sometimes|email|max:255',
        Entity::MERCHANT_ID               => 'required|alpha_num|size:14',
        Entity::NAME                      => 'sometimes|max:255',
        Entity::PHONE_PRIMARY             => 'sometimes|numeric|digits_between:8,15',
        Entity::PHONE_SECONDARY           => 'sometimes|numeric|digits_between:8,15',
        Entity::DIRECTOR                  => 'sometimes|boolean',
        Entity::EXECUTIVE                 => 'sometimes|boolean',
        Entity::PERCENTAGE_OWNERSHIP      => 'sometimes|numeric',
        Entity::POI_IDENTIFICATION_NUMBER => 'sometimes|string',
        Entity::PAN_DOC_STATUS            => 'sometimes|string|nullable', // required when create from merchant details
        Entity::POI_STATUS                => 'sometimes|string|nullable',
        Entity::POA_STATUS                => 'sometimes|string|nullable',
        Entity::AADHAAR_ESIGN_STATUS      => 'sometimes|string|nullable',
        Entity::AADHAAR_LINKED            => 'sometimes|boolean',
        Entity::AADHAAR_VERIFICATION_WITH_PAN_STATUS      => 'sometimes|string|nullable',
        Entity::AADHAAR_PIN               => 'sometimes|string|nullable',
        Entity::BVS_PROBE_ID              => 'sometimes|string|nullable',
        Entity::VERIFICATION_METADATA     => 'sometimes|array'
    ];

    protected static $editRules = [
        Entity::EMAIL                     => 'sometimes|email|max:255',
        Entity::NAME                      => 'sometimes|max:255',
        Entity::PHONE_PRIMARY             => 'sometimes|numeric|digits_between:8,15',
        Entity::PHONE_SECONDARY           => 'sometimes|numeric|digits_between:8,15',
        Entity::DIRECTOR                  => 'sometimes|boolean',
        Entity::EXECUTIVE                 => 'sometimes|boolean',
        Entity::PERCENTAGE_OWNERSHIP      => 'sometimes|numeric',
        Entity::POI_IDENTIFICATION_NUMBER => 'sometimes|string',
        Entity::NOTES                     => 'sometimes|notes',
        Entity::AADHAAR_ESIGN_STATUS      => 'sometimes|string|nullable',
        Entity::AADHAAR_VERIFICATION_WITH_PAN_STATUS      => 'sometimes|string|nullable',
        Entity::AADHAAR_PIN               => 'sometimes|string|nullable',
        Entity::AADHAAR_LINKED  => 'sometimes|boolean',
        Entity::BVS_PROBE_ID              => 'sometimes|string|nullable',
        Entity::VERIFICATION_METADATA     => 'sometimes|array'
    ];

    protected static $createStakeholderRules = [
        Entity::PERCENTAGE_OWNERSHIP  => 'sometimes|numeric|min:0|max:100',
        Entity::NAME                  => 'required|max:255',
        Entity::EMAIL                 => 'required|email|max:255',
        Constants::RELATIONSHIP       => 'sometimes|array',
        Constants::PHONE              => 'sometimes|array',
        Constants::ADDRESSES          => 'sometimes|array',
        Constants::KYC                => 'sometimes|array',
        Entity::NOTES                 => 'sometimes|array',
    ];

    protected static $editStakeholderRules = [
        Entity::PERCENTAGE_OWNERSHIP  => 'sometimes|numeric|min:0|max:100',
        Entity::NAME                  => 'sometimes|max:255',
        Entity::EMAIL                 => 'sometimes|email|max:255',
        Constants::RELATIONSHIP       => 'sometimes|array',
        Constants::PHONE              => 'sometimes|array',
        Constants::ADDRESSES          => 'sometimes|array',
        Constants::KYC                => 'sometimes|array',
        Entity::NOTES                 => 'sometimes|array',
    ];

    protected static $relationshipInputRules = [
        Constants::EXECUTIVE  => 'sometimes|boolean',
        Constants::DIRECTOR   => 'sometimes|boolean',
    ];

    protected static $phoneInputRules = [
        Constants::PRIMARY     => 'sometimes|numeric|digits_between:8,11',
        Constants::SECONDARY   => 'sometimes|numeric|digits_between:8,11',
    ];

    protected static $addressesInputRules = [
        Constants::RESIDENTIAL => 'sometimes|array',
    ];

    protected static $kycInputRules = [
        Constants::PAN => 'sometimes|personalPan',
    ];

    protected static $createResidentialAddressRules = [
        Constants::STREET      => 'required|string|between:10,255',
        Constants::CITY        => 'required|alpha_space|between:2,32',
        Constants::STATE       => 'required|alpha_space|between:2,32|custom',
        Constants::POSTAL_CODE => 'required|string|between:2,10',
        Constants::COUNTRY     => 'required|alpha_space|between:2,64|custom',
    ];

    protected static $editResidentialAddressRules = [
        Constants::STREET      => 'sometimes|string|between:10,255',
        Constants::CITY        => 'sometimes|alpha_space|between:2,32',
        Constants::STATE       => 'sometimes|alpha_space|between:2,32|custom',
        Constants::POSTAL_CODE => 'sometimes|string|between:2,10',
        Constants::COUNTRY     => 'sometimes|alpha_space|between:2,64|custom',
    ];

    public static $activationRules = [
        Entity::AADHAAR_LINKED  => 'sometimes|boolean'
    ];

    protected static $createStakeholderValidators = [
        'relationship_input',
        'phone_input',
        'create_addresses_input',
        'kyc_input',
    ];

    protected static $editStakeholderValidators = [
        'relationship_input',
        'phone_input',
        'edit_addresses_input',
        'kyc_input',
    ];

    protected function validateRelationshipInput(array $input)
    {
        if (isset($input[Constants::RELATIONSHIP]) === false)
        {
            return;
        }

        $this->validateInput('relationship_input', $input[Constants::RELATIONSHIP]);
    }

    protected function validatePhoneInput(array $input)
    {
        if (isset($input[Constants::PHONE]) === false)
        {
            return;
        }

        $this->validateInput('phone_input', $input[Constants::PHONE]);
    }

    protected function validateCreateAddressesInput(array $input)
    {
        $this->validateAddressesInput($input, 'create');
    }

    protected function validateEditAddressesInput(array $input)
    {
        $this->validateAddressesInput($input, 'edit');
    }

    protected function validateAddressesInput(array $input, string $action = 'create')
    {
        if (isset($input[Constants::ADDRESSES]) === false)
        {
            return;
        }

        $this->validateInput('addresses_input', $input[Constants::ADDRESSES]);

        if (isset($input[Constants::ADDRESSES][Constants::RESIDENTIAL]) === true)
        {
            $this->validateInput($action.'_residential_address', $input[Constants::ADDRESSES][Constants::RESIDENTIAL]);
        }
    }

    protected function validateKycInput(array $input)
    {
        if (isset($input[Constants::KYC]) === false)
        {
            return;
        }

        $this->validateInput('kyc_input', $input[Constants::KYC]);
    }

    public function validateState($attribute, $value)
    {
        $isValid = IndianStates::checkIfValidStateCodeOrName($value);

        if ($isValid === false)
        {
            throw new BadRequestValidationFailureException('Not a valid state: '. $value, Constants::STATE);
        }
    }

    protected function validateCountry($attribute, $value)
    {
        $isValid = Country::checkIfValidCountry($value);

        if ($isValid === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_COUNTRY, null, [$value]);
        }
    }

    public function validateAccountStakeholder(Merchant\Entity $account, Entity $stakeholder)
    {
        if ($account->getId() !== $stakeholder->getMerchantId())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_STAKEHOLDER_DOES_NOT_BELONG_TO_MERCHANT);
        }
    }
}
