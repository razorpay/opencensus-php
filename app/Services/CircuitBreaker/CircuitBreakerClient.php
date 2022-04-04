<?php

namespace RZP\Services\CircuitBreaker;

use App;
use RZP\Services\CircuitBreaker\Store\CircuitBreakerStore;
use RZP\Exception\CircuitException;

/**
 * Class CircuitBreakerClient
 *
 * @package RZP\Services\CircuitBreaker\
 */
class CircuitBreakerClient
{
    /** @var CircuitBreakerStore $circuitBreaker */
    private $circuitBreaker;

    /** @var array $settings Circuit Breaker settings. */
    private $settings;

    /**
     * CircuitBreaker constructor.
     *
     * @param CircuitBreakerStore $circuitBreaker
     * @param array               $settings Custom settings.
     */
    public function __construct(
        CircuitBreakerStore $circuitBreaker,
        array $settings = []
    )
    {
        $this->circuitBreaker = $circuitBreaker;

        $defaultSettings = [
            Constant::EXCEPTIONS_ON => true,
            Constant::TIME_WINDOW => 60,
            Constant::TIME_OUT_OPEN => 30,
            Constant::TIME_OUT_HALF_OPEN => 20,
            Constant::TOTAL_FAILURES => 50
        ];

        $this->settings = array_merge($defaultSettings, $settings);
    }

    public function changeConfiguration(array $settings = [])
    {
        $this->settings = array_merge($this->settings,$settings);
    }

    /**
     * Check if the service is available.
     *
     * @param string $serviceName Service name to be checked.
     *
     * @return bool
     * @throws \Exception
     */
    public function canPass(string $serviceName): bool
    {
        $circuitState = $this->circuitBreaker->getState($serviceName);

        if ($circuitState === CircuitState::OPEN()) {
            if ($this->settings[Constant::EXCEPTIONS_ON] === true) {
                throw new CircuitException($serviceName, 'The circuit is open.');
            }

            return false;
        }

        return true;
    }

    /**
     * Reports a service failure.
     *
     * @param string $serviceName Service name to add a new failure.
     */
    public function failed(string $serviceName): void
    {
        $this->circuitBreaker->addFailure($serviceName, $this->settings[Constant::TIME_WINDOW]);

        $totalFailures = $this->circuitBreaker->getTotalFailures($serviceName);
        $circuitState = $this->circuitBreaker->getState($serviceName);

        if ($circuitState === CircuitState::HALF_OPEN()
            || $totalFailures >= $this->settings[Constant::TOTAL_FAILURES]
        ) {
            $timeOutOpen = $this->settings[Constant::TIME_OUT_OPEN];
            $timeOutHalfOpen = $this->settings[Constant::TIME_OUT_HALF_OPEN];

            $this->circuitBreaker->openCircuit($serviceName, $timeOutOpen);
            $this->circuitBreaker->setCircuitHalfOpen($serviceName, ($timeOutOpen + $timeOutHalfOpen));

        }
    }

    /**
     * Define that the request was succeed.
     *
     * @param string $serviceName Name of the service to inform the success.
     */
    public function succeed(string $serviceName): void
    {
        $this->circuitBreaker->closeCircuit($serviceName);
    }
}
