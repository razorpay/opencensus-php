<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Constants\HyperTrace;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankingAccountStatement as BAS;

class IciciBankingAccountStatement extends Job
{
    //TODO: Move these constants to config
    const MAX_RETRY_ATTEMPT = 7;

    const MAX_RETRY_DELAY = 120;

    // statement fetch fixed window rate limit related constants.
    const STATEMENT_FETCH_FIXED_WINDOW_CONFIG_KEY_PREFIX = "banking_account_statement_";

    const DEFAULT_RATE_LIMIT = 10;

    const DEFAULT_FIXED_WINDOW_LENGTH = 5;

    const MAX_RETRY_COUNT_FOR_PROCESSING_ACCOUNT_STATEMENT = 2;

    const DEFAULT_BANKING_ACCOUNT_STATEMENT_PROCESS_DELAY = 60;

    /**
     * @var string
     */
    protected $queueConfigKey = 'icici_banking_account_statement_fetch';

    /**
     * @var array
     */
    protected $params;

    /**
     * Default timeout value for a job is 60s. Changing it to 300s
     * as account statement process takes 1-2 mins to complete.
     * @var integer
     */
    public $timeout = 1800;

    // maintaining attemptNumber to keep track of number of attempts which are not rate limited. $this->attempts() will
    // include extra attempts made because of rate limiter.
    protected $attemptNumber;

