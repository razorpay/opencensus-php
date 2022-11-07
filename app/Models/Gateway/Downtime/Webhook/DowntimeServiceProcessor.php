<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

use anlutro\LaravelSettings\ArrayUtil;
use App;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Card;
use RZP\Http\RequestHeader;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Downtime\Metric;
use RZP\Trace\TraceCode;
use RZP\Constants\Environment;
use RZP\Models\Payment\Method;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Gateway\Downtime;
use RZP\Models\Gateway\Downtime\Entity;
use RZP\Models\Gateway\Downtime\ReasonCode;
use RZP\Models\Gateway\Downtime\Webhook\Constants\DowntimeService;

class DowntimeServiceProcessor implements ProcessorInterface
{
    const STATUS_RESOLVE = 'RESOLVE';

    const STATUS_CREATE                      = 'CREATE';

    protected $app;

    protected $trace;

    protected $core;

    protected $repo;

    protected $env;

    protected $mode;

    protected $mutex;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->env = $this->app['env'];

        $this->mode = $this->app['rzp.mode'];

        $this->core = new Downtime\Core;

        $this->mutex = $this->app['api.mutex'];
    }

    public function validate(array $input)
    {
        // TODO: Implement validate() method.
    }

    public function process(array $input)
    {
        $downtimeServiceEnable = (bool) ConfigKey::get(ConfigKey::ENABLE_DOWNTIME_SERVICE, false);

        if ($downtimeServiceEnable === false)
        {
            $this->trace->info(
                TraceCode::GATEWAY_DOWNTIME_SERVICE_REQUEST,
                [
                    'input' => $input,
                    'downtimeServiceEnabled' => 'false',
                ]
            );

            return null;
        }
        else {
            switch ($input['method'])
            {
                case Method::CARD :
                    $downtimeServiceEnableMethod = (bool) ConfigKey::get(ConfigKey::ENABLE_DOWNTIME_SERVICE_CARD, false);
                    break;
                case Method::UPI :
                    $downtimeServiceEnableMethod = (bool) ConfigKey::get(ConfigKey::ENABLE_DOWNTIME_SERVICE_UPI, false);
                    break;
                case Method::NETBANKING :
                    $downtimeServiceEnableMethod = (bool) ConfigKey::get(ConfigKey::ENABLE_DOWNTIME_SERVICE_NETBANKING, false);
                    break;
                case Method::EMANDATE :
                    $downtimeServiceEnableMethod = (bool) ConfigKey::get(ConfigKey::ENABLE_DOWNTIME_SERVICE_EMANDATE, false);
                    break;
                default :
                    $downtimeServiceEnableMethod = false;
            }
            if ($downtimeServiceEnableMethod === false)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_DOWNTIME_SERVICE_REQUEST,
                    [
                        'input' => $input,
                        'method' =>$input['method'],
                        'downtimeServiceEnabledMethod' => 'false',
                    ]
                );

                return null;
            }
        }

        $this->trace->info(
            TraceCode::GATEWAY_DOWNTIME_SERVICE_REQUEST,
            [
                'input' => $input,
                'downtimeServiceEnabled' => 'true',
            ]
        );

        $this->validateRequiredKeys($input);

        $this->trace->count(Metric::PG_AVAILABILITY_WEBHOOK_RECEIVED, [
            'method' => $input['method'],
            'action' => $input['action'],
            'type' => $input['type'],
            'severity' => $input['severity']
        ]);


        $status = $input['action'];

        $data = $this->buildInput($input);

        if ($status === self::STATUS_CREATE)
        {
            $useMutex = (bool) ConfigKey::get(ConfigKey::USE_MUTEX_FOR_DOWNTIMES, false);

            $this->trace->info(
                TraceCode::GATEWAY_DOWNTIME_SERVICE_USE_MUTEX,
                [
                    'useMutex' => $useMutex
                ]
            );

            if($useMutex)
            {
                $mutexKey = $this->getMutexKeyForGatewayDowntime($data);

                return $this->createDowntimeWithMutex($data, $mutexKey);
            }
            else
            {
                return $this->createDowntime($data);
            }
        }
        elseif ($status === self::STATUS_RESOLVE)
        {
            return $this->resolveDowntime($data);
        }
    }

    protected function getMutexKeyForGatewayDowntime(array $data):string
    {
        $mutexKey = $data[Entity::METHOD];

        $mutexKey .= isset($data[Entity::GATEWAY]) ? $data[Entity::GATEWAY] : "nullGateway";

        $mutexKey .= isset($data[Entity::NETWORK]) ? $data[Entity::NETWORK] : "nullnetwork";

        $mutexKey .= isset($data[Entity::ISSUER]) ? $data[Entity::ISSUER] : "nullissuer";

        $mutexKey .= isset($data[Entity::VPA_HANDLE]) ? $data[Entity::VPA_HANDLE] : "nullvpahandle";

        $mutexKey .= isset($data[Entity::MERCHANT_ID]) ? $data[Entity::MERCHANT_ID] : "platform";

        $mutexKey .= isset($data[Entity::CARD_TYPE]) ? $data[Entity::CARD_TYPE] : "all";

        $this->trace->info(
            TraceCode::GATEWAY_DOWNTIME_SERVICE_MUTEX_KEY,
            [
                'mutexKey' => $mutexKey
            ]
        );

        return $mutexKey;
    }

    protected function createDowntimeWithMutex(array $data, string $mutexKey)
    {
        return $this->mutex->acquireAndRelease(
            $mutexKey,
            function () use ($data)
            {
                return $this->createDowntime($data);
            },
            10,
            ErrorCode::BAD_REQUEST_GATEWAY_DOWNTIME_MUTEX_TIMED_OUT
        );
    }

    protected function createDowntime(array $data)
    {
        if(isset($data[Entity::MERCHANT_ID]) === true)
        {
            $this->checkOngoingPlatformDowntime($data);
        }

        $downtime = $this->core->fetchMostRecentActive($data, DowntimeService::UNIQUE_KEYS);

        if (is_null($downtime) === false)
        {
            if($downtime->getReasonCode() === $data[Entity::REASON_CODE])
            {
                throw new Exception\LogicException(
                    'Duplicate Ongoing Downtime Found by Downtime Service',
                    null,
                    [
                        'DowntimeData' => $data,
                    ]
                );
            }
            else
            {
                $id = $downtime->getId();

                $downtimeUpdate = [
                    Entity::REASON_CODE     => $data[Entity::REASON_CODE],
                ];

                $this->trace->info(
                    TraceCode::GATEWAY_DOWNTIME_SERVICE_REQUEST_EDIT,
                    [
                        'input' => $data,
                        'downtime_id' => $id,
                        'updateFields' => $downtimeUpdate,
                    ]
                );

                $downtime = $this->core->edit($id, $downtimeUpdate);
            }
        }
        else
        {
            $this->trace->info(
                TraceCode::GATEWAY_DOWNTIME_SERVICE_REQUEST_CREATE,
                [
                    'input' => $data,
                ]
            );

            $downtime = $this->core->create($data);
        }

        return $downtime->toArrayAdmin();
    }

    protected function resolveDowntime(array $data)
    {
        $downtime = $this->core->fetchMostRecentActive($data, DowntimeService::getUniqueKeys());

        if(is_null($downtime) === true)
        {
            $this->trace->error(TraceCode::GATEWAY_DOWNTIME_SERVICE_INVALID_RESOLVE,
            [
                'downtime' => $data,
            ]);

            return null;
//            throw new Exception\LogicException(
//                'Downtime Service Trying to resolve a non-existent downtime',
//                null,
//                [
//                    'downtime' => $data,
//                ]
//            );
        }

        $this->trace->info(
            TraceCode::GATEWAY_DOWNTIME_SERVICE_REQUEST_RESOLVE,
            [
                'input' => $data,
            ]
        );

        $downtime->setEnd();

        $this->repo->saveOrFail($downtime);

        return $downtime->toArrayAdmin();
    }

    protected function buildInput(array $input)
    {
        $buildInput = [
            Entity::SOURCE          => Downtime\Source::DOWNTIME_SERVICE,
            Entity::PARTIAL         => false,
            Entity::SCHEDULED       => false,
            Entity::METHOD          => $input[Entity::METHOD],
            Entity::GATEWAY         => $input[Entity::GATEWAY] ?? Entity::ALL,
            Entity::ISSUER          => $input[Entity::METHOD] != Method::UPI ? $input[Entity::ISSUER] ?? null : null,
            Entity::ACQUIRER        => Entity::UNKNOWN,
            Entity::CARD_TYPE       => null,
            Entity::NETWORK         => isset($input[Entity::NETWORK]) ? Card\NetworkName::$codes[$input[Entity::NETWORK]] : null,
            Entity::PSP             => $input[Entity::PSP] ?? null,
            Entity::VPA_HANDLE      => $input[Entity::METHOD] === Method::UPI ? $input[Entity::ISSUER] : null,
            Entity::MERCHANT_ID     => $input[DowntimeService::TYPE] === DowntimeService::MERCHANT ? $input[DowntimeService::MERCHANT_ID] : null,
        ];
        // if its merchant level downtime check if mid is not null

        if(isset($input[Entity::GATEWAY])===true) // For Gateway Downtimes
        {
            if($input[Entity::METHOD] === Method::UPI && isset($entity[ENTITY::ISSUER])===true)
            {
                $buildInput[Entity::VPA_HANDLE] = $input[Entity::ISSUER];
            }
        }else{                                    // For Payment Downtimes
            if($input[Entity::METHOD] === Method::UPI){
                $buildInput[Entity::VPA_HANDLE] = $input[Entity::ISSUER];
            }
        }

        switch ($input[Entity::SEVERITY])
        {
            case strtoupper(Downtime\Severity::HIGH):
                $buildInput[Entity::REASON_CODE] = ReasonCode::HIGHER_ERRORS;
                break;

            case strtoupper(Downtime\Severity::MEDIUM):
                $buildInput[Entity::REASON_CODE] = ReasonCode::LOW_SUCCESS_RATE;
                break;

            case strtoupper(Downtime\Severity::LOW):
                $buildInput[Entity::REASON_CODE] = ReasonCode::OTHER;
                break;
        }

        if ($input[DowntimeService::ACTION] === self::STATUS_CREATE)
        {
            $buildInput[Entity::BEGIN] = $input[DowntimeService::EVENT_TIME];
        }
        else {
            $buildInput[Entity::END] = $input[DowntimeService::EVENT_TIME];
        }

        $comment = 'RuleId :' . $input[DowntimeService::RULE_ID] . ', Strategy :' . $input[DowntimeService::STRATEGY];
        $buildInput[Entity::COMMENT] = $comment;

        if($input[Entity::METHOD] === Method::CARD)
        {
            $buildInput[Entity::CARD_TYPE] = isset($input[DowntimeService::CARD_TYPE]) ? $input[DowntimeService::CARD_TYPE] : null;
        }
        else if($input[Entity::METHOD] == Method::UPI)
        {
            $buildInput[Entity::CARD_TYPE] = isset($input[DowntimeService::FLOW]) ? $input[DowntimeService::FLOW] : null;
        }

        return $buildInput;
    }

    protected function validateRequiredKeys(array $input)
    {
        $requiredKeys = [
            Entity::METHOD,
            Entity::SEVERITY,
            DowntimeService::ACTION,
            DowntimeService::EVENT_TIME,
            DowntimeService::TYPE,
        ];

        $diffSet = array_diff_key(array_flip($requiredKeys), $input);

        if(empty($diffSet) === false)
        {
            $missingKeys = implode(", ", $diffSet);

            $this->trace->critical(
                TraceCode::GATEWAY_DOWNTIME_SERVICE_INVALID_INPUT,
                ['missing_keys' => $missingKeys]
            );

            throw new Exception\BadRequestValidationFailureException(
                'Downtime Service Missing required attribute for gateway downtime: ' . $missingKeys
            );
        }

        //// Add a new key for downtimes resolved in  morotorium period

        if(in_array($input[DowntimeService::ACTION], [self::STATUS_CREATE, self::STATUS_RESOLVE], true) === false)
        {
            $this->trace->critical(TraceCode::GATEWAY_DOWNTIME_SERVICE_INVALID_ACTION,
                ['Action' => $input[DowntimeService::ACTION]]);

            throw new Exception\BadRequestValidationFailureException(
                'Invalid Action Provided in Downtime Service request : ' . $input[DowntimeService::ACTION]);
        }

        if($input[DowntimeService::TYPE] === DowntimeService::MERCHANT && isset($input[DowntimeService::MERCHANT_ID]) === false)
        {
            $this->trace->critical(TraceCode::GATEWAY_DOWNTIME_SERVICE_INVALID_INPUT,
                ['Type' => $input[DowntimeService::TYPE],
                    'MerchantID' => 'Absent']);

            throw new Exception\BadRequestValidationFailureException(
                'Downtime Service Missing merchant id for gateway downtime type : ' . $input[DowntimeService::TYPE]);

        }
        elseif ($input[DowntimeService::TYPE] === DowntimeService::PLATFORM && isset($input[DowntimeService::MERCHANT_ID]) === true)
        {
            $this->trace->critical(TraceCode::GATEWAY_DOWNTIME_SERVICE_INVALID_INPUT,
                ['Type' => $input[DowntimeService::TYPE],
                    'MerchantID' => $input[DowntimeService::MERCHANT_ID]]);

            throw new Exception\BadRequestValidationFailureException(
                'Downtime Service merchant id present for gateway downtime type : ' . $input[DowntimeService::TYPE]);
        }

        if (isset($input[Entity::NETWORK]) === true && Network::isValidNetworkName($input[Entity::NETWORK]) === false)
        {
            $this->trace->critical(TraceCode::GATEWAY_DOWNTIME_SERVICE_INVALID_INPUT,
                [   'Type' => $input[DowntimeService::TYPE],
                    'Network' => $input[Entity::NETWORK]]   );

            throw new Exception\BadRequestValidationFailureException(
                'Downtime Service invalid network present : ' . $input[Entity::NETWORK]);
        }
    }

    private function checkOngoingPlatformDowntime(array $data)
    {
        $platformDowntime = $this->core->fetchMostRecentActive($data, DowntimeService::PLATFORM_DOWNTIME_UNIQUE_KEYS);
        if(is_null($platformDowntime)===false)
        {
            throw new Exception\LogicException(
                'Creating merchant downtime during ongoing Platform downtime',
                null,
                [
                    'DowntimeData' => $data,
                ]
            );
        }
    }
}
