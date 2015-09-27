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
        Entity::TRANSACTION_REPORT_EMAIL    => 'sometimes|email|max:255',
        Entity::RECEIPT_EMAIL_ENABLED       => 'sometimes|boolean'
    );

    protected static $editEmailRules = [
        Entity::EMAIL                       => 'sometimes|email|unique:merchants'
    ];

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
