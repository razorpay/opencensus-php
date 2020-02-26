<?php

namespace RZP\Exception\P2p;

use Exception;
use Throwable;

// This is an intermediate exception just to make sure that processors do not throw exception of their own
class ProcessorException extends Exception
{
    public function __construct(Throwable $previous = null)
    {
        parent::__construct('Should never come here', 0, $previous);
    }

    public function getActual(): Throwable
    {
        return $this->getPrevious();
    }
}
