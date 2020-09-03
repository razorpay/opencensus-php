<?php

namespace RZP\Models\Gateway\Downtime;

use App;
use function Clue\StreamFilter\append;
use Illuminate\Redis\RedisManager;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Support\Facades\Redis;

use RZP\Trace\TraceCode;
use RZP\Foundation\Application;
use RZP\Models\Admin\ConfigKey;

/**
 * Detect Gateway Downtime based on error percentage in last given time window,
 * if also total error have crossed a threshold value.
 *
 * Approach:
 *
 * 1.
 *
 */
class GatewayDowntimeDetection
{
    /**
     * @var Trace
     */
    protected $trace;

    /**
     * @var RedisManager
     */
    protected $redis;

    protected $gateway;

    protected $mode;

    protected $settings;

    const SETTINGS_KEY  = ConfigKey::DOWNTIME_DETECTION_CONFIGURATION;

    const PREFIX_KEY    = ConfigKey::DOWNTIME_DETECTION;

    public function __construct(string $gateway = null)
    {
        /** @var $app Application */
        $app = App::getFacadeRoot();

        $this->trace   = $app['trace'];

        $this->redis   = Redis::connection()->client();

        $this->gateway = $gateway;

        $this->initConfigurationSettings();

        $this->mode    = $app['rzp.mode'];
    }

    protected function initConfigurationSettings()
    {
        $settings = $this->loadSettingsFromRedis();

        if (empty($settings) === true)
        {
            // Alert for manual action if no configuration exist for gateway.
            $this->trace->warning(TraceCode::GATEWAY_DOWNTIME_CONFIGURATION_SETTINGS_MISSING,
                ['gateway' => $this->gateway]);
        }

        $this->settings = $settings;
    }

    protected function loadSettingsFromRedis()
    {
        $allGatewaySettings = $this->redis->hgetall(self::SETTINGS_KEY);

        if ($this->gateway !=  null)
        {
            return json_decode(array_get($allGatewaySettings, $this->gateway));
        }
        else
        {
            return null;
        }
    }

    /**
     * This function returns the array of durations
     * for which respective downtime should be created.
     *
     * This also updates the total attempts and total failure in redis.
     * @param int $count total failure count of a error code
     * @return array
     */
    public function gatewayDowntimeDurations(int $count): array
    {
        if (empty($this->settings) === true)
        {
            return [];
        }

        $args = [
            file_get_contents(__DIR__ . '/LuaScripts/FailureCount.lua'),
            1,
            $this->getThrottleKey(),
            $count,
        ];

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_EXECUTING_FAILURE_COUNT, [
            'key'       => $this->getThrottleKey(),
            'windows'   => $this->getAllWindows(),
        ]);

        $allWindows = $this->getAllWindows();

        $results = $this->redis->eval(
            ...$args,
            ...$allWindows
        );

        $durations = [];
        for ($i = 0; $i < count($results); $i++)
        {
            $this->updateDurationIfDowntimeDetected(
                $results[$i][0], // All Attempts in given time window.
                $results[$i][1], // Failure Attempts in given time window.
                $allWindows[$i],
                // Threshold Failure Percentage in window i from settings configuration.
                $this->settings[($i)][1],
                // Threshold Attempts in window i from settings configuration.
                $this->settings[($i)][2],
                // Duration for which downtime has to be created.
                $this->settings[($i)][3],
                $durations);
        }

