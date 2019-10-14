<?php

namespace RZP\Models\Merchant\Detail;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use Razorpay\IFSC\IFSC;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Detail\ActivationFlow\Factory;

class Validator extends Base\Validator
{
    const INVALID_REVIEWER                              = 'Invalid reviewer';
    const INVALID_MERCHANTS                             = 'Invalid merchants';
    const INVALID_STATUS_MESSAGE                        = 'Invalid status';
    const INVALID_IFSC_CODE_MESSAGE                     = 'Invalid IFSC Code';
    const INVALID_STATUS_CHANGE_MESSAGE                 = 'Invalid status change';
    const INVALID_CLARIFICATION_MODE_MESSAGE            = 'Invalid clarification mode';
    const INVALID_FILE_NON_NGO_ORGANISATION_TYPE        = 'Invalid file for non NGO organisation type';
    const INVALID_CLARIFICATION_MODE_FOR_STATUS_MESSAGE = 'Clarification mode should not be sent for this status';
    const INVALID_BUSINESS_CATEGORY                     = 'Invalid business category';
    const INVALID_BUSINESS_SUBCATEGORY                  = 'Invalid business subcategory';
    const INVALID_BUSINESS_SUBCATEGORY_FOR_CATEGORY     = 'Invalid business subcategory for business category';
    const BUSINESS_CATEGORY_MISSING_FOR_SUBCATEGORY     = 'Business category missing for business subcategory';

    // Constant representing operations for which Validation rules exists
    const BULK_EDIT                                     = 'bulkEdit';

    protected static $createRules = [
        Entity::CONTACT_NAME                    => 'sometimes|alpha_space|max:255',
        Entity::CONTACT_EMAIL                   => 'sometimes|email|max:255',
        Entity::CONTACT_MOBILE                  => 'sometimes|numeric|digits_between:8,11',
        Entity::CONTACT_LANDLINE                => 'sometimes|numeric|digits_between:8,11',
        Entity::BUSINESS_TYPE                   => 'sometimes|numeric|digits_between:1,10',
        Entity::BUSINESS_NAME                   => 'sometimes|string|max:255',
        Entity::BUSINESS_DESCRIPTION            => 'sometimes|string|max:255',
        Entity::BUSINESS_DBA                    => 'sometimes|string|max:255',
        Entity::BUSINESS_WEBSITE                => 'sometimes|active_url|max:255|nullable',
        Entity::BUSINESS_INTERNATIONAL          => 'sometimes|in:0,1',
        Entity::BUSINESS_PAYMENTDETAILS         => 'sometimes|max:2000',
        Entity::BUSINESS_MODEL                  => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_ADDRESS     => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_STATE       => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_CITY        => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_DISTRICT    => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_PIN         => 'sometimes|max:15',
        Entity::BUSINESS_OPERATION_ADDRESS      => 'sometimes|max:255',
        Entity::BUSINESS_OPERATION_STATE        => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_CITY         => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_DISTRICT     => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_PIN          => 'sometimes|max:15',
        Entity::BUSINESS_DOE                    => 'sometimes|date_format:"Y-m-d"|before:"today"',
        Entity::GSTIN                           => 'filled|string|size:15|nullable',
        Entity::P_GSTIN                         => 'filled|string|size:15',
        Entity::COMPANY_CIN                     => 'filled|alpha_num|max:21',
        Entity::COMPANY_PAN                     => 'filled|pan',
        Entity::COMPANY_PAN_NAME                => 'filled|max:255',
        Entity::BUSINESS_CATEGORY               => 'filled|max:255|custom',
        Entity::BUSINESS_SUBCATEGORY            => 'filled|max:255|custom',
        Entity::TRANSACTION_VOLUME              => 'sometimes|numeric|digits_between:1,4',
        Entity::TRANSACTION_VALUE               => 'filled|numeric|min:0|max:10000000',
        Entity::PROMOTER_PAN                    => 'sometimes|pan',
        Entity::PROMOTER_PAN_NAME               => 'sometimes|max:255',
        Entity::BANK_NAME                       => 'sometimes|alpha_num|between:5,20',
        Entity::BANK_ACCOUNT_NUMBER             => 'sometimes|regex:/^[a-zA-Z0-9-]+$/|between:5,20',
        Entity::BANK_ACCOUNT_NAME               => 'sometimes|string|min:4|max:120',
        Entity::BANK_ACCOUNT_TYPE               => 'sometimes|alpha_space|max:20',
        Entity::BANK_BRANCH                     => 'sometimes|max:255',
        Entity::BANK_BRANCH_IFSC                => 'sometimes|alpha_num|max:11|custom',
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
        Entity::BUSINESS_PROOF_URL              => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::BUSINESS_OPERATION_PROOF_URL    => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::BUSINESS_PAN_URL                => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::ADDRESS_PROOF_URL               => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::PROMOTER_PROOF_URL              => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::PROMOTER_PAN_URL                => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::PROMOTER_ADDRESS_URL            => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::FORM_12A_URL                    => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip|custom',
        Entity::FORM_80G_URL                    => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip|custom',
        Entity::TRANSACTION_REPORT_EMAIL        => 'sometimes|custom',
        Entity::ROLE                            => 'sometimes|max:255',
        Entity::DEPARTMENT                      => 'sometimes|max:255',
        Entity::LOCKED                          => 'sometimes|boolean',
        Entity::COMMENT                         => 'sometimes|max:255',
        Entity::SUBMIT                          => 'sometimes',
    ];

