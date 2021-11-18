<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Admin;
use RZP\Models\BankingAccount;

/**
 * This captures the details that Sales POCs enter into the admin dashboard
 * which need to be exported and sent to RBl.
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

    // This additional_details column will store tags of a lead in json format.

    const ADDITIONAL_DETAILS = 'additional_details';

    const COMMENT = 'comment';

    // team that is currently assigned to work on this
    const ASSIGNEE_TEAM = 'assignee_team';

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

    // relations
    // admin_audit_map is used here
    const SALES_POC_ID = 'sales_poc_id';

    //sales_poc_name and sales_poc_email are not part of entity, these are just constants needed for ToArrayPublic
    const SALES_POC_NAME = 'sales_poc_name';

    const SALES_POC_EMAIL = 'sales_poc_email';

    // Indicates Application is filled completely from merchant dashboard
    const DECLARATION_STEP = 'declaration_step';

    const BUSINESS_PAN_VALIDATION = 'business_pan_validation';

    const ADMIN_EMAIL = 'admin_email';

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
        self::BOOKING_DATE_AND_TIME,
        self::SALES_TEAM,
        self::SALES_POC_PHONE_NUMBER,
        self::RM_NAME,
        self::RM_PHONE_NUMBER,
        self::ACCOUNT_OPEN_DATE,
        self::ACCOUNT_LOGIN_DATE,
        self::BUSINESS_NAME,
        self::BUSINESS_TYPE,
        self::BUSINESS_PAN,
        self::DECLARATION_STEP,
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
        self::AVERAGE_MONTHLY_BALANCE,
        self::ACCOUNT_TYPE,
        self::IS_DOCUMENTS_WALKTHROUGH_COMPLETE,
        self::SALES_TEAM,
        self::SALES_POC_PHONE_NUMBER,
        self::ASSIGNEE_TEAM,
        self::BOOKING_DATE_AND_TIME,
        self::COMMENT,
        self::RM_NAME,
        self::DECLARATION_STEP,
        self::BUSINESS_PAN_VALIDATION,
        self::RM_PHONE_NUMBER,
        self::ACCOUNT_OPEN_DATE,
        self::ACCOUNT_LOGIN_DATE,
        self::CREATED_AT,
        self::ADDITIONAL_DETAILS,
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
        self::AVERAGE_MONTHLY_BALANCE,
        self::ACCOUNT_TYPE,
        self::IS_DOCUMENTS_WALKTHROUGH_COMPLETE,
        self::SALES_TEAM,
        self::SALES_POC_PHONE_NUMBER,
        self::ASSIGNEE_TEAM,
        self::BOOKING_DATE_AND_TIME,
        self::DECLARATION_STEP,
        self::BUSINESS_PAN_VALIDATION,
        self::COMMENT,
        self::RM_NAME,
        self::RM_PHONE_NUMBER,
        self::ACCOUNT_OPEN_DATE,
        self::ACCOUNT_LOGIN_DATE,
        self::CREATED_AT,
        self::ADDITIONAL_DETAILS,
    ];

    protected $publicSetters = [
        self::ADDITIONAL_DETAILS,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
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

    public function setPanVerificationStatus(string $status)
    {
        $this->setAttribute(self::BUSINESS_PAN_VALIDATION, $status);
    }

    public function setContactMobileVerified(bool $verified)
    {
        $this->setAttribute(self::CONTACT_VERIFIED, $verified);
    }

    public function setSalesTeam(string $salesTeam)
    {
        $this->setAttribute(self::SALES_TEAM, $salesTeam);
    }

    public function getSalesTeam()
    {
        return $this->getAttribute(self::SALES_TEAM);
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

    public function isAssigneeTeamChanged()
    {
        return $this->isDirty(self::ASSIGNEE_TEAM);
    }

    public function isAssigneeTeamUpdated()
    {
        return array_key_exists(self::ASSIGNEE_TEAM, $this->getChanges());
    }

    public function setPublicAdditionalDetailsAttribute(array &$array)
    {
        if (app('basicauth')->isAdminAuth() === true and
            isset($array[self::ADDITIONAL_DETAILS]) === true) {

            $array[self::ADDITIONAL_DETAILS] = json_decode($array[self::ADDITIONAL_DETAILS], true);

        }
    }

    public function getAdditionalDetails()
    {
        return $this->getAttributeValue(self::ADDITIONAL_DETAILS);
    }
}
