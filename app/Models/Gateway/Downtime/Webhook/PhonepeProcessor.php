<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

use App;
use Carbon\Carbon;
use RZP\Http\Request\Requests;
use RZP\Models\Gateway\Downtime\Entity;
use RZP\Models\Gateway\Downtime\ReasonCode;
use RZP\Models\Payment\Method;
use RZP\Tests\Functional\Fixtures\Entity\GatewayDowntime;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Downtime;
use RZP\Gateway\Upi\Base;

class PhonepeProcessor implements ProcessorInterface
{
    protected $app;

    protected $trace;

    protected $core;

    protected $repo;

    protected $env;

    protected $mode;

    const OVERAPP_HEALTH = 'overallHealth';
    const INSTRUMENTS = 'instruments';
    const INSTRUMNET = 'instrument';
    const HEALTH = 'health';
    const UP = 'UP';
    const DOWN = 'DOWN';
    const PROVIDERS = 'providers';
    const PROVIDER_TYPE = 'providerType';
    const PROVIDER_ID = 'providerId';
    const REASON = 'reason';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->env = $this->app['env'];

        $this->mode = $this->app['rzp.mode'];

        $this->core = new Downtime\Core;
    }
    public function validate(array $input)
    {
        // TODO: Implement validate() method.
    }

    public function process(array $input)
    {
        $downtimesInput = $this->app['phonepe']->sendRequest($input);

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_PHONEPE_WEBHOOK, $downtimesInput);

        $this->processDowntimes($downtimesInput);
    }

    protected function processDowntimes($input)
    {
        foreach ($input[self::INSTRUMENTS] as $instrument)
        {
            switch ($instrument[self::INSTRUMNET])
            {
                case 'UPI':
                    $this->processUPIVPADowntime($instrument);
                    $this->processUPIIssuerDowntime($instrument);
            }
        }
    }

    protected function createUPIVPADowntime()
    {
        $downtimeData = $this->baseInput('begin');

        $downtimeData[Entity::VPA_HANDLE] = Base\ProviderCode::YBL;

        $downtime = $this->core->fetchMostRecentActive($downtimeData);

        if (is_null($downtime) === true)
        {
            $this->trace->info(
                TraceCode::PHONEPE_DOWNTIME_CREATE,
                [
                    'input' => $downtimeData,
                ]
            );

            $downtime = $this->core->create($downtimeData);
        }
    }


    protected function processUPIVPADowntime($input)
    {
        if ($input[self::HEALTH] === self::DOWN)
        {
            $this->createUPIVPADowntime();
        }
        else
        {
            $this->resolveUPIVPADowntime();
        }
    }

    protected function processUPIIssuerDowntime($input)
    {
        $unavailableProviders = [];

        foreach($input[self::PROVIDERS] as $provider)
        {
            if($provider[self::HEALTH] !== self::DOWN)
            {
                continue;
            }

            $downtimeData = $this->baseInput('begin');

            if($provider[self::PROVIDER_TYPE] !== 'BANK')
            {
                continue;
            }

            $downtimeData[Entity::ISSUER] = $provider[self::PROVIDER_ID];

            $downtime = $this->core->fetchMostRecentActive($downtimeData);

            if (is_null($downtime) === true)
            {
                $this->trace->info(
                    TraceCode::PHONEPE_DOWNTIME_CREATE,
                    [
                        'input' => $downtimeData,
                    ]
                );

                $downtime = $this->core->create($downtimeData);

                array_push($unavailableProviders, $provider[self::PROVIDER_ID]);
            }
        }

        $input = $this->baseInput();

        $activeDowntimes = $this->core->fetchActiveDowntime($input);

        $activeDowntimes = $activeDowntimes->where(Entity::VPA_HANDLE, '!=', 'ybl');

        $resolvedDowntimes = $activeDowntimes->whereNotIn(Entity::ISSUER, $unavailableProviders);

        foreach ($resolvedDowntimes as $resolvedDowntime)
        {
            $resolvedDowntime->setEnd();

            $this->repo->saveOrFail($resolvedDowntime);
        }
    }

    protected function baseInput($status = null)
    {
        $baseInput = [
            Entity::SOURCE          => Downtime\Source::PHONEPE,
            Entity::PARTIAL         => false,
            Entity::SCHEDULED       => false,
            Entity::REASON_CODE     => ReasonCode::HIGHER_ERRORS,
            Entity::GATEWAY         => Entity::ALL,
            Entity::METHOD          => Method::UPI,
        ];

        if($status === 'begin')
        {
            $baseInput[Entity::BEGIN] = Carbon::now()->getTimestamp();
        }
        elseif ( $status === 'end')
        {
            $baseInput[Entity::END] = Carbon::now()->getTimestamp();
        }

        return $baseInput;
    }

    protected function resolveUPIVPADowntime(): void
    {
        $downtimeData = $this->baseInput('end');

        $downtimeData[Entity::VPA_HANDLE] = Base\ProviderCode::YBL;

        $downtime = $this->core->fetchMostRecentActive($downtimeData);

        if (is_null($downtime) === false) {
            $this->trace->info(
                TraceCode::PHONEPE_DOWNTIME_RESOLVE,
                [
                    'input' => $downtimeData,
                ]
            );

            $downtime->setEnd();

            $this->repo->saveOrFail($downtime);
        }
    }
}
