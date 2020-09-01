<?php


namespace RZP\Models\Gateway\Downtime;


use App;
use Database\Connection;
use RZP\Models\Base\UniqueIdEntity;
use \stdClass;
use Carbon\Carbon;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Redis;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Foundation\Application;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;

class DowntimeDetection
{
    /**
     * @var Trace
     */
    protected $trace;

    /**
     * @var RazorX
     */
    protected $razorx;

    /**
     * @var RedisManager
     */
    protected $redis;

    protected $repo;

    protected $app;

    const ISSUER            = 'ISSUER';

    const NETWORK           = 'NETWORK';

    const PROVIDER          = 'PROVIDER';

    const BANK              = 'BANK';

    const METHOD            = 'METHOD';

    const RAZORX_FEATURE    = 'downtime_detection';

    /**
     * Type of Downtime Check
     * Success_rate: will calculate success rate on last N payments having final state.
     * Payment_interval: will calculate rate at which we receive callbacks for last N payments.
     */
    const SUCCESS_RATE      = 'success_rate';

    const PAYMENT_INTERVAL  = 'payment_interval';

    // 10 seconds
    const MAX_LAG_IN_MILLI  = 10000;

    public function __construct()
    {
        /**
         * @var $app Application
         */
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace   = $app['trace'];

        $this->razorx  = $app['razorx'];

        $this->mode    = $app['rzp.mode'];

        $this->repo = $app['repo'];

        $this->redis   = Redis::connection('mutex_redis')->client();
    }

    protected function initConfigurationSettings($type, $method, $key, $value, $settingType)
    {
        $settings = $this->loadSettingsFromRedis($type, $method, $key, $value, $settingType);

        if (empty($settings) === true)
        {
            // Alert for manual action if no configuration exist for key.
            $this->trace->error(
                TraceCode::GATEWAY_DOWNTIME_CONFIGURATION_V2_SETTINGS_MISSING,
                [
                    'type'          => $type,
                    'method'        => $method,
                    'key'           => $key,
                    'value'         => $value,
                    'setting_type'  => $settingType,
                ]
            );
        }

        return $settings;
    }

    protected function loadSettingsFromRedis($type, $method, $key, $value, $settingType)
    {
        $arrayKey = $type . '_' . $method . '_' . $key . '_' . $value . '_' . $settingType;

        $allSettings = $this->redis->hget(Constants::SETTINGS_KEY, strtolower($arrayKey));

        return json_decode($allSettings);
    }

