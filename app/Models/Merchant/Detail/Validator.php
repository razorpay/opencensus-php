<?php

namespace RZP\Models\Merchant\Detail;

use App;

use RZP\Base;
use RZP\Exception;
use Lib\PhoneBook;
use Razorpay\IFSC\IFSC;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Constants\IndianStates;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\RazorxTreatment;
use libphonenumber\NumberParseException;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Models\DeviceDetail\Constants as DDConstants;
use RZP\Models\Merchant\Detail\ActivationFlow\Factory;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\Consent\Constants as ConsentConstant;
use RZP\Models\Merchant\BusinessDetail\Constants as BDConstants;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;

class Validator extends Base\Validator
{
    protected $env;

    protected $app;

    public function __construct($entity = null)
    {
        parent::__construct($entity);

        $this->app = App::getFacadeRoot();

        $this->env = $this->app['env'];
    }

    const INVALID_REVIEWER                              = 'Invalid reviewer';
    const INVALID_MERCHANTS                             = 'Invalid merchants';
    const INVALID_STATUS_MESSAGE                        = 'Invalid status';
    const INVALID_IFSC_CODE_MESSAGE                     = 'Invalid IFSC Code';
    const INVALID_BANK_BRANCH_CODE_MESSAGE              = 'Invalid Bank Branch Code';
    const INVALID_BANK_BRANCH_CODE_TYPE_MESSAGE         = 'Invalid Bank Branch Code Type';
    const INVALID_INDUSTRY_CATEGORY_CODE_TYPE_MESSAGE   = 'Invalid Industry Category Code Type';
    const INVALID_STATUS_CHANGE_MESSAGE                 = 'Invalid status change';
    const INVALID_CLARIFICATION_MODE_MESSAGE            = 'Invalid clarification mode';
    const INVALID_FILE_NON_NGO_ORGANISATION_TYPE        = 'Invalid file for non NGO organisation type';
    const INVALID_CLARIFICATION_MODE_FOR_STATUS_MESSAGE = 'Clarification mode should not be sent for this status';
    const INVALID_ACTIVATION_FORM_MILESTONE_MESSAGE     = 'Invalid Activation Form milestone';
    const INVALID_BUSINESS_CATEGORY                     = 'Invalid business category';
    const INVALID_BUSINESS_SUBCATEGORY                  = 'Invalid business subcategory';
    const INVALID_PREDEFINED_REASON                     = 'Invalid Predefined Reason';
    const INVALID_ADDITIONAL_DETAIL_FIELD               = 'Invalid additional detail field';
    const INVALID_BUSINESS_SUBCATEGORY_FOR_CATEGORY     = 'Invalid business subcategory for business category';
    const INVALID_BUSINESS_CATEGORY_FOR_PARENT_CATEGORY = 'Invalid business category for business parent category';
    const BUSINESS_CATEGORY_MISSING_FOR_SUBCATEGORY     = 'Business category missing for business subcategory';
    const INVALID_REASON_TYPE                           = 'Invalid reason type';
    const BLACKLISTED_BANK_ACCOUNT_NUMBER               = 'Accounts from this Bank are temporarily not supported. Please add another bank a/c or contact support.';
    const ADDITIONAL_FIELD_NOT_REQUIRED                 = 'Not required additional field ';

    // Constant representing operations for which Validation rules exists
    const BULK_EDIT                                     = 'bulkEdit';

    protected static $createRules = [
        Entity::STAKEHOLDER                     => 'sometimes|array|custom',
        Entity::MERCHANT_AVG_ORDER_VALUE        => 'sometimes|array|custom',
        Entity::CONTACT_NAME                    => 'sometimes|alpha_space|max:255',
        Entity::CONTACT_EMAIL                   => 'sometimes|email|max:255',
        Entity::CONTACT_MOBILE                  => 'sometimes|max:15|contact_syntax',
        Entity::CONTACT_LANDLINE                => 'sometimes|numeric|digits_between:8,11',
        Entity::BUSINESS_TYPE                   => 'sometimes|numeric|digits_between:1,10',
        Entity::BUSINESS_NAME                   => 'filled|string|max:255',
        Entity::BUSINESS_DESCRIPTION            => 'sometimes|string|max:255',
        Entity::BUSINESS_DBA                    => 'sometimes|string|max:255',
        Entity::BUSINESS_WEBSITE                => 'sometimes|max:255|custom',
        Entity::ADDITIONAL_WEBSITE              => 'sometimes|max:255|custom',
        Entity::BUSINESS_INTERNATIONAL          => 'sometimes|in:0,1',
        Entity::BUSINESS_PAYMENTDETAILS         => 'sometimes|max:2000',
        Entity::BUSINESS_MODEL                  => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_ADDRESS     => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_ADDRESS_L2  => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_STATE       => 'sometimes|alpha_space|max:2|custom',
        Entity::BUSINESS_REGISTERED_CITY        => 'sometimes|alpha_space_num|max:255',
        Entity::BUSINESS_REGISTERED_DISTRICT    => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_COUNTRY     => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_PIN         => 'sometimes|size:6',
        Entity::BUSINESS_OPERATION_ADDRESS      => 'sometimes|max:255',
        Entity::BUSINESS_OPERATION_ADDRESS_L2   => 'sometimes|max:255',
        Entity::BUSINESS_OPERATION_STATE        => 'sometimes|alpha_space|max:2|custom',
        Entity::BUSINESS_OPERATION_CITY         => 'sometimes|alpha_space_num|max:255',
        Entity::BUSINESS_OPERATION_DISTRICT     => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_COUNTRY      => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_PIN          => 'sometimes|size:6',
        Entity::BUSINESS_DOE                    => 'sometimes|date_format:"Y-m-d"|before:"today"',
        Entity::GSTIN                           => 'filled|string|size:15|nullable',
        Entity::P_GSTIN                         => 'filled|string|size:15',
        Entity::COMPANY_CIN                     => ['filled', 'regex:/^([A-Z|a-z]{3}-\d{4}|[F|f]\w{3}-\d{4}|[ulUL]\d{5}[A-Z|a-z]{2}\d{4}[A-Z|a-z]{3}\d{6})$/'],
        Entity::COMPANY_PAN                     => 'filled|companyPan',
        Entity::COMPANY_PAN_NAME                => 'filled|max:255',
        Entity::BUSINESS_CATEGORY               => 'filled|max:255|custom',
        Entity::BUSINESS_SUBCATEGORY            => 'filled|max:255|custom',
        Entity::TRANSACTION_VOLUME              => 'sometimes|numeric|digits_between:1,4',
        Entity::TRANSACTION_VALUE               => 'filled|numeric|min:0|max:10000000',
        Entity::PROMOTER_PAN                    => 'sometimes|personalPan',
        Entity::PROMOTER_PAN_NAME               => 'sometimes|max:255',
        Entity::BANK_NAME                       => 'sometimes|alpha_num|between:5,20',
        Entity::BANK_ACCOUNT_NUMBER             => 'sometimes|regex:/^[a-zA-Z0-9]+$/|between:5,20|custom',
        Entity::BANK_ACCOUNT_NAME               => 'sometimes|string|min:3|max:120',
        Entity::BANK_ACCOUNT_TYPE               => 'sometimes|alpha_space|max:20',
        Entity::BANK_BRANCH                     => 'sometimes|max:255',
        Entity::BANK_BRANCH_IFSC                => 'sometimes|alpha_num|max:11|custom',
        Entity::BANK_BRANCH_CODE_TYPE           => 'required_with:'. Entity::BANK_BRANCH_CODE . '|string|max:255|custom',
        Entity::BANK_BRANCH_CODE                => 'required_with:'. Entity::BANK_BRANCH_CODE_TYPE . '|alpha_num|max:11',
        Entity::BANK_BENEFICIARY_ADDRESS1       => 'sometimes|max:30',
        Entity::BANK_BENEFICIARY_ADDRESS2       => 'sometimes|max:30',
        Entity::BANK_BENEFICIARY_ADDRESS3       => 'sometimes|max:30',
        Entity::BANK_BENEFICIARY_CITY           => 'sometimes|max:30',
        Entity::BANK_BENEFICIARY_STATE          => 'sometimes|max:2',
        Entity::BANK_BENEFICIARY_PIN            => 'sometimes|max:15',
        Entity::WEBSITE_ABOUT                   => 'sometimes|max:255|url',
        Entity::WEBSITE_CONTACT                 => 'sometimes|max:255|url',
        Entity::WEBSITE_PRIVACY                 => 'sometimes|max:255|url',
        Entity::WEBSITE_TERMS                   => 'sometimes|max:255|url',
        Entity::WEBSITE_REFUND                  => 'sometimes|max:255|url',
        Entity::WEBSITE_PRICING                 => 'sometimes|max:255|url',
        Entity::WEBSITE_LOGIN                   => 'sometimes|max:255|url',
        Entity::BUSINESS_PROOF_URL              => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::BUSINESS_OPERATION_PROOF_URL    => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::BUSINESS_PAN_URL                => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::ADDRESS_PROOF_URL               => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::PROMOTER_PROOF_URL              => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::PROMOTER_PAN_URL                => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::PROMOTER_ADDRESS_URL            => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::FORM_12A_URL                    => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif|custom',
        Entity::FORM_80G_URL                    => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif|custom',
        Entity::TRANSACTION_REPORT_EMAIL        => 'sometimes|custom',
        Entity::ROLE                            => 'sometimes|max:255',
        Entity::DEPARTMENT                      => 'sometimes|max:255',
        Entity::LOCKED                          => 'sometimes|boolean',
        Entity::COMMENT                         => 'sometimes|max:255',
        Entity::SUBMIT                          => 'sometimes',
        Entity::ADDITIONAL_WEBSITES             => 'sometimes|array|max:15',
        Entity::ADDITIONAL_WEBSITES. '.*'       => 'required_with:'. Entity::ADDITIONAL_WEBSITES . '|string|custom:active_url',
        Entity::ACTIVATION_FORM_MILESTONE       => 'sometimes|string|max:30|custom',
        Entity::SHOP_ESTABLISHMENT_NUMBER       => 'sometimes|string|max:100|nullable',
        Entity::INDUSTRY_CATEGORY_CODE_TYPE     => 'required_with:'. Entity::INDUSTRY_CATEGORY_CODE . '|string|max:255|custom',
        Entity::INDUSTRY_CATEGORY_CODE          => 'required_with:'. Entity::INDUSTRY_CATEGORY_CODE_TYPE . '|alpha_num|max:5',
        BDConstants::PLAYSTORE_URL              => 'sometimes|custom:active_url|max:255|nullable',
        BDConstants::APPSTORE_URL               => 'sometimes|custom:active_url|max:255|nullable',
        BDConstants::PHYSICAL_STORE             => 'sometimes|boolean',
        BDConstants::SOCIAL_MEDIA               => 'sometimes|boolean',
        BDConstants::WEBSITE_OR_APP             => 'sometimes|boolean',
        BDConstants::WEBSITE_NOT_READY          => 'sometimes|boolean',
        BDConstants::WEBSITE_COMPLIANCE_CONSENT => 'sometimes|boolean',
        BDConstants::OTHERS                     => 'sometimes|string',
        BDConstants::WEBSITE_PRESENT            => 'sometimes|boolean',
        BDConstants::IOS_APP_PRESENT            => 'sometimes|boolean',
        BDConstants::OTHERS_PRESENT             => 'sometimes|boolean',
        BDConstants::ANDROID_APP_PRESENT        => 'sometimes|boolean',
        BDConstants::WHATSAPP_SMS_EMAIL         => 'sometimes|boolean',
        BDConstants::SOCIAL_MEDIA_URLS          => 'sometimes|array',
        BDConstants::CONSENT                    => 'sometimes|boolean',
        BDConstants::DOCUMENTS_DETAIL           => 'sometimes|array',

        Entity::MERCHANT_ID                             => 'sometimes|string|max:14',
        Entity::POI_VERIFICATION_STATUS                 => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::POA_VERIFICATION_STATUS                 => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::GSTIN_VERIFICATION_STATUS               => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::CIN_VERIFICATION_STATUS                 => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::COMPANY_PAN_VERIFICATION_STATUS         => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::PERSONAL_PAN_DOC_VERIFICATION_STATUS    => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::COMPANY_PAN_DOC_VERIFICATION_STATUS     => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS  => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::MSME_DOC_VERIFICATION_STATUS            => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::BANK_DETAILS_DOC_VERIFICATION_STATUS    => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',

    ];

