<?php

namespace RZP\Models\Customer\Token;

use Carbon\Carbon;
use RZP\Constants\Environment;
use RZP\Constants\Timezone;
use RZP\Diag\EventCode;
use Aws\Ec2\Exception\Ec2Exception;
use phpseclib\Crypt\AES;
use RZP\Encryption\AESEncryption;
use RZP\Http\RequestHeader;
use RZP\Jobs\MerchantAsyncTokenisationJob;
use RZP\Jobs\SavedCardTokenisationJob;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Batch\Header;
use RZP\Constants\Mode;
use RZP\Models\Card;
use RZP\Models\Customer;
use RZP\Models\Customer\Token\Constants as TokenConstants;
use RZP\Models\Merchant;
use RZP\Models\Feature;
use RZP\Encryption;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Jobs\PushProvisioningTokenCreateJob;
use RZP\Models\Customer\AppToken;
use RZP\Models\Customer\Token;
use RZP\Models\Customer\GatewayToken;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Mpan;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Models\PaymentsUpi;
use RZP\Models\CardMandate;
use RZP\Models\Terminal;
use RZP\Gateway\Base\Metric as BaseMetric;
use RZP\Models\Customer\Token\Entity as TokenEntity;
use RZP\Models\CardMandate\CardMandateNotification;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Service extends Base\Service
{
    use Card\InputDecryptionTrait;

    const CREATE_GLOBAL_TOKEN_CRON_KEY = 'CREATE_GLOBAL_TOKEN_CRON_KEY';

    const IDEMPOTENCY_KEY             = 'idempotent_id';
    const BATCH_ERROR                 = 'error';
    const BATCH_ERROR_CODE            = 'code';
    const BATCH_ERROR_DESCRIPTION     = 'description';
    const BATCH_SUCCESS               = 'success';
    const BATCH_HTTP_STATUS_CODE      = 'http_status_code';

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
        $token = $this->repo->token->findByPublicId($tokenId);

        if((($token->getEntityType() === Entity::SUBSCRIPTION) and
            ($id === "cust_") and
            ($token->getMethod() === Payment\Method::UPI)) === false)
        {
            $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

            $token = $this->core->getByTokenIdAndCustomer($tokenId, $customer);
        }

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

       if (($this->mode !== MODE::TEST || ($this->app->runningUnitTests() === true)) && ($token->isCard() === true) && ($token->isRecurring() === false) && ($token->card->isTokenisationCompliant($token->merchant) === false) && ( ($token->getStatus() === 'failed') || ($token->getStatus() === null )))
       {
            $errorCode = $token->getInternalErrorCode() ?? ErrorCode::BAD_REQUEST_TOKEN_CREATION_FAILED;

            try
            {
                throw new Exception\BadRequestException($errorCode);
            }
            catch (\Throwable $exception)
            {
                $error = $exception->getError();

                $token->setStatus(Token\Constants::FAILED);
                $token->setErrorCode($error->getPublicErrorCode());
                $token->setErrorDescription($exception->getMessage());
            }
        }
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

    public function fetchSubscriptionCardMandateDetails($id)
    {
        try
        {
            $token = $this->repo->token->getByPublicIdAndMerchant($id, $this->merchant);

            if ($token === null)
            {
                $sharedMerchant = $this->repo->merchant->getSharedAccount();

                $token = $this->repo->token->findByPublicIdAndMerchant($id, $sharedMerchant);
            }

            return [
                'maxAmount' => $token->cardMandate->getMaxAmount()
            ];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SUBSCRIPTION_CARD_MANDATE_DATA_FETCH_FAILED
            );

            throw $e;
        }
    }

    /**
     * fetch tokens for local customer
     *
     * @param string $id customer ID
     *
     * @return array tokens
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

        $tokens = $this->repo->token->getByCustomer($customer, $withVpas, $this->merchant->getId(), $this->mode);

        if ($this->mode !== "test")
        {
            $tokens = $this->core->removeNonCompliantCardTokens($tokens);
        }

        return $tokens->toArrayPublic();
    }

    /**
     * Fetch tokens using customer id for Checkout.
     *
     * @param string $customerId
     *
     * @return array
     */
    public function fetchTokensForLocalCustomerForCheckout(string $customerId): array
    {
        // This is needed to ensure that the merchant is getting only his customer's details
        $customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $this->merchant);

        $tokens = $this->core->fetchTokensByCustomerForCheckout($customer, $this->merchant);

        $tokens = $this->core->filterTokensForCheckout($tokens);

        return $tokens->toArrayPublic();
    }

    /**
     * fetch tokens for an app_token (global customer)
     *
     * @return array tokens
     */
    public function fetchTokensForGlobalCustomer(): array
    {
        $appTokenId = AppToken\SessionHelper::getAppTokenFromSession($this->mode);

        $tokens = new Base\PublicCollection;

        if (!empty($appTokenId))
        {
            $app = (new AppToken\Core)->getAppByAppTokenId($appTokenId, $this->merchant);

            if ($app !== null && $app->customer !== null) {
                $tokens = $this->core->fetchTokensByCustomerForCheckout($app->customer, $this->merchant);

                $tokens = $this->core->filterTokensForCheckout($tokens);
            }
        }

        return $tokens->toArrayPublic();
    }

    /**
     * Fetched Tokens associated to Local (Based on customer_id in $input) or
     * Global (Based on Session Cookie passed in Headers) Customers.
     *
     * @param array $input
     * @return array
     */
    public function fetchLocalOrGlobalCustomerTokens(array $input): array
    {
        if (empty($input['customer_id'])) {
            $tokens = $this->fetchTokensForGlobalCustomer();
        } else {
            $tokens = $this->fetchTokensForLocalCustomerForCheckout($input['customer_id']);
        }

        // sending notes and card flows as empty object for empty values
        // without this, php sends them as empty arrays
        foreach ($tokens['items'] as $i => $token) {
            if (isset($token['card'])) {
                $tokens['items'][$i]['card']['flows'] = (object)($token['card']['flows'] ?? []);
            }
            $tokens['items'][$i]['notes'] = (object)($token['notes'] ?? []);
        }

        return $tokens;
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
        $succeeded = [];
        $failed = [];

        if (empty($input['token_ids']) === true)
        {
            return [
                'failed'    => $failed,
                'succeeded' => $succeeded,
            ];
        }

        foreach ($input['token_ids'] as $tokenId)
        {
            try
            {
                $this->core->pauseCardToken($tokenId);

                $succeeded[] = $tokenId;
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::NOT_SUPPORTED_CARD_TOKEN_PAUSE_FAILED, [
                    'token_id' => $tokenId,
                ]);

                $failed[] = $tokenId;
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

    public function tokensPush(& $input, $internalServiceRequest = false)
    {
        $startTime = microtime(true);

        try
        {
            $response = [];

            $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

            (new Validator)->validateInput(Validator::TOKEN_PUSH, $input);

            $this->trace->info(
                TraceCode::TOKEN_PUSH_INFO,
                ['mode' => $this->app['rzp.mode'],
                    'features' => $this->merchant->getEnabledFeatures(),
                    'merchantCount' => count($input[Token\Entity::ACCOUNT_IDS]),
                    'account_ids' => $input[Token\Entity::ACCOUNT_IDS]]);

            // validate merchant flag to check if this is issuer.
            // throw error otherwise
            if ($this->merchant->isFeatureEnabled(Feature\Constants::PUSH_PROVISIONING_LIVE) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, "push provisioning is not enabled for merchant");
            }

            if (($mode === Mode::LIVE) || app()->isEnvironmentQA() === true)
            {

                if (empty($input['card']['number']) === false)
                {
                    $input['card']['number'] = trim(str_replace(" ", "", $input['card']['number']));
                }

                $customerIssuer = $this->repo->customer->findByPublicIdAndMerchant($input['customer_id'], $this->merchant);

                //Should be in an async function

                $tokensResponse = [];

                foreach ($input[Token\Entity::ACCOUNT_IDS] as $account_id)
                {
                    $merchantId =  trim(str_replace("acc_", "", $account_id));

                    $merchantPushProvisioning = $this->repo->merchant->fetchMerchantFromId($merchantId);

                    $this->merchant = $merchantPushProvisioning;

                    $customer =  $this->getCustomerByMerchantType($customerIssuer);

                    $network = Card\Network::detectNetwork(substr($input['card']['number'], 0, 6));

                    $cardInput = [
                        Card\Entity::NUMBER           => $input['card']['number'],
                        Card\Entity::EXPIRY_MONTH     => $input['card']['expiry_month'],
                        Card\Entity::EXPIRY_YEAR      => $input['card']['expiry_year'],
                        Card\Entity::VAULT            => Card\Vault::RZP_VAULT,
                        Card\Entity::CVV              => Card\Entity::getDummyCvv($network)
                    ];

                    $cardData = (new Card\Core)->createAndReturnWithSensitiveData($cardInput, $merchantPushProvisioning, false, false);

                    $tokenCreateInput = [
                        Token\Entity::CARD_ID           => $cardData['id'],
                        Token\Entity::METHOD            => Payment\Method::CARD
                    ];

                    $token = (new Token\Core)->create($customer, $tokenCreateInput);

                    //Required to override incase of global/standard checkout cases merchant needs to be explicitly set to local merchant.
                    $token->merchant()->associate($this->merchant);

                    $token->setAcknowledgedAt(Carbon::now(Timezone::IST)->getTimestamp());

                    $token->setSource(TokenConstants::ISSUER);

                    //UsedCount and UsedAt set for token fetch in checkout.
                    $token->setUsedCount(1);
                    $token->setUsedAt(Carbon::now()->getTimestamp());

                    $this->repo->saveOrFail($token);

                    $asyncTokenisationJobId = "pushtokenmigrate";

                    (new Token\Core())->updateTokenStatus($token['id'], Token\Constants::INITIATED);

                    SavedCardTokenisationJob::dispatch($this->mode, $token['id'], $asyncTokenisationJobId, null);

                    $tokensResponse[$account_id] = "token_".$token['id'];

                }

                (new Metric())->pushTokenProvisioningResponseTimeMetrics($startTime, BaseMetric::SUCCESS, Token\Action::TOKEN_PUSH);
                (new Metric())->pushTokenProvisioningSRMetrics(BaseMetric::SUCCESS, Token\Action::TOKEN_PUSH_SR);

            }
            $response['tokens'] = $tokensResponse;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::TOKEN_PUSH_EXCEPTION);

            (new Metric())->pushTokenProvisioningResponseTimeMetrics($startTime, BaseMetric::FAILED, Token\Action::TOKEN_PUSH);
            (new Metric())->pushTokenProvisioningSRMetrics(BaseMetric::FAILED, Token\Action::TOKEN_PUSH_SR);

            throw $e;

        }

        return $response;
    }

    public function getCustomerByMerchantType($customerIssuer) {

        $merchantForCustomerCreation = $this->merchant;

        $variant = $this->app->razorx->getTreatment($this->merchant->getId(), RazorxTreatment::ENABLE_STANDARD_CHECKOUT_MERCHANTS_ON_PUSH_TOKEN_PROVISIONING, $this->mode);

        if(strtolower($variant) === 'on')
            $merchantForCustomerCreation = $this->repo->merchant->fetchMerchantFromId(Merchant\Account::SHARED_ACCOUNT);

        $customer =  (new Customer\Core)->createLocalCustomer([
            Customer\Entity::CONTACT       => $customerIssuer->getContact(),
            Customer\Entity::EMAIL         => $customerIssuer->getEmail(),
        ], $merchantForCustomerCreation, false);

        $this->trace->info(
            TraceCode::TOKEN_PUSH_CUSTOMER_INFO, [
            'variant' => $variant,
            'merchantForCustomerCreation' => $merchantForCustomerCreation['id'],
            'customer' => $customer['id']]);
        return $customer;
    }

    public function tokensPushFetch($id) {

        $startTime = microtime(true);

        $this->trace->info(
            TraceCode::TOKEN_PUSH_FETCH_INFO, ['mode' => $this->app['rzp.mode'], 'features' => $this->merchant->getEnabledFeatures(), 'public_id' => $id]);

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        try
        {
            $response = [];

            if ($this->merchant->isFeatureEnabled(Feature\Constants::PUSH_PROVISIONING_LIVE) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, "push provisioning is not enabled for merchant");
            }

            if ((($mode === Mode::LIVE) || app()->isEnvironmentQA() === true))
            {

                $token = $this->repo->token->findByPublicId($id);

                $response[Token\Entity::STATUS] = $token[Token\Entity::STATUS];
                $response[Token\Entity::CREATED_AT] = $token[Token\Entity::CREATED_AT];

                return $response;

            }

            $response[Token\Entity::STATUS] = 'test'; //response for test mode

            return  $response;
        }

        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PUSH_TOKEN_FETCH_EXCEPTION);

            (new Metric())->pushTokenHQResponseTimeMetrics($startTime, BaseMetric::FAILED, Token\Action::TOKEN_PUSH_FETCH);

            throw $e;
        }
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

    public function recurringTokenPreDebitNotify($id, $input)
    {
        (new Validator)->validateInput('recurring_token_pre_debit_notify', $input);

        $token = $this->repo->token->findByPublicIdAndMerchant($id, $this->merchant);

        if ($token->isCard() === false or
            $token->isRecurring() === false or
            $token->hasCardMandate() === false)
        {
            throw new Exception\BadRequestValidationFailureException('token does not support pre debit notify');
        }

        $cardMandate = $token->cardMandate;

        if ($cardMandate->getMaxAmount() < $input[CardMandateNotification\Entity::AMOUNT])
        {
            throw new Exception\BadRequestValidationFailureException('amount can\'t greater than max amount');
        }

        if ($cardMandate->getDebitType() === CardMandate\Constants::DEBIT_TYPE_FIXED_AMOUNT and
            $input[CardMandateNotification\Entity::AMOUNT] !== $cardMandate->getMaxAmount())
        {
            throw new Exception\BadRequestValidationFailureException(
                'amount has to be same as mandate\'s max amount for fixed amount debit type');
        }

        $cardMandateNotification = (new CardMandateNotification\Core)->create($token->cardMandate, $input);

        return $cardMandateNotification->toArrayPublic();
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

    // todo Rename this to createTokenAndTokenizeCard
    public function createNetworkToken($input, $merchantPushProvisioning = null)
    {
        $startTime = microtime(true);

        try
        {

            if($merchantPushProvisioning !== null) {
                $this->merchant = $merchantPushProvisioning;
            }

            if (empty($input['card']['number']) === false) {
                $input['card']['number'] = trim(str_replace(" ", "", $input['card']['number']));
            }

            $this->decryptCardNumberIfApplicable($input['card']);

            if ($this->merchant->isTokenizationEnabled() === true)
            {
                list($token, $serviceProviderTokens) = $this->core->createTokenAndTokenizedCard($input, $merchantPushProvisioning);

                (new Metric())->pushTokenHQResponseTimeMetrics($startTime, BaseMetric::SUCCESS, Token\Action::CREATE);

                $response = $token->toArrayPublicTokenizedCard($serviceProviderTokens);

                if($this->merchant->isFeatureEnabled(Feature\Constants::ALLOW_NETWORK_TOKENS) === false) {
                    unset($response['service_provider_tokens']);
                }

                $this->manualTriggerMerchantWebhook($token, $serviceProviderTokens);

                return $response;
            }

            $this->validateMode();

            $token = $this->core->createNetworkToken($input);

            return $this->generateMockResponse($token);
        }

        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::TOKEN_CREATE_FOR_TOKENIZED_CARD_EXCEPTION);

            (new Metric())->pushTokenHQResponseTimeMetrics($startTime, BaseMetric::FAILED, Token\Action::CREATE);

            throw $e;
        }
    }

    public function manualTriggerMerchantWebhook($token, $serviceProviderTokens) {

        $eventPayload = [
            ApiEventSubscriber::MAIN => $token,
            ApiEventSubscriber::WITH => $serviceProviderTokens,
        ];

        $this->trace->info(TraceCode::MANUAL_MERCHANT_WEBHOOK_TRIGGER,
            [
                "eventpayload" => $eventPayload
            ]
        );
        $this->app['events']->dispatch('api.token.service_provider.activated', $eventPayload);

    }

    public function pushFetchTokenEvents(& $input, $isPar)
    {
        $input["tokenised"] = null;

        if($isPar){
            $input += [
                "internal_service_request" => false,
                "merchant"                 => [
                    "id"                   => $this->merchant->getId(),
                ],
            ];

            (new Token\Event())->pushEvents($input, Event::PAR_API, "_REQUEST_RECEIVED");
        }
        else{
            (new Token\Event())->pushEvents($input, Event::FETCH_TOKEN, "_REQUEST_RECEIVED");
        }
    }

    public function fetchNetworkToken(& $input, $isPar = false, $internalServiceRequest = false)
    {
        $startTime = microtime(true);

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        try
        {
            if (($this->merchant->isTokenizationEnabled() === true ) && (($mode === Mode::LIVE) || app()->isEnvironmentQA() === true))
            {
                (new Validator)->validateInput(Validator::FETCH_TOKEN, $input);

                $token = $this->repo->token->findOrFailByPublicIdAndMerchant($input['id'], $this->merchant);

                $this->addAdditionalInputParamsIfPresent($token, $input, false, $internalServiceRequest);

                $this->pushFetchTokenEvents($input, $isPar);

                $serviceProviderTokens = [];

                if ($this->merchant->isFeatureEnabled(Feature\Constants::ALLOW_NETWORK_TOKENS) === true || $isPar)
                {
                    $serviceProviderTokens = $this->core->fetchToken($token, $internalServiceRequest);
                }

                (new Metric())->pushTokenHQResponseTimeMetrics($startTime, BaseMetric::SUCCESS, Token\Action::FETCH);

                $response = $token->toArrayPublicTokenizedCard($serviceProviderTokens);

                if ($token->card->isNetworkTokenisedCard() === true)
                {
                    $response['compliant_with_tokenisation_guidelines'] = true;

                    $response['expired_at'] = $token->card->getTokenExpiryTimestamp();

                }
                else {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR, null, null, "the saved card is no longer compliant with RBI guidelines. Please use another card/payment ");
                }

                (new Token\Event())->pushEvents($input, Event::FETCH_TOKEN, "_REQUEST_PROCESSED", $response);

                return $response;
            }

            if ($isPar && ($this->merchant->isTokenizationEnabled() === false ))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR, null, null, "network_tokenization_live feature is not enabled for this merchant");
            }

            $token = $this->repo->token->getByPublicIdAndMerchant($input['id'], $this->merchant);

            if ($token === null)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Token not found');
            }

            return $this->generateMockResponse($token);
        }

        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::TOKEN_FETCH_EXCEPTION);

            (new Metric())->pushTokenHQResponseTimeMetrics($startTime, BaseMetric::FAILED, Token\Action::FETCH);

            (new Token\Event())->pushEvents($input, Event::FETCH_TOKEN, "_REQUEST_PROCESSED", null, $e);

            throw $e;
        }
    }

    public function fetchParValue($input, $internalServiceRequest = false)
    {
        $startTime = microtime(true);

        $eventData = [];

        try {
            $this->decryptCardNumberIfApplicable($input);

            // If we are getting token_id in input then we can get PAR Or Fingerprint from fetchToken api
            // If we have card number then we will have to hit fetchParApi to get PAR/Fingerprint from the network
            if ($this->merchant->isFeatureEnabled(Feature\Constants::CARD_FINGERPRINTS) === false) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, "card_fingerprints feature is not enabled for this merchant");
            }

            $network = null;

            $isTokenized = null;

            if (empty($input["token"]) == false)
            {
                $this->trace->info(TraceCode::FETCH_NETWORK_TOKEN, [
                    "token" => $input["token"]
                ]);

                $input["id"] = $input["token"];

                unset($input["token"]);

                $data = $this->fetchNetworkToken($input, true, true);

                $result["provider"] = $data["card"]["network"];
            }
            else
            {
                $this->trace->info(TraceCode::FETCH_PAR_VALUE);

                list($network, $data) = $this->core->fetchParValue($input, $internalServiceRequest);

                $result["network"] = $network;
            }

            $result["network_reference_id"] = $data["service_provider_tokens"][0]["provider_data"]["network_reference_id"]??null;

            $result["payment_account_reference"] = $data["service_provider_tokens"][0]["provider_data"]["payment_account_reference"]??null;

            (new Token\Event())->pushEvents($input, Event::PAR_API, "_RESPONSE_SENT", $data);

            (new Metric())->pushTokenHQResponseTimeMetrics($startTime, BaseMetric::SUCCESS, Token\Action::PAR_API);

            return $result;
        }
        catch (\Throwable $e){

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAR_FETCH_EXCEPTION);

            (new Token\Event())->pushEvents($input, Event::PAR_API, "_RESPONSE_SENT", null, $e);

            (new Metric())->pushTokenHQResponseTimeMetrics($startTime, BaseMetric::FAILED, Token\Action::PAR_API);

            throw $e;
        }
    }

    public function fetchCryptoGram($input)
    {
        $startTime = microtime(true);

        try
        {
            if ($this->merchant->isTokenizationEnabled() === true)
            {
                (new Validator)->validateInput(Validator::FETCH_CRYPTOGRAM, $input);

                $isSptToken = (isset($input['token_id']) === false);

                $token = $isSptToken ? $input['id'] : $token = $this->repo->token->getByPublicIdAndMerchant($input['token_id'], $this->merchant);

                $this->addAdditionalInputParamsIfPresent($token, $input, $isSptToken, false);

                (new Token\Event())->pushEvents($input, Event::NETWORK_CRYPTOGRAM, "_REQUEST_RECEIVED");

                $serviceProviderToken = $this->core->fetchCryptogram($input, $this->merchant, $token);

                (new Metric())->pushTokenHQResponseTimeMetrics($startTime, BaseMetric::SUCCESS, Token\Action::CRYPTOGRAM);

                $response = $this->generateCryptogramResponse($serviceProviderToken);

                (new Token\Event())->pushEvents($input, Event::NETWORK_CRYPTOGRAM, "_RESPONSE_SENT", $response);

                return $response;
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
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::TOKEN_CRYPTOGRAM_EXCEPTION);

            (new Metric())->pushTokenHQResponseTimeMetrics($startTime, BaseMetric::FAILED, Token\Action::CRYPTOGRAM);

            (new Token\Event())->pushEvents($input, Event::NETWORK_CRYPTOGRAM, "_RESPONSE_SENT", null, $e);

            throw $e;
        }
    }

    public function fetchMerchantsWithTokenPresent(& $input, $internalServiceRequest = false)
    {

        $startTime = microtime(true);

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        try
        {

            (new Validator)->validateInput(Validator::FETCH_MERCHANTS_WITH_TOKEN_PRESENT, $input);

            $this->trace->info(TraceCode::PUSH_PROVISIONING_FETCH_MERCHANTS_REQUEST, [
                'mode'          => $this->app['rzp.mode'],
                'account_ids'   => $input['account_ids']
            ]);

            // validate merchant flag to check if this is issuer.
            // throw error otherwise
            if ($this->merchant->isFeatureEnabled(Feature\Constants::PUSH_PROVISIONING_LIVE) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, "push provisioing is not enabled for merchant");
            }

            if (($mode === Mode::LIVE) || app()->isEnvironmentQA() === true)
            {

                if (empty($input['card']['number']) === false)
                {
                    $input['card']['number'] = trim(str_replace(" ", "", $input['card']['number']));
                }

                $card = [
                    'number'                => $input['card']['number'],
                    'via_push_provisioning' => true
                ];

                if ($this->merchant->isFeatureEnabled(Feature\Constants::CARD_FINGERPRINTS) === false)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, "card_fingerprints feature is not enabled for this merchant");
                }

                list($network, $data) = $this->core->fetchParValue($card, $internalServiceRequest);

                if(isset($data["service_provider_tokens"][0]["provider_data"]) === true)
                {
                    $fingerprint = $data["service_provider_tokens"][0]["provider_data"]["payment_account_reference"] ??
                        $data["service_provider_tokens"][0]["provider_data"]["network_reference_id"];
                }

                if(empty($fingerprint))
                {
                    throw new Exception\BadRequestException(ErrorCode::SERVER_ERROR, null, null, "card fingerprint could not be fetched for identification");
                }

                $data = $this->core->fetchCardMerchantListByFingerprint($fingerprint, $input['account_ids']);

                $response = [
                        'account_ids' => $data
                ];

                (new Metric())->pushTokenProvisioningResponseTimeMetrics($startTime, BaseMetric::SUCCESS, Token\Action::FETCH_MERCHANTS);
                (new Metric())->pushTokenProvisioningSRMetrics(BaseMetric::SUCCESS, Token\Action::FETCH_MERCHANTS_SR);


                return $response;
            }

            return $this->generateFetchMerchantsWithTokenMockResponse($input);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FETCH_MERCHANTS_WITH_TOKEN_EXEPTION);

            (new Metric())->pushTokenProvisioningResponseTimeMetrics($startTime, BaseMetric::FAILED, Token\Action::FETCH_MERCHANTS);
            (new Metric())->pushTokenProvisioningSRMetrics(BaseMetric::FAILED, Token\Action::FETCH_MERCHANTS_SR);


            throw $e;
        }
    }

    public function deleteNetworkToken($input)
    {
        $startTime = microtime(true);

        try
        {
            if ($this->merchant->isTokenizationEnabled() === true)
            {
                (new Validator)->validateInput(Validator::FETCH_TOKEN, $input);

                $token = $this->repo->token->findOrFailByPublicIdAndMerchant($input['id'], $this->merchant);

                $this->core->deleteToken($token);

                (new Metric())->pushTokenHQResponseTimeMetrics($startTime, BaseMetric::SUCCESS, Token\Action::DELETE);

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
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::TOKEN_DELETE_EXCEPTION);

            (new Metric())->pushTokenHQResponseTimeMetrics($startTime, BaseMetric::FAILED, Token\Action::DELETE);

            throw $e;
        }
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
                'status'         => 'initiated',
                'interoperable'  => true,
            ]];

            if ($token->card->getNetwork() === Mpan\Constants::MASTERCARD)
            {
                $response['status'] = 'initiated';

                $response['expired_at'] = null;

                $response['service_provider_tokens'][0]['provider_data'] = [
                    'token_reference_number'     => $token->card->getVaultToken(),
                    'payment_account_reference'  => $token->card->getGlobalFingerPrint(),
                    'token_iin'                  => null,
                    'token_expiry_month'         => null,
                    'token_expiry_year'          => null,
                ];
            }
            else
            {
                $response['status'] = ($token->isExpired() === true) ? 'deactivated' : 'active';

                $response['service_provider_tokens'][0]['provider_data'] = [
                    'token_reference_number'     => $token->card->getVaultToken(),
                    'payment_account_reference'  => $token->card->getGlobalFingerPrint(),
                    'token_iin'                  => $token->card->getIin(),
                    'token_expiry_month'         => $token->card->getExpiryMonth(),
                    'token_expiry_year'          => $token->card->getExpiryYear(),
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

            // We don't have a cryptogram value for issuer tokenisation
            if (isset($provider[Token\Entity::PROVIDER_DATA][Token\Entity::CRYPTOGRAM_VALUE]) === true)
            {
                // If cryptogram is present, don't send card data
                unset($provider[Token\Entity::PROVIDER_DATA][Token\Entity::CARD]);

                $provider[Token\Entity::PROVIDER_DATA][Token\Entity::CRYPTOGRAM_VALUE] = (string)$provider[Token\Entity::PROVIDER_DATA][Token\Entity::CRYPTOGRAM_VALUE];
            }

            if (isset($provider[Token\Entity::PROVIDER_DATA][Token\Entity::CVV])  === true) {
                $provider[Token\Entity::PROVIDER_DATA][Token\Entity::CVV] = (string)$provider[Token\Entity::PROVIDER_DATA][Token\Entity::CVV];
            }
            array_push($serviceProviderTokensArray, $provider);
        }

        return $serviceProviderTokensArray[0][Token\Entity::PROVIDER_DATA];
    }

    public function updateStatus($input)
    {

       $traceInput = $input ;

       $this->unsetSensitiveCardMetaDetails($traceInput);

        $this->trace->info(
            TraceCode::VAULT_TOKEN_STATUS_UPDATE_SERVICE,
            ['input' => $traceInput]);

        (new Validator)->validateInput(Validator::GET_STATUS, $input);

        $response = [
            'token_id' => $input['token_id'],
            'status'   => $input['status']
        ];

        $token = $this->core->updateStatus($input);

        $this->triggerStatusWebhook($input, $token);

        $response['vault_token'] = $token->card['vault_token'];

        return $response;
    }

    public function updateTokenOnAuthorized($input) {

        $token = $this->core->updateTokenOnAuthorized($input);

        $response = [
            'token_id' => $input['token_id'],
        ];

        $oldRecurringStatus = $token->getRecurringStatus();
        (new Payment\Processor\Processor($token->merchant))->eventTokenStatus($token, $oldRecurringStatus);

        $response['vault_token'] = $token->card['vault_token'];

        return $response;
    }

    protected function unsetSensitiveCardMetaDetails(array & $input)
    {
        unset($input[Card\Entity::IIN]);
        unset($input[Card\Entity::EXPIRY_MONTH]);
        unset($input[Card\Entity::EXPIRY_YEAR]);
    }

    protected function triggerStatusWebhook($input, $dbToken)
    {
        $serviceProviderTokens = $this->core->fetchToken($dbToken, true);

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

    public function localSavedCardAsyncTokenisationRecurring(): array
    {
        return $this->localSavedCardAsyncTokenisation(true);
    }

    public function localSavedCardAsyncTokenisation($recurring = false): array
    {
        try
        {
            $asyncTokenisationJobId = UniqueIdEntity::generateUniqueId();

            $featureName = $recurring ? Feature\Constants::ASYNC_TOKENISATION_RECUR : Feature\Constants::ASYNC_TOKENISATION;

            $merchantIds = $this->repo->feature->findMerchantIdsHavingFeatures([$featureName]);

            $this->app['diag']->trackTokenisationEvent(EventCode::ASYNC_TOKENISATION_JOB_INITIATED, [
                'merchant_id_count'         => count($merchantIds),
                'merchant_id_list'          => $merchantIds,
                'async_tokenization_job_id' => $asyncTokenisationJobId,
            ]);

            $this->trace->info(TraceCode::ASYNC_LOCAL_TOKENISATION_REQUEST, [
                'merchantIdsCount'          => count($merchantIds),
                'merchantIdsList'           => $merchantIds,
                'async_tokenization_job_id' => $asyncTokenisationJobId,
            ]);

            foreach ($merchantIds as $merchantId)
            {
                MerchantAsyncTokenisationJob::dispatch(
                    $this->mode,
                    $merchantId,
                    $asyncTokenisationJobId,
                    Token\Entity::GLOBAL_MERCHANT_ASYNC_TOKENISATION_QUERY_LIMIT,
                    $recurring
                );
            }

            $this->trace->info(TraceCode::ASYNC_LOCAL_TOKENISATION_DISPATCH_SUCCESS, [
                'merchantIdsCount'  => count($merchantIds),
            ]);

            return ['success' => true];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ASYNC_LOCAL_TOKENISATION_ERROR
            );

            return ['success' => false];
        }
    }

    public function localSavedCardBulkTokenisation($input): array
    {
        (new Validator())->validateInput('validate_bulk_local_tokenisation', $input);

        try
        {
            $merchantId    = $input['merchant_id'];
            $inputTokenIds = $input['token_ids'];

            $this->trace->info(TraceCode::BULK_LOCAL_TOKENISATION_REQUEST, [
                'merchantId'    => $merchantId,
                'tokenIdsCount' => count($inputTokenIds),
            ]);

            $this->repo->merchant->findOrFailPublic($merchantId);

            $tokenIds = array_unique($inputTokenIds);

            $validTokenIds = $this->core->getValidTokensForTokenisation($merchantId, $tokenIds);

            $this->core->storeConsents($merchantId, $validTokenIds);

            $asyncTokenisationJobId = UniqueIdEntity::generateUniqueId();

            $this->core->pushTokenIdsToQueueForTokenisation($validTokenIds, $asyncTokenisationJobId);

            $this->triggerBulkConsentCollectionAndTokenisationEvent($merchantId, $asyncTokenisationJobId, $validTokenIds);

            $this->trace->info(TraceCode::BULK_LOCAL_TOKENISATION_DISPATCH_SUCCESS, [
                'merchantId'              => $merchantId,
                'inputTokenIdsCount'      => count($inputTokenIds),
                'uniqueTokenIdsCount'     => count($tokenIds),
                'dispatchedTokenIdsCount' => count($validTokenIds),
            ]);

            return [
                'success'                       => true,
                'message'                       => 'Tokenisation is triggered on valid token ids',
                'merchantId'                    => $merchantId,
                'inputTokenIdsCount'            => count($inputTokenIds),
                'triggeredTokenIdsCount'        => count($validTokenIds),
            ];
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::BULK_LOCAL_TOKENISATION_ERROR
            );

            return ['success' => false, 'message' => 'Error occurred while triggering tokenisation'];
        }
    }

    /**
     * @param string $merchantId
     * @param string $asyncTokenisationJobId
     * @param array  $tokenIds
     */
    protected function triggerBulkConsentCollectionAndTokenisationEvent(string $merchantId, string $asyncTokenisationJobId, array &$tokenIds): void
    {
        $tokenIdsChunk = array_chunk($tokenIds, 5000);
        $tokenIdsCount = count($tokenIds);

        foreach ($tokenIdsChunk as $chunk) {
            $this->app['diag']->trackTokenisationEvent(
                EventCode::ASYNC_TOKENISATION_ADMIN_CONSENT_COLLECTION_AND_TOKENISATION_TRIGGER,
                [
                    'merchant_id' => $merchantId,
                    'token_id_list' => $chunk,
                    'token_id_count' => count($chunk),
                    'total_token_id_count' => $tokenIdsCount,
                    'async_tokenisation_job_id' => $asyncTokenisationJobId,
                ]
            );
        }
    }

    public function globalSavedCardAsyncTokenisation(array $input): array
    {
        (new Validator())->validateInput('validate_global_saved_card_async_tokenisation', $input);

        try
        {
            $asyncTokenisationJobId = UniqueIdEntity::generateUniqueId();

            $this->app['diag']->trackTokenisationEvent(EventCode::ASYNC_TOKENISATION_JOB_INITIATED, [
                'merchant_id_count'         => 1,
                'merchant_id_list'          => [Merchant\Account::SHARED_ACCOUNT],
                'async_tokenization_job_id' => $asyncTokenisationJobId,
            ]);

            $this->trace->info(TraceCode::ASYNC_GLOBAL_TOKENISATION_REQUEST, [
                'async_tokenization_job_id' => $asyncTokenisationJobId,
            ]);

            $batchSize = $input['batch_size'] ?? Token\Entity::GLOBAL_MERCHANT_ASYNC_TOKENISATION_QUERY_LIMIT;

            MerchantAsyncTokenisationJob::dispatch($this->mode, Merchant\Account::SHARED_ACCOUNT, $asyncTokenisationJobId, $batchSize);

            $this->trace->info(TraceCode::ASYNC_GLOBAL_TOKENISATION_DISPATCH_SUCCESS, [
                'async_tokenization_job_id' => $asyncTokenisationJobId,
            ]);

            return ['success' => true];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ASYNC_GLOBAL_TOKENISATION_ERROR
            );

            return ['success' => false];
        }
    }

    public function globalCustomerLocalSavedCardAsyncTokenisation(array $input): array
    {
        (new Validator())->validateInput('validate_global_customer_local_saved_card_async_tokenisation', $input);

        $asyncTokenisationJobId = UniqueIdEntity::generateUniqueId();

        try
        {
            $this->trace->info(TraceCode::ASYNC_GLOBAL_CUSTOMER_LOCAL_TOKENISATION_REQUEST, [
                'asyncTokenizationJobId' => $asyncTokenisationJobId,
                'input'                  => $input,
            ]);

            $batchSize = $input['batch_size'] ?? Constants::GLOBAL_CUSTOMER_LOCAL_ASYNC_TOKENISATION_QUERY_LIMIT;

            $lastDispatchedTokenId = Cache::get(Constants::LAST_DISPATCHED_GLOBAL_CUSTOMER_LOCAL_TOKEN_CACHE_KEY, '');

            $supportedNetworks = Card\Network::getFullNames(Card\Network::NETWORKS_SUPPORTING_TOKEN_PROVISIONING);

            $startTime = millitime();

            $tokensData = $this->repo->token->fetchConsentReceivedGlobalCustomerLocalTokensDataFromDataLake($supportedNetworks, $lastDispatchedTokenId, $batchSize);

            $tokensCount = count($tokensData);

            $tokenIds = $this->getValidTokenIds($tokensData);

            $this->trace->info(TraceCode::ASYNC_GLOBAL_CUSTOMER_LOCAL_TOKENISATION_FETCH_SUCCESS, [
                'fetchedTokensCount' => $tokensCount,
                'validTokensCount'   => count($tokenIds),
                'offset'             => $lastDispatchedTokenId,
            ]);

            if ($tokensCount === 0)
            {
                $this->trace->warning(
                    TraceCode::ASYNC_GLOBAL_CUSTOMER_LOCAL_TOKENISATION_ERROR,
                    ['reason' => 'No global customer local tokens found for tokenisation.']
                );

                return ['success' => true];
            }

            $endTime = millitime();

            $this->core->pushTokenIdsToQueueForTokenisation($tokenIds, $asyncTokenisationJobId);

            $endQueuingTime = millitime();

            $offset = $lastDispatchedTokenId;

            $lastDispatchedTokenId = $tokensData[$tokensCount - 1][TokenEntity::ID];

            Cache::put(
                Constants::LAST_DISPATCHED_GLOBAL_CUSTOMER_LOCAL_TOKEN_CACHE_KEY,
                $lastDispatchedTokenId,
                Constants::LAST_DISPATCHED_GLOBAL_CUSTOMER_LOCAL_TOKEN_CACHE_TTL
            );


            $this->trace->info(TraceCode::ASYNC_GLOBAL_CUSTOMER_LOCAL_TOKENISATION_DISPATCH_SUCCESS, [
                'fetchedTokenIdCount'    => $tokensCount,
                'tokensPushedIntoQueue'  => count($tokenIds),
                'asyncTokenizationJobId' => $asyncTokenisationJobId,
                'queryTime'              => $endTime - $startTime,
                'queuingTime'            => $endQueuingTime - $endTime,
                'offset'                 => $offset,
                'lastDispatchedTokenId'  => $lastDispatchedTokenId,
            ]);

            app('diag')->trackTokenisationEvent(
                EVENTCODE::ASYNC_TOKENISATION_TOKENISATION_GLOBAL_CUSTOMER_LOCAL_TOKENS_PUSHED_TO_QUEUE,
                [
                    'token_id_count'            => count($tokenIds),
                    'async_tokenization_job_id' => $asyncTokenisationJobId,
                ]
            );

            return ['success' => true];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ASYNC_GLOBAL_CUSTOMER_LOCAL_TOKENISATION_ERROR
            );

            app('diag')->trackTokenisationEvent(
                EVENTCODE::ASYNC_TOKENISATION_TOKENISATION_GLOBAL_CUSTOMER_LOCAL_TOKENS_FAILED_WHILE_PUSHING_TO_QUEUE,
                [
                    'async_tokenization_job_id' => $asyncTokenisationJobId,
                    'error_details'          => json_encode(
                        [
                            'message' => $e->getMessage(),
                            'code'    => $e->getCode(),
                        ]
                    ),
                ]
            );

            return ['success' => false];
        }
    }

    public function bulkCreateLocalTokensFromConsents(array $input): array
    {
        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id, null);

        $bulkRequestUniqueId = UniqueIdEntity::generateUniqueId();

        $this->trace->info(TraceCode::BULK_CREATE_LOCAL_TOKENS_FROM_CONSENT_REQUEST, [
            'fileId'              => $batchId,
            'bulkRequestUniqueId' => $bulkRequestUniqueId,
            'input'               => $input,
        ]);

        app('diag')->trackTokenisationEvent(
            EVENTCODE::ASYNC_TOKENISATION_CREATE_GLOBAL_CUSTOMER_LOCAL_TOKENS_INITIATED,
            [
                'token_id_count'         => count($input),
                'bulk_request_unique_id' => $bulkRequestUniqueId,
                'file_id'                => $batchId,
            ]
        );

        $result = $this->core->bulkCreateLocalTokensFromConsents($input, $bulkRequestUniqueId, $batchId);

        return $result->toArrayWithItems();
    }

    /**
     * @param $token
     * @param $input
     * @param bool $isSptToken
     */
    protected function addAdditionalInputParamsIfPresent($token, &$input, $isSptToken, $internalServiceRequest)
    {
        if (isset($token) === false)
        {
            return;
        }

        $input += [
            'internal_service_request' => $internalServiceRequest,
            'spt_token'                => $isSptToken
        ];

        if (empty($this->merchant) === false)
        {
            $input['merchant'] =
            [
                'id' =>  $this->merchant->getMerchantId(),
            ];
        }

        if ($isSptToken)
        {
            return;
        }

        if ((isset($token->card)) && (!empty($token->card->getIin())))
        {
            $card = $token->card;

            $input['card_data'] = [
                'iin'      => $card->getIin(),
                'token_iin'     => $card->getTokenIin(),
                'payment_account_reference' => $card->getGlobalFingerPrint(),
            ];

            $iin = $this->repo->card->retrieveIinDetails($token->card->getIin());

            if (isset($iin) === false)
            {
                return;
            }

            $iinInfo = [
                'issuer' => $iin->getIssuer(),
                'network' => $iin->getNetwork(),
                'category' => $iin->getCategory(),
                'type' => $iin->getType(),
                'country' => $iin->getCountry(),
            ];

            $input['iin'] = $iinInfo;
        }
    }

    public function migrateVaultTokenViaBatch($input)
    {
        $this->trace->info(TraceCode::VAULT_MIGRATE_TOKEN_NAMESPACE_BATCH_SERVICE_REQUEST, ['input' => $input]);

        $response = new Base\PublicCollection;

        foreach($input as $row)
        {
            $result = [
                  self::IDEMPOTENCY_KEY         => $row[self::IDEMPOTENCY_KEY],
                  self::BATCH_SUCCESS           => true,
                  self::BATCH_HTTP_STATUS_CODE  => 200
            ];

            unset($row[self::IDEMPOTENCY_KEY]);

            $result = array_merge($result, $row);

            try
            {
                $tokenInput = new Base\PublicCollection;

                $row[Header::VAULT_MIGRATE_TOKEN_NAMESPACE_EXISTING_NAMESPACE] = (int)$row[Header::VAULT_MIGRATE_TOKEN_NAMESPACE_EXISTING_NAMESPACE];
                $row[Header::VAULT_MIGRATE_TOKEN_NAMESPACE_BU_NAMESPACE]       = (int)$row[Header::VAULT_MIGRATE_TOKEN_NAMESPACE_BU_NAMESPACE];

                $tokenInput->add($row);

                $data = $this->app['card.cardVault']->migrateVaultTokenNamespace($tokenInput);

                $result[Header::VAULT_MIGRATE_TOKEN_NAMESPACE_MIGRATED_TOKEN_ID] = $data['tokens'][0]['migrated_token_id'] ?? null;
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::VAULT_MIGRATE_TOKEN_BULK_ERROR
                );

                $exceptionData =  $e->getData();

                $result[self::BATCH_ERROR] = [
                    self::BATCH_ERROR_DESCRIPTION => $e->getMessage(),
                    self::BATCH_ERROR_CODE        =>  $exceptionData['error'] ?? $e->getCode()
                ];

                $result[self::BATCH_HTTP_STATUS_CODE] = 400;

                $result[self::BATCH_SUCCESS] = false;
            }

            $response->add($result);
        }

        $this->trace->info(TraceCode::VAULT_MIGRATE_TOKEN_NAMESPACE_BATCH_SERVICE_RESPONSE, ['response' => $response->toArrayWithItems()]);

        return $response->toArrayWithItems();
    }

    public function tokenHqChargeProcessingViaBatch ($input)
    {
        $this->trace->info(TraceCode::TOKEN_HQ_CHARGE_BATCH_SERVICE_REQUEST, ['input' => $input]);

        $response = new Base\PublicCollection;

        $batchId = Request::header(RequestHeader::X_Batch_Id);

        foreach($input as $row)
        {
            $result = [
                self::IDEMPOTENCY_KEY         => $row[self::IDEMPOTENCY_KEY],
                self::BATCH_SUCCESS           => true,
                self::BATCH_HTTP_STATUS_CODE  => 200
            ];

            $result = array_merge($result, $row);

            try
            {
                $rowInput = new Base\PublicCollection;

                $rowInput->add($row);

                $data = "to do"; // call core function for creating transaction and update balance

                $result[Header::TOKEN_HQ_FEES] = 'fees';

                $result[Header::TOKEN_HQ_TAX] = 'tax';

            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::TOKEN_HQ_CHARGE_BATCH_ERROR
                );

                $exceptionData =  $e->getData();

                $result[self::BATCH_ERROR] = [
                    self::BATCH_ERROR_DESCRIPTION => $e->getMessage(),
                    self::BATCH_ERROR_CODE        =>  $exceptionData['error'] ?? $e->getCode()
                ];

                $result[self::BATCH_HTTP_STATUS_CODE] = 400;

                $result[self::BATCH_SUCCESS] = false;
            }

            $response->add($result);
        }

        $this->trace->info(TraceCode::TOKEN_HQ_CHARGE_BATCH_SERVICE_RESPONSE, ['response' => $response->toArrayWithItems()]);

        return $response->toArrayWithItems();

    }

    public function tokenHqCron()
    {
        $file = $this->core->createCsvFileFromDataLake();

        $params = [
            'file'  => $file,
            'type'  => Constants::TOKEN_HQ_CHARGE,
        ];

        $batchResult = (new Batch\Core)->create($params, (new Merchant\Core())->get('100000Razorpay'));

        return $batchResult;
    }

    private function getValidTokenIds(array $tokensData): array
    {
        $validTokenIds = [];

        foreach ($tokensData as $tokenData) {
            $cardNetworkCode = Card\Network::getCode($tokenData[Card\Entity::NETWORK]);

            $onboardedNetworks = (new Terminal\Core())->getMerchantTokenisationOnboardedNetworks(
                $tokenData[TokenEntity::MERCHANT_ID]
            );

            if (in_array($cardNetworkCode, $onboardedNetworks, true)) {
                $validTokenIds[] = $tokenData[TokenEntity::ID];
            }
        }

        return $validTokenIds;
    }

    public function createTokenForRearch($input)
    {
        if (empty($input['payment_id']) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PAYMENT_ID
            );
        }

        $payment = $this->repo->payment->findOrFail($input['payment_id']);

        $card = $payment->card;

        $customer = $payment->customer;

        if ($payment->globalCustomer !== null)
        {
            $customer = $payment->globalCustomer;
        }

        if ($customer->isGlobal() === true)
        {
            $customer->merchant()->associate($payment->merchant);
        }

        $tokenCard = $card->replicate();

        $tokenCard->generateID();

        if (isset($tokenCard->message_type))
        {
            unset($tokenCard->message_type);
        }

        $this->repo->saveOrFail($tokenCard);

        $saveMethodInput = [
            Token\Entity::METHOD => $payment->getMethod(),
            Token\Entity::CARD_ID => $tokenCard->getId(),
        ];

        $token = null;

        if ($customer != null)
        {
            try
            {
                $token = (new Token\Core)->create($customer, $saveMethodInput, null, true);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);

                throw new Exception\BadRequestException(
                    ErrorCode::API_CUSTOMER_TOKEN_CREATION_ERROR);
            }
        }

        $core = (new Token\Core());

        $core->updateTokenStatus($token->getId(), Token\Constants::INITIATED);

        $customer->merchant()->associate($this->repo->merchant->getSharedAccount());

        $token->incrementUsedCount();

        $token->setUsedAt(Carbon::now(Timezone::IST)->getTimestamp());

        $token->setAcknowledgedAt(Carbon::now(Timezone::IST)->getTimestamp());

        $this->repo->saveOrFail($token);

        $asyncTokenisationJobId = "paymentmigrate";

        $this->trace->info(TraceCode::TRACE_TOKEN_DISPATCH_LOG, [
            'tokenid'     =>  $token->getId(),
            'async'       =>  $asyncTokenisationJobId,
            'paymentId'   => $payment->getId(),
            'newCard'     => $tokenCard,
            'card'        => $card,
            'token'       => $token
        ]);

        SavedCardTokenisationJob::dispatch($this->mode, $token->getId(), $asyncTokenisationJobId,  $payment->getId());

        return $token->toArrayPublic();
    }

    public function generateFetchMerchantsWithTokenMockResponse($input)
    {
        unset($input['card']);

        $response = [];

        if ($this->merchant->isFeatureEnabled(Feature\Constants::CARD_FINGERPRINTS) === true)
        {
            $response['account_ids'] = $input['account_ids'];
        }

        return $response;
    }
}
