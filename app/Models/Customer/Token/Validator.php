<?php

namespace RZP\Models\Customer\Token;

use Carbon\Carbon;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Exception\BadRequestException;
use RZP\Models\Bank;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\PaperMandate\Constants as PaperMandateConstants;
use RZP\Models\Merchant;
use RZP\Models\Card;

class Validator extends Base\Validator
{
    const CREATE_DIRECT                                 = 'create_direct';
    const CREATE_NETWORK_TOKEN                          = 'create_network_token';
    const CREATE_NETWORK_TOKEN_RUPAY                    = 'create_network_token_rupay';
    const CREATE_NETWORK_CARD                           = 'create_network_card';
    const CREATE_NETWORK_TOKEN_AUTHENTICAION_DATA       = 'create_network_token_authentication_data';
    const CREATE_NETWORK_TOKEN_AUTHENTICAION_DATA_RUPAY = 'create_network_token_authentication_data_rupay';
    const FETCH_CRYPTOGRAM                              = 'fetch_cryptogram';
    const FETCH_TOKEN                                   = 'fetch_token';
    const DELETE_TOKEN                                  = 'delete_token';
    const GET_STATUS                                    = 'get_status';
    const FETCH_PAR_VALUE                               = 'fetch_par_value';
    const FETCH_MERCHANTS_WITH_TOKEN_PRESENT            = 'fetch_merchants_with_token_present';
    const TOKEN_PUSH                                    = 'token_push';

    /**
     * token epoch constrains :
     * min : Sat Jan  1 05:30:00 IST 2000
     * max : 17 August 292278994 - max for 64 bit signed int
    **/

    protected static $tokenPushRules = [
        Entity::CUSTOMER_ID          => 'required|public_id',
        Entity::METHOD               => 'required|in:card',
        Entity::CARD                 => 'required|array',
        Entity::ACCOUNT_IDS          => 'required|array|max:10'
    ];

    protected static $fetchParValueRules = [
        Card\Entity::NUMBER          => 'required_without:vault|numeric|luhn|digits_between:12,19',
        Card\Entity::TOKENISED       => 'sometimes|boolean',
        Card\Entity::VAULT           => 'required_without:number|string',
        Card\Entity::NETWORK         => 'sometimes|string',
        'via_push_provisioning'      => 'sometimes|boolean'
    ];

    protected static $createRules = [
        Entity::METHOD              => 'required|in:card,emandate,wallet,nach,upi,paylater',
        Entity::CARD_ID             => 'required_only_if:method,card|alpha_num|size:14',
        Entity::BANK                => 'required_only_if:method,emandate,nach|custom',
        Entity::VPA_ID              => 'required_only_if:method,upi|alpha_num|size:14',
        // We generate it if max_amount is not present and method is emandate or nach or upi or card
        Entity::MAX_AMOUNT          => 'sometimes_if:method,emandate,nach,upi,card',
        Entity::WALLET              => 'required_only_if:method,wallet,paylater|custom',
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
        Entity::FREQUENCY           => 'required_if:auth_type,migrated|required_if:recurring,1|string|in:adhoc,daily,weekly,monthly,quarterly,yearly,as_presented',
        Entity::STATUS              => 'sometimes|nullable',
        Entity::NOTES               => 'sometimes|notes'
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
        Entity::NOTES                => 'sometimes|notes',
        'via_push_provisioning'      => 'sometimes|boolean'
    ];

    protected static $createNetworkTokenRupayRules = [
        Entity::CARD                 => 'required|array',
        Entity::CUSTOMER_ID          => 'sometimes|public_id',
        Entity::METHOD               => 'required|in:card',
        Entity::AUTHENTICATION       => 'sometimes',
        Entity::NOTES                => 'sometimes|notes',
        'via_push_provisioning'      => 'sometimes|boolean'
    ];

    protected static $createNetworkCardRules = [
        'number'       => 'required',
        'expiry_month' => 'required|numeric|digits_between:1,2|max:12|min:1',
        'expiry_year'  => 'required|numeric|digits_between:2,4',
        'cvv'          => 'sometimes|numeric|digits_between:3,4|nullable',
    ];

    protected static $fetchMerchantsWithTokenPresentRules = [
        Entity::METHOD               => 'required|in:card',
        Entity::CARD                 => 'required|array',
        Entity::NOTES                => 'sometimes|notes',
        'account_ids'                => 'required|array|max:' . Entity::PUSH_PROVISIONING_FETCH_MERCHANTS_WITH_TOKEN_LIMIT,
        'account_ids.*'              => 'sometimes|public_id'
    ];

