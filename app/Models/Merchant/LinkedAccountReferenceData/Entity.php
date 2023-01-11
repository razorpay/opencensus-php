<?php

namespace RZP\Models\Merchant\LinkedAccountReferenceData;

use RZP\Models\Base;

//TODO check if this is a public entity or normal one
class Entity extends Base\PublicEntity
{
    const ID                       = 'id';
    const BUSINESS_NAME            = 'business_name';
    const BUSINESS_TYPE            = 'business_type';
    const BENEFICIARY_NAME         = 'beneficiary_name';
    const ACCOUNT_NAME             = 'account_name';
    const ACCOUNT_EMAIL            = 'account_email';
    const DASHBOARD_ACCESS         = 'dashboard_access';
    const CUSTOMER_REFUND_ACCESS   = 'customer_refund_access';
    const IFSC_CODE                = 'ifsc_code';
    const ACCOUNT_NUMBER           = 'account_number';
    const CATEGORY                 = 'category';
    const IS_ACTIVE                = 'is_active';
    const CREATED_AT               = 'created_at';
    const UPDATED_AT               = 'updated_at';

    protected $entity             = 'linked_account_reference_data';

    // General use constants
    const LA_REFERENCE_DATA        = 'la_reference_data';
    const SUCCESS                  = 'success';
    const FAILURE                  = 'failure';
    const TOTAL                    = 'total';
    const SUCCESSFUL               = 'successful';
    const FAILED                   = 'failed';
    const DATA                     = 'data';
    const INPUT_COUNT              = 'input_count';

    const ACCOUNT_NAME_LENGTH_MAX     = 255;
    const ACCOUNT_EMAIL_LENGTH_MAX    = 255;
    const BUSINESS_NAME_LENGTH_MAX    = 255;
    const BUSINESS_TYPE_LENGTH_MAX    = 255;
    const ACCOUNT_NUMBER_LENGTH_MAX   = 40;
    const BENEFICIARY_NAME_LENGTH_MAX = 120;
    const CATEGORY_LENGTH_MAX         = 60;
    const IFSC_CODE_LENGTH_MAX        = 11;

    protected static $generators = [
        self::ID,
    ];

    protected $fillable = [
        self::ID,
        self::BUSINESS_TYPE,
        self::BUSINESS_NAME,
        self::BENEFICIARY_NAME,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_EMAIL,
        self::ACCOUNT_NAME,
        self::IFSC_CODE,
        self::CATEGORY,
        self::IS_ACTIVE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DASHBOARD_ACCESS,
        self::CUSTOMER_REFUND_ACCESS,
    ];

    protected $public = [
        self::ID,
        self::BUSINESS_TYPE,
        self::BUSINESS_NAME,
        self::BENEFICIARY_NAME,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_EMAIL,
        self::ACCOUNT_NAME,
        self::IFSC_CODE,
        self::CATEGORY,
        self::DASHBOARD_ACCESS,
        self::CUSTOMER_REFUND_ACCESS,
        self::IS_ACTIVE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $defaults = [
        self::DASHBOARD_ACCESS         => 0,
        self::CUSTOMER_REFUND_ACCESS   => 0,
        self::IS_ACTIVE                => 1,
        self::CATEGORY                 => Category::AMC_BANK_ACCOUNT
    ];

    // ------------------------ Getters ----------------------------

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getBusinessName()
    {
        return $this->getAttribute(self::BUSINESS_NAME);
    }

    public function getBusinessType()
    {
        return $this->getAttribute(self::BUSINESS_TYPE);
    }

    public function getBeneficiaryName()
    {
        return $this->getAttribute(self::BENEFICIARY_NAME);
    }

    public function getAccountName()
    {
        return $this->getAttribute(self::ACCOUNT_NAME);
    }

    public function getAccountEmail()
    {
        return $this->getAttribute(self::ACCOUNT_EMAIL);
    }

    public function getDashboardAccess()
    {
        return $this->getAttribute(self::DASHBOARD_ACCESS);
    }

    public function getCustomerRefundAccess()
    {
        return $this->getAttribute(self::CUSTOMER_REFUND_ACCESS);
    }

    public function getIfscCode()
    {
        return $this->getAttribute(self::IFSC_CODE);
    }

    public function getAccountNumber()
    {
        return $this->getAttribute(self::ACCOUNT_NUMBER);
    }

    public function getCategory()
    {
        return $this->getAttribute(self::CATEGORY);
    }

    public function getIsActive()
    {
        return $this->getAttribute(self::IS_ACTIVE);
    }

    public function getCreatedAt()
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getUpdatedAt()
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    // LA created fof these ref data will have TNC Accepted =  true
    public function getTncAccepted()
    {
        return true;
    }

    // ------------------------ Setters ----------------------------

    public function setBusinessName(string $businessName)
    {
        $this->setAttribute(self::BUSINESS_NAME, $businessName);
    }

    public function setBusinessType(string $businessType)
    {
        $this->setAttribute(self::BUSINESS_TYPE, $businessType);
    }

    public function setBeneficiaryName(string $beneficiaryName)
    {
        return $this->setAttribute(self::BENEFICIARY_NAME, $beneficiaryName);
    }

    public function setAccountName(string $accountName)
    {
        return $this->setAttribute(self::ACCOUNT_NAME, $accountName);
    }

    public function setAccountEmail(string $email)
    {
        return $this->setAttribute(self::ACCOUNT_EMAIL, $email);
    }

    public function setDashboardAccess($dashboardAccess)
    {
        return $this->setAttribute(self::DASHBOARD_ACCESS, $dashboardAccess);
    }

    public function setCustomerRefundAccess($customerRefundAccess)
    {
        return $this->setAttribute(self::CUSTOMER_REFUND_ACCESS, $customerRefundAccess);
    }

    public function setIfscCode(string $ifscCode)
    {
        return $this->setAttribute(self::IFSC_CODE, $ifscCode);
    }

    public function setAccountNumber(string $accNumber)
    {
        return $this->setAttribute(self::ACCOUNT_NUMBER, $accNumber);
    }

    public function setCategory(string $category)
    {
        return $this->setAttribute(self::CATEGORY, $category);
    }

    public function setIsActive()
    {
        return $this->setAttribute(self::IS_ACTIVE, 1);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setAttribute(self::CREATED_AT, $createdAt);
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setAttribute(self::UPDATED_AT, $updatedAt);
    }
}
