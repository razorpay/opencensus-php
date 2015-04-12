<?php

namespace EE\Exception;

use EE\Error\Error;
use EE\Error\ErrorCode;

class PaymentVerificationException extends RecoverableException
{
    public function __construct(
        $data = [],
        \Exception $previous = null)
    {
        $code = ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED;

        $this->error = new Error($code, null, null, $data);

        $this->data = $data;

        $message = json_encode($data);

        parent::__construct($message, $code, $previous);
    }
}