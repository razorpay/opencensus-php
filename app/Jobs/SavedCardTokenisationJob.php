<?php

namespace RZP\Jobs;

use App;
use RZP\Constants;
use RZP\Base\RuntimeManager;
use RZP\Diag\EventCode;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Card\Entity as CardEntity;
use RZP\Models\CardMandate;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\WebhookV2\Stork;
use RZP\Modules\Base;
use RZP\Models\Event;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use Throwable;
use RZP\Models\Customer\Token\Metric;

class SavedCardTokenisationJob extends Job
{
    protected const RETRY_INTERVAL = 300;

    protected const MAX_RETRY_ATTEMPT = 1;

    protected $queueConfigKey = 'cardvault_migration';

    protected $storkProduct = 'primary';

    public $timeout = 300;

    protected $tokenId;

    protected $mainEntity;

    protected $paymentId;

    protected $merchantId;

    protected $asyncTokenisationJobId;

    protected $isGlobalCustomerLocalToken;

    protected const TOKENISATION_NO_RETRY_ERROR_CODES = [
        'BAD_REQUEST_CARD_INVALID',
        'BAD_REQUEST_CARD_NOT_ELIGIBLE',
        'BAD_REQUEST_INVALID_CARD_EXPIRY',
        'BAD_REQUEST_CARD_NOT_ALLOWED',
        'BAD_REQUEST_CARD_NOT_ALLOWED_BY_BANK',
        'BAD_REQUEST_CARD_NOT_ELIGIBLE_FOR_TOKENISATION',
        'BAD_REQUEST_INVALID_CARD_DETAILS',
        'BAD_REQUEST_CARD_DECLINED',
    ];

    /**
     * @var Token\Core
     */
    protected $tokenCore;

    protected $callbackData;
    private string $event;

    public function __construct(string $mode, string $tokenId, string $asyncTokenisationJobId, $paymentId = null, $callbackData = null)
    {
        parent::__construct($mode);

        $this->tokenId = $tokenId;

        $this->paymentId = $paymentId;

        $this->asyncTokenisationJobId = $asyncTokenisationJobId;
        $this->callbackData = $callbackData;
    }



    public function init(): void
    {
        parent::init();

        $this->tokenCore = new Token\Core();
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('4096M');

        RuntimeManager::setTimeLimit(300);
    }

