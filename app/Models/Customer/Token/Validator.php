<?php

namespace RZP\Models\Customer\Token;

use Carbon\Carbon;
use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Bank;
use RZP\Models\Card;
use RZP\Models\Payment\Processor\Wallet;

class Validator extends Base\Validator
{
    const CREATE_DIRECT = 'create_direct';

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
        // We generate it if max_amount is not present and method is emandate or nach or upi
        Entity::MAX_AMOUNT          => 'sometimes_if:method,emandate,nach,upi',
        Entity::WALLET              => 'required_only_if:method,wallet|custom',
        Entity::AUTH_TYPE           => 'required_only_if:method,emandate,nach|string|filled|in:netbanking,aadhaar,debitcard,physical,migrated',
        Entity::RECURRING           => 'sometimes|boolean',
        Entity::GATEWAY_TOKEN       => 'required_if:auth_type,migrated|string',
        Entity::GATEWAY_TOKEN2      => 'sometimes|string',
        // We generate it if expired_at is not present and method is emandate
        Entity::EXPIRED_AT          => 'sometimes|epoch:946684800,9223372036854775807|nullable|custom',
        Entity::ACCOUNT_NUMBER      => 'sometimes|nullable|alpha_num|between:5,20',
        Entity::ACCOUNT_TYPE        => 'sometimes|nullable|string|in:savings,current',
        Entity::BENEFICIARY_NAME    => 'sometimes|nullable|alpha_space_num|between:4,120',
        Entity::IFSC                => 'sometimes|nullable|alpha_num|size:11',
        Entity::AADHAAR_NUMBER      => 'sometimes|nullable|string|size:12',
        Entity::AADHAAR_VID         => 'sometimes|nullable|string|size:16',
        Entity::START_TIME          => 'sometimes_if:method,upi,nach,emandate',
        Entity::DEBIT_TYPE          => 'required_only_if:auth_type,migrated|string|in:max_amount,fixed_amount',
        Entity::FREQUENCY           => 'required_only_if:auth_type,migrated|string|in:adhoc,monthly,quarterly,yearly',
    ];

    protected static $createDirectRules = [
        Entity::CARD            => 'required|array',
        Entity::METHOD          => 'required|in:card'
    ];

    protected static $editRules = [
        Entity::RECURRING       => 'sometimes|in:0',
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