    protected function isSuccess(\RZP\Models\Payment\Entity $payment, $type): bool
    {
        if ($type === self::SUCCESS_RATE)
        {
            if ($payment->hasBeenAuthorized() === true)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else if ($type === self::PAYMENT_INTERVAL)
        {
            if ($payment->getStatus() !== Status::CREATED)
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        throw new Exception\LogicException("Invalid Type");
    }

    protected function calculateDowntimeMetric(PublicCollection $payments, string $type)
    {
        $downtimeMetric = new stdClass;
        $downtimeMetric->numerator = 0;
        $downtimeMetric->denominator = 0;

        $merchantTotalPaymentsMap = [];

        $downtimeMetric->top_merchant_count = 0;

        foreach ($payments as $index => $payment)
        {
            $downtimeMetric->denominator = $downtimeMetric->denominator + 1;

            if (array_key_exists($payment->getMerchantId(), $merchantTotalPaymentsMap) == true)
            {
                $merchantTotalPaymentsMap[$payment->getMerchantId()]++;
            }
            else
            {
                $merchantTotalPaymentsMap[$payment->getMerchantId()] = 1;
            }

            if ($merchantTotalPaymentsMap[$payment->getMerchantId()] > $downtimeMetric->top_merchant_count)
            {
                $downtimeMetric->top_merchant_count += 1;
            }

            if ($this->isSuccess($payment, $type) === true)
            {
                $downtimeMetric->numerator = $downtimeMetric->numerator + 1;
            }
        }

        return $downtimeMetric;
    }

    public function createDowntimeDetectionJobs()
    {
        $to = Carbon::now();

        $value = $this->razorx->getTreatment(UniqueIdEntity::generateUniqueId(), self::RAZORX_FEATURE, $this->mode);
        if ($value !== 'on')
        {
            return;
        }

        foreach (Constants::getAllJobTypes() as $job)
        {
            \RZP\Jobs\DowntimeDetection::dispatch(
                $this->mode,
                $job['type'],
                $job['method'],
                $job['key'],
                $job['value'],
                $to
            );
        }
    }

    public function createDowntimeIfNecessary($type, $method, $key, $value, $to)
    {
        $conn = $this->repo->payment->getSlaveConnection();

        $replicationLagInMilli = $this->app['db.connector.mysql']->getReplicationLagInMilli($conn);

        if ($replicationLagInMilli > self::MAX_LAG_IN_MILLI)
        {
            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_V2_DOWN_DUE_TO_REPLICA_LAG);

           return;
        }

        //Check if downtime present in Gateway downtime Table
        $downtimeCreatedSince = $this->fetchExistingDowntime($method, $key, $value);

        if (empty($downtimeCreatedSince) == true)
        {
            // if downtime is not present let's check if it needs to be created.
            $createSettings = $this->initConfigurationSettings($type, $method, $key, $value, 'create');

            foreach ($createSettings as $setting)
            {
                $windowSizeInSeconds = $setting[0];

                $minimumPayments = $setting[1];

                $successRateForDowntime = $setting[2];

                if ($type === self::SUCCESS_RATE)
                {
                    $from = $to->copy()->subSeconds($windowSizeInSeconds);
                }
                else if ($type === self::PAYMENT_INTERVAL)
                {
                    $from = $to->copy()->subSeconds(300);

                    $to = $to->subSeconds($windowSizeInSeconds);
                }
                else
                {
                    throw new Exception\LogicException("invalid type.");
                }

                $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_V2_QUERY_STARTED);

                if ($method === Method::CARD)
                {
                    $payments = $this->repo->payment->fetchLastNPaymentsForDowntime($from->timestamp, $to->timestamp, $type, $key, $value, $minimumPayments);
                }
                else if ($method === Method::NETBANKING)
                {
                    $payments = $this->repo->payment->fetchLastNNetbankingPaymentsForDowntime($from->timestamp, $to->timestamp, $type, $key, $value, $minimumPayments);
                }
                else if ($method === Method::UPI)
                {
                    $payments = $this->repo->payment->fetchLastNUpiPaymentsForDowntime($from->timestamp, $to->timestamp, $type, $key, $value, $minimumPayments);
                }
                else
                {
                    new Exception\LogicException("Method not supported yet.");
                }

                $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_V2_QUERY_COMPLETED);

                $metric = $this->calculateDowntimeMetric($payments, $type);

                $numerator = $metric->numerator;

                $denominator = $metric->denominator;

                $downtimeStartTime = Carbon::now()->timestamp;

                $this->trace->info(TraceCode::GATEWAY_DOWNTIME_CONFIGURATION_V2_METRIC,
                    [
                        'type'                      => $type,
                        'method'                    => $method,
                        'key'                       => $key,
                        'value'                     => $value,
                        'payments_from'             => $from,
                        'payments_to'               => $to,
                        'top_merchant_count'        => $metric->top_merchant_count,
                        'window'                    => $windowSizeInSeconds,
                        'minimumPayments'           => $minimumPayments,
                        'successRateForDowntime'    => $successRateForDowntime,
                        'numerator'                 => $numerator,
                        'denominator'               => $denominator,
                    ]);

                if ($denominator < $minimumPayments)
                {
                    continue;
                }

                $successRate = $this->checkPercentage($numerator, $denominator);

                if ($successRate <= $successRateForDowntime)
                {
                    //check if more than 50% of the payments are not of single merchant
                    if ($metric->top_merchant_count > (Constants::getMaxSingleMerchantContribution() * $minimumPayments))
                    {
                        continue;
                    }

                    //todo: more than 50% of the payments are not of error cancelled_by_user

                    $this->trace->info(TraceCode::GATEWAY_DOWNTIME_CONFIGURATION_V2_DOWNTIME_DETECTED,
                        [
                            'type'                      => $type,
                            'method'                    => $method,
                            'key'                       => $key,
                            'value'                     => $value,
                            'payments_from'             => $from,
                            'payments_to'               => $to,
                            'downtime_start_time'       => $downtimeStartTime,
                            'windowSizeInSeconds'       => $windowSizeInSeconds,
                            'minimumPayments'           => $minimumPayments,
                            'successRateForDowntime'    => $successRateForDowntime,
                        ]);

                    // Storing Downtime in Gateway Downtime table
                    $input = [
                        'type'                      => $type,
                        'method'                    => $method,
                        'key'                       => $key,
                        'value'                     => $value,
                        'downtime_start_time'       => $downtimeStartTime,
                    ];

                    (new Core)->createDowntimeV2($input);
                }

                break;
            }
        }
        else
        {
            // If downtime is present, let's check if it can be resolved.

            //TODO: If $downtimeCreatedSince is more then 5 hour. send slack notification.
            // Because There has never been downtime for this long.

            if ($type === self::PAYMENT_INTERVAL)
            {
                return;
            }

            $resolveSetting = $this->initConfigurationSettings($type, $method, $key, $value, 'resolve')[0];

            // valid only in case of payment_interval type
            $windowSizeInSeconds = $resolveSetting[0];

            $minimumPayments = $resolveSetting[1];

            $successRateToResolve = $resolveSetting[2];

            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_V2_QUERY_STARTED);

