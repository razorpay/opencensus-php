<?php


namespace RZP\Models\Merchant\Freshchat;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $putChatTimingsConfigRules = [
        Constants::CONFIG        => 'required|array|size:7',
        Constants::CONFIG . '.*' => 'custom:chat_timing_config',
    ];

    protected static $putChatTimingConfigRules = [
        Constants::START => 'required|integer|min:0|max:1439',
        Constants::END   => 'required|integer|min:0|max:1439|gte:start',
    ];

    protected static $putChatHolidaysConfigRules = [
        Constants::CONFIG        => 'required|array',
        Constants::CONFIG . '.*' => 'custom:chat_holiday_config',
    ];

    protected static $putChatHolidayConfigRules = [
        Constants::DAY           => 'required|int|min:1|max:31', //not validating invalid days like 31 feb etc to avoid complexity
        Constants::MONTH         => 'required|int|min:1|max:12',
        Constants::YEAR          => 'required|int',
    ];


    protected function validateChatTimingConfig($attribute, $input)
    {
        $this->validateInput('put_chat_timing_config', $input);
    }

    protected function validateChatHolidayConfig($attribute, $input)
    {
        $this->validateInput('put_chat_holiday_config', $input);
    }
}