        return $durations;
    }

    /**
     * This function increment the total attempts in redis.
     * which will be used for downtime detection.
     * @param int $count
     */
    public function incrementTotalAttempts(int $count)
    {
        if (empty($this->settings) === true)
        {
            return;
        }

        $args = [
            file_get_contents(__DIR__ . '/LuaScripts/AllAttemptsCount.lua'),
            1,
            $this->getThrottleKey(),
            $count
        ];

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_EXECUTING_ATTEMPTS_COUNT, [
            'key'       => $this->getThrottleKey(),
            'windows'   => $this->getAllWindows(),
        ]);

        $this->redis->eval(
            ...$args,
            ...$this->getAllWindows()
        );
    }

    /**
     * This function
     */
    public function purgeKeys()
    {
        $allGatewaySettings = $this->redis->hgetall(self::SETTINGS_KEY);

        foreach ($allGatewaySettings as $gateway => $configuration)
        {
            $this->gateway = $gateway;

            $this->settings = json_decode($configuration);

            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_PURGE_INITIATED, [
                'gateway'                           => $gateway,
                'settings'                          => $this->settings,
            ]);

            $args = [
                file_get_contents(__DIR__ . '/LuaScripts/PurgeExpiredCount.lua'),
                1,
                $this->getThrottleKey(),
            ];

            $keysPurged = $this->redis->eval(
                ...$args,
                ...$this->getAllWindows()
            );

            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_PURGE_COMPLETED, [
                'gateway'                           => $gateway,
                'settings'                          => $this->settings,
                'keys_purged'                       => $keysPurged,
            ]);
        }
    }

    protected function getThrottleKey(): string
    {
        $args = [
            self::PREFIX_KEY,
            $this->mode,
            $this->gateway,
        ];

        return implode(':', $args);
    }

    protected function getAllWindows(): array
    {
        return array_map(
            function($conf) {
                // return window length in seconds
                return $conf[0];
            },
            $this->settings);
    }

    /**
     * This function updates the downtime duration array
     * if a new downtime is detected for creation.
     *
     * @param int $allAttempts All Attempts in given time window.
     * @param int $totalFailure Failure Attempts in given time window.
     * @param int $window
     * @param int $thresholdFailurePercentage Threshold Failure Percentage
     * in window i from settings configuration.
     * @param int $thresholdAllAttempts Threshold Attempts in window i
     * from settings configuration.
     * @param int $downtimeDuration Duration for which downtime has to be created.
     * @param array $durations
     */
    protected function updateDurationIfDowntimeDetected(int $allAttempts,
                                                        int $totalFailure,
                                                        int $window,
                                                        int $thresholdFailurePercentage,
                                                        int $thresholdAllAttempts,
                                                        int $downtimeDuration,
                                                        array &$durations)
    {
        if ($allAttempts < $thresholdAllAttempts)
        {
            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_DISALLOWED, [
                'all_attempts'                      => $allAttempts,
                'total_failure'                     => $totalFailure,
                'window'                            => $window,
                'threshold_failure_percentage'      => $thresholdFailurePercentage,
                'threshold_all_attempts'            => $thresholdAllAttempts,
                'downtime_duration'                 => $downtimeDuration,
                'durations'                         => $durations,
                'gateway'                           => $this->gateway,
            ]);

            return;
        }

        $failurePercentage = ($totalFailure/$allAttempts) * 100;

        if ($failurePercentage < $thresholdFailurePercentage)
        {
            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_DISALLOWED, [
                'all_attempts'                      => $allAttempts,
                'total_failure'                     => $totalFailure,
                'window'                            => $window,
                'threshold_failure_percentage'      => $thresholdFailurePercentage,
                'threshold_all_attempts'            => $thresholdAllAttempts,
                'downtime_duration'                 => $downtimeDuration,
                'durations'                         => $durations,
                'gateway'                           => $this->gateway,
            ]);

            return;
        }

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_ALLOWED, [
            'all_attempts'                       => $allAttempts,
            'total_failure'                     => $totalFailure,
            'window'                            => $window,
            'threshold_failure_percentage'      => $thresholdFailurePercentage,
            'threshold_all_attempts'            => $thresholdAllAttempts,
            'downtime_duration'                 => $downtimeDuration,
            'durations'                         => $durations,
            'gateway'                           => $this->gateway,
        ]);

        $durations[] = $downtimeDuration;

        return;
    }
}
