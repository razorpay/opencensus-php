<?php

namespace RZP\Models\Merchant\VerificationDetail;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID           => 'required|string|size:14',
        Entity::ARTEFACT_TYPE         => 'required|string',
        Entity::ARTEFACT_IDENTIFIER   => 'required|string|in:number,doc',
        Entity::STATUS                => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated,not_initiated',
        Entity::METADATA              => 'sometimes|array',
    ];

    protected static $editRules = [
        Entity::MERCHANT_ID           => 'required|string|size:14',
        Entity::ARTEFACT_TYPE         => 'required|string',
        Entity::ARTEFACT_IDENTIFIER   => 'required|string|in:number,doc',
        Entity::STATUS                => 'sometimes|string|in:failed,verified,incorrect_details,not_matched,pending,initiated,not_initiated',
        Entity::METADATA              => 'sometimes|array',
    ];
}
