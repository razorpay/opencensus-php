<?php

namespace RZP\Models\Customer\Token;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Customer\AppToken;
use RZP\Models\Customer\Token;
use RZP\Models\Customer\GatewayToken;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
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

    public function createNetworkToken($input)
    {
        $this->validateMode();

        $token = $this->core->createNetworkToken($input);

        return $this->generateMockResponse($token);
    }

    public function fetchNetworkToken($id)
    {
        $this->validateMode();

        $token = $this->repo->token->getByPublicIdAndMerchant($id, $this->merchant);

        if ($token === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Token not found');
        }

        return $this->generateMockResponse($token);
    }

    public function fetchCryptoGram($id)
    {
        $this->validateMode();

        $token = $this->repo->token->getByPublicIdAndMerchant($id, $this->merchant);

        if ($token === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Token not found');
        }

        return $this->generateMockResponseForCryptoGram($token);
    }

    public function deleteNetworkToken($id)
    {
        $this->validateMode();

        $token = $this->repo->token->getByPublicIdAndMerchant($id, $this->merchant);

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

        $response['provider'] = [
                'type'  => 'network',
                'name'  => $token->card->getNetwork(),
                'data'  => [
                    'token_number'           => $token->card->getIin() .  strrev(substr($cardNumber, 7, strlen($cardNumber))),
                    'cryptogram_value'       => str_shuffle('1122334AWEQOELASRESAasdblqwer83446778899'),
                    'expiry_month'           => $token->card->getExpiryMonth(),
                    'expiry_year'            => $token->card->getExpiryYear(),
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

        $response['card']['token_iin']       = $token->card->getIin();

        $response[Card\Entity::EXPIRY_MONTH] = $token->card->getExpiryMonth();

        $response[Card\Entity::EXPIRY_YEAR]  = $token->card->getExpiryYear();

        $response['status'] = ($token->isExpired() === true) ? 'deactivated' : 'activated';

        $response['service_providers'] = [[
                'type'  => 'network',
                'name'  => $token->card->getNetwork(),
                'data'  => [
                    'token_reference_number' => $token->card->getVaultToken(),
                    'card_reference_number'  => $token->card->getGlobalFingerPrint(),
                    'interoperable'          => true,
                ],
            ]];

        return $response;
    }
}
