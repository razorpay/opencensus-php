<?php

namespace RZP\Jobs;

use App;
use RZP\Diag\EventCode;
use RZP\Models\Card\Entity as CardEntity;
use RZP\Models\Customer\Token;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use Throwable;

class SavedCardTokenisationJob extends Job
{
    protected const RETRY_INTERVAL = 300;

    protected const MAX_RETRY_ATTEMPT = 1;

    protected $queueConfigKey = 'cardvault_migration';

    public $timeout = 300;

    protected $tokenId;

    protected $merchantId;

    protected $asyncTokenisationJobId;

    /**
     * @var Token\Core
     */
    protected $tokenCore;

    public function __construct(string $mode, string $tokenId, string $asyncTokenisationJobId)
    {
        parent::__construct($mode);

        $this->tokenId = $tokenId;

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

            $card = $token->card;

            $this->triggerEvent(EventCode::ASYNC_TOKENISATION_TOKEN_CREATION_INITIATED, $card);

            $this->trace->info(TraceCode::SAVED_CARD_TOKENISATION_JOB_REQUEST, [
                'tokenId'                   => $this->tokenId,
                'merchantId'                => $this->merchantId,
                'async_tokenization_job_id' => $this->asyncTokenisationJobId,
            ]);


            if($this->tokenCore->checkIfTokenisationApplicable($token) === false)
            {
                $this->traceTokenisationNotApplicable();
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
            $this->tokenCore->migrateToTokenizedCard($token, $cardInput);

            $this->trace->info(TraceCode::SAVED_CARD_TOKENISATION_JOB_SUCCESS, [
                'tokenId' => $this->tokenId,
                'merchantId'    => $this->merchantId,
                'timeTaken'     => millitime() - $startTime,
                'network'       => $card->getNetwork(),
            ]);

            $this->triggerEvent(EventCode::ASYNC_TOKENISATION_TOKEN_CREATION_SUCCESS, $card);

            $this->delete();

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
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry(): void
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            // @TODO: Add analytics event later to show job failure
            $this->trace->error(TraceCode::SAVED_CARD_TOKENISATION_JOB_FAILED, [
                'tokenId'       => $this->tokenId,
                'merchantId'    => $this->merchantId,
                'jobAttempts'   => $this->attempts(),
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }

    protected function traceTokenisationNotApplicable(): void
    {
        $this->trace->info(TraceCode::SAVED_CARD_TOKENISATION_JOB_TOKEN_NOT_APPLICABLE, [
            'tokenId'       => $this->tokenId,
            'merchantId'    => $this->merchantId,
        ]);
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
        ];

        $properties = array_merge($properties, $customProperties);

        app('diag')->trackAsyncTokenisationEvent($eventData, $properties);
    }
}
