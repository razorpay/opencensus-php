<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;
use RZP\Constants\IndianStates;
use RZP\Models\Merchant\Detail;
use RZP\Exception\BadRequestValidationFailureException;

class Helper
{
    public static function getSubMerchantCreateInput(array $input): array
    {
        $data = [
            Merchant\Entity::NAME => $input[Constants::PROFILE][Constants::NAME],
        ];

        if (empty($input[Constants::EMAIL]) === false)
        {
            $data[Merchant\Entity::EMAIL] = $input[Constants::EMAIL];
        }

        if (empty($input[Constants::EXTERNAL_ID]) === false)
        {
            $data[Merchant\Entity::EXTERNAL_ID] = $input[Constants::EXTERNAL_ID];
        }

        if (empty($input[Constants::LEGAL_ENTITY_ID]) === false)
        {
            $data[Merchant\Entity::LEGAL_ENTITY_ID] = $input[Constants::LEGAL_ENTITY_ID];
        }
        else if (empty($input[Constants::LEGAL_EXTERNAL_ID]) === false)
        {
            $data[Merchant\Entity::LEGAL_EXTERNAL_ID] = $input[Constants::LEGAL_EXTERNAL_ID];
        }

        return $data;
    }

    public static function getSubMerchantInput(array $input): array
    {
        $details = [];

        if (isset($input[Constants::PROFILE]) === true)
        {
            if (isset($input[Constants::PROFILE][Constants::BRAND]) === true)
            {
                $attributeMapping = [
                    Constants::LOGO => Merchant\Entity::LOGO_URL,
                    Constants::ICON => Merchant\Entity::ICON_URL,
                    Constants::COLOR => Merchant\Entity::BRAND_COLOR,
                ];

                foreach ($attributeMapping as $key => $value)
                {
                    if (array_key_exists($key, $input[Constants::PROFILE][Constants::BRAND]) === true)
                    {
                        $details[$value] = $input[Constants::PROFILE][Constants::BRAND][$key];
                    }
                }
            }

            if (array_key_exists(Constants::DASHBOARD_DISPLAY, $input[Constants::PROFILE]) === true)
            {
                $details[Merchant\Entity::DISPLAY_NAME] = $input[Constants::PROFILE][Constants::DASHBOARD_DISPLAY];
            }
        }

        if (isset($input[Constants::NOTES]) === true)
        {
            $details[Merchant\Entity::NOTES] = $input[Constants::NOTES];
        }

        return $details;
    }

