<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Base;
use Exception;
use RZP\Exception\BadRequestValidationFailureException;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use RZP\Models\BankingAccountStatement\Generator\SupportedFormats;

class Validator extends Base\Validator
{
    const ACCOUNT_STATEMENT_GENERATE = 'accountStatementGenerate';

    protected static $createRules = [
        Entity::CHANNEL             => 'required|string|custom',
        Entity::ACCOUNT_NUMBER      => 'required|string|max:40',
        Entity::BANK_TRANSACTION_ID => 'required|string',
        Entity::AMOUNT              => 'required|integer',
        Entity::CURRENCY            => 'required|size:3',
        Entity::TYPE                => 'required|string|custom',
        Entity::DESCRIPTION         => 'required|string',
        Entity::CATEGORY            => 'required|string|custom',
        Entity::BANK_SERIAL_NUMBER  => 'required|string',
        Entity::BANK_INSTRUMENT_ID  => 'sometimes|string',
        Entity::BALANCE             => 'required|integer',
        Entity::BALANCE_CURRENCY    => 'required|size:3',
        Entity::POSTED_DATE         => 'required|integer',
        Entity::TRANSACTION_DATE    => 'required|integer',
    ];

    protected static $accountStatementGenerateRules = [
        Entity::CHANNEL        => 'required|string|custom',
        Entity::ACCOUNT_NUMBER => 'required|string|between:5,40',
        Entity::FROM_DATE      => 'required|epoch',
        Entity::TO_DATE        => 'required|epoch',
        Entity::FORMAT         => 'required|string|custom',
        Entity::SEND_EMAIL     => 'required|boolean',
        Entity::TO_EMAIL_LIST  => 'required_if:send_email,1|custom'
    ];

    protected function validateToEmails($attribute, $emailList)
    {
        # if this is not empty, then it must be a comma-separated list of valid emails
        $emails = explode(',', $emailList);
        foreach ($emails as $emailToVerify)
        {
            $validator = ValidatorFacade::make(['email' => $emailToVerify], [
                'email' => 'required|email',
            ]);

            try
            {
                $validator->validate();
            }
            catch (Exception $e)
            {
                throw new BadRequestValidationFailureException("Invalid Email: $emailToVerify");
            }
        }
    }

    protected function validateFormat($attribute, $format)
    {
        SupportedFormats::validateFormat($format);
    }

    protected function validateChannel($attribute, $channel)
    {
        Channel::validate($channel);
    }

    protected function validateType($attribute, $type)
    {
        Type::validate($type);
    }

    protected function validateCategory($attribute, $category)
    {
        Category::validate($category);
    }
}
