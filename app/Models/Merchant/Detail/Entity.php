<?php

namespace RZP\Models\Merchant\Detail;

use App;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lib\PhoneBook;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Address;
use RZP\Models\Merchant;
use RZP\Models\Admin\Admin;
use RZP\Models\Merchant\Store;
use RZP\Constants\IndianStates;
use RZP\Constants\Country;
use RZP\Models\Merchant\AutoKyc;
use RZP\Models\ClarificationDetail;
use RZP\Exception\InvalidPermissionException;
use Illuminate\Database\Eloquent\Relations\HasOne;
use RZP\Models\Merchant\Document\OcrVerificationStatus;
use MVanDuijker\TransactionalModelEvents as TransactionalModelEvents;

/**
 * Class Entity
 *
 * @property Merchant\Entity                    $merchant
 * @property Merchant\Stakeholder\Entity        $stakeholder
 * @property Merchant\AvgOrderValue\Entity      $avgOrderValue
 * @property Merchant\Website\Entity            $merchantWebsite
 * @property Merchant\BusinessDetail\Entity     $businessDetail
 * @property Merchant\VerificationDetail\Entity $verificationDetail
 * @property Merchant\Product\Otp\Entity        $otp
 *
 * @package RZP\Models\Merchant\Detail
 */
class Entity extends Base\PublicEntity implements AutoKyc\KycEntity
{
    use TransactionalModelEvents\TransactionalAwareEvents;

    const MERCHANT_ID                        = 'merchant_id';
    const CONTACT_NAME                       = 'contact_name';
    const CONTACT_EMAIL                      = 'contact_email';
    const CONTACT_MOBILE                     = 'contact_mobile';
    const CONTACT_LANDLINE                   = 'contact_landline';
    const BUSINESS_TYPE                      = 'business_type';
    const BUSINESS_NAME                      = 'business_name';
    const BUSINESS_DESCRIPTION               = 'business_description';
    const BUSINESS_DBA                       = 'business_dba';
    const BUSINESS_WEBSITE                   = 'business_website';
    const ADDITIONAL_WEBSITES                = 'additional_websites';
    const ADDITIONAL_WEBSITE                 = 'additional_website';
    const BUSINESS_INTERNATIONAL             = 'business_international';
    const BUSINESS_PAYMENTDETAILS            = 'business_paymentdetails';
    const BUSINESS_MODEL                     = 'business_model';
    const BUSINESS_REGISTERED_ADDRESS        = 'business_registered_address';
    const BUSINESS_REGISTERED_ADDRESS_L2     = 'business_registered_address_l2';
    const BUSINESS_REGISTERED_COUNTRY        = 'business_registered_country';
    const BUSINESS_REGISTERED_STATE          = 'business_registered_state';
    const BUSINESS_REGISTERED_CITY           = 'business_registered_city';
    const BUSINESS_REGISTERED_DISTRICT       = 'business_registered_district';
    const BUSINESS_REGISTERED_PIN            = 'business_registered_pin';
    const BUSINESS_OPERATION_ADDRESS         = 'business_operation_address';
    const BUSINESS_OPERATION_ADDRESS_L2      = 'business_operation_address_l2';
    const BUSINESS_OPERATION_COUNTRY         = 'business_operation_country';
    const BUSINESS_OPERATION_STATE           = 'business_operation_state';
    const BUSINESS_OPERATION_CITY            = 'business_operation_city';
    const BUSINESS_OPERATION_DISTRICT        = 'business_operation_district';
    const BUSINESS_OPERATION_PIN             = 'business_operation_pin';
    const BUSINESS_DOE                       = 'business_doe';
    const GSTIN                              = 'gstin'; // Goods and Services Tax Identification Number
    const P_GSTIN                            = 'p_gstin';
    const COMPANY_CIN                        = 'company_cin';
    const COMPANY_PAN                        = 'company_pan';
    const COMPANY_PAN_NAME                   = 'company_pan_name';
    const BUSINESS_CATEGORY                  = 'business_category';
    const BUSINESS_SUBCATEGORY               = 'business_subcategory';
    const TRANSACTION_VOLUME                 = 'transaction_volume';
    const TRANSACTION_VALUE                  = 'transaction_value';
    const PROMOTER_PAN                       = 'promoter_pan';
    const PROMOTER_PAN_NAME                  = 'promoter_pan_name';
    const DATE_OF_BIRTH                      = 'date_of_birth';
    const BANK_NAME                          = 'bank_name';
    const BANK_ACCOUNT_NUMBER                = 'bank_account_number';
    const BANK_ACCOUNT_NAME                  = 'bank_account_name';
    const BANK_ACCOUNT_TYPE                  = 'bank_account_type';
    const BANK_BRANCH_CODE_TYPE              = 'bank_branch_code_type';
    const BANK_BRANCH_CODE                   = 'bank_branch_code';
    const INDUSTRY_CATEGORY_CODE_TYPE        = 'industry_category_code_type';
    const INDUSTRY_CATEGORY_CODE             = 'industry_category_code';
    const BANK_BRANCH                        = 'bank_branch';
    const BANK_BRANCH_IFSC                   = 'bank_branch_ifsc';
    const BANK_BENEFICIARY_ADDRESS1          = 'bank_beneficiary_address1';
    const BANK_BENEFICIARY_ADDRESS2          = 'bank_beneficiary_address2';
    const BANK_BENEFICIARY_ADDRESS3          = 'bank_beneficiary_address3';
    const BANK_BENEFICIARY_CITY              = 'bank_beneficiary_city';
    const BANK_BENEFICIARY_STATE             = 'bank_beneficiary_state';
    const BANK_BENEFICIARY_PIN               = 'bank_beneficiary_pin';
    const WEBSITE_ABOUT                      = 'website_about';
    const WEBSITE_CONTACT                    = 'website_contact';
    const WEBSITE_PRIVACY                    = 'website_privacy';
    const WEBSITE_TERMS                      = 'website_terms';
    const WEBSITE_REFUND                     = 'website_refund';
    const WEBSITE_PRICING                    = 'website_pricing';
    const WEBSITE_LOGIN                      = 'website_login';
    const BUSINESS_PROOF_URL                 = 'business_proof_url';
    const BUSINESS_OPERATION_PROOF_URL       = 'business_operation_proof_url';
    const BUSINESS_PAN_URL                   = 'business_pan_url';
    const ADDRESS_PROOF_URL                  = 'address_proof_url';
    const PROMOTER_PROOF_URL                 = 'promoter_proof_url';
    const PROMOTER_PAN_URL                   = 'promoter_pan_url';
    const PROMOTER_ADDRESS_URL               = 'promoter_address_url';
    const FORM_12A_URL                       = 'form_12a_url';
    const FORM_80G_URL                       = 'form_80g_url';
    const TRANSACTION_REPORT_EMAIL           = 'transaction_report_email';
    const COMMENT                            = 'comment';
    const ROLE                               = 'role';
    const DEPARTMENT                         = 'department';
    const STEPS_FINISHED                     = 'steps_finished';
    const ACTIVATION_PROGRESS                = 'activation_progress';
    const LOCKED                             = 'locked';
    const ACTIVATION_STATUS                  = 'activation_status';
    const BANK_DETAILS_VERIFICATION_STATUS   = 'bank_details_verification_status';
    const POI_VERIFICATION_STATUS            = 'poi_verification_status';
    const COMPANY_PAN_VERIFICATION_STATUS    = 'company_pan_verification_status';
    const POA_VERIFICATION_STATUS            = 'poa_verification_status';
    const CIN_VERIFICATION_STATUS            = 'cin_verification_status';
    const CLARIFICATION_MODE                 = 'clarification_mode';
    const ARCHIVED_AT                        = 'archived_at';
    const REVIEWER_ID                        = 'reviewer_id';
    const ISSUE_FIELDS                       = 'issue_fields';
    const ISSUE_FIELDS_REASON                = 'issue_fields_reason';
    const INTERNAL_NOTES                     = 'internal_notes';
    const CUSTOM_FIELDS                      = 'custom_fields';
    const CLIENT_APPLICATIONS                = 'client_applications';
    const MARKETPLACE_ACTIVATION_STATUS      = 'marketplace_activation_status';
    const VIRTUAL_ACCOUNTS_ACTIVATION_STATUS = 'virtual_accounts_activation_status';
    const SUBSCRIPTIONS_ACTIVATION_STATUS    = 'subscriptions_activation_status';
    const QR_CODES_ACTIVATION_STATUS         = 'qr_codes_activation_status';
    const SUBMITTED                          = 'submitted';
    const SUBMITTED_AT                       = 'submitted_at';
    const CREATED_AT                         = 'created_at';
    const UPDATED_AT                         = 'updated_at';
    const AUDIT_ID                           = 'audit_id';
    const COUPON_CODE                        = 'coupon_code';
    const REFERRAL_CODE                      = 'referral_code';
    const FUND_ACCOUNT_VALIDATION_ID         = 'fund_account_validation_id';
    const GSTIN_VERIFICATION_STATUS          = 'gstin_verification_status';