    public static function getSubMerchantDetailInput(array $input): array
    {
        $detailInput = [];

        if (isset($input[Constants::EMAIL]) === true)
        {
            $detailInput[Detail\Entity::CONTACT_EMAIL]            = $input[Constants::EMAIL];
            $detailInput[Detail\Entity::TRANSACTION_REPORT_EMAIL] = $input[Constants::EMAIL];
        }

        if (isset($input[Constants::CONTACT_INFO]) === true)
        {
            $detailInput[Detail\Entity::CONTACT_EMAIL]  = $input[Constants::CONTACT_INFO][Constants::EMAIL];
            $detailInput[Detail\Entity::CONTACT_NAME]   = $input[Constants::CONTACT_INFO][Constants::NAME];
            $detailInput[Detail\Entity::CONTACT_MOBILE] = $input[Constants::CONTACT_INFO][Constants::PHONE];
        }

        if (isset($input[Constants::PHONE]) === true)
        {
            $detailInput[Detail\Entity::CONTACT_MOBILE] = $input[Constants::PHONE];
        }

        if (isset($input[Constants::BUSINESS_ENTITY]) === true)
        {
            $businessType = Detail\BusinessType::getIndexFromKey($input[Constants::BUSINESS_ENTITY]);

            $detailInput[Detail\Entity::BUSINESS_TYPE] = $businessType;
        }

        $customFields = self::getCustomFieldsFromInput($input);

        if (empty($customFields) === false)
        {
            $detailInput[Detail\Entity::CUSTOM_FIELDS] = $customFields;
        }

        if (isset($input[Constants::PROFILE]) === true)
        {
            // fill profile data
            $profileAttributesMapping = [
                Constants::DESCRIPTION    => Detail\Entity::BUSINESS_DESCRIPTION,
                Constants::BUSINESS_MODEL => Detail\Entity::BUSINESS_PAYMENTDETAILS,
                Constants::BILLING_LABEL  => Detail\Entity::BUSINESS_DBA,
                Constants::WEBSITE        => Detail\Entity::BUSINESS_WEBSITE,
                Constants::NAME           => Detail\Entity::BUSINESS_NAME,
            ];

            foreach ($profileAttributesMapping as $key => $value)
            {
                if (array_key_exists($key, $input[Constants::PROFILE]) === true)
                {
                    $detailInput[$value] = $input[Constants::PROFILE][$key];
                }
            }

            $categoryData = [];

            if (isset($input[Constants::PROFILE][Constants::MCC]) === true)
            {
                $mccCode = $input[Constants::PROFILE][Constants::MCC];

                $categoryData = Detail\BusinessSubCategoryMetaData::fetchCategoryAndSubCategoryByMccCode($mccCode);
            }

            $ownerInfo = [];

            if (isset($input[Constants::PROFILE][Constants::OWNER_INFO]) === true)
            {
                $ownerInfo[Detail\Entity::PROMOTER_PAN]      = $input[Constants::PROFILE][Constants::OWNER_INFO][Constants::IDENTIFICATION][0][Constants::IDENTIFICATION_NUMBER];
                $ownerInfo[Detail\Entity::PROMOTER_PAN_NAME] = $input[Constants::PROFILE][Constants::OWNER_INFO][Constants::NAME];
            }

            $detailInput = array_merge(
                $detailInput,
                self::getDocumentDetailsFromInput($input),
                $categoryData,
                $ownerInfo,
                ...self::getAddressesFromInput($input)
            );
        }

        if (self::shouldEnableInternational($input) === true)
        {
            $detailInput[Detail\Entity::BUSINESS_INTERNATIONAL] = true;
        }

        $detailInput = array_merge($detailInput, self::getBankAccountFromInput($input));

        return $detailInput;
    }

    public static function validateCreateInputForKyc(Merchant\Entity $partner, array $input)
    {
        if ($partner->isKycHandledByPartner() === false)
        {
            self::validateCreateInputWithKycNotHandledByPartner($partner, $input);
        }
        else
        {
            self::validateCreateInputWithKycHandledByPartner($partner, $input);
        }
    }

    protected static function validateCreateInputWithKycHandledByPartner(Merchant\Entity $partner, array $input)
    {
        $partner->getValidator()->validateMerchantEmailUnique($input[Constants::EMAIL], $partner->getOrgId());

        if (empty($input[Constants::PHONE]) === true)
        {
            throw new BadRequestValidationFailureException(Constants::PHONE . ' is required');
        }

        foreach ($input[Constants::PROFILE][Constants::ADDRESSES] as $address)
        {
            if (empty($address[Constants::DISTRICT_NAME]) === true)
            {
                throw new BadRequestValidationFailureException(Constants::DISTRICT_NAME . ' is required');
            }
        }
    }

