<?php

namespace RZP\Jobs;

use RZP\Models\Merchant\Account;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use Razorpay\Trace\Logger as Trace;

class MerchantAsyncTokenisationJob extends Job
{
    protected $queueConfigKey = 'merchant_async_tokenisation';

    public $timeout = 2700;

    /**
     * @var Token\Core
     */
    protected $tokenCore;

    protected $merchantId;

    public function __construct(string $mode, string $merchantId)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;
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
            $merchantId = $this->merchantId;

            $this->trace->info(TraceCode::MERCHANT_ASYNC_TOKENISATION_JOB_REQUEST, [
                'mode'          => $this->mode,
                'merchantId'    => $merchantId,
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

                $this->tokenCore->pushTokenIdsToQueueForTokenisation($tokenIds);

                $this->trace->info(TraceCode::ASYNC_TOKENISATION_TOKEN_DISPATCH_SUCCESS, [
                    'merchantId'    => $this->merchantId,
                    'offset'        => $offset,
                    'tokensCount'   => count($tokenIds),
                ]);

                $offset += $queryLimit;
                $tokensCount = count($tokenIds);
            }

            $this->trace->info(TraceCode::MERCHANT_ASYNC_TOKENISATION_JOB_SUCCESS, [
                'mode'          => $this->mode,
                'merchantId'    => $merchantId,
            ]);
        }
        catch (\Throwable $e)
        {
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
}