    const PERSONAL_PAN_DOC_VERIFICATION_STATUS = 'personal_pan_doc_verification_status';
    const COMPANY_PAN_DOC_VERIFICATION_STATUS  = 'company_pan_doc_verification_status';
    const BANK_DETAILS_DOC_VERIFICATION_STATUS = 'bank_details_doc_verification_status';
    const MSME_DOC_VERIFICATION_STATUS         = 'msme_doc_verification_status';

    const SUBMIT                                   = 'submit';
    const ARCHIVE                                  = 'archive';
    const ARCHIVED                                 = 'archived';
    const REJECTION_REASONS                        = 'rejection_reasons';
    const ALLOWED_NEXT_ACTIVATION_STATUSES         = 'allowed_next_activation_statuses';
    const VERIFICATION                             = 'verification';
    const CAN_SUBMIT                               = 'can_submit';
    const REVIEWER                                 = 'reviewer';
    const MERCHANTS                                = 'merchants';
    const ACTIVATION_FLOW                          = 'activation_flow';
    const INTERNATIONAL_ACTIVATION_FLOW            = 'international_activation_flow';
    const LIVE_TRANSACTION_DONE                    = 'live_transaction_done';
    const KYC_CLARIFICATION_REASONS                = 'kyc_clarification_reasons';
    const KYC_ADDITIONAL_DETAILS                   = 'kyc_additional_details';
    const CLARIFICATION_REASONS                    = 'clarification_reasons';
    const CLARIFICATION_REASONS_V2                 = 'clarification_reasons_v2';
    const ADDITIONAL_DETAILS                       = 'additional_details';
    const KYC_ID                                   = 'kyc_id';
    const ESTD_YEAR                                = 'estd_year';
    const DATE_OF_ESTABLISHMENT                    = 'date_of_establishment';
    const AUTHORIZED_SIGNATORY_RESIDENTIAL_ADDRESS = 'authorized_signatory_residential_address';
    const AUTHORIZED_SIGNATORY_DOB                 = 'authorized_signatory_dob';
    const PLATFORM                                 = 'platform';
    const PENNY_TESTING_UPDATED_AT                 = 'penny_testing_updated_at';
    const SHOP_ESTABLISHMENT_NUMBER                = 'shop_establishment_number';
    const SHOP_ESTABLISHMENT_VERIFICATION_STATUS   = 'shop_establishment_verification_status';
    const REJECTION_OPTION                         = 'rejection_option';

    const BUSINESS_SUGGESTED_PIN     = 'business_suggested_pin';
    const BUSINESS_SUGGESTED_ADDRESS = 'business_suggested_address';

    const FRAUD_TYPE = 'fraud_type';


    // key used to for iec code when creating merchant's with specific purpose code
    const IEC_CODE = 'iec_code';

    //merchant's business banking id generated from banking account service.
    const BAS_BUSINESS_ID = 'bas_business_id';

    const ACTIVATION_FORM_MILESTONE = 'activation_form_milestone';

    const BUSINESS_NAME_SUGGESTED       = 'business_name_suggested';
    const PROMOTER_PAN_NAME_SUGGESTED   = 'promoter_pan_name_suggested';

    // relation name
    const STAKEHOLDER                   = 'stakeholder';
    const MERCHANT_AVG_ORDER_VALUE      = 'merchant_avg_order_value';
    const MERCHANT_WEBSITE              = 'merchant_website';
    const MERCHANT_VERIFICATION_DETAIL  = 'merchant_verification_detail';
    const CLARIFICATION_DETAIL          = 'clarification_detail';
    const MERCHANT_BUSINESS_DETAIL      = 'merchant_business_detail';
    const MERCHANT_OTP_VERIFICATION_LOG = 'merchant_otp_verification_log';
    // fields_pending field is used in new Account APIs.
    const FIELDS_PENDING = 'fields_pending';

    // required_fields is used in older APIs
    const REQUIRED_FIELDS = 'required_fields';

    // Enum values used for product activation status
    const PENDING  = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';

    // Details for PROMO campaign for onboarding.
    const PROMO_COUPON_CODE = 'SURGESEPT';

    // For mailers
    const ACTIVATION_DURATION = '4-5 working days';

    // Other general use input constants
    const FILE = 'file';

    const SEND_ACTIVATION_EMAIL = 'send_activation_email';

    //Config to store virtual account ids for fund addition
    const FUND_ADDITION_VA_IDS = 'fund_addition_va_ids';
    protected $entity     = 'merchant_detail';

    protected $primaryKey = self::MERCHANT_ID;

