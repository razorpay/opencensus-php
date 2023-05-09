<?php


namespace RZP\Models\Merchant\Store;

use RZP\Base;
use RZP\Exception\InvalidPermissionException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $updateRules = [
        Constants::NAMESPACE                                       => 'required|string|custom',
        ConfigKey::MTU_COUPON_POPUP_COUNT                          => 'filled|integer|min:1|max:5',
        ConfigKey::REFERRED_COUNT                                  => 'filled|integer',
        ConfigKey::REFERRAL_LINK                                   => 'filled|string',
        ConfigKey::REFERRAL_CODE                                   => 'filled|string',
        ConfigKey::REFERRAL_SUCCESS_POPUP_COUNT                    => 'filled|integer|min:0|max:5',
        ConfigKey::REFEREE_SUCCESS_POPUP_COUNT                     => 'filled|integer|min:0|max:5',
        ConfigKey::IS_SIGNED_UP_REFEREE                            => 'filled|bool',
        ConfigKey::REFERRAL_AMOUNT                                 => 'filled|integer',
        ConfigKey::REFERRAL_AMOUNT_CURRENCY                        => 'filled|string',
        ConfigKey::REFEREE_NAME                                    => 'sometimes|array',
        ConfigKey::REFEREE_ID                                      => 'sometimes|array',
        ConfigKey::GST_DETAILS_FROM_PAN                            => 'filled|json',
        ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT          => 'filled|integer',
        ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT         => 'filled|integer',
        ConfigKey::ENABLE_MTU_CONGRATULATORY_POPUP                 => 'filled|bool',
        ConfigKey::NO_DOC_ONBOARDING_INFO                          => 'filled|array',
        ConfigKey::POLICY_DATA                                     => 'filled|array',
        ConfigKey::GET_COMPANY_PAN_DETAILS_FROM_BVS_ATTEMPT_COUNT  => 'filled|integer',
        ConfigKey::GET_PROMOTER_PAN_DETAILS_FROM_BVS_ATTEMPT_COUNT => 'filled|integer',
        ConfigKey::PROMOTER_PAN_NAME_SUGGESTED                     => 'sometimes|string',
        ConfigKey::BUSINESS_NAME_SUGGESTED                         => 'sometimes|string',
        ConfigKey::IS_PAYMENT_HANDLE_ONBOARDING_INITIATED          => 'filled|bool',
        ConfigKey::WEBSITE_INCOMPLETE_SOFT_NUDGE_TIMESTAMP         => 'filled|integer',
        ConfigKey::WEBSITE_INCOMPLETE_SOFT_NUDGE_COUNT             => 'filled|integer|min:0|max:5',
        ConfigKey::SHOW_FTUX_FINAL_SCREEN                          => 'filled|bool',
        ConfigKey::SHOW_FIRST_PAYMENT_BANNER                       => 'filled|bool',
        ConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER          => 'filled|string|in:pending,pending_seen,pending_ack,no_banner,success,rejected',
    ];

    protected static $fetchRules  = [
        Constants::NAMESPACE => 'filled|string|custom',
    ];

    public static    $validRoles  = [
        Constants::PUBLIC,
        Constants::INTERNAL,
    ];

    public function isPermittedAction($config, $action, $role)
    {
        $this->validateRole($role);

        //action is read/write
        //if allowed roles list for the action is empty then default allowed role is public
        $isPermitted = (isset($config[$action]) === false);

        //if allowed roles list  for the action has public role
        $isPermitted = ($isPermitted or in_array(Constants::PUBLIC, $config[$action]));

        //if role is in allowed roles list for the action
        $isPermitted = ($isPermitted or in_array($role, $config[$action]));

        return $isPermitted;

    }

    public function validateUpdateRequest(array $input, string $role)
    {
        $this->validateInput('update', $input);

        $namespace = $input[Constants::NAMESPACE];

        foreach ($input as $key => $value)
        {
            if (Constants::NAMESPACE === $key)
            {
                continue;
            }
            $config = ConfigKey::NAMESPACE_KEY_CONFIG[$namespace][$key];

            if ($this->isPermittedAction($config, Constants::WRITE, $role) === false)
            {
                throw new InvalidPermissionException('Not permitted action ' . $role . ' for key ' . $key);
            }
        }
    }

    public function validateDeleteRequest(string $namespace, array $keys, string $role)
    {
        foreach ($keys as $key)
        {
            if (Constants::NAMESPACE === $key)
            {
                continue;
            }
            $config = ConfigKey::NAMESPACE_KEY_CONFIG[$namespace][$key];

            if ($this->isPermittedAction($config, Constants::DELETE, $role) === false)
            {
                throw new InvalidPermissionException('Not permitted action ' . $role . ' for key ' . $key);
            }
        }
    }

    public function validateRole($role)
    {
        if (in_array($role, self::$validRoles) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid role: ' . $role);
        }
    }

    public function validateNamespace($attirbute, $value)
    {
        if (array_key_exists($value, ConfigKey::NAMESPACE_KEY_CONFIG) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid namespace: ' . $value);
        }
    }

    public function validateKey($namespace, $key)
    {
        $this->validateNamespace(Constants::NAMESPACE, $namespace);

        $configKeys = ConfigKey::NAMESPACE_KEY_CONFIG[$namespace] ?? [];

        if (array_key_exists($key, $configKeys) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid namespace ' . $namespace . ' and key: ' . $key);
        }
    }
}
