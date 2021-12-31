<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

use App;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Http\RequestHeader;
use RZP\Trace\TraceCode;
use RZP\Constants\Environment;
use RZP\Models\Gateway\Downtime;
use RZP\Models\Gateway\Downtime\Entity;
use RZP\Models\Gateway\Downtime\ReasonCode;

class DopplerProcessor implements ProcessorInterface
{
    const STATUS_UP = 'UP';

    const STATUS_DOWN = 'DOWN';

    const DOPPLER_HITTING_DOWNTIME_DATABASE = 'doppler_hitting_downtime_database';

    protected $app;

    protected $trace;

    protected $core;

    protected $repo;

    protected $env;

    protected $mode;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->env = $this->app['env'];

        $this->mode = $this->app['rzp.mode'];

        $this->core = new Downtime\Core;
    }

    protected function validateStatus($status)
    {
        $validStatus = [self::STATUS_UP, self::STATUS_DOWN];

        if(in_array($status, $validStatus, true) === false)
        {
            $this->trace->critical(
                TraceCode::GATEWAY_DOWNTIME_DOPPLER_INVALID_STATUS,
                ['status' => $status]);

            throw new Exception\BadRequestValidationFailureException(
                'Invalid Doppler status provided: ' . $status);
        }
    }

    protected function validateRequiredKeys(array $input)
    {
        $this->validateStatus($input['status']);

        $requiredKeys = [Entity::REASON_CODE, Entity::METHOD, Entity::GATEWAY];

        $diffSet = array_diff_key(array_flip($requiredKeys), $input);

        if(empty($diffSet) === false)
        {
            $missingKeys = implode(", ", $diffSet);

            $this->trace->critical(
                TraceCode::GATEWAY_DOWNTIME_DOPPLER_INVALID_MESSAGE,
                ['missing_keys' => $missingKeys]
            );

            throw new Exception\BadRequestValidationFailureException(
                'Doppler Missing required attribute for gateway downtime: ' . $missingKeys
            );
        }

        if(ReasonCode::isValidReasonCode($input[Entity::REASON_CODE]) === false)
        {
            $this->trace->critical(
                TraceCode::GATEWAY_DOWNTIME_DOPPLER_INVALID_REASONCODE,
                ['reason_code' => $input[Entity::REASON_CODE]]
            );

            throw new Exception\BadRequestValidationFailureException(
                'Doppler invalid reason code for gateway downtime: ' . $input[Entity::REASON_CODE]
            );
        }
    }

    public function validate(array $input)
    {
        $token = $this->app['request']->header('Authorization');

        list($key, $secret) = $this->fetchdopplerCredentials();

        $hash = base64_encode($key.":".$secret);

        $apiKey = "basic ".$hash;

        if (hash_equals($apiKey, $token) === false)
        {
            $this->trace->error(
                TraceCode::GATEWAY_DOWNTIME_DOPPLER_INVALID_TOKEN, []);

            throw new Exception\BadRequestValidationFailureException(
                'Doppler token validation failure.');
        }
    }

    public function process(array $input)
    {
        try
        {
            $status = $input['status'];

            $this->trace->info(
                TraceCode::GATEWAY_DOWNTIME_DOPPLER_REQUEST,
                [
                    'input' => $input,
                ]
            );

            $this->validateRequiredKeys($input);

            $data = $this->buildInput($input);

            if ($status === self::STATUS_DOWN)
            {
                return $this->createDowntime($data);
            }
            elseif ($status === self::STATUS_UP)
            {
                return $this->resolveDowntime($data);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_DOPPLER,
                null,
                $e
            );
        }

    }

    private function buildInput(array $input)
    {
        $buildInput = [
            Entity::SOURCE          => Downtime\Source::DOPPLER,
            Entity::PARTIAL         => $input[Entity::PARTIAL] ?? false,
            Entity::SCHEDULED       => false,
            Entity::METHOD          => $input[Entity::METHOD],
            Entity::REASON_CODE     => $input[Entity::REASON_CODE],
            Entity::GATEWAY         => $input[Entity::GATEWAY] ?? Entity::ALL,
            Entity::COMMENT         => $input[Entity::COMMENT] ?? "",
            Entity::ISSUER          => $input[Entity::ISSUER] ?? null,
            Entity::ACQUIRER        => $input[Entity::ACQUIRER] ?? Entity::UNKNOWN,
            Entity::CARD_TYPE       => $input[Entity::CARD_TYPE] ?? null,
            Entity::NETWORK         => $input[Entity::NETWORK] ?? null,
            //Entity::PSP             => $input[Entity::PSP] ?? null,
        ];

        $status = $input['status'];

        if($status === self::STATUS_DOWN)
        {
            // Downtime Start
            $buildInput[Entity::BEGIN] = time();
        }
        else
        {
            // Downtime End
            $buildInput[Entity::END] = time();
        }

        return $buildInput;
    }

    private function createDowntime(array $downtimeData)
    {
        $downtime = $this->core->fetchMostRecentActive($downtimeData);

        $razorXDowntimeDatabase = $this->shouldHitDowntimeDatabase();

        if (is_null($downtime) === false)
        {
            if($downtime->getReasonCode() === $downtimeData['reason_code'])
            {
                throw new Exception\LogicException(
                    'Duplicate Ongoing Downtime Found by Doppler',
                    null,
                    [
                        'DowntimeData' => $downtimeData,
                    ]
                );
            }
            else
            {
                $id = $downtime->getId();

                $downtimeUpdate = [
                    Entity::REASON_CODE     => $downtimeData[Entity::REASON_CODE],
                    Entity::PARTIAL         => $downtimeData[Entity::PARTIAL] ?? false,
                ];

                if ($razorXDowntimeDatabase === true)
                {
                    $downtime = $this->core->edit($id, $downtimeUpdate);
                }
                else
                {
                    $downtime = (new Entity)->build($downtimeUpdate);

                    $this->trace->info(
                        TraceCode::GATEWAY_DOWNTIME_DOPPLER_EDIT,
                        [
                            'data' => $downtimeUpdate,
                            'doppler_hitting_downtime_database' => $razorXDowntimeDatabase
                        ]
                    );
                }

                return $downtime->toArrayAdmin();

            }
        }

        if ($razorXDowntimeDatabase === true)
        {
            $downtime = $this->core->create($downtimeData);
        }
        else
        {
            $downtime = (new Entity)->build($downtimeData);

            $this->trace->info(
                TraceCode::GATEWAY_DOWNTIME_DOPPLER_CREATE,
                [
                    'data' => $downtimeData,
                    'doppler_hitting_downtime_database' => $razorXDowntimeDatabase
                ]
            );
        }

        return $downtime->toArrayAdmin();
    }

    private function resolveDowntime(array $downtimeData)
    {
        $downtime = $this->core->fetchMostRecentActive($downtimeData);

        $razorXDowntimeDatabase = $this->shouldHitDowntimeDatabase();

        if ($razorXDowntimeDatabase === true)
        {

            if(is_null($downtime) === true)
            {
                throw new Exception\LogicException(
                    'Doppler Trying to resolve a non-existent downtime',
                    null,
                    [
                        'downtimeData' => $downtimeData,
                    ]
                );
            }

            $downtime->setEnd();

            $this->repo->saveOrFail($downtime);
        }
        else
        {
            $this->trace->info(
                TraceCode::GATEWAY_DOWNTIME_DOPPLER_RESOLVE,
                [
                    'data'  => $downtimeData,
                    'doppler_hitting_downtime_database' => $razorXDowntimeDatabase
                ]
            );

            return $downtimeData;
        }

        return $downtime->toArrayAdmin();
    }

    protected function shouldHitDowntimeDatabase()
    {
        if (($this->env !== Environment::PRODUCTION) or
            ($this->mode !== Mode::LIVE))
        {
            return true;
        }

        $response = $this->app->razorx->getTreatment(
            $this->app['request']->getId(),
            self::DOPPLER_HITTING_DOWNTIME_DATABASE,
            $this->mode);

        if ($response === 'on')
        {
            return true;
        }

        return false;
    }

    protected function fetchdopplerCredentials()
    {
        $key = $this->app['config']->get('applications.doppler.key');

        $secret = $this->app['config']->get('applications.doppler.secret');

        return [$key, $secret];
    }

}
