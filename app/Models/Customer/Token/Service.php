<?php

namespace RZP\Models\Customer\Token;

use Aws\Ec2\Exception\Ec2Exception;
use phpseclib\Crypt\AES;
use RZP\Encryption\AESEncryption;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Feature;
use RZP\Encryption;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Customer\AppToken;
use RZP\Models\Customer\Token;
use RZP\Models\Customer\GatewayToken;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Models\Mpan;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Models\PaymentsUpi;

class Service extends Base\Service
{
    const CREATE_GLOBAL_TOKEN_CRON_KEY = 'CREATE_GLOBAL_TOKEN_CRON_KEY';

    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Token\Core;
    }

    /**
     * Adds token for a customer
     *
     * @param string $id customer ID
     * @param array  $input token params
     *
     * @return array
     */
    public function add($id, $input)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $token = $this->core->createDirectToken($customer, $input);

        return $token->toArrayPublic();
    }

    /**
     * Edit an existing token for local customer
     * @param  string $id customer_id
     * @param  entity $tokenId token
     * @param  array  $input token edit params
     *
     * @return array  edited token
     */
    public function edit($id, $tokenId, $input)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $token = $this->core->getByTokenIdAndCustomer($tokenId, $customer);

        if ($token->getMethod() === Payment\Method::UPI)
        {
            $this->core->validateUpiTokenForUpdate($token);

            $paymentServiceClass = new Payment\Service;

            $paymentServiceClass->mandateUpdate($id, $token, $input);
        }

        $token = $this->core->edit($token, $input);

        return $token->toArrayPublic();
    }

    public function cancel($id, $tokenId)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $token = $this->core->getByTokenIdAndCustomer($tokenId, $customer);

        $this->core->validateTokenForCancel($token);

        $upiMandate = $this->repo->upi_mandate->findByTokenId($token['id']);

        $paymentServiceClass = new Payment\Service;

        $response = $paymentServiceClass->mandateCancel($id, $upiMandate, $token);

        return $response;
    }

    /**
     * fetch token for local customer
     *
     * @param  string $id customer_id
     * @param  string $tokenId token id
     * @return entity token
     */
    public function fetch($id, $tokenId)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $token = $this->core->getByTokenIdAndCustomer($tokenId, $customer);

        return $token->toArrayPublic();
    }

    /**
     * Fetch card details associated with a token
     * - Used by subcriptions service to populate mail data.
     * - Checks for local tokens, then global ones.
     * - Card entity includes expiry info, see isPublicExpiryAllowed
     *
     * @param  string $id public token id
     * @return array public card entity
     */
    public function fetchCard($id)
    {
        $token = $this->repo->token->getByPublicIdAndMerchant($id, $this->merchant);

        if ($token === null)
        {
            $sharedMerchant = $this->repo->merchant->getSharedAccount();

            $token = $this->repo->token->findByPublicIdAndMerchant($id, $sharedMerchant);
        }

        return $token->card->toArrayPublic();
    }

    /**
     * Fetch vpq details associated with a token
     * - Used by subcriptions service to populate mail/checkout page data.
     * - VPA entity includes HANDLE and username
     *
     * @param  string $id public token id
     * @return array public card entity
     */
    public function fetchVpa($id)
    {
        $token = $this->repo->token->getByPublicIdAndMerchant($id, $this->merchant);

        if ($token === null)
        {
            $sharedMerchant = $this->repo->merchant->getSharedAccount();

            $token = $this->repo->token->findByPublicIdAndMerchant($id, $sharedMerchant);
        }

        return $token->vpa->toArrayToken();
    }

    /**
     * Fetch bank details for Subscriptions Emandate
     * - Used by subscriptions service to populate mail/checkout page data.
     *
     * @param  string $id public token id
     * @return array bank details
     */
    public function fetchSubscriptionEmandateDetails($id)
    {
        $token = $this->repo->token->getByPublicIdAndMerchant($id, $this->merchant);

        if ($token === null)
        {
            $sharedMerchant = $this->repo->merchant->getSharedAccount();

            $token = $this->repo->token->findByPublicIdAndMerchant($id, $sharedMerchant);
        }

        return [
            'bank'               => $token->getBank(),
            'auth_type'          => $token->getAuthType(),
            'accountNumberLast4' => substr($token->getAccountNumber(), -4)
        ];
    }

    /**
     * fetch tokens for local customer
     *
     * @param string $id customer ID
     *
     * @return entity tokens
     */
    public function fetchMultiple($id)
    {
        // This is needed to ensure that the merchant is getting only HIS customer's details
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $withVpas = false;

        // We will exclude VPAs tokens except of for this conditions
        // 1. Feature SAVE_VPA is enabled for merchant.
        // 2. We do not want this to be on shared merchant (Adding check Just In Case)
        if (($this->merchant instanceof Merchant\Entity) and
            ($this->merchant->isShared() === false) and
            ($this->merchant->shouldSaveVpa() === true))
        {
            $withVpas = true;
        }

        $tokens = $this->repo->token->getByCustomer($customer, $withVpas);

        return $tokens->toArrayPublic();
    }

    /**
     * fetch tokens for an app_token (global customer)
     *
     * @return entity tokens
     */
    public function fetchTokensForGlobalCustomer()
    {
        $appTokenId = AppToken\SessionHelper::getAppTokenFromSession($this->mode);

        $tokens = new Base\PublicCollection;

        if ($appTokenId !== null)
        {
            $app = (new AppToken\Core)->getAppByAppTokenId($appTokenId, $this->merchant);

            $tokens = $this->core->fetchTokensByCustomer($app->customer, $this->merchant);
        }

        return $tokens->toArrayPublic();
    }

    /**
     * Deletes tokens associated with the local customer
     */
    public function deleteTokenForLocalCustomer($id, $token)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        return $this->deleteTokenForCustomer($token, $customer);
    }

    /**
     * Deletes token associated with a card for a global customer
     */
    public function deleteTokenForGlobalCustomer($token)
    {
        $appTokenId = AppToken\SessionHelper::getAppTokenFromSession($this->mode);

        if ($appTokenId !== null)
        {
            $app = (new AppToken\Core)->getAppByAppTokenId($appTokenId, $this->merchant);

            return $this->deleteTokenForCustomer($token, $app->customer);
        }

        return null;
    }

    public function pauseNotSupportedCardTokens($input)
    {
        $count = 1000;
        if (empty($input['count']) === false)
        {
            $count = $input['count'];
        }

        $tokens = $this->repo->token->getDomesticTokensWithoutCardMandateToPause($count);

        $succeeded = [];
        $failed = [];

        foreach ($tokens as $token)
        {
            try {
                $this->core->pauseCardToken($token->getId());

                $succeeded[] = $token->getId();
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::NOT_SUPPORTED_CARD_TOKEN_PAUSE_FAILED, [
                    'token_id' => $token->getId(),
                ]);

                $failed[] = $token->getId();
            }
        }

        $this->trace->info(TraceCode::CARD_TOKEN_PAUSE_PROCESSED, [
            'failed'    => $failed,
            'succeeded' => $succeeded
        ]);

        return [
            'failed'    => $failed,
            'succeeded' => $succeeded,
        ];
    }

    public function migrateToGatewayTokens(array $input = [])
    {
        $failureCount = $total = $successCount = 0;
        $failures = [];

        $tokens = $this->repo->token->findMany($input['token_ids']);

        $total = $tokens->count();

        $this->trace->info(
            TraceCode::TOKENS_FETCHED_COUNT_FOR_MIGRATE,
            [
                'input' => $input,
                'count' => $total,
            ]);

        foreach ($tokens as $token)
        {
            $this->trace->info(TraceCode::TOKEN_BEING_MIGRATED, $token->toArrayPublic());

            if (($token->isRecurring() === false) or ($token->getMethod() !== 'card'))
            {
                throw new Exception\LogicException(
                    'Only card and recurring tokens can be migrated',
                    null,
                    [
                        $token->toArrayPublic()
                    ]);
            }

            try
            {
                $gatewayTokenInput = [
                    GatewayToken\Entity::RECURRING      => $token->isRecurring(),
                    GatewayToken\Entity::ACCESS_TOKEN   => $token->getGatewayToken(),
                    GatewayToken\Entity::REFRESH_TOKEN  => $token->getGatewayToken2(),
                ];

                $gatewayToken = (new GatewayToken\Entity)->build($gatewayTokenInput);

                $gatewayToken->token()->associate($token);
                $gatewayToken->merchant()->associate($token->merchant);
                $gatewayToken->terminal()->associate($token->terminal);

                $this->repo->saveOrFail($gatewayToken);

                $this->trace->info(TraceCode::GATEWAY_TOKEN_MIGRATED, $gatewayToken->toArray());

                $successCount++;
            }
            catch(\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::DEBUG,
                    TraceCode::TOKEN_MIGRATE_TO_GATEWAY_TOKEN_FAILED,
                    [
                        'token' => $token->toArrayPublic(),
                    ]);

                $failureCount++;
                $failures[] = $token->getId();

                continue;
            }
        }

        $summary = [
            'total'         => $total,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'failures'      => $failures,
        ];

        return $summary;
    }

    public function createTokensUpiVpaBulk($input)
    {
        $limit = 100;

        if (isset($input['limit']) === true)
        {
            $limit = $input['limit'];
        }

        // Adding time log for fetching payments
        $time = time();

        /**
         * Trying to get the last created at set for cron, and use that in the query.
         */
        $lastCreatedAt = $this->app['cache']->get(self::CREATE_GLOBAL_TOKEN_CRON_KEY);

        $payments = $this->repo->useSlave(function() use ($limit, $lastCreatedAt)
        {
            return $this->repo->payment->getPaymentsForCreatingCustomerVpaTokens($limit, $lastCreatedAt ?? null);
        });

        $time = time() - $time;

        $count = count($payments);

        $this->trace->info(TraceCode::CUSTOMER_TOKENS_UPI_VPA_BULK,
            [
                'count' =>  $count,
                'time'  =>  $time.' Secs'
            ]);

        $tokensCreated = 0;
        $errors = 0;

        $customerRepo = (new Customer\Repository);
        $vpaCore = new PaymentsUpi\Vpa\Core();

        foreach ($payments as $payment)
        {
            /**
             * @var $payment Payment\Entity
             */

            $this->trace->info(TraceCode::CUSTOMER_TOKENS_UPI_VPA_BULK, [
               'payment_id'         => $payment->getId(),
               'global_customer_id' => $payment->getGlobalCustomerId(),
               'vpa'                => $payment->getVpa(),
               'global_token_id'    => $payment->getGlobalTokenId(),
            ]);

            try
            {
                $this->repo->transaction(function () use ($payment, $customerRepo, $vpaCore, &$tokensCreated)
                {
                    $customerId = $payment->getGlobalCustomerId();

                    $customer = $customerRepo->find($customerId);

                    $vpa = $vpaCore->firstOrCreate([
                        'vpa' => $payment->getVpa(),
                    ]);

                    $tokenInput = [
                        Token\Entity::METHOD    => Payment\Method::UPI,
                        Token\Entity::VPA_ID    => $vpa->getId(),
                        Token\Entity::USED_AT   => $payment->getCreatedAt(),
                    ];

                    $token = (new Token\Core)->create($customer, $tokenInput);

                    $payment->globalToken()->associate($token);

                    $payment->save();

                    $tokensCreated++;
                });
            }
            catch (\Exception $e)
            {
                $errors++;

                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::CUSTOMER_VPA_TOKEN_CREATE_FAILED,
                    [
                        'payment_id' => $payment->getId(),
                    ]);
            }
        }

        /**
         * Checking if any error occurred, we wont update the cache key.
         * Ideally, It will never occur. We can always set the new created at.
         */
        if (($errors === 0) and ($count > 0))
        {
            /**
             * Adding 7 minutes as ttl, so next cron(which runs after 5 minutes) can pick it up.
             * Multiplying by 60, since set accepts ttl in secs
             */
            $this->app['cache']->set(self::CREATE_GLOBAL_TOKEN_CRON_KEY, $payments->last()->getCreatedAt(), 7 * 60);
        }

        $response = [
            'errors'            => $errors,
            'tokens_created'    => $tokensCreated,
        ];

        $this->trace->info(TraceCode::CUSTOMER_TOKENS_UPI_VPA_BULK, [
           'response' => $response,
        ]);

        return $response;
    }

    protected function deleteTokenForCustomer($tokenId, $customer)
    {
        $token = $this->core->getByTokenIdAndCustomer($tokenId, $customer);

        if ($token === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Token not found');
        }

        $token = $this->repo->token->deleteOrFail($token);

        if ($token === null)
        {
            return ['deleted' => true];
        }

        return $token->toArrayPublic();
    }

    public function decryptCardNumberIfApplicable(& $input)
    {
        if (empty($input['card']['encrypted_number']) === true)
        {
            return;
        }

        $this->trace->info(TraceCode::TOKEN_REQUESTOR_CARD_NUMBER_DECRYPTION, [$input["card"]["encrypted_number"]]);

        try
        {
            $params = [
                AESEncryption::MODE => AES::MODE_CBC,
                AESEncryption::IV => $this->app['config']->get('applications.tokenisation.flipkart_secure_IV'),
                AESEncryption::SECRET => $this->app['config']->get('applications.tokenisation.flipkart_secure_key'),
            ];

            $cipher = base64_decode($input["card"]["encrypted_number"]);

            $Decryptor = new Encryption\AESEncryption($params);

            $plainText = $Decryptor->decrypt($cipher);
        }
        catch (\Exception $e)
        {
            throw new \Exception(ErrorCode::BAD_REQUEST_DECRYPTION_FAILED);
        }

        if (empty($plainText) === true)
        {
            throw new \Exception(ErrorCode::BAD_REQUEST_INPUT_VALIDATION_FAILURE);
        }

        unset($input["card"]["encrypted_number"]);

        $input["card"]["number"] = $plainText;
    }

    // todo Rename this to createTokenAndTokenizeCard
    public function createNetworkToken($input)
    {
        $this->decryptCardNumberIfApplicable($input);

        if ($this->merchant->isFeatureEnabled(Feature\Constants::NETWORK_TOKENIZATION_LIVE) === true)
        {
            list($token, $serviceProviderTokens) = $this->core->createTokenAndTokenizedCard($input);

            return $token->toArrayPublicTokenizedCard($serviceProviderTokens);
        }

        $this->validateMode();

        $token = $this->core->createNetworkToken($input);

        return $this->generateMockResponse($token);
    }

    public function fetchNetworkToken($input)
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::NETWORK_TOKENIZATION_LIVE) === true)
        {
            (new Validator)->validateInput(Validator::FETCH_TOKEN, $input);

            $token = $this->repo->token->findOrFailByPublicIdAndMerchant($input['id'], $this->merchant);

            $serviceProviderTokens = [];

            if ($this->merchant->isFeatureEnabled(Feature\Constants::ALLOW_NETWORK_TOKENS) === true)
            {
                $serviceProviderTokens = $this->core->fetchToken($token);
            }

            return $token->toArrayPublicTokenizedCard($serviceProviderTokens);
        }

        $this->validateMode();

        $token = $this->repo->token->getByPublicIdAndMerchant($input['id'], $this->merchant);

        if ($token === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Token not found');
        }

        return $this->generateMockResponse($token);
    }

    public function fetchCryptoGram($input)
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::NETWORK_TOKENIZATION_LIVE) === true)
        {
            (new Validator)->validateInput(Validator::FETCH_CRYPTOGRAM, $input);

            $serviceProviderToken = $this->core->fetchCryptogram($input['id'], $this->merchant);

            return $this->generateCryptogramResponse($serviceProviderToken);
        }

        $this->validateMode();

        $token = $this->repo->token->getByPublicIdAndMerchant($input['id'], $this->merchant);

        if ($token === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Token not found');
        }

        return $this->generateMockResponseForCryptoGram($token);
    }

    public function deleteNetworkToken($input)
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::NETWORK_TOKENIZATION_LIVE) === true)
        {
            (new Validator)->validateInput(Validator::FETCH_TOKEN, $input);

            $token = $this->repo->token->findOrFailByPublicIdAndMerchant($input['id'], $this->merchant);

            $this->core->deleteToken($token);

            return [];
        }

        $this->validateMode();

        $token = $this->repo->token->getByPublicIdAndMerchant($input['id'], $this->merchant);

        if ($token === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Token not found');
        }

        $token = $this->repo->token->deleteOrFail($token);

        return [];
    }


    public function validateMode()
    {
        if ($this->app['rzp.mode'] !== 'test')
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

    }
    public function generateMockResponseForCryptoGram($token)
    {
        $cardToken = $token->card->getVaultToken();

        $cardNumber = (new Card\CardVault)->getCardNumber($cardToken);

        $response['service_provider_tokens'] = [[
                'provider_type'  => 'network',
                'provider_name'  => $token->card->getNetwork(),
                'interoperable'  => true,
                'provider_data'  => [
                    'token_number'           => $token->card->getIin() .  strrev(substr($cardNumber, 7, strlen($cardNumber))),
                    'cryptogram_value'       => str_shuffle('1122334AWEQOELASRESAasdblqwer83446778899'),
                    'token_expiry_month'     => $token->card->getExpiryMonth(),
                    'token_expiry_year'      => $token->card->getExpiryYear(),
                ]
            ]];

        return $response;
    }

    public function generateMockResponse($token)
    {
        $response = $token->toArrayPublic();

        foreach (Token\Entity::$networkTokenUnsetAttributes as $attribute)
        {
            unset($response[$attribute]);
        }

        foreach (Card\Entity::$networkTokenCardUnsetAttributes as $attribute)
        {
            unset($response['card'][$attribute]);
        }

        if (empty($token->getCustomerId()) === false)
        {
            $response[Token\Entity::CUSTOMER_ID] = $token->customer->getPublicId();
        }

        if ($this->merchant->isFeatureEnabled(Feature\Constants::ALLOW_NETWORK_TOKENS) === true)
        {
            $response['compliant_with_tokenisation_guidelines'] = true;

            $response['service_provider_tokens'] = [[
                'id'             => 'spt_' . substr(UniqueIdEntity::generateUniqueId() ?? null, 0, 8),
                'entity'         => 'service_provider_token',
                'provider_type'  => 'network',
                'provider_name'  => $token->card->getNetwork(),
                'status'         => 'created',
                'interoperable'  => true,
            ]];

            if ($token->card->getNetwork() === Mpan\Constants::MASTERCARD)
            {
                $response['status'] = 'created';

                $response['expired_at'] = null;

                $response['service_provider_tokens'][0]['provider_data'] = [
                    'token_reference_number' => $token->card->getVaultToken(),
                    'card_reference_number'  => $token->card->getGlobalFingerPrint(),
                    'token_iin'              => null,
                    'token_expiry_month'     => null,
                    'token_expiry_year'      => null,
                ];
            }
            else
            {
                $response['status'] = ($token->isExpired() === true) ? 'deactivated' : 'activated';

                $response['service_provider_tokens'][0]['provider_data'] = [
                    'token_reference_number' => $token->card->getVaultToken(),
                    'card_reference_number'  => $token->card->getGlobalFingerPrint(),
                    'token_iin'              => $token->card->getIin(),
                    'token_expiry_month'     => $token->card->getExpiryMonth(),
                    'token_expiry_year'      => $token->card->getExpiryYear(),
                ];
            }
        }

        $response['notes'] = [];

        return $response;
    }

    public function generateCryptogramResponse($serviceProviderTokens)
    {
        $serviceProviderTokensArray = array();

        foreach ($serviceProviderTokens as $provider)
        {
            foreach (Token\Entity::$cryptogramDataServiceProviderTokensUnsetAttributes as $attribute)
            {
                unset($provider[$attribute]);
            }

            foreach (Token\Entity::$cryptogramDataProviderDataUnsetAttributes as $attribute)
            {
                unset($provider[Token\Entity::PROVIDER_DATA][$attribute]);
            }

            $provider[Token\Entity::PROVIDER_DATA][Token\Entity::CRYPTOGRAM_VALUE] = (string)$provider[Token\Entity::PROVIDER_DATA][Token\Entity::CRYPTOGRAM_VALUE];

            array_push($serviceProviderTokensArray, $provider);
        }

        return $serviceProviderTokensArray[0][Token\Entity::PROVIDER_DATA];
    }

    public function updateStatus($input)
    {
        $this->trace->info(
            TraceCode::VAULT_TOKEN_STATUS_UPDATE_SERVICE,
            ['input' => $input]);

        (new Validator)->validateInput(Validator::GET_STATUS, $input);

        $response = [
            'token_id' => $input['token_id'],
            'status'   => $input['status']
        ];

        $token = $this->core->updateStatus($input);

        $this->triggerStatusWebhook($input, $token);

        $response['vault_token'] = $token->card['vault_token'];

        $this->trace->info(
            TraceCode::VAULT_TOKEN_STATUS_UPDATE_SERVICE,
            ['input' => $input]);

        return $response;
    }

    protected function triggerStatusWebhook($input, $dbToken)
    {
        $serviceProviderTokens = $this->core->fetchToken($dbToken);

        $eventPayload = [
            ApiEventSubscriber::MAIN => $dbToken,
            ApiEventSubscriber::WITH => $serviceProviderTokens,
        ];

        if ($input[Token\Entity::STATUS] === 'active')
        {
            $this->app['events']->dispatch('api.token.service_provider.activated', $eventPayload);
        }
        elseif ($input[Token\Entity::STATUS] === 'suspended')
        {
            $this->app['events']->dispatch('api.token.service_provider.cancelled', $eventPayload);
        }
        elseif ($input[Token\Entity::STATUS] === 'deactivated')
        {
            $this->app['events']->dispatch('api.token.service_provider.deactivated', $eventPayload);
        }
    }
}
