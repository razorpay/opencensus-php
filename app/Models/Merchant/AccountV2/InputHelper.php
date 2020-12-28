<?php

namespace RZP\Models\Merchant\AccountV2;

use RZP\Constants\IndianStates;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Detail\Entity as DE;
use RZP\Models\Merchant\Account\Constants;

class InputHelper
{
    public static function getSubMerchantCreateInput(array $input): array
    {
        $data = [
            Merchant\Entity::NAME  => $input[Constants::LEGAL_BUSINESS_NAME],
            Merchant\Entity::EMAIL => $input[Constants::EMAIL]
        ];

        return $data;
    }

    public static function getSubMerchantInput(array $input): array
    {
        $subMerchant = [];

        if (isset($input[Constants::BRAND]) === true)
        {
            $attributeMapping = [
                Constants::COLOR => Merchant\Entity::BRAND_COLOR,
            ];

            foreach ($attributeMapping as $key => $value)
            {
                if (array_key_exists($key, $input[Constants::BRAND]) === true)
                {
                    $subMerchant[$value] = $input[Constants::BRAND][$key];
                }
            }
        }

        if (isset($input[Constants::ACCOUNT_CODE]) === true)
        {
            $subMerchant[Merchant\Entity::ACCOUNT_CODE] = $input[Constants::ACCOUNT_CODE];
        }


        if (isset($input[Constants::NOTES]) === true)
        {
            $subMerchant[Merchant\Entity::NOTES] = $input[Constants::NOTES];
        }

        return $subMerchant;
    }

    public static function getSubMerchantDetailInput(array $input): array
    {
        $detailInput = [];

        $businessType = $input[Constants::BUSINESS_TYPE];

        $detailInput[Detail\Entity::BUSINESS_TYPE] = Detail\BusinessType::getIndexFromKey($businessType);

        if (isset($input[Constants::EMAIL]) === true)
        {
            $detailInput[Detail\Entity::CONTACT_EMAIL]            = $input[Constants::EMAIL];
            $detailInput[Detail\Entity::TRANSACTION_REPORT_EMAIL] = $input[Constants::EMAIL];
        }

        if (isset($input[Constants::PHONE]) === true)
        {
            $detailInput[Detail\Entity::CONTACT_MOBILE] = $input[Constants::PHONE];
        }

        $customFields = self::getCustomFieldsFromInput($input);

        if (empty($customFields) === false)
        {
            $detailInput[Detail\Entity::CUSTOM_FIELDS] = $customFields;
        }

        if (isset($input[Constants::DOING_BUSINESS_AS]) === true)
        {
            $detailInput[Detail\Entity::BUSINESS_DBA] = $input[Constants::DOING_BUSINESS_AS];
        }

        if (isset($input[Constants::LEGAL_BUSINESS_NAME]) === true)
        {
            $detailInput[Detail\Entity::BUSINESS_NAME] = $input[Constants::LEGAL_BUSINESS_NAME];
        }

        if (isset($input[Constants::PROFILE]) === true)
        {
            // fill profile data
            $profileAttributesMapping = [
                Constants::DESCRIPTION    => Detail\Entity::BUSINESS_DESCRIPTION,
                Constants::BUSINESS_MODEL => Detail\Entity::BUSINESS_PAYMENTDETAILS,
            ];

            foreach ($profileAttributesMapping as $key => $value)
            {
                if (array_key_exists($key, $input[Constants::PROFILE]) === true)
                {
                    $detailInput[$value] = $input[Constants::PROFILE][$key];

                    if ($key === Constants::BUSINESS_MODEL)
                    {
                        $detailInput[Detail\Entity::BUSINESS_MODEL] = $input[Constants::PROFILE][$key];
                    }
                }
            }

            $categoryData = [];

            if (isset($input[Constants::PROFILE][Constants::CATEGORY]) === true and isset($input[Constants::PROFILE][Constants::SUBCATEGORY]) === true)
            {
                $category = $input[Constants::PROFILE][Constants::CATEGORY];

                $subCategory = $input[Constants::PROFILE][Constants::SUBCATEGORY];

                $subCategoryMetaData = Detail\BusinessSubCategoryMetaData::getSubCategoryMetaData($category, $subCategory);

                if ($subCategoryMetaData[Entity::CATEGORY2] === $category)
                {
                    $mcc = $subCategoryMetaData[Entity::CATEGORY];

                    $categoryData = [
                        DE::BUSINESS_CATEGORY    => $category,
                        DE::BUSINESS_SUBCATEGORY => $subCategory,
                    ];
                }
                else
                {
                    throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_INVALID_MCC_CODE,
                                                  [Constants::SUBCATEGORY => $subCategory, Constants::CATEGORY => $category]);
                }
            }

            $detailInput = array_merge($detailInput, $categoryData);
        }

        $ownerInfo = [];

