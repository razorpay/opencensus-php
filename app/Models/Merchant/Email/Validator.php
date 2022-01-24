<?php

namespace RZP\Models\Merchant\Email;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::TYPE   => 'required|string|max:255|custom',
        Entity::EMAIL  => 'sometimes|string|custom',
        Entity::PHONE  => 'sometimes|numeric|digits_between:8,11',
        Entity::POLICY => 'sometimes|string|nullable',
        Entity::URL    => 'sometimes|custom:active_url|max:255|nullable',
    ];

    protected static $editRules = [
        Entity::TYPE   => 'required|string|max:255|custom',
        Entity::EMAIL  => 'sometimes|string|custom',
        Entity::PHONE  => 'sometimes|numeric|digits_between:8,11',
        Entity::POLICY => 'sometimes|string|nullable',
        Entity::URL    => 'sometimes|custom:active_url|max:255|nullable',
    ];

    protected static $emailRules = [
        Entity::EMAIL => 'required|email',
    ];

    /**
     * validateType validate if the type of the email is valid or not
     *
     * @param string $attribute
     * @param string $value
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateType(string $attribute, string $value)
    {
        if (Type::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Email type is invalid: ' . $value);
        }
    }

    /**
     * validateEmail validates email string contain any invalid email or not
     *
     * @param string $attribute
     * @param string $value
     */
    public function validateEmail(string $attribute, string $value)
    {
        $emails = explode(',', $value);

        foreach ($emails as $email)
        {
            $this->validateInput('email', [Entity::EMAIL => $email]);
        }
    }

    /**
     * @param array $types
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateTypes(array $types)
    {
        foreach ($types as $type)
        {
            $this->validateType(Entity::TYPE, $type);
        }
    }
}
