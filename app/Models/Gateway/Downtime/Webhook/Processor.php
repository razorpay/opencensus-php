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
        $class = __NAMESPACE__ .'\\' . studly_case(strtolower($source)) . 'Processor';

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
