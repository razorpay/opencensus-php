<?php

namespace EE\Error;

class Error
{
    protected $attributes = array(
        'class' => null,
        'code' => null,
        'gateway_error_code' => null,
        'gateway_error_desc' => null,
        'data' => null,
        'desc' => null,
        'field' => null);

    protected $publicError = null;

    public function __construct($code, $desc = null, $field = null)
    {
        $data = null;

        $this->fill($code, $desc, $field);
    }

    public function fill($code, $desc = null, $field = null)
    {
        $this->setCode($code);

        $this->setClass($code);

        // $this->setData($data);

        $this->setDesc($desc);

        $this->setField($field);

        $this->constructPublicError();
    }

    public function setGatewayErrorCodeAndDesc($code, $desc)
    {
        $this->attributes['gateway_error_code'] = $code;

        $this->attributes['gateway_error_desc'] = $desc;
    }

    protected function setCodeAndClass($code)
    {
        $this->setCode($code);

        $this->setClass($code);
    }

    protected function setCode($code)
    {
        if ($code === 0)
            return;

        if (ErrorCode::errorCodeExists($code) === false)
        {
            throw new \InvalidArgumentException('Error code is not valid. Code: ' . $code);
        }

        $this->attributes['code'] = $code;
    }

    protected function setClass($code)
    {
        if ($code === 0)
            return;

        $pos = strpos($code, '_');

        $class = substr($code, 0, $pos);

        if (defined(__NAMESPACE__.'\ErrorClass::'.$class) === false)
        {
            throw \InvalidNewArgument($class . ' is not a valid class');
        }

        $this->attributes['class'] = $class;
    }

    protected function setData($data)
    {
        $this->attributes['data'] = $data;
    }

    protected function setDesc(/* string */ $desc = null)
    {
        if ($desc === null)
            return;

        if (! is_string($desc))
        {
            throw new \InvalidArgumentException('desc should be string');
        }

        $this->attributes['desc'] = $desc;
    }

    protected function setField($field)
    {
        $this->attributes['field'] = $field;
    }

    protected function getAttribute($attr)
    {
        return $this->attributes[$attr];
    }

    public function getGatewayErrorCode()
    {
        return $this->attributes['gateway_error_code'];
    }

    public function getGatewayErrorDesc()
    {
        return $this->attributes['gateway_error_desc'];
    }

    protected function constructPublicError()
    {
        $this->publicError = new PublicError();

        switch ($this->getAttribute('class'))
        {
            case ErrorClass::GATEWAY:
                $this->handleGatewayErrors();
                break;
            case ErrorClass::CARD:
                $this->handleCardErrors();
                break;
            case ErrorClass::UDF:
                $this->handleUdfErrors();
                break;
            case ErrorClass::BAD_REQUEST:
                $this->handleBadRequestErrors();
                break;
            case ErrorClass::DB:
                // @todo fill this case
                $this->handleDBErrors();
                break;
            case ErrorClass::TRACE:
                // @todo
                break;
            default:
                throw new \InvalidArgumentException('Not a valid class');
        }

        $publicError = new PublicError($code, $description);
    }

    public function getPublicError()
    {
        return $this->publicError;
    }

    public function getCode()
    {
        return $this->getAttribute('code');
    }

    public function getDesc()
    {
        return $this->getAttribute('desc');
    }

    public function getField()
    {
        return $this->getAttribute('field');
    }

    public function getPublicErrorCode()
    {
        return $this->publicError->getErrorCode();
    }

    public function getPublicErrorDescription()
    {
        return $this->publicError->getErrorDescription();
    }

    protected function handleBadRequestErrors()
    {
        $code = $this->getCode();
        $desc = $this->getDesc();
        $field = $this->getField();

        $this->publicError->setBadRequestError(
            $desc,
            $field);
    }

    protected function handleGatewayErrors()
    {
        $code = $this->getAttribute('code');

        switch ($code)
        {
            case ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT:
                $this->publicError->setGatewayTimeout();
                return;

            default:
                $this->publicError->setGatewayError();
                return;
        }
    }

    protected function handleCardErrors()
    {
        $code = $this->getAttribute('code');
        $desc = $this->getAttribute('desc');

        $this->publicError->setCardError($code, $desc);
    }

    protected function handleAPIErrors()
    {
        $code = $this->getAttribute('code');
        $desc = $this->getAttribute('desc');

        switch ($code)
        {
            case Error::API_CARD_INVALID_NAME:
            case Error::API_CARD_INVALID_EXPIRY_MONTH:
            case Error::API_CARD_INVALID_EXPIRY_YEAR:
            case Error::API_CARD_INVALID_CVC:
            case Error::API_CARD_INVALID_BRAND:
            case Error::API_CARD_INVALID_AMOUNT:
            case Error::API_CARD_INVALID_UDF:
            case Error::API_CARD_INVALID_NUMBER:
            case Error::API_CARD_EXPIRED:
            case Error::API_TRANSACTION_INVALID_CURRENCY:
            case Error::API_TRANSACTION_INVALID_AMOUNT:
            default:
                throw new \InvalidArgumentException('This part is to be done.');
                break;
        }
    }

    protected function handleServerErrors()
    {
        $code = $this->getAttribute('code');
        $data = $this->getAttribute('data');

        switch ($code)
        {
            case Error::DB_RECORD_NOT_FOUND:
            case Error::DB_QUERY_FAILED:
            case Error::DB_QUERY_INVALID_SYNTAX:
                break;
        }
    }
}