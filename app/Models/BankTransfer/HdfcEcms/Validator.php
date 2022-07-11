<?php

namespace RZP\Models\BankTransfer\HdfcEcms;

use RZP\Models\BankTransfer;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends BankTransfer\Validator
{
    protected static $allowedModes = [
        BankTransfer\Mode::RTGS,
        BankTransfer\Mode::NEFT,
        BankTransfer\Mode::IMPS,
        BankTransfer\Mode::UPI,
        BankTransfer\Mode::FT
    ];

    protected static $hdfcEcmsRules = [
        Entity::TRANSACTION_DATE        => 'required|string',
        Entity::ACCOUNT_NUMBER          => 'nullable|string',
        Entity::CLIENT_CODE             => 'required|string',
        Entity::VIRTUAL_ACCOUNT_NO      => 'required|string',
        Entity::BENE_NAME               => 'nullable|string',
        Entity::TRANSACTION_DESCRIPTION => 'required|string',
        Entity::DEBIT_CREDIT            => 'nullable|string',
        Entity::CHEQUE_NO               => 'nullable|string',
        Entity::REFERENCE_NO            => 'required|string|alpha_num',
        Entity::AMOUNT                  => 'required|string',
        Entity::TYPE                    => 'required|string',
        Entity::REMITTER_IFSC           => 'nullable|string',
        Entity::REMITTER_BANK_NAME      => 'nullable|string',
        Entity::REMITTING_BANK_BRANCH   => 'nullable|string',
        Entity::REMITTER_ACCOUNT_NO     => 'nullable|string',
        Entity::REMITTER_NAME           => 'nullable|string',
        Entity::USER_ID                 => 'nullable|string',
        Entity::UNIQUE_ID               => 'required|string|alpha_num|custom',
    ];

    public function validateUniqueID($key, $value, $data)
    {
        if(is_string($value) === false or ctype_alnum($value) === false)
        {
            throw new BadRequestValidationFailureException(
                StatusCode::INVALID_UTR,
                null,
                "not an alphanumeric string"
            );
        }
    }

    public function validateRequestPayload(array $requestPayload)
    {
        $this->validateInput('hdfcEcms', $requestPayload);

        $mode = strtolower($requestPayload[Entity::TYPE]);

        if (self::isModeValid($mode) === false)
        {
            throw new BadRequestValidationFailureException('Invalid Type: ' . $requestPayload[Entity::TYPE], null, $requestPayload);
        }
    }

    public static function isModeValid($mode)
    {
        return in_array($mode, self::$allowedModes);
    }
}
