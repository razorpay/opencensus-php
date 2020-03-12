<?php

namespace RZP\Error;

use App;
use RZP\Exception;
use Illuminate\Support;
use RZP\Trace\TraceCode;
use RZP\Services\DowntimeMetric;

class Error extends Support\Fluent
{
     /** Error codes in which data needs to persist in response
     * Data will be persisted in the error response in non-debug also
     */
    const ERROR_CODES_PERSIST_DATA_IN_RESPONSE = [
        ErrorCode::BAD_REQUEST_LOCKED_USER_LOGIN,
        ErrorCode::BAD_REQUEST_USER_2FA_ALREADY_SETUP,
        ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP,
        ErrorCode::BAD_REQUEST_2FA_SETUP_INCORRECT_OTP,
        ErrorCode::BAD_REQUEST_2FA_SETUP_ACCOUNT_LOCKED,
        ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED,
        ErrorCode::BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED,
        ErrorCode::BAD_REQUEST_2FA_SETUP_USER_2FA_NOT_ENABLED,
        ErrorCode::BAD_REQUEST_RESTRICTED_USER_CANNOT_SETUP_2FA,
        ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE,
    ];

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
    const METADATA              = 'metadata';
    const REASON                = 'reason';
    const FAILURE_TYPE          = 'failure_type';
    const POINT_OF_FAILURE      = 'point_of_failure';
    const FAILURE_STAGE         = 'failure_stage';
    const NEXT_BEST_ACTION      = 'next_best_action';
    const PAYMENT_METHOD        = 'payment_method';
    const RECOVERABLE           = 'recoverable';

    const ERROR_CODE_FILE_PATH  = 'files/errorcodes/error_reason_%s.csv';

    const ERROR_CODE_CACHE_KEY  = 'error_code_map_cache_%s';

    const CACHE_EXPIRY          = 14400;

    const REDIS_EXPIRY_PARAM    = 'ex';

    protected $attributes = array();

    protected $app;

    protected $trace;

    protected $redis;

