<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use Carbon\Carbon;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Admin;
use RZP\Models\BankingAccount;
use RZP\Models\Merchant\Detail\BusinessType;
use stdClass;

/**
 * This captures the details that Sales POCs enter into the admin dashboard
 * which need to be exported and sent to RBl.
 * Furthermore, with introduction of Partner LMS for bank,
 * It also stores data that Bank shares with RZP through Reverse MIS.
 * This  data contains both lead-related and ops-related data.
 * For example, the GMV, Insignia, etc are lead-related,
 * and the Merchant POC details are OPs related (for documentation purposes)
 * In an ideal setup, the lead-related data should be captured on Salesforce
 * (that is a lead-management software),
 * and then be pulled to Admin Dashboard for display purposes.
 * (It's not possible to grant Ops team access to Salesforce dashboard
 * because it contains confidential information.)
 *
 * We would also need filtering to be done on a few lead-related fields
 * like city, docs_walkthrough_complete?.
 * This prevents any solution along the lines of using Zapier/sheets, to push and pull data.
 *
 * Class Entity
 * @package RZP\Models\BankingAccount\Activation\Detail
 */
class Entity extends Base\PublicEntity
{
    const BANKING_ACCOUNT = 'banking_account';

    const BANKING_ACCOUNT_ID = 'banking_account_id';

    // merchant details
    const MERCHANT_POC_NAME = 'merchant_poc_name';

    const MERCHANT_POC_DESIGNATION = 'merchant_poc_designation';

    // HACK: merchant poc email and phone number can be multiple cardinality
    // Not validating against it. Ideally, this should be normalized,
    // but the use-case is purely for information retrieval from admin dashboard.
    const MERCHANT_POC_EMAIL = 'merchant_poc_email';
    const MERCHANT_POC_PHONE_NUMBER = 'merchant_poc_phone_number';

    const MERCHANT_CITY = 'merchant_city';

    const MERCHANT_STATE = 'merchant_state';

    const MERCHANT_DOCUMENTS_ADDRESS = 'merchant_documents_address';

    // east, west, north, south
    const MERCHANT_REGION = 'merchant_region';

    // business details
    const EXPECTED_MONTHLY_GMV = 'expected_monthly_gmv';

    const INITIAL_CHEQUE_VALUE = 'initial_cheque_value';

    // One of https://razorpay.com/docs/razorpayx/current-account
    const BUSINESS_CATEGORY = 'business_category';

    const BUSINESS_TYPE = 'business_type';

    const BUSINESS_NAME = 'business_name';

    const BANKING_ACCOUNT_ACTIVATION_DETAILS = 'banking_account_activation_details';

    /**
     * Stores Personal Pan if Business category is sole_proprietorship
     * else stores Business Pan
     */
    const BUSINESS_PAN = 'business_pan';

    // This is to store verification date done by ops team
    const VERIFICATION_DATE = 'verification_date';

    const CONTACT_VERIFIED = 'contact_verified';

    const AVERAGE_MONTHLY_BALANCE = 'average_monthly_balance';

    // For RBL, this can be Insignia
    const ACCOUNT_TYPE = 'account_type';

    // internal POC details
    const SALES_TEAM = 'sales_team';

    // HACK: Ideally this should be handled as part of the admin user (sales_poc_id is basically
    // the admin_id) . THis information will be redundant.
    // But since admin entity has no information about phone number, adding it here.
    const SALES_POC_PHONE_NUMBER = 'sales_poc_phone_number';

    const IS_DOCUMENTS_WALKTHROUGH_COMPLETE = 'is_documents_walkthrough_complete';

    const DWT_SCHEDULED_TIMESTAMP = 'dwt_scheduled_timestamp';
    const OPS_FOLLOW_UP_DATE = 'ops_follow_up_date';

    // This additional_details column will store tags of a lead in json format.

    const ADDITIONAL_DETAILS = 'additional_details';

    // Additional details fields
    const ACCOUNT_OPENING_WEBHOOK_DATE = 'account_opening_webhook_date';
    const GREEN_CHANNEL = 'green_channel';
    const FEET_ON_STREET = 'feet_on_street';
    const REVIVED_LEAD = 'revived_lead';
    const DOCKET_DELIVERED_DATE = 'docket_delivered_date';
    const DOCKET_REQUESTED_DATE = 'docket_requested_date';
    const DOCKET_ESTIMATED_DELIVERY_DATE = 'docket_estimated_delivery_date';
    const MID_OFFICE_POC_NAME = 'mid_office_poc_name';
    const SKIP_MID_OFFICE_CALL = 'skip_mid_office_call';
    const APPOINTMENT_SOURCE = 'appointment_source';

    const SENT_DOCKET_AUTOMATICALLY = 'sent_docket_automatically';
    const REASONS_TO_NOT_SEND_DOCKET = 'reasons_to_not_send_docket';

    // Business Details in Additional Details
    const BUSINESS_DETAILS = 'business_details';
    const CATEGORY = 'category';
    const SUB_CATEGORY = 'sub_category';

