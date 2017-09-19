<?php

namespace RZP\Foundation;

class Application extends \Illuminate\Foundation\Application
{
    /**
     * Used during unit tests to clear out all the data structures
     * that may contain references.
     * Without this phpunit runs out of memory.
     */
    public function flush()
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
}
