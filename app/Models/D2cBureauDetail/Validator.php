<?php

namespace RZP\Models\D2cBureauDetail;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::FIRST_NAME          => 'sometimes|nullable|string|max:255',
        Entity::LAST_NAME           => 'sometimes|nullable|string|max:255',
        Entity::DATE_OF_BIRTH       => 'sometimes|date_format:Y-m-d|before:today',
        Entity::GENDER              => 'sometimes|in:male,female',
        Entity::CONTACT_MOBILE      => 'sometimes|nullable|max:15',
        Entity::EMAIL               => 'required|email',
        Entity::ADDRESS             => 'sometimes|nullable|string|max:255',
        Entity::CITY                => 'sometimes|nullable|string|max:255',
        Entity::STATE               => 'sometimes|nullable|string|size:2',
        Entity::PINCODE             => 'sometimes|max:15',
        Entity::PAN                 => 'required|pan',
        Entity::STATUS              => 'required',
    ];

    protected static $editRules = [
        Entity::FIRST_NAME          => 'sometimes|string|max:255',
        Entity::LAST_NAME           => 'sometimes|string|max:255',
        Entity::DATE_OF_BIRTH       => 'sometimes|date_format:Y-m-d|before:today',
        Entity::GENDER              => 'sometimes|in:male,female',
        Entity::CONTACT_MOBILE      => 'sometimes|max:15',
        Entity::EMAIL               => 'sometimes|email',
        Entity::ADDRESS             => 'sometimes|max:255',
        Entity::CITY                => 'sometimes|max:255',
        Entity::STATE               => 'sometimes|size:2',
        Entity::PINCODE             => 'sometimes|max:15',
        Entity::PAN                 => 'sometimes|pan',
    ];
}