    protected static function validateCreateInputWithKycNotHandledByPartner(Merchant\Entity $partner, array $input)
    {
        // TODO: move to validator later if more code gets added here
        if (empty($input[Constants::BUSINESS_ENTITY]) === true)
        {
            throw new BadRequestValidationFailureException(Constants::BUSINESS_ENTITY . ' is required');
        }

        if (empty($input[Constants::CONTACT_INFO]) === true)
        {
            throw new BadRequestValidationFailureException(Constants::CONTACT_INFO . ' is required');
        }

        if ((empty($input[Constants::EMAIL]) === true) and ($partner->hasOptionalSubmerchantEmailFeature() === false))
        {
            throw new BadRequestValidationFailureException(Constants::EMAIL . ' is required');
        }

        if (isset($input[Constants::PROFILE]) === true)
        {
            if (empty($input[Constants::PROFILE][Constants::IDENTIFICATION]) === true)
            {
                throw new BadRequestValidationFailureException(Constants::IDENTIFICATION . ' is required');
            }

            if (empty($input[Constants::PROFILE][Constants::OWNER_INFO]) === true)
            {
                throw new BadRequestValidationFailureException(Constants::OWNER_INFO . ' is required');
            }
        }
    }

    private static function getCustomFieldsFromInput(array $input): array
    {
        $customFields = [];

        if (isset($input[Constants::TNC]) === true)
        {
            $customFields[Constants::TNC] = $input[Constants::TNC];
        }

        if ((isset($input[Constants::PROFILE][Constants::APPS]) === true))
        {
            $customFields[Constants::APPS] = $input[Constants::PROFILE][Constants::APPS];
        }

        return $customFields;
    }

    /**
     * We modify the input here because for some cases like address type, we want to accept case insensitive chars
     * like both REGISTERED and registered. So we modify the input here, so that validations and === comparisons will
     * pass and there will be uniformity elsewhere in the code
     *
     * @param array $input
     *
     * @return array
     */
    public static function modifyAccountInput(array $input): array
    {
        if (empty($input[Constants::PROFILE][Constants::ADDRESSES]) === false)
        {
            foreach ($input[Constants::PROFILE][Constants::ADDRESSES] as $key => $address)
            {
                $type = strtolower($input[Constants::PROFILE][Constants::ADDRESSES][$key][Constants::TYPE]);

                $input[Constants::PROFILE][Constants::ADDRESSES][$key][Constants::TYPE] = $type;
            }
        }

        // convert email to lowercase
        if (empty($input[Constants::EMAIL]) === false)
        {
            $input[Constants::EMAIL] = mb_strtolower($input[Constants::EMAIL]);
        }

        if (empty($input[Constants::LEGAL_EXTERNAL_ID]) === false)
        {
            $legalEntity = app('repo')->legal_entity->fetchByExternalId($input[Constants::LEGAL_EXTERNAL_ID]);

            // if its a valid legal entity passed and it already exists
            if (empty($legalEntity) === false)
            {
                $input[Constants::LEGAL_ENTITY_ID] = $legalEntity->getId();

                unset($input[Constants::LEGAL_EXTERNAL_ID]);
            }
        }

        return $input;
    }

