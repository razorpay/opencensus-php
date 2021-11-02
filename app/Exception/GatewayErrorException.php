<?php

namespace RZP\Exception;

use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Error\ErrorClass;

class GatewayErrorException extends RecoverableException
{
    protected $twoFaError = false;

    protected $action = null;

    protected $safeRetry;

    protected $twoFaErrorCodes = [
        ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_ENROLLED_FOR_3DSECURE,
        ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED,
        ErrorCode::BAD_REQUEST_PAYMENT_OTP_EXPIRED,
    ];

    public static $safeRetryErrorCodes = [
        ErrorCode::GATEWAY_ERROR_VALIDATION_ERROR,
    ];

    public function __construct(
        $code,
        $gatewayErrorCode = null,
        $gatewayErrorDesc = null,
        $data = [],
        \Exception $previous = null,
        $action = null,
        $safeRetry = false)
    {
        parent::__construct('', $code, $previous);

        $this->initError($code, $data);

        $this->setData($data);

        $this->setGatewayErrorCodeAndDesc(
            $gatewayErrorCode,
            $gatewayErrorDesc);

        $this->setAction($action);

        $this->setSafeRetry($safeRetry);
    }

    public function markTwoFaError()
    {
        $this->twoFaError = true;
    }

    public function hasTwoFaError()
    {
        if ($this->twoFaError === true)
        {
            return true;
        }

        $errorCode = $this->getError()->getInternalErrorCode();

        if ($this->isTwoFaError($errorCode) === true)
        {
            $this->markTwoFaError();

            return true;
        }

        return false;
    }

    public function setGatewayErrorCodeAndDesc($code, $desc)
    {
        $this->error->setGatewayErrorCodeAndDesc($code, $desc);

        $message = $this->error->getDescription();

        $message .= PHP_EOL . 'Gateway Error Code: ' . $code .
                    PHP_EOL . 'Gateway Error Desc: ' . $desc;

        $this->message = $message;
    }

    public function getGatewayErrorCodeAndDesc()
    {
        return [
            $this->error->getGatewayErrorCode(),
            $this->error->getGatewayErrorDesc(),
        ];
    }

    public function getGatewayErrorDesc()
    {
        return $this->error->getGatewayErrorDesc();
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function isCritical()
    {
        return (ErrorClass::isCritical($this->getError()->getClass()) === true);
    }

    public function getAction()
    {
        return $this->action;
    }

    protected function isTwoFaError($errorCode)
    {
        return in_array($errorCode, $this->twoFaErrorCodes);
    }

    public function markSafeRetryTrue()
    {
        $this->safeRetry = true;
    }

    protected function setSafeRetry($safeRetry)
    {
        $this->safeRetry = $safeRetry or ($this->action === Base\Action::AUTHENTICATE);
    }

    public function getSafeRetry()
    {
        return $this->safeRetry;
    }
}
