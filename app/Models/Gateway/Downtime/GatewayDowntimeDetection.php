<?php

namespace RZP\Models\Gateway\Downtime;

use App;
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

    public function __construct(string $gateway)
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

    protected function loadSettingsFromRedis(): array
    {
        $allGatewaySettings = $this->redis->hgetall(self::SETTINGS_KEY);

        return json_decode(array_get($allGatewaySettings, $this->gateway));
    }

    public function gatewayDowntimeDurations(): array
    {
        if (empty($this->settings) === true)
        {
            return [];
        }

        $args = [
            file_get_contents(__DIR__ . '/LuaScripts/FailureCount.lua'),
            1,
            $this->getThrottleKey(),
        ];

        $results = $this->redis->eval(
            ...$args,
            ...$this->getAllWindows()
        );

        $durations = [];
        for ($i = 0; $i < count($results); $i++)
        {
            $this->updateDurationIfApplicable(
                $results[$i][0], // All Attempts in given time window.
                $results[$i][1], // Failure Attempts in given time window.
                // Threshold Failure Percentage in window i from settings configuration.
                $this->settings[($i/2)][1],
                // Threshold Attempts in window i from settings configuration.
                $this->settings[($i/2)][2],
                // Duration for which downtime has to be created.
                $this->settings[($i/2)][3],
                $durations);
        }

        return $durations;
    }

    public function incrementTotalAttempts()
    {
        if (empty($this->settings) === true)
        {
            return;
        }

        $args = [
            file_get_contents(__DIR__ . '/LuaScripts/AllAttemptsCount.lua'),
            1,
            $this->getThrottleKey(),
        ];

        $this->redis->eval(
            ...$args,
            ...$this->getAllWindows()
        );
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
                return $conf[0];
            },
            $this->settings);
    }

    protected function updateDurationIfApplicable(int $allAttempts,
                                      int $totalFailure,
                                      int $thresholdFailurePercentage,
                                      int $thresholdAllAttempts,
                                      int $downtimeDuration,
                                      array &$durations)
    {
        if ($allAttempts < $thresholdAllAttempts)
        {
            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_DISALLOWED, [
                'all_failure'                       => $allAttempts,
                'total_failure'                     => $totalFailure,
                'threshold_failure_percentage'      => $thresholdFailurePercentage,
                'threshold_all_attempts'            => $thresholdAllAttempts,
                'downtime_duration'                 => $downtimeDuration,
                'durations'                         => $durations,
            ]);

            return;
        }

        $failurePercentage = ($totalFailure/$allAttempts) * 100;

        if ($failurePercentage < $thresholdFailurePercentage)
        {
            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_DISALLOWED, [
                'all_failure'                       => $allAttempts,
                'total_failure'                     => $totalFailure,
                'threshold_failure_percentage'      => $thresholdFailurePercentage,
                'threshold_all_attempts'            => $thresholdAllAttempts,
                'downtime_duration'                 => $downtimeDuration,
                'durations'                         => $durations,
            ]);

            return;
        }

        $durations[] = $downtimeDuration;

        return;
    }
}
