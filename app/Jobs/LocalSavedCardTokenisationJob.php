<?php

namespace RZP\Jobs;

use RZP\Models\Customer\Token;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class LocalSavedCardTokenisationJob extends Job
{
    protected const RETRY_INTERVAL = 300;

    protected const MAX_RETRY_ATTEMPT = 1;

    protected $queueConfigKey = 'cardvault_migration';

    public $timeout = 300;

    protected $tokenId;

    protected $merchantId;

    /**
     * @var Token\Core
     */
    protected $tokenCore;

    public function __construct($mode, $tokenId)
    {
        parent::__construct($mode);

        $this->tokenId = $tokenId;
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
            $token = $this->repoManager->token->findOrFailPublic($this->tokenId);

            $this->merchantId = $token->getMerchantId();

            $this->trace->info(TraceCode::LOCAL_TOKENISATION_JOB_REQUEST, [
                'tokenId'       => $this->tokenId,
                'merchantId'    => $this->merchantId,
            ]);

            if($token->isLocal() === false)
            {
                $this->traceTokenisationNotApplicable();
                $this->delete();
                return;
            }

            if($this->tokenCore->checkIfTokenisationApplicable($token) === false)
            {
                $this->traceTokenisationNotApplicable();
                $this->delete();
                return;
            }

            $card = $token->card;

            $cardInput = $this->tokenCore->buildCardInputForTokenisation($card);

            /**
             * this takes card details from existing token, card entity
             * calls vault service for tokenising the card with vault token,expiry month,year,cvv, iin props, features,etc
             * on receiving the response, new card entity is created
             * existing token entity is associated to new card entity
             */
            $this->tokenCore->migrateToTokenizedCard($token, $cardInput);

            $this->trace->info(TraceCode::LOCAL_TOKENISATION_JOB_SUCCESS, [
                'tokenId' => $this->tokenId,
                'merchantId'    => $this->merchantId,
            ]);

            $this->delete();

            return;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LOCAL_TOKENISATION_JOB_ERROR,
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
            $this->trace->error(TraceCode::LOCAL_TOKENISATION_JOB_FAILED, [
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
        $this->trace->info(TraceCode::LOCAL_TOKENISATION_JOB_TOKEN_NOT_APPLICABLE, [
            'tokenId'       => $this->tokenId,
            'merchantId'    => $this->merchantId,
        ]);
    }
}
