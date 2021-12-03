<?php

namespace RZP\Models\FundLoadingDowntime;

use RZP\Models\Payout\Mode as Modes;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends \RZP\Base\Validator
{
    protected static $createRules = [
        Entity::TYPE             => 'required|string|in:Scheduled Maintenance Activity,Sudden Downtime',
        Entity::SOURCE           => 'required|string|in:Partner Bank,RBI,NPCI',
        Entity::CHANNEL          => 'required|string|custom',
        Entity::MODE             => 'required|string|custom',
        Entity::START_TIME       => 'required|epoch',
        Entity::END_TIME         => 'sometimes|required_with:start_time|epoch',
        Entity::DOWNTIME_MESSAGE => 'sometimes|required|string',
        Entity::CREATED_BY       => 'required|string|max:255',
    ];

    protected static $updateRules = [
        Entity::TYPE             => 'sometimes|required|string|in:Scheduled Maintenance Activity,Sudden Downtime',
        Entity::SOURCE           => 'sometimes|required|string|in:Partner Bank,RBI,NPCI',
        Entity::CHANNEL          => 'sometimes|required|string|custom',
        Entity::MODE             => 'sometimes|required|string|custom',
        Entity::START_TIME       => 'sometimes|required|epoch',
        Entity::END_TIME         => 'sometimes|required_with:start_time|epoch',
        Entity::DOWNTIME_MESSAGE => 'sometimes|required|string',
        Entity::CREATED_BY       => 'sometimes|required|string|max:255',
    ];


    public function getChannels()
    {
        return [
            Constants::YES_BANK,
            Constants::ICICI_BANK,
            Constants::ALL,
        ];
    }

    public function getModes()
    {
        return [
            Modes::NEFT,
            Modes::IMPS,
            Modes::RTGS,
            Modes::UPI,
            Modes::IFT,
        ];
    }

    protected function validateChannel(string $attribute, string $channel)
    {
        if (in_array($channel, self::getChannels(), true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid channel name: ' . $channel);
        }
    }

    protected function validateMode(string $attribute, string $mode)
    {
        if (in_array($mode, self::getModes(), true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid mode name: ' . $mode);
        }
    }
}
