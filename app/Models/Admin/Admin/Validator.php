<?php

namespace RZP\Models\Admin\Admin;

use RZP\Models\Admin\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Org\AuthPolicy;
use RZP\Models\Admin\Action;

class Validator extends Base\Validator
{
    const TOKEN              = 'token';
    const RESET_PASSWORD_URL = 'reset_password_url';

    const SUSPENDED          = 'suspended';
    const ARCHIVED           = 'archived';
    const ACTIVATED          = 'activated';
    const PENDING            = 'pending';
    const DEAD               = 'dead';
    const SUB_ACCOUNTS       = 'sub_accounts';

    protected static $createRules = [
        Entity::EMAIL                 => 'required|max:255|email|custom',
        Entity::NAME                  => 'required|alpha_space|between:3,100',
        Entity::USERNAME              => 'sometimes|alpha_dash|between:3,50',
        Entity::PASSWORD              => 'sometimes|string|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'sometimes',
        Entity::REMEMBER_TOKEN        => 'sometimes|string|max:255',
        Entity::OAUTH_ACCESS_TOKEN    => 'sometimes|string|max:255',
        Entity::OAUTH_PROVIDER_ID     => 'sometimes|string|max:255',
        Entity::BRANCH_CODE           => 'required|string',
        Entity::DEPARTMENT_CODE       => 'required|string',
        Entity::SUPERVISOR_CODE       => 'required|string',
        Entity::LOCATION_CODE         => 'required|string',
        Entity::EMPLOYEE_CODE         => 'required|string',
        Entity::ROLES                 => 'required|array|filled',
        Entity::MERCHANTS             => 'sometimes|array',
        Entity::GROUPS                => 'sometimes|array',
        Entity::ALLOW_ALL_MERCHANTS   => 'sometimes|in:0,1',
        Entity::DISABLED              => 'sometimes|in:0,1',
    ];

    protected static $editRules = [
        Entity::NAME                  => 'sometimes|alpha_space|between:3,100',
        Entity::PASSWORD              => 'sometimes|string|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'sometimes',
        Entity::OAUTH_ACCESS_TOKEN    => 'sometimes|string|max:255',
        Entity::OAUTH_PROVIDER_ID     => 'sometimes|string|max:255',
        Entity::BRANCH_CODE           => 'sometimes|string',
        Entity::DEPARTMENT_CODE       => 'sometimes|string',
        Entity::SUPERVISOR_CODE       => 'sometimes|string',
        Entity::LOCATION_CODE         => 'sometimes|string',
        Entity::EMPLOYEE_CODE         => 'sometimes|string',
        Entity::ROLES                 => 'sometimes|array|filled',
        Entity::MERCHANTS             => 'sometimes|array',
        Entity::GROUPS                => 'sometimes|array',
        Entity::ALLOW_ALL_MERCHANTS   => 'sometimes|in:0,1',
        Entity::LOCKED                => 'sometimes|in:0,1',
        Entity::DISABLED              => 'sometimes|in:0,1',
    ];

    protected static $loginRules = [
        Entity::USERNAME              => 'required|email|max:255',
        Entity::PASSWORD              => 'required'
    ];

    protected static $oAuthLoginRules = [
        Entity::OAUTH_ACCESS_TOKEN    => 'required|string|max:255',
        Entity::OAUTH_PROVIDER_ID     => 'required|string|max:255',
        Entity::EMAIL                 => 'required|max:255|email',
    ];

    protected static $passwordAuthRules = [
        Entity::USERNAME              => 'required|alpha_dash|between:3,50',
        Entity::PASSWORD              => 'required|string|confirmed',
        Entity::PASSWORD_CONFIRMATION => 'required',
    ];

    protected static $verifyAdminSecondFactorRules = [
        Constant::OTP                 => 'required|string|between:4,6',
        Entity::USERNAME              => 'required|email|max:255',
        Entity::PASSWORD              => 'required',
    ];

    protected static $change2faSettingRules = [
        Constant::SECOND_FACTOR_AUTH    => 'required|boolean',
    ];

    protected static $adminAccountLockUnlockRules = [
        Constant::ADMIN_ID => 'required|alpha_num|size:14',
        Constant::ACTION  =>  'required|string|filled|in:lock,unlock',
    ];

    protected static $resetRules = [
        Entity::EMAIL                 => 'required|email|max:255',
        Entity::PASSWORD              => 'required|string|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required|string',
        self::TOKEN                   => 'required|string',
        Org\Entity::AUTH_TYPE         => 'required|string|in:password',
    ];

    protected static $forgotRules = [
        Entity::EMAIL                 => 'required|email|max:255',
        self::RESET_PASSWORD_URL      => 'required|string',
        Org\Entity::AUTH_TYPE         => 'required|string|in:password',
    ];

    protected static $changeRules = [
        Entity::PASSWORD              => 'required|confirmed|numbers|letters',
        Entity::PASSWORD_CONFIRMATION => 'required',
        Entity::OLD_PASSWORD          => 'required|string',
    ];

    protected static $createValidators = [
        Entity::PASSWORD
    ];

    protected static $editValidators = [
        Entity::PASSWORD
    ];

    protected static $changeValidators = [
        Entity::PASSWORD
    ];

    protected static $resetValidators = [
        Entity::PASSWORD
    ];

    public $isOrgSpecificValidationSupported = true;

    public function validateCredentials(array $input)
    {
        $this->validateInput('login', $input);
    }

    protected function validateEmail($parameter, $email)
    {
        $emailDomains = $this->entity->org->getEmailDomains();

        $domain = explode('@', $email, 2)[1];

        if (in_array($domain, $emailDomains) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ADMIN_EMAIL_HOSTNAME, 'email', $email);
        }
    }

    protected function validatePassword($input)
    {
        if (isset($input[Entity::PASSWORD]) === true)
        {
            $admin = $this->entity;

            (new AuthPolicy\Service)->validatePasswordCreate($admin, ['password' => $input[Entity::PASSWORD]]);
        }
    }

    public function validateSelfEditForbidden(Entity $authAdmin, Entity $admin)
    {
        if ($authAdmin->getId() === $admin->getId())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ADMIN_SELF_EDIT_PROHIBITED);
        }
    }

    public function validatePasswordAuthType(
        string $authType,
        array $input)
    {
        if ($authType !== Org\AuthType::PASSWORD)
        {
            return;
        }

        $keys = [
            Entity::USERNAME,
            Entity::PASSWORD,
            Entity::PASSWORD_CONFIRMATION,
        ];

        $adminInput = [];

        foreach ($input as $key => $value)
        {
            if (in_array($key, $keys, true) === true)
            {
                $adminInput[$key] = $input[$key];
            }
        }

        $this->validateInput('password_auth', $adminInput);
    }
}
