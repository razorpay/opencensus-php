<?php

namespace EE\Exception;

use EE\Error\Error;
use EE\Error\ErrorCode;

class PaymentVerificationException extends RecoverableException
{
    /**
     * The verify object containing all data
     * @var Gateway\Base\Verify
     */
    protected $verify = null;

    public function __construct(
        $data = [],
        $verify = null,
        \Exception $previous = null)
    {
        $code = ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED;

        $this->error = new Error($code, null, null, $data);

        $this->data = $data;

        $this->verify = $verify;

        $message = json_encode($data);

        parent::__construct($message, $code, $previous);
    }

    public function getVerifyObject()
    {
        return $this->verify;
    }
}