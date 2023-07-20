<?php

namespace RZP\Models\Payment\Downtime;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Method;
use RZP\Trace\TraceCode;
use RZP\Jobs\PaymentDowntimeEvent;
use Illuminate\Support\Facades\Redis;
use RZP\Services\RazorpayLabs\SlackApp as SlackAppService;
use RZP\Models\Gateway\Downtime\Source;
use RZP\Models\Payment\Downtime\Repository;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class Core extends Base\Core
{
    protected $mutex;
    protected $redis;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->redis = Redis::Connection('mutex_redis');
    }

    public function create(array $input): Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_DOWNTIME_CREATE,
            $input
        );

        $downtime = (new Entity)->build($input);

        $this->repo->saveOrFail($downtime);

        if($downtime->isScheduled() === false && $downtime->getMethod() !== Method::EMANDATE)
        {
            (new Service())->emailDowntime(Constants::CREATED, $downtime);

            $this->trace->info(TraceCode::TRIGGER_WEBHOOK_NOTIFICATIONS, ["state"=> Status::STARTED, "downtime" => $downtime]);

            PaymentDowntimeEvent::dispatch($this->mode, Status::STARTED, serialize($downtime));
            (new DowntimeManagerService($this->app))->notifyDowntime($downtime, Status::STARTED);
            (new SlackAppService($this->app))
                ->sendDowntimeRequestToSlack($downtime, Status::STARTED);
        }

        $this->refreshOngoingDowntimesCache($downtime);
        return $downtime;
    }

    public function edit(Entity $downtime, array $input): Entity
    {
        $this->trace->info(TraceCode::PAYMENT_DOWNTIME_EDIT, [
            'Input'     => $input,
            'Downtime'  => $downtime->toArray(),
        ]);

        $lastSeverity = $downtime->getSeverity();

        $downtime->edit($input);

        $this->repo->saveOrFail($downtime);

        if($downtime->isScheduled() === false && $downtime->getMethod() !== Method::EMANDATE)
        {
            (new Service())->emailDowntime(Constants::CREATED, $downtime, $lastSeverity);

            $this->trace->info(TraceCode::TRIGGER_WEBHOOK_NOTIFICATIONS, ["state"=> $downtime::STATUS, "downtime" => $downtime]);

            if((new Service())->shouldSendMerchantDowntimes(Constants::WEBHOOKS) === true)
            {
                PaymentDowntimeEvent::dispatch($this->mode, Status::UPDATED, serialize($downtime), $lastSeverity);
            }
            else
            {
                PaymentDowntimeEvent::dispatch($this->mode, Status::STARTED, serialize($downtime), $lastSeverity);
            }

            (new DowntimeManagerService($this->app))->notifyDowntime($downtime, Status::UPDATED);
            (new SlackAppService($this->app))->sendDowntimeRequestToSlack($downtime, Status::STARTED);
        }

        $this->refreshOngoingDowntimesCache($downtime);
        return $downtime;
    }

    public function refreshOngoingDowntimesCache($downtime)
    {
        try
        {
            $timeStarted = millitime();
            $this->trace->count(Metric::ECO_SYSTEM_CACHE_REFRESH_COUNT,
                                ['cache_type' => 'ongoing_downtimes']);

            if((empty($downtime) === false) and (isset($downtime['merchant_id']) === true))
            {
                return;
            }

            $this->mutex->acquireAndRelease(
                "ongoing_downtimes_mutex_key",
                function () use ($downtime)
                {
                    $this->trace->info(TraceCode::REFRESH_ONGOING_DOWNTIME_CACHE, ['key'=>$downtime['id']]);

                    $downtimes = $this->fetchOngoingDowntimesFromDB();

                    $this->redis->HMSET("ongoing_downtimes", ['ongoing_downtimes' => json_encode($downtimes)]);
                },
                10,
                ErrorCode::BAD_REQUEST_PAYMENT_DOWNTIME_MUTEX_TIMED_OUT
            );

            $this->trace->histogram(Metric::ECO_SYSTEM_CACHE_REFRESH_DURATION,
                                    millitime() - $timeStarted,
                                    ['cache_type' => 'ongoing_downtimes']);
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::FAILED_TO_REFRESH_ONGOING_PAYMENT_DOWNTIME_CACHE, ['key'=>$downtime['id']]);
        }
    }

    public function refreshScheduledDowntimesCache()
    {
        try
        {
            $timeStarted = millitime();
            $this->trace->count(Metric::ECO_SYSTEM_CACHE_REFRESH_COUNT,
                                ['cache_type' => 'scheduled_downtimes']);

            $this->trace->info(TraceCode::REFRESH_SCHEDULED_DOWNTIME_CACHE);

            $downtimes = $this->fetchScheduledDowntimesFromDB();

            $this->redis->HMSET("scheduled_downtimes", ['scheduled_downtimes' => json_encode($downtimes)]);

            $this->trace->histogram(Metric::ECO_SYSTEM_CACHE_REFRESH_DURATION,
                                    millitime() - $timeStarted,
                                    ['cache_type' => 'scheduled_downtimes']);
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::FAILED_TO_REFRESH_SCHEDULED_PAYMENT_DOWNTIME_CACHE);
        }
    }

    public function refreshHistoricalDowntimeCache( $lookbackPeriod = 0 )
    {
        try{
            $timeStarted = millitime();
            $this->trace->count(Metric::ECO_SYSTEM_CACHE_REFRESH_COUNT,
                                    ['cache_type' => 'resolved_downtimes']);

            $remainingDays = $lookbackPeriod;
            $endDate = Carbon::now(Timezone::IST)->format('Y-m-d');

            while($remainingDays >=0)
            {
                $startDate = $this->getStartDate($remainingDays, $endDate);

                $params = ["startDate"=> $startDate, "endDate"=>$endDate];

                $this->trace->info(TraceCode::DOWNTIME_HISTORY_REFRESH_PARAMS, $params);

                $this->mutex->acquireAndRelease(
                    "resolved_downtimes_mutex_key",
                    function () use ($params)
                    {
                        $downtimes = $this->fetchResolvedDowntimesFromDB($params);

                        $indexList = $this->indexDowntimesByDateAndMethod($downtimes);

                        $this->trace->info(TraceCode::CACHING_DOWNTIME_HISTORY_STARTED, $params);

                        $this->trace->info(TraceCode::REFRESH_ONGOING_DOWNTIME_CACHE);

                        $this->cacheResolvedDowntimesByDateAndMethod($indexList);

                        $this->trace->info(TraceCode::CACHING_DOWNTIME_HISTORY_COMPELTED, $params);
                    },
                    30,
                    ErrorCode::BAD_REQUEST_PAYMENT_DOWNTIME_MUTEX_TIMED_OUT
                );

                $remainingDays = $remainingDays - Constants::HISTORY_REFRESH_BATCH_SIZE;

                $endDateEpoch = strtotime($endDate) -  ((Constants::HISTORY_REFRESH_BATCH_SIZE ) * Constants::SECONDS_IN_A_DAY);

                $endDate = date("Y-m-d", $endDateEpoch);
            }

            $this->trace->histogram(Metric::ECO_SYSTEM_CACHE_REFRESH_DURATION,
                                    millitime() - $timeStarted,
                                    ['cache_type' => 'resolved_downtimes']);
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::FAILED_TO_REFRESH_HISTORICAL_PAYMENT_DOWNTIME_CACHE);
        }
    }

    public function fetchOngoingDowntimes()
    {
        try
        {
            return $this->fetchOngoingDowntimesFromCache();
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::FAILED_TO_FETCH_ONGOING_DOWNTIMES_FROM_CACHE);

            return $this->fetchOngoingDowntimesFromDB();
        }
    }

    public function fetchResolvedDowntimes($params)
    {
        try
        {
            return $this->fetchResolvedDowntimesFromCache($params);
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::FAILED_TO_FETCH_RESOLVED_DOWNTIMES_FROM_CACHE);

            return $this->fetchResolvedDowntimesFromDB($params);
        }
    }

    public function fetchScheduledDowntimes()
    {
        try
        {
            return $this->fetchScheduledDowntimesFromCache();
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::FAILED_TO_FETCH_SCHEDULED_DOWNTIMES_FROM_CACHE);

            return $this->fetchScheduledDowntimesFromDB();
        }
    }

    public function fetchOngoingDowntimesFromDB()
    {
        $this->trace->info(TraceCode::FETCH_ONGOING_DOWNTIMES_FROM_DB);

        $downtimes = (new Repository())->fetchOngoingDowntimes()->toArrayPublic();

        (new Service())->removeGranularDowntimeKeysFromCollection($downtimes);

        return $downtimes['items'];
    }

    public function fetchResolvedDowntimesFromDB($params)
    {
        $this->trace->info(TraceCode::FETCH_RESOLVED_DOWNTIMES_FROM_DB, ['params' =>$params]);

        $downtimes = (new Repository())->fetchResolvedDowntimes($params)->toArrayPublic();

        (new Service())->removeGranularDowntimeKeysFromCollection($downtimes);

        return $downtimes['items'];
    }

    public function fetchScheduledDowntimesFromDB()
    {
        $this->trace->info(TraceCode::FETCH_SCHEDULED_DOWNTIMES_FROM_DB);

        $downtimes = (new Repository())->fetchScheduledDowntimes()->toArrayPublic();

        (new Service())->removeGranularDowntimeKeysFromCollection($downtimes);

        return $downtimes['items'];
    }

    public function createFromGatewayDowntimes(array $input = [])
    {
        $gatewayDowntimes = $this->repo->gateway_downtime->fetchCurrentAndFutureDowntimes($withoutTerminal = true);

        $this->trace->info(TraceCode::FETCHED_GATEWAY_DOWNTIMES_FROM_DB, ["context" => $gatewayDowntimes]);

        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::STATUSCAKE);

        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::VAJRA);

        $paymentDowntimesEnabled = (bool) ConfigKey::get(ConfigKey::ENABLE_PAYMENT_DOWNTIME_PHONEPE, false);

        if ($paymentDowntimesEnabled === false)
        {
            $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::PHONEPE);
        }

        foreach (Payment\Method::getAllPaymentMethods() as $method)
        {
            $downtimeProcessor = __NAMESPACE__ . '\\' . studly_case($method) . 'Processor';

            if (class_exists($downtimeProcessor) === true)
            {
                (new $downtimeProcessor)->process($gatewayDowntimes);
            }
        }
    }

    /**
     * @param array $downtimes
     *
     * @return array|mixed
     */
    private function indexDowntimesByDateAndMethod(array $downtimes)
    {
        $endResults = [];
        foreach ($downtimes as $downtime)
        {
            $createdDate = date("Y-m-d", $downtime['created_at']);

            $method      = $downtime['method'];

            $key         = $createdDate . '#' . $method;

            if (array_key_exists($key, $endResults))
            {
                $indexList = $endResults[$key];

                array_push($indexList, $downtime);

                $endResults[$key] = $indexList;
            }
            else
            {
                $indexList   = array();

                array_push($indexList, $downtime);

                $endResults[$key] = $indexList;

                $this->trace->info(TraceCode::RESOLVES_DOWNTIMES_INDEX_KEY_NOT_PRESENT, ["key" => $key]);
            }
        }
        return $endResults;
    }

    /**
     * @param array $indexList
     */
    private function cacheResolvedDowntimesByDateAndMethod(array $indexList): void
    {
        foreach ($indexList as $index => $list)
        {
            $this->redis->HMSET($index,  ["downtimes" => json_encode($list)]);

            $this->redis->EXPIRE($index, Constants::MAX_LOOKBACK_PERIOD * Constants::SECONDS_IN_A_DAY);

            $this->trace->info(TraceCode::CACHED_DOWNTIMES_FOR_KEY, ["key" =>  $index]);
        }
    }

    private function fetchDowntimesByDateFromCache($key)
    {
        $downtimes = $this->redis->HGETALL($key);

        if (empty($downtimes) === false)
        {
            $downtimes = json_decode($downtimes['downtimes']);
        }
        else
        {
            $downtimes = [];
        }

        return $downtimes;
    }

    private function getStartDate( $remainingDays,  $endDate)
    {
        if ($remainingDays < Constants::HISTORY_REFRESH_BATCH_SIZE)
        {
            $startDateEpoch = strtotime($endDate) - ($remainingDays * Constants::SECONDS_IN_A_DAY);

            $startDate      = date("Y-m-d", $startDateEpoch);
        }
        else
        {
            $startDateEpoch = strtotime($endDate) - (Constants::HISTORY_REFRESH_BATCH_SIZE * Constants::SECONDS_IN_A_DAY);

            $startDate      = date("Y-m-d", $startDateEpoch);
        }

        return $startDate;
    }

    /**
     * @return mixed
     */
    private function fetchOngoingDowntimesFromCache()
    {
        $this->trace->info(TraceCode::FETCH_PAYMENTS_DOWNTIMES_FROM_CACHE);

        $downtimes = $this->redis->HGETALL('ongoing_downtimes');

        return json_decode($downtimes['ongoing_downtimes']);
    }

    /**
     * @param $params
     *
     * @return array
     */
    private function fetchResolvedDowntimesFromCache($params): array
    {
        $startDate = $params['startDate'];

        $endDate = $params['endDate'];

        $method = "";

        $maxCount = -1;
        $skip = -1;
        $count = -1;

        if (isset($params['method']))
        {
            $method = $params['method'];
        }

        if(isset($params['skip']) and isset($params['count']))
        {
            $maxCount = $params['skip'] + $params['count'];
            $skip = $params['skip'];
            $count = $params['count'];
        }

        $endDateEpoch = strtotime($endDate);

        $dayDiff = (strtotime($endDate) - strtotime($startDate)) / Constants::SECONDS_IN_A_DAY;

        $downtimes = [];

        for ($dayVal = 0; $dayDiff >= $dayVal; $dayVal++)
        {
            $dayForamt = date("Y-m-d", $endDateEpoch);

            $this->trace->info(TraceCode::FETCH_RESOLVED_PLATFORM_DOWNTIMES_FROM_CACHE, ['key' => $dayForamt]);

            if (empty($method))
            {
                $cardDowntimes = $this->fetchDowntimesByDateFromCache($dayForamt . "#card");

                $netbankingDowntimes = $this->fetchDowntimesByDateFromCache($dayForamt . "#netbanking");

                $upiDowntimes = $this->fetchDowntimesByDateFromCache($dayForamt . "#upi");

                $emandateDowntimes = $this->fetchDowntimesByDateFromCache($dayForamt . "#emandate");

                $downtimes = array_merge($downtimes, $cardDowntimes, $netbankingDowntimes, $upiDowntimes, $emandateDowntimes);
            }
            else
            {
                $dTimes = $this->fetchDowntimesByDateFromCache($dayForamt . "#" . $method);

                $downtimes = array_merge($downtimes, $dTimes);
            }

            if($maxCount !== -1 and sizeof($downtimes) >= $maxCount)
            {
                $this->trace->info(TraceCode::RETURN_PAGE_DOWNTIMES_FROM_CACHE, ['params' => $params, 'beyondMaxSize' => false]);
                return array_slice($downtimes, $skip, $count);
            }
            $endDateEpoch = $endDateEpoch - (Constants::SECONDS_IN_A_DAY);
        }

        if($maxCount !== -1)
        {
            $this->trace->info(TraceCode::RETURN_PAGE_DOWNTIMES_FROM_CACHE, ['params' => $params, 'beyondMaxSize' => true]);
            return array_slice($downtimes, $skip, $count);
        }
        return $downtimes;
    }

    private function fetchScheduledDowntimesFromCache()
    {
        $downtimes = $this->redis->HGETALL('scheduled_downtimes');

        if (empty($downtimes) === false)
        {
            $downtimes = json_decode($downtimes['scheduled_downtimes']);
        }
        else
        {
            $downtimes = [];
        }

        return $downtimes;
    }

}
