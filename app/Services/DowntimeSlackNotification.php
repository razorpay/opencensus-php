<?php

namespace RZP\Services;


use RZP\Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use RZP\Constants\Timezone;
use RZP\Http\Request\Requests;
use RZP\Models\Gateway\Downtime\Constants;
use RZP\Models\Gateway\Downtime\Entity;
use RZP\Models\Gateway\Downtime\Webhook\Constants\DowntimeService;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Gateway\Downtime\Core;

class DowntimeSlackNotification
{

    protected $app;

    protected $config;

    protected $trace;

    protected $defaultResponse;

    protected $slackChannelConfig;

    protected $redis;

    protected $razorx;

    private $DOWNTIME_BASE_TEMPLATE = '`[$severity]  $heading` '."\n".'```Method : $method'."\n".'Start Time : $startTime'."\n".'$additionalFields```';

    private $MERCHANT_DETAILS_TEMPLATE = 'Merchant Name: $merchantName'."\n".'Merchant Id : $merchantId';

    private $DOWNTIME_BASE_TEMPLATE_WITH_LOOKER_LINK = '`[$severity]  $heading` '."\n".'```Method : $method'."\n".'Start Time : $startTime'."\n".'$additionalFields```'."\n".'<$lookerLink|Looker Dashboard>';

    private $RESOLUTION_TEMPLATE = 'End Time: $endTime'."\n".'Duration : $duration minutes';

    public function __construct($app)
    {
        $this->trace = $app['trace'];
        $this->config = $app['config']->get('applications.gateway_downtime.slack');
        $this->razorx = $app['razorx'];

        $this->slackChannelConfig = [
            [
                'channel' => 'C0243P7P7H7',
                'emitTransitions' => true,
                "allowedTypes" => ['PLATFORM', 'PLTF', 'MERCHANT']
            ],
            [
                'channel' => 'C023ABXTCGP',
                'emitTransitions' => false,
                "allowedTypes" => ['MERCHANT_', 'SRM']
            ]
        ];

        if ($app->environment('production') === true)
        {
            $this->slackChannelConfig =  [
                [
                    'channel' => 'C01B1J4N1E1',
                    'emitTransitions' => true,
                    "allowedTypes" => ['PLATFORM', 'PLTF']
                ],
                [
                    'channel' => 'CQHNFF004',
                    'emitTransitions' => false,
                    "allowedTypes" => ['MERCHANT_', 'SRM_']
                ],
                [
                    'channel' => 'C0259FLRRAS',
                    'emitTransitions' => true,
                    "allowedTypes" => ['MERCHANT', 'SRM']
                ]
            ];
        }


        $this->redis = Redis::Connection('mutex_redis');
    }

    public function notifyPaymentDowntime($downtime): void
    {
        $this->validateRequiredKeys($downtime);

        $downtimeId = $downtime['id'];
        $severity = $downtime['severity'];

        list($channelThreadKey, $channelThreadMap) = $this->getDowntimeChannelTheadMap($downtimeId);

        $this->trace->info(TraceCode::RETRIVED_CHANNEL_MAP_FROM_REDIS,
            [
                'key' => $channelThreadKey,
                'channelmap' => $channelThreadMap,
                'downtimeId' => $downtimeId
            ]);

        $downtimeState = $this->getDowntimeState($downtime, $channelThreadMap);
        if ($this->isResolve($downtimeState) and empty($channelThreadMap) === true)
        {
            $this->trace->info(TraceCode::ABORT_DOWNTIME_SLACK_NOTIFICATION,
                [
                    'key' => $channelThreadKey,
                    'channelmap' => $channelThreadMap,
                    'downtimeId' => $downtimeId
                ]);
            return;
        }
        else
        {
            if(  ($this->isResolve($downtimeState) === false)and
                 (empty($channelThreadMap) === false) and
                 $this->isSameSeverity($severity, $channelThreadMap))
            {
                $this->trace->info(TraceCode::ABORT_SAME_SEVERITY_DOWNTIME_SLACK_NOTIFICATION,
                                   [
                                       'key' => $channelThreadKey,
                                       'channelmap' => $channelThreadMap,
                                       'downtimeId' => $downtimeId,
                                       'severity' => $severity
                                   ]);
                return;
            }
        }

        $eventTime = null;

        if ($this->isResolve($downtimeState))
        {
            $eventTime = $channelThreadMap['eventTime'];
        }

        $message = $this->formMesssage($downtimeState, $downtime, $eventTime);

        $responses = $this->notifyDowntime($downtimeState, $channelThreadMap, $message, $downtime['type']);

        if ($this->isCreateState($downtimeState))
        {
            $this->updateCache($downtime['eventTime'], $responses, $channelThreadKey, $downtimeId, $downtimeState,  $severity);
        }

        if($this->isTrannsitionState($downtimeState))
        {
            $this->updateSeverity($channelThreadKey, $channelThreadMap, $severity);
        }

        if ($this->isResolve($downtimeState))
        {
            $this->deleteChannelConfig($channelThreadKey);
        }

    }

