<?php


namespace RZP\Models\Merchant\Detail\Upload;

use App;

use RZP\Base;
use RZP\Models\Merchant;
use RZP\Models\Batch\Header;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Detail\Upload\Constants as UConstants;


class Validator extends Base\Validator
{
    const HTTPS_RULE = '/^https(.)+$/';

    public function __construct($entity = null)
    {
        parent::__construct($entity);

        $app = App::getFacadeRoot();
    }

    protected static $uploadMerchantRules = [
        Constants::FORMAT   => 'required|string|max:255',
        Constants::FILE     => 'required|file',
    ];

    protected static array $uploadMiqBatchRules = [
        Header::MIQ_MERCHANT_NAME                    => 'required|regex:/^[a-zA-Z$@]+( [a-zA-Z$@]+)+?$/|max:255',
        Header::MIQ_DBA_NAME                         => 'required|regex:/^[a-zA-Z$@]+( [a-zA-Z$@]+)+?$/|max:255',
        Header::MIQ_WEBSITE                          => 'sometimes|nullable|max:255|custom:website',
        Header::MIQ_WEBSITE_ABOUT_US                 => 'required_with:'.Header::MIQ_WEBSITE.'|max:255|custom:website',
        Header::MIQ_WEBSITE_TERMS_CONDITIONS         => 'required_with:'.Header::MIQ_WEBSITE.'|max:255|custom:website',
        Header::MIQ_WEBSITE_CONTACT_US               => 'required_with:'.Header::MIQ_WEBSITE.'|max:255|custom:website',
        Header::MIQ_WEBSITE_PRIVACY_POLICY           => 'required_with:'.Header::MIQ_WEBSITE.'|max:255|custom:website',
        Header::MIQ_WEBSITE_PRODUCT_PRICING          => 'required_with:'.Header::MIQ_WEBSITE.'|max:255|custom:website',
        Header::MIQ_WEBSITE_REFUNDS                  => 'required_with:'.Header::MIQ_WEBSITE.'|max:255|custom:website',
        Header::MIQ_WEBSITE_CANCELLATION             => 'required_with:'.Header::MIQ_WEBSITE.'|max:255|custom:website',
        Header::MIQ_WEBSITE_SHIPPING_DELIVERY        => 'required_with:'.Header::MIQ_WEBSITE.'|max:255|custom:website',
        Header::MIQ_CONTACT_NAME                     => 'required|alpha_space|max:255',
        Header::MIQ_CONTACT_EMAIL                    => 'required|email|max:255',
        Header::MIQ_TXN_REPORT_EMAIL                 => 'required|email|max:255',
        Header::MIQ_ADDRESS                          => 'required|max:255',
        Header::MIQ_CITY                             => 'required|alpha_space_num|max:255',
        Header::MIQ_PIN_CODE                         => 'required|alpha_space_num|max:255',
        Header::MIQ_STATE                            => 'required',
        Header::MIQ_CONTACT_NUMBER                   => 'required|min:10|max:15|contact_syntax',
        Header::MIQ_CIN                              => 'filled|companyCin',
        Header::MIQ_BUSINESS_TYPE                    => 'required|custom:businessType',
        Header::MIQ_BUSINESS_PAN                     => 'required|companyPan',
        Header::MIQ_BUSINESS_NAME                    => 'required|max:255',
        Header::MIQ_AUTHORISED_SIGNATORY_PAN         => 'filled|personalPan',
        Header::MIQ_PAN_OWNER_NAME                   => 'required|max:255',
        Header::MIQ_BUSINESS_CATEGORY                => 'required|custom:businessCategory',
        Header::MIQ_SUB_CATEGORY                     => 'required|custom:businessSubCategory',
        Header::MIQ_GSTIN                            => 'sometimes',
        Header::MIQ_BUSINESS_DESCRIPTION             => 'required|max:255',
        Header::MIQ_ESTD_DATE                        => 'required|before:"today"',
        Header::MIQ_FEE_MODEL                        => 'required|custom:feeModel',
        Header::MIQ_UPI_FEE_TYPE                     => 'required|custom:feeType',
        Header::MIQ_UPI_FEE_BEARER                   => 'sometimes|nullable|custom:feeBearer',
        Header::MIQ_UPI                              => 'sometimes|nullable|numeric',
        Header::MIQ_NB_FEE_TYPE                      => 'required|custom:feeType',
        Header::MIQ_NB_FEE_BEARER                    => 'sometimes|nullable|custom:feeBearer',
        Header::MIQ_AXIS                             => 'sometimes|nullable|numeric',
        Header::MIQ_HDFC                             => 'sometimes|nullable|numeric',
        Header::MIQ_ICICI                            => 'sometimes|nullable|numeric',
        Header::MIQ_SBI                              => 'sometimes|nullable|numeric',
        Header::MIQ_YES                              => 'sometimes|nullable|numeric',
        Header::MIQ_NB_ANY                           => 'sometimes|nullable|numeric',
        Header::MIQ_WALLETS_FEE_TYPE                 => 'required|custom:feeType',
        Header::MIQ_WALLETS_FEE_BEARER               => 'sometimes|nullable|custom:feeBearer',
        Header::MIQ_WALLETS_FREECHARGE               => 'sometimes|nullable|numeric',
        Header::MIQ_WALLETS_ANY                      => 'sometimes|nullable|numeric',
        Header::MIQ_DEBIT_CARD_FEE_TYPE              => 'required|custom:feeType',
        Header::MIQ_DEBIT_CARD_FEE_BEARER            => 'sometimes|nullable|custom:feeBearer',
        Header::MIQ_DEBIT_CARD_0_2K                  => 'sometimes|nullable|numeric',
        Header::MIQ_DEBIT_CARD_2K_1CR                => 'sometimes|nullable|numeric',
        Header::MIQ_RUPAY_FEE_TYPE                   => 'required|custom:feeType',
        Header::MIQ_RUPAY_FEE_BEARER                 => 'sometimes|nullable|custom:feeBearer',
        Header::MIQ_RUPAY_0_2K                       => 'sometimes|nullable|numeric',
        Header::MIQ_RUPAY_2K_1CR                     => 'sometimes|nullable|numeric',
        Header::MIQ_CREDIT_CARD_FEE_TYPE             => 'required|custom:feeType',
        Header::MIQ_CREDIT_CARD_FEE_BEARER           => 'sometimes|nullable|custom:feeBearer',
        Header::MIQ_CREDIT_CARD_0_2K                 => 'sometimes|nullable|numeric',
        Header::MIQ_CREDIT_CARD_2K_1CR               => 'sometimes|nullable|numeric',
        Header::MIQ_INTERNATIONAL                    => 'required|string',
        Header::MIQ_INTL_CARD_FEE_TYPE               => 'required|custom:feeType',
        Header::MIQ_INTL_CARD_FEE_BEARER             => 'sometimes|nullable|custom:feeBearer',
        Header::MIQ_INTERNATIONAL_CARD               => 'sometimes|nullable|numeric',
        Header::MIQ_BUSINESS_FEE_TYPE                => 'required|custom:feeType',
        Header::MIQ_BUSINESS_FEE_BEARER              => 'sometimes|nullable|custom:feeBearer',
        Header::MIQ_BUSINESS                         => 'sometimes|nullable|numeric',
        Header::MIQ_BANK_ACC_NUMBER                  => 'required',
        Header::MIQ_BENEFICIARY_NAME                 => 'required|string|min:4|max:120',
        Header::MIQ_BRANCH_IFSC_CODE                 => 'required|alpha_num|max:11',
        Merchant\Entity::ORG_ID                      => 'required',
    ];

