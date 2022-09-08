<?php


namespace RZP\Models\Merchant\Acs\EventProcessor;


use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Error\PublicErrorDescription;

class EventProcessorFactory
{
    // Clients can add implementation of IEventProcessor
    private $eventProcessorClassList = [
        LoggingEventProcessor::class
    ];

    public function SetEventProcessorClassList(array $classList)
    {
        $this->eventProcessorClassList = $classList;
    }

    /**
     * Returns array of Subscribed Event processor object
     *
     * @return IEventProcessor[]
     * @throws LogicException
     */
    public function GetEventProcessors(): array
    {
        $eventProcessors = [];

        foreach ($this->eventProcessorClassList as $eventProcessor) {
            $object = new $eventProcessor();

            if ($object instanceof IEventProcessor === true) {
                array_push($eventProcessors, $object);
            } else {
                throw new LogicException(
                    PublicErrorDescription::SERVER_ERROR_INVALID_ACS_EVENT_PROCESSOR,
                    ErrorCode::SERVER_ERROR,
                    [Constant::EVENT_PROCESSOR_CLASS => $eventProcessor]);
            }
        }

        return $eventProcessors;
    }
}
