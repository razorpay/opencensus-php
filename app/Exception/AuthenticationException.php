<?php

namespace RZP\Exception;

use RZP\Error\Error;

class AuthenticationException extends RecoverableException
{
    public function __construct(
        $code,
        $autheticationErrorDesc = null,
        \Exception $previous = null)
    {
        parent::__construct('', $code, $previous);

        Error::checkErrorCode($code);

        $error = new Error($code);

        $this->setError($error);

        $this->setAuthenticationErrorDesc($autheticationErrorDesc);

        $desc = $error->getDescription();

        $desc .= PHP_EOL . 'Authentication Error Desc: ' . $autheticationErrorDesc;

        $this->message = $desc;
    }
}