    protected static $editRules = [
        Entity::SUBMITTED                                => 'sometimes|boolean',
        Entity::SUBMITTED_AT                             => 'sometimes|integer',
        Entity::STAKEHOLDER                              => 'sometimes|array|custom',
        Entity::MERCHANT_AVG_ORDER_VALUE                 => 'filled|array|custom',
        Entity::CONTACT_NAME                             => 'sometimes|alpha_space|max:255',
        Entity::CONTACT_EMAIL                            => 'sometimes|nullable|email|max:255',
        Entity::CONTACT_MOBILE                           => 'sometimes|max:15|contact_syntax',
        Entity::CONTACT_LANDLINE                         => 'sometimes|numeric|digits_between:8,11',
        Entity::BUSINESS_TYPE                            => 'filled|numeric|digits_between:1,13',
        Entity::BUSINESS_NAME                            => 'sometimes|max:255',
        Entity::BUSINESS_DESCRIPTION                     => 'filled|max:255',
        Entity::BUSINESS_DBA                             => 'filled|max:255',
        Entity::BUSINESS_WEBSITE                         => 'sometimes|max:255|custom',
        Entity::ADDITIONAL_WEBSITE                       => 'sometimes|max:255|custom',
        Entity::BUSINESS_INTERNATIONAL                   => 'sometimes|in:0,1',
        Entity::BUSINESS_PAYMENTDETAILS                  => 'sometimes|max:2000',
        Entity::BUSINESS_MODEL                           => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_ADDRESS              => 'filled|max:255',
        Entity::BUSINESS_REGISTERED_ADDRESS_L2           => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_STATE                => 'filled|alpha_space|max:2|custom',
        Entity::BUSINESS_REGISTERED_COUNTRY              => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_CITY                 => 'filled|alpha_space_num|max:255',
        Entity::BUSINESS_REGISTERED_DISTRICT             => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_PIN                  => 'filled|size:6',
        Entity::BUSINESS_OPERATION_ADDRESS               => 'filled|max:255',
        Entity::BUSINESS_OPERATION_ADDRESS_L2            => 'sometimes|max:255',
        Entity::BUSINESS_OPERATION_STATE                 => 'filled|alpha_space|max:2|custom',
        Entity::BUSINESS_OPERATION_COUNTRY               => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_CITY                  => 'filled|alpha_space_num|max:255',
        Entity::BUSINESS_OPERATION_DISTRICT              => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_PIN                   => 'filled|size:6',
        Entity::BUSINESS_DOE                             => 'sometimes|date_format:"Y-m-d"|before:"today"',
        Entity::GSTIN                                    => 'sometimes|string|size:15|nullable',
        Entity::P_GSTIN                                  => 'sometimes|string|size:15',
        Entity::COMPANY_CIN                              => ['filled', 'regex:/^([A-Z|a-z]{3}-\d{4}|[F|f]\w{3}-\d{4}|[ulUL]\d{5}[A-Z|a-z]{2}\d{4}[A-Z|a-z]{3}\d{6})$/'],
        Entity::COMPANY_PAN                              => 'filled|companyPan',
        Entity::COMPANY_PAN_NAME                         => 'sometimes|max:255',
        Entity::BUSINESS_CATEGORY                        => 'filled|max:255|custom',
        Entity::BUSINESS_SUBCATEGORY                     => 'sometimes|max:255|custom',
        Entity::TRANSACTION_VOLUME                       => 'sometimes|numeric|digits_between:1,4',
        Entity::TRANSACTION_VALUE                        => 'filled|numeric|min:0|max:10000000',
        Entity::PROMOTER_PAN                             => 'filled|personalPan',
        Entity::PROMOTER_PAN_NAME                        => 'filled|max:255',
        Entity::BANK_NAME                                => 'sometimes|alpha_num|between:5,20',
        Entity::BANK_ACCOUNT_NUMBER                      => 'filled|regex:/^[a-zA-Z0-9-]+$/|between:5,35|custom',
        Entity::BANK_ACCOUNT_NAME                        => 'filled|string|min:3|max:120',
        Entity::BANK_ACCOUNT_TYPE                        => 'sometimes|alpha_space|max:20',
        Entity::BANK_BRANCH                              => 'sometimes|max:255',
        Entity::BANK_BRANCH_IFSC                         => 'filled|alpha_num|max:11|custom',
        Entity::BANK_BRANCH_CODE_TYPE                    => 'required_with:'. Entity::BANK_BRANCH_CODE . '|string|max:255|custom',
        Entity::BANK_BRANCH_CODE                         => 'required_with:'. Entity::BANK_BRANCH_CODE_TYPE . '|alpha_num|max:11',
        Entity::BANK_BENEFICIARY_ADDRESS1                => 'sometimes|max:30',
        Entity::BANK_BENEFICIARY_ADDRESS2                => 'sometimes|max:30',
        Entity::BANK_BENEFICIARY_ADDRESS3                => 'sometimes|max:30',
        Entity::BANK_BENEFICIARY_CITY                    => 'sometimes|max:30',
        Entity::BANK_BENEFICIARY_STATE                   => 'sometimes|max:2',
        Entity::BANK_BENEFICIARY_PIN                     => 'sometimes|max:15',
        Entity::WEBSITE_ABOUT                            => 'sometimes|max:255|url',
        Entity::WEBSITE_CONTACT                          => 'sometimes|max:255|url',
        Entity::WEBSITE_PRIVACY                          => 'sometimes|max:255|url',
        Entity::WEBSITE_TERMS                            => 'sometimes|max:255|url',
        Entity::WEBSITE_REFUND                           => 'sometimes|max:255|url',
        Entity::WEBSITE_PRICING                          => 'sometimes|max:255|url',
        Entity::WEBSITE_LOGIN                            => 'sometimes|max:255|url',
        Entity::BUSINESS_PROOF_URL                       => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::BUSINESS_OPERATION_PROOF_URL             => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::BUSINESS_PAN_URL                         => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::ADDRESS_PROOF_URL                        => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::PROMOTER_PROOF_URL                       => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::PROMOTER_PAN_URL                         => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::PROMOTER_ADDRESS_URL                     => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        Entity::FORM_12A_URL                             => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif|custom',
        Entity::FORM_80G_URL                             => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif|custom',
        Entity::TRANSACTION_REPORT_EMAIL                 => 'sometimes|custom',
        Entity::ROLE                                     => 'sometimes|max:255',
        Entity::DEPARTMENT                               => 'sometimes|max:255',
        Entity::LOCKED                                   => 'sometimes|boolean',
        Entity::COMMENT                                  => 'sometimes|max:255',
        Entity::SUBMIT                                   => 'sometimes|boolean',
        Entity::ACTIVATION_STATUS                        => 'sometimes|max:30',
        Entity::ACTIVATION_PROGRESS                      => 'sometimes|numeric|min:0|max:100',
        Entity::CLARIFICATION_MODE                       => 'sometimes|max:15',
        Entity::ISSUE_FIELDS                             => 'sometimes|string',
        Entity::ISSUE_FIELDS_REASON                      => 'sometimes|string',
        Entity::INTERNAL_NOTES                           => 'sometimes|string',
        Entity::ACTIVATION_FLOW                          => 'sometimes|custom',
        Entity::INTERNATIONAL_ACTIVATION_FLOW            => 'sometimes|custom',
        Entity::CUSTOM_FIELDS                            => 'filled|array',
        Entity::CLIENT_APPLICATIONS                      => 'filled|array',
        Entity::LIVE_TRANSACTION_DONE                    => 'filled|numeric|in:0,1,2',
        Entity::KYC_CLARIFICATION_REASONS                => 'sometimes|array|custom',
        Entity::KYC_ADDITIONAL_DETAILS                   => 'sometimes|array|custom',
        Entity::ADDITIONAL_WEBSITES                      => 'sometimes|array|max:15',
        Entity::ADDITIONAL_WEBSITES . '.*'               => 'required_with:' . Entity::ADDITIONAL_WEBSITES . '|string|custom:active_url',
        Entity::ESTD_YEAR                                => 'sometimes|max:4',
        Entity::DATE_OF_ESTABLISHMENT                    => 'filled|date_format:"Y-m-d"|before:"today"',
        Entity::AUTHORIZED_SIGNATORY_RESIDENTIAL_ADDRESS => 'sometimes|max:255',
        Entity::AUTHORIZED_SIGNATORY_DOB                 => 'sometimes|date_format:"Y-m-d"|before:"today"',
        Entity::PLATFORM                                 => 'sometimes|max:40',
        Entity::ACTIVATION_FORM_MILESTONE                => 'sometimes|string|max:30|custom',
        Entity::SHOP_ESTABLISHMENT_NUMBER                => 'sometimes|string|max:100|nullable',
        Entity::BUSINESS_SUGGESTED_PIN                   => 'sometimes|size:6',
        Entity::BUSINESS_SUGGESTED_ADDRESS               => 'sometimes|max:255',
        Entity::INDUSTRY_CATEGORY_CODE_TYPE              => 'required_with:'. Entity::INDUSTRY_CATEGORY_CODE . '|string|max:255|custom',
        Entity::INDUSTRY_CATEGORY_CODE                   => 'required_with:'. Entity::INDUSTRY_CATEGORY_CODE_TYPE . '|alpha_num|max:5',
        BDConstants::PLAYSTORE_URL                       => 'sometimes|custom:active_url|max:255|nullable',
        BDConstants::APPSTORE_URL                        => 'sometimes|custom:active_url|max:255|nullable',
        BDConstants::PHYSICAL_STORE                      => 'sometimes|boolean',
        BDConstants::SOCIAL_MEDIA                        => 'sometimes|boolean',
        BDConstants::WEBSITE_OR_APP                      => 'sometimes|boolean',
        BDConstants::WEBSITE_NOT_READY                   => 'sometimes|boolean',
        BDConstants::WEBSITE_COMPLIANCE_CONSENT          => 'sometimes|boolean',
        BDConstants::OTHERS                              => 'sometimes|string',
        BDConstants::WEBSITE_PRESENT                     => 'sometimes|boolean',
        BDConstants::IOS_APP_PRESENT                     => 'sometimes|boolean',
        BDConstants::OTHERS_PRESENT                      => 'sometimes|boolean',
        BDConstants::ANDROID_APP_PRESENT                 => 'sometimes|boolean',
        BDConstants::WHATSAPP_SMS_EMAIL                  => 'sometimes|boolean',
        BDConstants::SOCIAL_MEDIA_URLS                   => 'sometimes|array',
        Entity::IEC_CODE                                 => 'sometimes|string|max:20',
        BDConstants::CONSENT                             => 'sometimes|boolean',
        BDConstants::DOCUMENTS_DETAIL                    => 'sometimes|array',

        Entity::POI_VERIFICATION_STATUS                 => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::POA_VERIFICATION_STATUS                 => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::GSTIN_VERIFICATION_STATUS               => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::CIN_VERIFICATION_STATUS                 => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::COMPANY_PAN_VERIFICATION_STATUS         => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::PERSONAL_PAN_DOC_VERIFICATION_STATUS    => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::COMPANY_PAN_DOC_VERIFICATION_STATUS     => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS  => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::MSME_DOC_VERIFICATION_STATUS            => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',
        Entity::BANK_DETAILS_DOC_VERIFICATION_STATUS    => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated',

   ];

