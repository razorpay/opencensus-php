<?php

namespace RZP\Exception;

use RZP\Error\Error;

class TwirpException extends BaseException
{
    public function __construct($twirpResponse)
    {
        $error = Error::fromTwirpResponse($twirpResponse);

        parent::__construct($error->getDescription(), $error->getInternalErrorCode());

        $this->setError($error);
    }
}
