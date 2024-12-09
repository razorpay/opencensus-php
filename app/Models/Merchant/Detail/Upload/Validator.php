<?php


namespace RZP\Models\Merchant\Detail\Upload;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Batch\Header;
use RZP\Base\RepositoryManager;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Detail\Entity as MDEntity;
use RZP\Models\Merchant\Detail\Upload\Constants as UConstants;
use Lib\Gstin;
use RZP\Models\Admin\Org;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Entity;
use Razorpay\Trace\Logger as Trace;


class Validator extends Base\Validator
{
    /**
     * Application instance
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    protected $orgId;

    const HTTPS_RULE = '/^https(.)+$/';
    const RAZORPAY_URL = '/^https?:\/\/(www\.)?razorpay\.[a-z]{2,}(\/.*)?$/i';
    const COMPANY_CIN_REGEX = '/^[ulUL]{1}[0-9]{5}[A-Z|a-z]{2}[0-9]{4}[A-Z|a-z]{3}[0-9]{6}$/';
    const COMPANY_CIN_PRIVATE_LIMITED_REGEX = '/^[ulUL]{1}[0-9]{5}[A-Za-z]{2}[0-9]{4}(?i:FTC|GAT|OPC|PTC|ULT)[0-9]{6}$/';
    const COMPANY_CIN_PUBLIC_LIMITED_REGEX = '/^[ulUL]{1}[0-9]{5}[A-Za-z]{2}[0-9]{4}(?i:FLC|GAP|GOI|NPL|PLC|SGC|ULL)[0-9]{6}$/';
    const COMPANY_LLPIN_REGEX = '/^[A-Z|a-z]{3}-[0-9]{4}$/';
    //Added the New LLPIN regex as per RBI Compliance
    const NEW_COMPANY_LLPIN_REGEX = '/^([A-Za-z]{3}-\d{4}|[Ff]\w{3}-\d{4})$/';
    const PERSONAL_PAN_NUMBER_REGEX = '/^[A-Za-z]{3}[Pp][A-Za-z]{1}\d{4}[A-Za-z]{1}$/';
    const COMPANY_PAN_NUMBER_LETTER_F_REGEX = '/^[A-Za-z]{3}[FfGg][A-Za-z]{1}\d{4}[A-Za-z]{1}$/' ;
    const COMPANY_PAN_NUMBER_LETTERS_ABTG_REGEX = '/^[A-Za-z]{3}[ABTGabtg][A-Za-z]{1}\d{4}[A-Za-z]{1}$/';
    const COMPANY_PAN_NUMBER_LETTERS_ABTGL_REGEX = '/^[A-Za-z]{3}[ABTGLabtgl][A-Za-z]{1}\d{4}[A-Za-z]{1}$/';
    const COMPANY_PAN_NUMBER_LETTER_C_REGEX = '/^[A-Za-z]{3}[CcGg][A-Za-z]{1}\d{4}[A-Za-z]{1}$/' ;
    const COMPANY_PAN_NUMBER_LETTER_H_REGEX = '/^[A-Za-z]{3}[HhGg][A-Za-z]{1}\d{4}[A-Za-z]{1}$/' ;
    const COMPANY_PAN_NUMBER_REGEX  = '/^[A-Za-z]{3}[CcHhFfAaTtBbLlJjGg][A-Za-z]{1}\d{4}[A-Za-z]{1}$/';

    public function __construct($entity = null)
    {
        parent::__construct($entity);

        $this->app = App::getFacadeRoot();

        $this->orgId = $this->app['basicauth']->getOrgId();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

    }

    protected static $uploadMerchantRules = [
        Constants::FORMAT   => 'required|string|max:255',
        Constants::FILE     => 'required|file',
    ];

    protected static array $uploadMiqBatchRules = [
        Header::MIQ_MERCHANT_NAME                    => 'required',
        Header::MIQ_DBA_NAME                         => 'required',
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
        Header::MIQ_ADDRESS                          => 'required|max:255|custom:naValue',
        Header::MIQ_CITY                             => 'required|max:255|custom:naValue',
        Header::MIQ_PIN_CODE                         => 'required|numeric|custom:naValue',
        Header::MIQ_STATE                            => 'required|max:255|custom:naValue',
        Header::MIQ_CONTACT_NUMBER                   => 'required|min:10|max:15|contact_syntax',
        Header::MIQ_CIN                              => 'sometimes',
        Header::MIQ_BUSINESS_TYPE                    => 'required|custom:businessType',
        Header::MIQ_BUSINESS_PAN                     => 'sometimes',
        Header::MIQ_BUSINESS_NAME                    => 'required|max:255',
        Header::MIQ_AUTHORISED_SIGNATORY_PAN         => 'filled|personalPan',
        Header::MIQ_PAN_OWNER_NAME                   => 'required|max:255',
        Header::MIQ_BUSINESS_CATEGORY                => 'required|custom:businessCategory',
        Header::MIQ_SUB_CATEGORY                     => 'required|custom:businessSubCategory',
        Header::MIQ_GSTIN                            => 'sometimes|custom:miqGstin',
        Header::MIQ_BUSINESS_DESCRIPTION             => 'required|max:255',
        Header::MIQ_ESTD_DATE                        => 'required|before:"today"',
        Header::MIQ_FEE_MODEL                        => 'required|custom:feeModel',
        Header::MIQ_UPI_FEE_TYPE                     => 'required|custom:feeType',
        Header::MIQ_UPI_FEE_BEARER                   => 'sometimes',
        Header::MIQ_UPI                              => 'sometimes',
        Header::MIQ_NB_FEE_TYPE                      => 'required|custom:feeType',
        Header::MIQ_NB_FEE_BEARER                    => 'sometimes',
        Header::MIQ_AXIS                             => 'sometimes',
        Header::MIQ_HDFC                             => 'sometimes',
        Header::MIQ_ICICI                            => 'sometimes',
        Header::MIQ_SBI                              => 'sometimes',
        Header::MIQ_YES                              => 'sometimes',
        Header::MIQ_NB_ANY                           => 'sometimes',
        Header::MIQ_WALLETS_FEE_TYPE                 => 'required|custom:feeType',
        Header::MIQ_WALLETS_FEE_BEARER               => 'sometimes',
        Header::MIQ_WALLETS_FREECHARGE               => 'sometimes',
        Header::MIQ_WALLETS_ANY                      => 'sometimes',
        Header::MIQ_DEBIT_CARD_FEE_TYPE              => 'required|custom:feeType',
        Header::MIQ_DEBIT_CARD_FEE_BEARER            => 'sometimes',
        Header::MIQ_DEBIT_CARD_0_2K                  => 'sometimes|nullable|numeric',
        Header::MIQ_DEBIT_CARD_2K_1CR                => 'sometimes|nullable|numeric',
        Header::MIQ_RUPAY_FEE_TYPE                   => 'required|custom:feeType',
        Header::MIQ_RUPAY_FEE_BEARER                 => 'sometimes',
        Header::MIQ_RUPAY_0_2K                       => 'sometimes',
        Header::MIQ_RUPAY_2K_1CR                     => 'sometimes',
        Header::MIQ_CREDIT_CARD_FEE_TYPE             => 'required|custom:feeType',
        Header::MIQ_CREDIT_CARD_FEE_BEARER           => 'sometimes',
        Header::MIQ_CREDIT_CARD_0_2K                 => 'sometimes',
        Header::MIQ_CREDIT_CARD_2K_1CR               => 'sometimes',
        Header::MIQ_INTERNATIONAL                    => 'required|string',
        Header::MIQ_INTL_CARD_FEE_TYPE               => 'required|custom:feeType',
        Header::MIQ_INTL_CARD_FEE_BEARER             => 'sometimes',
        Header::MIQ_INTERNATIONAL_CARD               => 'sometimes',
        Header::MIQ_BUSINESS_FEE_TYPE                => 'required|custom:feeType',
        Header::MIQ_BUSINESS_FEE_BEARER              => 'sometimes',
        Header::MIQ_BUSINESS                         => 'sometimes',
        Header::MIQ_BANK_ACC_NUMBER                  => 'required',
        Header::MIQ_BENEFICIARY_NAME                 => 'required|string|min:4|max:120',
        Header::MIQ_BRANCH_IFSC_CODE                 => 'required|alpha_num|max:11',
        Merchant\Entity::ORG_ID                      => 'required',
        UConstants::IS_DS_MERCHANT                   => 'sometimes',
        UConstants::IS_PERMISSION_ENABLED            => 'sometimes',
        Header::FIELD1                               => 'sometimes|nullable',
        Header::FIELD2                               => 'sometimes|nullable',
        Header::FIELD3                               => 'sometimes|nullable',
        Header::FIELD4                               => 'sometimes|nullable',
        Header::FIELD5                               => 'sometimes|nullable',
        Header::FIELD6                               => 'sometimes|nullable',
        Header::FIELD7                               => 'sometimes|nullable',
        Header::FIELD8                               => 'sometimes|nullable',
        Header::FIELD9                               => 'sometimes|nullable',
        Header::FIELD10                              => 'sometimes|nullable',
        Header::FIELD11                              => 'sometimes|nullable',
        Header::FIELD12                              => 'sometimes|nullable',
        Header::FIELD13                              => 'sometimes|nullable',
        Header::FIELD14                              => 'sometimes|nullable',
        Header::FIELD15                              => 'sometimes|nullable'
    ];

    protected static array $updateMiqMerchantBatchRules = [
        Header::MIQ_MERCHANT_ID                      => 'required',
        Header::MIQ_STATUS                           => 'sometimes',
        Header::MIQ_MERCHANT_NAME_BUSINESS_NAME      => 'sometimes|alpha_space',
        Header::MIQ_DBA_NAME                         => 'sometimes|alpha_space',
        Header::MIQ_WEBSITE                          => 'sometimes|max:255',
        Header::MIQ_WEBSITE_ABOUT_US                 => 'sometimes|max:255',
        Header::MIQ_WEBSITE_TERMS_CONDITIONS         => 'sometimes|max:255',
        Header::MIQ_WEBSITE_CONTACT_US               => 'sometimes|max:255',
        Header::MIQ_WEBSITE_PRIVACY_POLICY           => 'sometimes|max:255',
        Header::MIQ_WEBSITE_PRODUCT_PRICING          => 'sometimes|max:255',
        Header::MIQ_WEBSITE_REFUNDS                  => 'sometimes|max:255',
        Header::MIQ_WEBSITE_CANCELLATION             => 'sometimes|max:255',
        Header::MIQ_WEBSITE_SHIPPING_DELIVERY        => 'sometimes|max:255',
        Header::MIQ_CONTACT_NAME                     => 'sometimes|alpha_space|max:255',
        Header::MIQ_CONTACT_EMAIL                    => 'sometimes|naEmail|max:255',
        Header::MIQ_TXN_REPORT_EMAIL                 => 'sometimes|naEmail|max:255',
        Header::MIQ_ADDRESS                          => 'sometimes|max:255',
        Header::MIQ_CITY                             => 'sometimes|alpha_space_num|max:255',
        Header::MIQ_PIN_CODE                         => 'sometimes|alpha_space_num|max:255',
        Header::MIQ_STATE                            => 'sometimes|alpha_space',
        Header::MIQ_CONTACT_NUMBER                   => 'sometimes|max:15|naContact',
        Header::MIQ_CIN                              => 'sometimes|companyCin',
        Header::MIQ_BUSINESS_TYPE                    => 'sometimes|custom:businessType',
        Header::MIQ_BUSINESS_PAN                     => 'sometimes|companyPan',
        Header::MIQ_BUSINESS_NAME                    => 'sometimes|max:255',
        Header::MIQ_AUTHORISED_SIGNATORY_PAN         => 'sometimes|personalPan',
        Header::MIQ_PAN_OWNER_NAME                   => 'sometimes|max:255',
        Header::MIQ_BUSINESS_CATEGORY                => 'sometimes|custom:businessCategory',
        Header::MIQ_SUB_CATEGORY                     => 'sometimes|custom:businessSubCategory',
        Header::MIQ_GSTIN                            => 'sometimes',
        Header::MIQ_BUSINESS_DESCRIPTION             => 'sometimes|max:255',
        Header::MIQ_ESTD_DATE                        => 'sometimes|before:"today"',
        Header::MIQ_FEE_MODEL                        => 'sometimes|custom:feeModel',
        Header::FIELD1                               => 'sometimes|nullable',
        Header::FIELD2                              => 'sometimes|nullable',
        Header::FIELD3                               => 'sometimes|nullable',
        Header::FIELD4                               => 'sometimes|nullable',
        Header::FIELD5                               => 'sometimes|nullable',
        Header::FIELD6                               => 'sometimes|nullable',
        Header::FIELD7                               => 'sometimes|nullable',
        Header::FIELD8                               => 'sometimes|nullable',
        Header::FIELD9                               => 'sometimes|nullable',
        Header::FIELD10                               => 'sometimes|nullable',
        Header::FIELD11                              => 'sometimes|nullable',
        Header::FIELD12                              => 'sometimes|nullable',
        Header::FIELD13                               => 'sometimes|nullable',
        Header::FIELD14                               => 'sometimes|nullable',
        Header::FIELD15                               => 'sometimes|nullable'
    ];

    protected static array $updateMiqPricingBatchRules = [
        Header::MIQ_MERCHANT_ID                      => 'required',
        Header::MIQ_UPI_FEE_TYPE                     => 'sometimes|custom:feeType',
        Header::MIQ_UPI_FEE_BEARER                   => 'sometimes|nullable',
        Header::MIQ_UPI                              => 'sometimes|nullable',
        Header::MIQ_NB_FEE_TYPE                      => 'sometimes|custom:feeType',
        Header::MIQ_NB_FEE_BEARER                    => 'sometimes|nullable',
        Header::MIQ_AXIS                             => 'sometimes|nullable',
        Header::MIQ_HDFC                             => 'sometimes|nullable',
        Header::MIQ_ICICI                            => 'sometimes|nullable',
        Header::MIQ_SBI                              => 'sometimes|nullable',
        Header::MIQ_YES                              => 'sometimes|nullable',
        Header::MIQ_NB_ANY                           => 'sometimes|nullable',
        Header::MIQ_WALLETS_FEE_TYPE                 => 'sometimes|custom:feeType',
        Header::MIQ_WALLETS_FEE_BEARER               => 'sometimes|nullable',
        Header::MIQ_WALLETS_FREECHARGE               => 'sometimes|nullable',
        Header::MIQ_WALLETS_ANY                      => 'sometimes|nullable',
        Header::MIQ_DEBIT_CARD_FEE_TYPE              => 'sometimes|custom:feeType',
        Header::MIQ_DEBIT_CARD_FEE_BEARER            => 'sometimes|nullable',
        Header::MIQ_DEBIT_CARD_0_2K                  => 'sometimes|nullable',
        Header::MIQ_DEBIT_CARD_2K_1CR                => 'sometimes|nullable',
        Header::MIQ_RUPAY_FEE_TYPE                   => 'sometimes|custom:feeType',
        Header::MIQ_RUPAY_FEE_BEARER                 => 'sometimes|nullable',
        Header::MIQ_RUPAY_0_2K                       => 'sometimes|nullable',
        Header::MIQ_RUPAY_2K_1CR                     => 'sometimes|nullable',
        Header::MIQ_CREDIT_CARD_FEE_TYPE             => 'sometimes|custom:feeType',
        Header::MIQ_CREDIT_CARD_FEE_BEARER           => 'sometimes|nullable',
        Header::MIQ_CREDIT_CARD_0_2K                 => 'sometimes|nullable',
        Header::MIQ_CREDIT_CARD_2K_1CR               => 'sometimes|nullable',
        Header::MIQ_INTERNATIONAL                    => 'sometimes|string',
        Header::MIQ_INTL_CARD_FEE_TYPE               => 'sometimes|custom:feeType',
        Header::MIQ_INTL_CARD_FEE_BEARER             => 'sometimes|nullable',
        Header::MIQ_INTERNATIONAL_CARD               => 'sometimes|nullable',
        Header::MIQ_BUSINESS_FEE_TYPE                => 'sometimes|custom:feeType',
        Header::MIQ_BUSINESS_FEE_BEARER              => 'sometimes|nullable',
        Header::MIQ_BUSINESS                         => 'sometimes|nullable',
        Header::MIQ_BANK_ACC_NUMBER                  => 'sometimes',
        Header::MIQ_BENEFICIARY_NAME                 => 'sometimes|string',
        Header::MIQ_BRANCH_IFSC_CODE                 => 'sometimes|alpha_num|max:11',
    ];

    protected static array $additionalMerchantFieldsBatchRules = [
        Header::MERCHANT_ID                      => 'required',
        Header::ORG_ID                           => 'required',
        Header::FIELD1                           => 'sometimes|nullable',
        Header::FIELD2                           => 'sometimes|nullable',
        Header::FIELD3                           => 'sometimes|nullable',
        Header::FIELD4                           => 'sometimes|nullable',
        Header::FIELD5                           => 'sometimes|nullable',
        Header::FIELD6                           => 'sometimes|nullable',
        Header::FIELD7                           => 'sometimes|nullable',
        Header::FIELD8                           => 'sometimes|nullable',
        Header::FIELD9                           => 'sometimes|nullable',
        Header::FIELD10                          => 'sometimes|nullable',
        Header::FIELD11                          => 'sometimes|nullable',
        Header::FIELD12                          => 'sometimes|nullable',
        Header::FIELD13                          => 'sometimes|nullable',
        Header::FIELD14                          => 'sometimes|nullable',
        Header::FIELD15                          => 'sometimes|nullable'
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

        try
        {
            if($this->orgId !== null)
            {
                $this->orgId = Org\Entity::verifyIdAndSilentlyStripSign($this->orgId);

                $this->org = $this->repo->org->findOrFailPublic($this->orgId);

            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);
        }

        if(empty($entry[Header::MIQ_ADDRESS]) === true || strtolower($entry[Header::MIQ_ADDRESS]) === "na")
        {
            throw new BadRequestValidationFailureException("The ".Header::MIQ_ADDRESS. " is required");
        }

        $businessType = strtolower($entry[Header::MIQ_BUSINESS_TYPE]);

        if($businessType === Merchant\Detail\BusinessType::PUBLIC_LIMITED)
        {
            if(empty($entry[Header::MIQ_CIN]) === true){
                throw new BadRequestValidationFailureException("The ".Header::MIQ_CIN. " is required");
            }else if((preg_match(self::COMPANY_CIN_REGEX, $entry[Header::MIQ_CIN]) === 0)){
                 throw new BadRequestValidationFailureException("The ".Header::MIQ_CIN. " is invalid");
            }
        }else if($businessType === Merchant\Detail\BusinessType::LLP)
        {
            if(empty($entry[Header::MIQ_CIN]) === true)
            {
                throw new BadRequestValidationFailureException("The ".Header::MIQ_CIN. " is required");
            }
            else
            {
                if($this->org !== null and $this->org->isFeatureEnabled(Feature\Constants::VAS_KYC_RBI) === true)
                {
                    if(preg_match(self::NEW_COMPANY_LLPIN_REGEX, $entry[Header::MIQ_CIN]) === 0)
                    {
                        throw new BadRequestValidationFailureException("The ".Header::MIQ_CIN. " is invalid for ". $businessType);
                    }
                }
                else if((preg_match(self::COMPANY_LLPIN_REGEX, $entry[Header::MIQ_CIN]) === 0))
                {
                    throw new BadRequestValidationFailureException("The ".Header::MIQ_CIN. " is invalid");
                }
             }
        }

         $businessTypesRequiringBusinessPan = [
                Merchant\Detail\BusinessType::LLP, Merchant\Detail\BusinessType::NGO,
                Merchant\Detail\BusinessType::SOCIETY,Merchant\Detail\BusinessType::HUF,
                Merchant\Detail\BusinessType::PARTNERSHIP, Merchant\Detail\BusinessType::TRUST,
                Merchant\Detail\BusinessType::PUBLIC_LIMITED, Merchant\Detail\BusinessType::PRIVATE_LIMITED,
            ];

        if(in_array($businessType, $businessTypesRequiringBusinessPan) === true){
            if(empty($entry[Header::MIQ_BUSINESS_PAN]) === true){
                 throw new BadRequestValidationFailureException("The ".Header::MIQ_BUSINESS_PAN. " is required");
            }else if(preg_match(self::COMPANY_PAN_NUMBER_REGEX, $entry[Header::MIQ_BUSINESS_PAN]) === 0){
                 throw new BadRequestValidationFailureException("The ".Header::MIQ_BUSINESS_PAN. " is invalid");
            }
         }

         $businessTypesRequiringPersonalPan = [
                Merchant\Detail\BusinessType::NOT_YET_REGISTERED, Merchant\Detail\BusinessType::PROPRIETORSHIP,
            ];

        if($this->org === null or $this->org->isFeatureEnabled(Feature\Constants::VAS_KYC_RBI) === false)
        {
            if(in_array($businessType, $businessTypesRequiringPersonalPan) === true)
            {
                if(empty($entry[Header::MIQ_BUSINESS_PAN]) === false)
                {
                    if(preg_match(self::PERSONAL_PAN_NUMBER_REGEX, $entry[Header::MIQ_BUSINESS_PAN]) === 0){

                        throw new BadRequestValidationFailureException("The ".Header::MIQ_BUSINESS_PAN. " is invalid for ".$businessType);
                    }
                }
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

        $validFeeTypes = [
            Header::MIQ_UPI_FEE_TYPE => Header::MIQ_UPI_FEE_BEARER,
            Header::MIQ_NB_FEE_TYPE => Header::MIQ_NB_FEE_BEARER,
            Header::MIQ_WALLETS_FEE_TYPE => Header::MIQ_WALLETS_FEE_BEARER,
            Header::MIQ_DEBIT_CARD_FEE_TYPE => Header::MIQ_DEBIT_CARD_FEE_BEARER,
            Header::MIQ_RUPAY_FEE_TYPE => Header::MIQ_RUPAY_FEE_BEARER,
            Header::MIQ_CREDIT_CARD_FEE_TYPE => Header::MIQ_CREDIT_CARD_FEE_BEARER,
            Header::MIQ_INTL_CARD_FEE_TYPE => Header::MIQ_INTL_CARD_FEE_BEARER,
            Header::MIQ_BUSINESS_FEE_TYPE => Header::MIQ_BUSINESS_FEE_BEARER
        ];

        foreach ($validFeeTypes as $feeType => $feeBearer)
        {
            if(strtolower($entry[$feeType]) !== UConstants::FEE_TYPE_NA)
            {
                $validTypes = [
                    Merchant\FeeBearer::PLATFORM,
                    Merchant\FeeBearer::CUSTOMER
                ];

                if (!in_array(strtolower($entry[$feeBearer]), $validTypes, true))
                {
                    throw new BadRequestValidationFailureException('Invalid ' . $feeBearer);
                }

                // net banking validations
                if($feeType === Header::MIQ_NB_FEE_TYPE){
                    $netBankingTypes = [
                        Header::MIQ_AXIS,
                        Header::MIQ_HDFC,
                        Header::MIQ_ICICI,
                        Header::MIQ_SBI,
                        Header::MIQ_YES,
                        Header::MIQ_NB_ANY,
                    ];

                    foreach ($netBankingTypes as $index => $value){
                        if(empty($entry[$value]) === true)
                        {
                                throw new BadRequestValidationFailureException("The ".$value. " is required");
                        }
                    }
                }

                // debit card validations
                if($feeType === Header::MIQ_DEBIT_CARD_FEE_TYPE)
                {
                    $cardTypes = [
                        Header::MIQ_DEBIT_CARD_0_2K,
                        Header::MIQ_DEBIT_CARD_2K_1CR
                      ];

                    foreach ($cardTypes as $index => $value){
                        if(empty($entry[$value]) === true)
                        {
                                throw new BadRequestValidationFailureException("The ".$value. " is required");
                        }
                    }
                }

                // rupey validations
                if($feeType === Header::MIQ_RUPAY_FEE_TYPE)
                {
                    $cardTypes = [
                        Header::MIQ_RUPAY_0_2K,
                        Header::MIQ_RUPAY_2K_1CR
                      ];

                    foreach ($cardTypes as $index => $value){
                        if(empty($entry[$value]) === true)
                        {
                            throw new BadRequestValidationFailureException("The ".$value. " is required");
                        }
                    }
                }

                // UPI validations
                if($feeType === Header::MIQ_UPI_FEE_TYPE)
                {
                    if(empty($entry[Header::MIQ_UPI]) === true)
                    {
                        throw new BadRequestValidationFailureException("The ".Header::MIQ_UPI. " is required");
                    }
                }

                // Wallets validations
                if($feeType === Header::MIQ_WALLETS_FEE_TYPE)
                {
                    $walletTypes = [
                        Header::MIQ_WALLETS_FREECHARGE,
                        Header::MIQ_WALLETS_ANY
                    ];

                    foreach ($walletTypes as $index => $value){
                        if(empty($entry[$value]) === true)
                        {
                            throw new BadRequestValidationFailureException("The ".$value. " is required");
                        }
                    }
                }

                // Credit card validations
                if($feeType === Header::MIQ_CREDIT_CARD_FEE_TYPE)
                {
                    $creditCardTypes = [
                        Header::MIQ_CREDIT_CARD_0_2K,
                        Header::MIQ_CREDIT_CARD_2K_1CR
                    ];

                    foreach ($creditCardTypes as $index => $value){
                        if(empty($entry[$value]) === true)
                        {
                            throw new BadRequestValidationFailureException("The ".$value. " is required");
                        }
                    }
                }

                // International Cards validations
                if($feeType === Header::MIQ_INTL_CARD_FEE_TYPE && strtolower($entry[Header::MIQ_INTERNATIONAL]) !== 'no')
                {
                    if(empty($entry[Header::MIQ_INTERNATIONAL_CARD]) === true)
                    {
                        throw new BadRequestValidationFailureException("The ".Header::MIQ_INTERNATIONAL_CARD. " is required");
                    }
                }

                // Business validations
                if($feeType === Header::MIQ_BUSINESS_FEE_TYPE)
                {
                    if(empty($entry[Header::MIQ_BUSINESS]) === true)
                    {
                        throw new BadRequestValidationFailureException("The ".Header::MIQ_BUSINESS. " is required");
                    }
                }

            }
        }

        (new Detail\Validator)->validateMerchantFieldsForBankingCompliance($entry);
    }


    protected function validateMiqGstin($attribute, $value)
    {
        if(empty($value) === false){
            $isValidGstin = Gstin::isValid($value);

            if ($isValidGstin === false)
            {
                throw new BadRequestValidationFailureException('Invalid ' . $attribute);
            }
        }
    }

    public function validateUpdateRequestInput(array $entry): void
    {
        (new Validator)->validateInput('updateMiqMerchantBatch', $entry);

        if(empty($entry[Header::MIQ_MERCHANT_ID]))
        {
            throw new BadRequestValidationFailureException("The ".Header::MIQ_MERCHANT_ID." is required");
        }
    }

    public function validateUpdatePricingRequestInput(array $entry): void
    {
        (new Validator)->validateInput('updateMiqPricingBatch', $entry);

        if(empty($entry[Header::MIQ_MERCHANT_ID]))
        {
            throw new BadRequestValidationFailureException("The ".Header::MIQ_MERCHANT_ID." is required");
        }
    }

    public function validateAdditionalMerchantFieldsRequestInput(array $entry): void
    {
        (new Validator)->validateInput('additionalMerchantFieldsBatch', $entry);

        if(empty($entry[Header::MERCHANT_ID]))
        {
            throw new BadRequestValidationFailureException("The ".Header::MERCHANT_ID." is required");
        }

        if(empty($entry[Header::ORG_ID]))
        {
            throw new BadRequestValidationFailureException("The ".Header::ORG_ID." is required");
        }
    }


    /**
     * Validate the fee bearer
     * Validate the NA/na
     * @param $attribute
     * @param $value
     * @return void
     * @throws BadRequestValidationFailureException
     */
    protected function validateNaValue($attribute, $value): void
    {
        $route  = $this->app['api.route']->getCurrentRouteName();

        if(strtolower($value) === 'na' and $route === 'pricing_update_miq') // only for our flow
        {
            return;
        }

        $validTypes = [
            Merchant\FeeBearer::PLATFORM,
            Merchant\FeeBearer::CUSTOMER
        ];

        $validHeader = [
            Header::MIQ_ADDRESS,Header::MIQ_CITY,Header::MIQ_PIN_CODE,Header::MIQ_STATE,
        ];

        if (in_array($attribute, $validHeader, true) === true)
        {
            if ((strtolower($value) === 'na'))
            {
                throw new BadRequestValidationFailureException('Invalid ' . $attribute);
            }
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
        $route  = $this->app['api.route']->getCurrentRouteName();

        if(strtolower($value) === 'na' and $route === 'pricing_update_miq') // only for our flow
        {
            return;
        }

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
        $route  = $this->app['api.route']->getCurrentRouteName();

        if(strtolower($value) === 'na' and $route === 'merchant_update_miq') // only for our flow
        {
            return;
        }

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
        $route  = $this->app['api.route']->getCurrentRouteName();

        if(strtolower($value) === 'na' and $route === 'merchant_update_miq') // only for our flow
        {
            return;
        }

        if(preg_match(self::HTTPS_RULE, $value) === 0)
        {
            throw new BadRequestValidationFailureException('Invalid ' . $attribute);
        }

        try
        {
            if($this->orgId !== null)
            {
                $this->orgId = Org\Entity::verifyIdAndSilentlyStripSign($this->orgId);

                $org = $this->repo->org->findOrFailPublic($this->orgId);

                if($org !== null and $org->isFeatureEnabled(Feature\Constants::VAS_KYC_RBI) === true)
                {
                    // Check if the Website is Razorpay URL
                    if (preg_match(self::RAZORPAY_URL, $value) === 1)
                    {
                        throw new BadRequestValidationFailureException('Invalid ' . $attribute . " : ". $value);
                    }

                    // Check if the URL is active
                    $this->validateActiveUrl($attribute, $value);

                    $this->trace->info(TraceCode::MERCHANT_VALIDATE, [
                        'attribute_name'   => $attribute,
                        'attribute_value' => $value,
                    ]);
                }
            }
        }
        catch (BadRequestValidationFailureException $ex)
        {
            $this->trace->traceException($ex);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                null,$ex,$ex->getMessage()
            );
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);
        }
    }

    protected function validateWebsiteDetails($attribute, $value): void
    {
        $route  = $this->app['api.route']->getCurrentRouteName();

        if(strtolower($value) === 'na' and $route === 'merchant_update_miq') // only for our flow
        {
            return;
        }

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
        $route  = $this->app['api.route']->getCurrentRouteName();

        if(strtolower($value) === 'na' and $route === 'merchant_update_miq') // only for our flow
        {
            return;
        }

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
        $route  = $this->app['api.route']->getCurrentRouteName();

        if(strtolower($value) === 'na' and $route === 'merchant_update_miq') // only for our flow
        {
            return;
        }

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
        $route  = $this->app['api.route']->getCurrentRouteName();

        if(strtolower($value) === 'na' and $route === 'merchant_update_miq') // only for our flow
        {
            return;
        }
        if(!Merchant\Detail\BusinessCategoriesV2\BusinessSubcategory::isValidSubcategory($value))
        {
            throw new BadRequestValidationFailureException('Invalid ' . $attribute);
        }
    }

    public function validateOrgDefinedMerchantFieldsInput(array $entry, $orgId)
    {
        $additionalFields = [
            Header::FIELD1 => $entry[Header::FIELD1],
            Header::FIELD2 => $entry[Header::FIELD2],
            Header::FIELD3 => $entry[Header::FIELD3],
            Header::FIELD4 => $entry[Header::FIELD4],
            Header::FIELD5 => $entry[Header::FIELD5],
            Header::FIELD6 => $entry[Header::FIELD6],
            Header::FIELD7 => $entry[Header::FIELD7],
            Header::FIELD8 => $entry[Header::FIELD8],
            Header::FIELD9 => $entry[Header::FIELD9],
            Header::FIELD10 => $entry[Header::FIELD10],
            Header::FIELD11 => $entry[Header::FIELD11],
            Header::FIELD12 => $entry[Header::FIELD12],
            Header::FIELD13 => $entry[Header::FIELD13],
            Header::FIELD14 => $entry[Header::FIELD14],
            Header::FIELD15 => $entry[Header::FIELD15]
        ];

        $orgCustomConfig = (new Org\Service)->getOrgCustomConfig();
        $orgCustomConfig = array_key_exists($orgId, $orgCustomConfig) ? $orgCustomConfig[$orgId] : null;

        if($orgCustomConfig === null)
        {
            return [Header::ERROR_CODE => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
                Header::ERROR_DESCRIPTION => "Org Defined Merchant fields are not configured for {$orgId}"];
        }

        // Create a lookup array from $orgCustomConfig for quick access
        $lookup = [];
        foreach ($orgCustomConfig as $field)
        {
            $lookup[strtolower($field['id'])] = $field;
        }

        // Define regex patterns for types
        $patterns = [
            'alphaNumeric' => '/^[a-zA-Z0-9]+$/',
            'number' => '/^[0-9]+$/',
            'alphabet' => '/^[a-zA-Z]+$/',
            'amount' => '/^\d+(\.\d{1,2})?$/',
            'date' => '/^\d{4}-\d{2}-\d{2}$/', // Format: YYYY-MM-DD
            'string' => '/^[a-zA-Z0-9\s\W]+$/', // Allows alphanumeric, spaces, and special characters
            'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'bool' => '/^(true|false)$/i'
        ];

        $errorDescriptionCombined = '';
        $errorCodeCombined = '';
        foreach ($additionalFields as $key => $value)
        {
            $fieldKey = strtolower($key);
            $currErrorCode = '';
            $currErrorDescription = '';
            if (empty($value) or strtolower($value) === 'na')
            {
                //empty values allowed
                continue;
            }
            else if (isset($lookup[$fieldKey]))
            {
                $type = $lookup[$fieldKey]['type'];

                // Check if the type is valid and matches the value
                if (isset($patterns[$type]) && preg_match($patterns[$type], $value))
                {
                    continue; // Value matches the type
                }
                else
                {
                    $currErrorCode = $currErrorCode . ', ' . ErrorCode::BAD_REQUEST_VALIDATION_FAILURE;
                    $currErrorDescription = $currErrorDescription . ', ' . "Value type doesn't match for {$key}";
                }
            }
            else
            {
                $currErrorCode = $currErrorCode . ', ' . ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED;
                $currErrorDescription = $currErrorDescription . ', ' . "{$key} is not defined in org custom config";
            }
            $errorCodeCombined = $errorCodeCombined . ',' . $currErrorCode;
            $errorDescriptionCombined = $errorDescriptionCombined . ',' . $currErrorDescription;
        }
        return [Header::ERROR_CODE => $errorCodeCombined, Header::ERROR_DESCRIPTION => $errorDescriptionCombined];
    }
}