    protected static $preSignupRules = [
        Entity::BUSINESS_TYPE                   => 'sometimes|numeric|digits_between:1,10',
        Entity::COUPON_CODE                     => 'filled|string|max:10',
        Entity::REFERRAL_CODE                   => 'filled|string',
        Entity::TRANSACTION_VOLUME              => 'sometimes|numeric|digits_between:1,4',
        Entity::ROLE                            => 'sometimes|numeric|digits_between:1,6',
        Entity::DEPARTMENT                      => 'sometimes|numeric|digits_between:1,7',
        Entity::BUSINESS_NAME                   => 'sometimes|string|max:255',
        Entity::CONTACT_NAME                    => 'sometimes|alpha_space|max:255',
        Entity::CONTACT_MOBILE                  => 'sometimes|max:15|contact_syntax',
//        Entity::CONTACT_EMAIL                   => 'sometimes|email|max:255|unique:merchant_details',
        Entity::BUSINESS_WEBSITE                => 'sometimes|max:255|custom',
    ];

    protected static $uploadDocumentRules = [
        Merchant\Document\Entity::DOCUMENT_TYPE => 'required|string|max:255|custom',
        Entity::FILE                            => 'required|file|mimes:pdf,jpeg,jpg,png,jfif,heic,heif',
    ];

    protected static $editLinkedAccountBusinessNameRules = [
        Entity::BUSINESS_NAME => [
                                    'sometimes',
                                    'string',
                                    'max:255',
                                    'not_regex:"(https?:\/\/)*(w{3}\.)*[a-zA-Z0-9]+(\.)(com|in|net|co\.in|org|us|info|co)+(\ |\/|$|\n)"',
                                    'not_regex:"(<!doctype>|<!--|<.*>|&[a-z0-9]+;)+"'
                                ]
    ];

    protected static $updateContactAndLoginMobileRules = [
        Constants::OLD_CONTACT_NUMBER                             => 'required|max:15|contact_syntax',
        Constants::NEW_CONTACT_NUMBER                             => 'required|max:15|contact_syntax',
    ];

    protected static $cinVerificationRules = [
        Constants::COMPANY_CIN => ['required', 'regex:/^([A-Z|a-z]{3}-\d{4}|[F|f]\w{3}-\d{4}|[ulUL]\d{5}[A-Z|a-z]{2}\d{4}[A-Z|a-z]{3}\d{6})$/'],
    ];

    protected static $sendWhatsappNotificationRules = [
        'ticket_id' => 'required|string',
        'documents' => 'required|array',
     ];

    protected static $activationEmailRules = [
        'email'                 => 'sometimes|email|max:255',
    ];

    protected static $archiveFormRules = [
        Entity::ARCHIVE                         => 'required|boolean',
    ];

    protected static $identityVerificationRules = [
        'verification_type'         => 'required|in:AADHAAR_EKYC',
        'redirect_url'              => 'sometimes|string|custom:active_url'
    ];

    protected static $digilockerUrlRules = [
        'redirect_url'              => 'required|string|custom:active_url'
    ];

    protected static $activationStatusRules = [
        Entity::ACTIVATION_STATUS               => 'required|string|max:30',
        Entity::CLARIFICATION_MODE              => 'filled|string|max:15',
        Entity::REJECTION_REASONS               => 'filled|array',
        Entity::REJECTION_OPTION                => 'sometimes|string|max:30'
    ];

    protected static $activationStatusInternalRules = [
        Entity::ACTIVATION_STATUS               => 'required|string|max:30',
        Entity::CLARIFICATION_MODE              => 'filled|string|max:15',
        Entity::REJECTION_REASONS               => 'filled|array',
        Entity::REJECTION_OPTION                => 'sometimes|string|max:30',
        Constants::WORKFLOW_MAKER_ADMIN_ID      => 'required|string|max:30',
    ];

    protected static $merchantConsentRules = [
        'consents'                              => 'filled|array',
        'is_provided'                           => ['required|boolean'],
        'documents_detail'                      => 'filled|array',
        'type'                                  => ['required|in:' . ConsentConstant::VALID_LEGAL_DOC_KEYS],
        'url'                                   => ['required|string|custom:active_url']
    ];

    protected  static $bulkEditRules = [
        Entity::FILE                            => 'required|file|max:1024|mime_types:text/csv,text/plain|mimes:csv,txt',
    ];

    protected static $activationStatusValidators = [
        'activation_status',
        'clarification_mode',
    ];

