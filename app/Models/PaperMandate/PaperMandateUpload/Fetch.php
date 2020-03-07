<?php

namespace RZP\Models\PaperMandate\PaperMandateUpload;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID      => 'filled|alpha_num|size:14',
            Entity::STATUS           => 'filled|required_with:status_reason|custom',
            Entity::PAPER_MANDATE_ID => 'filled|alpha_num|size:14',
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            Entity::MERCHANT_ID,
            Entity::STATUS,
            Entity::PAPER_MANDATE_ID
        ],
    ];

    protected function validateStatus(string $attribute, string $value)
    {
        Status::isValidType($value);
    }
}