    protected $fillable   = [
        self::ACTIVATION_FLOW,
        self::ACTIVATION_PROGRESS,
        self::CONTACT_NAME,
        self::CONTACT_EMAIL,
        self::CONTACT_MOBILE,
        self::CONTACT_LANDLINE,
        self::BUSINESS_TYPE,
        self::BUSINESS_NAME,
        self::BUSINESS_DESCRIPTION,
        self::BUSINESS_DBA,
        self::BUSINESS_WEBSITE,
        self::BUSINESS_INTERNATIONAL,
        self::BUSINESS_PAYMENTDETAILS,
        self::BUSINESS_MODEL,
        self::BUSINESS_REGISTERED_ADDRESS,
        self::BUSINESS_REGISTERED_ADDRESS_L2,
        self::BUSINESS_REGISTERED_COUNTRY,
        self::BUSINESS_REGISTERED_STATE,
        self::BUSINESS_REGISTERED_CITY,
        self::BUSINESS_REGISTERED_DISTRICT,
        self::BUSINESS_REGISTERED_PIN,
        self::BUSINESS_OPERATION_ADDRESS,
        self::BUSINESS_OPERATION_ADDRESS_L2,
        self::BUSINESS_OPERATION_COUNTRY,
        self::BUSINESS_OPERATION_STATE,
        self::BUSINESS_OPERATION_CITY,
        self::BUSINESS_OPERATION_DISTRICT,
        self::BUSINESS_OPERATION_PIN,
        self::BUSINESS_DOE,
        self::GSTIN,
        self::P_GSTIN,
        self::COMPANY_CIN,
        self::COMPANY_PAN,
        self::COMPANY_PAN_NAME,
        self::BUSINESS_CATEGORY,
        self::BUSINESS_SUBCATEGORY,
        self::TRANSACTION_VOLUME,
        self::TRANSACTION_VALUE,
        self::PROMOTER_PAN,
        self::PROMOTER_PAN_NAME,
        self::BANK_NAME,
        self::BANK_ACCOUNT_NUMBER,
        self::BANK_ACCOUNT_NAME,
        self::BANK_ACCOUNT_TYPE,
        self::BANK_BRANCH_CODE_TYPE,
        self::BANK_BRANCH_CODE,
        self::BANK_BRANCH,
        self::BANK_BRANCH_IFSC,
        self::BANK_BENEFICIARY_ADDRESS1,
        self::BANK_BENEFICIARY_ADDRESS2,
        self::BANK_BENEFICIARY_ADDRESS3,
        self::BANK_BENEFICIARY_CITY,
        self::BANK_BENEFICIARY_STATE,
        self::BANK_BENEFICIARY_PIN,
        self::WEBSITE_ABOUT,
        self::WEBSITE_CONTACT,
        self::WEBSITE_PRIVACY,
        self::WEBSITE_TERMS,
        self::WEBSITE_REFUND,
        self::WEBSITE_PRICING,
        self::WEBSITE_LOGIN,
        self::BUSINESS_PROOF_URL,
        self::BUSINESS_OPERATION_PROOF_URL,
        self::BUSINESS_PAN_URL,
        self::ADDRESS_PROOF_URL,
        self::PROMOTER_PROOF_URL,
        self::PROMOTER_PAN_URL,
        self::PROMOTER_ADDRESS_URL,
        self::FORM_12A_URL,
        self::FORM_80G_URL,
        self::TRANSACTION_REPORT_EMAIL,
        self::ROLE,
        self::DEPARTMENT,
        self::COMMENT,
        self::STEPS_FINISHED,
        self::LOCKED,
        self::ACTIVATION_STATUS,
        self::CLARIFICATION_MODE,
        self::ARCHIVED_AT,
        self::ISSUE_FIELDS,
        self::ISSUE_FIELDS_REASON,
        self::INTERNAL_NOTES,
        self::MARKETPLACE_ACTIVATION_STATUS,
        self::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS,
        self::SUBSCRIPTIONS_ACTIVATION_STATUS,
        self::SUBMITTED,
        self::SUBMITTED_AT,
        self::INTERNATIONAL_ACTIVATION_FLOW,
        self::CUSTOM_FIELDS,
        self::LIVE_TRANSACTION_DONE,
        self::DATE_OF_BIRTH,
        self::KYC_CLARIFICATION_REASONS,
        self::KYC_ADDITIONAL_DETAILS,
        self::BANK_DETAILS_VERIFICATION_STATUS,
        self::POA_VERIFICATION_STATUS,
        self::ADDITIONAL_WEBSITES,
        self::ESTD_YEAR,
        self::AUTHORIZED_SIGNATORY_RESIDENTIAL_ADDRESS,
        self::AUTHORIZED_SIGNATORY_DOB,
        self::PLATFORM,
        self::DATE_OF_ESTABLISHMENT,
        self::ACTIVATION_FORM_MILESTONE,
        self::SHOP_ESTABLISHMENT_NUMBER,
        self::CLIENT_APPLICATIONS,
        self::BUSINESS_SUGGESTED_PIN,
        self::BUSINESS_SUGGESTED_ADDRESS,
        self::FRAUD_TYPE,
        self::IEC_CODE,
        self::AUDIT_ID,
        self::INDUSTRY_CATEGORY_CODE_TYPE,
        self::INDUSTRY_CATEGORY_CODE,

        self::POI_VERIFICATION_STATUS,
        self::POA_VERIFICATION_STATUS,
        self::GSTIN_VERIFICATION_STATUS,
        self::CIN_VERIFICATION_STATUS,
        self::COMPANY_PAN_VERIFICATION_STATUS,
        self::PERSONAL_PAN_DOC_VERIFICATION_STATUS,
        self::COMPANY_PAN_DOC_VERIFICATION_STATUS,
        self::SHOP_ESTABLISHMENT_VERIFICATION_STATUS,
        self::MSME_DOC_VERIFICATION_STATUS,
        self::BANK_DETAILS_DOC_VERIFICATION_STATUS
    ];

    protected $public     = [
        self::CONTACT_NAME,
        self::CONTACT_EMAIL,
        self::CONTACT_MOBILE,
        self::CONTACT_LANDLINE,
        self::BUSINESS_TYPE,
        self::BUSINESS_NAME,
        self::BUSINESS_DESCRIPTION,
        self::BUSINESS_DBA,
        self::BUSINESS_WEBSITE,
        self::BUSINESS_INTERNATIONAL,
        self::BUSINESS_PAYMENTDETAILS,
        self::BUSINESS_REGISTERED_ADDRESS,
        self::BUSINESS_REGISTERED_ADDRESS_L2,
        self::BUSINESS_REGISTERED_COUNTRY,
        self::BUSINESS_REGISTERED_STATE,
        self::BUSINESS_REGISTERED_CITY,
        self::BUSINESS_REGISTERED_DISTRICT,
        self::BUSINESS_REGISTERED_PIN,
        self::BUSINESS_OPERATION_ADDRESS,
        self::BUSINESS_OPERATION_ADDRESS_L2,
        self::BUSINESS_OPERATION_COUNTRY,
        self::BUSINESS_OPERATION_STATE,
        self::BUSINESS_OPERATION_CITY,
        self::BUSINESS_OPERATION_DISTRICT,
        self::BUSINESS_OPERATION_PIN,
        self::PROMOTER_PAN,
        self::PROMOTER_PAN_NAME,
        self::BUSINESS_DOE,
        self::GSTIN,
        self::P_GSTIN,
        self::COMPANY_CIN,
        self::COMPANY_PAN,
        self::COMPANY_PAN_NAME,
        self::BUSINESS_CATEGORY,
        self::BUSINESS_SUBCATEGORY,
        self::BUSINESS_MODEL,
        self::TRANSACTION_VOLUME,
        self::TRANSACTION_VALUE,
        self::WEBSITE_ABOUT,
        self::WEBSITE_CONTACT,
        self::WEBSITE_PRIVACY,
        self::WEBSITE_TERMS,
        self::WEBSITE_REFUND,
        self::WEBSITE_PRICING,
        self::WEBSITE_LOGIN,
        self::STEPS_FINISHED,
        self::ACTIVATION_PROGRESS,
        self::LOCKED,
        self::ACTIVATION_STATUS,
        self::BANK_DETAILS_VERIFICATION_STATUS,
        self::POA_VERIFICATION_STATUS,
        self::POI_VERIFICATION_STATUS,
        self::CLARIFICATION_MODE,
        self::ARCHIVED,
        self::ALLOWED_NEXT_ACTIVATION_STATUSES,
        self::REVIEWER_ID,
        self::REVIEWER,
        self::ISSUE_FIELDS,
        self::ISSUE_FIELDS_REASON,
        self::INTERNAL_NOTES,
        self::MARKETPLACE_ACTIVATION_STATUS,
        self::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS,
        self::SUBSCRIPTIONS_ACTIVATION_STATUS,
        self::SUBMITTED,
        self::SUBMITTED_AT,
        self::TRANSACTION_REPORT_EMAIL,
        self::BANK_ACCOUNT_NUMBER,
        self::BANK_ACCOUNT_NAME,
        self::BANK_ACCOUNT_TYPE,
        self::BANK_BRANCH,
        self::BANK_BRANCH_IFSC,
        self::BANK_BRANCH_CODE,
        self::BANK_BRANCH_CODE_TYPE,
        self::BANK_BENEFICIARY_ADDRESS1,
        self::BANK_BENEFICIARY_ADDRESS2,
        self::BANK_BENEFICIARY_ADDRESS3,
        self::BANK_BENEFICIARY_CITY,
        self::BANK_BENEFICIARY_STATE,
        self::BANK_BENEFICIARY_PIN,
        self::ROLE,
        self::DEPARTMENT,
        self::STAKEHOLDER,
        self::MERCHANT_AVG_ORDER_VALUE,
        self::MERCHANT_WEBSITE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::ACTIVATION_FLOW,
        self::INTERNATIONAL_ACTIVATION_FLOW,
        self::LIVE_TRANSACTION_DONE,
        self::KYC_CLARIFICATION_REASONS,
        self::KYC_ADDITIONAL_DETAILS,
        self::ADDITIONAL_WEBSITES,
        self::ESTD_YEAR,
        self::AUTHORIZED_SIGNATORY_RESIDENTIAL_ADDRESS,
        self::AUTHORIZED_SIGNATORY_DOB,
        self::PLATFORM,
        self::FUND_ACCOUNT_VALIDATION_ID,
        self::GSTIN_VERIFICATION_STATUS,
        self::DATE_OF_ESTABLISHMENT,
        self::ACTIVATION_FORM_MILESTONE,
        self::COMPANY_PAN_VERIFICATION_STATUS,
        self::CIN_VERIFICATION_STATUS,
        self::COMPANY_PAN_DOC_VERIFICATION_STATUS,
        self::PERSONAL_PAN_DOC_VERIFICATION_STATUS,
        self::BANK_DETAILS_DOC_VERIFICATION_STATUS,
        self::MSME_DOC_VERIFICATION_STATUS,
        self::SHOP_ESTABLISHMENT_NUMBER,
        self::SHOP_ESTABLISHMENT_VERIFICATION_STATUS,
        self::CLIENT_APPLICATIONS,
        self::BUSINESS_SUGGESTED_PIN,
        self::BUSINESS_SUGGESTED_ADDRESS,
        self::FRAUD_TYPE,
        self::BAS_BUSINESS_ID,
        self::IEC_CODE,
        self::MERCHANT_OTP_VERIFICATION_LOG,
        self::PROMOTER_PAN_NAME_SUGGESTED,
        self::BUSINESS_NAME_SUGGESTED,
        self::INDUSTRY_CATEGORY_CODE,
        self::INDUSTRY_CATEGORY_CODE_TYPE,
    ];