        if (isset($input[Constants::LEGAL_INFO]) === true)
        {
            $ownerInfo[Detail\Entity::COMPANY_PAN] = $input[Constants::LEGAL_INFO][Constants::PAN];
            $ownerInfo[Detail\Entity::GSTIN]       = $input[Constants::LEGAL_INFO][Constants::GST];
        }

        if (isset($input[Constants::APPS][Constants::WEBSITES]) === true)
        {
            $websites = $input[Constants::APPS][Constants::WEBSITES];

            if (count($websites) > 1)
            {
                $businessWebsite = $websites[0];

                $additionalWebsites = array_slice($websites, 1);

                $detailInput[Detail\Entity::ADDITIONAL_WEBSITES] = $additionalWebsites;

                $detailInput[Detail\Entity::BUSINESS_WEBSITE] = $businessWebsite;
            }
            else
            {
                if (count($websites) == 1)
                {
                    $detailInput[Detail\Entity::BUSINESS_WEBSITE] = $websites[0];
                }
            }
        }

        $detailInput = array_merge($detailInput, $ownerInfo, ...self::getAddressesFromInput($input));

        $clientApplications = [];

        if (isset($input[Constants::APPS][Constants::ANDROID]) === true)
        {
            $android = $input[Constants::APPS][Constants::ANDROID];

            $clientApplications[Detail\Entity::CLIENT_APPLICATIONS][Constants::ANDROID] = $android;
        }

        if (isset($input[Constants::APPS][Constants::IOS]) === true)
        {
            $ios = $input[Constants::APPS][Constants::IOS];

            $clientApplications[Detail\Entity::CLIENT_APPLICATIONS][Constants::IOS] = $ios;
        }

        if (isset($clientApplications[Detail\Entity::CLIENT_APPLICATIONS]) === true)
        {
            $detailInput[Detail\Entity::CLIENT_APPLICATIONS] = json_encode($clientApplications[Detail\Entity::CLIENT_APPLICATIONS], true);
        }

        return $detailInput;
    }

    private static function getCustomFieldsFromInput(array $input): array
    {
        $customFields = [];

        if (isset($input[Constants::TOS_ACCEPTANCE]) === true)
        {
            $customFields[Constants::TOS_ACCEPTANCE] = $input[Constants::TOS_ACCEPTANCE];
        }

        return $customFields;
    }

    private static function getAddressesFromInput(array $input): array
    {
        $addresses = [];

        if (empty($input[Constants::PROFILE][Constants::ADDRESSES]) === true)
        {
            return $addresses;
        }

        if (isset($input[Constants::PROFILE][Constants::ADDRESSES][Constants::REGISTERED]) === true)
        {
            $mapping = [
                Constants::STREET1     => Detail\Entity::BUSINESS_REGISTERED_ADDRESS,
                Constants::STREET2     => Detail\Entity::BUSINESS_REGISTERED_ADDRESS_L2,
                Constants::CITY        => Detail\Entity::BUSINESS_REGISTERED_CITY,
                Constants::STATE       => Detail\Entity::BUSINESS_REGISTERED_STATE,
                Constants::POSTAL_CODE => Detail\Entity::BUSINESS_REGISTERED_PIN,
                Constants::COUNTRY     => Detail\Entity::BUSINESS_REGISTERED_COUNTRY,
            ];

            $registeredAddress = $input[Constants::PROFILE][Constants::ADDRESSES][Constants::REGISTERED];

            $addresses = self::getAddressMapping($mapping,$registeredAddress, $addresses);
        }

        if (isset($input[Constants::PROFILE][Constants::ADDRESSES][Constants::OPERATION]) === true)
        {
            $mapping = [
                Constants::STREET1     => Detail\Entity::BUSINESS_OPERATION_ADDRESS,
                Constants::STREET2     => Detail\Entity::BUSINESS_OPERATION_ADDRESS_L2,
                Constants::CITY        => Detail\Entity::BUSINESS_OPERATION_CITY,
                Constants::STATE       => Detail\Entity::BUSINESS_OPERATION_STATE,
                Constants::POSTAL_CODE => Detail\Entity::BUSINESS_OPERATION_PIN,
                Constants::COUNTRY     => Detail\Entity::BUSINESS_OPERATION_COUNTRY,
            ];
            $operationAddress = $input[Constants::PROFILE][Constants::ADDRESSES][Constants::OPERATION];

            $addresses = self::getAddressMapping($mapping, $operationAddress, $addresses);
        }

        return $addresses;
    }

    private static function getAddressMapping(array $mapping, array $address, array $addresses): array
    {
        $registeredAddress = [];

        foreach ($mapping as $key => $value)
        {
            if (isset($address[$key]) === true)
            {
                if ($key === Constants::STATE)
                {
                    $stateCode = IndianStates::getStateCode($address[Constants::STATE]);

                    $registeredAddress[$value] = $stateCode;
                }
                else
                {
                    $registeredAddress[$value] = $address[$key];
                }
            }
        }

        $addresses[] = $registeredAddress;

        return $addresses;
    }
}
