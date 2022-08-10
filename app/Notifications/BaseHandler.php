<?php

namespace RZP\Notifications;

use App;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Foundation\Application;

abstract class BaseHandler
{
    protected $args;
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;
    /**
    * Trace instance used for tracing
    * @var Trace
    */
    protected $trace;

    /**
     * @var array
     */
    protected $files;

    public function __construct(array $args,$files=null)
    {
        $this->args = $args;

        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->files=$files;

    }

    /**
     * This method is responsible for sending notification through various channels
     * depending on the event.
     *
     * @param string $event
     *
     * @throws LogicException
     */
    public function sendForEvent(string $event, bool $traceArguments = true)
    {
        $channels = $this->getSupportedchannels($event);

        foreach ($channels as $channel)
        {
            $serviceInstance = Factory::getInstance($channel, $event, $this->getNamespace(), $this->args,$this->files);

            $serviceInstance->send();

            $traceData = [
                'type'     => 'sendForEvent',
                'channel'  => $channel
            ];

            if ($traceArguments === true)
            {
                $traceData = array_merge($traceData, [
                    'merchant' => $this->args,
                ]);
            }

            $this->trace->info(TraceCode::SEND_NOTIFICATION, $traceData);
        }
    }

    /**
     * This method is responsible to provide the list of supported channels
     * for the given event
     *
     * @param string $event
     *
     * @return mixed
     */
    protected abstract function getSupportedchannels(string $event);

    /**
     * Utility method to provide namespace of current class
     *
     * @return false|string
     */
    protected function getNamespace()
    {
        $clazz = get_called_class();

        return substr($clazz, 0, strrpos($clazz, "\\"));
    }
}
