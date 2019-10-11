<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;
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
        Entity::CHANNEL              => 'required|string|custom',
        Entity::ACCOUNT_NUMBER       => 'required|string|between:5,40',
        Entity::FROM_DATE            => 'required|epoch',
        Entity::TO_DATE              => 'required|epoch',
        Entity::FORMAT               => 'required|string|custom',
        Entity::SEND_EMAIL           => 'required|boolean',
        Entity::TO_EMAIL_LIST        => 'required_if:send_email,1|array',
        Entity::TO_EMAIL_LIST . '.*' => 'filled|email'
    ];

    protected static $accountStatementGenerateValidators = [
        'channel_format'
    ];

    protected function validateChannelFormat($input)
    {
        $channel = $input[Entity::CHANNEL];

        $format = $input[Entity::FORMAT];

        SupportedFormats::validateChannelFormat($channel, $format);
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