    /**
     * @param string $mode
     * @param array  $params
     *      1. channel (bank channel)
     *      2. Account number (account for statement fetch)
     */
    public function __construct(string $mode, array $params)
    {
        $this->attemptNumber = array_pull($params, 'attempt_number');

        $this->params = $params;

        parent::__construct($mode);
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $BASCore = new BAS\Core;

            $basDetails = $BASCore->getBasDetails($this->params['account_number'], $this->params['channel']);

            if (isset($basDetails) === false)
            {
                $this->trace->info(TraceCode::BAS_DETAILS_NOT_FOUND);

                $this->delete();

                return;
            }

            $this->params['merchant_id'] = $basDetails->getMerchantId();

            if ($BASCore->shouldBlockNon2faAndNonBaasMerchants($this->params) === true)
            {
                $this->delete();

                return;
            }

            $enableRateLimit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::ICICI_ENABLE_RATE_LIMIT_FLOW]);

            if ($enableRateLimit === 1)
            {
                list($passRateLimit, $rateLimitRequestNumber, $redisKeyName) = $this->checkRateLimit($this->params['channel']);
            }
            else
            {
                list($passRateLimit, $rateLimitRequestNumber, $redisKeyName) = [true, 0, ''];
            }

            if ($passRateLimit === false)
            {
                $this->trace->debug(
                    TraceCode::BANKING_ACCOUNT_STATEMENT_RATE_LIMITED,
                    [
                        BAS\Entity::CHANNEL            => $this->params['channel'],
                        BAS\Entity::MERCHANT_ID        => $BASCore->getBasDetails()->getMerchantId(),
                        BAS\Details\Entity::BALANCE_ID => $BASCore->getBasDetails()->getBalanceId(),
                        'rate_limit_request_number'    => $rateLimitRequestNumber,
                        'redis_key_name'               => $redisKeyName
                    ]);

                Tracer::startSpanWithAttributes(HyperTrace::BANKING_ACCOUNT_STATEMENT_RATE_LIMITED,
                                                [
                                                    BAS\Entity::CHANNEL            => $this->params['channel'],
                                                    'redis_key_name'               => $redisKeyName
                                                ]);

                $rateLimitReleaseDelay = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::ICICI_STATEMENT_FETCH_RATE_LIMIT_RELEASE_DELAY]);

                if (empty($rateLimitReleaseDelay) == true)
                {
                    $rateLimitReleaseDelay = 0;
                }

                $this->release($rateLimitReleaseDelay);

                return;
            }

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_INIT,
                [
                    BAS\Entity::CHANNEL            => $this->params['channel'],
                    BAS\Entity::ACCOUNT_NUMBER     => $this->params['account_number'],
                    BAS\Entity::MERCHANT_ID        => $BASCore->getBasDetails()->getMerchantId(),
                    BAS\Details\Entity::BALANCE_ID => $BASCore->getBasDetails()->getBalanceId(),
                    'rate_limit_request_number'    => $rateLimitRequestNumber,
                    'redis_key_name'               => $redisKeyName
                ]);

            Tracer::startSpanWithAttributes(HyperTrace::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_INIT,
                                            [
                                                BAS\Entity::CHANNEL => $this->params['channel'],
                                                'redis_key_name'    => $redisKeyName
                                            ]);

            $workerStartTime = Carbon::now()->getTimestamp();

            (new BAS\Core)->fetchAccountStatementV2($this->params);

            $workerEndTime = Carbon::now()->getTimestamp();

            $this->trace->info(TraceCode::BAS_FETCH_PROCESSED_BY_QUEUE,
                               [
                                   BAS\Entity::CHANNEL            => $this->params['channel'],
                                   BAS\Entity::ACCOUNT_NUMBER     => $this->params['account_number'],
                                   BAS\Entity::MERCHANT_ID        => $BASCore->getBasDetails()->getMerchantId(),
                                   BAS\Details\Entity::BALANCE_ID => $BASCore->getBasDetails()->getBalanceId(),
                                   'start_time'                   => $workerStartTime,
                                   'end_time'                     => $workerEndTime
                               ]);

            $this->dispatchJobForStatementProcessing($this->params);

            $this->delete();

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_FAILED, $this->params);

            $BASCore = new BAS\Core;

            $this->trace->count(BAS\Metric::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_FAILED,
                                [
                                    BAS\Details\Entity::CHANNEL => $this->params['channel'],
                                ]);

            $this->checkRetry();
        }
    }

    protected function dispatchJobForStatementProcessing(array $params, $retryCount = 0)
    {
        $delay = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::BANKING_ACCOUNT_STATEMENT_PROCESS_DELAY]);

        if (empty($delay) == true)
        {
            $delay = self::DEFAULT_BANKING_ACCOUNT_STATEMENT_PROCESS_DELAY;
        }

        try
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESS_DISPATCH_JOB_REQUEST,
                $this->params + ['delay' => $delay]);

            unset($params['attempt_number']);

            BankingAccountStatementProcessor::dispatch($this->mode, $params)->delay($delay);
        }
        catch (\Throwable $e)
        {
            if ($retryCount < self::MAX_RETRY_COUNT_FOR_PROCESSING_ACCOUNT_STATEMENT)
            {
                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESS_DISPATCH_JOB_RETRY,
                    $this->params +
                    [
                        'delay'       => $delay,
                        'retry_count' => $retryCount + 1,
                    ]);

                return $this->dispatchJobForStatementProcessing($params, $retryCount+1);
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAILED_TO_ENQUEUE_BANKING_ACCOUNT_STATEMENT_PROCESSING_JOB);

            $this->trace->count(BAS\Metric::BAS_PROCESSOR_QUEUE_PUSH_FAILURES_TOTAL);

            Tracer::startSpanWithAttributes(HyperTrace::BAS_PROCESSOR_QUEUE_PUSH_FAILURES_TOTAL);
        }
    }


    protected function checkRetry()
    {
        if ($this->attemptNumber < self::MAX_RETRY_ATTEMPT)
        {
            $data                           = $this->params;
            $data[BAS\Core::DELAY]          = self::MAX_RETRY_DELAY * pow(2, $this->attemptNumber);
            $data[BAS\Core::ATTEMPT_NUMBER] = $this->attemptNumber + 1;

            (new BAS\Core)->dispatchBankingAccountStatementJob($data);

            $data['attempt_number'] = $this->attemptNumber;

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_RELEASED, $data);
        }
        else
        {
            $traceData                 = $this->params;
            $traceData['job_attempts'] = $this->attemptNumber;
            $traceData['message']      = 'Deleting the job after configured number of tries. Still unsuccessful.';

            if (array_key_exists('balance_id', $traceData) === true)
            {
                unset($traceData['account_number']);
            }

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_DELETED, $traceData);

            $operation = 'icici banking account statement fetch job failed';

            //TODO:// setup new channel for icici
            (new SlackNotification)->send($operation, $this->params, null, 1, 'rx_ca_rbl_alerts');
        }

        $this->delete();
    }

    /***
     * Fixed window rate limiter. allows only $rateLimit number of requests in $windowLength seconds.
     *
     * @param string $channel
     *
     * @return array of 2 elements.
     *               1st element is true if it passes rate limit else false.
     *               2nd element tells the number of request i.e. if the request is accepted then total number of accepted requests.
     *               1st element will be true until and unless 2nd element is <= rate limit.
     */
    public function checkRateLimit(string $channel)
    {
        $app = App::getFacadeRoot();

        $redis = $app['redis']->connection();

        $rateLimit = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::ICICI_STATEMENT_FETCH_RATE_LIMIT]);

        $windowLength = (int) (new AdminService)->getConfigKey(['key' => ConfigKey::ICICI_STATEMENT_FETCH_WINDOW_LENGTH]);

        // rate_limit and window_length is an interdependent combination, hence even if one of them is missing we take
        // default values for both.
        if (empty($rateLimit) === true or empty($windowLength) === true)
        {
            $rateLimit = self::DEFAULT_RATE_LIMIT;

            $windowLength = self::DEFAULT_FIXED_WINDOW_LENGTH;
        }

        $currentTime = Carbon::now()->getTimestamp();

        $redisKey = self::STATEMENT_FETCH_FIXED_WINDOW_CONFIG_KEY_PREFIX . $channel . stringify((int) ($currentTime/$windowLength));

        // if the redis key is unset it will return null.
        $currentRequests = $redis->get($redisKey);

        if ($currentRequests !== null and $currentRequests >= $rateLimit)
        {
            // returning $currentRequests + 1 as $currentRequests number of requests were already sent in the current window
            // and the request received now is ($currentRequests + 1)th request.
            return [false, $currentRequests + 1, $redisKey];
        }

        if ($currentRequests === null)
        {
            $redis->set($redisKey, 0, 'NX','EX', $windowLength);
        }

        $updatedCurrentRequests = $redis->incr($redisKey);

        if ($updatedCurrentRequests > $rateLimit)
        {
            $redis->decr($redisKey);

            return [false, $updatedCurrentRequests, $redisKey];
        }

        return [true, $updatedCurrentRequests, $redisKey];
    }
}