    protected static $createNetworkTokenAuthenticationDataRules = [
        "provider"              => "string",
        "provider_reference_id" => "string",
    ];

    protected static $createNetworkTokenAuthenticationDataRupayRules = [
        "provider"                        => "sometimes|string",
        "provider_reference_id"           => "sometimes|string",
        "authentication_reference_number" => "sometimes|string",
    ];

    protected static $fetchCryptogramRules = [
        'id'         => 'sometimes',
        'token_id'   => 'sometimes|public_id',
    ];

    protected static $fetchTokenRules = [
        'id'         => 'sometimes',
        'token_id'   => 'sometimes|public_id',
    ];

    protected static $deleteTokenRules = [
        'id'         => 'sometimes',
        'token_id'   => 'sometimes|public_id',
    ];

    protected static $getStatusRules = [
        'token_id'     => 'required|string',
        'status'       => 'required|string',
        'iin'          => 'sometimes',
        'expiry_month' => 'sometimes',
        'expiry_year'  => 'sometimes',
    ];

    protected static $recurringTokenPreDebitNotifyRules = [
        'debit_at'      => 'required|epoch:946684800,9223372036854775807',
        'amount'        => 'required|integer|min_amount',
        'purpose'       => 'sometimes|string|max:512',
        'currency'      => 'sometimes|string|in:INR',
        'notes'         => 'sometimes|notes',
        'payment_id'    => 'sometimes|string',
    ];

    protected static $validateBulkLocalTokenisationRules = [
        'merchant_id'   => 'required|string|not_in:' . Merchant\Account::SHARED_ACCOUNT,
        'token_ids'     => 'required|filled|array|max:' . Entity::BULK_TOKENISATION_INPUT_LIMIT,
    ];

    protected static $validateGlobalSavedCardAsyncTokenisationRules = [
        'batch_size'    => 'sometimes|integer|max:10000'
    ];

    protected static $deleteTokensRules = [
        Card\Constants::TOKENS        => 'required|array',
        Card\Constants::TOKENS . '.*' => 'required_with:' . Card\Constants::TOKENS . '|string|size:14',
    ];

    protected static $validateGlobalCustomerLocalSavedCardAsyncTokenisationRules = [
        'batch_size' => 'sometimes|integer|max:100000'
    ];

    /**
     * @param ?Entity $token
     * @param ?Merchant\Entity $merchant
     * @return array
     */
    public function validateGlobalTokenToLocalTokenMigrationInput(
        ?Entity $token,
        ?Merchant\Entity $merchant
    ): array
    {
        $output = ['reason' => '', 'valid' => false];

        if ($token === null)
        {
            $output['reason'] = 'Token is deleted or does not exist';

            return $output;
        }

        if ($merchant === null)
        {
            $output['reason'] = 'Merchant is deleted or does not exist';

            return $output;
        }

        if ($token->isLocal())
        {
            $output['reason'] = 'Token merchant is not global';

            return $output;
        }

        if ($token->hasBeenAcknowledged() === false)
        {
            $output['reason'] = 'Consent is not received for the token';

            return $output;
        }

        if (!isset($token->customer) || $token->customer->isGlobal() === false)
        {
            $output['reason'] = 'Token customer merchant is not global';

            return $output;
        }

        if ($token->isExpired() === true || $token->card->getExpiryTimestamp() <= Carbon::now()->getTimestamp())
        {
            $output['reason'] = 'Token expired';

            return $output;
        }

        if ($merchant->isShared() === true)
        {
            $output['reason'] = 'Input merchant is global merchant';

            return $output;
        }

        $existingToken = (new Core())->checkIfSimilarTokenCardAlreadyExistsOnCustomerAndMerchant(
            $token,
            $token->customer,
            $merchant
        );

        if (isset($existingToken))
        {
            $output['reason'] = 'Duplicate token';
            $output['existing_token_id'] = $existingToken->getId();

            return $output;
        }

        $output['valid'] = true;

        return $output;
    }

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
        if ((Wallet::exists($value) === false) and (PayLater::exists($value) === false))
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

    public function validateDeleteTokensInput($input, $customerId)
    {
        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];

        (new static)->validateInput('delete_tokens', $input);

        $tokenCount = $this->repo->token->getCountOfExistingTokensByTokensAndCustomer($input['tokens'], $customerId);

        // check if tokens found in db are less than provided
        if ($tokenCount !== count($input['tokens']))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_CUSTOMER_TOKEN_COUNT_NOT_EQUAL
            );
        }
    }
}
