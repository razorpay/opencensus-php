<?php


namespace RZP\Models\VirtualAccountTpv;

use RZP\Base;
use RZP\Models\VirtualAccount\AllowedPayerType;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $addAllowedPayerRules = [
        Entity::TYPE                                            => 'required|string|custom',
        AllowedPayerType::BANK_ACCOUNT                          => 'required_if:type,bank_account',
        AllowedPayerType::BANK_ACCOUNT . '.' . 'ifsc'           => 'required_with:bank_account',
        AllowedPayerType::BANK_ACCOUNT . '.' . 'account_number' => 'required_with:bank_account',

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
}
