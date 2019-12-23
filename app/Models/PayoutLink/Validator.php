<?php

namespace RZP\Models\PayoutLink;

use RZP\Base;
use RZP\Models\Payout\Mode;

class Validator extends Base\Validator
{
    const CONTACT_ID                       = 'contact.id';
    const CONTACT                          = 'contact';
    const CONTACT_NAME                     = 'contact.name';
    const ACCOUNT_NUMBER                   = 'account_number';
    const ACCOUNT_TYPE                     = 'account_type';
    const VPA                              = 'vpa';
    const BANK_ACCOUNT                     = 'bank_account';

    const COMPOSITE_CREATE_RULE            = 'composite_create';
    const VERIFY_OTP                       = 'verify_otp';
    const GET_FUND_ACCOUNT_BY_CONTACT_RULE = 'get_fund_account_by_contact';
    const GENERATE_OTP                     = 'generate_otp';
    const ADD_FUND_ACCOUNT_RULE            = 'add_fund_account';
    const SETTINGS_RULE                    = 'settings';

    protected static $settingsRules = [
        Mode::UPI  => 'sometimes|boolean|filled',
        Mode::IMPS => 'sometimes|boolean|filled',
    ];

    protected static $addFundAccountRules = [
        Entity::FUND_ACCOUNT_ID => 'nullable|string|size:14',
        self::ACCOUNT_TYPE      => 'required_if:fund_account_id,null|string|in:bank_account,vpa',
        self::VPA               => 'required_if:type,vpa|array',
        self::BANK_ACCOUNT      => 'required_if:type,bank_account|array',
        Entity::TOKEN           => 'required|string'
    ];

    protected static $getFundAccountByContactRules = [
        Entity::TOKEN => 'required|string'
    ];

    protected static $generateOtpRules = [
        Entity::CONTEXT => 'sometimes|string|min:5|max:10'
    ];

    protected static $createRules = [
        Entity::CONTACT_NAME         => 'required|string|max:50',
        Entity::CONTACT_EMAIL        => 'sometimes|nullable|email',
        Entity::CONTACT_PHONE_NUMBER => 'sometimes|nullable|contact_syntax',
        Entity::BALANCE_ID           => 'sometimes|string|size:14',
        Entity::AMOUNT               => 'required|integer',
        Entity::CURRENCY             => 'required|size:3|in:INR',
        Entity::NOTES                => 'sometimes|notes',
        Entity::DESCRIPTION          => 'required|string|max:255',
        Entity::RECEIPT              => 'sometimes|string|max:40'
    ];

    protected static $compositeCreateRules = [
        Entity::AMOUNT       => 'required|integer',
        Entity::CURRENCY     => 'required|size:3|in:INR',
        Entity::NOTES        => 'sometimes|notes',
        self::ACCOUNT_NUMBER => 'required|alpha_num|between:5,40',
        Entity::DESCRIPTION  => 'required|string|max:255',
        Entity::RECEIPT      => 'sometimes|string|max:40',
        self::CONTACT        => 'required|array',
        self::CONTACT_ID     => 'required_without:contact.name|nullable|string|size:14'
    ];

    protected static $verifyOtpRules = [
        Entity::OTP     => 'required|string|min:4|max:6',
        Entity::CONTEXT => 'sometimes|string|min:5|max:10'
    ];
}
