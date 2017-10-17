<?php

namespace RZP\Exception;

use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Verify;

class PaymentVerificationException extends RecoverableException
{
    /**
     * The verify object containing all data
     * @var Verify|null
     */
    protected $verify = null;

    /**
     * Action that need to be performed,
     * after the exception is catched by parent caller
     */
    protected $action = null;

    protected $error;

    protected $data;

    /**
     * PaymentVerificationException constructor.
     * @param array           $data This is the verify response received from verify object's getDataToTrace()
     * @param Verify          $verify
     * @param string|null     $action
     * @param string          $code
     * @param \Exception|null $previous
     */
    public function __construct(
        $data,
        $verify,
        $action = null,
        $code = ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        \Exception $previous = null)
    {
        $this->error = new Error($code, null, null, $data);

        $this->data = $data;

        $this->verify = $verify;

        $message = json_encode($data);

        $this->setAction($action);

        parent::__construct($message, $code, $previous);
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function getVerifyObject()
    {
        return $this->verify;
    }
}
