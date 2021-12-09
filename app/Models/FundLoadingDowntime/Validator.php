<?php

namespace RZP\Models\FundLoadingDowntime;

use RZP\Models\Payout\Mode as Modes;
use RZP\Models\FundLoadingDowntime\Entity as E;
use RZP\Models\FundLoadingDowntime\Constants as C;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundLoadingDowntime\Notifications as Notifications;

class Validator extends \RZP\Base\Validator
{
    const CREATION_FLOW     = 'creation_flow';
    const UPDATION_FLOW     = 'updation_flow';
    const CANCELLATION_FLOW = 'cancellation_flow';

    protected static $createRules = [
        E::TYPE             => 'required|string|in:Scheduled Maintenance Activity,Sudden Downtime',
        E::SOURCE           => 'required|string|in:Partner Bank,RBI,NPCI',
        E::CHANNEL          => 'required|string|custom',
        E::MODE             => 'required|string|custom',
        E::START_TIME       => 'required|epoch',
        E::END_TIME         => 'sometimes|nullable|epoch',
        E::DOWNTIME_MESSAGE => 'sometimes|string',
        E::CREATED_BY       => 'required|string|max:255',
    ];

    protected static $updateRules = [
        E::START_TIME => 'sometimes|epoch',
        E::END_TIME   => 'sometimes|epoch',
    ];

    protected static $creationFlowRules = [
        C::DOWNTIME_INPUTS                                                          => 'required',
        Notifications::SEND_SMS                                                     => 'present|boolean',
        Notifications::SEND_EMAIL                                                   => 'present|boolean',
        C::DOWNTIME_INPUTS . '.' . C::DURATIONS_AND_MODES                           => 'required|array|min:1',
        C::DOWNTIME_INPUTS . '.' . C::DURATIONS_AND_MODES . '.*.' . E::START_TIME   => 'required|epoch',
        C::DOWNTIME_INPUTS . '.' . C::DURATIONS_AND_MODES . '.*.' . E::END_TIME     => 'sometimes|nullable|epoch',
        C::DOWNTIME_INPUTS . '.' . C::DURATIONS_AND_MODES . '.*.' . C::MODES        => 'required|array|min:1',
        C::DOWNTIME_INPUTS . '.' . C::DURATIONS_AND_MODES . '.*.' . C::MODES . '.*' => 'required|string|in:IMPS,NEFT,RTGS,UPI,IFT',
    ];

    protected static $updationFlowRules = [
        C::UPDATE_DETAILS                         => 'required|array|min:1',
        Notifications::SEND_SMS                   => 'present|boolean',
        Notifications::SEND_EMAIL                 => 'present|boolean',
        C::UPDATE_DETAILS . '.*.' . E::ID         => 'required|string',
        C::UPDATE_DETAILS . '.*.' . E::START_TIME => 'sometimes|nullable|epoch',
        C::UPDATE_DETAILS . '.*.' . E::END_TIME   => 'sometimes|nullable|epoch',
    ];

    protected static $cancellationFlowRules = [
        C::DOWNTIME_IDS           => 'required|array|min:1',
        C::DOWNTIME_IDS . ".*"    => 'required|string',
        Notifications::SEND_SMS   => 'present|boolean',
        Notifications::SEND_EMAIL => 'present|boolean',
    ];

    public function getChannels()
    {
        return [
            C::YES_BANK,
            C::ICICI_BANK,
            C::ALL,
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
