<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Base;
use RZP\Exception;
use Razorpay\IFSC\IFSC;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    const INVALID_IFSC_CODE_MESSAGE = 'Invalid IFSC Code';

    protected static $createRules = [
        Entity::CONTACT_NAME                    => 'sometimes|alpha_space|max:255',
        Entity::CONTACT_EMAIL                   => 'sometimes|email|max:255',
        Entity::CONTACT_MOBILE                  => 'sometimes|numeric|digits_between:8,11',
        Entity::CONTACT_LANDLINE                => 'sometimes|numeric|digits_between:8,11',
        Entity::BUSINESS_TYPE                   => 'sometimes|numeric|digits_between:1,10',
        Entity::BUSINESS_NAME                   => 'sometimes|max:255',
        Entity::BUSINESS_DBA                    => 'sometimes|max:255',
        Entity::BUSINESS_WEBSITE                => 'sometimes|max:255|url',
        Entity::BUSINESS_INTERNATIONAL          => 'sometimes|in:0,1',
        Entity::BUSINESS_PAYMENTDETAILS         => 'sometimes|max:2000',
        Entity::BUSINESS_MODEL                  => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_ADDRESS     => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_STATE       => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_CITY        => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_PIN         => 'sometimes|max:15',
        Entity::BUSINESS_OPERATION_ADDRESS      => 'sometimes|max:255',
        Entity::BUSINESS_OPERATION_STATE        => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_CITY         => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_PIN          => 'sometimes|max:15',
        Entity::BUSINESS_DOE                    => 'sometimes|date_format:"Y-m-d"|before:"today"',
        Entity::GSTIN                           => 'sometimes|string|size:15',
        Entity::P_GSTIN                         => 'sometimes|string|size:15',
        Entity::COMPANY_CIN                     => 'sometimes|alpha_num|max:21',
        Entity::COMPANY_PAN                     => 'sometimes|alpha_num|max:15',
        Entity::COMPANY_PAN_NAME                => 'sometimes|max:255',
        Entity::TRANSACTION_VOLUME              => 'sometimes|numeric|digits_between:1,4',
        Entity::TRANSACTION_VALUE               => 'filled|numeric|min:0|max:10000000',
        Entity::PROMOTER_PAN                    => 'sometimes|alpha_num|max:15',
        Entity::PROMOTER_PAN_NAME               => 'sometimes|max:255',
        Entity::BANK_NAME                       => 'sometimes|alpha_num|between:5,20',
        Entity::BANK_ACCOUNT_NUMBER             => 'sometimes|alpha_num|between:5,20',
        Entity::BANK_ACCOUNT_NAME               => 'sometimes|alpha_space_num|max:40',
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
        Entity::BUSINESS_NAME                   => 'sometimes|max:255',
        Entity::BUSINESS_DBA                    => 'sometimes|max:255',
        Entity::BUSINESS_WEBSITE                => 'sometimes|max:255|url',
        Entity::BUSINESS_INTERNATIONAL          => 'sometimes|in:0,1',
        Entity::BUSINESS_PAYMENTDETAILS         => 'sometimes|max:2000',
        Entity::BUSINESS_MODEL                  => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_ADDRESS     => 'sometimes|max:255',
        Entity::BUSINESS_REGISTERED_STATE       => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_CITY        => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_PIN         => 'sometimes|max:15',
        Entity::BUSINESS_OPERATION_ADDRESS      => 'sometimes|max:255',
        Entity::BUSINESS_OPERATION_STATE        => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_CITY         => 'sometimes|alpha_space|max:255',
        Entity::BUSINESS_OPERATION_PIN          => 'sometimes|max:15',
        Entity::BUSINESS_DOE                    => 'sometimes|date_format:"Y-m-d"|before:"today"',
        Entity::GSTIN                           => 'sometimes|string|size:15',
        Entity::P_GSTIN                         => 'sometimes|string|size:15',
        Entity::COMPANY_CIN                     => 'sometimes|alpha_num|max:21',
        Entity::COMPANY_PAN                     => 'sometimes|alpha_num|max:15',
        Entity::COMPANY_PAN_NAME                => 'sometimes|max:255',
        Entity::TRANSACTION_VOLUME              => 'sometimes|numeric|digits_between:1,4',
        Entity::TRANSACTION_VALUE               => 'filled|numeric|min:0|max:10000000',
        Entity::PROMOTER_PAN                    => 'sometimes|alpha_num|max:15',
        Entity::PROMOTER_PAN_NAME               => 'sometimes|max:255',
        Entity::BANK_NAME                       => 'sometimes|alpha_num|between:5,20',
        Entity::BANK_ACCOUNT_NUMBER             => 'sometimes|alpha_num|between:5,20',
        Entity::BANK_ACCOUNT_NAME               => 'sometimes|alpha_space_num|max:40',
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
        Entity::TRANSACTION_REPORT_EMAIL        => 'sometimes|custom',
        Entity::ROLE                            => 'sometimes|max:255',
        Entity::DEPARTMENT                      => 'sometimes|max:255',
        Entity::LOCKED                          => 'sometimes|boolean',
        Entity::COMMENT                         => 'sometimes|max:255',
        Entity::SUBMIT                          => 'sometimes|boolean',
    ];

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

    public function validateIsNotLocked($accountCheck = false)
    {
        $errorCode = ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED;

        if ($accountCheck === true)
        {
            $errorCode = ErrorCode::BAD_REQUEST_ACCOUNT_LOCKED;
        }

        if ($this->entity->isLocked() === true)
        {
            throw new Exception\BadRequestException($errorCode);
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
}
