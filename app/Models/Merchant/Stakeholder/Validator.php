<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::EMAIL                     => 'sometimes|email|max:255',
        Entity::MERCHANT_ID               => 'required|alpha_num|size:14',
        Entity::NAME                      => 'sometimes|max:255',
        Entity::PHONE_PRIMARY             => 'sometimes|numeric|digits_between:8,11',
        Entity::PHONE_SECONDARY           => 'sometimes|numeric|digits_between:8,11',
        Entity::DIRECTOR                  => 'sometimes|boolean',
        Entity::EXECUTIVE                 => 'sometimes|boolean',
        Entity::PERCENTAGE_OWNERSHIP      => 'sometimes|numeric|digits_between:1,100',
        Entity::POI_IDENTIFICATION_NUMBER => 'sometimes|personalPan',
        Entity::PAN_DOC_STATUS            => 'sometimes|string|nullable', // required when create from merchant details
        Entity::POI_STATUS                => 'sometimes|string|nullable',
        Entity::POA_STATUS                => 'sometimes|string|nullable',
    ];

    protected static $editRules = [
        Entity::EMAIL                     => 'sometimes|email|max:255',
        Entity::NAME                      => 'sometimes|max:255',
        Entity::PHONE_PRIMARY             => 'sometimes|numeric|digits_between:8,11',
        Entity::PHONE_SECONDARY           => 'sometimes|numeric|digits_between:8,11',
        Entity::DIRECTOR                  => 'sometimes|boolean',
        Entity::EXECUTIVE                 => 'sometimes|boolean',
        Entity::PERCENTAGE_OWNERSHIP      => 'sometimes|numeric|digits_between:1,100',
        Entity::POI_IDENTIFICATION_NUMBER => 'sometimes|personalPan',
    ];
}
