<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Base;
use RZP\Models\Merchant;

/**
 * Class Entity
 *
 * @property Merchant\Entity $merchant
 *
 * @package RZP\Models\Merchant\Detail
 */
class Entity extends Base\PublicEntity
{
    const MERCHANT_ID                       =      'merchant_id';
    const CONTACT_NAME                      =      'contact_name';
    const CONTACT_EMAIL                     =      'contact_email';
    const CONTACT_MOBILE                    =      'contact_mobile';
    const CONTACT_LANDLINE                  =      'contact_landline';
    const BUSINESS_TYPE                     =      'business_type';
    const BUSINESS_NAME                     =      'business_name';
    const BUSINESS_DBA                      =      'business_dba';
    const BUSINESS_WEBSITE                  =      'business_website';
    const BUSINESS_INTERNATIONAL            =      'business_international';
    const BUSINESS_PAYMENTDETAILS           =      'business_paymentdetails';
    const BUSINESS_MODEL                    =      'business_model';
    const BUSINESS_REGISTERED_ADDRESS       =      'business_registered_address';
    const BUSINESS_REGISTERED_STATE         =      'business_registered_state';
    const BUSINESS_REGISTERED_CITY          =      'business_registered_city';
    const BUSINESS_REGISTERED_PIN           =      'business_registered_pin';
    const BUSINESS_OPERATION_ADDRESS        =      'business_operation_address';
    const BUSINESS_OPERATION_STATE          =      'business_operation_state';
    const BUSINESS_OPERATION_CITY           =      'business_operation_city';
    const BUSINESS_OPERATION_PIN            =      'business_operation_pin';
    const BUSINESS_DOE                      =      'business_doe';
    const GSTIN                             =      'gstin'; // Goods and Services Tax Identification Number
    const P_GSTIN                           =      'p_gstin';
    const COMPANY_CIN                       =      'company_cin';
    const COMPANY_PAN                       =      'company_pan';
    const COMPANY_PAN_NAME                  =      'company_pan_name';
    const TRANSACTION_VOLUME                =      'transaction_volume';
    const TRANSACTION_VALUE                 =      'transaction_value';
    const PROMOTER_PAN                      =      'promoter_pan';
    const PROMOTER_PAN_NAME                 =      'promoter_pan_name';
    const BANK_NAME                         =      'bank_name';
    const BANK_ACCOUNT_NUMBER               =      'bank_account_number';
    const BANK_ACCOUNT_NAME                 =      'bank_account_name';
    const BANK_ACCOUNT_TYPE                 =      'bank_account_type';
    const BANK_BRANCH                       =      'bank_branch';
    const BANK_BRANCH_IFSC                  =      'bank_branch_ifsc';
    const BANK_BENEFICIARY_ADDRESS1         =      'bank_beneficiary_address1';
    const BANK_BENEFICIARY_ADDRESS2         =      'bank_beneficiary_address2';
    const BANK_BENEFICIARY_ADDRESS3         =      'bank_beneficiary_address3';
    const BANK_BENEFICIARY_CITY             =      'bank_beneficiary_city';
    const BANK_BENEFICIARY_STATE            =      'bank_beneficiary_state';
    const BANK_BENEFICIARY_PIN              =      'bank_beneficiary_pin';
    const WEBSITE_ABOUT                     =      'website_about';
    const WEBSITE_CONTACT                   =      'website_contact';
    const WEBSITE_PRIVACY                   =      'website_privacy';
    const WEBSITE_TERMS                     =      'website_terms';
    const WEBSITE_REFUND                    =      'website_refund';
    const WEBSITE_PRICING                   =      'website_pricing';
    const WEBSITE_LOGIN                     =      'website_login';
    const BUSINESS_PROOF_URL                =      'business_proof_url';
    const BUSINESS_OPERATION_PROOF_URL      =      'business_operation_proof_url';
    const BUSINESS_PAN_URL                  =      'business_pan_url';
    const ADDRESS_PROOF_URL                 =      'address_proof_url';
    const PROMOTER_PROOF_URL                =      'promoter_proof_url';
    const PROMOTER_PAN_URL                  =      'promoter_pan_url';
    const PROMOTER_ADDRESS_URL              =      'promoter_address_url';
    const TRANSACTION_REPORT_EMAIL          =      'transaction_report_email';
    const COMMENT                           =      'comment';
    const ROLE                              =      'role';
    const DEPARTMENT                        =      'department';
    const STEPS_FINISHED                    =      'steps_finished';
    const ACTIVATION_PROGRESS               =      'activation_progress';
    const LOCKED                            =      'locked';
    const SUBMITTED                         =      'submitted';
    const SUBMITTED_AT                      =      'submitted_at';
    const CREATED_AT                        =      'created_at';
    const UPDATED_AT                        =      'updated_at';

    const SUBMIT                            =       'submit';

    protected $entity = 'merchant_detail';

    protected $primaryKey = self::MERCHANT_ID;