    protected static $createValidators = [
        'business_subcategory_for_category',
        'blacklisted_bank',
        Entity::BANK_BRANCH_CODE,
    ];

    protected static $editValidators = [
        'business_subcategory_for_category',
        'blacklisted_bank',
        Entity::BANK_BRANCH_CODE,
    ];

    protected static $pennyTestingEventPayloadRules = [
        Constants::MERCHANT_ID              => 'required|string|max:14',
        Constants::ACCOUNT_STATUS           => 'sometimes|nullable|string',
        Constants::REGISTERED_NAME          => 'sometimes|string',
        Constants::PENNY_TESTING_REASON     => 'sometimes',
    ];

    protected static $instantActivationRules = [
        Entity::COMPANY_CIN                 => ['sometimes', 'regex:/^([A-Z|a-z]{3}-\d{4}|[F|f]\w{3}-\d{4}|[ulUL]\d{5}[A-Z|a-z]{2}\d{4}[A-Z|a-z]{3}\d{6})$/'],
        Entity::COMPANY_PAN                 => 'sometimes|max:255|companyPan',
        Entity::BUSINESS_CATEGORY           => 'required|max:255|custom',
        Entity::BUSINESS_SUBCATEGORY        => 'sometimes|max:255|custom',
        Entity::PROMOTER_PAN                => 'required|personalPan',
        Entity::PROMOTER_PAN_NAME           => 'sometimes|string|max:255',
        Entity::BUSINESS_NAME               => 'sometimes|string|max:255',
        Entity::BUSINESS_MODEL              => 'sometimes|max:255',
        Entity::BUSINESS_WEBSITE            => 'sometimes|max:255|custom',
        Entity::BUSINESS_DBA                => 'required|string|max:255',
        Entity::BUSINESS_TYPE               => 'required|numeric|digits_between:1,10',
        Entity::BUSINESS_OPERATION_ADDRESS  => 'sometimes|max:255',
        Entity::BUSINESS_OPERATION_STATE    => 'sometimes|alpha_space|max:2|custom',
        Entity::BUSINESS_OPERATION_CITY     => 'sometimes|alpha_space_num|max:255',
        Entity::BUSINESS_OPERATION_PIN      => 'sometimes|size:6',
        Entity::BUSINESS_REGISTERED_ADDRESS => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_STATE   => 'sometimes|alpha_space|max:2|custom',
        Entity::BUSINESS_REGISTERED_CITY    => 'sometimes|alpha_space_num|max:255',
        Entity::BUSINESS_REGISTERED_PIN     => 'sometimes|size:6',
        Entity::ACTIVATION_FORM_MILESTONE   => 'sometimes|string|max:30|custom',
        Entity::CONTACT_MOBILE              => 'sometimes|max:15|contact_syntax',
        Entity::CONTACT_NAME                => 'sometimes|string|max:50',
        Entity::CONTACT_EMAIL               => 'sometimes|email|max:255',
        BDConstants::CONSENT                => 'sometimes|boolean',
        BDConstants::DOCUMENTS_DETAIL       => 'sometimes|array'
    ];

    protected static $instantActivationBatchRules = [
        Entity::BUSINESS_CATEGORY           => 'required|max:255|custom',
        Entity::BUSINESS_SUBCATEGORY        => 'sometimes|max:255|custom',
        Entity::BUSINESS_NAME               => 'required|string|max:255',
        Entity::BUSINESS_DBA                => 'required|string|max:255',
        Entity::BUSINESS_TYPE               => 'required|numeric|digits_between:1,10',
        Entity::BUSINESS_REGISTERED_ADDRESS => 'required|max:255',
        Entity::BUSINESS_REGISTERED_STATE   => 'required|alpha_space|max:2|custom',
        Entity::BUSINESS_REGISTERED_CITY    => 'required|alpha_space_num|max:255',
        Entity::BUSINESS_REGISTERED_PIN     => 'required|size:6',
        Entity::BUSINESS_OPERATION_ADDRESS  => 'required|max:255',
        Entity::BUSINESS_OPERATION_STATE    => 'required|alpha_space|max:2|custom',
        Entity::BUSINESS_OPERATION_CITY     => 'required|alpha_space_num|max:255',
        Entity::BUSINESS_OPERATION_PIN      => 'required|size:6',
    ];

    protected static $instantActivationValidators = [
        'registered_business_rules',
        'unregistered_business_rules',
    ];

    protected static $websiteDetailsRules = [
        Entity::BUSINESS_WEBSITE                => 'required|max:255|custom',
    ];

    protected static $additionalWebsitesRules = [
        Entity::ADDITIONAL_WEBSITE              => 'required|max:255|custom',
    ];

    protected static $deleteAdditionalWebsitesRules = [
        Entity::ADDITIONAL_WEBSITES             => 'required|array|min:0'
    ];

    protected static $patchMerchantDetailsRules = [
        Entity::BUSINESS_OPERATION_ADDRESS               => 'filled|max:255',
        Entity::BUSINESS_OPERATION_STATE                 => 'filled|alpha_space|max:2|custom',
        Entity::BUSINESS_OPERATION_CITY                  => 'filled|alpha_space_num|max:255',
        Entity::BUSINESS_OPERATION_PIN                   => 'filled|size:6',
        Entity::CONTACT_MOBILE                           => 'sometimes|max:15|contact_syntax',
        Entity::BUSINESS_CATEGORY                        => 'sometimes|max:255|custom',
        Entity::BUSINESS_SUBCATEGORY                     => 'sometimes|max:255|custom',
        Entity::BUSINESS_NAME                            => 'sometimes|string|max:255',
        Entity::BUSINESS_MODEL                           => 'sometimes|max:255',
        Entity::INTERNATIONAL_ACTIVATION_FLOW            => 'filled|custom',
        Entity::BANK_DETAILS_VERIFICATION_STATUS         => 'filled|custom',
        Entity::ESTD_YEAR                                => 'filled|max:4',
        Entity::DATE_OF_ESTABLISHMENT                    => 'filled|date_format:"Y-m-d"|before:"today"',
        Entity::AUTHORIZED_SIGNATORY_RESIDENTIAL_ADDRESS => 'filled|max:255',
        Entity::AUTHORIZED_SIGNATORY_DOB                 => 'filled|date_format:"Y-m-d"|before:"today"',
        Entity::PLATFORM                                 => 'filled|max:40',
    ];

    protected static $updateEntityBatchActionRules = [
        Entity::BUSINESS_NAME               => 'filled|max:255',
        Entity::BUSINESS_REGISTERED_ADDRESS => 'filled|max:255',
        Entity::BUSINESS_REGISTERED_STATE   => 'filled|max:2|custom',
    ];

    protected static $batchInstantActivationRules = [
        Entity::BUSINESS_CATEGORY           => 'sometimes|max:255|custom',
        Entity::BUSINESS_SUBCATEGORY        => 'sometimes|max:255|custom',
        Entity::BUSINESS_NAME               => 'sometimes|string|max:255',
        Entity::BUSINESS_DBA                => 'sometimes|string|max:255',
        Entity::BUSINESS_TYPE               => 'sometimes|numeric|digits_between:1,10',
        Entity::BUSINESS_REGISTERED_ADDRESS => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_STATE   => 'sometimes|alpha_space|max:2|custom',
        Entity::BUSINESS_REGISTERED_CITY    => 'sometimes|alpha_space_num|max:255',
        Entity::BUSINESS_REGISTERED_PIN     => 'sometimes|size:6',
        Entity::BUSINESS_OPERATION_ADDRESS  => 'sometimes|max:255',
        Entity::BUSINESS_OPERATION_STATE    => 'sometimes|alpha_space|max:2|custom',
        Entity::BUSINESS_OPERATION_CITY     => 'sometimes|alpha_space_num|max:255',
        Entity::BUSINESS_OPERATION_PIN      => 'sometimes|size:6',
        Entity::SEND_ACTIVATION_EMAIL       => 'sometimes|in:0,1',
    ];

    protected static $gstinSelfServeRules = [
        Entity::GSTIN                           => 'filled|string|size:15',
        Constants::GSTIN_SELF_SERVE_CERTIFICATE => 'required|file|mimes:pdf,jpeg,jpg,png,jfif,heic,heif'
    ];

    protected static $gstinSelfServeValidators = [
        'gstin_self_serve_not_in_progress',
    ];

    protected static $businessWebsitesCheckRules = [
        DetailConstants::BUSINESS_WEBSITE_MAIN_PAGE          => 'required|max:255|custom:active_url',
        DetailConstants::BUSINESS_WEBSITE_ABOUT_US           => 'required|max:255|custom:active_url',
        DetailConstants::BUSINESS_WEBSITE_CONTACT_US         => 'required|max:255|custom:active_url',
        DetailConstants::BUSINESS_WEBSITE_PRICING_DETAILS    => 'required|max:255|custom:active_url',
        DetailConstants::BUSINESS_WEBSITE_PRIVACY_POLICY     => 'required|max:255|custom:active_url',
        DetailConstants::BUSINESS_WEBSITE_TNC                => 'required|max:255|custom:active_url',
        DetailConstants::BUSINESS_WEBSITE_REFUND_POLICY      => 'required|max:255|custom:active_url',
        DetailConstants::BUSINESS_WEBSITE_USERNAME           => 'sometimes|string|max:50',
        DetailConstants::BUSINESS_WEBSITE_PASSWORD           => 'sometimes|string|max:50',
        DetailConstants::URL_TYPE                            => 'required|string|in:'.DetailConstants::URL_TYPE_WEBSITE,
    ];

