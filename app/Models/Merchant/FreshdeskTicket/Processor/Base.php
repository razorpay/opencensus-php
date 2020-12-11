<?php


namespace RZP\Models\Merchant\FreshdeskTicket\Processor;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\FreshdeskTicket\Service;

class Base extends Service
{

    protected $event;

    /**
     * Base constructor.
     */
    public function __construct($event)
    {
        parent::__construct();

        $this->event = $event;
    }

    public static function getProcessor($event)
    {
        $processor = __NAMESPACE__. '\\' . studly_case($event);

        return new $processor($event);
    }

    public function process($input)
    {
        $this->validateInput($input);

        $this->traceInput($input);

        return $this->processEvent($input);
    }

    protected function validateInput($input)
    {
        (new Validator)->validateInput($this->event, $input);
    }

    protected function processEvent($freshdeskTicket)
    {
        return;
    }

    protected function traceInput($input)
    {
        $redactedInput = $this->getRedactedInput($input);

        $this->trace->info(TraceCode::FRESHDESK_CONSUME_WEBHOOK_INPUT, [
            'event'     => $this->event,
            'input'     => $redactedInput,
        ]);
    }

    protected function getRedactedInput($input)
    {
        return [];
    }

}
