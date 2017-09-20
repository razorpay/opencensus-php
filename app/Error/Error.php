<?php

namespace RZP\Error;

use RZP\Exception;
use Illuminate\Support;

class Error extends Support\Fluent
{
    const INTERNAL_ERROR_CODE   = 'internal_error_code';
    const INTERNAL_ERROR_DESC   = 'internal_error_desc';
    const PUBLIC_ERROR_CODE     = 'code';
    const HTTP_STATUS_CODE      = 'http_status_code';
    const DESCRIPTION           = 'description';
    const FIELD                 = 'field';
    const ERROR_CLASS           = 'class';
    const DATA                  = 'data';
    const ACTION                = 'action';
    const GATEWAY_ERROR_CODE    = 'gateway_error_code';
    const GATEWAY_ERROR_DESC    = 'gateway_error_desc';

    protected $attributes = array();

    public function __construct(
        $code,
        $desc = null,
        $field = null,
        $data = null)
    {
        $this->fill($code, $desc, $field, $data);
    }

    public function fill($code, $desc = null, $field = null, $data = null, $internalDesc = null)
    {
        $this->setAttribute(self::DATA, $data);

        $this->setAttribute(self::FIELD, $field);

        $this->setInternalErrorCode($code);

        $this->setClass($code);

        $this->setPublicErrorDetails();

        $this->setDesc($desc);

        $this->setAction($code);

        $this->setAttribute(self::INTERNAL_ERROR_DESC, $internalDesc);
    }

    public function setGatewayErrorCodeAndDesc($code, $desc)
    {
        $this->attributes[self::GATEWAY_ERROR_CODE] = $code;
        $this->attributes[self::GATEWAY_ERROR_DESC] = $desc;
    }

    protected function setAttribute($key, $value)
    {
        // if (defined(__CLASS__.'::'.$key) === false)
        // {
        //     throw new InvalidArgumentException($key . ' not defined');
        // }

        $this->attributes[$key] = $value;
    }

    protected function setInternalErrorCode($code)
    {
        self::checkErrorCode($code);

        $this->setAttribute(self::INTERNAL_ERROR_CODE, $code);
    }

    protected function setClass($code)
    {
        $class = $this->getErrorClassFromErrorCode($code);

        self::checkErrorClass($class);

        $this->setAttribute(self::ERROR_CLASS, $class);
    }

    protected function setDesc(/* string */ $desc = null)
    {
        //
        // We get description in this order
        // * From function argument
        // * From description of internal error code
        // * From description of public error code
        //
        // If all 3 above are null, then throw exception
        //

        if ($desc === null)
        {
            $code = $this->getInternalErrorCode();

            $desc = $this->getDescriptionFromErrorCode($code);

            if ($desc === null)
            {
                $code = $this->getPublicErrorCode();

                $desc = $this->getDescriptionFromErrorCode($code);

                if ($desc === null)
                    throw new Exception\InvalidArgumentException(
                        'Description not provided for code: '. $code);
            }
        }

        if (is_string($desc) === false)
        {
            throw new Exception\InvalidArgumentException('desc should be string');
        }

        $this->setAttribute(self::DESCRIPTION, $desc);
    }

    protected function setAction($code = null)
    {
        $actionCode = Action::class . '::' . $code;

        if (defined($actionCode))
        {
            $this->setAttribute(self::ACTION, constant($actionCode));
        }
    }

    protected function setPublicErrorCode($code)
    {
        $this->setAttribute(self::PUBLIC_ERROR_CODE, $code);
    }

    protected function setHttpStatusCode($code)
    {
        $this->setAttribute(self::HTTP_STATUS_CODE, $code);
    }

    protected function getAttribute($attr)
    {
        if (isset($this->attributes[$attr]))
        {
            return $this->attributes[$attr];
        }

        return null;
    }

    protected function setPublicErrorDetails()
    {
        $class = $this->getAttribute(self::ERROR_CLASS);

        switch ($class)
        {
            case ErrorClass::GATEWAY:
                $this->handleGatewayErrors();
                break;

            case ErrorClass::BAD_REQUEST:
                $this->handleBadRequestErrors();
                break;

            case ErrorClass::SERVER:
                $this->setPublicErrorCode(PublicErrorCode::SERVER_ERROR);
                $this->setHttpStatusCode(500);
                break;

            default:
                throw new Exception\InvalidArgumentException('Not a valid class');
        }
    }

