<?php

namespace RZP\Models\Gateway\Downtime;

use Carbon\Carbon;


use Razorpay\Trace\Logger;
use RZP\Services;
use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\Payment;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Services\DowntimeMetric as DowntimeMetric;

class Core extends Base\Core
{
    // 10 minutes
    const DEFAULT_DOWNTIME_DURATION = 600;

    const GATEWAY_EXCEPTION_DOWNTIME = 'gateway_exception_downtime_';

    const MOZART_GET_DOWNTIME_ACTION = "downtime";

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Prevent duplicate creation of the same error model.
     * Basically, since we pass an empty 'to', it means, this is for an unscheduled
     * maintenance. In case of a scheduled maintenance, the 'to' param is set.
     * For an unscheduled one, in case there already does exist a record for the
     * same gateway, issuer and method, update the unscheduled with scheduled. In
     * case there already does exist a scheduled one, and the current one is unscheduled,
     * do not replace. Essentially, the scheduled one precedes the unscheduled.
     *
     * @param array $input
     *
     * @param array $uniqueRecordIdentifiers
     * @param bool $allowUpdateOfExistingDowntime
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function create(array $input, array $uniqueRecordIdentifiers = [], $allowUpdateOfExistingDowntime = true)
    {
        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_CREATE, $input);

        $downtime = $this->repo->gateway_downtime->getConflictingDowntime($input, $uniqueRecordIdentifiers);

