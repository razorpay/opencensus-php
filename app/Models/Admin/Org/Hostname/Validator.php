<?php

namespace RZP\Models\Admin\Org\Hostname;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::HOSTNAME    => 'required|string|max:255|unique:org_hostname|custom'
    ];

    protected function validateHostname($attribute, $hostname)
    {
        if (filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid hostname provided', $attribute, $hostname);
        }
    }
}