    protected static $businessAppUrlCheckRules = [
        DetailConstants::BUSINESS_APP_URL           => 'sometimes|string|max:100',
        DetailConstants::BUSINESS_APP_USERNAME      => 'sometimes|string|max:50',
        DetailConstants::BUSINESS_APP_PASSWORD      => 'sometimes|string|max:50',
        DetailConstants::URL_TYPE                   => 'required|string|in:'.DetailConstants::URL_TYPE_APP,
    ];

    protected static $additionalWebsiteCheckRules = [
        DetailConstants::ADDITIONAL_WEBSITE_MAIN_PAGE          => 'required|max:255|custom:active_url',
        DetailConstants::ADDITIONAL_WEBSITE_ABOUT_US           => 'required|max:255|custom:active_url',
        DetailConstants::ADDITIONAL_WEBSITE_CONTACT_US         => 'required|max:255|custom:active_url',
        DetailConstants::ADDITIONAL_WEBSITE_PRICING_DETAILS    => 'required|max:255|custom:active_url',
        DetailConstants::ADDITIONAL_WEBSITE_PRIVACY_POLICY     => 'required|max:255|custom:active_url',
        DetailConstants::ADDITIONAL_WEBSITE_TNC                => 'required|max:255|custom:active_url',
        DetailConstants::ADDITIONAL_WEBSITE_REFUND_POLICY      => 'required|max:255|custom:active_url',
        DetailConstants::ADDITIONAL_WEBSITE_TEST_USERNAME      => 'sometimes|string|max:50',
        DetailConstants::ADDITIONAL_WEBSITE_TEST_PASSWORD      => 'sometimes|string|max:50',
        DetailConstants::ADDITIONAL_WEBSITE_REASON             => 'required|string|min:100',
        DetailConstants::ADDITIONAL_WEBSITE_PROOF_URL          => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip,jfif,heic,heif',
        DetailConstants::URL_TYPE                              => 'required|string|in:website',
    ];

    protected static $additionalAppCheckRules = [
        DetailConstants::ADDITIONAL_APP_URL             => 'required|string|custom:active_url|max:255',
        DetailConstants::ADDITIONAL_APP_TEST_USERNAME   => 'sometimes|string|max:50',
        DetailConstants::ADDITIONAL_APP_TEST_PASSWORD   => 'sometimes|string|max:50',
        DetailConstants::ADDITIONAL_APP_REASON          => 'required|string|min:100',
        DetailConstants::URL_TYPE                       => 'required|string|in:app',
    ];

    protected static $uniqueContactMobileRules = [
      Entity::CONTACT_MOBILE                            => 'filled|unique:merchant_details',
    ];

    /**
     * @param $input
     * @param $merchantId
     * @throws NumberParseException
     */
    public function validateUniqueContactMobile($input, $merchantId)
    {
        $uniqueMobileCheckEnabled = (new Merchant\Core())->isRazorxExperimentEnable(
            $merchantId,
            \RZP\Models\Feature\Constants::UNIQUE_MOBILE_ON_PRESIGNUP
        );

        if(
            (isset($input[Entity::CONTACT_MOBILE]) === true)
            and ($uniqueMobileCheckEnabled === true)
        )
        {
            $validMobileNumberFormats = (new PhoneBook($input[Entity::CONTACT_MOBILE]))->getMobileNumberFormats();
            foreach ($validMobileNumberFormats as $mobileNumber)
            {
                $this->validateInput('unique_contact_mobile', [Entity::CONTACT_MOBILE => $mobileNumber]);
            }
        }

    }