    protected $defaults   = [
        self::SUBMITTED_AT        => null,
        self::ACTIVATION_PROGRESS => 0,
        self::GSTIN               => null,
        self::P_GSTIN             => null,
        self::ADDITIONAL_WEBSITES => [],
        self::FUND_ADDITION_VA_IDS => null,
    ];

    protected $casts      = [
        self::LOCKED                    => 'bool',
        self::SUBMITTED                 => 'bool',
        self::BUSINESS_INTERNATIONAL    => 'bool',
        self::ACTIVATION_PROGRESS       => 'int',
        self::KYC_CLARIFICATION_REASONS => 'array',
        self::KYC_ADDITIONAL_DETAILS    => 'array',
        self::ADDITIONAL_WEBSITES       => 'array',
    ];

    const UPLOADED_FIELDS = [
        self::PROMOTER_PAN_URL,
        self::BUSINESS_PAN_URL,
        self::ADDRESS_PROOF_URL,
        self::PROMOTER_PROOF_URL,
        self::BUSINESS_PROOF_URL,
        self::PROMOTER_ADDRESS_URL,
        self::BUSINESS_OPERATION_PROOF_URL,
    ];

    const GST_FIELDS = [
        self::GSTIN,
        self::P_GSTIN
    ];

    protected $eventFields     = [
        self::BUSINESS_NAME,
        self::CONTACT_NAME,
        self::CONTACT_EMAIL,
        self::CONTACT_MOBILE,
        self::BUSINESS_TYPE,
        self::TRANSACTION_VOLUME,
        self::BUSINESS_REGISTERED_CITY,
        self::BUSINESS_REGISTERED_STATE,
        self::BUSINESS_OPERATION_CITY,
        self::BUSINESS_OPERATION_STATE,
    ];

    protected $publicSetters   = [
        self::REVIEWER,
        self::REVIEWER_ID,
        self::ARCHIVED_AT,
        self::ALLOWED_NEXT_ACTIVATION_STATUSES,
    ];

    protected $adminOnlyPublic = [
        self::REVIEWER,
        self::REVIEWER_ID,
        self::ISSUE_FIELDS,
        self::INTERNAL_NOTES,
        self::ISSUE_FIELDS_REASON,
    ];

    protected static $modifiers = [
        'bank_branch_input',
        'contact_mobile'
    ];

    /**
     * Attributes that are critical in the instant activations flow.
     *
     * The merchant will not be allowed to edit these fields once instantly activated. These fields will be blocked
     * while filling the complete KYC form.
     */
    const INSTANT_ACTIVATION_CRITICAL_ATTRIBUTES = [
        self::BUSINESS_CATEGORY,
        self::BUSINESS_SUBCATEGORY,
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity', self::MERCHANT_ID, 'id');
    }

    /**
     * Every detail entity will have one stakeholder entity to start with to store the promoter attributes
     *
     * @return HasOne
     */
    public function stakeholder()
    {
        return $this->hasOne('RZP\Models\Merchant\Stakeholder\Entity', self::MERCHANT_ID, self::MERCHANT_ID);
    }

    /**
     * Every detail entity will have one avg_order_value entity to start with to store the aov details
     *
     * @return HasOne
     */
    public function avgOrderValue()
    {
        return $this->hasOne('RZP\Models\Merchant\AvgOrderValue\Entity', self::MERCHANT_ID, self::MERCHANT_ID);
    }

    /**
     * Every detail entity will have one merchant_website entity to start with to store the website section details
     *
     * @return HasOne
     */
    public function merchantWebsite()
    {
        return $this->hasOne('RZP\Models\Merchant\Website\Entity', self::MERCHANT_ID, self::MERCHANT_ID);
    }


    /**
     * Every detail entity will have one verification_detail entity to start with to store the bvs verification details
     *
     * @return HasMany
     */
    public function verificationDetail()
    {
        return $this->hasMany('RZP\Models\Merchant\VerificationDetail\Entity', self::MERCHANT_ID, self::MERCHANT_ID);
    }

    public function businessDetail()
    {
        return $this->hasOne('RZP\Models\Merchant\BusinessDetail\Entity', self::MERCHANT_ID, self::MERCHANT_ID);
    }

    public function otpVerificationLog()
    {
        return $this->hasOne('RZP\Models\Merchant\Product\Otp\Entity' , self::MERCHANT_ID, self::MERCHANT_ID);
    }

    public function getReviewer(){
        // https://tomgrohl.medium.com/how-to-not-load-null-relations-in-laravel-5-dbfaedf56df2
        // even if reviewer_id is null, $this->reviewer will make an unnecessary query
        // select * from `admins` where `admins`.`id` is null and `admins`.`deleted_at` is null limit 1`
        if ($this->getAttribute(self::REVIEWER_ID) === null)
        {
            return null;
        }
        else
        {
            return $this->reviewer;
        }
    }

    public function reviewer()
    {
        return $this->belongsTo(Admin\Entity::class);
    }

    public function isLocked()
    {
        return ($this->getAttribute(self::LOCKED) === true);
    }

    public function setLocked(bool $locked)
    {
        $this->setAttribute(self::LOCKED, $locked);
    }

    public function getWebsite()
    {
        return $this->getAttribute(self::BUSINESS_WEBSITE);
    }

    public function setWebsite(?string $businessWebsite)
    {
        $this->setAttribute(self::BUSINESS_WEBSITE, $businessWebsite);
    }

    public function getSubmittedAt()
    {
        return $this->getAttribute(self::SUBMITTED_AT);
    }


    public function isSubmitted()
    {
        return ($this->getAttribute(self::SUBMITTED) === true);
    }

    public function isArchived()
    {
        return ($this->isAttributeNotNull(self::ARCHIVED_AT));
    }

    public function setArchivedAt($archived_at)
    {
        $this->setAttribute(self::ARCHIVED_AT, $archived_at);
    }

    public function hasBankAccountDetails(): bool
    {
        $ifscCode      = $this->getBankBranchIfsc();
        $accountNumber = $this->getBankAccountNumber();

        return ((empty($accountNumber) === false) and (empty($ifscCode) === false));
    }

    protected function setPublicArchivedAtAttribute(array &$array)
    {
        $array[self::ARCHIVED] = (isset($array[self::ARCHIVED_AT]) === true) ? 1 : 0;

        unset($array[self::ARCHIVED_AT]);
    }

