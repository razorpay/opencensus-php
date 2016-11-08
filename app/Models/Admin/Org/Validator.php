<?php

namespace RZP\Models\Admin\Org;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::DISPLAY_NAME  => 'required|string|max:250',
        Entity::BUSINESS_NAME => 'required|string|max:250',
        Entity::EMAIL         => 'required|email',
        Entity::EMAIL_DOMAINS => 'required|custom',
        Entity::AUTH_TYPE     => 'required|string|max:250',
        Entity::LOGO_URL      => 'sometimes|url',
    ];

    protected static $editRules = [
        Entity::DISPLAY_NAME   => 'sometimes|string|max:250',
        Entity::BUSINESS_NAME  => 'sometimes|string|max:250',
        Entity::EMAIL          => 'sometimes|email',
        Entity::EMAIL_DOMAINS  => 'sometimes|custom',
        Entity::AUTH_TYPE      => 'sometimes|string|max:250',
        Entity::LOGO_URL       => 'sometimes|url',
    ];

    protected function validateEmailDomains($attribute, $value)
    {
        $domains = explode(',', $value);

        foreach ($domains as $domain)
        {
            $this->validateDomain($domain);
        }
    }

    protected function validateDomain($domain)
    {
        // TODO: validate if the domain is a proper domain
    }
}