    /**
     * Validate the request input for merchant and pricing creation.
     *
     * @param array $entry
     * @return void
     * @throws BadRequestValidationFailureException
     */
    public function validateRequestInput(array $entry): void
    {
        (new Validator)->validateInput('uploadMiqBatch', $entry);

        if(empty($entry[Header::MIQ_ADDRESS]) === true || strtolower($entry[Header::MIQ_ADDRESS]) === "na")
        {
            throw new BadRequestValidationFailureException("The ".Header::MIQ_ADDRESS. " is required");
        }

        $businessType = strtolower($entry[Header::MIQ_BUSINESS_TYPE]);

        if($businessType === Merchant\Detail\BusinessType::PUBLIC_LIMITED && empty($entry[Header::MIQ_CIN]) === true)
        {
            throw new BadRequestValidationFailureException("The ".Header::MIQ_CIN. " is required");
        }

        if(empty($entry[Header::MIQ_BUSINESS_PAN]) === true)
        {
            $businessTypesRequiringPan = [
                Merchant\Detail\BusinessType::LLP, Merchant\Detail\BusinessType::NGO,
                Merchant\Detail\BusinessType::SOCIETY,Merchant\Detail\BusinessType::HUF,
                Merchant\Detail\BusinessType::PARTNERSHIP, Merchant\Detail\BusinessType::TRUST,
                Merchant\Detail\BusinessType::PUBLIC_LIMITED, Merchant\Detail\BusinessType::PRIVATE_LIMITED,
            ];

            if(in_array($businessType, $businessTypesRequiringPan) === true)
            {
                throw new BadRequestValidationFailureException("The ".Header::MIQ_BUSINESS_PAN. " is required");
            }
        }

        if($businessType !== Merchant\Detail\BusinessType::PROPRIETORSHIP and empty($entry[Header::MIQ_BUSINESS_NAME]))
        {
            throw new BadRequestValidationFailureException("The ".Header::MIQ_BUSINESS_NAME. " is required");
        }

        if($businessType !== Merchant\Detail\BusinessType::NOT_YET_REGISTERED)
        {
            if(empty($entry[Header::MIQ_AUTHORISED_SIGNATORY_PAN]))
            {
                throw new BadRequestValidationFailureException("The ".Header::MIQ_AUTHORISED_SIGNATORY_PAN. " is required");
            }

            if(empty($entry[Header::MIQ_PAN_OWNER_NAME]))
            {
                throw new BadRequestValidationFailureException("The ".Header::MIQ_PAN_OWNER_NAME. " is required");
            }
        }

        if(empty($entry[Header::MIQ_GSTIN]))
        {
            $categoryNotRequiringGSTIN = [
                Merchant\Detail\BusinessCategoriesV2\BusinessCategory::EDUCATION,
                Merchant\Detail\BusinessCategoriesV2\BusinessCategory::GOVERNMENT,
                Merchant\Detail\BusinessCategoriesV2\BusinessCategory::NOT_FOR_PROFIT,
            ];

            if(!in_array(strtolower($entry[Header::MIQ_BUSINESS_CATEGORY]), $categoryNotRequiringGSTIN))
            {
                throw new BadRequestValidationFailureException("The ".Header::MIQ_GSTIN. " is required");
            }
        }
    }

