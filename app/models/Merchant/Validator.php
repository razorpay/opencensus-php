<?php

namespace Models\Merchant;

use EE\Exception;
use Models\Base;
use Models\Merchant;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::ID                  => 'required|alpha_num|size:14',
        Entity::NAME                => 'required|alpha_space_num|max:200',
        Entity::EMAIL               => 'required|email|unique:merchants',
    );

    protected static $editRules = array(
        Entity::HOLD_FUNDS                  => 'sometimes|in:0,1',
        Entity::WEBSITE                     => 'sometimes|url|max:255',
        Entity::CATEGORY                    => 'sometimes|numeric|digits:4',
        Entity::INTERNATIONAL               => 'sometimes|boolean',
        Entity::BILLING_LABEL               => 'sometimes|max:255',
        Entity::TRANSACTION_REPORT_EMAIL    => 'sometimes|max:255',
        Entity::RECEIPT_EMAIL_ENABLED       => 'sometimes|boolean',
        Entity::SETTLEMENT_SCHEDULE         => 'sometimes|integer|min:1|max:30',
    );

    protected static $editEmailRules = [
        Entity::EMAIL                       => 'sometimes|email|unique:merchants'
    ];

    protected static $editValidators = [
        'csv_email'
    ];

    protected function validateCsvEmail($input)
    {
        if (isset($input[Entity::TRANSACTION_REPORT_EMAIL]) === false)
            return;

        $emails = $input[Entity::TRANSACTION_REPORT_EMAIL];

        foreach ($emails as $email)
        {
            $email = trim($email); // Remove whitespace
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "The provided transaction report email is invalid: $email",
                    Entity::TRANSACTION_REPORT_EMAIL
                );
            }
        }
    }

    public function validateBeforeActivate(Merchant\Entity $merchant)
    {
        $attributes = array(
            Entity::WEBSITE,
            Entity::CATEGORY,
            Entity::BILLING_LABEL,
            Entity::TRANSACTION_REPORT_EMAIL);

        foreach ($attributes as $attribute)
        {
            $value = $merchant->getAttribute($attribute);

            if (empty($value))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Please set value for attribute: ' . $attribute);
            }
        }
    }
}
