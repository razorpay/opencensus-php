<?php

namespace RZP\Error\P2p;

class Error extends \RZP\Error\Error
{
    protected function setInternalErrorCode($code)
    {
        self::checkErrorCode($code);

        $this->setAttribute(self::INTERNAL_ERROR_CODE, $code);
    }

    protected function setAction($code = null)
    {
        if (isset(Action::MAP[$code]) === true)
        {
            $this->setAttribute(self::ACTION, Action::MAP[$code]);
        }
    }

    protected function getDescriptionFromErrorCode($code)
    {
        $code = strtoupper($code);

        if (defined(PublicErrorDescription::class . '::' . $code))
        {
            return constant(PublicErrorDescription::class.'::'.$code);
        }
    }

    public static function checkErrorCode($code)
    {
        if (defined(ErrorCode::class.'::'.$code) === false)
        {
            throw new Exception\InvalidArgumentException('ErrorCode: ' . $code . ' is not defined');
        }
    }

    protected function handleBadRequestErrors()
    {
        parent::handleBadRequestErrors();

        $code = $this->getInternalErrorCode();

        switch($code)
        {
            case ErrorCode::BAD_REQUEST_MERCHANT_NOT_ALLOWED_ON_HANDLE:
            case ErrorCode::BAD_REQUEST_INVALID_MERCHANT_IN_CONTEXT:
                $this->setHttpStatusCode(401);

                break;
        }
    }

    public function setGatewayErrorCodeAndDesc($code, $desc)
    {
        parent::setGatewayErrorCodeAndDesc($code, $desc);

        $internalErrorCode = $this->getInternalErrorCode();

        switch($internalErrorCode)
        {
            case ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE:
                $this->setDesc($code . '. ' . $desc);

                break;
        }
    }

}
