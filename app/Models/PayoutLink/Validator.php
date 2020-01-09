<?php

namespace RZP\Models\PayoutLink;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Payout\Mode;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    const CONTACT_ID                       = 'contact.id';
    const CONTACT_NAME                     = 'contact.name';

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
        Entity::FUND_ACCOUNT_ID => 'filled|string|public_id',
        Entity::ACCOUNT_TYPE    => 'required_if:fund_account_id,null|string|in:bank_account,vpa',
        Entity::VPA             => 'required_if:type,vpa|array',
        Entity::BANK_ACCOUNT    => 'required_if:type,bank_account|array',
        Entity::TOKEN           => 'required|string'
    ];

    protected static $getFundAccountByContactRules = [
        Entity::TOKEN => 'required|string'
    ];

    protected static $generateOtpRules = [
        Entity::CONTEXT => 'sometimes|string|min:5|max:15'
    ];

    protected static $createRules = [
        Entity::CONTACT_NAME         => 'required|string|max:50',
        Entity::CONTACT_EMAIL        => 'sometimes|nullable|email',
        Entity::CONTACT_PHONE_NUMBER => 'sometimes|nullable|contact_syntax',
        Entity::BALANCE_ID           => 'sometimes|string|size:14',
        Entity::AMOUNT               => 'required|integer|min:100|max:'. Entity::MAX_PAYOUT_LIMIT,
        Entity::CURRENCY             => 'required|size:3|in:INR',
        Entity::NOTES                => 'sometimes|notes',
        Entity::DESCRIPTION          => 'required|string|max:255',
        Entity::PURPOSE              => 'required|filled|string|max:30|alpha_dash_space',
        Entity::RECEIPT              => 'sometimes|string|max:40'
    ];

    protected static $compositeCreateRules = [
        Entity::AMOUNT         => 'required|integer',
        Entity::CURRENCY       => 'required|size:3|in:INR',
        Entity::NOTES          => 'sometimes|notes',
        Entity::ACCOUNT_NUMBER => 'required|alpha_num|between:5,40',
        Entity::DESCRIPTION    => 'required|string|max:255',
        Entity::PURPOSE        => 'required|filled|string|max:30|alpha_dash_space',
        Entity::RECEIPT        => 'sometimes|string|max:40',
        Entity::CONTACT        => 'required|array',
        self::CONTACT_ID       => 'required_without:contact.name|nullable|string|public_id'
    ];

    protected static $verifyOtpRules = [
        Entity::OTP     => 'required|string|min:4|max:6',
        Entity::CONTEXT => 'sometimes|string|min:5|max:15'
    ];

    protected static $compositeCreateValidators = [
        Entity::CONTACT
    ];

    /**
     * Check that if contact_id is present and along with it other information is present then fail the api
     * @param array $input
     * @throws BadRequestException
     */
    protected function validateContact(array $input)
    {
        if (isset($input[Entity::CONTACT][Entity::ID]) === true)
        {
            if ((isset($input[Entity::CONTACT][Entity::EMAIL]) === true) or
                (isset($input[Entity::CONTACT][Entity::PHONE_NUMBER]) === true)
            )
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_EITHER_CONTACT_ID_OR_INFORMATION_TO_BE_SENT,
                                              null,
                                              $input);
            }
        }
    }
}
