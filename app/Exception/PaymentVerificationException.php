<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Verify;

class PaymentVerificationException extends RecoverableException
{
    /**
     * The verify object containing all data
     * @var Verify
     */
    protected $verify = null;

    /**
     * PaymentVerificationException constructor.
     * @param string $data This is the verify response received from verify object's getDataToTrace()
     * @param Verify $verify
     * @param \Exception|null $previous
     */
    public function __construct(
        $data,
        $verify,
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