    /**
     * If user has signup via email then we cannot allow email in pre_signup details
     * if user has signup via contact mobile then we cannot allow contact mobile in pre_signup details
     * @param array $input
     * @param $merchant
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateSignupViaChannel(array $input, $merchant)
    {
        if (
            ($merchant[Merchant\Entity::SIGNUP_VIA_EMAIL] === 1)
            && (isset($input[Entity::CONTACT_EMAIL]) === true)
            && (strlen($input[Entity::CONTACT_EMAIL]) !== 0)
        )
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::PRE_SIGNUP_EMAIL_NOT_ALLOWED
            );
        }
        else if (
            ($merchant[Merchant\Entity::SIGNUP_VIA_EMAIL] === 0)
            && (isset($input[Entity::CONTACT_MOBILE]) === true)
            && (strlen($input[Entity::CONTACT_MOBILE]) !== 0)
        )
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::PRE_SIGNUP_CONTACT_MOBILE_NOT_ALLOWED
            );
        }
    }

    public function validateBusinessRegisteredState(string $attribute, $value)
    {
        if(empty($value) === true)
        {
            return;
        }

        if(IndianStates::stateValueExist($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_INVALID_STATE_CODE
            );
        }
    }

    public function validateBusinessOperationState(string $attribute, $value)
    {
        if(empty($value) === true)
        {
            return;
        }

        if(IndianStates::stateValueExist($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_INVALID_STATE_CODE
            );
        }
    }

    public function validateDocumentUpload(array $input)
    {
        if (empty($input) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_DOCUMENT_TYPE_INVALID
            );
        }

        foreach ($input as $key => $value)
        {
            $payload = [
                Merchant\Document\Entity::DOCUMENT_TYPE => $key,
                Entity::FILE                            => $value,
            ];

            $this->validateInput('uploadDocument', $payload);
        }
    }

    public function validateDocumentType(string $attribute, $value)
    {
        if (Type::isValid($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_DOCUMENT_TYPE_INVALID . ':' . $value
            );
        }
    }

    public function validateBankDetailsVerificationStatus($attribute, $value)
    {

        $this->validateActivationFormSubmitted();

        $validBankDetailValidationStatuses = BankDetailsVerificationStatus::ALLOWED_NEXT_BANK_DETAIL_VERIFICATION_STATUSES_MAPPING;

        $this->isAllowedStatusChange(
            $this->entity->getBankDetailsVerificationStatus(),
            $value,
            $validBankDetailValidationStatuses,
            ErrorCode::BAD_REQUEST_INVALID_BANK_DETAIL_VERIFICATION_STATUS_CHANGE);

    }

    private function isAllowedStatusChange($currentStatus,
                                           string $newStatus,
                                           array $allowedStatus,
                                           string $errorMessage)
    {

        if (empty($currentStatus) === true)
        {
            return;
        }

        if ((isset($allowedStatus[$currentStatus]) === false) or
            (in_array($newStatus, $allowedStatus[$currentStatus], true) === false))
        {
            throw new Exception\BadRequestValidationFailureException($errorMessage, null,
                                                                     [
                                                                         "current_status" => $currentStatus,
                                                                         "new_status"     => $newStatus
                                                                     ]);
        }

    }

    protected static $bulkAssignReviewerRules = [
        Entity::REVIEWER_ID     => 'required|public_id|size:20',
        Entity::MERCHANTS       => 'filled|array',
        Entity::MERCHANTS . '*' => 'sometimes|public_id|size:14',
    ];

    protected static $merchantMtuUpdateRules = [
        Entity::MERCHANTS               => 'filled|array|between:0,15',
        Entity::MERCHANTS . '*'         => 'sometimes|public_id|size:14',
        Entity::LIVE_TRANSACTION_DONE   => 'filled|numeric|in:0,1,2',
    ];

    protected static $searchBusinessDetailsRules = [
      Constants::SEARCH_STRING  => 'sometimes|string|max:25'
    ];

    protected static $companySearchRules = [
        Constants::SEARCH_STRING  => 'required|string|min:3|max:80'
    ];

    protected function validateRegisteredBusinessRules(array $input)
    {
        if (BusinessType::isUnregisteredBusinessIndex($input[Entity::BUSINESS_TYPE]) === true)
        {
            return;
        }

        // if business is a registered business type, then business name is mandatory
        if (empty($input[Entity::BUSINESS_NAME]) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_BUSINESS_NAME_REQUIRED);
        }
    }

    protected function validateUnregisteredBusinessRules(array $input)
    {
        if (BusinessType::isUnregisteredBusinessIndex($input[Entity::BUSINESS_TYPE]) === false)
        {
            return;
        }

        $enabled = (new Merchant\Core())->isUnRegisteredOnBoardingEnabled($this->entity->merchant, true);

        if ($enabled === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_UNREGISTERED_NOT_SUPPORTED);
        }

        $this->validateForUnregisteredBlackListedCategories($input);

        if (empty($input[Entity::PROMOTER_PAN_NAME]) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_PAN_NAME_REQUIRED);
        }

        if (empty($input[Entity::COMPANY_PAN]) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_EXTRA_FIELDS_PRESENT_IN_INPUT);
        }
    }

    protected function validateForUnregisteredBlackListedCategories(array $input)
    {
        $category = array_key_exists(Entity::BUSINESS_CATEGORY, $input) ?
            $input[Entity::BUSINESS_CATEGORY] : $this->entity->getBusinessCategory();

        $subcategory = array_key_exists(Entity::BUSINESS_SUBCATEGORY, $input) ?
            $input[Entity::BUSINESS_SUBCATEGORY] : $this->entity->getBusinessSubcategory();

        $subcategoryMetaData = BusinessSubCategoryMetaData::getSubCategoryMetaData($category, $subcategory);

        if ($subcategoryMetaData[BusinessSubCategoryMetaData::NON_REGISTERED_ACTIVATION_FLOW] === ActivationFlow::BLACKLIST and
            $this->entity->merchant->isSignupCampaign(DDConstants::EASY_ONBOARDING) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_UNSUPPORTED_BUSINESS_CATEGORY);
        }
    }

    protected static $kycClarificationReasonRules = [
        Entity::CLARIFICATION_REASONS     => 'filled|array|custom',
        Entity::ADDITIONAL_DETAILS        => 'filled|array|custom:clarification_reasons',
    ];

    protected static $customClarificationReasonJsonValidationRules = [
        Merchant\Constants::REASON_TYPE => "required|string|in:custom",
        Merchant\Constants::FIELD_VALUE => 'filled',
        Merchant\Constants::FIELD_TYPE  => ['filled', 'in:document,text'],
        Merchant\Constants::REASON_CODE => 'required|string|max:500',
    ];

    protected static $predefinedClarificationReasonJsonValidationRules = [
        Merchant\Constants::REASON_TYPE => "required|string|in:predefined",
        Merchant\Constants::FIELD_VALUE => 'filled',
        Merchant\Constants::FIELD_TYPE  => ['filled', 'in:document,text'],
        Merchant\Constants::REASON_CODE => 'required|string|custom',
    ];

    protected static $clarificationResponseRules = [
        Merchant\Constants::FIELD_VALUE => 'required|string|max:200',
    ];

    /**
     * Validate the transaction report email
     *
     * @param $attribute
     * @param $value
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateTransactionReportEmail($attribute, $value)
    {
        $emails = explode(',', $value);

        foreach ($emails as $email)
        {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "The provided transaction report email is invalid: $email",
                    Entity::TRANSACTION_REPORT_EMAIL
                );
            }
        }
    }

    public function validateBankBranchIfsc($attribute, $value)
    {
        if (IFSC::validate($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_IFSC_CODE_MESSAGE);
        }
    }

    public function validateBankBranchCode($input)
    {
        if(isset($input[Entity::BANK_BRANCH_CODE]) === true){
            if ($input[Entity::BANK_BRANCH_CODE_TYPE] === BankBranchCodeType::IFSC && IFSC::validate($input[Entity::BANK_BRANCH_CODE]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(self::INVALID_BANK_BRANCH_CODE_MESSAGE);
            }
        }
    }

    public function validateBankBranchCodeType($attribute, $value)
    {
        if(!in_array($value, BankBranchCodeType::getAllowableEnumValues(), false)){
            throw new Exception\BadRequestValidationFailureException(self::INVALID_BANK_BRANCH_CODE_TYPE_MESSAGE);
        }
    }

    public function validateIndustryCategoryCodeType($attribute, $value)
    {
        if(!in_array($value, IndustryCategoryCodeType::getAllowableEnumValues(), false)){
            throw new Exception\BadRequestValidationFailureException(self::INVALID_INDUSTRY_CATEGORY_CODE_TYPE_MESSAGE);
        }
    }


    /**
     * @throws Exception\BadRequestException
     */
    public function validateMerchantUniqueNumberExcludingCurrentMerchantDetails($merchantId, $newNumber)
    {
        $validNewMobileNumberFormats = (new PhoneBook($newNumber))->getMobileNumberFormats();

        $merchantDetail = (new Repository())->findMerchantWithContactNumbersExcludingMerchant($merchantId, $validNewMobileNumberFormats);

        if (isset($merchantDetail) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_TAKEN);
        }
    }

    public function validateUniqueMerchantOwnerUserForMobile(Merchant\Entity $merchant, $oldNumber)
    {
        $validOldMobileNumberFormats = (new PhoneBook($oldNumber))->getMobileNumberFormats();

        $userIdsForAllMobileNumberFormats = $merchant->users()
                                                     ->where(Merchant\Detail\Entity::ROLE, '=', DetailConstants::OWNER)
                                                     ->whereIn(Entity::CONTACT_MOBILE, $validOldMobileNumberFormats)
                                                     ->pluck('id')
                                                     ->toArray();

        $userCountForAllMobileNumberFormats = count(array_unique($userIdsForAllMobileNumberFormats));

        if($userCountForAllMobileNumberFormats > 1)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MULTI_OWNER_ACCOUNTS_ASSOCIATED);
        }

        if($userCountForAllMobileNumberFormats === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_OWNER_ACCOUNTS_ASSOCIATED);
        }
    }

    public function validateActivationStatus(array $input)
    {
        $validActivationStatuses = ($this->checkIfKQUStateExperimentEnabled($this->entity->merchant->getId()) === true) ?
            array_keys(Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING_WITH_KQU) : array_keys(Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING);

        if (in_array($input[Entity::ACTIVATION_STATUS], $validActivationStatuses, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_STATUS_MESSAGE);
        }
    }

    public function validateClarificationMode(array $input)
    {
        if (empty($input[Entity::CLARIFICATION_MODE]) === true)
        {
            return;
        }

        if ($input[Entity::ACTIVATION_STATUS] !== Status::NEEDS_CLARIFICATION)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_CLARIFICATION_MODE_FOR_STATUS_MESSAGE);
        }

        $allowedClarificationModes = ClarificationMode::ALLOWED_CLARIFICATION_MODES;

        if (in_array($input[Entity::CLARIFICATION_MODE], $allowedClarificationModes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_CLARIFICATION_MODE_MESSAGE);
        }
    }

    public function validateActivationStatusChange($currentStatus, string $newStatus)
    {
        if (empty($currentStatus) === true)
        {
            return;
        }

        $allowedNextActivationStatusMapping = $this->getAllowedNextActivationStatus($currentStatus);

        if (in_array($newStatus, $allowedNextActivationStatusMapping, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_STATUS_CHANGE_MESSAGE);
        }
    }

    public function getAllowedNextActivationStatus(string $currentStatus): array
    {
        //
        // - With new flow for Linked Accounts where activation status can go back to 'under_review'
        // - from 'activated' status, created a new activation status mapping specifically for linked accounts
        // Jira EPA-168
        //
        if ($this->entity->merchant->isLinkedAccount() === true)
        {
            return Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING_LINKED_ACCOUNT[$currentStatus];
        }

        return ($this->checkIfKQUStateExperimentEnabled($this->entity->merchant->getId()) === true) ? Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING_WITH_KQU[$currentStatus] : Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING[$currentStatus];
    }

    public function validateActivationFormMilestone($attribute, $value)
    {
        if (empty($value) === true)
        {
            return;
        }

        $allowedMilestones = Constants::ALLOWED_MILESTONES;

        if (in_array($value, $allowedMilestones, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_ACTIVATION_FORM_MILESTONE_MESSAGE);
        }
    }

    public function validateBusinessCategory(string $attribute, $businessCategory)
    {
        $errorMessage = (empty($businessCategory) === true) ?
                         self::INVALID_BUSINESS_CATEGORY :
                        (self::INVALID_BUSINESS_CATEGORY . ': ' . $businessCategory);

        if (isset(BusinessCategory::SUBCATEGORY_MAP[$businessCategory]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $errorMessage,
                Entity::BUSINESS_CATEGORY,
                [
                    Entity::BUSINESS_CATEGORY => $businessCategory
                ]);
        }
    }

    /**
     * @param array $input
     * @param string $parentMerchantId
     * @throws BadRequestValidationFailureException
     * @throws Exception\BadRequestException
     */
    public function validateLinkedAccountBusinessNameInput(array $input, string $parentMerchantId)
    {
        try
        {
            $isUrlValidationEnabled = (new Merchant\Core())->isRazorxExperimentEnable(
                $parentMerchantId,
                RazorxTreatment::URL_VALIDATION_FOR_LINKED_ACCOUNT_NAME
            );
            if ($isUrlValidationEnabled === true)
            {
                $this->validateInput('edit_linked_account_business_name', $input);
            }
        }
        catch (BadRequestValidationFailureException $e)
        {
            if ($e->getMessage() === 'validation.not_regex')
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_URL_NOT_ALLOWED_IN_LINKED_ACCOUNT_NAME
                );
            }
            throw $e;
        }
    }

    public function validateBusinessSubcategory(string $attribute, $businessSubcategory)
    {
        if ((isset($businessSubcategory) === true) and
            (BusinessSubcategory::isValidSubcategory($businessSubcategory) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_BUSINESS_SUBCATEGORY . ': ' . $businessSubcategory,
                Entity::BUSINESS_SUBCATEGORY,
                [
                    Entity::BUSINESS_SUBCATEGORY => $businessSubcategory
                ]);
        }
    }

    /**
     * @param array $input
     */
    public function validateBlacklistedBank(array $input)
    {
        if (isset($input[Entity::BANK_BRANCH_IFSC]) === true)
        {
            $code = $input[Entity::BANK_BRANCH_IFSC];

            $bankCode   = strtoupper(substr($code, 0, 4));

            if (in_array($bankCode, Constants::BLACKLISTED_BANKS) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    self::BLACKLISTED_BANK_ACCOUNT_NUMBER,
                    Merchant\Detail\Entity::BANK_BRANCH_IFSC,
                    [
                        Merchant\Detail\Entity::BANK_BRANCH_IFSC => $code
                    ]);
            }
        }
    }

    private function extractBusinessCategory(array $input, $subCategory)
    {
        if(array_key_exists(Entity::BUSINESS_CATEGORY, $input))
        {
            return $input[Entity::BUSINESS_CATEGORY];
        }
        if(empty($subCategory) === false)
        {
            return BusinessCategory::getCategoryFromSubCategory($subCategory);
        }

        return $this->entity->getBusinessCategory();
    }

    public function validateBusinessSubcategoryForCategory(array $input)
    {
        if (empty($this->entity) === false and optional($this->entity->merchant)->isSignupCampaign(DDConstants::EASY_ONBOARDING) === true)
        {
            return;
        }

        // If category and subcategory are not set
        if ((isset($input[Entity::BUSINESS_CATEGORY]) === false) and
            (isset($input[Entity::BUSINESS_SUBCATEGORY]) === false))
        {
            return;
        }

        $subcategory = array_key_exists(Entity::BUSINESS_SUBCATEGORY, $input) ?
                        $input[Entity::BUSINESS_SUBCATEGORY] : $this->entity->getBusinessSubcategory();

        $category = $this->extractBusinessCategory($input, $subcategory);

        // If category is `null`
        if (isset($category) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::BUSINESS_CATEGORY_MISSING_FOR_SUBCATEGORY . ': ' . $subcategory,
                Entity::BUSINESS_CATEGORY,
                [
                    Entity::BUSINESS_SUBCATEGORY => $subcategory,
                ]);
        }

        $subcategoryMap     = BusinessCategory::SUBCATEGORY_MAP;

        $validSubcategories = $subcategoryMap[$category] ?? [];

        $isError            = false;

        // If category is `others` and subcategory is not `null` and not equal to `others`
        if (($category === BusinessCategory::OTHERS) and
            (empty($subcategory) === false) and
            ($subcategory !== BusinessSubcategory::OTHERS))
        {
            $isError = true;
        }

        // If category is not `others` and subcategory is not valid
        if (($category !== BusinessCategory::OTHERS) and
            (in_array($subcategory, $validSubcategories, true) === false))
        {
            $isError = true;
        }

        if ($isError === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_BUSINESS_SUBCATEGORY_FOR_CATEGORY . ': ' . $category,
                Entity::BUSINESS_SUBCATEGORY,
                [
                    Entity::BUSINESS_CATEGORY    => $category,
                    Entity::BUSINESS_SUBCATEGORY => $subcategory,
                ]);
        }
    }

    public function validateBusinessSubcategoryForCategoryForEasyOnboarding($input)
    {
        $category = $this->extractBusinessCategory($input, null);

        $businessDetail = $this->entity->businessDetail;

        if (empty($businessDetail) === false)
        {
            $parentCategory = array_key_exists(BusinessDetailEntity::BUSINESS_PARENT_CATEGORY, $input) ?
                $input[BusinessDetailEntity::BUSINESS_PARENT_CATEGORY] : $businessDetail->getBusinessParentCategory();

            $categoryMap = BusinessCategoriesV2\BusinessParentCategory::CATEGORY_MAP;
            $validCategories = $categoryMap[$parentCategory] ?? [];

            if (empty($category) === false and in_array($category, $validCategories, true) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    self::INVALID_BUSINESS_CATEGORY_FOR_PARENT_CATEGORY);
            }
        }

        $subcategory = $this->entity->getBusinessSubcategory();

        $subcategoryMap = BusinessCategory::SUBCATEGORY_MAP;

        $validSubcategories = $subcategoryMap[$category] ?? [];

        if (empty($subcategory) === false and in_array($subcategory, $validSubcategories, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_BUSINESS_SUBCATEGORY_FOR_CATEGORY);
        }
    }

    public function validateKycAdditionalDetails(string $attribute, $value)
    {
        $merchantDetailCore = (new Core);

        if (isset($value) === false)
        {
            return;
        }

        foreach ($value as $key => $values)
        {
            if ((NeedsClarificationMetaData::isValidPredefinedAdditionalField($key) == true) and
                ($merchantDetailCore->isAdditionalFieldRequired($key) === true))
            {

                (new Validator())->validateInput("clarificationResponse", $values);
            }
            else
            {
                throw new Exception\BadRequestValidationFailureException(
                    self::ADDITIONAL_FIELD_NOT_REQUIRED . ':' . $key,
                    Merchant\Constants::FIELD_NAME,
                    [
                        Merchant\Constants::FIELD_NAME => $key
                    ]
                );

            }
        }
    }

    /**
     * Custom function for validation of NEEDS_CLARIFICATION_REASON
     *
     * @param string $attribute
     * @param        $value
     *
     */
    public function validateKYCClarificationReasons(string $attribute, $value)
    {
        (new Validator())->validateInput("kycClarificationReason", $value);
    }

    public function validateStakeholder(string $attribute, $value)
    {
        (new Merchant\Stakeholder\Validator)->validateInput("activation", $value);
    }

    public function validateMerchantAvgOrderValue(string $attribute, $value)
    {
        (new Merchant\AvgOrderValue\Validator)->validateInput("create", $value);
    }

    /**
     * @param string $attribute
     * @param        $value
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateClarificationReasons(string $attribute, $value)
    {
        if (isset($value) === false)
        {
            return;
        }

        $arrayValues = array_values($value);

        foreach ($arrayValues as $key => $values)
        {
            foreach ($values as $val)
            {
                $isReasonTypeValid = false;
                if ((is_array($val)) === true)
                {
                    if($val['reason_type'] === 'custom')
                    {
                        $isReasonTypeValid = true;
                        (new Validator)->validateInput('customClarificationReasonJsonValidation', $val);
                    }
                    if($val['reason_type'] === 'predefined')
                    {
                        $isReasonTypeValid = true;
                        (new Validator)->validateInput('predefinedClarificationReasonJsonValidation', $val);
                    }
                }
                if(!$isReasonTypeValid)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        self::INVALID_REASON_TYPE,
                        Merchant\Constants::REASON,
                        [
                            Merchant\Constants::REASON_TYPE => $value
                        ]);
                }
            }
        }
    }

    public function validateReasonCode(string $attribute, $predefinedReason)
    {
        if (NeedsClarificationReasonsList::isValidPredefinedReason($predefinedReason) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_PREDEFINED_REASON . ': ' . $predefinedReason,
                Merchant\Constants::REASON,
                [
                    Merchant\Constants::REASON => $predefinedReason
                ]);
        }
    }

    public function validateForm12aUrl($attribute, $value)
    {
        $form12aBusinessTypes = [BusinessType::SOCIETY, BusinessType::TRUST, BusinessType::NGO];
        if (in_array($this->entity->getBusinessType(), $form12aBusinessTypes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_FILE_NON_NGO_ORGANISATION_TYPE);
        }
    }

    public function validateForm80gUrl($attribute, $value)
    {
        $form80gBusinessTypes = [BusinessType::SOCIETY, BusinessType::TRUST, BusinessType::NGO];
        if (in_array($this->entity->getBusinessType(), $form80gBusinessTypes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_FILE_NON_NGO_ORGANISATION_TYPE);
        }
    }

    public function validateIsGSTEditable(array $input)
    {
        $error = false;

        if ((empty($this->entity->getGstin()) === false) and
            (isset($input[Entity::GSTIN]) === true))
        {
            $error = true;
        }

        if ((empty($this->entity->getPGstin()) === false) and
            (isset($input[Entity::P_GSTIN]) === true))
        {
            $error = true;
        }

        if ($error === true)
        {
            throw new Exception\BadRequestValidationFailureException('Cannot update GSTIN value once set');
        }
    }

    public function validateIsNotLocked()
    {
        if ($this->entity->isLocked() === true)
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED);
        }
    }

    public function validateFile($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());

        /**
         * Guess extension from mime type if getClientOriginalExtension does not exist
         */
        if (empty($extension) === true)
        {
            $extension = strtolower($file->guessExtension());
        }

        $mime = $file->getMimeType();

        if ((in_array($extension, FileType::ALLOWED_EXTENSIONS) === false) or
            (in_array($mime, FileType::ALLOWED_MIMES) === false))
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_FILE_TYPE);
        }
    }

    /**
     * Throws an exception if the activation form is not submitted
     *
     * @throws Exception\BadRequestException
     */
    public function validateActivationFormSubmitted()
    {
        $merchantDetail = $this->entity;

        if ($merchantDetail->isSubmitted() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ACTIVATION_FORM_NOT_SUBMITTED,
                Entity::SUBMITTED);
        }
    }

    /**
     * Block the merchant from updating the instant activation critical fields if the merchant is already activated.
     *
     * @param array  $input
     *
     * @param Entity $merchantDetails
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function blockInstantActivationCriticalFields(array $input)
    {
        $merchant = $this->entity->merchant;

        if ($merchant->isSignupCampaign(DDConstants::EASY_ONBOARDING) === true)
        {
            return;
        }

        $criticalInput = array_only($input, Entity::INSTANT_ACTIVATION_CRITICAL_ATTRIBUTES);

        if (($merchant->isActivated() === true) and
            (empty($criticalInput) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_MERCHANT_DETAIL_CANNOT_BE_UPDATED,
                null,
                $criticalInput);
        }
    }

    /**
     * Throws an exception if the merchant details are archived
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsNotArchived()
    {
        $merchantDetail = $this->entity;

        if ($merchantDetail->isArchived() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_UNARCHIVE_BEFORE_ACTIVATION,
                Entity::ARCHIVED_AT);
        }
    }

    /**
     * @param array $input
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function performInstantActivationValidations(array $input)
    {
        $merchantDetails = $this->entity;

        $this->validateIsNotLocked();

        // validates if the business subcategory belongs to the business category
        $this->validateBusinessSubcategoryForCategory($input);

        $merchantValidator = new Merchant\Validator;

        $merchant = $merchantDetails->merchant;

        //
        // Block a whitelisted (and hence, activated) merchant from submitting the instant activation form again.
        // However, a non activated merchant (blacklisted and greylisted merchants) can still submit the form.
        //
        $merchantValidator->validateIsNotActivated($merchant);
    }

    /**
     * This Function Skip the validation activation form lock status, as some merchant already filled up the L2 form
     * and those form will be locked, so to update through batch we are skipping lock check.
     *
     * @param array $input
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function performInstantActivationValidationsBatch(array $input)
    {
        $merchantDetails = $this->entity;

        // validates if the business subcategory belongs to the business category
        $this->validateBusinessSubcategoryForCategory($input);

        $merchantValidator = new Merchant\Validator;

        $merchant = $merchantDetails->merchant;

        //
        // Block a whitelisted (and hence, activated) merchant from submitting the instant activation form again.
        // However, a non activated merchant (blacklisted and greylisted merchants) can still submit the form.
        //
        $merchantValidator->validateIsNotActivated($merchant);
    }

    /**
     * Contains validations for full activation form (L2 activation form)
     * L1 and L2 activation form have different validations
     *
     * In L2 activation form for Blacklist flow -> merchant can't fill L2 form ,
     * no detail will be save in db and validation exception will be thrown
     *
     * @param Merchant\Entity $merchant
     *
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function validateFullActivationForm(Merchant\Entity $merchant)
    {
        $this->validateIsNotLocked();

        if ($this->entity->getActivationFlow() !== null)
        {
            $activationFlowImpl = Factory::getActivationFlowImpl($this->entity);

            $activationFlowImpl->validateFullActivationForm($merchant);
        }
    }

    /**
     * Validates international activation flow
     *
     * @param string $attribute
     * @param string $internationalActivationFlow
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateActivationFlow(string $attribute, string $activationFlow)
    {
        if (ActivationFlow::isValid($activationFlow) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid activation flow: ' . $activationFlow,
                Entity::ACTIVATION_FLOW,
                [
                    Entity::ACTIVATION_FLOW => $activationFlow
                ]);
        }
    }

    /**
     * Validates international activation flow
     *
     * @param string $attribute
     * @param string $internationalActivationFlow
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateInternationalActivationFlow(string $attribute, string $internationalActivationFlow)
    {
        if (ActivationFlow::isValid($internationalActivationFlow) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid activation flow: ' . $internationalActivationFlow,
                Entity::INTERNATIONAL_ACTIVATION_FLOW,
                [
                    Entity::INTERNATIONAL_ACTIVATION_FLOW => $internationalActivationFlow
                ]);
        }
    }

    public function validateMerchantHasRegisteredAddress()
    {
        // check that registered address is present
        if ($this->entity->hasBusinessRegisteredAddress() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCOUNT_REGISTRATION_ADDRESS_REQUIRED,
                null,
                [
                    'account_id' => $this->entity->getKey(),
                ]);
        }
    }

    public function validateBankAccountNumber($attribute, $bankAccountNumber)
    {
        $merchantDetails = $this->entity;

        if($merchantDetails->merchant->isLinkedAccount() === true)
        {
            $ifscCode      = $merchantDetails->getBankBranchIfsc();
            $accountNumber = $merchantDetails->getBankAccountNumber();

            $this->performBankAccountValidationForLinkedAccount($ifscCode, $accountNumber);
        }

        if(\RZP\Models\BankAccount\Validator::isBlacklistedAccountNumber($bankAccountNumber))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_BANK_ACCOUNT);
        }
    }

    /**
     * @param string|null $ifscCode
     * @param string|null $accountNumber
     */
    private function performBankAccountValidationForLinkedAccount(?string $ifscCode, ?string $accountNumber)
    {
        if(empty($ifscCode) === false and empty($accountNumber) === false)
        {
            $virtualAccountPrefixList = array_column(
                DetailConstants::VIRTUAL_BANK_ACCOUNTS_PREFIX, DetailConstants::ACCOUNT_PREFIX);
            $virtualIfscPrefixList = array_column(
                DetailConstants::VIRTUAL_BANK_ACCOUNTS_PREFIX, DetailConstants::IFSC_PREFIX);

            $isVirtualAccount = starts_with($accountNumber, $virtualAccountPrefixList);
            $isVirtualIfsc    = starts_with($ifscCode, $virtualIfscPrefixList);

            if($isVirtualAccount === true and $isVirtualIfsc === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VIRTUAL_BANK_ACCOUNT);
            }
        }
    }

    /**
     * This functions is to Check if activation is coming from batch flow and if it is coming from batch flow  check from
     * DB whether activation status is null or not. If activation status is null in DB, we allow activationStatus to get updated.
     * If activation status is not null in DB and it is batch flow, then skip updateActivationStatus function
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     * @param bool            $batchFlow
     *
     * @return bool
     */
    public function validateBatchFlowAndActivationStatusState(Merchant\Entity $merchant, array $input, bool $batchFlow = false):bool
    {
        $detailCore = new Merchant\Detail\Core;

        $merchantDetails = $detailCore->getMerchantDetails($merchant, $input);

        $merchantDetails->getValidator()->validateInput('activationStatus', $input);

        $currentActivationStatus = $merchantDetails->getActivationStatus();

        return (($batchFlow === true) and ($currentActivationStatus !== null) === false);
    }

    protected function validateGstinSelfServeNotInProgress()
    {
        $status = (new Merchant\Detail\Service)->getGstinSelfServeStatus()[Constants::STATUS];

        if ($status === Merchant\Detail\Constants::GSTIN_SELF_SERVE_STATUS_NOT_STARTED)
        {
            return;
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_GSTIN_SELF_SERVE_IN_PROGRESS);
    }

     /**
     * custom validation for business website to enable ipv6 and ipv4 urls
     * @param array $input
     *
     * @return void
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateBusinessWebsite(string $attribute, $value)
    {
        $this->validateURL($value, "Invalid Business website");
    }

    /**
     * custom validation for additional website to enable ipv6 and ipv4 urls
     * @param array $input
     *
     * @return void
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateAdditionalWebsite(string $attribute, $value)
    {
        $this->validateURL($value, "Invalid Additional website");
    }

    /**
     * validate any url - supports IPV6 and IPV4
     * @throws Exception\BadRequestValidationFailureException
     */
    private function validateURL($value,$message)
    {
        if(empty($value) === true)
        {
            return;
        }

        // replace underscore with '-' as this is php bug and which consider valid url as invalid if it has a underscore
        // https://bugs.php.net/bug.php?id=64948
        $value = str_replace('_', "-", $value);

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {

            $host = parse_url($value, PHP_URL_HOST);

            $ipv = trim($host, '[]'); // trim potential enclosing tags for IPV6

            if (filter_var($ipv, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false AND
                filter_var($ipv, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false)
            {
                throw new Exception\BadRequestValidationFailureException($message." ".$value);
            }
        }
    }

    public function validatePartnerActivationStatus(Merchant\Entity $merchant, bool $partnerKycFlow = false)
    {
        $partnerActivation = (new PartnerCore())->getPartnerActivation($merchant);

        $merchantDetail = $merchant->merchantDetail;

        if (!empty($partnerActivation) and ($partnerActivation->getActivationStatus() === Status::NEEDS_CLARIFICATION) and
            empty($merchantDetail->getActivationStatus()) and ($partnerKycFlow === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_FORM_UNDER_NEEDS_CLARIFICATION);
        }
    }

    public function validateAddAdditionalWebsiteConditions(Entity $merchantDetails)
    {
        $merchant = $merchantDetails->merchant;

        //check if merchant status is activated
        if($merchantDetails->getActivationStatus() != Status::ACTIVATED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED);
        }

        $businessWebsite = $merchantDetails->getWebsite();

        //check if merchant has a business website set
        $merchantDetails->getValidator()->validateMerchantHasBusinessWebsiteSet($businessWebsite);

        $keyAccess = $merchant->getHasKeyAccess();

        //check if merchant has been provided with key access
        $merchantDetails->getValidator()->validateMerchantHasKeyAccess($keyAccess);

        $additionalWebsites = $merchantDetails->getAdditionalWebsites();

        //check that no. of additional websites should not be more than 5
        if($additionalWebsites != null)
        {
            $merchantDetails->getValidator()->validateAdditionalWebsiteLimit($additionalWebsites);
        }
    }

    public function validateMerchantHasBusinessWebsiteSet($businessWebsite)
    {
        if (empty($businessWebsite) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_NOT_SET);
        }
    }

    public function validateMerchantHasKeyAccess(bool $keyAccess)
    {
        if ($keyAccess === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_NO_KEY_ACCESS);
        }
    }

    public function validateAdditionalWebsiteLimit(array $additionalWebsites)
    {
        if (sizeof($additionalWebsites) >= Constants::ADDITIONAL_DOMAIN_WHITELISTING_LIMIT)
        {
            throw new Exception\BadRequestValidationFailureException('Additional websites may not have more than 5 items');
        }
    }

    /**
     * @param $merchantId
     *
     * @return bool
     */
    public function checkIfKQUStateExperimentEnabled($merchantId): bool
    {
        return (new Merchant\Core)->isSplitzExperimentEnable(
            [
                'id'            => $merchantId,
                'experiment_id' => $this->app['config']->get('app.enable_kyc_qualified_unactivated'),
            ],
            'variables'
        );
    }
}