    protected static $editRules = [
        Entity::CONTACT_NAME                    => 'sometimes|alpha_space|max:255',
        Entity::CONTACT_EMAIL                   => 'sometimes|email|max:255',
        Entity::CONTACT_MOBILE                  => 'sometimes|numeric|digits_between:8,11',
        Entity::CONTACT_LANDLINE                => 'sometimes|numeric|digits_between:8,11',
        Entity::BUSINESS_TYPE                   => 'sometimes|numeric|digits_between:1,10',
        Entity::BUSINESS_NAME                   => 'filled|max:255',
        Entity::BUSINESS_DESCRIPTION            => 'filled|max:255',
        Entity::BUSINESS_DBA                    => 'sometimes|max:255',
        Entity::BUSINESS_WEBSITE                => 'sometimes|active_url|max:255|nullable',
        Entity::BUSINESS_INTERNATIONAL          => 'sometimes|in:0,1',
        Entity::BUSINESS_PAYMENTDETAILS         => 'sometimes|max:2000',
        Entity::BUSINESS_MODEL                  => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_ADDRESS     => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_ADDRESS_L2  => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_STATE       => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_COUNTRY     => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_CITY        => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_DISTRICT    => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_PIN         => 'sometimes|max:15',
        Entity::BUSINESS_OPERATION_ADDRESS      => 'sometimes|max:255',
        Entity::BUSINESS_OPERATION_ADDRESS_L2   => 'sometimes|max:255',
        Entity::BUSINESS_OPERATION_STATE        => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_COUNTRY      => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_CITY         => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_DISTRICT     => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_PIN          => 'sometimes|max:15',
        Entity::BUSINESS_DOE                    => 'sometimes|date_format:"Y-m-d"|before:"today"',
        Entity::GSTIN                           => 'sometimes|string|size:15|nullable',
        Entity::P_GSTIN                         => 'sometimes|string|size:15',
        Entity::COMPANY_CIN                     => 'sometimes|alpha_num|max:21',
        Entity::COMPANY_PAN                     => 'sometimes|pan',
        Entity::COMPANY_PAN_NAME                => 'sometimes|max:255',
        Entity::BUSINESS_CATEGORY               => 'sometimes|max:255|custom',
        Entity::BUSINESS_SUBCATEGORY            => 'sometimes|max:255|custom',
        Entity::TRANSACTION_VOLUME              => 'sometimes|numeric|digits_between:1,4',
        Entity::TRANSACTION_VALUE               => 'filled|numeric|min:0|max:10000000',
        Entity::PROMOTER_PAN                    => 'sometimes|pan',
        Entity::PROMOTER_PAN_NAME               => 'sometimes|max:255',
        Entity::BANK_NAME                       => 'sometimes|alpha_num|between:5,20',
        Entity::BANK_ACCOUNT_NUMBER             => 'sometimes|regex:/^[a-zA-Z0-9-]+$/|between:5,22',
        Entity::BANK_ACCOUNT_NAME               => 'sometimes|string|min:4|max:120',
        Entity::BANK_ACCOUNT_TYPE               => 'sometimes|alpha_space|max:20',
        Entity::BANK_BRANCH                     => 'sometimes|max:255',
        Entity::BANK_BRANCH_IFSC                => 'sometimes|alpha_num|max:11|custom',
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
        Entity::BUSINESS_PROOF_URL              => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::BUSINESS_OPERATION_PROOF_URL    => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::BUSINESS_PAN_URL                => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::ADDRESS_PROOF_URL               => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::PROMOTER_PROOF_URL              => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::PROMOTER_PAN_URL                => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::PROMOTER_ADDRESS_URL            => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::FORM_12A_URL                    => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip|custom',
        Entity::FORM_80G_URL                    => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip|custom',
        Entity::TRANSACTION_REPORT_EMAIL        => 'sometimes|custom',
        Entity::ROLE                            => 'sometimes|max:255',
        Entity::DEPARTMENT                      => 'sometimes|max:255',
        Entity::LOCKED                          => 'sometimes|boolean',
        Entity::COMMENT                         => 'sometimes|max:255',
        Entity::SUBMIT                          => 'sometimes|boolean',
        Entity::ACTIVATION_STATUS               => 'sometimes|max:30',
        Entity::CLARIFICATION_MODE              => 'sometimes|max:15',
        Entity::ISSUE_FIELDS                    => 'sometimes|string',
        Entity::ISSUE_FIELDS_REASON             => 'sometimes|string',
        Entity::INTERNAL_NOTES                  => 'sometimes|string',
        Entity::INTERNATIONAL_ACTIVATION_FLOW   => 'sometimes|custom',
        Entity::CUSTOM_FIELDS                   => 'filled|array',
    ];