    protected $fillable = [
        self::CONTACT_NAME,
        self::CONTACT_EMAIL,
        self::CONTACT_MOBILE,
        self::CONTACT_LANDLINE,
        self::BUSINESS_TYPE,
        self::BUSINESS_NAME,
        self::BUSINESS_DBA,
        self::BUSINESS_WEBSITE,
        self::BUSINESS_INTERNATIONAL,
        self::BUSINESS_PAYMENTDETAILS,
        self::BUSINESS_MODEL,
        self::BUSINESS_REGISTERED_ADDRESS,
        self::BUSINESS_REGISTERED_STATE,
        self::BUSINESS_REGISTERED_CITY,
        self::BUSINESS_REGISTERED_PIN,
        self::BUSINESS_OPERATION_ADDRESS,
        self::BUSINESS_OPERATION_STATE,
        self::BUSINESS_OPERATION_CITY,
        self::BUSINESS_OPERATION_PIN,
        self::BUSINESS_DOE,
        self::GSTIN,
        self::P_GSTIN,
        self::COMPANY_CIN,
        self::COMPANY_PAN,
        self::COMPANY_PAN_NAME,
        self::TRANSACTION_VOLUME,
        self::TRANSACTION_VALUE,
        self::PROMOTER_PAN,
        self::PROMOTER_PAN_NAME,
        self::BANK_NAME,
        self::BANK_ACCOUNT_NUMBER,
        self::BANK_ACCOUNT_NAME,
        self::BANK_ACCOUNT_TYPE,
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
        self::TRANSACTION_REPORT_EMAIL,
        self::ROLE,
        self::DEPARTMENT,
        self::COMMENT,
        self::STEPS_FINISHED,
        self::LOCKED,
        self::SUBMITTED,
        self::SUBMITTED_AT,
    ];

    protected $public = [
        self::CONTACT_NAME,
        self::CONTACT_EMAIL,
        self::CONTACT_MOBILE,
        self::CONTACT_LANDLINE,
        self::BUSINESS_TYPE,
        self::BUSINESS_NAME,
        self::BUSINESS_DBA,
        self::BUSINESS_WEBSITE,
        self::BUSINESS_INTERNATIONAL,
        self::BUSINESS_PAYMENTDETAILS,
        self::BUSINESS_REGISTERED_ADDRESS,
        self::BUSINESS_REGISTERED_STATE,
        self::BUSINESS_REGISTERED_CITY,
        self::BUSINESS_REGISTERED_PIN,
        self::BUSINESS_OPERATION_ADDRESS,
        self::BUSINESS_OPERATION_STATE,
        self::BUSINESS_OPERATION_CITY,
        self::BUSINESS_OPERATION_PIN,
        self::PROMOTER_PAN,
        self::PROMOTER_PAN_NAME,
        self::BUSINESS_DOE,
        self::GSTIN,
        self::P_GSTIN,
        self::COMPANY_CIN,
        self::COMPANY_PAN,
        self::COMPANY_PAN_NAME,
        self::BUSINESS_MODEL,
        self::TRANSACTION_VOLUME,
        self::TRANSACTION_VALUE,
        self::BUSINESS_WEBSITE,
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
        self::SUBMITTED,
        self::SUBMITTED_AT,
        self::TRANSACTION_REPORT_EMAIL,
        self::BANK_ACCOUNT_NUMBER,
        self::BANK_ACCOUNT_NAME,
        self::BANK_ACCOUNT_TYPE,
        self::BANK_BRANCH,
        self::BANK_BRANCH_IFSC,
        self::BANK_BENEFICIARY_ADDRESS1,
        self::BANK_BENEFICIARY_ADDRESS2,
        self::BANK_BENEFICIARY_ADDRESS3,
        self::BANK_BENEFICIARY_CITY,
        self::BANK_BENEFICIARY_STATE,
        self::BANK_BENEFICIARY_PIN,
        self::BUSINESS_PROOF_URL,
        self::BUSINESS_PAN_URL,
        self::ADDRESS_PROOF_URL,
        self::PROMOTER_ADDRESS_URL,
        self::PROMOTER_PAN_URL,
        self::ROLE,
        self::DEPARTMENT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::SUBMITTED_AT        => null,
        self::ACTIVATION_PROGRESS => 0,
        self::GSTIN               => null,
        self::P_GSTIN             => null,
    ];

    protected $casts = [
        self::LOCKED                 => 'bool',
        self::SUBMITTED              => 'bool',
        self::BUSINESS_INTERNATIONAL => 'bool',
        self::ACTIVATION_PROGRESS    => 'int',
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

    protected $eventFields = [
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

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity', self::MERCHANT_ID, 'id');
    }

    public function isLocked()
    {
        return ($this->getAttribute(self::LOCKED) === true);
    }

    public function setLocked(bool $locked)
    {
        $this->setAttribute(self::LOCKED, $locked);
    }

    public function isSubmitted()
    {
        return ($this->getAttribute(self::SUBMITTED) === true);
    }

    public function getGstin()
    {
        return $this->getAttribute(self::GSTIN);
    }

    public function getPGstin()
    {
        return $this->getAttribute(self::P_GSTIN);
    }

    public function getBusinessRegisteredAddress()
    {
        return $this->getAttribute(self::BUSINESS_REGISTERED_ADDRESS);
    }

    public function getBusinessStateCode()
    {
        $gstin = $this->getGstin() ?? $this->getPGstin();

        return self::getBusinessStateCodeFromGstin($gstin);
    }

    public static function getBusinessStateCodeFromGstin(string $gstin = null)
    {
        if (empty($gstin) === true)
        {
            return null;
        }

        return substr($gstin, 0, 2);
    }

    public function setContactEmail($email)
    {
        $this->setAttribute(self::CONTACT_EMAIL, $email);
    }

    public function setActivationProgress($activationProgress)
    {
        $this->setAttribute(self::ACTIVATION_PROGRESS, $activationProgress);
    }

    public function getActivationProgress()
    {
        return $this->getAttribute(self::ACTIVATION_PROGRESS);
    }

    public function getContactMobile()
    {
        return $this->getAttribute(self::CONTACT_MOBILE);
    }

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
}
