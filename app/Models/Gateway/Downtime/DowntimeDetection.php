<?php


namespace RZP\Models\Gateway\Downtime;


use App;
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

    public function __construct()
    {
        /**
         * @var $app Application
         */
        $app = App::getFacadeRoot();

        $this->trace   = $app['trace'];

        $this->razorx  = $app['razorx'];

        $this->mode    = $app['rzp.mode'];

        $this->redis   = Redis::connection()->client();
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

        // will only be used if downtime is detected
        $downtimeMetric->downtime_start_time = null;

        // will be used if downtime is resolved
        $downtimeMetric->downtime_recover_time = null;

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

                // First success payment will be assumed as downtime resolve time.
                $downtimeMetric->downtime_recover_time = $payment->getCreatedAt();
            }
            else
            {
                // First failed payment after all success payment will be assumed as downtime start time.
                $downtimeMetric->downtime_start_time = $payment->getCreatedAt();
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
        $redisKeyForDowntime = Constants::DOWNTIME_KEY . '_' . $type .'_' . $method . '_' . $key .'_' . $value;

        // check if downtime is already there for this issuer
        $downtimeCreatedSince = $this->redis->get($redisKeyForDowntime);

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
                    $payments = (new \RZP\Models\Payment\Repository())->fetchLastNPaymentsForDowntime($from->timestamp, $to->timestamp, $type, $key, $value, $minimumPayments);
                }
                else if ($method === Method::NETBANKING)
                {
                    $payments = (new \RZP\Models\Payment\Repository())->fetchLastNNetbankingPaymentsForDowntime($from->timestamp, $to->timestamp, $type, $key, $value, $minimumPayments);
                }
                else if ($method === Method::UPI)
                {
                    $payments = (new \RZP\Models\Payment\Repository())->fetchLastNUpiPaymentsForDowntime($from->timestamp, $to->timestamp, $type, $key, $value, $minimumPayments);
                }
                else
                {
                    new Exception\LogicException("Method not supported yet.");
                }

                $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_V2_QUERY_COMPLETED);

                $metric = $this->calculateDowntimeMetric($payments, $type);

                $numerator = $metric->numerator;

                $denominator = $metric->denominator;

                $this->trace->info(TraceCode::GATEWAY_DOWNTIME_CONFIGURATION_V2_METRIC,
                    [
                        'type'                      => $type,
                        'method'                    => $method,
                        'key'                       => $key,
                        'value'                     => $value,
                        'downtime_start_time'       => $metric->downtime_start_time,
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
                            'downtime_start_time'       => $metric->downtime_start_time,
                            'windowSizeInSeconds'       => $windowSizeInSeconds,
                            'minimumPayments'           => $minimumPayments,
                            'successRateForDowntime'    => $successRateForDowntime,
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
            // Because There has never been downtime for this long.

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
                $payments = (new \RZP\Models\Payment\Repository())->fetchLastNPaymentsForDowntime($from, $to, $type, $key, $value, $minimumPayments);
            }
            else if ($method === Method::NETBANKING)
            {
                $payments = (new \RZP\Models\Payment\Repository())->fetchLastNNetbankingPaymentsForDowntime($from, $to, $type, $key, $value, $minimumPayments);
            }
            else if ($method === Method::UPI)
            {
                $payments = (new \RZP\Models\Payment\Repository())->fetchLastNUpiPaymentsForDowntime($from, $to, $type, $key, $value, $minimumPayments);
            }

            else
            {
                new Exception\LogicException("Method not supported yet.");
            }

            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_V2_QUERY_COMPLETED);

            $metric = $this->calculateDowntimeMetric($payments, $type);

            $numerator = $metric->numerator;

            $denominator = $metric->denominator;

            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_CONFIGURATION_V2_METRIC,
                [
                    'type' => $type,
                    'method' => $method,
                    'key' => $key,
                    'value' => $value,
                    'setting_type' => 'resolve',
                    'downtime_start_time' => $downtimeCreatedSince,
                    'downtime_recover_time' => $metric->downtime_recover_time,
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
