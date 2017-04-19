<?php

namespace RZP\Models\User;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ID                    => 'required|max:14',
        Entity::NAME                  => 'sometimes|string|max:200',
        Entity::EMAIL                 => 'required|email|unique:users,email',
        Entity::PASSWORD              => 'required|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required',
        Entity::CONTACT_MOBILE        => 'sometimes|max:15',
        Entity::REMEMBER_TOKEN        => 'sometimes',
        Entity::CONFIRM_TOKEN         => 'required',
    ];

    protected static $editRules = [
        Entity::NAME                  => 'sometimes|string|max:200',
        Entity::CONTACT_MOBILE        => 'sometimes|max:15',
    ];

    protected static $changePasswordRules = [
        Entity::PASSWORD              => 'required|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required',
    ];

    protected static $actionRules = [
        Entity::ACTION            => 'required|custom',
        Entity::MERCHANT_ID       => 'required|max:14',
        Entity::ROLE              => 'required|string',
    ];

    protected static $loginRules = [
        Entity::EMAIL                 => 'required|email',
        Entity::PASSWORD              => 'required|between:6,50',
    ];


    protected function validateAction(string $attribute, string $action)
    {
        if (Action::exists($action) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_USER_ACTION_NOT_SUPPORTED);
        }
    }
}
