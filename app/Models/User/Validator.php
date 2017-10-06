<?php

namespace RZP\Models\User;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ID                    => 'sometimes|max:14',
        Entity::NAME                  => 'sometimes|string|max:200',
        Entity::EMAIL                 => 'required|email|unique:users,email',
        Entity::PASSWORD              => 'required|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required',
        Entity::CONTACT_MOBILE        => 'sometimes|max:15',
        Entity::REMEMBER_TOKEN        => 'sometimes',
        Entity::CONFIRM_TOKEN         => 'sometimes',
    ];

    protected static $editRules = [
        Entity::NAME                  => 'sometimes|string|max:200',
        Entity::EMAIL                 => 'sometimes|email|unique:users,email',
        Entity::CONTACT_MOBILE        => 'sometimes|max:15',
    ];

    protected static $changePasswordRules = [
        Entity::PASSWORD              => 'required|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required',
    ];

    protected static $actionRules = [
        Entity::ACTION                => 'required|custom',
        Entity::MERCHANT_ID           => 'required|max:14',
        Entity::ROLE                  => 'sometimes|string',
    ];

    protected static $loginRules = [
        Entity::EMAIL                 => 'required|email',
        Entity::PASSWORD              => 'required|between:6,50',
    ];

    protected static $confirmRules = [
        Entity::CONFIRM_TOKEN         => 'sometimes',
        Entity::EMAIL                 => 'sometimes|email',
    ];

    protected static $preSignupRules = [
        Entity::NAME                  => 'sometimes|alpha_space|max:200',
        Entity::CONTACT_MOBILE        => 'sometimes|numeric|digits_between:8,11',
    ];

    protected function validateAction(string $attribute, string $action)
    {
        if (Action::exists($action) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_USER_ACTION_NOT_SUPPORTED);
        }
    }
}
