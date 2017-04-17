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
        Entity::EMAIL                 => 'required|email|unique:users',
        Entity::PASSWORD              => 'required|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required',
        Entity::CONTACT_MOBILE        => 'sometimes|max:15',
        Entity::REMEMBER_TOKEN        => 'sometimes',
        Entity::CONFIRM_TOKEN         => 'required',
    ];

    protected static $loginRules = [
        Entity::EMAIL     =>      'required|email',
        Entity::PASSWORD  =>      'required|between:6,50',
    ];

    protected static $changePasswordRules = [
        Entity::OLD_PASSWORD          => 'required',
        Entity::PASSWORD              => 'required|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required',
        Entity::NAME                  => 'required|string|max:200',
        Entity::EMAIL                 => 'required|email',
        Entity::PASSWORD              => 'required|max:100',
        Entity::CONTACT_MOBILE        => 'required|max:15',
        Entity::REMEMBER_TOKEN        => 'required',
        Entity::CONFIRM_TOKEN         => 'required',
    ];

    protected static $editRules = [
        Entity::NAME            => 'sometimes|string|max:200',
        Entity::CONTACT_MOBILE  => 'sometimes|max:15',
    ];

    protected static $actionRules = [
        Entity::ACTION            => 'required|custom',
        Entity::MERCHANT_ID       => 'required|max:14',
        Entity::ROLE              => 'sometimes|string',
    ];

    protected static $validActions = [
        'attach',
        'detach',
        'update'
    ];

    protected function validateAction(string $attribute, string $action)
    {
        if (in_array($action, self::$validActions, true) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_USER_ACTION_NOT_SUPPORTED);
        }
    }
}
