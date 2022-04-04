<?php


namespace RZP\Services\CircuitBreaker\Store;

use Cache;
use App;
use RZP\Trace\TraceCode;
use RZP\Services\CircuitBreaker\CircuitState;
use RZP\Services\CircuitBreaker\KeyHelper;
use Predis\Client;

/**
 * Class CircuitBreakerRedisStore

 */
class CircuitBreakerRedisStore implements CircuitBreakerStore
{
    /** @var KeyHelper $keyHelper Helper to use with keys. */
    private $keyHelper;

    /**
     * RedisCircuitBreaker constructor.
     *
     * @param KeyHelper|null $keyHelper
     */
    public function __construct(KeyHelper $keyHelper = null)
    {
        $this->keyHelper = $keyHelper ? $keyHelper : new KeyHelper;
    }

    /**
     * Return the current circuit state.
     *
     * @param string $serviceName Name of the service for the circuit.
     *
     * @return string
     */
    public function getState(string $serviceName): string
    {
        $circuitState = CircuitState::CLOSED();

        $halfOpenCircuitKey = $this->keyHelper->generateKeyHalfOpen($serviceName);
        $openCircuitKey     = $this->keyHelper->generateKeyOpen($serviceName);

        App::getFacadeRoot()['trace']->info(TraceCode::CIRCUIT_BREAKER_STATE,["state"=>"getState",
                                                                      "halfOpenCircuitKey"=>$halfOpenCircuitKey,
                                                                      "openCircuitKey"=>$openCircuitKey,
                                                                      "openCircuitValue"=>Cache::get($openCircuitKey),
                                                                      "halfOpenCircuitValue"=>Cache::get($halfOpenCircuitKey)]);

        if (empty(Cache::get($openCircuitKey)) === false)
        {
            $circuitState = CircuitState::OPEN();
        }
        else
        {
            if (empty(Cache::get($halfOpenCircuitKey)) == false)
            {
                $circuitState = CircuitState::HALF_OPEN();
            }
        }

        return $circuitState;
    }

    /**
     * Increment a failure in the total of failures for a service.
     *
     * @param string $serviceName Service name to increment a failure.
     * @param int    $timeWindow  Time for each error be stored.
     *
     */
    public function addFailure(string $serviceName, int $timeWindow): void
    {
        $keyTotalFailures = $this->keyHelper->generateKeyTotalFailuresToStore($serviceName);

        $totalFailures    = Cache::get($keyTotalFailures);

        if (empty($totalFailures))
        {
            $totalFailures = 0;
        }

        $totalFailures++;

        $dataInserted = Cache::put($keyTotalFailures, $totalFailures, $timeWindow);

        App::getFacadeRoot()['trace']->info(TraceCode::CIRCUIT_BREAKER_FAILED,["addFailure"=>$totalFailures,
                                                                      "keyTotalFailures"=>$keyTotalFailures]);

    }

    /**
     * Get the total of failures for a specific service.
     *
     * @param string $serviceName Service name to check the total of failures.
     *
     * @return int
     */
    public function getTotalFailures(string $serviceName): int
    {
        $key = $this->keyHelper->generateKeyTotalFailuresToStore($serviceName);

        $totalFailures    = Cache::get($key);

        App::getFacadeRoot()['trace']->info(TraceCode::CIRCUIT_BREAKER_TOTAL_FAILURES,["getTotalFailures"=>$totalFailures,
                                                                      "keyTotalFailures"=>$key]);
        return $totalFailures;
    }

    /**
     * Open the circuit for a specific time.
     *
     * @param string $serviceName Service name of the circuit to be opened.
     * @param int    $timeOpen    Time in second that the circuit will stay open.
     *
     */
    public function openCircuit(string $serviceName, int $timeOpen): void
    {
        $key = $this->keyHelper->generateKeyOpen($serviceName);

        App::getFacadeRoot()['trace']->info(TraceCode::CIRCUIT_BREAKER_OPEN,[
            "openCircuit"=>$key,"serviceName"=>$serviceName]);

        $dataInserted = Cache::put($key, true, $timeOpen);

    }

    /**
     * Define a succeed request for this service and close the circuit.
     *
     * @param string $serviceName
     *
     */
    public function closeCircuit(string $serviceName): void
    {
        App::getFacadeRoot()['trace']->info(TraceCode::CIRCUIT_BREAKER_CLOSED,["step"=>"closeCircuit","serviceName"=>$serviceName]);

        $dataDeleted  = Cache::pull($this->keyHelper->generateKeyOpen($serviceName));
        $dataDeleted  = Cache::pull($this->keyHelper->generateKeyHalfOpen($serviceName));

        $dataDeleted  = Cache::pull($this->keyHelper->generateKeyTotalFailuresToStore($serviceName));

    }

    /**
     * Define the circuit as half-open.
     *
     * @param string $serviceName Service name
     * @param int    $timeOpen    Time that the circuit will be half-open.
     *
     */
    public function setCircuitHalfOpen(string $serviceName, int $timeOpen): void
    {
        $key = $this->keyHelper->generateKeyHalfOpen($serviceName);;

        $dataInserted = Cache::put($key, true, $timeOpen);

    }
}