    private function notifyDowntime($downtimeState, $channelThreadMap, $message, $downtimeType)
    {
        $responses = [];
        foreach ($this->slackChannelConfig as $config)
        {
            $channel = $config['channel'];
            $threadId = null;
            $this->trace->info(TraceCode::UPDATED_DOWNTIME_NOTIFICATION_CHANNEL_MAP,
                [
                    'channelMap' => $channelThreadMap,
                ]);

            if (array_key_exists($channel, $channelThreadMap))
            {
                $threadId = $channelThreadMap[$channel];
            }

            if (in_array($downtimeType, $config['allowedTypes']))
            {
                $this->trace->info(TraceCode::NOTIFYING_DOWNTIME_ON_SLACK,
                    [
                        'channel' => $channel,
                        'thread' => $threadId
                    ]);

                if($config['emitTransitions'] === true or $downtimeState !== "TRANSITION")
                {
                    $res = $this->sendNotification($downtimeState, $channel, $threadId, $message);
                }

                $this->trace->info(TraceCode::NOTIFIED_DOWNTIME_ON_SLACK,
                    [
                        'channel' => $channel,
                        'thread' => $threadId,
                        'response' => $res
                    ]);

                array_push($responses, $res);
            }
        }
        return $responses;
    }

    private function sendNotification($downtimeState, $channel, $threadId, $message)
    {

        $request = $this->getRequestObject();
        $body = [
            'channel' => $channel,
            'text' => $message,
            'thread_ts' => $threadId
        ];

        if ($downtimeState === "RESOLVE")
        {
            $body['reply_broadcast'] = true;
        }

        return Requests::POST(
            $request['url'],
            $request['headers'],
            $body
        );
    }

    private function getRequestObject()
    {
        $request['url'] = $this->config['url'];
        $request['headers'] = ['Authorization' => 'Bearer ' . $this->config['bearer_token']];
        return $request;
    }

    private function isMerchantDowntime($downtime)
    {
        $isMerchantDowntime = false;
        if (empty($downtime['merchantId']) === false)
        {
            $isMerchantDowntime = true;
        }
        return $isMerchantDowntime;
    }

    private function formMesssage($downtimeState, $downtime, $eTime)
    {
        $severity   = $downtime['severity'];
        $eventTime  = $downtime['eventTime'];
        $startTime  = $this->formatEpoc($eventTime, 'M d, H:i:s');

        $additionalDetails = "";

        if($this->isMerchantDowntime($downtime))
        {
            $additionalDetails = $this->getMerchantDetails($downtime);
        }

        if($this->isResolve($downtimeState) === true)
        {
            $severity = 'RESOLVED';
            $startTime  = $this->formatEpoc($eTime, 'M d, H:i:s');
            $additionalDetails = $additionalDetails."\n".$this->getResolutionDetails($eventTime, $eTime);
        }

        $heading = $this->getHeading($downtime);

        $method = $this->getMethod($downtime);

        $variant = $this->razorx->getTreatment(
            Constants::LOOKER_NOTIFICATIONS_RAZORX_KEY,
            Merchant\RazorxTreatment::DOWNTIMES_LOOKER_TO_SLACK,
            Core::getMode()
        );

        $lookerLink = $this->getLookerLink($downtime);

        if (strtolower($variant) === 'on') {
            return strtr($this->DOWNTIME_BASE_TEMPLATE_WITH_LOOKER_LINK, ['$severity' => $severity,
                '$heading' => $heading, '$method' => $method, '$startTime' => $startTime, '$additionalFields' => $additionalDetails, '$lookerLink' => $lookerLink]);
        }

        return strtr($this->DOWNTIME_BASE_TEMPLATE, ['$severity' => $severity,
            '$heading' => $heading, '$method' => $method, '$startTime' => $startTime, '$additionalFields' => $additionalDetails]);
    }

