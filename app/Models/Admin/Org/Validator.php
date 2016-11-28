<?php

namespace RZP\Models\Admin\Org;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::DISPLAY_NAME        => 'required|string|max:255',
        Entity::BUSINESS_NAME       => 'required|string|max:255',
        Entity::HOSTNAME            => 'required|string|max:255|url',
        Entity::EMAIL               => 'required|email',
        Entity::EMAIL_DOMAINS       => 'required|custom',
        Entity::AUTH_TYPE           => 'required|string|max:255',
        Entity::LOGIN_LOGO_URL      => 'sometimes|url',
        Entity::MAIN_LOGO_URL       => 'sometimes|url',
    ];

    protected static $editRules = [
        Entity::DISPLAY_NAME        => 'sometimes|string|max:255',
        Entity::BUSINESS_NAME       => 'sometimes|string|max:255',
        Entity::EMAIL               => 'sometimes|email',
        Entity::EMAIL_DOMAINS       => 'sometimes|custom',
        Entity::AUTH_TYPE           => 'sometimes|string|max:255',
        Entity::LOGIN_LOGO_URL      => 'sometimes|url',
        Entity::MAIN_LOGO_URL       => 'sometimes|url',
    ];

    protected function validateEmailDomains($attribute, $value)
    {
        $domains = explode(',', $value);

        foreach ($domains as $domain)
        {
            $this->validateHostname($domain);
        }
    }

    protected function validateHostname($domain)
    {
        return;
    }
}
