<?php

namespace RZP\Models\FundLoadingDowntime;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundLoadingDowntime\Notifications as Notification;

class Core extends \RZP\Models\Base\Core
{
    public function create($input)
    {
        $input['created_by'] = $this->app['basicauth']->getAdmin()->getId();
        // comment above line and uncomment below line for testing in local using private auth
        //$input['created_by'] = $this->app['basicauth']->getMerchant()->getId();
        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_CREATE_REQUEST, ['input' => $input]);

        $duplicateDowntime = $this->repo->fund_loading_downtimes->getSimilarDowntime($input);

        if (empty($duplicateDowntime) === false)
        {
            $this->trace->info(
                TraceCode::DUPLICATE_FUND_LOADING_DOWNTIME_WHILE_CREATE,
                [
                    Constants::ID => $duplicateDowntime->getPublicId(),
                ]
            );

            return $duplicateDowntime;
        }

        $downtime = (new Entity)->build($input);

        $this->repo->fund_loading_downtimes->saveOrFailEntity($downtime);

        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_CREATED,
                           [
                               'id'     => $downtime->getId(),
                               'values' => $downtime->toArrayPublic()
                           ]);

        return $downtime;
    }

    public function update($id, $input)
    {
        $id         = trim($id);
        $downtimeId = Entity::verifyIdAndSilentlyStripSign($id);

        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_UPDATE_REQUEST,
                           [
                               'input' => $input,
                               'id'    => $downtimeId
                           ]);

        if (empty($input) === true)
        {
            throw new BadRequestValidationFailureException("Nothing to update");
        }

        $downtime = $this->repo->fund_loading_downtimes->findOrFailPublic($downtimeId);

        $duplicateDowntime = $this->repo->fund_loading_downtimes->getSimilarDowntime($input, $downtime);

        if (empty($duplicateDowntime) === false)
        {
            $this->trace->info(TraceCode::DUPLICATE_FUND_LOADING_DOWNTIME_WHILE_UPDATE,
                               [
                                   Entity::ID      => $duplicateDowntime->getPublicId(),
                                   'update_params' => $input,
                               ]
            );

            return $duplicateDowntime;
        }

        $downtime->edit($input, 'update');

        $this->repo->fund_loading_downtimes->saveOrFailEntity($downtime);

        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_UPDATED,
                           [
                               Entity::ID => $downtime->getPublicId(),
                           ]
        );

        return $downtime;
    }

    public function listAllDowntimes($input)
    {
        return $this->repo->fund_loading_downtimes->fetch($input);
    }

    public function fetch($id)
    {
        $downtimeId = Entity::verifyIdAndSilentlyStripSign($id);

        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_FETCH_BY_ID, ['id' => $downtimeId]);

        return $this->repo->fund_loading_downtimes->findOrFailPublic($downtimeId);
    }

    public function listActiveDowntimes($input)
    {
        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_FETCH_ACTIVE_REQUEST, ['parameters' => $input]);

        if (array_key_exists(Entity::START_TIME, $input) or
            array_key_exists(Entity::END_TIME, $input))
        {
            $downtime = $this->repo->fund_loading_downtimes->fetchDowntimeBetweenTimestamp($input);
        }
        else
        {
            $downtime = $this->repo->fund_loading_downtimes->fetchByCurrentTime($input);
        }

        return $downtime;
    }

    public function delete(Entity $downtime)
    {
        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_DELETE_REQUEST, ['id' => $downtime->getId()]);

        $this->repo->fund_loading_downtimes->deleteOrFail($downtime);

        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_DELETED, ['id' => $downtime->getId()]);
    }

    public function createMultipleDowntimesAndNotify($input, $flowType)
    {
        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_MULTIPLE_CREATE_REQUEST,
                           [
                               'input'     => $input,
                               'flow_type' => $flowType
                           ]
        );

        $modesAndTimes = array_pull($input[Constants::DOWNTIME_INPUTS], Constants::DURATIONS_AND_MODES);

        $notifyRequest = $this->buildNotifyRequest($input);

        $downtimeInput = $input[Constants::DOWNTIME_INPUTS];

        try
        {
            $notifyRequest = $this->repo->transaction(function() use ($downtimeInput, $modesAndTimes, $notifyRequest) {
                foreach ($modesAndTimes as $modesAndTime)
                {
                    $downtimeInput[Entity::START_TIME] = $modesAndTime[Entity::START_TIME];

                    $downtimeInput[Entity::END_TIME] = empty($modesAndTime[Entity::END_TIME]) ? null : $modesAndTime[Entity::END_TIME];

                    foreach ($modesAndTime[Constants::MODES] as $mode)
                    {
                        $downtimeInput[Entity::MODE]                 = $mode;
                        $notifyRequest[Constants::DOWNTIME_INPUTS][] = $this->create($downtimeInput);
                    }
                }

                return $notifyRequest;
            });
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::FUND_LOADING_DOWNTIME_ENTITY_CREATION_FAILED
            );

            throw $exception;
        }

        return $this->sendNotifications($notifyRequest, $flowType);
    }

    public function updateMultipleDowntimesAndNotify($input, $flowType)
    {
        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_MULTIPLE_UPDATE_REQUEST,
                           [
                               'input'     => $input,
                               'flow_type' => $flowType
                           ]
        );

        $notifyRequest = $this->buildNotifyRequest($input);

        try
        {
            $notifyRequest = $this->repo->transaction(function() use ($input, $notifyRequest, $flowType) {
                foreach ($input[Constants::UPDATE_DETAILS] as $updateDetail)
                {
                    $data = [];

                    if (empty($updateDetail[Entity::START_TIME]) === false)
                    {
                        $data[Entity::START_TIME] = $updateDetail[Entity::START_TIME];
                    }

                    if (empty($updateDetail[Entity::END_TIME]) === false)
                    {
                        $data[Entity::END_TIME] = $updateDetail[Entity::END_TIME];
                    }

                    $notifyRequest[Constants::DOWNTIME_INPUTS][] = $this->update($updateDetail[Constants::ID], $data);
                }

                return $notifyRequest;
            });
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::FUND_LOADING_DOWNTIME_ENTITY_UPDATION_FAILED
            );

            throw $exception;
        }

        return $this->sendNotifications($notifyRequest, $flowType);
    }

    public function deleteMultipleDowntimesAndNotify($input, $flowType)
    {
        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_MULTIPLE_DELETE_REQUEST,
                           [
                               'input'     => $input,
                               'flow_type' => $flowType
                           ]
        );

        $notifyRequest = $this->buildNotifyRequest($input);

        try
        {
            $notifyRequest = $this->repo->transaction(function() use ($notifyRequest, $input) {
                foreach ($input[Constants::DOWNTIME_IDS] as $downtimeId)
                {
                    $downtime                                    = $this->fetch($downtimeId);
                    $notifyRequest[Constants::DOWNTIME_INPUTS][] = $downtime;

                    $this->delete($downtime);
                }

                return $notifyRequest;
            });

        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::FUND_LOADING_DOWNTIME_ENTITY_DELETION_FAILED
            );

            throw $exception;
        }

        return $this->sendNotifications($notifyRequest, $flowType);
    }

    public function buildNotifyRequest($input, $notifyRequest = [])
    {
        $notifyRequest[Notification::SEND_SMS] = $input[Notification::SEND_SMS];

        $notifyRequest[Notification::SEND_EMAIL] = $input[Notification::SEND_EMAIL];

        $notifyRequest[Constants::NOTE] = $input[Constants::NOTE] ?? null;

        return $notifyRequest;
    }


    public function sendNotifications($input, $flowType)
    {
        $downtimeInformation = $this->getDowntimeInformation($input[Constants::DOWNTIME_INPUTS]);
        unset($input[Constants::DOWNTIME_INPUTS]);
        $input[Constants::DOWNTIME_INFO] = $downtimeInformation;

        // if neither sms nor email option is selected, we return here
        if (boolval($input[Notification::SEND_SMS]) === false and boolval($input[Notification::SEND_EMAIL]) === false)
        {
            $this->trace->info(
                TraceCode::FUND_LOADING_DOWNTIME_NOTIFICATIONS_NOT_OPTED,
                [
                    Constants::DOWNTIME_INFO => $downtimeInformation,
                    Notification::SEND_EMAIL => $input[Notification::SEND_EMAIL],
                    Notification::SEND_SMS   => $input[Notification::SEND_SMS],
                ]
            );

            return [
                Constants::DOWNTIME_INFO => $downtimeInformation
            ];
        }

        // As at least one of the options (send_email,send_sms) has been selected, we go forward with the flow and send notifications
        // Either sms or email or both depending on the values of 'send_sms' and 'send_email' fields in the input
        $this->trace->info(
            TraceCode::FUND_LOADING_DOWNTIME_NOTIFICATIONS_INIT,
            [
                Constants::DOWNTIME_INFO => $downtimeInformation,
                Notification::SEND_EMAIL => $input[Notification::SEND_EMAIL],
                Notification::SEND_SMS   => $input[Notification::SEND_SMS],
            ]
        );

        $response = (new Notifications($input, $flowType))->sendNotifications();

        $this->trace->info(
            TraceCode::FUND_LOADING_DOWNTIME_NOTIFICATIONS_SENT,
            [
                Notification::SMS   => $response[Notification::SMS],
                Notification::EMAIL => $response[Notification::EMAIL],
            ]
        );

        return $response;
    }

    protected function getDowntimeInformation($downtimeInputs)
    {
        $downtimeInfo[Entity::TYPE]                   = $downtimeInputs[0]->getType();
        $downtimeInfo[Entity::SOURCE]                 = $downtimeInputs[0]->getSource();
        $downtimeInfo[Entity::CHANNEL]                = $downtimeInputs[0]->getChannel();
        $downtimeInfo[Constants::DURATIONS_AND_MODES] = $this->getDownTimesWithModes($downtimeInputs);

        return $downtimeInfo;
    }

    /** This function consumes an array of downtime entities and builds an array with downtime durations as keys along
     * with the modes that are down in that duration as the key's value.
     *
     * @return array
     * @var $downtimeInputs
     */
    protected function getDownTimesWithModes($downtimeInputs)
    {
        $timesAndModes = [];

        // Here we make a key of start_time and end_time and see if the duration is already present in the array
        // $timesAndModes. If yes we append the mode to the key, or else we make a new key in the array
        foreach ($downtimeInputs as $downtime)
        {
            $startTime = $downtime->getStartTime();
            $endTime   = $downtime->getEndTime();

            if (empty($endTime) === true)
            {
                $endTime = Constants::DEFAULT_END_TIME;
            }

            $duration = $startTime . '@' . $endTime;

            // check if the key already exists. If exists, append the mode, else, create a new key

            if (array_key_exists($duration, $timesAndModes))
            {
                array_push($timesAndModes[$duration], $downtime['mode']);
            }
            else
            {
                $timesAndModes[$duration] = [$downtime['mode']];
            }

        }

        return $this->combineModes($timesAndModes);
    }

    protected function combineModes($timesAndModes)
    {
        $combinedModes = [];

        // Here we break the start_time@end_time key and build an array with duration along with the modes that are down
        // in that duration. We also convert the epoch from string to int if the time is not 'until further notice'
        // Finally we return the array with the durations as keys and modes that are down as a string with ',' delimiter
        foreach ($timesAndModes as $key => $value)
        {
            $duration  = explode('@', $key);
            $startTime = (int) ($duration[0]);
            $endTime   = $duration[1] === Constants::DEFAULT_END_TIME ? $duration[1] : (int) ($duration[1]);
            $modes     = implode(',', $value);

            $combinedModes[] = [
                Entity::START_TIME => $startTime,
                Entity::END_TIME   => $endTime,
                Constants::MODES   => $modes
            ];
        }

        return $combinedModes;
    }
}