    const SALES_PITCH_COMPLETED = 'sales_pitch_completed';

    const ENTITY_PROOF_DOCUMENTS = 'entity_proof_documents';

    const DOCUMENT_TYPE = 'document_type';

    const GSTIN_PREFILLED_ADDRESS = 'gstin_prefilled_address';

    const GSTIN = 'gstin';

    const PROOF_OF_ENTITY = 'proof_of_entity';

    const PROOF_OF_ADDRESS = 'proof_of_address';

    const RBL_NEW_ONBOARDING_FLOW_DECLARATIONS = 'rbl_new_onboarding_flow_declarations';

    const AVAILABLE_AT_PREFERRED_ADDRESS_TO_COLLECT_DOCS = 'available_at_preferred_address_to_collect_docs';

    const SEAL_AVAILABLE = 'seal_available';

    const SIGNATORIES_AVAILABLE_AT_PREFERRED_ADDRESS = 'signatories_available_at_preferred_address';

    const SIGNBOARD_AVAILABLE = 'signboard_available';

    const SKIP_DWT = 'skip_dwt';

    const CALENDLY_SLOT_BOOKING_COMPLETED = 'calendly_slot_booking_completed';

    const COMMENT = 'comment'; // Comment added during form filling- added as first comment

    // team that is currently assigned to work on this
    const ASSIGNEE_TEAM = 'assignee_team';

    const ASSIGNEE_NAME = 'assignee_name';

    const BOOKING_DATE_AND_TIME = 'booking_date_and_time';

    // Details received from RBL
    const RM_NAME = 'rm_name';
    const RM_PHONE_NUMBER = 'rm_phone_number';
    const ACCOUNT_OPEN_DATE = 'account_open_date';
    // (RBL) Date at which RM logs the docs into the bank's system, and processing starts.
    const ACCOUNT_LOGIN_DATE = 'account_login_date';
    // Bank is sending us this data and we have to start saving it in our system
    const API_ONBOARDED_DATE        = 'api_onboarded_date';
    const API_ONBOARDING_LOGIN_DATE = 'api_onboarding_login_date';

    // RBL - Bank LMS activation related field

    // For Assigning Leads to Bank POC
    const BANK_POC_USER_ID = 'bank_poc_user_id';
    const BANK_POC_NAME = 'bank_poc_name';
    const BANK_POC_EMAIL = 'bank_poc_email';
    const BANK_POC_PHONE_NUMBER = 'bank_poc_phone_number';
    const BANK_POC_ASSIGNED_DATE = 'bank_poc_assigned_date';

    const RBL_ACTIVATION_DETAILS = 'rbl_activation_details';
    // Following fields will be part of rbl_activation_details
    const IR_NUMBER             = 'ir_number';
    const LEAD_IR_NUMBER = 'lead_ir_number';
    const IP_CHEQUE_VALUE = 'ip_cheque_value';
    const OFFICE_DIFFERENT_LOCATIONS = 'office_different_locations';
    const API_DOCS_RECEIVED_WITH_CA_DOCS = 'api_docs_received_with_ca_docs';
    const ACCOUNT_OPENING_IR_NUMBER = 'account_opening_ir_number';
    const CASE_LOGIN_DIFFERENT_LOCATIONS = 'case_login_different_locations';
    const SR_NUMBER = 'sr_number';
    const REVISED_DECLARATION = 'revised_declaration';
    const API_IR_NUMBER = 'api_ir_number';
    const UPI_CREDENTIAL_NOT_DONE_REMARKS = 'upi_credential_not_done_remarks';
    const PROMO_CODE = 'promo_code';
    const LEAD_REFERRED_BY_RBL_STAFF = 'lead_referred_by_rbl_staff';
    const ACCOUNT_OPENING_TAT_EXCEPTION = 'account_opening_tat_exception';
    const ACCOUNT_OPENING_TAT_EXCEPTION_REASON = 'account_opening_tat_exception_reason';
    const API_ONBOARDING_TAT_EXCEPTION = 'api_onboarding_tat_exception';
    const API_ONBOARDING_TAT_EXCEPTION_REASON = 'api_onboarding_tat_exception_reason';
    const BANK_DUE_DATE = 'bank_due_date';
    const PCARM_MANAGER_NAME = 'pcarm_manager_name';

