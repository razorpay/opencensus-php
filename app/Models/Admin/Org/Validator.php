<?php

namespace RZP\Models\Admin\Org;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Org\Hostname;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::DISPLAY_NAME                    => 'required|string|max:255',
        Entity::BUSINESS_NAME                   => 'required|string|max:255',
        Hostname\Entity::HOSTNAME               => 'sometimes|string',
        Entity::EMAIL                           => 'required|email',
        Entity::EMAIL_DOMAINS                   => 'required|custom',
        Entity::ALLOW_SIGN_UP                   => 'sometimes|boolean',
        Entity::AUTH_TYPE                       => 'required|string|max:255|in:password,google_auth',
        Entity::LOGIN_LOGO_URL                  => 'sometimes|url',
        Entity::MAIN_LOGO_URL                   => 'sometimes|url',
        Entity::INVOICE_LOGO_URL                => 'sometimes|url',
        Entity::CHECKOUT_LOGO_URL               => 'sometimes|url',
        Entity::EMAIL_LOGO_URL                  => 'sometimes|url',
        Entity::PAYMENT_APPS_LOGO_URL           => 'sometimes|url',
        Entity::PAYMENT_BTN_LOGO_URL            => 'sometimes|url',
        Entity::ADMIN                           => 'required|array',
        Entity::CUSTOM_CODE                     => 'required',
        Entity::FROM_EMAIL                      => 'sometimes|email',
        Entity::SIGNATURE_EMAIL                 => 'sometimes|email',
        Entity::PERMISSIONS                     => 'required|array',
        Entity::WORKFLOW_PERMISSIONS            => 'sometimes|array',
        Entity::TYPE                            => 'filled|in:restricted',
        Entity::BACKGROUND_IMAGE_URL            => 'sometimes|url',
        Entity::MERCHANT_STYLES                 => 'sometimes|array',
        Entity::MERCHANT_SESSION_TIMEOUT_IN_SECONDS => 'required|numeric|min:300',
        Entity::MERCHANT_SECOND_FACTOR_AUTH     => 'sometimes|boolean',
        Entity::MERCHANT_MAX_WRONG_2FA_ATTEMPTS => 'sometimes|numeric',
        Entity::ADMIN_SECOND_FACTOR_AUTH        => 'sometimes|boolean',
        Entity::ADMIN_MAX_WRONG_2FA_ATTEMPTS    => 'sometimes|numeric',
        Entity::SECOND_FACTOR_AUTH_MODE         => 'sometimes|string|in:sms,email,sms_and_email',
        Entity::EXTERNAL_REDIRECT_URL           => 'sometimes|url',
        Entity::EXTERNAL_REDIRECT_URL_TEXT      => 'sometimes|string'

    ];

    protected static $editRules = [
        Entity::DISPLAY_NAME                    => 'sometimes|string|max:255',
        Entity::BUSINESS_NAME                   => 'sometimes|string|max:255',
        Hostname\Entity::HOSTNAME               => 'sometimes|string',
        Entity::EMAIL                           => 'sometimes|email',
        Entity::EMAIL_DOMAINS                   => 'sometimes|custom',
        Entity::ALLOW_SIGN_UP                   => 'sometimes|boolean',
        Entity::AUTH_TYPE                       => 'sometimes|string|max:255|in:password,google_auth',
        Entity::LOGIN_LOGO_URL                  => 'sometimes|url',
        Entity::MAIN_LOGO_URL                   => 'sometimes|url',
        Entity::INVOICE_LOGO_URL                => 'sometimes|url',
        Entity::CHECKOUT_LOGO_URL               => 'sometimes|url',
        Entity::EMAIL_LOGO_URL                  => 'sometimes|url',
        Entity::PAYMENT_APPS_LOGO_URL           => 'sometimes|url',
        Entity::PAYMENT_BTN_LOGO_URL            => 'sometimes|url',
        Entity::CUSTOM_CODE                     => 'sometimes',
        Entity::FROM_EMAIL                      => 'sometimes|email',
        Entity::SIGNATURE_EMAIL                 => 'sometimes|email',
        Entity::PERMISSIONS                     => 'sometimes|array',
        Entity::WORKFLOW_PERMISSIONS            => 'sometimes|array',
        Entity::DEFAULT_PRICING_PLAN_ID         => 'sometimes|alpha_num|size:14',
        Entity::BACKGROUND_IMAGE_URL            => 'sometimes|url',
        Entity::MERCHANT_STYLES                 => 'sometimes|array',
        Entity::MERCHANT_SESSION_TIMEOUT_IN_SECONDS => 'required|numeric|min:300',
        Entity::MERCHANT_SECOND_FACTOR_AUTH     => 'sometimes|boolean',
        Entity::MERCHANT_MAX_WRONG_2FA_ATTEMPTS => 'sometimes|numeric',
        Entity::ADMIN_SECOND_FACTOR_AUTH        => 'sometimes|boolean',
        Entity::ADMIN_MAX_WRONG_2FA_ATTEMPTS    => 'sometimes|numeric',
        Entity::SECOND_FACTOR_AUTH_MODE         => 'sometimes|string|in:sms,email,sms_and_email',
        Entity::EXTERNAL_REDIRECT_URL           => 'sometimes|url',
        Entity::EXTERNAL_REDIRECT_URL_TEXT      => 'sometimes|string'
    ];

    protected function validateEmailDomains($attribute, $domains)
    {
        if (is_array($domains) === false)
        {
            $domains = explode(',', $domains);
        }

        foreach ($domains as $domain)
        {
            if (filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid domain name provided', $attribute, $domain);
            }
        }
    }
}
