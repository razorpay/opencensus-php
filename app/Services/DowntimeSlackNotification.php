<?php

namespace RZP\Services;


use RZP\Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use RZP\Constants\Timezone;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class DowntimeSlackNotification
{

    protected $app;

    protected $config;

    protected $trace;

    protected $defaultResponse;

    protected $slackChannelConfig;

    protected $redis;

    private $DOWNTIME_BASE_TEMPLATE = '`[$severity]  $heading` '."\n".'```Method : $method'."\n".'Start Time : $startTime'."\n".'$additionalFields```';

    private $MERCHANT_DETAILS_TEMPLATE = 'Merchant Name: $merchantName'."\n".'Merchant Id : $merchantId';

    private $RESOLUTION_TEMPLATE = 'End Time: $endTime'."\n".'Duration : $duration minutes';

    public function __construct($app)
    {
        $this->trace = $app['trace'];
        $this->config = $app['config']->get('applications.gateway_downtime.slack');

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

        $eventTime = null;

        if ($this->isResolve($downtimeState))
        {
            $eventTime = $channelThreadMap['eventTime'];
        }

        $message = $this->formMesssage($downtimeState, $downtime, $eventTime);

        $responses = $this->notifyDowntime($downtimeState, $channelThreadMap, $message, $downtime['type']);

        if ($this->isCreateState($downtimeState))
        {
            $this->updateCache($downtime['eventTime'], $responses, $channelThreadKey, $downtimeId, $downtimeState);
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
                    'channelmap' => $channelThreadMap,
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
        $startTime  = $this->formatEpoc($eventTime);

        $additionalDetails = "";

        if($this->isMerchantDowntime($downtime))
        {
            $additionalDetails = $this->getMerchantDetails($downtime);
        }

        if($this->isResolve($downtimeState) === true)
        {
            $severity = 'RESOLVED';
            $startTime  = $this->formatEpoc($eTime);
            $additionalDetails = $additionalDetails."\n".$this->getResolutionDetails($eventTime, $eTime);
        }

        $heading = $this->getHeading($downtime);

        $method = $this->getMethod($downtime);

         return strtr($this->DOWNTIME_BASE_TEMPLATE, ['$severity' => $severity,
             '$heading' => $heading, '$method'=>$method, '$startTime' => $startTime, '$additionalFields' => $additionalDetails ]);
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

    /**
     * @param array $responses
     * @param $channelThreadKey
     * @param $downtimeId
     * @param string $downtimeState
     */
    private function updateCache($eventTime, array $responses, $channelThreadKey, $downtimeId, string $downtimeState): void
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

        $newMap['eventTime'] = $eventTime;

        $this->redis->HMSET($channelThreadKey, ['config' => json_encode($newMap)]);
    }

    /**
     * @param $eventTime
     * @return string
     */
    private function formatEpoc($eventTime): string
    {
        return Carbon::createFromTimestamp($eventTime, Timezone::IST)->format('M d, H:i:s');
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

        $eventTime = $this->formatEpoc($eventTime);

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



}
