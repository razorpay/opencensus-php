<?php

namespace RZP\Models\Admin\Org\Hostname;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ORG_ID      => 'required|string',
        Entity::HOSTNAME    => 'required|string|max:255|custom|unique:org_hostname'
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