    protected function setPublicAllowedNextActivationStatusesAttribute(array &$array)
    {
        $activationStatus = $this->getActivationStatus();

        $allowedNextActivationStatuses = [];

        $app = App::getFacadeRoot();

        if (empty($activationStatus) === false)
        {
            $allowedNextActivationStatuses = ((new Validator())->checkIfKQUStateExperimentEnabled($this->merchant->getId()) === true) ? Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING_WITH_KQU[$activationStatus] : Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING[$activationStatus];
        }

        $array[self::ALLOWED_NEXT_ACTIVATION_STATUSES] = $allowedNextActivationStatuses;
    }

    public function getActivationStatus()
    {
        return $this->getAttribute(self::ACTIVATION_STATUS);
    }

    public function getActivationFormMilestone()
    {
        return $this->getAttribute(self::ACTIVATION_FORM_MILESTONE);
    }

    public function setActivationFormMilestone($milestone)
    {
        $this->setAttribute(self::ACTIVATION_FORM_MILESTONE, $milestone);
    }

    public function getBankAccountName()
    {
        return $this->getAttribute(self::BANK_ACCOUNT_NAME);
    }

    public function setBankAccountName($bankAccountName)
    {
        $this->setAttribute(self::BANK_ACCOUNT_NAME, $bankAccountName);
    }

    public function getBankAccountNumber()
    {
        return $this->getAttribute(self::BANK_ACCOUNT_NUMBER);
    }

    public function getBankBranchIfsc()
    {
        return $this->getAttribute(self::BANK_BRANCH_IFSC);
    }

    public function getBankBranchCode()
    {
        return $this->getAttribute(self::BANK_BRANCH_CODE);
    }

    public function getBankBranchCodeType()
    {
        return $this->getAttribute(self::BANK_BRANCH_CODE_TYPE);
    }

    public function getIndustryCategoryCode()
    {
        return $this->getAttribute(self::INDUSTRY_CATEGORY_CODE);
    }

    public function getIndustryCategoryCodeType()
    {
        return $this->getAttribute(self::INDUSTRY_CATEGORY_CODE_TYPE);
    }

    public function getCompanyCin()
    {
        return $this->getAttribute(self::COMPANY_CIN);
    }

    public function getGstin()
    {
        return $this->getAttribute(self::GSTIN);
    }

    // @codingStandardsIgnoreLine
    public function getPGstin()
    {
        return $this->getAttribute(self::P_GSTIN);
    }

    public function getPan()
    {
        return $this->getAttribute(self::COMPANY_PAN);
    }

    public function getPanName()
    {
        return $this->getAttribute(self::COMPANY_PAN_NAME);
    }

    public function setPanName($panName)
    {
        $this->setAttribute(self::COMPANY_PAN_NAME, $panName);
    }

    public function getPromoterPan()
    {
        return $this->getAttribute(self::PROMOTER_PAN);
    }

    public function getPromoterPanName()
    {
        return $this->getAttribute(self::PROMOTER_PAN_NAME);
    }

    public function setPromoterPanName($promoterPanName)
    {
        $this->setAttribute(self::PROMOTER_PAN_NAME, $promoterPanName);
    }

    public function getBusinessProofFile()
    {
        return $this->getAttribute(self::BUSINESS_PROOF_URL);
    }

    public function getAddressProofFile()
    {
        return $this->getAttribute(self::ADDRESS_PROOF_URL);
    }

    public function getBusinessRegisteredAddress()
    {
        return $this->getAttribute(self::BUSINESS_REGISTERED_ADDRESS);
    }

    public function setBusinessRegisteredPin(string $businessPin)
    {
        return $this->setAttribute(self::BUSINESS_REGISTERED_PIN, $businessPin);
    }

    public function setBusinessRegisteredAddress(string $businessAddress)
    {
        return $this->setAttribute(self::BUSINESS_REGISTERED_ADDRESS, $businessAddress);
    }

    public function getBusinessRegisteredAddressLine2()
    {
        return $this->getAttribute(self::BUSINESS_REGISTERED_ADDRESS_L2);
    }

    public function getBusinessRegisteredAddressAsText(string $delimiter = PHP_EOL)
    {
        return Address\Utility::formatAddressAsText(
            $this->getBusinessAddress(),
            $delimiter);
    }

    public function getBusinessAddress(): array
    {
        return [
            Address\Entity::LINE1   => $this->getBusinessRegisteredAddress(),
            Address\Entity::LINE2   => $this->getBusinessRegisteredAddressLine2(),
            Address\Entity::CITY    => $this->getBusinessRegisteredCity(),
            Address\Entity::STATE   => $this->getBusinessRegisteredStateName(),
            Address\Entity::COUNTRY => 'India',
            Address\Entity::ZIPCODE => $this->getBusinessRegisteredPin(),
        ];
    }

    public function hasBusinessRegisteredAddress(): bool
    {
        $city  = $this->getBusinessRegisteredCity();
        $state = $this->getBusinessRegisteredStateName();

        return ((empty($city) === false) and (empty($state) === false));
    }

    public function hasBusinessOperationAddress(): bool
    {
        $city  = $this->getBusinessOperationCity();
        $state = $this->getBusinessOperationStateName();

        return ((empty($city) === false) and (empty($state) === false));
    }

    public function getBusinessRegisteredCity()
    {
        return $this->getAttribute(self::BUSINESS_REGISTERED_CITY);
    }

    public function getBusinessRegisteredDistrict()
    {
        return $this->getAttribute(self::BUSINESS_REGISTERED_DISTRICT);
    }

    public function getBusinessRegisteredState()
    {
        return $this->getAttribute(self::BUSINESS_REGISTERED_STATE);
    }

    public function getBusinessRegisteredCountry()
    {
        return $this->getAttribute(self::BUSINESS_REGISTERED_COUNTRY);
    }

    public function getBusinessRegisteredStateName()
    {
        $state     = $this->getBusinessRegisteredState();
        $stateName = $state !== null ? IndianStates::getStateNameByCode($state) : null;

        return $stateName !== null ? ucwords(strtolower($stateName)) : null;
    }

    public function getBusinessOperationStateName()
    {
        $state     = $this->getBusinessOperationState();
        $stateName = $state !== null ? IndianStates::getStateNameByCode($state) : null;

        return $stateName !== null ? ucwords(strtolower($stateName)) : null;
    }

    public function getBusinessRegisteredPin()
    {
        return $this->getAttribute(self::BUSINESS_REGISTERED_PIN);
    }

    public function getBusinessOperationAddress()
    {
        return $this->getAttribute(self::BUSINESS_OPERATION_ADDRESS);
    }

    public function getBusinessOperationAddressLine2()
    {
        return $this->getAttribute(self::BUSINESS_OPERATION_ADDRESS_L2);
    }

    public function getBusinessOperationCity()
    {
        return $this->getAttribute(self::BUSINESS_OPERATION_CITY);
    }

    public function getBusinessOperationDistrict()
    {
        return $this->getAttribute(self::BUSINESS_OPERATION_DISTRICT);
    }

    public function getBusinessOperationState()
    {
        return $this->getAttribute(self::BUSINESS_OPERATION_STATE);
    }

    public function getBusinessOperationCountry()
    {
        return $this->getAttribute(self::BUSINESS_OPERATION_COUNTRY);
    }

    public function getBusinessOperationPin()
    {
        return $this->getAttribute(self::BUSINESS_OPERATION_PIN);
    }

    public function getBusinessStateCode()
    {
        $gstin = $this->getGstin() ?? $this->getPGstin();

        return self::getStateCodeFromGstin($gstin);
    }

    public static function getStateCodeFromGstin(string $gstin = null)
    {
        if (empty($gstin) === true)
        {
            return null;
        }

        return substr($gstin, 0, 2);
    }

    public function getBankDetailsVerificationStatus()
    {
        return $this->getAttribute(self::BANK_DETAILS_VERIFICATION_STATUS);
    }