    /**
     * Validate the fee bearer
     *
     * @param $attribute
     * @param $value
     * @return void
     * @throws BadRequestValidationFailureException
     */
    protected function validateFeeBearer($attribute, $value): void
    {
        $validTypes = [
            Merchant\FeeBearer::PLATFORM,
            Merchant\FeeBearer::CUSTOMER
        ];

        if (!in_array(strtolower($value), $validTypes, true))
        {
            throw new BadRequestValidationFailureException('Invalid ' . $attribute);
        }
    }

    /**
     * Validate the fee type
     *
     * @param $attribute
     * @param $value
     * @return void
     * @throws BadRequestValidationFailureException
     */
    protected function validateFeeType($attribute, $value): void
    {
        $validTypes = [
            UConstants::FEE_TYPE_NA,
            UConstants::FEE_TYPE_FLAT,
            UConstants::FEE_TYPE_PERCENT,
        ];

        if (!in_array(strtolower($value), $validTypes, true))
        {
            throw new BadRequestValidationFailureException('Invalid ' . $attribute);
        }
    }

    /**
     * Validate the fee model
     *
     * @param $attribute
     * @param $value
     * @return void
     * @throws BadRequestValidationFailureException
     */
    protected function validateFeeModel($attribute, $value): void
    {
        $validTypes = [
            Merchant\FeeModel::NA,
            Merchant\FeeModel::PREPAID,
            Merchant\FeeModel::POSTPAID,
        ];

        if (!in_array(strtolower($value), $validTypes, true))
        {
            throw new BadRequestValidationFailureException('Invalid ' . $attribute);
        }
    }

    /**
     * Validate the websites
     *
     * @param $attribute
     * @param $value
     * @return void
     * @throws BadRequestValidationFailureException
     */
    protected function validateWebsite($attribute, $value): void
    {
        if(preg_match(self::HTTPS_RULE, $value) === 0)
        {
            throw new BadRequestValidationFailureException('Invalid ' . $attribute);
        }
    }

    /**
     * Validate the business type
     *
     * @param $attribute
     * @param $value
     * @return void
     * @throws BadRequestValidationFailureException
     */
    protected function validateBusinessType($attribute, $value): void
    {
        $validBusinessType = [
            Merchant\Detail\BusinessType::LLP, Merchant\Detail\BusinessType::NGO,
            Merchant\Detail\BusinessType::SOCIETY, Merchant\Detail\BusinessType::HUF,
            Merchant\Detail\BusinessType::PROPRIETORSHIP, Merchant\Detail\BusinessType::PARTNERSHIP,
            Merchant\Detail\BusinessType::PRIVATE_LIMITED, Merchant\Detail\BusinessType::PUBLIC_LIMITED,
            Merchant\Detail\BusinessType::TRUST, Merchant\Detail\BusinessType::NOT_YET_REGISTERED,
        ];

        if (in_array(strtolower($value), $validBusinessType, true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid ' . $attribute);
        }
    }

    /**
     * Validate the business category
     *
     * @param $attribute
     * @param $value
     * @return void
     * @throws BadRequestValidationFailureException
     */
    protected function validateBusinessCategory($attribute, $value): void
    {
        if(!Merchant\Detail\BusinessCategoriesV2\BusinessCategory::isValidCategory($value))
        {
            throw new BadRequestValidationFailureException('Invalid ' . $attribute);
        }
    }

    /**
     * Validate the business sub category
     *
     * @param $attribute
     * @param $value
     * @return void
     * @throws BadRequestValidationFailureException
     */
    protected function validateBusinessSubCategory($attribute, $value): void
    {
        if(!Merchant\Detail\BusinessCategoriesV2\BusinessSubcategory::isValidSubcategory($value))
        {
            throw new BadRequestValidationFailureException('Invalid ' . $attribute);
        }
    }
}