    const API_SERVICE_FIRST_QUERY = 'api_service_first_query';
    const API_BEYOND_TAT = 'api_beyond_tat';
    const API_BEYOND_TAT_DEPENDENCY = 'api_beyond_tat_dependency';
    const FIRST_CALLING_TIME = 'first_calling_time';
    const SECOND_CALLING_TIME = 'second_calling_time';
    const WA_MESSAGE_SENT_DATE = 'wa_message_sent_date';
    const WA_MESSAGE_RESPONSE_DATE = 'wa_message_response_date';
    const API_DOCKET_RELATED_ISSUE = 'api_docket_related_issue';
    const AOF_SHARED_WITH_MO = 'aof_shared_with_mo';
    const AOF_SHARED_DISCREPANCY = 'aof_shared_discrepancy';
    const AOF_NOT_SHARED_REASON ='aof_not_shared_reason';
    const CA_BEYOND_TAT_DEPENDENCY = 'ca_beyond_tat_dependency';
    const CA_BEYOND_TAT = 'ca_beyond_tat';
    const CA_SERVICE_FIRST_QUERY = 'ca_service_first_query';
    const CUSTOMER_APPOINTMENT_BOOKING_DATE = 'customer_appointment_booking_date';
    const CUSTOMER_ONBOARDING_TAT = 'customer_onboarding_tat';
    const LEAD_IR_STATUS = 'lead_ir_status';

    const CUSTOMER_APPOINTMENT_DATE = 'customer_appointment_date';
    const BRANCH_CODE = 'branch_code';
    const RM_EMPLOYEE_CODE = 'rm_employee_code';
    const RM_ASSIGNMENT_TYPE = 'rm_assignment_type';
    const VERIFICATION_COMPLETION_DATE = 'verification_completion_date';
    const VERIFICATION_TAT = 'verification_tat';

    const DOC_COLLECTION_DATE = 'doc_collection_date';
    const API_DOCS_DELAY_REASON = 'api_docs_delay_reason';
    const DOC_COLLECTION_COMPLETION_DATE = 'doc_collection_completion_date';
    const DOC_COLLECTION_TAT = 'doc_collection_tat';

    const ACCOUNT_OPENING_IR_CLOSE_DATE = 'account_opening_ir_close_date';
    const ACCOUNT_OPENING_FTNR = 'account_opening_ftnr';
    const ACCOUNT_OPENING_FTNR_REASONS = 'account_opening_ftnr_reasons';
    const ACCOUNT_OPENING_COMPLETION_DATE = 'account_opening_completion_date';
    const ACCOUNT_OPENING_TAT = 'account_opening_tat';

    const API_IR_CLOSED_DATE = 'api_ir_closed_date';
    const LDAP_ID_MAIL_DATE = 'ldap_id_mail_date';
    const API_ONBOARDING_FTNR = 'api_onboarding_ftnr';
    const API_ONBOARDING_FTNR_REASONS = 'api_onboarding_ftnr_reasons';
    const API_ONBOARDING_COMPLETION_DATE = 'api_onboarding_completion_date';
    const API_ONBOARDING_TAT = 'api_onboarding_tat';

    const RZP_CA_ACTIVATED_DATE = 'rzp_ca_activated_date';
    const DROP_OFF_DATE = 'drop_off_date';
    const ACCOUNT_ACTIVATION_COMPLETION_DATE = 'account_activation_completion_date';
    const ACCOUNT_ACTIVATION_TAT = 'account_activation_tat';

    const UPI_CREDENTIAL_RECEIVED_DATE = 'upi_credential_received_date';
    const UPI_ACTIVATION_COMPLETION_DATE = 'upi_activation_completion_date';
    const UPI_ACTIVATION_TAT = 'upi_activation_tat';

    // relations
    // admin_audit_map is used here
    const SALES_POC_ID = 'sales_poc_id';

    //sales_poc_name and sales_poc_email are not part of entity, these are just constants needed for ToArrayPublic
    const SALES_POC_NAME = 'sales_poc_name';

    const SALES_POC_EMAIL = 'sales_poc_email';

    // Indicates Application is filled completely from merchant dashboard
    const DECLARATION_STEP = 'declaration_step';

    const BUSINESS_PAN_VALIDATION = 'business_pan_validation';

    const APPLICATION_TYPE = 'application_type';

    const ADMIN_EMAIL = 'admin_email';

    // Application Types
    const CO_CREATED = 'co_created';

    // assignee team
    const BANK = "bank";
    const OPS = "ops";
    const SALES = "sales";
    const BANK_OPS = "bank_ops";

    // appointment source
    const MID_OFFICE = 'mid_office';


    protected $entity = 'banking_account_activation_detail';

