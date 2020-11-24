<?php

namespace RZP\Notifications;


abstract class BaseHandler
{
    protected $args;

    public function __construct(array $args)
    {
        $this->args = $args;
    }

    /**
     * This method is responsible for sending notification through various channels
     * depending on the event.
     * @param string $event
     * @throws \RZP\Exception\LogicException
     */
    public function sendForEvent(string $event)
    {
        $channels = $this->getSupportedchannels($event);

        foreach ($channels as $channel)
        {
            $serviceInstance = Factory::getInstance($channel, $event, $this->getNamespace(), $this->args);

            $serviceInstance->send();
        }
    }

    /**
     * This method is responsible to provide the list of supported channels
     * for the given event
     * @param string $event
     * @return mixed
     */
    protected abstract function getSupportedchannels(string $event);

    /**
     * Utility method to provide namespace of current class
     * @return false|string
     */
    protected function getNamespace()
    {
        $clazz = get_called_class();
        return substr($clazz, 0, strrpos($clazz, "\\"));
    }
}
