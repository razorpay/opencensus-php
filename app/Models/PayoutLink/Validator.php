<?php

namespace RZP\Models\PayoutLink;

use RZP\Base;

class Validator extends Base\Validator
{
    const CONTACT_ID           = 'contact.contact_id';
    const CONTACT_EMAIL        = 'contact.email';
    const CONTACT_PHONE_NUMBER = 'contact.phone_number';
    const CONTACT_TYPE         = 'contact.type';
    const CONTACT_NAME         = 'contact.name';

    const COMPOSITE_CREATE     = 'composite_create';

    protected static $createRules = [
        Entity::AMOUNT          => 'required|integer',
        Entity::CURRENCY        => 'required|size:3',
        Entity::NOTES           => 'sometimes|notes',
        Entity::DESCRIPTION     => 'required|string|max:255',
        Entity::RECEIPT         => 'sometimes|string|max:40',
    ];

    protected static $compositeCreateRules = [
        Entity::AMOUNT          => 'required|integer',
        Entity::CURRENCY        => 'required|size:3',
        Entity::NOTES           => 'sometimes|notes',
        Entity::DESCRIPTION     => 'required|string|max:255',
        Entity::RECEIPT         => 'sometimes|string|max:40',
        'contact'               => 'required|array',
        'contact.contact_id'    => 'present|nullable|string|size:14',
        'contact.name'          => 'required_without:contact.contact_id|string|max:50',
        'contact.email'         => 'nullable|email|filled',
        'contact.contact'       => 'nullable|contact_syntax|filled',
        'contact.type'          => 'nullable|max:40|alpha_dash_space|filled',
    ];
}
