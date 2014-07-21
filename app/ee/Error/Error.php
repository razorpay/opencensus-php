<?php

namespace EE\Error;

use EE\Exception\InvalidArgumentException;

class Error
{
    protected $attributes = array(
        'class' => null,
        'data' => null,
        'description' => null,
        'field' => null,
        'gateway_error_code' => null,
        'gateway_error_desc' => null);

    protected $publicError = null;

    public function __construct(
        $code,
        $desc = null,
        $field = null,
        $data = null)
    {
        $this->fill($code, $desc, $field, $data);
    }

    public function fill($code, $desc = null, $field = null, $data = null)
    {
        $this->setCode($code);

        $this->setClass($code);

        $this->setData($data);

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
        self::checkErrorCode($code);

        $this->attributes['code'] = $code;
    }

    protected function setClass($code)
    {
        $class = $this->getErrorClassFromErrorCode($code);

        self::checkErrorClass($class);

        $this->attributes['class'] = $class;
    }

    protected function setData($data)
    {
        $this->attributes['data'] = $data;
    }

    protected function setDesc(/* string */ $desc = null)
    {
        if ($desc === null)
        {
            $code = $this->getCode();

            $desc = $this->getDescriptionFromErrorCode($code);

            if ($desc === null)
                return;
        }

        if (! is_string($desc))
        {
            throw new InvalidArgumentException('desc should be string');
        }

        $this->attributes['description'] = $desc;
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

            case ErrorClass::FIELD:
                $this->handleFieldErrors();
                break;

            case ErrorClass::BAD_REQUEST:
                $this->handleBadRequestErrors();
                break;

            case ErrorClass::LOGICAL:
            case ErrorClass::SERVER:
                $this->handleServerErrors();
                break;

            default:
                throw new InvalidArgumentException('Not a valid class');
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
        return $this->getAttribute('description');
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

        $httpStatusCode = 400;

        switch($code)
        {
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED:
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY:
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET:
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED:
                $httpStatusCode = 401;
                break;
            case ErrorCode::BAD_REQUEST_ONLY_HTTPS_ALLOWED:
                $httpStatusCode = 403;
                break;
        }

        $this->publicError->setBadRequestError(
            $desc,
            $field,
            $httpStatusCode);
    }

    protected function handleGatewayErrors()
    {
        $code = $this->getAttribute('code');

        switch ($code)
        {
            case ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT:
                $this->publicError->setGatewayTimeout();
                break;

            case ErrorCode::GATEWAY_ERROR_CAPTURE_GREATER_THAN_AUTH:
                $this->publicError->setBadRequestError(
                    PublicErrorDescription::BAD_REQUEST_CAPTURE_AMOUNT_GREATER_THAN_AUTH);
                break;

            default:
                $this->publicError->setGatewayError();
                break;
        }
    }

    protected function handleCardErrors()
    {
        $code = $this->getAttribute('code');
        $desc = $this->getAttribute('description');
        $field = $this->getAttribute('field');

        $this->publicError->setCardError($code, $desc, $field);
    }

    protected function handleFieldErrors()
    {
        $code = $this->getAttribute('code');
        $desc = $this->getAttribute('description');
        $field = $this->getAttribute('field');

        $this->publicError->setFieldError($code, $desc, $field);
    }

    protected function handleServerErrors()
    {
        $this->publicError->setServerError();
    }

    protected function getErrorArray()
    {
        return $this->attributes;
    }
    public function toArray()
    {
        return array(
            'error' => $this->getErrorArray());
    }

    protected function getDescriptionFromErrorCode($code)
    {
        if (defined(__NAMESPACE__.'\PublicErrorDescription::'.$code))
        {
            return constant(__NAMESPACE__.'\PublicErrorDescription::'.$code);
        }
    }

    protected function getErrorClassFromErrorCode($code)
    {
        $pos = strpos($code, '_');

        $class = substr($code, 0, $pos);

        if ($class == 'BAD')
        {
            $class = ErrorClass::BAD_REQUEST;
        }

        return $class;
    }

    public static function checkErrorCode($code)
    {
        if ($code === null)
        {
            throw new \InvalidArgumentException('null provided for errorcode');
        }
        if (defined(__NAMESPACE__.'\ErrorCode::'.$code) === false)
        {
            throw new \InvalidArgumentException('ErrorCode: ' . $code . ' is not defined');
        }
    }

    protected static function checkErrorClass($class)
    {
        if (defined(__NAMESPACE__.'\ErrorClass::'.$class) === false)
        {
            throw new \InvalidArgumentException($class . ' is not a valid class');
        }
    }
}