    protected static $preSignupRules = [
        Entity::BUSINESS_TYPE                   => 'sometimes|numeric|digits_between:1,10',
        Entity::COUPON_CODE                     => 'filled|string|max:10',
        Entity::TRANSACTION_VOLUME              => 'sometimes|numeric|digits_between:1,4',
        Entity::ROLE                            => 'sometimes|numeric|digits_between:1,6',
        Entity::DEPARTMENT                      => 'sometimes|numeric|digits_between:1,7',
        Entity::BUSINESS_NAME                   => 'sometimes|string|max:255',
        Entity::CONTACT_NAME                    => 'sometimes|alpha_space|max:255',
        Entity::CONTACT_MOBILE                  => 'sometimes|numeric|digits_between:8,11',
        Entity::BUSINESS_WEBSITE                => 'sometimes|active_url|max:255|nullable',
    ];

    protected static $archiveFormRules = [
        Entity::ARCHIVE                         => 'required|boolean',
    ];

    protected static $activationStatusRules = [
        Entity::ACTIVATION_STATUS               => 'required|string|max:30',
        Entity::CLARIFICATION_MODE              => 'filled|string|max:15',
        Entity::REJECTION_REASONS               => 'filled|array',
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
    ];

    protected static $editValidators = [
        'business_subcategory_for_category',
    ];

    protected static $instantActivationRules = [
        Entity::BUSINESS_CATEGORY           => 'required|max:255|custom',
        Entity::BUSINESS_SUBCATEGORY        => 'sometimes|max:255|custom',
        Entity::PROMOTER_PAN                => 'required|pan',
        Entity::PROMOTER_PAN_NAME           => 'sometimes|string|max:255',
        Entity::BUSINESS_NAME               => 'sometimes|string|max:255',
        Entity::BUSINESS_MODEL              => 'sometimes|max:255',
        Entity::BUSINESS_WEBSITE            => 'sometimes|active_url|max:255|nullable',
        Entity::BUSINESS_DBA                => 'required|string|max:255',
        Entity::BUSINESS_TYPE               => 'required|numeric|digits_between:1,10',
        Entity::BUSINESS_OPERATION_ADDRESS  => 'sometimes|max:255',
        Entity::BUSINESS_OPERATION_STATE    => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_CITY     => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_PIN      => 'sometimes|max:15',
        Entity::BUSINESS_REGISTERED_ADDRESS => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_STATE   => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_CITY    => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_PIN     => 'sometimes|max:15',
    ];

    protected static $instantActivationValidators = [
        'registered_business_rules',
        'unregistered_business_rules',
    ];

    protected static $websiteDetailsRules = [
        Entity::BUSINESS_WEBSITE                => 'required|max:255|url',
    ];

