<?php

namespace RZP\Models\Merchant\Fraud\HealthChecker;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Illuminate\Cache\RedisStore;
use RZP\Jobs\RiskHealthChecker;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    /**
     * @var RedisStore
     */
    private $redis;

    public function __construct()
    {
        parent::__construct();

        $this->redis = $this->app['cache'];
    }

    public function periodicCron($checkerType): array
    {
        $this->trace->info(TraceCode::HEALTH_CHECKER_PERIODIC_CRON_STARTED);

        $merchantList = $this->repo->merchant->getMerchantListForPeriodicHealthCheck($checkerType);

        $this->trace->info(
            TraceCode::HEALTH_CHECKER_DEBUG, [
                Constants::EVENT_TYPE           => Constants::PERIODIC_CHECKER_EVENT,
                'operation'                     => 'get_eligible_merchants',
                'count'                         => count($merchantList),
                Constants::CHECKER_TYPE         => $checkerType,
            ]
        );

        /**
 * @var Merchant\Entity $merchant
*/
        foreach ($merchantList as $merchant)
        {
            if ($this->isMerchantEligibleForRiskCheck($merchant, Constants::PERIODIC_CHECKER_EVENT, $checkerType) === false) {
                continue;
            }

            $paymentExistsInWindow = $merchant->payments()
                ->select(Payment\Entity::ID)
                ->where(Payment\Entity::CREATED_AT, '>=', now()->timestamp - Constants::PERIODIC_CHECKER_MERCHANT_LIST_PAYMENT_CREATED_WINDOW_SECONDS)
                ->first();

            if (is_null($paymentExistsInWindow) === true) {
                $this->trace->info(
                    TraceCode::HEALTH_CHECKER_CRON_MERCHANT_SKIPPED, [
                    'merchant_id'               => $merchant->getId(),
                    'skip_reason'               => Constants::SKIP_REASON_NO_PAYMENT_IN_WINDOW,
                    Constants::CHECKER_TYPE     => $checkerType,
                    ]
                );

                continue;
            }

            $this->notifyRiskChecker(
                $merchant->getId(), Constants::PERFORM_HEALTH_CHECK_JOB, [
                    Constants::RETRY_COUNT_KEY          => 0,
                    Constants::EVENT_TYPE               => Constants::PERIODIC_CHECKER_EVENT,
                    Constants::CHECKER_TYPE             => $checkerType,
                ]
            );
        }

        $this->trace->info(TraceCode::HEALTH_CHECKER_PERIODIC_CRON_ENDED);

        return ['success' => true];
    }

    public function retryCron($checkerType): array
    {
        $this->trace->info(
            TraceCode::HEALTH_CHECKER_RETRY_CRON_STARTED,
            [
                Constants::CHECKER_TYPE  => $checkerType,
            ]
        );

        $eventTypeRedisMap = Constants::getAllEventTypeRedisMap($checkerType);
        foreach ($eventTypeRedisMap as  $eventType => $redisMap)
        {

            $retryMerchantIdList = $this->getMerchantList(
                Constants::RETRY_WAIT_SECONDS,
                $redisMap,
                $checkerType
            );

            $this->trace->info(
                TraceCode::HEALTH_CHECKER_DEBUG,
                [
                    Constants::EVENT_TYPE       => $eventType,
                    'operation'                 => 'get_retryable_merchants',
                    'count'                     => count($retryMerchantIdList),
                    Constants::CHECKER_TYPE     => $checkerType,
                ]
            );

            foreach ($retryMerchantIdList as $merchantId) {
                $this->notifyRiskChecker(
                    $merchantId, Constants::PERFORM_HEALTH_CHECK_JOB,
                    [
                        Constants::RETRY_COUNT_KEY  => 1,
                        Constants::EVENT_TYPE       => $eventType,
                        Constants::CHECKER_TYPE     => $checkerType,
                    ]
                );
            }
        }

        $this->trace->info(TraceCode::HEALTH_CHECKER_RETRY_CRON_ENDED, [
            Constants::CHECKER_TYPE  => $checkerType,
        ]);

        return ['success' => true];
    }

    public function reminderCron($checkerType): array
    {
        $this->trace->info(TraceCode::HEALTH_CHECKER_REMINDER_CRON_STARTED, [
            Constants::CHECKER_TYPE  => $checkerType,
        ]);

        $reminderMerchantIdList = $this->getMerchantList(
            Constants::REMINDER_WAIT_SECONDS,
            Constants::REDIS_REMINDER_MAP_NAME[$checkerType],
            $checkerType
        );

        $this->trace->info(
            TraceCode::HEALTH_CHECKER_DEBUG, [
            'operation'                 => 'get_reminder_merchants',
            'count'                     => count($reminderMerchantIdList),
            Constants::CHECKER_TYPE     => $checkerType,
            ]
        );

        foreach ($reminderMerchantIdList as $merchantId)
        {
            // for reminder, using the same queue as website checker
            $this->notifyRiskChecker(
                $merchantId,
                Constants::SEND_REMINDER_TO_MERCHANT_JOB,
                [
                    Constants::CHECKER_TYPE  => $checkerType,
                ]
            );
        }

        $this->trace->info(TraceCode::HEALTH_CHECKER_REMINDER_CRON_ENDED,
        [
            Constants::CHECKER_TYPE  => $checkerType,
        ]);

        return ['success' => true];
    }

    public function milestoneCron($checkerType): array
    {
        $this->trace->info(TraceCode::HEALTH_CHECKER_MILESTONE_CRON_STARTED,
        [
            Constants::CHECKER_TYPE  => $checkerType,
        ]);

        $this->getDataAndRunCron(
            Constants::MILESTONE_CHECKER_EVENT,
            $checkerType
        );

        $this->trace->info(TraceCode::HEALTH_CHECKER_MILESTONE_CRON_ENDED,
        [
            Constants::CHECKER_TYPE  => $checkerType,
        ]);

        return ['success' => true];
    }

    public function riskScoreCron($checkerType): array
    {
        $this->trace->info(TraceCode::HEALTH_CHECKER_RISK_SCORE_CRON_STARTED, [
            Constants::CHECKER_TYPE  => $checkerType,
        ]);

        $this->getDataAndRunCron(
            Constants::RISK_SCORE_CHECKER_EVENT,
            $checkerType
        );

        $this->trace->info(TraceCode::HEALTH_CHECKER_RISK_SCORE_CRON_ENDED, [
            Constants::CHECKER_TYPE  => $checkerType,
        ]);

        return ['success' => true];
    }

    public function getDataAndRunCron(string $eventType, $checkerType)
    {
        $experimentResult       = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(),
            Merchant\RazorxTreatment::MERCHANT_RISK_FACT_MIGRATION,
            Mode::LIVE);

        $isMerchantRiskFactMigrationEnabled = ( $experimentResult === 'on' ) ? true : false;

        $merchantIdList          = [];

        $experimentResult       = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(),
            Merchant\RazorxTreatment::DRUID_MIGRATION,
            Mode::LIVE);

        $isDruidMigrationEnabled = ( $experimentResult === 'on' ) ? true : false;

        if($eventType === Constants::MILESTONE_CHECKER_EVENT and $isMerchantRiskFactMigrationEnabled === true)
        {
            $pinotQuery                 = Constants::EVENT_TYPE_QUERY_MAP[$eventType];

            $merchantIdList             = $this->getMerchantListFromPinot($pinotQuery);
        }
        elseif ($eventType === Constants::RISK_SCORE_CHECKER_EVENT and $isDruidMigrationEnabled === true )
        {
            $pinotQuery                 = Constants::EVENT_TYPE_QUERY_MAP[$eventType];

            $merchantIdList             = $this->getMerchantListFromPinot($pinotQuery);
        }
        else
        {
            $query                      = Constants::EVENT_TYPE_DRUID_QUERY_MAP[$eventType];

            $merchantIdList             = $this->getMerchantListFromDruid($query);
        }

        $this->trace->info(
            TraceCode::HEALTH_CHECKER_DEBUG, [
            Constants::EVENT_TYPE       => $eventType,
            'operation'                 => 'get_eligible_merchants',
            'count'                     => count($merchantIdList),
            Constants::CHECKER_TYPE     => $checkerType,
            ]
        );

        foreach ($merchantIdList as $merchantId)
        {
            /**
             * @var Merchant\Entity $merchant
            */
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            if ($this->isMerchantEligibleForRiskCheck($merchant, $eventType, $checkerType) === false) {
                continue;
            }

            $this->notifyRiskChecker(
                $merchant->getId(), Constants::PERFORM_HEALTH_CHECK_JOB, [
                    Constants::RETRY_COUNT_KEY  => 0,
                    Constants::EVENT_TYPE       => $eventType,
                    Constants::CHECKER_TYPE     => $checkerType,
                ]
            );
        }
    }

    private function getMerchantList(int $waitSeconds, string $redisMap, string $checkerType): array
    {
        $retryCutoffTimestamp = now()->timestamp - $waitSeconds;

        $mapValues = $this->redis->connection()->hgetall($redisMap);

        $this->trace->debug(
            TraceCode::HEALTH_CHECKER_DEBUG,
            [
                'redis_map'                     => $redisMap,
                'retry_map_values_unfiltered'   => $mapValues,
                Constants::CHECKER_TYPE         => $checkerType,
            ]
        );

        $merchantList = [];
        foreach ($mapValues as $merchantId => $setTimestamp)
        {
            $setTimestamp = (int) $setTimestamp;

            if ($setTimestamp > $retryCutoffTimestamp) {
                continue;
            }

            $merchantList []= $merchantId;
        }

        return $merchantList;
    }

    private function isMerchantEligibleForRiskCheck(Merchant\Entity $merchant, string $eventType, $checkerType)
    {
        if ($merchant->isFeatureEnabled(Feature\Constants::APPS_EXTEMPT_RISK_CHECK) === true) {
            $this->trace->info(
                TraceCode::HEALTH_CHECKER_CRON_MERCHANT_SKIPPED, [
                'merchant_id'               => $merchant->getId(),
                'skip_reason'               => Constants::SKIP_REASON_EXEMPT_RISK_CHECK,
                Constants::CHECKER_TYPE     => $checkerType,
                ]
            );

            return false;
        }

        // NOTE: additonal check, though doesn't guard from corner case retries
        //
        // Can have additonal set to have a full proof solution to avoid retries, but then
        // will add if needed (as will be storing way more data compared to the retry map)
        // also, chances that it will be retried are extremely low

        $redisMap = Constants::eventAndCheckerTypeRedisMap($eventType, $checkerType);

        $isAlreadyScheduledForRetry = $this->redis->connection()->hexists($redisMap, $merchant->getId());

        if (empty($isAlreadyScheduledForRetry) === false) {
            $this->trace->info(
                TraceCode::HEALTH_CHECKER_CRON_MERCHANT_SKIPPED,
                [
                    Constants::EVENT_TYPE           => $eventType,
                    'merchant_id'                   => $merchant->getId(),
                    'skip_reason'                   => Constants::SKIP_REASON_RETRY_SCHEDULED,
                    Constants::CHECKER_TYPE         => $checkerType,
                ]
            );

            return false;
        }

        return true;
    }

    public function notifyRiskChecker(string $merchantId, string $jobType, array $jobDetails)
    {
        $checkerType = $jobDetails[Constants::CHECKER_TYPE] ?? Constants::WEBSITE_CHECKER;
        $this->trace->info(
            TraceCode::HEALTH_CHECKER_JOB_ENQUEUE_INITIATED,
            [
                'merchant_id'               => $merchantId,
                Constants::CHECKER_TYPE     => $checkerType,
            ]
        );

        try
        {
            $jobRequest = [
                'job_type'    => $jobType,
                'job_details' => $jobDetails,
                'merchant_id' => $merchantId,
            ];

            $this->dispatchInQueueOnCheckerType($jobRequest, $checkerType);
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::HEALTH_CHECKER_JOB_ENQUEUE_FAILED,
                [
                    'merchant_id'               => $merchantId,
                    Constants::CHECKER_TYPE     => $jobDetails[Constants::CHECKER_TYPE],
                ]
            );
        }
    }

    private function getMerchantListFromDruid(string $query): array
    {
        list($error, $res) = $this->app['druid.service']->getDataFromDruid(
            [
            'query' => $query
            ]
        );

        if (isset($error) === true) {
            $this->trace->traceException(
                new \Exception($error),
                Trace::ERROR,
                TraceCode::HEALTH_CHECKER_DRUID_ERROR

            );

            return [];
        }

        return array_pluck($res, 'merchants_id');
    }


    private function getMerchantListFromPinot(string $query): array
    {
        $pinotClient = $this->app['eventManager'];

        try
        {
            $res = $pinotClient->getDataFromPinot(
                [
                    'query' => $query
                ]
            );
        }
        catch(\Throwable $e)
        {
            // No need to trace error as its harvester client already logs it.
            return [];
        }

        if (empty($res) === true)
        {
            return [];
        }

        return array_pluck($res, 'merchants_id');
    }

    private function dispatchInQueueOnCheckerType(array $params, string $checkerType)
    {
        if ($checkerType === Constants::APP_CHECKER)
        {
            // delay added due to app checker rate limit
            RiskHealthChecker::dispatch($this->mode, $params, $checkerType)->delay(random_int(0, Constants::APP_CHECKER_QUEUE_DELAY_LIMIT));
            return;
        }
        RiskHealthChecker::dispatch($this->mode, $params, $checkerType);
    }
}