    private static function getAddressesFromInput(array $input): array
    {
        $addresses = [];

        if (empty($input[Constants::PROFILE][Constants::ADDRESSES]) === true)
        {
            return $addresses;
        }

        foreach ($input[Constants::PROFILE][Constants::ADDRESSES] as $address)
        {
            $mapping = null;

            if ($address[Constants::TYPE] === Constants::REGISTERED)
            {
                $mapping = [
                    Constants::LINE1         => Detail\Entity::BUSINESS_REGISTERED_ADDRESS,
                    Constants::LINE2         => Detail\Entity::BUSINESS_REGISTERED_ADDRESS_L2,
                    Constants::CITY          => Detail\Entity::BUSINESS_REGISTERED_CITY,
                    Constants::STATE         => Detail\Entity::BUSINESS_REGISTERED_STATE,
                    Constants::DISTRICT_NAME => Detail\Entity::BUSINESS_REGISTERED_DISTRICT,
                    Constants::PIN           => Detail\Entity::BUSINESS_REGISTERED_PIN,
                    Constants::COUNTRY       => Detail\Entity::BUSINESS_REGISTERED_COUNTRY,
                ];
            }
            else if ($address[Constants::TYPE] === Constants::OPERATION)
            {
                $mapping = [
                    Constants::LINE1         => Detail\Entity::BUSINESS_OPERATION_ADDRESS,
                    Constants::LINE2         => Detail\Entity::BUSINESS_OPERATION_ADDRESS_L2,
                    Constants::CITY          => Detail\Entity::BUSINESS_OPERATION_CITY,
                    Constants::STATE         => Detail\Entity::BUSINESS_OPERATION_STATE,
                    Constants::DISTRICT_NAME => Detail\Entity::BUSINESS_OPERATION_DISTRICT,
                    Constants::PIN           => Detail\Entity::BUSINESS_OPERATION_PIN,
                    Constants::COUNTRY       => Detail\Entity::BUSINESS_OPERATION_COUNTRY,
                ];
            }

            if (empty($mapping) === false)
            {
                $registeredAddress = [];

                foreach ($mapping as $key => $value)
                {
                    if (isset($address[$key]) === true)
                    {
                        if ($key === Constants::STATE)
                        {
                            $stateCode  = IndianStates::getStateCode($address[Constants::STATE]);

                            if (isset($stateCode) === false)
                            {
                                throw new BadRequestValidationFailureException('Not a valid state: '. $address[Constants::STATE]);
                            }

                            $registeredAddress[$value] = $stateCode;
                        }
                        else
                        {
                            $registeredAddress[$value] = $address[$key];
                        }
                    }
                }

                $addresses[] = $registeredAddress;
            }
        }

        return $addresses;
    }

    private static function getDocumentDetailsFromInput(array $input): array
    {
        $details = [];

        if (isset($input[Constants::PROFILE][Constants::IDENTIFICATION]) === false)
        {
            return $details;
        }

        foreach ($input[Constants::PROFILE][Constants::IDENTIFICATION] as $document)
        {
            switch ($document[Constants::TYPE])
            {
                case DocumentType::COMPANY_PAN:
                    $details[Detail\Entity::COMPANY_PAN] = $document[Constants::IDENTIFICATION_NUMBER];
                    break;

                case DocumentType::GSTIN:
                    $details[Detail\Entity::GSTIN] = $document[Constants::IDENTIFICATION_NUMBER];
                    break;
            }
        }

        return $details;
    }

    private static function getBankAccountFromInput(array $input): array
    {
        if ((isset($input[Constants::SETTLEMENT]) === false) or
            (isset($input[Constants::SETTLEMENT][Constants::FUND_ACCOUNTS][0][Constants::BANK_ACCOUNT])) === false)
        {
            return [];
        }

        $bankAccount = $input[Constants::SETTLEMENT][Constants::FUND_ACCOUNTS][0][Constants::BANK_ACCOUNT];

        return [
            Detail\Entity::BANK_ACCOUNT_NAME           => $bankAccount[Constants::NAME],
            Detail\Entity::BANK_BRANCH_IFSC            => $bankAccount[Constants::IFSC],
            Detail\Entity::BANK_ACCOUNT_NUMBER         => $bankAccount[Constants::ACCOUNT_NUMBER],
        ];
    }

    public static function getBankAccountNotesFromInput(array $input)
    {
        if ((isset($input[Constants::SETTLEMENT]) === false) or
            (isset($input[Constants::SETTLEMENT][Constants::FUND_ACCOUNTS][0][Constants::BANK_ACCOUNT])) === false)
        {
            return [];
        }

        $bankAccount = $input[Constants::SETTLEMENT][Constants::FUND_ACCOUNTS][0][Constants::BANK_ACCOUNT];

        return $bankAccount[Constants::NOTES] ?? [];
    }

    public static function shouldEnableInternational(array $input)
    {
        if ((isset($input[Constants::SETTINGS][Constants::PAYMENT]) === true) and
            (empty($input[Constants::SETTINGS][Constants::PAYMENT][Constants::INTERNATIONAL]) === false))
        {
            return true;
        }

        return false;
    }
}
