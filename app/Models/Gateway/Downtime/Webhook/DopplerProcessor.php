<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

use App;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Downtime;
use RZP\Models\Gateway\Downtime\Entity;
use RZP\Models\Gateway\Downtime\ReasonCode;

class DopplerProcessor implements ProcessorInterface
{
    const STATUS_UP = 'up';
    const STATUS_DOWN = 'down';

    protected $app;

    protected $trace;

    protected $core;

    protected $repo;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

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
        //
    }

    public function process(array $input)
    {
        try
        {
            $status = $input['status'];

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

            return [];
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);
        }

        return [];
    }

    private function buildInput(array $input)
    {
        $buildInput = [
            Entity::SOURCE          => Downtime\Source::DOPPLER,
            Entity::PARTIAL         => false,
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

        if(is_null($downtime) === false)
        {
            throw new Exception\LogicException(
              'Duplicate Ongoing Downtime Found by Doppler',
              null,
              [
                  'DowntimeData' => $downtimeData,
              ]
            );
        }

        $this->trace->info(
            TraceCode::GATEWAY_DOWNTIME_DOPPLER_CREATE, ['data' => $downtimeData]
        );

        $downtime = $this->core->create($downtimeData);

        return $downtime->toArrayAdmin();
    }

    private function resolveDowntime(array $downtimeData)
    {
        $downtime = $this->core->fetchMostRecentActive($downtimeData);

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

        return $downtime->toArrayAdmin();
    }

}
