<?php

namespace RZP\Models\VirtualAccountTpv;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\VirtualAccount\AllowedPayerType;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $addAllowedPayerRules = [
        Entity::TYPE                                            => 'required|string|custom',
        AllowedPayerType::BANK_ACCOUNT                          => 'required_if:type,bank_account',
        AllowedPayerType::BANK_ACCOUNT . '.' . 'ifsc'           => 'required_with:bank_account|alpha_num|size:11',
        AllowedPayerType::BANK_ACCOUNT . '.' . 'account_number' => 'required_with:bank_account',
    ];

    const ERROR_DESC_BANK_ACCOUNT_NOT_PRESENT = 'The bank account.account number field is required when bank account is present.';
    const ERROR_DESC_IFSC_NOT_PRESENT         = 'The bank account.ifsc field is required when bank account is present.';
    const ERROR_DESC_IFSC_INVALID             = 'The bank account.ifsc must be 11 characters.';

    protected static $errorDescriptionMapping = [
        self::ERROR_DESC_IFSC_NOT_PRESENT         =>
            [
                'error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_ALLOWED_PAYER_IFSC_REQUIRED,
                'field_name' => 'ifsc'
            ],
        self::ERROR_DESC_BANK_ACCOUNT_NOT_PRESENT =>
            [
                'error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_ALLOWED_PAYER_BANK_ACCOUNT_REQUIRED,
                'field_name' => 'account_number'
            ],
        self::ERROR_DESC_IFSC_INVALID             =>
            [
                'error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_ALLOWED_PAYER_INVALID_IFSC,
                'field_name' => 'ifsc'
            ]
    ];

    protected function validateType($attribute, $input)
    {
        if (AllowedPayerType::isValid($input) === false)
        {
            throw new BadRequestValidationFailureException($input . ' is not an allowed payer type.');
        }
    }

    public function validateAllowedPayers(array $payers)
    {
        foreach ($payers as $payer)
        {
            $this->validateAllowedPayer($payer);
        }
    }

    public function validateAllowedPayer($payer)
    {
        $this->validateInput('add_allowed_payer', $payer);
    }

    public function updateErrorMessages(&$value, $key)
    {
        if (array_key_exists($value, self::$errorDescriptionMapping))
        {
            throw new Exception\BadRequestException(
                self::$errorDescriptionMapping[$value]['error_code'],
                self::$errorDescriptionMapping[$value]['field_name']
            );
        }
    }

    protected function processValidationFailure($messages, $operation, $input)
    {
        $messagesArray = $messages->getMessages();

        array_walk_recursive($messagesArray, 'self::updateErrorMessages');

        parent::processValidationFailure($messages, $operation, $input);
    }
}
