<?php


namespace RZP\Exception;

use RZP\Services\CircuitBreaker\CircuitState;
use Throwable;

/**
 * Class CircuitException
 *
 * @package RZP\Services\CircuitBreaker\Exception
 *
 */
class CircuitException extends \Exception
{
    /** @var string $serviceName Name of the service related to the error. */
    private $serviceName;

    /**
     * CircuitException constructor.
     *
     * @param string         $serviceName
     * @param string         $message
     * @param int            $code
     */
    public function __construct(
        string $serviceName,
        $message = "",
        $code = 0
    )
    {
        parent::__construct($message, $code);

        $this->serviceName = $serviceName;
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }
}
