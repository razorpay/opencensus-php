<?php declare(strict_types=1);

namespace RZP\Services\CircuitBreaker\Store;

use RZP\Services\CircuitBreaker\CircuitState;

/**
 * Class CircuitBreakerStore
 *
 */
interface  CircuitBreakerStore
{
    /**
     * Return the current circuit state.
     *
     * @param string $serviceName Name of the service for the circuit.
     *
     * @return string
     */
    public function getState(string $serviceName): string;

    /**
     * Increment a failure in the total of failures for a service.
     *
     * @param string $serviceName Service name to increment a failure.
     * @param int    $timeWindow  Time for each error be stored.
     */
    public function addFailure(string $serviceName, int $timeWindow): void;

    /**
     * Get the total of failures for a specific service.
     *
     * @param string $serviceName Service name to check the total of failures.
     *
     * @return int
     */
    public function getTotalFailures(string $serviceName): int;

    /**
     * Open the circuit for a specific time.
     *
     * @param string $serviceName Service name of the circuit to be opened.
     * @param int    $timeOpen    Time in second that the circuit will stay open.
     */
    public function openCircuit(string $serviceName, int $timeOpen): void;

    /**
     * Define a succeed request for this service and close the circuit.
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    public function closeCircuit(string $serviceName): void;

    /**
     * Define the circuit as half-open.
     *
     * @param string $serviceName Service name
     * @param int    $timeOpen    Time that the circuit will be half-open.
     */
    public function setCircuitHalfOpen(string $serviceName, int $timeOpen): void;

}
