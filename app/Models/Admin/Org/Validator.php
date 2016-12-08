<?php

namespace RZP\Models\Admin\Org;

use RZP\Base;
use RZP\Models\Admin\Org\Hostname;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::DISPLAY_NAME        => 'required|string|max:255',
        Entity::BUSINESS_NAME       => 'required|string|max:255',
        Hostname\Entity::HOSTNAME   => 'sometimes|string',
        Entity::EMAIL               => 'required|email',
        Entity::EMAIL_DOMAINS       => 'required|custom',
        Entity::AUTH_TYPE           => 'required|string|max:255|in:password,google_auth',
        Entity::LOGIN_LOGO_URL      => 'sometimes|url',
        Entity::MAIN_LOGO_URL       => 'sometimes|url',
        Entity::INVOICE_LOGO_URL    => 'sometimes|url',
        'admin'                     => 'required|array',
    ];

    protected static $editRules = [
        Entity::DISPLAY_NAME        => 'sometimes|string|max:255',
        Entity::BUSINESS_NAME       => 'sometimes|string|max:255',
        Hostname\Entity::HOSTNAME   => 'sometimes|string',
        Entity::EMAIL               => 'sometimes|email',
        Entity::EMAIL_DOMAINS       => 'sometimes|custom',
        Entity::AUTH_TYPE           => 'sometimes|string|max:255|in:password,google_auth',
        Entity::LOGIN_LOGO_URL      => 'sometimes|url',
        Entity::MAIN_LOGO_URL       => 'sometimes|url',
        Entity::INVOICE_LOGO_URL    => 'sometimes|url',
    ];

    protected function validateEmailDomains($attribute, $value)
    {
        $domains = explode(',', $value);

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