        if ($downtime !== null)
        {
            if (($allowUpdateOfExistingDowntime === false) or
                ($this->allowUpdateOfExistingDowntimes() === false))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_GATEWAY_DOWNTIME_CONFLICT,
                    null,
                    $downtime->toArrayPublic());
            }

            $downtime->edit($input, 'edit_duplicate');
        }
        else
        {
            $downtime = (new Entity)->build($input);
        }

        if (isset($input[Entity::TERMINAL_ID]) === true)
        {
            $terminal = $this->repo->terminal->findOrFailPublic($input[Entity::TERMINAL_ID]);

            $downtime->terminal()->associate($terminal);
        }

        $this->repo->saveOrFail($downtime);

        return $downtime;
    }

    /**
     * Updates via creation endpoint are not permitted if request is from
     * dashboard, since manual users can just as well use the edit route.
     * This functionality exists only to serve automated downtime creation and updates.
     *
     * @return bool
     */
    protected function allowUpdateOfExistingDowntimes()
    {
        if (($this->app['basicauth']->isAdminAuth() === true) and
            ($this->app['basicauth']->isDashboardApp() === true))
        {
            return false;
        }

        return true;
    }

    public function edit(string $id, array $input)
    {
        $downtime = $this->repo->gateway_downtime->findOrFailPublic($id);

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_EDIT, $input);

        $downtime->edit($input);

        if (isset($input[Entity::TERMINAL_ID]) === true)
        {
            $terminal = $this->repo->terminal->findOrFailPublic($input[Entity::TERMINAL_ID]);

            $downtime->terminal()->associate($terminal);
        }

        $this->repo->saveOrFail($downtime);

        return $downtime;
    }

    public function delete(Entity $downtime): Entity
    {
        $this->repo->gateway_downtime->deleteOrFail($downtime);

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DELETED, $downtime->toArray());

        return $downtime;
    }

    /**
     * Fetches downtime information at the current time and Future for displaying at Dashboard
     *
     *
     * @return Collection      Collection of downtimes
     */
    public function getCurrentAndFutureGatewayDowntimeData(): Base\PublicCollection
    {
        // Currently we are fetching only downtimes with null terminal id
        // as only a particular gateway terminal having a systemic downtime hasn't
        // been encountered yet. Will need to modify this later when we deal with
        // such downtimes
        $downtimes = $this->repo->gateway_downtime
                                ->fetchCurrentAndFutureDowntimes();

        return $downtimes;
    }

    public function archiveGatewayDowntimes(): array
    {
        $downtimes = $this->repo->gateway_downtime
            ->fetchPastDowntimes();

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_ARCHIVE_STARTED,
            [
                'total_records' => $downtimes->count(),
            ]);

        foreach ($downtimes as $downtime)
        {
            $this->repo->transaction(function () use ($downtime)
            {
                $this->repo->gateway_downtime->archive($downtime);
            });
        }

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_ARCHIVE_COMPLETED,
            [
                'total_records' => $downtimes->count(),
            ]);

        return [
            'total_records' => $downtimes->count(),
        ];
    }

    /**
     * Fetches downtime information at the current time
     * @param  array  $methods Array of methods for which to fetch downtime
     *                         If empty, then downtime for all methods are returned
     * @return Collection      Collection of downtimes
     */
    public function getPublicGatewayDowntimeData(array $methods = []): Base\PublicCollection
    {
        // set the from time to current time. For all practical
        // purposes, this is usually not set by input.
        $input = [
            Entity::BEGIN => Carbon::now()->getTimestamp(),
        ];

        // Currently we are fetching only downtimes with null terminal id
        // as only a particular gateway terminal having a systemic downtime hasn't
        // been encountered yet. Will need to modify this later when we deal with
        // such downtimes
        $downtimes = $this->repo->gateway_downtime
                                ->fetchDowntimesWithoutTerminal($input, $methods);

        return $downtimes;
    }

    public function getExternalApiHealthData(array $input)
    {
        $this->trace->info(TraceCode::GATEWAY_HEALTH_CHECK_REQUEST, $input);

        if ($this->app['config']->get('applications.health_check_client.mock') === true)
        {
            return (new Services\Mock\HealthCheckClient)->check($input);
        }

        return (new Services\HealthCheckClient)->check($input);
    }

    public function fetchMostRecentActive(array $input, $fetchByKeys = [])
    {
        return $this->repo->gateway_downtime->fetchMostRecentActive($input, $fetchByKeys);
    }

    public function fetchActiveDowntime($param)
    {
        return $this->repo->gateway_downtime->fetchActiveDowntime($param);
    }

    /**
     * Gets the list of relevant downtimes from database
     *
     * @param  array        $terminals Set of all terminals
     * @param  array        $input     Array containing payment, merchant enttties
     * @return Base\PublicCollection collection of applicable downtimes
     */
    public function getApplicableDowntimesForPayment(
                        array $terminals,
                        array $input): Base\PublicCollection
    {
        $params = $this->getDowntimeFetchParams($terminals, $input);

        $downtimes = $this->repo
                          ->gateway_downtime
                          ->fetchApplicableDowntimesForPayment($params);

        return $downtimes;
    }

    public function getApplicableDowntimesForPaymentForRouter(
        array $terminals,
        array $payment)
    {
        $params = $this->buildFetchDowntimeRequest($terminals, $payment);

        $downtimes = $this->repo
            ->gateway_downtime
            ->fetchApplicableDowntimesForPayment($params);

        $this->trace->info(TraceCode::GET_GATEWAY_DOWNTIME_REQUEST, $downtimes->toArrayAdmin());

        return $downtimes;
    }

    /**
     * @deprecated This approach is not being used now.
     * @see Core::createDowntimeIfApplicable for current implementation
     *
     * @param string $gateway
     * @param array $gatewayData
     */
    public function createForGatewayException(string $gateway, array $gatewayData)
    {
        $method = $gatewayData['payment']['method'];

        $allowed = (new GatewayErrorThrottler($gateway, $method))->attempt();

        if ($allowed === false)
        {
            $this->attemptCreation($gateway, $gatewayData);
        }
    }

    protected function attemptCreation(string $gateway, array $gatewayData)
    {
        $resource = self::GATEWAY_EXCEPTION_DOWNTIME . $gateway;

        //
        // It's possible that multiple failures at the same time will get past
        // the uniqueness check in create (since we do the DB query before the
        // creation). For this reason, we're adding a mutex lock around creation.
        // If aquisition fails, that's fine, we don't need to retry since the
        // parallel process will end up creating the same gateway downtime anyway.
        //
        if ($this->mutex->acquire($resource) === false)
        {
            return;
        }

        $now = Carbon::now()->getTimestamp();

        $duration = $this->getDuration();

        $this->create([
            Entity::GATEWAY     => $gateway,
            Entity::REASON_CODE => ReasonCode::HIGHER_ERRORS,
            Entity::BEGIN       => $now,
            Entity::END         => $now + $duration,
            Entity::METHOD      => $gatewayData['payment']['method'],
            Entity::SOURCE      => Source::INTERNAL,
            Entity::COMMENT     => 'Downtime created by internal gateway response analysis and throttling',
            Entity::SCHEDULED   => false,
        ]);

        $this->mutex->release($resource);
    }

    /**
     * @param string $gateway
     * @param array $gatewayData
     * @param int $duration
     */
    protected function attemptDowntimeCreation(string $gateway, array $gatewayData, int $duration)
    {
        $resource = self::GATEWAY_EXCEPTION_DOWNTIME . $gateway . $duration;

        //
        // It's possible that multiple failures at the same time will get past
        // the uniqueness check in create (since we do the DB query before the
        // creation). For this reason, we're adding a mutex lock around creation.
        // If acquisition fails, that's fine, we don't need to retry since the
        // parallel process will end up creating the same gateway downtime anyway.
        //
        if ($this->mutex->acquire($resource) === false)
        {
            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_ALREADY_CREATING_DOWNTIME, [
                'resource'                         => $resource,
                'gateway'                          => $gateway,
                'duration'                         => $duration,
            ]);

            return;
        }

        $now = Carbon::now()->getTimestamp();

        try
        {
            $this->create([
                Entity::GATEWAY     => $gateway,
                Entity::REASON_CODE => ReasonCode::HIGHER_ERRORS,
                Entity::BEGIN       => $now,
                Entity::END         => $now + $duration,
                Entity::METHOD      => $gatewayData['payment']['method'],
                Entity::SOURCE      => Source::INTERNAL,
                Entity::COMMENT     => 'Downtime created by internal gateway response analysis for sliding window: ' . $duration . ' seconds',
                Entity::SCHEDULED   => false,
            ],
                [
                    Entity::GATEWAY,
                    Entity::ISSUER,
                    Entity::METHOD,
                    Entity::SOURCE,
                    Entity::NETWORK,
                    Entity::COMMENT,
                ],
                false);
        }
        catch (\Throwable $e)
        {
            // This can happen due to duplicate downtime creation.
            $this->trace->traceException($e, Logger::WARNING);
        }
        finally
        {
            $this->mutex->release($resource);
        }
    }

    protected function getDuration(): int
    {
        $redis = $this->app['redis']->connection('mutex_redis');

        $settings = $redis->hgetall(ConfigKey::DOWNTIME_THROTTLE);

        return $settings['duration'] ?? self::DEFAULT_DOWNTIME_DURATION;
    }

    /**
     * Forms the query params for fetching downtimes,
     * based on payment and list of terminal gateways.
     *
     * Common fields :
     * 1. `method`  : netbanking/card/emi
     * 2. `gateway` : list of terminal gateways & `all`
     * 3. `partial` : false (since we do not want to filter
     *                       downtimes with low success rate)
     *
     * Fields for netbanking : (not implemented, keeping for ref)
     * 1. `method` : netbanking
     * 2. `issuer` : bank name
     *
     * Fiels for Card/Emi :
     * 1. `method`    : [card, emi]
     * 2. `network`   : card network & `all`
     * 3. `card_type` : card type & `all`
     * 4. `issuer`    : if present & `all`, else, only `all`
     *
     * @param $terminals array
     * @param $input     array
     * @return $params   array
     */
    protected function getDowntimeFetchParams(array $terminals, array $input): array
    {
        $payment = $input['payment'];

        $gateways = $this->getTerminalGateways($terminals);

        $gateways[] = Entity::ALL;

        $now = Carbon::now()->getTimestamp();

        $params = [
            Entity::GATEWAY => $gateways,
            Entity::PARTIAL => false,
            Entity::BEGIN   => $now,
        ];

        switch ($payment->getMethod())
        {
            case Payment\Method::CARD:
            case Payment\Method::EMI:
                $this->fillCardDetails($params, $payment);

                return $params;
                break;

            case Payment\Method::UPI:
                $this->fillUpiDetails($params, $payment);

                return $params;
                break;
        }

        return [];
    }

    protected function buildFetchDowntimeRequest(array $terminals, array $payment): array
    {
        $gateways = $this->getTerminalGateways($terminals);

        $gateways[] = Entity::ALL;

        $now = Carbon::now()->getTimestamp();

        $params = [
            Entity::GATEWAY => $gateways,
            Entity::PARTIAL => false,
            Entity::BEGIN   => $now,
        ];

        switch ($payment['method'])
        {
            case Payment\Method::CARD:
            case Payment\Method::EMI:
                $this->fillCardDetailsForRouter($params, $payment);

                return $params;
                break;

            case Payment\Method::UPI:
                $params[Entity::METHOD] = [Payment\Method::UPI];

                return $params;
                break;
        }

        return [];
    }

    /**
     * Sets card related data in params
     *
     * @param $params by reference
     * @param $payment Payment\Entity
     */
    protected function fillCardDetails(array & $params, Payment\Entity $payment)
    {
        $params[Entity::METHOD] = [Payment\Method::CARD, Payment\Method::EMI];

        $params[Entity::NETWORK] = [$payment->card->getNetworkCode(),
                                    Entity::ALL,
                                    Entity::UNKNOWN,
                                    strtolower(Entity::UNKNOWN)];

        $params[Entity::CARD_TYPE] = [$payment->card->getType(),
                                      Entity::ALL,
                                      Entity::UNKNOWN,
                                      strtolower(Entity::UNKNOWN)];

        $params[Entity::ISSUER] = [Entity::ALL,
                                   Entity::UNKNOWN,
                                   strtolower(Entity::UNKNOWN)];

        $issuer = $payment->card->getIssuer();

        if (empty($issuer) === false)
        {
            $params[Entity::ISSUER][] = $issuer;
        }
    }

    // fillCardDetailsForRouter builds the request for fetching gateway downtimes from DB for Card payments
    protected function fillCardDetailsForRouter(array & $params, $payment)
    {
        $params[Entity::METHOD] = [Payment\Method::CARD, Payment\Method::EMI];

        $params[Entity::NETWORK] = [$payment['card']['network_code'],
            Entity::ALL,
            Entity::UNKNOWN,
            strtolower(Entity::UNKNOWN)];

        $params[Entity::CARD_TYPE] = [$payment['card']['type'],
            Entity::ALL,
            Entity::UNKNOWN,
            strtolower(Entity::UNKNOWN)];

        $params[Entity::ISSUER] = [Entity::ALL,
            Entity::UNKNOWN,
            strtolower(Entity::UNKNOWN)];

        if (isset($payment['card']['issuer']) === true)
        {
            $params[Entity::ISSUER][] = $payment['card']['issuer'];
        }
    }

    protected function fillUpiDetails(array & $params, Payment\Entity $payment)
    {
        $params[Entity::METHOD] = [Payment\Method::UPI];
    }

    /**
     * Gets the list of gateways from the given terminals
     *
     * Sometimes the list might have duplicates,
     * as more than one terminal can have same gateway
     * Hence, we use `array_unique` before returning
     *
     * @param $terminals array of Terminal\Entity
     * @return $terminalGateways array
     */
    protected function getTerminalGateways(array $terminals) : array
    {
        $gateways = array_pluck($terminals, 'gateway');

        $gateways = array_values(array_unique($gateways));

        return $gateways;
    }

    public static function getMode()
    {
        $app = \App::getFacadeRoot();

        // We use the more restricted option as default
        $mode = Mode::LIVE;

        // This blocks writing tests in live mode, but that's
        // acceptable till we have a better way to set mode in tests
        if ($app->runningUnitTests() === true)
        {
            $mode = Mode::TEST;
        }

        // If explicitly sent in the request, use it. This allows for using
        // test mode on production if ever needed for direct auth routes.
        $modeHeader = $app['request']->headers->get('Razorpay-Mode');

        if (empty($modeHeader) === false)
        {
            $mode = $modeHeader;
        }

        // In almost all flows except unit tests and direct auth requests,
        // rzp.mode should be used as source of truth for mode. If this is
        // already set, then it should get highest precedence.
        if (isset($app['rzp.mode']) === true)
        {
            $mode = $app['rzp.mode'];
        }

        return $mode;
    }

    protected function getGatewayDowntimeMetric(): DowntimeMetric
    {
        return $this->app['gateway_downtime_metric'];
    }

    /**
     * This function creates the downtime and
     * update the required metric for downtime detection,
     * if $gatewayDowntimeError is present. Otherwise
     * it just update the required metric for downtime detection.
     * @param array $gatewayData
     */
    public function createDowntimeIfApplicable(array $gatewayData)
    {
        $metrics = $this->getGatewayDowntimeMetric()->getMetrics();

        foreach ($metrics as $gateway => $metric)
        {
            $downtimeMetric = [];

            if (isset($metric[DowntimeMetric::Success]) === true)
            {
                $downtimeMetric = $metric[DowntimeMetric::Success];
            }

            if (isset($metric[DowntimeMetric::Failure]) === true)
            {
                $downtimeMetric = array_merge($downtimeMetric, $metric[DowntimeMetric::Failure]);
            }

            foreach ($downtimeMetric as $errorCode => $count)
            {
                if (Error::isGatewayDowntimeErrorCode($errorCode) === true)
                {
                    $durations = (new GatewayDowntimeDetection($gateway))->gatewayDowntimeDurations($count);

                    foreach ($durations as $duration)
                    {
                        $this->attemptDowntimeCreation($gateway, $gatewayData, $duration);
                    }
                }
                else
                {
                    (new GatewayDowntimeDetection($gateway))->incrementTotalAttempts($count);
                }
            }
        }
    }

    public function createDowntimeV2(array $input)
    {
        $downtimeArray = $this->DowntimeV2Request($input);

        try
        {
            $this->create($downtimeArray);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                Logger::ERROR,
                TraceCode::GATEWAY_DOWNTIME_V2_CREATE_FAILED,
                $input
            );
        }
    }

    public function resolveDowntimeV2(array $input)
    {
        $downtimeArray = $this->DowntimeV2Request($input);

        $downtime = $this->fetchMostRecentActive($downtimeArray);

        if(is_null($downtime) === true)
        {
            throw new Exception\LogicException(
                'DowntimeV2 Trying to resolve a non-existent downtime',
                null,
                [
                    'DowntimeData' => $downtimeArray,
                ]
            );
        }

        $downtime->setEndTime($input['downtime_recover_time']);

        $this->repo->saveOrFail($downtime);
    }

    public function DowntimeV2Request(array $input)
    {
        $downtimeArray = [
            Entity::GATEWAY       => Entity::ALL,
            Entity::BEGIN         => $input['downtime_start_time'],
            Entity::COMMENT       => "Message : " . $input['type'],
            Entity::REASON_CODE   => ReasonCode::HIGHER_ERRORS,
            Entity::SOURCE        => Source::DOWNTIME_V2,
            Entity::METHOD        => $input['method'],
            Entity::SCHEDULED     => false,
        ];

        switch ($input['method'])
        {
            case 'upi' :
                if ($input['key'] === DowntimeDetection::PROVIDER)
                {
                    $downtimeArray[Entity::VPA_HANDLE] = $input['value'];
                }
                break;

            case 'card' :
                if ($input['key'] === DowntimeDetection::ISSUER)
                {
                    $downtimeArray[Entity::ISSUER] = $input['value'];
                    $downtimeArray[Entity::NETWORK] = Entity::UNKNOWN;
                }
                elseif ($input['key'] === DowntimeDetection::NETWORK)
                {
                    $downtimeArray[Entity::NETWORK] = $input['value'];
                    $downtimeArray[Entity::ISSUER] = Entity::UNKNOWN;
                }
                break;

            case 'netbanking' :
                if ($input['key'] === DowntimeDetection::BANK)
                {
                    $downtimeArray[Entity::ISSUER] = $input['value'];
                }
                break;
        }

        return $downtimeArray;
    }
}
