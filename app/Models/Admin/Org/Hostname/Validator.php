<?php

namespace RZP\Models\Admin\Org\Hostname;

use RZP\Base;

class Validator extends Base\Validator
{
    protected $createRules = [
        Entity::ORG_ID      => 'required|string',
        Entity::HOSTNAME    => 'required|string|max:255|custom'
    ];

    protected function validateHostname($attribute, $hostname)
    {
        if (filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid domain name provided', $attribute, $hostname);
        }
    }
}