    protected $table  = Table::BANKING_ACCOUNT_ACTIVATION_DETAIL;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::BANKING_ACCOUNT_ID,
        self::MERCHANT_POC_NAME,
        self::MERCHANT_POC_DESIGNATION,
        self::MERCHANT_POC_EMAIL,
        self::MERCHANT_POC_PHONE_NUMBER,
        self::MERCHANT_DOCUMENTS_ADDRESS,
        self::MERCHANT_CITY,
        self::MERCHANT_STATE,
        self::MERCHANT_REGION,
        self::EXPECTED_MONTHLY_GMV,
        self::INITIAL_CHEQUE_VALUE,
        self::BUSINESS_CATEGORY,
        self::AVERAGE_MONTHLY_BALANCE,
        self::ACCOUNT_TYPE,
        self::IS_DOCUMENTS_WALKTHROUGH_COMPLETE,
        self::COMMENT,
        self::ASSIGNEE_TEAM,
        self::VERIFICATION_DATE,
        self::BOOKING_DATE_AND_TIME,
        self::SALES_TEAM,
        self::SALES_POC_PHONE_NUMBER,
        self::RM_NAME,
        self::RM_PHONE_NUMBER,
        self::BANK_POC_USER_ID,
        self::RBL_ACTIVATION_DETAILS,
        self::CUSTOMER_APPOINTMENT_DATE,
        self::BRANCH_CODE,
        self::RM_EMPLOYEE_CODE,
        self::RM_ASSIGNMENT_TYPE,
        self::DOC_COLLECTION_DATE,
        self::ACCOUNT_OPENING_IR_CLOSE_DATE,
        self::ACCOUNT_OPENING_FTNR,
        self::ACCOUNT_OPENING_FTNR_REASONS,
        self::API_IR_CLOSED_DATE,
        self::LDAP_ID_MAIL_DATE,
        self::API_ONBOARDING_FTNR,
        self::API_ONBOARDING_FTNR_REASONS,
        self::UPI_CREDENTIAL_RECEIVED_DATE,
        self::RZP_CA_ACTIVATED_DATE,
        self::DROP_OFF_DATE,
        self::ACCOUNT_OPEN_DATE,
        self::ACCOUNT_LOGIN_DATE,
        self::BUSINESS_NAME,
        self::BUSINESS_TYPE,
        self::BUSINESS_PAN,
        self::DECLARATION_STEP,
        self::APPLICATION_TYPE,
        self::ADDITIONAL_DETAILS,
    ];

    protected $visible = [
        self::ID,
        self::BANKING_ACCOUNT_ID,
        self::MERCHANT_POC_NAME,
        self::MERCHANT_POC_DESIGNATION,
        self::MERCHANT_POC_EMAIL,
        self::MERCHANT_POC_PHONE_NUMBER,
        self::MERCHANT_DOCUMENTS_ADDRESS,
        self::MERCHANT_CITY,
        self::MERCHANT_STATE,
        self::MERCHANT_REGION,
        self::EXPECTED_MONTHLY_GMV,
        self::INITIAL_CHEQUE_VALUE,
        self::BUSINESS_CATEGORY,
        self::BUSINESS_NAME,
        self::BUSINESS_TYPE,
        self::BUSINESS_PAN,
        self::CONTACT_VERIFIED,
        self::VERIFICATION_DATE,
        self::AVERAGE_MONTHLY_BALANCE,
        self::ACCOUNT_TYPE,
        self::IS_DOCUMENTS_WALKTHROUGH_COMPLETE,
        self::SALES_TEAM,
        self::SALES_POC_PHONE_NUMBER,
        self::ASSIGNEE_TEAM,
        self::BOOKING_DATE_AND_TIME,
        self::COMMENT,
        self::RM_NAME,
        self::RM_PHONE_NUMBER,
        self::BANK_POC_USER_ID,
        self::DECLARATION_STEP,
        self::APPLICATION_TYPE,
        self::BUSINESS_PAN_VALIDATION,
        self::BANK_POC_USER_ID,
        self::RBL_ACTIVATION_DETAILS,
        self::CUSTOMER_APPOINTMENT_DATE,
        self::BRANCH_CODE,
        self::RM_EMPLOYEE_CODE,
        self::RM_ASSIGNMENT_TYPE,
        self::VERIFICATION_COMPLETION_DATE,
        self::VERIFICATION_TAT,
        self::DOC_COLLECTION_DATE,
        self::DOC_COLLECTION_COMPLETION_DATE,
        self::DOC_COLLECTION_TAT,
        self::ACCOUNT_OPENING_IR_CLOSE_DATE,
        self::ACCOUNT_OPENING_FTNR,
        self::ACCOUNT_OPENING_FTNR_REASONS,
        self::ACCOUNT_OPENING_COMPLETION_DATE,
        self::ACCOUNT_OPENING_TAT,
        self::API_IR_CLOSED_DATE,
        self::LDAP_ID_MAIL_DATE,
        self::API_ONBOARDING_FTNR,
        self::API_ONBOARDING_FTNR_REASONS,
        self::API_ONBOARDING_COMPLETION_DATE,
        self::API_ONBOARDING_TAT,
        self::UPI_CREDENTIAL_RECEIVED_DATE,
        self::RZP_CA_ACTIVATED_DATE,
        self::DROP_OFF_DATE,
        self::ACCOUNT_ACTIVATION_COMPLETION_DATE,
        self::ACCOUNT_ACTIVATION_TAT,
        self::ACCOUNT_OPEN_DATE,
        self::ACCOUNT_LOGIN_DATE,
        self::CREATED_AT,
        self::ADDITIONAL_DETAILS,
        self::UPI_ACTIVATION_TAT,
        self::CUSTOMER_ONBOARDING_TAT,
        self::ASSIGNEE_NAME,
    ];

    public $public = [
        self::ID,
        self::BANKING_ACCOUNT_ID,
        self::MERCHANT_POC_NAME,
        self::MERCHANT_POC_DESIGNATION,
        self::MERCHANT_POC_EMAIL,
        self::MERCHANT_POC_PHONE_NUMBER,
        self::MERCHANT_DOCUMENTS_ADDRESS,
        self::MERCHANT_CITY,
        self::MERCHANT_STATE,
        self::MERCHANT_REGION,
        self::EXPECTED_MONTHLY_GMV,
        self::INITIAL_CHEQUE_VALUE,
        self::BUSINESS_CATEGORY,
        self::BUSINESS_NAME,
        self::BUSINESS_TYPE,
        self::BUSINESS_PAN,
        self::CONTACT_VERIFIED,
        self::VERIFICATION_DATE,
        self::AVERAGE_MONTHLY_BALANCE,
        self::ACCOUNT_TYPE,
        self::IS_DOCUMENTS_WALKTHROUGH_COMPLETE,
        self::SALES_TEAM,
        self::SALES_POC_PHONE_NUMBER,
        self::ASSIGNEE_TEAM,
        self::BOOKING_DATE_AND_TIME,
        self::DECLARATION_STEP,
        self::APPLICATION_TYPE,
        self::BUSINESS_PAN_VALIDATION,
        self::COMMENT,
        self::RM_NAME,
        self::RM_PHONE_NUMBER,
        self::BANK_POC_USER_ID,
        self::RBL_ACTIVATION_DETAILS,
        self::CUSTOMER_APPOINTMENT_DATE,
        self::BRANCH_CODE,
        self::RM_EMPLOYEE_CODE,
        self::RM_ASSIGNMENT_TYPE,
        self::VERIFICATION_COMPLETION_DATE,
        self::VERIFICATION_TAT,
        self::DOC_COLLECTION_DATE,
        self::DOC_COLLECTION_COMPLETION_DATE,
        self::DOC_COLLECTION_TAT,
        self::ACCOUNT_OPENING_IR_CLOSE_DATE,
        self::ACCOUNT_OPENING_FTNR,
        self::ACCOUNT_OPENING_FTNR_REASONS,
        self::ACCOUNT_OPENING_COMPLETION_DATE,
        self::ACCOUNT_OPENING_TAT,
        self::API_IR_CLOSED_DATE,
        self::LDAP_ID_MAIL_DATE,
        self::API_ONBOARDING_FTNR,
        self::API_ONBOARDING_FTNR_REASONS,
        self::API_ONBOARDING_COMPLETION_DATE,
        self::API_ONBOARDING_TAT,
        self::UPI_CREDENTIAL_RECEIVED_DATE,
        self::RZP_CA_ACTIVATED_DATE,
        self::DROP_OFF_DATE,
        self::ACCOUNT_ACTIVATION_COMPLETION_DATE,
        self::ACCOUNT_ACTIVATION_TAT,
        self::ACCOUNT_OPEN_DATE,
        self::ACCOUNT_LOGIN_DATE,
        self::CREATED_AT,
        self::ADDITIONAL_DETAILS,
        self::UPI_ACTIVATION_TAT,
        self::CUSTOMER_ONBOARDING_TAT,
    ];

    protected $publicSetters = [
        self::ADDITIONAL_DETAILS,
        self::RBL_ACTIVATION_DETAILS,
        self::ASSIGNEE_TEAM,
        self::VERIFICATION_COMPLETION_DATE,
        self::VERIFICATION_TAT,
        self::DOC_COLLECTION_COMPLETION_DATE,
        self::DOC_COLLECTION_TAT,
        self::ACCOUNT_OPENING_COMPLETION_DATE,
        self::ACCOUNT_OPENING_TAT,
        self::API_ONBOARDING_COMPLETION_DATE,
        self::API_ONBOARDING_TAT,
        self::ACCOUNT_ACTIVATION_COMPLETION_DATE,
        self::ACCOUNT_ACTIVATION_TAT,
        self::UPI_ACTIVATION_COMPLETION_DATE,
        self::UPI_ACTIVATION_TAT,
        self::CUSTOMER_ONBOARDING_TAT,
        self::BANK_POC_NAME,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $appends = [
        self::ASSIGNEE_NAME
    ];

    public function getBankingAccountId()
    {
        return $this->getAttributeValue(self::BANKING_ACCOUNT_ID);
    }

    public function bankingAccount()
    {
        return $this->belongsTo(BankingAccount\Entity::class);
    }

    public function getAssigneeTeam()
    {
        return $this->getAttributeValue(self::ASSIGNEE_TEAM);
    }

    public function getContactVerified()
    {
        return $this->getAttributeValue(self::CONTACT_VERIFIED);
    }

    public function getBusinessPan()
    {
        return $this->getAttributeValue(self::BUSINESS_PAN);
    }

    public function getDeclarationStep()
    {
        return $this->getAttributeValue(self::DECLARATION_STEP);
    }

    public function getBusinessName()
    {
        return $this->getAttributeValue(self::BUSINESS_NAME);
    }

    public function getPanVerificationStatus()
    {
        return $this->getAttributeValue(self::BUSINESS_PAN_VALIDATION);
    }

    public function getMerchantPocName()
    {
        return $this->getAttributeValue(self::MERCHANT_POC_NAME);
    }

    public function getBusinessCategory()
    {
        return $this->getAttributeValue(self::BUSINESS_CATEGORY);
    }

    public function getMerchantPocEmail()
    {
        return $this->getAttributeValue(self::MERCHANT_POC_EMAIL);
    }

    public function getMerchantPocPhoneNumber()
    {
        return $this->getAttributeValue(self::MERCHANT_POC_PHONE_NUMBER);
    }

    public function getMerchantDocumentsAddress()
    {
        return $this->getAttributeValue(self::MERCHANT_DOCUMENTS_ADDRESS);
    }

    public function getBookingDateAndTime()
    {
        return $this->getAttributeValue(self::BOOKING_DATE_AND_TIME);
    }

    public function setPanVerificationStatus(string $status)
    {
        $this->setAttribute(self::BUSINESS_PAN_VALIDATION, $status);
    }

    public function setContactMobileVerified(bool $verified)
    {
        $this->setAttribute(self::CONTACT_VERIFIED, $verified);
    }

    public function isPoEVerified()
    {
        $poe = $this->extractFieldFromJSONField($this->getAdditionalDetails(), self::PROOF_OF_ENTITY);

        if (empty($poe) === false && isset($poe['status']))
        {
            return $poe['status'] === 'verified';
        }

        return $this->extractFieldFromJSONField($this->getAdditionalDetails(), self::GSTIN) ?? false;
    }

    public function isPoAVerified()
    {
        $poa = $this->extractFieldFromJSONField($this->getAdditionalDetails(), self::PROOF_OF_ADDRESS);

        if (empty($poa) === false && isset($poa['status']))
        {
            return $poa['status'] === 'verified';
        }

        return $this->extractFieldFromJSONField($this->getAdditionalDetails(), self::GSTIN_PREFILLED_ADDRESS) ?? false;
    }

    public function getPoESource()
    {
        $poe = $this->extractFieldFromJSONField($this->getAdditionalDetails(), self::PROOF_OF_ENTITY);

        return $poe ? $poe['source'] : null;
    }

    public function getPoASource()
    {
        $poa = $this->extractFieldFromJSONField($this->getAdditionalDetails(), self::PROOF_OF_ADDRESS);

        return $poa ? $poa['source'] : null;
    }

    public function getAppointmentSource()
    {
        return $this->extractFieldFromJSONField($this->getAdditionalDetails(), self::APPOINTMENT_SOURCE);
    }

    public function getSkipMidOfficeCall()
    {
        return $this->extractFieldFromJSONField($this->getAdditionalDetails(), self::SKIP_MID_OFFICE_CALL);
    }

    /**
     * Check that the Business Category is matching with Merchant details -> Business Type
     *
     * Both should be non-empty
     */
    public function businessCategoryMatchesMerchantBusinessType($merchantBusinessType)
    {
        $businessCategory = $this->getBusinessCategory();

        if (empty($businessCategory) || empty($merchantBusinessType))
        {
            return false;
        }

        if (array_key_exists($businessCategory, Validator::$xToPGBusinessTypeMapping))
        {
            $allowedMappings = Validator::$xToPGBusinessTypeMapping[$businessCategory];

            if (in_array($merchantBusinessType, $allowedMappings))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Check Business name is same as Merchant Name
     * Both should be non empty, check is case-insensitive
     */
    public function businessNameMatchesMerchantName($merchantName)
    {
        $businessName = $this->getBusinessName();

        if (empty($businessName) || empty($merchantName))
        {
            return false;
        }

        if (strtolower($businessName) !== strtolower($merchantName))
        {
            return false;
        }

        return true;
    }

    public function setBankPOCUserId(string $userId)
    {
        $this->setAttribute(self::BANK_POC_USER_ID, $userId);

        $rblActivationDetails = $this->getRblActivationDetails();

        if (isset($rblActivationDetails) === true)
        {
            $rblActivationDetails = json_decode($rblActivationDetails, true);
            $rblActivationDetails[self::BANK_POC_ASSIGNED_DATE] = Carbon::now()->timestamp;
            $rblActivationDetails[self::BANK_DUE_DATE] = Carbon::now()->addHours(4)->timestamp;
        }
        else
        {
            $rblActivationDetails = [
                self::BANK_POC_ASSIGNED_DATE => Carbon::now()->timestamp,
                self::BANK_DUE_DATE => Carbon::now()->addHours(4)->timestamp,
            ];
        }
        $this->setAttribute(self::RBL_ACTIVATION_DETAILS, $rblActivationDetails);
    }

    public function setSalesTeam(string $salesTeam)
    {
        $this->setAttribute(self::SALES_TEAM, $salesTeam);
    }

    public function setAdditionalDetails(string $additionalDetails)
    {
        $this->setAttribute(self::ADDITIONAL_DETAILS, $additionalDetails);
    }

    public function getSalesTeam()
    {
        return $this->getAttribute(self::SALES_TEAM);
    }

    public function getBankPOCUserId()
    {
        return $this->getAttribute(self::BANK_POC_USER_ID);
    }

    public function getLDAPIDMailDate()
    {
        return $this->getAttribute(self::LDAP_ID_MAIL_DATE);
    }

    public function getAssigneeName()
    {
        $assigneeTeam = $this->getAssigneeTeam();

        switch ($assigneeTeam)
        {
            case 'ops':
                return $this->bankingAccount->reviewers->first()['name'];
            case 'sales':
                return $this->bankingAccount->spocs->first()['name'];
        }

        return '';
    }

    public function getAssigneeNameAttribute()
    {
        return $this->getAssigneeName();
    }

    public function getBankPOCUser()
    {
        $bankPOCUserId = $this->getBankPOCUserId();

        if (is_null($bankPOCUserId))
            return null;
        return (new \RZP\Models\User\Repository())->find($bankPOCUserId);
    }

    public function isAssigneeTeamChanged()
    {
        return $this->isDirty(self::ASSIGNEE_TEAM);
    }

    public function isAssigneeTeamUpdated()
    {
        return array_key_exists(self::ASSIGNEE_TEAM, $this->getChanges());
    }

    public function setPublicRblActivationDetailsAttribute(array &$array)
    {
        if (isset($array[self::RBL_ACTIVATION_DETAILS]) === true)
        {
            if (is_string($array[self::RBL_ACTIVATION_DETAILS]) === true)
            {
                $array[self::RBL_ACTIVATION_DETAILS] = json_decode($array[self::RBL_ACTIVATION_DETAILS], true);
            }
        }
    }

    public function getRblActivationDetails()
    {
        return $this->getAttributeValue(self::RBL_ACTIVATION_DETAILS);
    }

    public function setPublicAdditionalDetailsAttribute(array &$array)
    {
        if (app('basicauth')->isAdminAuth() === true and
            isset($array[self::ADDITIONAL_DETAILS]) === true) {

            $array[self::ADDITIONAL_DETAILS] = json_decode($array[self::ADDITIONAL_DETAILS], true);

        }
    }

    /**
     * Handle paralle assignee for different auth
     */
    public function setPublicAssigneeTeamAttribute(array &$array)
    {
        if(array_key_exists(self::ASSIGNEE_TEAM, $array) === false)
        {
            return;
        }

        if($array[self::ASSIGNEE_TEAM] != self::BANK_OPS)
        {
            return;
        }

        if (app('basicauth')->isAdminAuth() === true)
        {
            $array[self::ASSIGNEE_TEAM] = self::OPS;
        }
        else
        {
            $array[self::ASSIGNEE_TEAM] = self::BANK;
        }
    }

    public function getAdditionalDetails()
    {
        return $this->getAttributeValue(self::ADDITIONAL_DETAILS);
    }

    public static function extractFieldFromJSONField($json, $field)
    {
        if (isset($json) === true)
        {
            if (is_string($json) === true)
            {
                $json = json_decode($json, true);
            }

            if (is_array($json) && array_key_exists($field, $json))
            {
                return $json[$field];
            }

            if ($json instanceof stdClass && isset($json->$field))
            {
                return $json->$field;
            }
        }

        return null;
    }

    public static function hourDifferenceBetweenTimestamps($t1, $t2, $skipWeekend = true)
    {
        if (empty($t1) === true || empty($t2) === true)
        {
            return null;
        }

        $t1 = (int)$t1;
        $t2 = (int)$t2;

        // t1 <= t2, since these dates refer to a process that must happen sequentially
        if ($t1 > $t2)
        {
            // Not throwing any error
            return null;
        }

        $time1 = new \Carbon\CarbonImmutable($t1);
        $time2 = new \Carbon\CarbonImmutable($t2);

        $diffInMinutes = 0;
        $step = $time1;

        while ($step < $time2) {
            if ($skipWeekend === true && $step->isWeekend()) {
                $step = $step->next('Monday');

                continue;
            }

            $nextStep = min($time2, $step->addDay()->startOfDay());

            $diffInMinutes += $step->diffInMinutes($nextStep);
            $step = $nextStep;
        }

        return round($diffInMinutes / 60);
    }

    public function setPublicVerificationCompletionDateAttribute(array &$array)
    {
        $array[self::VERIFICATION_COMPLETION_DATE] = $this->getAttribute(self::CUSTOMER_APPOINTMENT_DATE);
    }

    public function setPublicVerificationTatAttribute(array &$array)
    {
        $verificationDate = $array[self::VERIFICATION_COMPLETION_DATE] ?? Carbon::now()->timestamp;
        $rblActivationDetails = $array[self::RBL_ACTIVATION_DETAILS] ?? $this->getRblActivationDetails();
        $assignedDate = self::extractFieldFromJSONField($rblActivationDetails, self::BANK_POC_ASSIGNED_DATE);

        $array[self::VERIFICATION_TAT] =
            self::hourDifferenceBetweenTimestamps($assignedDate, $verificationDate);
    }

    public function setPublicDocCollectionCompletionDateAttribute(array &$array)
    {
        $array[self::DOC_COLLECTION_COMPLETION_DATE] = $this->getAttribute(self::DOC_COLLECTION_DATE);
    }

    public function setPublicDocCollectionTatAttribute(array &$array)
    {
        $verificationDate = $array[self::VERIFICATION_COMPLETION_DATE];
        $docCollectionDate = $array[self::DOC_COLLECTION_COMPLETION_DATE] ?? Carbon::now()->timestamp;

        $array[self::DOC_COLLECTION_TAT] =
                self::hourDifferenceBetweenTimestamps($verificationDate, $docCollectionDate);
    }

    public function setPublicAccountOpeningCompletionDateAttribute(array &$array)
    {
        $array[self::ACCOUNT_OPENING_COMPLETION_DATE] = $this->getAttribute(self::ACCOUNT_OPEN_DATE);
    }

    public function setPublicAccountOpeningTatAttribute(array &$array)
    {
        $docCollectionDate = $array[self::DOC_COLLECTION_COMPLETION_DATE];
        $accountOpeningDate = $array[self::ACCOUNT_OPENING_COMPLETION_DATE] ?? Carbon::now()->timestamp;

        $array[self::ACCOUNT_OPENING_TAT] =
            self::hourDifferenceBetweenTimestamps($docCollectionDate, $accountOpeningDate);
    }

    public function setPublicApiOnboardingCompletionDateAttribute(array &$array)
    {
        $array[self::API_ONBOARDING_COMPLETION_DATE] = $this->getAttribute(self::API_IR_CLOSED_DATE);
    }

    public function setPublicApiOnboardingTatAttribute(array &$array)
    {
        $accountOpeningDate = $array[self::ACCOUNT_OPENING_COMPLETION_DATE];
        $apiOnboardingDate = $array[self::API_ONBOARDING_COMPLETION_DATE] ?? Carbon::now()->timestamp;

        $array[self::API_ONBOARDING_TAT] =
            self::hourDifferenceBetweenTimestamps($accountOpeningDate, $apiOnboardingDate);
    }

    public function setPublicAccountActivationCompletionDateAttribute(array &$array)
    {
        $array[self::ACCOUNT_ACTIVATION_COMPLETION_DATE] = $this->getAttribute(self::RZP_CA_ACTIVATED_DATE);
    }

    public function setPublicAccountActivationTatAttribute(array &$array)
    {
        $apiOnboardingDate = $array[self::API_ONBOARDING_COMPLETION_DATE];
        $accountActivationDate = $array[self::ACCOUNT_ACTIVATION_COMPLETION_DATE] ?? Carbon::now()->timestamp;

        $array[self::ACCOUNT_ACTIVATION_TAT] =
            self::hourDifferenceBetweenTimestamps($apiOnboardingDate, $accountActivationDate);
    }

    public function setPublicUpiActivationCompletionDateAttribute(array &$array)
    {
        $array[self::UPI_ACTIVATION_COMPLETION_DATE] = $this->getAttribute(self::UPI_CREDENTIAL_RECEIVED_DATE);
    }

    public function setPublicUpiActivationTatAttribute(array &$array)
    {
        $apiOnboardingDate = $array[self::API_ONBOARDING_COMPLETION_DATE];
        $upiActivationDate = $array[self::UPI_ACTIVATION_COMPLETION_DATE] ?? Carbon::now()->timestamp;

        $array[self::UPI_ACTIVATION_TAT] =
            self::hourDifferenceBetweenTimestamps($apiOnboardingDate, $upiActivationDate);
    }

    public function setPublicCustomerOnboardingTatAttribute(array &$array)
    {
        $customerOnboardingTat = null;

        if (empty($array[self::DOC_COLLECTION_DATE]) === false)
        {
            $docCollectionDate = $array[self::DOC_COLLECTION_DATE];

            $apiIRClosedDate = $array[self::API_IR_CLOSED_DATE]?? Carbon::now()->timestamp;

            $customerOnboardingTat = self::hourDifferenceBetweenTimestamps($docCollectionDate, $apiIRClosedDate);
        }

        $array[self::CUSTOMER_ONBOARDING_TAT] = $customerOnboardingTat;
    }

    public function setPublicBankPocNameAttribute(array & $array)
    {
        $bankPocUser = $this->getBankPOCUser();

        if(empty($bankPocUser))
        {
            $array[self::BANK_POC_NAME] = null;

            return;
        }

        $array[self::BANK_POC_NAME] = $bankPocUser->getName();
    }

}
