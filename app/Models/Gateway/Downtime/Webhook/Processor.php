<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

class Processor
{
    public function __construct($source)
    {
        $class = $this->getProcessorClass($source);

        $this->driver = new $class;
    }

    protected function getProcessorClass($source)
    {
        $class = '\RZP\Models\Gateway\Downtime\Webhook\\' . studly_case($source) . 'Processor';

        return $class;
    }

    public function validate($input)
    {
        return $this->driver->validate($input);
    }

    public function process(array $input)
    {
        return $this->driver->process($input);
    }
}