    protected static $patchMerchantDetailsRules = [
        Entity::BUSINESS_OPERATION_ADDRESS       => 'filled|max:255',
        Entity::BUSINESS_OPERATION_STATE         => 'filled|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_CITY          => 'filled|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_PIN           => 'filled|max:15',
        Entity::BUSINESS_CATEGORY                => 'sometimes|max:255|custom',
        Entity::BUSINESS_SUBCATEGORY             => 'sometimes|max:255|custom',
        Entity::BUSINESS_MODEL                   => 'sometimes|max:255',
        Entity::INTERNATIONAL_ACTIVATION_FLOW    => 'filled|custom',
        Entity::BANK_DETAILS_VERIFICATION_STATUS => 'filled|custom',
        Entity::POA_VERIFICATION_STATUS          => 'filled|custom'
    ];

    public function validateBankDetailsVerificationStatus($attribute, $value)
    {
        $validBankDetailValidationStatuses = BankDetailsVerificationStatus::ALLOWED_NEXT_BANK_DETAIL_VERIFICATION_STATUSES_MAPPING;

        $this->isAllowedStatusChange(
            $this->entity->getBankDetailsVerificationStatus(),
            $value,
            $validBankDetailValidationStatuses,
            ErrorCode::BAD_REQUEST_INVALID_BANK_DETAIL_VERIFICATION_STATUS_CHANGE);

    }

    public function validatePOAVerificationStatus($attribute, $value)
    {
        $validPoaValidationStatuses = PoaVerificationStatus::ALLOWED_NEXT_POA_VERIFICATION_STATUSES_MAPPING;

        $this->isAllowedStatusChange(
            $this->entity->getPoaVerificationStatus(),
            $value,
            $validPoaValidationStatuses,
            ErrorCode::BAD_REQUEST_INVALID_POA_VERIFICATION_STATUS_CHANGE);
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
            return;
        }

        $this->validateForBlackListedCategories($input);

        if (empty($input[Entity::PROMOTER_PAN_NAME]) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_PAN_NAME_REQUIRED);
        }
    }

    protected function validateForBlackListedCategories(array $input)
    {
        $category = array_key_exists(Entity::BUSINESS_CATEGORY, $input) ?
            $input[Entity::BUSINESS_CATEGORY] : $this->entity->getBusinessCategory();

        $subcategory = array_key_exists(Entity::BUSINESS_SUBCATEGORY, $input) ?
            $input[Entity::BUSINESS_SUBCATEGORY] : $this->entity->getBusinessSubcategory();

        $subcategoryMetaData = BusinessSubCategoryMetaData::getSubCategoryMetaData($category, $subcategory);

        if ($subcategoryMetaData[Entity::ACTIVATION_FLOW] === ActivationFlow::BLACKLIST)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_UNSUPPORTED_BUSINESS_CATEGORY);
        }
    }

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

    public function validateActivationStatus(array $input)
    {
        $validActivationStatuses = array_keys(Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING);

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

        if (in_array($newStatus, Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING[$currentStatus], true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_STATUS_CHANGE_MESSAGE);
        }
    }

    public function validateBusinessCategory(string $attribute, string $businessCategory)
    {
        if (isset(BusinessCategory::SUBCATEGORY_MAP[$businessCategory]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_BUSINESS_CATEGORY . ': ' . $businessCategory,
                Entity::BUSINESS_CATEGORY,
                [
                    Entity::BUSINESS_CATEGORY => $businessCategory
                ]);
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

    public function validateBusinessSubcategoryForCategory(array $input)
    {
        // If category and subcategory are not set
        if ((isset($input[Entity::BUSINESS_CATEGORY]) === false) and
            (isset($input[Entity::BUSINESS_SUBCATEGORY]) === false))
        {
            return;
        }

        $category    = array_key_exists(Entity::BUSINESS_CATEGORY, $input) ?
                        $input[Entity::BUSINESS_CATEGORY] : $this->entity->getBusinessCategory();

        $subcategory = array_key_exists(Entity::BUSINESS_SUBCATEGORY, $input) ?
                        $input[Entity::BUSINESS_SUBCATEGORY] : $this->entity->getBusinessSubcategory();

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

        // If category is `others` and subcategory is not `null`
        if (($category === BusinessCategory::OTHERS) and
            (empty($subcategory) === false))
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

    public function validateFileType($file)
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
     * Contains validations for full activation form (L2 activation form)
     * L1 and L2 activation form have different validations
     *
     * In L2 activation form for Blacklist flow -> merchant can't fill L2 form ,
     * no detail will be save in db and validation exception will be thrown
     *
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function validateFullActivationForm()
    {
        $this->validateIsNotLocked();

        if ($this->entity->getActivationFlow() !== null)
        {
            $activationFlowImpl = Factory::getActivationFlowImpl($this->entity);

            $activationFlowImpl->validateFullActivationForm($this->entity);
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
}