    public function setContactName($name)
    {
        $this->setAttribute(self::CONTACT_NAME, $name);
    }

    public function setBankDetailsVerificationStatus(string $bankDetailsVerificationStatus)
    {
        $this->setAttribute(self::BANK_DETAILS_VERIFICATION_STATUS, $bankDetailsVerificationStatus);
    }

    public function setContactEmail($email)
    {
        $this->setAttribute(self::CONTACT_EMAIL, $email);
    }

    public function setActivationFlow(string $activationFlow = null)
    {
        $this->setAttribute(self::ACTIVATION_FLOW, $activationFlow);
    }

    public function setPoaVerificationStatus(?string $poaVerificationStatus)
    {
        $this->setAttribute(self::POA_VERIFICATION_STATUS, $poaVerificationStatus);
    }

    public function getPoaVerificationStatus()
    {
        return $this->getAttribute(self::POA_VERIFICATION_STATUS);
    }

    public function isPoaVerified(): bool
    {
        return ($this->getPoaVerificationStatus() === OcrVerificationStatus::VERIFIED);
    }

    public function isCinVerified(): bool
    {
        return ($this->getCinVerificationStatus() === CinVerificationStatus::VERIFIED);
    }

    public function isPoiVerified(): bool
    {
        return ($this->getPoiVerificationStatus() === POIStatus::VERIFIED);
    }

    public function isBankDetailStatusVerified(): bool
    {
        return ($this->getBankDetailsVerificationStatus() === BankDetailsVerificationStatus::VERIFIED);
    }

    public function getActivationFlow()
    {
        return $this->getAttribute(self::ACTIVATION_FLOW);
    }

    public function setInternationalActivationFlow(string $internationalActivationFlow = null)
    {
        $this->setAttribute(self::INTERNATIONAL_ACTIVATION_FLOW, $internationalActivationFlow);
    }

    public function getInternationalActivationFlow()
    {
        return $this->getAttribute(self::INTERNATIONAL_ACTIVATION_FLOW);
    }

    public function getPoiVerificationStatus()
    {
        return $this->getAttribute(self::POI_VERIFICATION_STATUS);
    }

    public function getPersonalPanDocVerificationStatus()
    {
        return $this->getAttribute(self::PERSONAL_PAN_DOC_VERIFICATION_STATUS);
    }

    public function getMsmeDocVerificationStatus()
    {
        return $this->getAttribute(self::MSME_DOC_VERIFICATION_STATUS);
    }

    public function setMsmeDocVerificationStatus(?string $status = null)
    {
        return $this->setAttribute(self::MSME_DOC_VERIFICATION_STATUS, $status);
    }

    public function getCompanyPanDocVerificationStatus()
    {
        return $this->getAttribute(self::COMPANY_PAN_DOC_VERIFICATION_STATUS);
    }

    public function setPersonalPanDocVerificationStatus(?string $status = null)
    {
        return $this->setAttribute(self::PERSONAL_PAN_DOC_VERIFICATION_STATUS, $status);
    }

    public function setCompanyPanDocVerificationStatus(?string $status = null)
    {
        return $this->setAttribute(self::COMPANY_PAN_DOC_VERIFICATION_STATUS, $status);
    }

    public function setBankDetailsDocVerificationStatus(?string $status = null)
    {
        return $this->setAttribute(self::BANK_DETAILS_DOC_VERIFICATION_STATUS, $status);
    }

    public function getBankDetailsDocVerificationStatus()
    {
        return $this->getAttribute(self::BANK_DETAILS_DOC_VERIFICATION_STATUS);
    }

    public function setPoiVerificationStatus(string $status = null)
    {
        return $this->setAttribute(self::POI_VERIFICATION_STATUS, $status);
    }

    public function getCompanyPanVerificationStatus()
    {
        return $this->getAttribute(self::COMPANY_PAN_VERIFICATION_STATUS);
    }

    public function setCompanyPanVerificationStatus(string $status = null)
    {
        return $this->setAttribute(self::COMPANY_PAN_VERIFICATION_STATUS, $status);
    }

    public function getCinVerificationStatus()
    {
        return $this->getAttribute(self::CIN_VERIFICATION_STATUS);
    }

    public function setCinVerificationStatus(string $status = null)
    {
        return $this->setAttribute(self::CIN_VERIFICATION_STATUS, $status);
    }

    public function setActivationProgress($activationProgress)
    {
        $this->setAttribute(self::ACTIVATION_PROGRESS, $activationProgress);
    }

    public function setMarketplaceActivationStatus(string $status)
    {
        $this->setAttribute(self::MARKETPLACE_ACTIVATION_STATUS, $status);
    }

    public function setVirtualAccountsActivationStatus(string $status)
    {
        $this->setAttribute(self::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS, $status);
    }

    public function setQrCodesActivationStatus(string $status)
    {
        // There's no column 'qr_codes_activation_status' for now. So no action here.
        // Directly checking if Feature is enabled in getQrCodesActivationStatus
    }

    public function setSubscriptionsActivationStatus(string $status)
    {
        $this->setAttribute(self::SUBSCRIPTIONS_ACTIVATION_STATUS, $status);
    }

    public function setTransactionReportEmail($email)
    {
        $this->setAttribute(self::TRANSACTION_REPORT_EMAIL, $email);
    }

    public function getMarketplaceActivationStatus()
    {
        return $this->getAttribute(self::MARKETPLACE_ACTIVATION_STATUS);
    }

    public function getVirtualAccountsActivationStatus()
    {
        return $this->getAttribute(self::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS);
    }

    public function getSubscriptionsActivationStatus()
    {
        return $this->getAttribute(self::SUBSCRIPTIONS_ACTIVATION_STATUS);
    }

    public function getActivationProgress()
    {
        return $this->getAttribute(self::ACTIVATION_PROGRESS);
    }

    public function getContactName()
    {
        return $this->getAttribute(self::CONTACT_NAME);
    }

    public function getContactMobile()
    {
        return $this->getAttribute(self::CONTACT_MOBILE);
    }

    public function getContactEmail()
    {
        return $this->getAttribute(self::CONTACT_EMAIL);
    }

    public function getContactLandline()
    {
        return $this->getAttribute(self::CONTACT_LANDLINE);
    }

    public function getBusinessType()
    {
        return BusinessType::getKeyFromIndex($this->getBusinessTypeValue());
    }

    public function setBusinessTypeValue(string $businessTypeValue)
    {
        $this->setAttribute(self::BUSINESS_TYPE, $businessTypeValue);
    }

    public function getBusinessTypeValue()
    {
        return $this->getAttribute(self::BUSINESS_TYPE);
    }

    public function isUnregisteredBusiness(): bool
    {
        if (empty($this->getAttribute(self::BUSINESS_TYPE)))
        {
            return false;
        }

        return BusinessType::isUnregisteredBusinessIndex($this->getAttribute(self::BUSINESS_TYPE));
    }

    public function getBusinessName()
    {
        return $this->getAttribute(self::BUSINESS_NAME);
    }

    public function setBusinessName($name)
    {
        $this->setAttribute(self::BUSINESS_NAME, $name);
    }

    public function getBusinessNameSuggested()
    {
        return $this->getAttribute(self::BUSINESS_NAME_SUGGESTED);
    }

    public function setBusinessNameSuggested($name)
    {
       $this->setAttribute(self::BUSINESS_NAME_SUGGESTED, $name);
    }

    public function getPromoterPanNameSuggested()
    {
        return $this->getAttribute(self::PROMOTER_PAN_NAME_SUGGESTED);
    }

    public function setPromoterPanNameSuggested($name)
    {
        $this->setAttribute(self::PROMOTER_PAN_NAME_SUGGESTED, $name);
    }

