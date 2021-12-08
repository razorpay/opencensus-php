<?php

namespace RZP\Models\Customer\Token;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Bank;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\PaperMandate\Constants as PaperMandateConstants;

class Validator extends Base\Validator
{
    const CREATE_DIRECT                           = 'create_direct';
    const CREATE_NETWORK_TOKEN                    = 'create_network_token';
    const CREATE_NETWORK_CARD                     = 'create_network_card';
    const CREATE_NETWORK_TOKEN_AUTHENTICAION_DATA = 'create_network_token_authentication_data';
    const FETCH_CRYPTOGRAM                        = 'fetch_cryptogram';
    const FETCH_TOKEN                             = 'fetch_token';
    const DELETE_TOKEN                            = 'delete_token';
    const GET_STATUS                              = 'get_status';

    /**
     * token epoch constrains :
     * min : Sat Jan  1 05:30:00 IST 2000
     * max : 17 August 292278994 - max for 64 bit signed int
    **/

    protected static $createRules = [
        Entity::METHOD              => 'required|in:card,emandate,wallet,nach,upi',
        Entity::CARD_ID             => 'required_only_if:method,card|alpha_num|size:14',
        Entity::BANK                => 'required_only_if:method,emandate,nach|custom',
        Entity::VPA_ID              => 'required_only_if:method,upi|alpha_num|size:14',
        // We generate it if max_amount is not present and method is emandate or nach or upi or card
        Entity::MAX_AMOUNT          => 'sometimes_if:method,emandate,nach,upi,card',
        Entity::WALLET              => 'required_only_if:method,wallet|custom',
        Entity::AUTH_TYPE           => 'required_only_if:method,emandate,nach|string|filled|in:netbanking,aadhaar,debitcard,physical,migrated',
        Entity::RECURRING           => 'sometimes|boolean',
        Entity::GATEWAY_TOKEN       => 'required_if:auth_type,migrated|string',
        Entity::GATEWAY_TOKEN2      => 'sometimes|string',
        // We generate it if expired_at is not present and method is emandate
        Entity::EXPIRED_AT          => 'sometimes|epoch:946684800,9223372036854775807|nullable|custom',
        Entity::ACCOUNT_NUMBER      => 'sometimes|nullable|alpha_num|between:5,20',
        Entity::ACCOUNT_TYPE        => 'sometimes|nullable|string',
        Entity::BENEFICIARY_NAME    => 'sometimes|nullable|alpha_space_num|between:4,120',
        Entity::IFSC                => 'sometimes|nullable|alpha_num|size:11',
        Entity::AADHAAR_NUMBER      => 'sometimes|nullable|string|size:12',
        Entity::AADHAAR_VID         => 'sometimes|nullable|string|size:16',
        Entity::START_TIME          => 'sometimes_if:method,upi,nach,emandate',
        Entity::DEBIT_TYPE          => 'required_only_if:auth_type,migrated|string|in:max_amount,fixed_amount',
        Entity::FREQUENCY           => 'required_only_if:auth_type,migrated|string|in:adhoc,monthly,quarterly,yearly',
        Entity::STATUS              => 'sometimes|nullable',
        Entity::NOTES               => 'sometimes|nullable'
    ];

    protected static $createDirectRules = [
        Entity::CARD            => 'required|array',
        Entity::METHOD          => 'required|in:card'
    ];

    protected static $editRules = [
        Entity::RECURRING       => 'sometimes|in:0',
    ];

    protected static $createValidators = [
        Entity::ACCOUNT_TYPE
    ];

    protected static $createNetworkTokenRules = [
        Entity::CARD                 => 'required|array',
        Entity::CUSTOMER_ID          => 'sometimes|public_id',
        Entity::METHOD               => 'required|in:card',
        Entity::AUTHENTICATION       => 'sometimes',
        Entity::NOTES                => 'sometimes',
    ];

    protected static $createNetworkCardRules = [
        'number'       => 'required',
        'expiry_month' => 'required',
        'expiry_year'  => 'required',
        'cvv'          => 'sometimes',
    ];

    protected static $createNetworkTokenAuthenticationDataRules = [
        "provider"              => "string",
        "provider_reference_id" => "string",
    ];

    protected static $fetchCryptogramRules = [
        'id'     => 'required|public_id',
    ];

    protected static $fetchTokenRules = [
        'id'     => 'required|public_id',
    ];

    protected static $deleteTokenRules = [
        'id'     => 'required|public_id',
    ];

    protected static $getStatusRules = [
        'token_id'     => 'required|string',
        'status'       => 'required|string',
        'iin'          => 'sometimes',
        'expiry_month' => 'sometimes',
        'expiry_year'  => 'sometimes',
    ];

    protected static function validateBank($attribute, $value)
    {
        if (Bank\IFSC::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid bank name in input: '. $value);
        }
    }

    protected function validateWallet($attribute, $value)
    {
        if (Wallet::exists($value) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED);
        }
    }

    protected static function validateAccountType(array $input)
    {
        if (isset($input[Entity::ACCOUNT_TYPE]) === false or
            $input[Entity::ACCOUNT_TYPE] === Entity::ACCOUNT_TYPE_SAVINGS or
            $input[Entity::ACCOUNT_TYPE] === Entity::ACCOUNT_TYPE_CURRENT)
        {
            return;
        }

        // If Not NACH return error
        if ($input[Entity::METHOD] !== Method::NACH)
        {
            app('trace')->count(\RZP\Models\SubscriptionRegistration\Metric::INVALID_TOKEN_PER_METHOD, [
                'mode'      => app('rzp.mode'),
            ]);

            throw new Exception\BadRequestValidationFailureException(
                'The selected account type is invalid.');
        }

        if (in_array($input[Entity::ACCOUNT_TYPE],
                PaperMandateConstants::NACH_EXTRA_BANK_ACCOUNT_TYPES, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The selected account type is invalid.');
        }
    }

    protected function validateExpiredAt($attribute, $value)
    {
        if (empty($value) === false)
        {
            $currentTime = Carbon::now()->getTimestamp();

            if ($value <= $currentTime)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Expiry time should be greater than the current time',
                    null,
                    [
                        'expired_at'    => $value,
                        'id'            => $this->entity->getId(),
                    ]);
            }
        }
    }
}
