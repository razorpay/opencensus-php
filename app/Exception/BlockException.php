<?php

namespace RZP\Exception;

class BlockException extends ThrottleException
{
    /**
     * @var string
     */
    protected $message = 'Request blocked!';
}