    public function getKycClarificationReasons()
    {
        if (empty($this->getAttribute(self::KYC_CLARIFICATION_REASONS)) === false)
        {
            $existingKycClarifications      = $this->getAttribute(self::KYC_CLARIFICATION_REASONS);
            $ncCount                        = $existingKycClarifications[Merchant\Constants::NC_COUNT] ?? null;
            $existingReasons                = $existingKycClarifications[Entity::CLARIFICATION_REASONS] ?? [];
            $existingAdditionalDetails      = $existingKycClarifications[Entity::ADDITIONAL_DETAILS] ?? [];
            $existingClarificationReasonsV2 = $existingKycClarifications[Entity::CLARIFICATION_REASONS_V2] ?? [];

            $clarificationReasons   = $this->getClarificationReasons($existingReasons);
            $additionalDetails      = $this->getClarificationReasons($existingAdditionalDetails);
            $clarificationReasonsV2 = $this->getClarificationReasons($existingClarificationReasonsV2);

            $KycClarifications = [
                Entity::CLARIFICATION_REASONS    => $clarificationReasons,
                Entity::ADDITIONAL_DETAILS       => $additionalDetails,
                Entity::CLARIFICATION_REASONS_V2 => $clarificationReasonsV2
            ];

            if (empty($ncCount) === false)
            {
                $KycClarifications[Merchant\Constants::NC_COUNT] = $ncCount;
            }

            return $KycClarifications;
        }

        return null;
    }

    protected function getClarificationReasons($existingReasons)
    {
        $existingReasons = $existingReasons ?? [];

        foreach ($existingReasons as $key => $values)
        {
            $newValues = [];

            foreach ($values as $val)
            {
                if (isset($val[Merchant\Constants::FROM]) === true)
                {
                    $newValues[] = $val;
                }
            }

            $existingReasons[$key] = $newValues;
        }

        return $existingReasons;
    }

    public function setKycClarificationReasons(array $reasons)
    {
        return $this->setAttribute(self::KYC_CLARIFICATION_REASONS, $reasons);
    }

    public function setBusinessNameNull()
    {
        $this->setAttribute(self::BUSINESS_NAME, null);
    }

    public function setContactNameNull()
    {
        $this->setAttribute(self::CONTACT_NAME, null);
    }

    public function setContactMobileNull()
    {
        $this->setAttribute(self::CONTACT_MOBILE, null);
    }

    public function setBusinessTypeNull()
    {
        $this->setAttribute(self::BUSINESS_TYPE, null);
    }

    public function setTransactionVolumeNull()
    {
        $this->setAttribute(self::TRANSACTION_VOLUME, null);
    }

    public function getBusinessCategory()
    {
        return $this->getAttribute(self::BUSINESS_CATEGORY);
    }

    public function setBusinessCategory($category)
    {
        return $this->setAttribute(self::BUSINESS_CATEGORY, $category);
    }

    public function getBusinessSubcategory()
    {
        return $this->getAttribute(self::BUSINESS_SUBCATEGORY);
    }

    public function setBusinessSubcategory($subcategory)
    {
        return $this->setAttribute(self::BUSINESS_SUBCATEGORY, $subcategory);
    }

    public function getTransactionReportEmail()
    {
        return $this->getAttribute(self::TRANSACTION_REPORT_EMAIL);
    }

    public function getBusinessPaymentDetails()
    {
        return $this->getAttribute(self::BUSINESS_PAYMENTDETAILS);
    }

    public function getBusinessDateOfEstablishment()
    {
        return $this->getAttribute(self::BUSINESS_DOE);
    }

    public function getBusinessDba()
    {
        return $this->getAttribute(self::BUSINESS_DBA);
    }

    public function getTransactionVolume()
    {
        return $this->getAttribute(self::TRANSACTION_VOLUME);
    }

    public function getBusinessDescription()
    {
        return $this->getAttribute(self::BUSINESS_DESCRIPTION);
    }

    public function getTransactionValue()
    {
        return $this->getAttribute(self::TRANSACTION_VALUE);
    }

    public function getBusinessInternational()
    {
        return $this->getAttribute(self::BUSINESS_INTERNATIONAL);
    }

    public function getBusinessModel()
    {
        return $this->getAttribute(self::BUSINESS_MODEL);
    }

    /**
     * @throws InvalidPermissionException
     */
    protected function getBusinessNameSuggestedAttribute()
    {
        $fetchData = (new Store\Core())->fetchValuesFromStore(
            $this->merchant->getId(),
            Store\ConfigKey::ONBOARDING_NAMESPACE,
            [self::BUSINESS_NAME_SUGGESTED],
            Store\Constants::INTERNAL);

        return $fetchData[self::BUSINESS_NAME_SUGGESTED] ?? null;
    }

    protected function setBusinessNameSuggestedAttribute($name)
    {
        $data = [
            Store\Constants::NAMESPACE                    => Store\ConfigKey::ONBOARDING_NAMESPACE,
            Store\ConfigKey::BUSINESS_NAME_SUGGESTED      => $name
        ];

        (new Store\Core())->updateMerchantStore($this->merchant->getId(), $data, Store\Constants::INTERNAL);
    }

    /**
     * @throws InvalidPermissionException
     */
    protected function getPromoterPanNameSuggestedAttribute()
    {
        $fetchData = (new Store\Core())->fetchValuesFromStore(
            $this->merchant->getId(),
            Store\ConfigKey::ONBOARDING_NAMESPACE,
            [self::PROMOTER_PAN_NAME_SUGGESTED],
            Store\Constants::INTERNAL);

        return $fetchData[self::PROMOTER_PAN_NAME_SUGGESTED] ?? null;
    }

    protected function setPromoterPanNameSuggestedAttribute($name)
    {
        $data = [
            Store\Constants::NAMESPACE                    => Store\ConfigKey::ONBOARDING_NAMESPACE,
            Store\ConfigKey::PROMOTER_PAN_NAME_SUGGESTED  => $name
        ];

        (new Store\Core())->updateMerchantStore($this->merchant->getId(), $data, Store\Constants::INTERNAL);
    }

    // @codingStandardsIgnoreLine
    public function toArrayGST()
    {
        return array_only($this->toArrayPublic(), self::GST_FIELDS);
    }

    public function toArrayEvent()
    {
        $merchantDetailAttributes = [];

        foreach ($this->eventFields as $eventField)
        {
            if ($this->hasAttribute($eventField))
            {
                $merchantDetailAttributes[$eventField] = $this->getAttribute($eventField);
            }
        }

        return $merchantDetailAttributes;
    }

    public function canDetermineActivationFlow()
    {
        if ($this->getBusinessCategory() === BusinessCategory::OTHERS)
        {
            return true;
        }

        $hasBusinessCategory    = empty($this->getBusinessCategory()) === false;
        $hasBusinessSubCategory = empty($this->getBusinessSubcategory()) === false;

        return $hasBusinessCategory and $hasBusinessSubCategory;
    }

    /**
     * Returns an array with the statuses of the feature onboarding submissions
     *
     * @return array
     */
    public function getFeatureOnboardingStatuses(): array
    {
        $response = [
            self::MARKETPLACE_ACTIVATION_STATUS      => $this->getMarketplaceActivationStatus(),
            self::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS => $this->getVirtualAccountsActivationStatus(),
            self::SUBSCRIPTIONS_ACTIVATION_STATUS    => $this->getSubscriptionsActivationStatus(),
            self::QR_CODES_ACTIVATION_STATUS         => $this->getQrCodesActivationStatus(),
        ];

        // Filter out the null values
        $response = array_filter($response, function($status) {
            return ($status !== null);
        });

        return $response;
    }

    public function setPublicReviewerAttribute(array &$attributes)
    {
        $reviewer = $this->getReviewer();

        if ($reviewer !== null)
        {
            $attributes[Entity::REVIEWER] = $reviewer->toArrayPublic();
        }
    }

    public function setPublicReviewerIdAttribute(array &$attributes)
    {
        $adminId = $this->getAttribute(Entity::REVIEWER_ID);

        $attributes[Entity::REVIEWER_ID] = Admin\Entity::getSignedIdOrNull($adminId);
    }

