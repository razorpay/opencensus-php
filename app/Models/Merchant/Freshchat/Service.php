<?php


namespace RZP\Models\Merchant\Freshchat;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Http\Controllers\CareProxyController;

class Service extends Base\Service
{
    const DEFAULT_CHAT_TIMINGS_CONFIG = [
        0 => ['start' => 600, 'end' => 1320],
        1 => ['start' => 600, 'end' => 1320],
        2 => ['start' => 600, 'end' => 1320],
        3 => ['start' => 600, 'end' => 1320],
        4 => ['start' => 600, 'end' => 1320],
        5 => ['start' => 600, 'end' => 1320],
        6 => ['start' => 600, 'end' => 1320],
    ];

    const DEFAULT_CHAT_HOLIDAYS_CONFIG = [
        [
            'day'   => 13,
            'month' => 4,
            'year'  => 2021,
        ],
    ];

    public function putChatTimingsConfig($input)
    {
        $this->app['trace']->info(TraceCode::PUT_FRESHCHAT_TIMINGS_CONFIG, $input);

        (new Validator)->validateInput('put_chat_timings_config', $input);

        $config = $input[Constants::CONFIG];

        $this->app['cache']->forever(Constants::CHAT_TIMINGS_CACHE_CONFIG_KEY, $config);

        return $config;
    }

    public function getChatTimingsConfig(): array
    {
        $config = $this->app['cache']->get(Constants::CHAT_TIMINGS_CACHE_CONFIG_KEY);

        if ($config === null)
        {
            return self::DEFAULT_CHAT_TIMINGS_CONFIG;
        }

        return $config;
    }

    public function putChatHolidaysConfig(array $input)
    {
        $this->app['trace']->info(TraceCode::PUT_FRESHCHAT_HOLIDAYS_CONFIG, $input);

        (new Validator)->validateInput('put_chat_holidays_config', $input);

        $config = $input[Constants::CONFIG];

        $this->app['cache']->forever(Constants::CHAT_HOLIDAYS_CACHE_CONFIG_KEY, $config);

        return $config;
    }

    public function getChatHolidaysConfig()
    {
        $config = $this->app['cache']->get(Constants::CHAT_HOLIDAYS_CACHE_CONFIG_KEY);

        if ($config === null)
        {
            return self::DEFAULT_CHAT_HOLIDAYS_CONFIG;
        }

        return $config;
    }

    public function isChatEnabledNow() : bool
    {
        $properties = [
            'id'            => $this->merchant->getId(),
            'experiment_id' => $this->app['config']->get('app.care_chat_migration_splitz_experiment_id'),
        ];

        $response = $this->app['splitzService']->evaluateRequest($properties);

        $variant = $response['response']['variant']['name'] ?? '';

        $this->trace->info(TraceCode::FRESHCHAT_CARE_MIGRATION_EXPERIMENT_VARIANT, ['variant' => $variant]);

        if ($variant == Constants::ENABLE)
        {
            $response = $this->app['care_service']->dashboardProxyRequest(CareProxyController::CHAT_CHECK_AVAILABILITY, []);

            return $response['is_available'];
        }

        return (($this->isValidChatTiming() === true) and
                ($this->isHolidayForChat() === false));
    }

    public function isValidChatTiming(): bool
    {
        $now = Carbon::now(Timezone::IST);

        $minuteOfDay = $now->hour * 60 + $now->minute;

        $todayConfig = $this->getChatTimingsConfig()[$now->dayOfWeek];

        $todayStart = $todayConfig[Constants::START];

        $todayEnd = $todayConfig[Constants::END];

        if (($minuteOfDay > $todayEnd) or
            ($minuteOfDay < $todayStart))
        {
            return false;
        }

        return true;
    }

    public function isHolidayForChat() : bool
    {
        $holidays = $this->getChatHolidaysConfig();

        $now = Carbon::now(Timezone::IST);

        foreach ($holidays as $holiday)
        {
           if (($holiday[Constants::DAY] === $now->day) and
               ($holiday[Constants::MONTH] == $now->month) and
               ($holiday[Constants::YEAR] === $now->year))
           {
               return true;
           }
        }
        return false;
    }
}
