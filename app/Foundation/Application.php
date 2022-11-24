<?php

namespace RZP\Foundation;

use RZP\Constants\Environment;

class Application extends \Illuminate\Foundation\Application
{
    /**
     * Used during unit tests to clear out all the data structures
     * that may contain references.
     * Without this phpunit runs out of memory.
     *
     * @return void
     */
    public function flush(): void
    {
        parent::flush();

        // Resetting all the properties to empty array
        $this->bootingCallbacks         = [];
        $this->bootedCallbacks          = [];
        $this->middlewares              = [];
        $this->serviceProviders         = [];
        $this->loadedProviders          = [];
        $this->deferredServices         = [];
        $this->reboundCallbacks         = [];
        $this->resolvingCallbacks       = [];
        $this->afterResolvingCallbacks  = [];
        $this->globalResolvingCallbacks = [];
        $this->buildStack               = [];
    }

    /**
     * Determines if code is being run inside a queue worker.
     * There are a few flows where in core layer we check for
     * basic auth type. Ideally the application's core logic
     * should be open to run from HTTP or/and CLI(command/cronstab,
     * queue) equally, but in those few places it's difficult
     * to handle in current situation.
     *
     * Now with payments being created via queue (via batch entity
     * for type=bank_transfer, ref: #6259) we need following check
     * and decide accordingly.
     *
     * @return bool
     */
    public function runningInQueue(): bool
    {
        //
        // Unit tests are always running in CLI mode and queues
        // are sync as well.
        //
        return (($this->runningInConsole() === true) and
                ($this->runningUnitTests() === false));
    }

    public function isEnvironmentQA(): bool
    {
        return Environment::isEnvironmentQA($this->env);
    }

    public function isEnvironmentProduction(): bool
    {
        return ($this->env === Environment::PRODUCTION);
    }

    /**
     * Determine if the application is running unit tests.
     *
     * @return bool
     */
    public function runningUnitTests()
    {
        return $this->bound('env') && str_contains($this['env'], 'testing');
    }
}