    protected function getCustomFieldsAttribute($customFields): array
    {
        $customFields = json_decode($customFields, true);

        if (empty($customFields) === true)
        {
            return [];
        }

        return $customFields;
    }

    public function getCustomFields(): array
    {
        return $this->getAttribute(self::CUSTOM_FIELDS);
    }

    public function getClientApplications()
    {
        return $this->getAttribute(self::CLIENT_APPLICATIONS);
    }

    protected function getClientApplicationsAttribute($clientApplications): array
    {
        $clientApplications = json_decode($clientApplications, true);

        if (empty($clientApplications) === true)
        {
            return [];
        }

        return $clientApplications;
    }

    protected function setClientApplicationsAttribute(array $clientApplications)
    {
        $this->attributes[self::CLIENT_APPLICATIONS] = json_encode($clientApplications);
    }

    protected function setCustomFieldsAttribute(array $customFields)
    {
        $this->attributes[self::CUSTOM_FIELDS] = json_encode($customFields);
    }

    public function setCustomFields(array $customFields)
    {
        $this->setAttribute(self::CUSTOM_FIELDS, $customFields);
    }

    public function getIfsc()
    {
        return $this->getAttribute(self::BANK_BRANCH_IFSC);
    }

    public function getLiveTransactionDone()
    {
        return $this->getAttribute(self::LIVE_TRANSACTION_DONE);
    }

    public function getAdditionalWebsites()
    {
        return $this->getAttribute(self::ADDITIONAL_WEBSITES);
    }

    public function getIssueFields()
    {
        return $this->getAttribute(self::ISSUE_FIELDS);
    }

    public function getKycId()
    {
        return $this->getAttribute(self::KYC_ID);
    }

    public function setKycId(string $kycId)
    {
        $this->setAttribute(self::KYC_ID, $kycId);
    }

    public function getEntityId()
    {
        return $this->getMerchantId();
    }

    public function getId()
    {
        return $this->getMerchantId();
    }

    public function setFundAccountValidationId(string $favId)
    {
        return $this->setAttribute(self::FUND_ACCOUNT_VALIDATION_ID, $favId);
    }

    public function getFundAccountValidationId()
    {
        return $this->getAttribute(self::FUND_ACCOUNT_VALIDATION_ID);
    }

    public function getPennyTestingUpdatedAt()
    {
        $this->getAttribute(self::PENNY_TESTING_UPDATED_AT);
    }

    public function setPennyTestingUpdatedAt(string $pennyTestingUpdatedAt)
    {
        $this->setAttribute(self::PENNY_TESTING_UPDATED_AT, $pennyTestingUpdatedAt);
    }

    public function getGstinVerificationStatus()
    {
        return $this->getAttribute(self::GSTIN_VERIFICATION_STATUS);
    }

    public function setGstinVerificationStatus(string $gstinVerificationStatus = null)
    {
        return $this->setAttribute(self::GSTIN_VERIFICATION_STATUS, $gstinVerificationStatus);
    }

    public function setAdditionalWebsites($additionalWebsites)
    {
        return $this->setAttribute(self::ADDITIONAL_WEBSITES, $additionalWebsites);
    }

    public function getShopEstbNumber()
    {
        return $this->getAttribute(self::SHOP_ESTABLISHMENT_NUMBER);
    }

    public function getShopEstbVerificationStatus()
    {
        return $this->getAttribute(self::SHOP_ESTABLISHMENT_VERIFICATION_STATUS);
    }

    public function setShopEstbVerificationStatus(string $shopEstabVerificationStatus = null)
    {
        return $this->setAttribute(self::SHOP_ESTABLISHMENT_VERIFICATION_STATUS, $shopEstabVerificationStatus);
    }

    public function setBusinessSuggestedPin(string $businessSuggestedPin)
    {
        return $this->setAttribute(self::BUSINESS_SUGGESTED_PIN, $businessSuggestedPin);
    }

    public function getBusinessSuggestedPin()
    {
        return $this->getAttribute(self::BUSINESS_SUGGESTED_PIN);
    }

    public function setBusinessSuggestedAddress(string $businessSuggestedAddress)
    {
        return $this->setAttribute(self::BUSINESS_SUGGESTED_ADDRESS, $businessSuggestedAddress);
    }

    public function getBusinessSuggestedAddress()
    {
        return $this->getAttribute(self::BUSINESS_SUGGESTED_ADDRESS);
    }

    public function getFraudType()
    {
        return $this->getAttribute(self::FRAUD_TYPE);
    }

    public function setFraudType($fraudType)
    {
        return $this->setAttribute(self::FRAUD_TYPE, $fraudType);
    }

    public function setBasBusinessId($businessId)
    {
        $this->setAttribute(self::BAS_BUSINESS_ID, $businessId);
    }

    public function getBasBusinessId()
    {
        return $this->getAttribute(self::BAS_BUSINESS_ID);
    }

    public function getQrCodesActivationStatus()
    {
        return $this->merchant->isFeatureEnabled(Feature\Constants::QR_CODES) === true ? self::APPROVED : null;
    }

    public function getFundAdditionVAIds()
    {
        $config = $this->getAttribute(self::FUND_ADDITION_VA_IDS);

        if($config !== null)
        {
            return json_decode($config, true);
        }

        return null;
    }

    public function setFundAdditionVAIds($fundAdditionVAIds)
    {
        $this->setAttribute(self::FUND_ADDITION_VA_IDS, $fundAdditionVAIds);
    }

    public function getIecCode()
    {
        return $this->getAttribute(self::IEC_CODE);
    }

    /**
     * {@inheritDoc}
     */
    protected $dispatchesEvents = [
        // Event 'saved' fires on insert and update both.
        'saved'   => EventSaved::class,
    ];

    static function modifyConvertEmptyStringsToNull(& $input)
    {
        $array = [
            Entity::KYC_ADDITIONAL_DETAILS,
            Entity::KYC_CLARIFICATION_REASONS,
            Entity::FUND_ADDITION_VA_IDS
        ];

        foreach ($array as $key)
        {
            if (empty($input[$key]))
            {
                unset($input[$key]);
            }
        }
    }

    protected function modifyBankBranchInput(& $input)
    {
        if (isset($input[Entity::BANK_BRANCH_IFSC]) === true)
        {
            $input[Entity::BANK_BRANCH_CODE] = $input[Entity::BANK_BRANCH_IFSC];
            $input[Entity::BANK_BRANCH_CODE_TYPE] = BankBranchCodeType::IFSC;
        }
    }



    protected function modifyContactMobile(& $input)
    {
        if (isset($input[Entity::CONTACT_MOBILE]) === true)
        {
            $contact = & $input[Entity::CONTACT_MOBILE];
            $contact = str_replace(' ', '', $contact);
            $contact = str_replace('-', '', $contact);
            $contact = str_replace('(', '', $contact);
            $contact = str_replace(')', '', $contact);

            // Remove the 0 at the start
            if ((strlen($contact) > 1) and
                ($contact[0] === '0'))
            {
                $contact = substr($contact, 1);
            }

             $input[self::CONTACT_MOBILE] =  $contact;

        }
    }

    public function setContactMobileAttribute($contact)
    {
        $number = new PhoneBook($contact, true, $this->merchant->getCountry());

        if ($number->isValidNumber() === true)
        {
            $this->attributes[self::CONTACT_MOBILE] = $number->format();
        }
        else
        {
            $normalizedNumber = $number->getRawInput();

            $this->attributes[self::CONTACT_MOBILE] = $normalizedNumber;
        }
    }

    public function edit(array $input = array(), $operation = 'edit')
    {
        $this->modify($input);

        $this->validateInput($operation, $input);

        $this->unsetInput($operation, $input);

        $this->fill($input);

        return $this;
    }

    public function getBusinessAttributes()
    {
        $businessDetail = $this->businessDetail;

        return $businessDetail ? $businessDetail->getEsAttributes():[];
    }
}
