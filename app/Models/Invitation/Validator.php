<?php

namespace RZP\Models\Invitation;

use RZP\Base;
use RZP\Exception;
use RZP\Models\User;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ROLE        => 'required|string|custom',
        Entity::EMAIL       => 'required|max:255|email|custom',
        Entity::TOKEN       => 'required|string',
        Entity::SENDER_NAME => 'sometimes|string',
    ];

    protected static $editRules = [
        Entity::ROLE => 'required|string|custom',
    ];

    protected static $resendRules = [
        Entity::SENDER_NAME => 'sometimes|string',
    ];

    protected static $actionRules = [
        Entity::USER_ID => 'required|string|max:14',
        Entity::ACTION  => 'required|string|in:accept,reject',
    ];

    public function validateEmail(string $attribute, string $email)
    {
        $merchant = $this->entity->merchant;

        if (($merchant->invitations
                      ->where(Entity::EMAIL, $email)
                      ->isEmpty()) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVITATION_USER_ALREADY_INVITED);
        }

        if (($merchant->users
                      ->where(Entity::EMAIL, $email)
                      ->isEmpty()) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVITATION_USER_ALREADY_MEMBER);
        }
    }

    protected function validateRole(string $attribute, string $role)
    {
        $merchant = $this->entity->merchant;

        if ($merchant->isLinkedAccount() === true)
        {
            $dashboardRoles = User\Role::LINKED_ACCOUNT_ROLES;
        }
        else
        {
            $dashboardRoles = User\Role::ALL_ROLES;
        }

        if (in_array($role, $dashboardRoles) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_ROLE_INVALID);
        }
    }
}
