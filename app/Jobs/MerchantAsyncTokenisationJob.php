<?php

namespace RZP\Jobs;

use Illuminate\Support\Facades\Cache;
use RZP\Diag\EventCode;
use RZP\Models\Merchant\Account;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use Razorpay\Trace\Logger as Trace;
use Throwable;

class MerchantAsyncTokenisationJob extends Job
{
    /**
     * Cache key used to store the last processed token id which is used as an
     * offset to fetch next set of tokens.
     *
     * @var string
     */
    public const LAST_DISPATCHED_GLOBAL_TOKEN_CACHE_KEY = 'global_cards_tokenisation_last_dispatched_token_id';

    /** @var int Cache TTL of 30 days in seconds. */
    public const LAST_DISPATCHED_GLOBAL_TOKEN_CACHE_TTL = 30 * 24 * 60 * 60;

    protected $queueConfigKey = 'merchant_async_tokenisation';

    public $timeout = 2700;

    /**
     * @var Token\Core
     */
    protected $tokenCore;

    protected $merchantId;

    protected $asyncTokenisationJobId;

    protected $batchSize;

    protected $recurring;

    public function __construct(
        string $mode,
        string $merchantId,
        string $asyncTokenisationJobId,
        int $batchSize = Token\Entity::GLOBAL_MERCHANT_ASYNC_TOKENISATION_QUERY_LIMIT,
        bool $recurring = false
    ) {
        parent::__construct($mode);

        $this->merchantId = $merchantId;

        $this->asyncTokenisationJobId = $asyncTokenisationJobId;

        $this->batchSize = $batchSize;

        $this->recurring = $recurring;
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

            if ($merchantId === Account::SHARED_ACCOUNT)
            {
                $this->handleGlobalMerchant();

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
                $tokenIds = $this->tokenCore->fetchConsentReceivedLocalTokenIdsForTokenisation($merchantId, $offset, 0, $this->recurring);

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

    /**
     * Handle processing of global saved card tokens.
     */
    protected function handleGlobalMerchant(): void
    {
        try {
            $lastDispatchedTokenId = Cache::get(self::LAST_DISPATCHED_GLOBAL_TOKEN_CACHE_KEY, '');

            $globalTokens = $this->tokenCore->fetchConsentReceivedGlobalTokenIdsForTokenisation($lastDispatchedTokenId, $this->batchSize);

            $tokensCount = count($globalTokens);

            if ($tokensCount === 0) {
                $this->trace->warning(
                    TraceCode::MERCHANT_ASYNC_TOKENISATION_JOB_ERROR,
                    ['reason' => 'No tokens found for global shared merchant.']
                );

                return;
            }

            $this->logInfo(TraceCode::ASYNC_TOKENISATION_TOKEN_FETCH_SUCCESS, $tokensCount, $lastDispatchedTokenId);

            $this->tokenCore->pushTokenIdsToQueueForTokenisation($globalTokens, $this->asyncTokenisationJobId);

            $offset = $lastDispatchedTokenId;

            $lastDispatchedTokenId = $globalTokens[$tokensCount - 1];

            $this->triggerEvent(EventCode::ASYNC_TOKENISATION_MERCHANT_COMPLETED, [
                'total_tokens_dispatched' => $tokensCount,
            ]);

            Cache::put(
                self::LAST_DISPATCHED_GLOBAL_TOKEN_CACHE_KEY,
                $lastDispatchedTokenId,
                self::LAST_DISPATCHED_GLOBAL_TOKEN_CACHE_TTL
            );

            $this->logInfo(
                TraceCode::MERCHANT_ASYNC_TOKENISATION_JOB_SUCCESS,
                $tokensCount,
                $offset,
                ['last_dispatched_token_id' => $lastDispatchedTokenId]
            );
        } catch (Throwable $exception) {
            $this->trackAsyncTokenisationJobErrorEvent($exception, 0);

            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::MERCHANT_ASYNC_TOKENISATION_JOB_ERROR,
                [
                    'mode'       => $this->mode,
                    'merchantId' => $this->merchantId,
                ]
            );
        }
    }

    /**
     * @param string     $message
     * @param int        $tokensCount
     * @param int|string $offset
     * @param array      $customProperties
     */
    protected function logInfo(string $message, int $tokensCount, $offset, array $customProperties = []): void
    {
        $properties = [
            'mode'          => $this->mode,
            'merchantId'    => $this->merchantId,
            'tokensCount'   => $tokensCount,
            'offset'        => $offset,
        ];

        $properties = array_merge($properties, $customProperties);

        $this->trace->info($message, $properties);
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

        app('diag')->trackTokenisationEvent($eventData, $properties);
    }
}