    public function __construct(
        $code,
        $desc = null,
        $field = null,
        $data = null)
    {
        $this->fill($code, $desc, $field, $data);

        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->redis = $this->app['redis']->connection();
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

    public function appendToField(string $string)
    {
        $field = $this->getAttribute(self::FIELD);

        if (empty($field) === true)
        {
            $this->setAttribute(self::FIELD, $string);
        }
        else
        {
            $this->setAttribute(self::FIELD, $string . '.' . $field);
        }
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

    public function isInvalidTerminalError()
    {
        $terminalRelatedErrors = [
            ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL,
            ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
            ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_SECRET,
        ];

        $internalCode = $this->getInternalErrorCode();

        return in_array($internalCode, $terminalRelatedErrors, true);
    }

    public static function isGatewayDowntimeErrorCode(string $errorCode)
    {
        // NoError is set when there is no error code and that is a success case.
        if ($errorCode === DowntimeMetric::NoError)
        {
            return false;
        }

        return (in_array(Error::getErrorClassFromErrorCode($errorCode), [ErrorClass::GATEWAY, ErrorClass::SERVER]) === true);
    }

    protected function setInternalErrorCode($code)
    {
        self::checkErrorCode($code);

        $this->setAttribute(self::INTERNAL_ERROR_CODE, $code);
    }

    protected function setClass($code)
    {
        $class = self::getErrorClassFromErrorCode($code);

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

    public function setMetadata($metadata)
    {
        $this->setAttribute(self::METADATA, $metadata);
    }

    public function setPaymentMethod($paymentMethod)
    {
        $this->setAttribute(self::PAYMENT_METHOD, $paymentMethod);
    }

    public function setDetailedError($code, $method)
    {
        if (isset($method) === false)
        {
            return;
        }

        $errorCodeMap = array();

        $cacheKey = sprintf(self::ERROR_CODE_CACHE_KEY,$method);

        if ($this->redis->exists($cacheKey) === 1)
        {
            if ($this->redis->get($cacheKey) !== null)
            {
                $errorCodeMap = get_object_vars(json_decode($this->redis->get($cacheKey)));
            }
            else
            {
                $this->readMappingFromFile($cacheKey, $method, $errorCodeMap);
            }

        }
        else
        {
            $this->readMappingFromFile($cacheKey, $method, $errorCodeMap);
        }

        $this->setErrorParamsIfApplicable($errorCodeMap, $code, $method);
    }

    protected function readMappingFromFile($cacheKey, $method, & $errorCodeMap)
    {
        $filePath = storage_path(sprintf(self::ERROR_CODE_FILE_PATH, $method));

        if (file_exists($filePath) === false)
        {
            return;
        }

        $handle = fopen($filePath,"r");

        if ($handle === false)
        {
            return;
        }

        try
        {
            $header = fgetcsv($handle);

            while ($row = fgetcsv($handle))
            {
                $key = array_shift($row);

                $errorCodeMap[$key] = $row;
            }

            $this->redis->set($cacheKey, json_encode($errorCodeMap), self::REDIS_EXPIRY_PARAM, self::CACHE_EXPIRY);
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception, null, TraceCode::ERROR_RESPONSE_FILE_READING_FAILED,
                ['payment_method'  => $method, 'cacheKey'  => $cacheKey]);
        }
        finally
        {
            fclose($handle);
        }
    }

    protected function setErrorParamsIfApplicable($errorCodeMap, $code, $method)
    {
        try
        {
            if (array_key_exists($code, $errorCodeMap))
            {
                //$this->setDesc($errorCodeMap[$code][0]);

                $this->setReason($errorCodeMap[$code][1]);

                $this->setFailureType($errorCodeMap[$code][2]);

                $this->setPointOfFailure($errorCodeMap[$code][3] ?: "NA");

                $this->setNextBestAction($errorCodeMap[$code][4]);

                $this->setFailureStage($errorCodeMap[$code][5] ?: "NA");

                $this->setRecoverable($errorCodeMap[$code][6]);
            }
            else
            {
                $metadata = $this->getAttribute(self::METADATA);

                if ($metadata !== null)
                {
                    $this->trace->info(TraceCode::ERROR_RESPONSE_MAPPING_NOT_FOUND,
                        [
                            'payment_method'       => $method,
                            'internal_error_code'  => $code,
                            'description'          => $this->getDescription()
                        ]
                    );
                }
            }
        }
        catch (\Exception $exception)
        {
            $this->trace->info(TraceCode::ERROR_RESPONSE_MAPPING_READ_FAILED, $errorCodeMap[$code]);
        }
    }

    protected function setReason($reason)
    {
        $this->setAttribute(self::REASON, $reason);
    }

    protected function setFailureType($failureType)
    {
        $this->setAttribute(self::FAILURE_TYPE, $failureType);
    }

    protected function setPointOfFailure($pointOfFailure)
    {
        $this->setAttribute(self::POINT_OF_FAILURE, $pointOfFailure);
    }

    protected function setFailureStage($failureStage)
    {
        $this->setAttribute(self::FAILURE_STAGE, $failureStage);
    }

    protected function setNextBestAction($nextBestAction)
    {
        $this->setAttribute(self::NEXT_BEST_ACTION, $nextBestAction);
    }

    protected function setRecoverable($recoverable)
    {
        $this->setAttribute(self::RECOVERABLE, $recoverable);
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

    public function getGatewayErrorCode()
    {
        return $this->getAttribute(self::GATEWAY_ERROR_CODE);
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
            case ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED:
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED_USER_ROLE_MISSING:
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
        $this->setDetailedError($this->getInternalErrorCode(), $this->getAttribute(self::PAYMENT_METHOD));

        $description = $isPublicRoute ? $this->getCustomerDescription() : $this->getDescription();

        $publicReason   = null;

        if($this->getAttribute(self::REASON) !== null)
        {
            $publicReason   = $this->getAttribute(self::POINT_OF_FAILURE)."-".
                $this->getAttribute(self::FAILURE_STAGE)."-".$this->getAttribute(self::REASON);
        }

        $error = array(
            self::PUBLIC_ERROR_CODE => $this->getPublicErrorCode(),
            self::DESCRIPTION       => $description,
            self::REASON            => $publicReason,
            self::METADATA          => $this->getAttribute(self::METADATA)
        );

        $this->trace->info(TraceCode::ERROR_RESPONSE_DATA,
            [
                'error_response' => $error
            ]
        );

        $error = $this->checkAndAddDataToErrorResp($error);

        $action = $this->getAttribute(self::ACTION);

        if ($action !== null)
            $error[self::ACTION] = $action;

        $field = $this->getAttribute(self::FIELD);

        if ($field !== null)
            $error[self::FIELD] = $field;

        $array = ['error' => $error];

        $extra = $this->getExtraAttributes();

        if ($extra !== null)
        {
            $array = array_merge($array, $extra);
        }

        return $array;
    }

    /** We generally don't send the data in the error response. However, in few situations
    * need to send extra data in case of error. So, adding that extra data to the error
    * response. Ref: https://razorpay.slack.com/archives/C6QPQKVLZ/p1568717119044100
    */
    public function checkAndAddDataToErrorResp(array $error)
    {
        $dataAttributes = $this->getAttribute(self::DATA);

        if ((is_null($dataAttributes) === false) and
            (in_array($this->getInternalErrorCode(), self::ERROR_CODES_PERSIST_DATA_IN_RESPONSE) === true))
        {
            $error = array_merge($error, ['_internal' => $dataAttributes]);
        }

        return $error;
    }

    public function toDebugArray()
    {
        $this->setDetailedError($this->getInternalErrorCode(), $this->getAttribute(self::PAYMENT_METHOD));

        $error = $this->checkAndAddDataToErrorResp($this->getAttributes());

        return array('error' => $error);
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

    protected function getExtraAttributes()
    {
        $attributes = $this->getAttribute(self::DATA);

        // in headless otpsubmit failure we need to send next attribute.
        if (($attributes !== null) and
            (isset($attributes['next']) === true))
        {
            return ['next' => $attributes['next']];
        }

        return null;
    }

    public static function getErrorClassFromErrorCode($code)
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
