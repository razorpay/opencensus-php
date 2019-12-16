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


    const COMPOSITE_CREATE_RULE = 'composite_create';
    const VERIFY_OTP            = 'verify_otp';
    const GENERATE_OTP          = 'generate_otp';

    protected static $generateOtpRules = [
        Entity::CONTEXT => 'sometimes|string|min:5|max:10'
    ];
    protected static $createRules = [
        Entity::CONTACT_NAME         => 'required|string|max:50',
        Entity::CONTACT_EMAIL        => 'sometimes|nullable|email',
        Entity::CONTACT_PHONE_NUMBER => 'sometimes|nullable|contact_syntax',
        Entity::AMOUNT               => 'required|integer',
        Entity::CURRENCY             => 'required|size:3|in:INR',
        Entity::NOTES                => 'sometimes|notes',
        Entity::DESCRIPTION          => 'required|string|max:255',
        Entity::RECEIPT              => 'sometimes|string|max:40'
    ];

    protected static $compositeCreateRules = [
        Entity::AMOUNT      => 'required|integer',
        Entity::CURRENCY    => 'required|size:3|in:INR',
        Entity::NOTES       => 'sometimes|notes',
        Entity::DESCRIPTION => 'required|string|max:255',
        Entity::RECEIPT     => 'sometimes|string|max:40',
        'contact'           => 'required|array',
        'contact.id'        => 'sometimes|nullable|string|size:14',
        'contact.name'      => 'required_without:contact.id|string|max:50',
        'contact.email'     => 'nullable|email|filled',
        'contact.contact'   => 'nullable|contact_syntax|filled',
        'contact.type'      => 'nullable|max:40|alpha_dash_space|filled',
    ];

    protected static $verifyOtpRules = [
        Entity::OTP     => 'required|string|max:6',
        Entity::CONTEXT => 'sometimes|string|min:5|max:10'
    ];
}