    /**
     * @param $downtimeId
     * @return array
     */
    private function getDowntimeChannelTheadMap($downtimeId): array
    {
        $channelThreadKey = "downtime_channel_thread_map_" . $downtimeId;

        $channelThreadMap = $this->redis->HGETALL($channelThreadKey);
        if (empty($channelThreadMap) === true)
        {
            $channelThreadMap = [];
        }
        else
        {
            $channelThreadMap = json_decode($channelThreadMap['config'], true);
        }
        return array($channelThreadKey, $channelThreadMap);
    }

    private function getDowntimeState($downtime, $channelThreadMap): string
    {
        $downtimeState = $downtime['action'];

        if ($this->isCreateState($downtimeState) and empty($channelThreadMap) === false)
        {
            $downtimeState = 'TRANSITION';
        }

        return $downtimeState;
    }

    private function deleteChannelConfig($channelThreadKey): void
    {
        $this->redis->del($channelThreadKey);
    }

    private function isCreateState($downtimeState): bool
    {
        return $downtimeState === "CREATE";
    }

    private function isTrannsitionState($downtimeState): bool
    {
        return $downtimeState === "TRANSITION";
    }


    private function isResolve($downtimeState): bool
    {
        return $downtimeState === "RESOLVE";
    }

    private function getHeading($downtime): string
    {
        $heading = '';

        if(isset($downtime['issuer']) === true)
        {
            $heading = $downtime['issuer'];
        }

        if($downtime['method'] === "card" and isset($downtime['network']) === true)
        {
            if(empty($downtime['issuer']) === false)
            {
                $heading = $downtime['network']." - ".$heading;
            }
            else
            {
                $heading = $downtime['network'];
            }
        }

        if(isset($downtime['sr']) === true)
        {
            $heading = $heading. " SR : ".$downtime['sr']."%";
        }

        return $heading;
    }

    private function getMethod($downtime): string
    {
        $method = $downtime['method'];

        switch ($method)
        {
            case "card":
                if(empty($downtime['cardType']) === false)
                    $method = $downtime['cardType']." ".$method;
                break;
            case "upi":
                if(empty($downtime['flow']) === false)
                    $method = $downtime['flow']." ".$method;
                break;
            default:
                break;
        }
        return $method;
    }

    private function getLookerLink($downtime): string
    {
        $method = $downtime[Entity::METHOD];

        $lookerLink = Constants::LOOKER_URL
            . $this->getDashboardForMethod($method)
            . '?' . Constants::METHOD_FILTER . '='
            . $this->getMethodFilter($downtime);

        if(empty($downtime[Entity::NETWORK]) === false)
        {
            $lookerLink .= "&" . Constants::NETWORK_FILTER . "=" . $downtime[Entity::NETWORK];
        }

        if(empty($downtime[Entity::ISSUER]) === false)
        {
            $lookerLink .= "&" . Constants::ISSUER_FILTER. "=" . $downtime[Entity::ISSUER];
        }

        if(empty($downtime[DowntimeService::MERCHANT_ID]) === false)
        {
            $lookerLink .= "&" . Constants::MERCHANT_ID_FILTER . "=" . $downtime[DowntimeService::MERCHANT_ID];
        }

        $lookerLink .= "&" . $this->getTimeFilter($downtime);

        $this->trace->info(
            TraceCode::LOOKER_DASHBOARD_LINK,
            [
                'Link' => $lookerLink
            ]
        );

        return $lookerLink;
    }

    private function getTimeFilter($downtime): string
    {
        $timeFilter = Constants::FROM . '=';

        $timeFilter  .= $this->formatEpoc($downtime[Entity::BEGIN], 'Y-m-d H:i')
            . " " . Constants::TO . " "
            . $this->formatEpoc($downtime[Entity::END], 'Y-m-d H:i');

        return $timeFilter;
    }

    private function getDashboardForMethod($method): int
    {
        if(isset(Constants::getLookerDashboardForMethod()[$method]) === true)
        {
            return Constants::getLookerDashboardForMethod()[$method];
        }

        return Constants::DEFAULT_LOOKER_DASHBOARD;
    }

    private function getMethodFilter($downtime): string
    {
        $method = $downtime[Entity::METHOD];

        switch ($method)
        {
            case Constants::CARD:
                return $this->getCardMethodFilter($downtime);
            case Constants::UPI:
                return $this->getUpiMethodFilter($downtime);
            default:
                return $method;
        }
    }

