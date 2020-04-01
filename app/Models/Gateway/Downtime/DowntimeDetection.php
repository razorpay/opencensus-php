<?php


namespace RZP\Models\Gateway\Downtime;


use App;
use \stdClass;
use Carbon\Carbon;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Redis;

use RZP\Exception;
use RZP\Trace\TraceCode;
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
     * @var RedisManager
     */
    protected $redis;

    const ISSUER = 'ISSUER';

    const NETWORK = 'NETWORK';

    const SUCCESS_RATE = 'success_rate';

    const PAYMENT_INTERVAL = 'payment_interval';

    public function __construct()
    {
        /**
         * @var $app Application
         */
        $app = App::getFacadeRoot();

        $this->trace   = $app['trace'];

        $this->redis   = Redis::connection()->client();
    }

    protected function initConfigurationSettings($type, $key, $value, $settingType)
    {
        $settings = $this->loadSettingsFromRedis($type, $key, $value, $settingType);

        if (empty($settings) === true)
        {
            // Alert for manual action if no configuration exist for key.
            $this->trace->error(
                TraceCode::GATEWAY_DOWNTIME_CONFIGURATION_V2_SETTINGS_MISSING,
                [
                    'type' => $type,
                    'key' => $key,
                    'value' => $value,
                    'setting_type' => $settingType,
                ]
            );

            throw new Exception\LogicException('Gateway Downtime Configuration v2 missing.');
        }

        return $settings;
    }

    protected function loadSettingsFromRedis($type, $key, $value, $settingType)
    {
        $arrayKey = $type . '_' . $key . '_' . $value . '_' . $settingType;

        $allSettings = $this->redis->hget(Constants::SETTINGS_KEY, strtolower($arrayKey));

        return json_decode($allSettings);
    }

    protected function calculateDowntimeMetric(PublicCollection $payments)
    {
        $downtimeMetric = new stdClass;
        $downtimeMetric->total_authorized = 0;
        $downtimeMetric->total_payments = 0;

        // will only be used if downtime is detected
        $downtimeMetric->downtime_start_time = null;
        if ($payments->isEmpty() == false)
        {
            $downtimeMetric->downtime_start_time = $payments->get(0)->getCreatedAt();
        }

        // will be used if downtime is resolved
        $downtimeMetric->downtime_recover_time = null;

        $merchantTotalPaymentsMap = [];

        $downtimeMetric->top_merchant_count = 0;

        foreach ($payments as $index => $payment)
        {
            if ($payment->getStatus() != Status::CREATED)
            {
                $downtimeMetric->total_payments = $downtimeMetric->total_payments + 1;

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
            }

            if ($payment->hasBeenAuthorized())
            {
                $downtimeMetric->total_authorized = $downtimeMetric->total_authorized + 1;

                // First failed payment after all success payment will be assumed as downtime start time.
                $nextPayment = $payments->get($index+1);
                $downtimeMetric->downtime_start_time = (empty($nextPayment) == false) ? $nextPayment->getCreatedAt() : null;

                if (empty($downtimeMetric->downtime_recover_time) == true)
                {
                    // First success payment will be assumed as downtime resolve time.
                    $downtimeMetric->downtime_recover_time = $payment->getCreatedAt();
                }
            }
        }

        return $downtimeMetric;
    }

    public function createDowntimeIfNecessary($type, $key, $value)
    {
        $redisKeyForDowntime = Constants::DOWNTIME_KEY . '_' . $type . '_' . $key .'_' . $value;

        $key = strtoupper($key);

        $value = strtoupper($value);

        // check if downtime is already there for this issuer
        $downtimeCreatedSince = $this->redis->get($redisKeyForDowntime);

        if (empty($downtimeCreatedSince) == true)
        {
            // if downtime is not present let's check if it's needed to be created.
            $createSettings = $this->initConfigurationSettings($type, $key, $value, 'create');

            foreach ($createSettings as $setting)
            {
                $maxWindowSizeInSeconds = $setting[0];

                $minimumPayments = $setting[1];

                $successRateForDowntime = $setting[2];

                $to = Carbon::now();

                $from = Carbon::now()->subSeconds($maxWindowSizeInSeconds);

                $payments = (new \RZP\Models\Payment\Repository())->fetchLastNPaymentsForDowntime($from->timestamp, $to->timestamp, $key, $value, $minimumPayments);

                $metric = $this->calculateDowntimeMetric($payments);

                $totalAuthorized = $metric->total_authorized;

                $totalPayments = $metric->total_payments;

                $this->trace->info(TraceCode::GATEWAY_DOWNTIME_CONFIGURATION_V2_METRIC,
                    [
                        'type' => $type,
                        'key' => $key,
                        'value' => $value,
                        'setting_type' => 'create',
                        'downtime_start_time' => $metric->downtime_start_time,
                        'top_merchant_count' => $metric->top_merchant_count,
                        'maxWindowSizeInSeconds' => $maxWindowSizeInSeconds,
                        'minimumPayments' => $minimumPayments,
                        'successRateForDowntime' => $successRateForDowntime,
                        'totalAuthorized' => $totalAuthorized,
                        'totalPayments' => $totalPayments,

                    ]);

                if ($totalPayments < $minimumPayments)
                {
                    continue;
                }

                $successRate = $this->checkPercentage($totalAuthorized, $totalPayments);

                if ($successRate <= $successRateForDowntime)
                {
                    //check if more than 50% of the payments are not of single merchant
                    if ($metric->top_merchant_count > (Constants::getMaxSingleMerchantContribution() * $minimumPayments))
                    {
                        continue;
                    }

                    //todo: more than 50% of the payments are not of error cancelled_by_user

                    if (empty($metric->downtime_start_time) == true)
                    {
                        // downtime start time can be null if last payment was success.
                        // Do not create downtime in this case.
                        //
                        continue;
                    }

                    $this->trace->info(TraceCode::GATEWAY_DOWNTIME_CONFIGURATION_V2_DOWNTIME_DETECTED,
                        [
                            'type' => $type,
                            'key' => $key,
                            'value' => $value,
                            'downtime_start_time' => $metric->downtime_start_time,
                            'maxWindowSizeInSeconds' => $maxWindowSizeInSeconds,
                            'minimumPayments' => $minimumPayments,
                            'successRateForDowntime' => $successRateForDowntime,
                        ]);


                    // set downtime in redis
                    // plus 30 because payment was created at $metric->downtime_start_time but failed at later time.
                    $this->redis->set($redisKeyForDowntime, $metric->downtime_start_time + 30);
                }

                break;
            }
        }
        else
        {
            // If downtime is present, let's check if it can be resolved.

            //TODO: If $downtimeCreatedSince is more then 5 hour. send slack notification.
            // Because There has never been metric downtime for this long.

            $resolveSetting = $this->initConfigurationSettings($type, $key, $value, 'resolve')[0];

            $minimumPayments = $resolveSetting[0];

            $successRateToResolve = $resolveSetting[1];

            // from and to are not needed here.
            $payments = (new \RZP\Models\Payment\Repository())->fetchLastNPaymentsForDowntime(($downtimeCreatedSince-30), null, $key, $value, $minimumPayments);

            $metric = $this->calculateDowntimeMetric($payments);

            $totalAuthorized = $metric->total_authorized;

            $totalPayments = $metric->total_payments;

            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_CONFIGURATION_V2_METRIC,
                [
                    'type' => $type,
                    'key' => $key,
                    'value' => $value,
                    'setting_type' => 'resolve',
                    'downtime_start_time' => $downtimeCreatedSince,
                    'downtime_recover_time' => $metric->downtime_recover_time,
                    'top_merchant_count' => $metric->top_merchant_count,
                    'minimumPayments' => $minimumPayments,
                    'successRateToResolve' => $successRateToResolve,
                    'totalAuthorized' => $totalAuthorized,
                    'totalPayments' => $totalPayments,
                ]);

            if ($totalPayments < $minimumPayments)
            {
                return;
            }

            $successRate = $this->checkPercentage($totalAuthorized, $totalPayments);

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
                        'downtime_start_time' => $downtimeCreatedSince,
                        'downtime_recover_time' => $metric->downtime_recover_time,
                        'minimumPayments' => $minimumPayments,
                        'successRateToResolve' => $successRateToResolve,
                    ]);

                // delete redis key to resolve
                $this->redis->del([$redisKeyForDowntime]);
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
}
