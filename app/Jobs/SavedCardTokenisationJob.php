<?php

namespace RZP\Jobs;

use App;
use RZP\Diag\EventCode;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Card\Entity as CardEntity;
use RZP\Models\CardMandate;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Modules\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use Throwable;
use RZP\Models\Customer\Token\Metric;

class SavedCardTokenisationJob extends Job
{
    protected const RETRY_INTERVAL = 300;

    protected const MAX_RETRY_ATTEMPT = 1;

    protected $queueConfigKey = 'cardvault_migration';

    public $timeout = 300;

    protected $tokenId;

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

    public function __construct(string $mode, string $tokenId, string $asyncTokenisationJobId, $paymentId = null )
    {
        parent::__construct($mode);

        $this->tokenId = $tokenId;

        $this->paymentId = $paymentId;

        $this->asyncTokenisationJobId = $asyncTokenisationJobId;
    }

    public function init(): void
    {
        parent::init();

        $this->tokenCore = new Token\Core();
    }

    /**
     * Process queue request
     */
    public function handle(): void
    {
        parent::handle();

        try
        {
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

            $this->tokenCore->migrateToTokenizedCard($token, $cardInput, $payment, true, $this->asyncTokenisationJobId);

            // Notify to mandateHQ for successful tokenisation
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

            if ($this->asyncTokenisationJobId === "paymentmigrate") {

                $serviceProviderTokens = (new Token\Core)->fetchToken($token, true);

                unset($token['card']['iin']);
                unset($token['card']['expiry_month']);
                unset($token['card']['expiry_year']);
                unset($token['card']['name']);

                $eventPayload = [
                    ApiEventSubscriber::MAIN => $token,
                    ApiEventSubscriber::WITH => $serviceProviderTokens,
                ];

                $this->trace->info(TraceCode::RESPONSE,
                    [
                        "eventpayload" => $eventPayload
                    ]
                );

                app('events')->dispatch('api.token.service_provider.activated', $eventPayload);

            }

            $this->triggerEvent(EventCode::ASYNC_TOKENISATION_TOKEN_CREATION_SUCCESS, $card);

            $this->delete();
            $this->trace->info(TraceCode::DEBUG_LOGGING, [
                'checking if we are going till the function or failing before that in try'
            ]);
            (new Token\Metric())->pushMigrateMetrics($token,Metric::SUCCESS);
            $this->trace->info(TraceCode::DEBUG_LOGGING, [
                'checking if after the function call it is failing or it is going beyond this call as well in try'
            ]);

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

            $this->trace->info(TraceCode::DEBUG_LOGGING, [
                'checking if we are going till the function or failing before that in catch'
            ]);

            (new Token\Metric())->pushMigrateMetrics($token, Metric::FAILED, $e);

            $this->trace->info(TraceCode::DEBUG_LOGGING, [
                'checking if after the function call it is failing or it is going beyond this call as well in catch'
            ]);
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
}