            if ($type === self::SUCCESS_RATE)
            {
                // todo: we may wanna limit this later.
                $from = ($downtimeCreatedSince-30);

                $to = null;
            }
            else if ($type === self::PAYMENT_INTERVAL)
            {
                $from = $to->copy()->subSeconds(300)->timestamp;

                $to = $to->subSeconds($windowSizeInSeconds)->timestamp;
            }
            else
            {
                throw new Exception\LogicException("invalid type");
            }

            if ($method === Method::CARD)
            {
                $payments = $this->repo->payment->fetchLastNPaymentsForDowntime($from, $to, $type, $key, $value, $minimumPayments);
            }
            else if ($method === Method::NETBANKING)
            {
                $payments = $this->repo->payment->fetchLastNNetbankingPaymentsForDowntime($from, $to, $type, $key, $value, $minimumPayments);
            }
            else if ($method === Method::UPI)
            {
                $payments = $this->repo->payment->fetchLastNUpiPaymentsForDowntime($from, $to, $type, $key, $value, $minimumPayments);
            }
            else
            {
                new Exception\LogicException("Method not supported yet.");
            }

            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_V2_QUERY_COMPLETED);

            $metric = $this->calculateDowntimeMetric($payments, $type);

            $numerator = $metric->numerator;

            $denominator = $metric->denominator;

            $downtimeResolvedAt = Carbon::now()->timestamp;

            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_CONFIGURATION_V2_METRIC,
                [
                    'type' => $type,
                    'method' => $method,
                    'key' => $key,
                    'value' => $value,
                    'setting_type' => 'resolve',
                    'downtime_start_time' => $downtimeCreatedSince,
                    'downtime_recover_time' => $downtimeResolvedAt,
                    'payments_from' => $from,
                    'payments_to' => $to,
                    'top_merchant_count' => $metric->top_merchant_count,
                    'minimumPayments' => $minimumPayments,
                    'successRateToResolve' => $successRateToResolve,
                    'numerator' => $numerator,
                    'denominator' => $denominator,
                ]);

            if ($denominator < $minimumPayments)
            {
                return;
            }

            $successRate = $this->checkPercentage($numerator, $denominator);

            if ($successRate > $successRateToResolve)
            {
                //check if more than 50% of the payments are not of single merchant
                if ($metric->top_merchant_count > (Constants::getMaxSingleMerchantContribution() * $minimumPayments))
                {
                    return;
                }

                $this->trace->info(TraceCode::GATEWAY_DOWNTIME_CONFIGURATION_V2_DOWNTIME_RESOLVED,
                    [
                        'type' => $type,
                        'key' => $key,
                        'value' => $value,
                        'payments_from' => $from,
                        'payments_to' => $to,
                        'downtime_start_time' => $downtimeCreatedSince,
                        'downtime_recover_time' => $downtimeResolvedAt,
                        'minimumPayments' => $minimumPayments,
                        'successRateToResolve' => $successRateToResolve,
                    ]);

                // delete redis key to resolve
                //$this->redis->del([$redisKeyForDowntime]);

                // Resolving Downtime stored in Gateway Downtime table
                $input = [
                    'type'                      => $type,
                    'method'                    => $method,
                    'key'                       => $key,
                    'value'                     => $value,
                    'downtime_start_time'       => $downtimeCreatedSince,
                    'downtime_recover_time'     => $downtimeResolvedAt,
                ];

                (new Core)->resolveDowntimeV2($input);
            }
        }

        return;
    }

    public function checkPercentage($success, $totalCompleted)
    {
        if ($totalCompleted == 0)
        {
            return 1;
        }

        return $success/$totalCompleted;
    }

    public function fetchExistingDowntime($method, $key, $value)
    {
        $input = [
            Entity::GATEWAY => Entity::ALL,
            Entity::METHOD => $method,
            Entity::SOURCE => Source::DOWNTIME_V2,
        ];

        switch ($key)
        {
            case DowntimeDetection::BANK :
            case DowntimeDetection::ISSUER :
                $input[Entity::ISSUER] = $value;
                break;
            case DowntimeDetection::NETWORK :
                $input[Entity::NETWORK] = $value;
                break;
            case DowntimeDetection::PROVIDER :
                $input[Entity::VPA_HANDLE] = $value;
                break;
        }

        $downtime = $this->repo->gateway_downtime->fetchMostRecentActive($input);

        if(empty($downtime) === true)
        {
            return null;
        }
        else {
            $downtimeArray = $downtime->toArray();

            return $downtimeArray['begin'];
        }
    }

}
