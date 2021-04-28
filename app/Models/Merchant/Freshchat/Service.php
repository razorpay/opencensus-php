<?php


namespace RZP\Models\Merchant\Freshchat;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;

class Service extends Base\Service
{
    const DEFAULT_CHAT_TIMINGS_CONFIG = [
        0 => ['start' => 540, 'end' => 1260],
        1 => ['start' => 540, 'end' => 1260],
        2 => ['start' => 540, 'end' => 1260],
        3 => ['start' => 540, 'end' => 1260],
        4 => ['start' => 540, 'end' => 1260],
        5 => ['start' => 0, 'end' => 0],
        6 => ['start' => 0, 'end' => 0],
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