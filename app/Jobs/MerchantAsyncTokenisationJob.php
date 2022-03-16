<?php

namespace RZP\Jobs;

use App;
use RZP\Diag\EventCode;
use RZP\Models\Merchant\Account;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use Razorpay\Trace\Logger as Trace;
use Throwable;

class MerchantAsyncTokenisationJob extends Job
{
    protected $queueConfigKey = 'merchant_async_tokenisation';

    public $timeout = 2700;

    /**
     * @var Token\Core
     */
    protected $tokenCore;

    protected $merchantId;

    protected $asyncTokenisationJobId;

    public function __construct(string $mode, string $merchantId, string $asyncTokenisationJobId)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;

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

        $totalTokensDispatched = 0;

        try
        {
            $merchantId = $this->merchantId;

            $this->triggerEvent(EventCode::ASYNC_TOKENISATION_MERCHANT_PICKED);

            $this->trace->info(TraceCode::MERCHANT_ASYNC_TOKENISATION_JOB_REQUEST, [
                'mode'                      => $this->mode,
                'merchantId'                => $merchantId,
                'async_tokenization_job_id' => $this->asyncTokenisationJobId,
            ]);

            if($merchantId === Account::SHARED_ACCOUNT)
            {
                $this->delete();
                return;
            }

            $queryLimit = Token\Entity::MERCHANT_ASYNC_TOKENISATION_QUERY_LIMIT;
            $offset = 0;
            $tokensCount = $queryLimit;

            /**
             * The following loop
             * 1. fetches the token ids for tokenisation with a query limit of 1 lakh tokens
             * 2. if the query response is less than 1 lakh tokens loop is completed
             * 3. else offset is set to 1 lakh and next 1 lakh tokens are fetched until all tokens are fetched
             */
            while ($tokensCount === $queryLimit)
            {
                $tokenIds = $this->tokenCore->fetchConsentReceivedTokenIdsForTokenisation($merchantId, $offset);

                $this->trace->info(TraceCode::ASYNC_TOKENISATION_TOKEN_FETCH_SUCCESS, [
                    'merchantId'    => $this->merchantId,
                    'offset'        => $offset,
                    'tokensCount'   => count($tokenIds),
                ]);

                $this->tokenCore->pushTokenIdsToQueueForTokenisation($tokenIds, $this->asyncTokenisationJobId);

                $this->trace->info(TraceCode::ASYNC_TOKENISATION_TOKEN_DISPATCH_SUCCESS, [
                    'merchantId'    => $this->merchantId,
                    'offset'        => $offset,
                    'tokensCount'   => count($tokenIds),
                ]);

                $offset += $queryLimit;
                $tokensCount = count($tokenIds);
                $totalTokensDispatched += $tokensCount;
            }

            $this->triggerEvent(EventCode::ASYNC_TOKENISATION_MERCHANT_COMPLETED, [
                'total_tokens_dispatched' => $totalTokensDispatched,
            ]);

            $this->trace->info(TraceCode::MERCHANT_ASYNC_TOKENISATION_JOB_SUCCESS, [
                'mode'          => $this->mode,
                'merchantId'    => $merchantId,
                'total'         => $totalTokensDispatched
            ]);
        }
        catch (Throwable $e)
        {
            $this->trackAsyncTokenisationJobErrorEvent($e, $totalTokensDispatched);

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MERCHANT_ASYNC_TOKENISATION_JOB_ERROR,
                [
                    'mode'       => $this->mode,
                    'merchantId' => $this->merchantId,
                ]
            );
        }

        $this->delete();
    }

    protected function trackAsyncTokenisationJobErrorEvent(Throwable $e, int $totalTokensDispatched): void
    {
        $error_details = [
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
        ];

        $this->triggerEvent(EventCode::ASYNC_TOKENISATION_MERCHANT_FAILED, [
            'total_tokens_dispatched' => $totalTokensDispatched,
            'error_detail'            => json_encode($error_details),
        ]);
    }

    protected function triggerEvent(array $eventData, array $customProperties = []): void
    {
        $properties = [
            'merchant_id'               => $this->merchantId,
            'async_tokenization_job_id' => $this->asyncTokenisationJobId,
        ];

        $properties = array_merge($properties, $customProperties);

        app('diag')->trackAsyncTokenisationEvent($eventData, $properties);
    }
}
