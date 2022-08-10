<?php

namespace RZP\Models\Merchant\Consent;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID                            => 'required|string|size:14',
        Entity::USER_ID                                => 'required|string|size:14',
        // request id received as a response to call made to bvs
        Entity::REQUEST_ID                             => 'sometimes|string|size:14|nullable',
        // pending no call to bvs,
        // initiated call to bvs and we have request id
        // success the call to bvs is successful
        //failed call to bvs has failed
        Entity::STATUS                                 => 'required|string|nullable|in:success,failed,pending,initiated',
        Entity::DETAILS_ID                             => 'sometimes|string|size:14',
        Entity::CONSENT_FOR                            => 'required|string|in:'.Constants::CONSENT_KEYS,
        Entity::METADATA                               => 'sometimes|array',
        Entity::METADATA . '.' . Constants::IP         => 'sometimes|ip|nullable',
        Entity::METADATA . '.' . Constants::USER_AGENT => 'sometimes|nullable',
    ];

    protected static $editRules   = [
        Entity::REQUEST_ID => 'sometimes|string|size:14|nullable',
        Entity::STATUS     => 'required|string|nullable|in:success,failed,pending,initiated',
        Entity::METADATA   => 'sometimes|array',
    ];
}