    public function getPublicError()
    {
        return $this->publicError;
    }

    public function getInternalErrorCode()
    {
        return $this->getAttribute(self::INTERNAL_ERROR_CODE);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getClass()
    {
        return $this->getAttribute(self::ERROR_CLASS);
    }

    public function isGatewayError()
    {
        return ($this->getClass() === ErrorClass::GATEWAY);
    }

    public function getPublicErrorCode()
    {
        return $this->getAttribute(self::PUBLIC_ERROR_CODE);
    }

    public function getHttpStatusCode()
    {
        return $this->getAttribute(self::HTTP_STATUS_CODE);
    }

    public function getCustomerDescription()
    {
        $code = $this->getInternalErrorCode();

        return $this->getCustomerDescriptionFromErrorCode($code);
    }

    protected function handleBadRequestErrors()
    {
        $code = $this->getInternalErrorCode();

        $httpStatusCode = 400;

        switch($code)
        {
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED:
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY:
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET:
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED:
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED:
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID:
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID:
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED_OAUTH_SCOPE_INVALID:
                $httpStatusCode = 401;
                break;
            case ErrorCode::BAD_REQUEST_ONLY_HTTPS_ALLOWED:
            case ErrorCode::BAD_REQUEST_FORBIDDEN:
                $httpStatusCode = 403;
                break;
            case ErrorCode::BAD_REQUEST_RATE_LIMIT_EXCEEDED:
                $httpStatusCode = 429;
                break;
        }

        $this->setPublicErrorCode(PublicErrorCode::BAD_REQUEST_ERROR);
        $this->setHttpStatusCode($httpStatusCode);
    }

    protected function handleGatewayErrors()
    {
        $code = $this->getInternalErrorCode();

        $httpStatusCode = 502;

        switch ($code)
        {
            case ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT:
                $httpStatusCode = 504;
                break;
        }

        $this->setPublicErrorCode(PublicErrorCode::GATEWAY_ERROR);
        $this->setHttpStatusCode($httpStatusCode);
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function toPublicArray($isPublicRoute = false)
    {
        $description = $isPublicRoute ? $this->getCustomerDescription() : $this->getDescription();

        $array = array(
            self::PUBLIC_ERROR_CODE => $this->getPublicErrorCode(),
            self::DESCRIPTION       => $description,
        );

        $action = $this->getAttribute(self::ACTION);

        if ($action !== null)
            $array[self::ACTION] = $action;

        $field = $this->getAttribute(self::FIELD);

        if ($field !== null)
            $array[self::FIELD] = $field;

        return array('error' => $array);
    }

    public function toDebugArray()
    {
        return array('error' => $this->getAttributes());
    }

    protected function getDescriptionFromErrorCode($code)
    {
        $code = strtoupper($code);

        if (defined(PublicErrorDescription::class . '::' . $code))
        {
            return constant(PublicErrorDescription::class.'::'.$code);
        }
    }

    protected function getCustomerDescriptionFromErrorCode($code)
    {
        $code = strtoupper($code);
        $desc = null;

        if ($this->isValidationError($code))
        {
            return $this->getDescription();
        }

        if (defined(CustomerErrorDescription::class . '::' . $code))
        {
            return constant(CustomerErrorDescription::class . '::' . $code);
        }

        return $this->getDescription();
    }

    protected function getErrorClassFromErrorCode($code)
    {
        $pos = strpos($code, '_');

        $class = substr($code, 0, $pos);

        if ($class === 'BAD')
        {
            $class = ErrorClass::BAD_REQUEST;
        }

        return $class;
    }

    public static function hasAction($code)
    {
        if (defined(Action::class . '::' . $code))
        {
            return true;
        }

        return false;
    }

    public static function checkErrorCode($code)
    {
        if ($code === null)
        {
            throw new Exception\InvalidArgumentException('null provided for errorcode');
        }

        if (defined(ErrorCode::class.'::'.$code) === false)
        {
            throw new Exception\InvalidArgumentException('ErrorCode: ' . $code . ' is not defined');
        }
    }

    protected static function checkErrorClass($class)
    {
        if (defined(ErrorClass::class.'::'.$class) === false)
        {
            throw new Exception\InvalidArgumentException($class . ' is not a valid class');
        }
    }

    protected function isValidationError($code)
    {
        $validationErrorCodes = [
            ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED
        ];

        return in_array($code, $validationErrorCodes, true);
    }
}