    /**
     * Process queue request
     */
    public function handle(): void
    {
        parent::handle();

        try
        {

            RuntimeManager::setMemoryLimit('4096M');

            RuntimeManager::setTimeLimit(7200);

            RuntimeManager::setMaxExecTime(7200);

            /** @var Token\Entity $token */
            $token = $this->repoManager->token->findOrFailPublic($this->tokenId);

            $this->merchantId = $token->getMerchantId();

            $this->isGlobalCustomerLocalToken = $token->isLocalTokenOnGlobalCustomer();

            $card = $token->card;

           $this->triggerEvent(EventCode::ASYNC_TOKENISATION_TOKEN_CREATION_INITIATED, $card);

            $this->trace->info(TraceCode::SAVED_CARD_TOKENISATION_JOB_REQUEST, [
                'tokenId'                   => $this->tokenId,
                'merchantId'                => $this->merchantId,
                'async_tokenization_job_id' => $this->asyncTokenisationJobId,
                'is_global_customer_local_token' => $this->isGlobalCustomerLocalToken,
                'tokenpan' => $this->callbackData,
            ]);


            if($this->tokenCore->checkIfTokenisationApplicable($token) === false)
            {
                $this->traceTokenisationNotApplicable($card);
                $this->delete();
                return;
            }

            $cardInput = $this->tokenCore->buildCardInputForTokenisation($card);

            $startTime = millitime();

            /**
             * this takes card details from existing token, card entity
             * calls vault service for tokenising the card with vault token,expiry month,year,cvv, iin props, features,etc
             * on receiving the response, new card entity is created
             * existing token entity is associated to new card entity
             */

            $payment = null ;

            if(isset($this->paymentId))
            {
                $payment = $this->repoManager->payment->findOrFail($this->paymentId);
            }

            if($this->asyncTokenisationJobId === 'pushtokenmigrate'){
                $cardInput['via_push_provisioning'] = true;
            }

            [$tokenPanVaultToken, $tokenNumber , $cryptogramValue, $serviceProviderTokens] = $this->tokenCore->migrateToTokenizedCard($token, $cardInput, $payment, true, $this->asyncTokenisationJobId,$this->callbackData);

            $tokenIIN = null;
            if(isset($tokenNumber)) {
                $tokenIIN = substr($tokenNumber,0,9);
            }


//            // Notify to mandateHQ for successful tokenisation
            if($token->isRecurring() === true and $token->getCardMandateId() !== null)
            {
                try
                {
                    $this->notifyToMandateHubForSuccessfulTokenisation($token);
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::CRITICAL,
                        TraceCode::FAILED_REPORTING_TO_MANDATEHUB_AFTER_RECURRING_TOKENISATION,
                        ['tokenId' => $token->getId()]);
                }
            }

            $this->trace->info(TraceCode::SAVED_CARD_TOKENISATION_JOB_SUCCESS, [
                'tokenId'       => $this->tokenId,
                'merchantId'    => $this->merchantId,
                'timeTaken'     => millitime() - $startTime,
                'network'       => $card->getNetwork(),
                'attempt'       => $this->attempts(),
                'is_global_customer_local_token' => $this->isGlobalCustomerLocalToken,
                'asyncTokenisationJobId'    => $this->asyncTokenisationJobId,
            ]);



            if ($this->asyncTokenisationJobId === "paymentmigrate" || $this->asyncTokenisationJobId === 'pushtokenmigrate') {
                try {
                    $serviceProviderTokens = (new Token\Core)->fetchToken($token, true);
                } catch(Throwable $e) {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::SAVED_CARD_FETCH_TOKEN_ERROR
                    );
                    return;
                }



                unset($token['card']['iin']);
                unset($token['card']['expiry_month']);
                unset($token['card']['expiry_year']);
                unset($token['card']['name']);


                $tokenArray = $token->attributesToArray();

                $tokenEntity = new Token\Entity();

                $tokenEntity->forceFill($tokenArray);

                $eventPayload = [
                    ApiEventSubscriber::MAIN => $tokenEntity,
                    ApiEventSubscriber::WITH => $serviceProviderTokens,
                    'card' => $card,
                    'tokenIIN' => $tokenIIN
                ];

                try {
                    // New Stork Request
                    $this->dispatchWebhookToStork('api.token.service_provider.activated', $eventPayload);
                    // Old Stork event
                    // app('events')->dispatchNow('api.token.service_provider.activated', $eventPayload);
                } catch(Throwable $e) {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::STORK_DISPATCH_ERROR
                    );
                }
            }

            $this->triggerEvent(EventCode::ASYNC_TOKENISATION_TOKEN_CREATION_SUCCESS, $card);

            $this->delete();
       //     (new Token\Metric())->pushMigrateMetrics($token,Metric::SUCCESS);
            return;
        }
        catch (Throwable $e)
        {
           $this->trackFailedTokenCreationEvent($e, $card ?? new CardEntity());

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SAVED_CARD_TOKENISATION_JOB_ERROR,
                [
                    'tokenId'       => $this->tokenId,
                    'merchantId'    => $this->merchantId,
                    'attempt'       => $this->attempts(),
                    'is_global_customer_local_token' => $this->isGlobalCustomerLocalToken,
                    'asyncTokenisationJobId'    => $this->asyncTokenisationJobId,
                ]
            );

            $this->checkRetry($e);

           //(new Token\Metric())->pushMigrateMetrics($token, Metric::FAILED, $e);
        }
    }

    protected function checkRetry(Throwable $e): void
    {
        if (($this->attempts() > self::MAX_RETRY_ATTEMPT) or
            (in_array($e->getCode(), self::TOKENISATION_NO_RETRY_ERROR_CODES, true)))
        {
            $this->trace->error(TraceCode::SAVED_CARD_TOKENISATION_JOB_FAILED, [
                'tokenId'       => $this->tokenId,
                'merchantId'    => $this->merchantId,
                'jobAttempts'   => $this->attempts(),
                'is_global_customer_local_token' => $this->isGlobalCustomerLocalToken,
                'asyncTokenisationJobId' => $this->asyncTokenisationJobId,
            ]);

            $updateData[Token\Entity::STATUS] = Token\Constants::FAILED;

            $updateData[Token\Entity::INTERNAL_ERROR_CODE] = $e->getCode();

            $updateData[Token\Entity::ERROR_DESCRIPTION] = $e->getMessage();

            $rowsAffected = (new Token\Repository)->updateById($this->tokenId, $updateData);

            $this->trace->info(TraceCode::UPDATE_TOKEN_STATUS_DURING_MIGRATION,
                [
                    "rows" => $rowsAffected
                ]
            );

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }

    protected function traceTokenisationNotApplicable(CardEntity $card): void
    {
        $this->trace->info(TraceCode::SAVED_CARD_TOKENISATION_JOB_TOKEN_NOT_APPLICABLE, [
            'tokenId'       => $this->tokenId,
            'merchantId'    => $this->merchantId,
            'is_global_customer_local_token' => $this->isGlobalCustomerLocalToken,
            'asyncTokenisationJobId'    => $this->asyncTokenisationJobId,
        ]);

        $this->triggerEvent(EventCode::ASYNC_TOKENISATION_TOKEN_CREATION_NOT_APPLICABLE, $card);
    }

    protected function trackFailedTokenCreationEvent(Throwable $e, CardEntity $card): void
    {
        $error_details = [
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
        ];

        $properties = [
            'error_detail' => json_encode($error_details),
        ];

        $this->triggerEvent(EventCode::ASYNC_TOKENISATION_TOKEN_CREATION_FAILED, $card, $properties);
    }

    protected function triggerEvent(array $eventData, CardEntity $card, array $customProperties = []): void
    {
        $properties = [
            'token_id'                  => $this->tokenId,
            'merchant_id'               => $this->merchantId,
            'card_network'              => $card->getNetwork(),
            'card_issuer'               => $card->getIssuer(),
            'async_tokenization_job_id' => $this->asyncTokenisationJobId,
            'attempt'                   => $this->attempts(),
            'is_global_customer_local_token' => $this->isGlobalCustomerLocalToken,
        ];

        $properties = array_merge($properties, $customProperties);

        app('diag')->trackTokenisationEvent($eventData, $properties);
    }

    protected function notifyToMandateHubForSuccessfulTokenisation(Token\Entity $token)
    {
        $hub = $token->cardMandate->getMandateHub();

        $app = App::getFacadeRoot();

        $blockReportingToHubAfterAsyncRecurringTokenisationExperiment = $app['razorx']->getTreatment(
            strtolower($hub),
            RazorxTreatment::BLOCK_HUB_REPORT_AFTER_ASYNC_RECURRING_TOKENISATION,
            $this->mode);

        if((strtolower($blockReportingToHubAfterAsyncRecurringTokenisationExperiment) === 'on'))
        {
            $this->trace->info(TraceCode::SKIPPED_REPORTING_TO_MANDATEHUB_AFTER_RECURRING_TOKENISATION, [
                'tokenId'       => $this->tokenId ?? '',
                'hub'           => $hub ?? ''
            ]);

            return;
        }

        $tokenInput = $token->card->buildTokenisedTokenForMandateHub();

        (new CardMandate\Core)->updateTokenisedCardTokenInMandate($token->cardMandate, $tokenInput);
    }


    protected function dispatchWebhookToStork($event, $payload)
    {
        try{
            $event = substr($event, 4);

            $this->event = $event;

            $this->mainEntity  = $payload[ApiEventSubscriber::MAIN];
            $payload = $this->getTokenServiceProviderPayload($payload);

            $this->dispatchEventToStorkManual($payload);
        } catch (Throwable $e){
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::DEBUG_ERROR,
                [
                    'message' => 'Error',
                ]
            );
        }

    }


    public function dispatchEventToStorkManual(array $payload, string $ownerType = Constants\Entity::MERCHANT)
    {
        $event = $this->createEventEntity($payload);
        (new Stork($this->getMode(), $this->storkProduct))->processEventSafe($event, $ownerType);
    }

    protected function createEventEntity(array $payload): Event\Entity
    {
        $eventFired = $this->event;
        $entity     = $this->mainEntity;
        $merchant   = (new ApiEventSubscriber())->getMerchantFromEntityPublic($entity);
        //
        // Send the signed account id of the merchant associated with the entity, along with the payload
        // In case of settlements, $entity->merchant is the the merchant to whom the settlement is processed
        //
        $listeningMerchant = (new ApiEventSubscriber())->getMerchantPublic($entity);
        $signedAccountId = Merchant\Account\Entity::getSignedId($listeningMerchant->getId());

        $attributes = array(
            Event\Entity::EVENT      => $eventFired,
            Event\Entity::CONTEXT    => [],
            //
            // The same event may or may not contain some entities, based on the state.
            // For example, if subscription.pending is fired on an auth failure,
            // the payload will contain only subscription entity not contain `payment` entity.
            // If it's fired on capture failure, it'll contain both subscription and payment
            // entity. For this reason, we cannot have a static list of contains array.
            //
            Event\Entity::ACCOUNT_ID => $signedAccountId,
            Event\Entity::CONTAINS   => array_keys($payload),
            Event\Entity::CREATED_AT => $entity->getUpdatedAt(),
        );

        $event = new Event\Entity($attributes);
        $event->generateId();

        $event->setPayload($payload);

        $event->merchant()->associate($merchant);
        return $event;
    }

    protected function getTokenServiceProviderPayload($payload): array
    {
        $token = $payload[ApiEventSubscriber::MAIN];
        $serviceProviderTokens = $payload[ApiEventSubscriber::WITH];
        $card = $payload['card'];
        $tokenIIN = $payload['tokenIIN'];

        $publicToken = $token->toArrayPublicTokenizedCardManualDisapatch($token, $serviceProviderTokens, $card, $tokenIIN);


        $customerId = $token->getCustomerId();

        $customer = $this->repoManager->customer->findById($customerId);

        $partialPayload['token'] = [
            'entity' => $publicToken,
        ];

        $partialPayload['service_provider_token'] = [
            'entity' => $serviceProviderTokens,
        ];

        if($token->getSource() === Token\Constants::ISSUER && isset($customer) === true)
        {
            $partialPayload['customer'] = [
                'entity' => [
                    'id'  => $customer->getId(),
                    'email'   => $customer->getEmail(),
                    'contact' => $customer->getContact()
                ],
            ];
        }

        return $partialPayload;
    }
}