    private function getCardMethodFilter($downtime): string
    {
        if(empty($downtime['cardType']) === false)
        {
            return Constants::getLookerCardFilters()[$downtime['cardType']];
        }

        return Constants::getLookerCardFilters()[Constants::CARD];
    }

    private function getUpiMethodFilter($downtime): string
    {
        if(empty($downtime['flow']) === false)
        {
            return Constants::getLookerUpiFilters()[$downtime['flow']];
        }

        return Constants::getLookerUpiFilters()[Constants::UPI];
    }

    /**
     * @param array $responses
     * @param $channelThreadKey
     * @param $downtimeId
     * @param string $downtimeState
     */
    private function updateCache($eventTime, array $responses, $channelThreadKey, $downtimeId, string $downtimeState, $severity): void
    {
        $newMap = [];
        foreach ($responses as $res)
        {
            try
            {
                $body = json_decode($res->body);

                if ($this->isCreateState($downtimeState))
                {
                    $success = $body->ok;
                    if ($success === true)
                    {
                        $id = $body->ts;
                        $channel = $body->channel;
                        $newMap[$channel] = $id;
                    }

                    $this->trace->info(TraceCode::UPDATED_DOWNTIME_NOTIFICATION_CHANNEL_MAP,
                        [
                            'key' => $channelThreadKey,
                            'channelmap' => $newMap,
                            'downtimeId' => $downtimeId
                        ]);

                }
            }
            catch (\Exception $ex)
            {
                $this->trace->error(TraceCode::ERROR_PARSING_RESPONSE, ["error" => $ex]);
            }
        }

        $newMap['severity'] = $severity;
        $newMap['eventTime'] = $eventTime;

        $this->redis->HMSET($channelThreadKey, ['config' => json_encode($newMap)]);
    }

    /**
     * @param $eventTime
     * @param $format
     * @return string
     */
    private function formatEpoc($eventTime, $format): string
    {
        return Carbon::createFromTimestamp($eventTime, Timezone::IST)->format($format);
    }

    private function validateRequiredKeys($downtime)
    {
        $requiredKeys = [
            "id",
            "method",
            "severity",
            "action",
            "eventTime",
            "type"
        ];

        $diffSet = array_diff_key(array_flip($requiredKeys), $downtime);

        if (empty($diffSet) === false)
        {
            $missingKeys = implode(", ", $diffSet);

            $this->trace->critical(
                TraceCode::GATEWAY_DOWNTIME_SERVICE_INVALID_INPUT,
                ['missing_keys' => $missingKeys]
            );

            throw new Exception\BadRequestValidationFailureException(
                'Slack Notification Missing required attribute for downtime: ' . $missingKeys
            );
        }
    }

    private function getMerchantDetails($downtime): string
    {
        $id = $downtime['merchantId'];
        $name = $this->getMerchantName($downtime);

        return strtr($this->MERCHANT_DETAILS_TEMPLATE, [
            '$merchantId' => $id,
            '$merchantName' => $name,
        ]);
    }

    private function getResolutionDetails($eventTime, $startTime): string
    {
        $duration = ($eventTime - $startTime);
        $duration =  (round($duration / 60));

        $eventTime = $this->formatEpoc($eventTime, 'M d, H:i:s');

        return  strtr($this->RESOLUTION_TEMPLATE, ['$duration' =>  $duration, '$endTime' => $eventTime]);
    }

    private function getMerchantName($downtime)
    {
        $merchantId = $downtime['merchantId'];
        $key = "{downtime}:merchant_name_".$merchantId;
        $merchantName  = $this->redis->HGETALL($key);

        if(empty($merchantName) === true)
        {
            $this->trace->error(TraceCode::DOWNTIME_NOTIFICATION_MERCHANT_KEY_MISSING, ["merchantId" => $merchantId]);
            return $merchantId;
        }

        return $merchantName['name'];
    }

    private function isSameSeverity($severity, $channelThreadMap): bool
    {
        //For notifications where severity was not set.
        if(isset($channelThreadMap['severity']) === false)
        {
            return false;
        }
        else
        {
            $oldSeverity = $channelThreadMap['severity'];
            $this->trace->info(TraceCode::ACCOUNT_CREATION_V2_REQUEST, ["c"=>$channelThreadMap]);
            return ($oldSeverity === $severity);
        }
    }

    private function updateSeverity($channelThreadKey, $channelThreadMap, $severity)
    {
        $channelThreadMap['severity'] = $severity;
        $this->redis->HMSET($channelThreadKey, ['config' => json_encode($channelThreadMap)]);
    }


}
