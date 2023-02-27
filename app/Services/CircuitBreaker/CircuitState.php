<?php

namespace RZP\Services\CircuitBreaker;

/**
 * Class CircuitState
 */
class CircuitState
{
    /** @var string OPEN Define that the circuit is open. */
    const OPEN = 'open';

    /** @var string CLOSED Define that the circuit is clsoed. */
    const CLOSED = 'close';

    /** @var string HALF_OPEN Define that the circuit is half-open. */
    const HALF_OPEN = 'half_open';

    public function open()
    {
        return self::OPEN;
    }

    public function closed()
    {
        return self::CLOSED;
    }

    public function halfOpen()
    {
        return self::HALF_OPEN;
    }

}
