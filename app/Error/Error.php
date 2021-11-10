<?php

namespace RZP\Error;

use App;
use ArrayObject;
use RZP\Constants\Mode;
use RZP\Exception;
use Illuminate\Support;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use \JsonMachine\JsonMachine;
use RZP\Services\DowntimeMetric;
use RZP\Error\Twirp\ErrorCodeMap;
use RZP\Models\Payout\PayoutError;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment\DetailedError;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Payout\Entity as PayoutEntity;


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
        ErrorCode::BAD_REQUEST_USER_2FA_VALIDATION_REQUIRED,
        ErrorCode::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
        ErrorCode::BAD_REQUEST_USER_2FA_LOCKED,
        ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
        ErrorCode::BAD_REQUEST_2FA_LOGIN_INCORRECT_PASSWORD,
        ErrorCode::BAD_REQUEST_USER_2FA_LOGIN_PASSWORD_REQUIRED,
        ErrorCode::BAD_REQUEST_LOGIN_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
        ErrorCode::BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED,
        ErrorCode::BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED,
        ErrorCode::BAD_REQUEST_EMAIL_LOGIN_OTP_SEND_THRESHOLD_EXHAUSTED,
        ErrorCode::BAD_REQUEST_EMAIL_NOT_VERIFIED,
        ErrorCode::BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED,
        ErrorCode::BAD_REQUEST_2FA_LOGIN_PASSWORD_SUSPENDED,
        ErrorCode::BAD_REQUEST_OTP_LOGIN_LOCKED,
        ErrorCode::BAD_REQUEST_EMAIL_VERIFICATION_OTP_SEND_THRESHOLD_EXHAUSTED,
        ErrorCode::BAD_REQUEST_VERIFICATION_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
        ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_VERIFIED,
        ErrorCode::BAD_REQUEST_EMAIL_ALREADY_VERIFIED,
        ErrorCode::BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED,
        ErrorCode::BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED
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
    const SOURCE                = 'source';
    const STEP                  = 'step';
    const NEXT_BEST_ACTION      = 'next_best_action';
    const PAYMENT_METHOD        = 'payment_method';
    const RECOVERABLE           = 'recoverable';
    const REASON_CODE           = 'reason_code';
    const ENGLISH_DESCRIPTION   = 'english_description';
    const BASE_URL              = 'base_url';
    const ERROR_FILE_PATH       = 'error_file_path';

    const BANKING_ERROR_CODE_FILE_PATH  = 'files/errorcodes/error_reason_%s.json';

    const ERROR_CODE_VERIFIABLE_FILE_PATH  = 'files/errorcodes/error_verifiable_%s.csv';

    const READ_DESC_ERROR_CODES_FILE_PATH  = 'files/errorcodes/read_desc_error_codes.json';

    protected $attributes = array();

    protected $app;

    protected $mode;

    protected $trace;

    protected $errorMapper;

    protected $product;

    protected $merchant;

    protected $errorFolder;

    public function __construct(
        $code,
        $desc = null,
        $field = null,
        $data = null)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->errorMapper = $this->app['error_mapper'];

        $this->product = $this->app['basicauth']->getProduct();

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->fill($code, $desc, $field, $data);
    }

    public static function fromTwirpResponse($twirpResponse): Error
    {
        if ((isset($twirpResponse['code']) === false) || (isset($twirpResponse['msg']) === false))
        {
            throw new Exception\InvalidArgumentException('invalid twirp response.', $twirpResponse);
        }

        $twirpErrorCode = $twirpResponse['code'];
        $description    = $twirpResponse['msg'];
        $data           = isset($twirpResponse['meta']) ? $twirpResponse['meta'] : null;

        $errorCode = ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE;

        if (isset(ErrorCodeMap::$twirpErrorCodeMap[$twirpErrorCode]) === true)
        {
            $errorCode = ErrorCodeMap::$twirpErrorCodeMap[$twirpErrorCode];
        }

        return new Error($errorCode, $description, null, $data);
    }

    public function fill($code, $desc = null, $field = null, $data = null, $internalDesc = null)
    {
        $this->setAttribute(self::DATA, $data);

        $this->setAttribute(self::FIELD, $field);

        $this->setInternalErrorCode($code);

        $this->setClass($code);

        $this->setPublicErrorDetails();

        $this->setDesc($desc);

        $this->setEnglishDescription($this->getDescription());

        $this->setAction($code);

        $this->setAttribute(self::INTERNAL_ERROR_DESC, $internalDesc);
    }

    private function setDescForLocale()
    {
        $locale = App::getLocale();

        if ($locale !== 'en')
        {
            $localeDescription = __($this->getDescription());

            $this->trace->info(TraceCode::SET_LOCALE_TRACE,
                [
                    'actual description' => $this->getDescription(),
                    'locale description' => $localeDescription,
                    'locale' => $locale
                ]
            );

            $this->setDesc($localeDescription);
        }
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
        if ($metadata === null)
        {
            $metadata = new \ArrayObject([], ArrayObject::STD_PROP_LIST|ArrayObject::ARRAY_AS_PROPS);
        }

        $this->setAttribute(self::METADATA, $metadata);
    }

    public function setPaymentMethod($paymentMethod)
    {
        $this->setAttribute(self::PAYMENT_METHOD, $paymentMethod);
    }

    /**
     * Check if method is set in the error object.
     * Method field is used to error_reason_{method}.csv files and
     * is used by Payment side of api.
     * If method is not set i.e for RaxorpayX product banking
     * we use error_reason_{product}.json file to fill details in error object.
     *
     * @param $code
     * @param $method
     */
    public function setDetailedError($code, $method)
    {
        if ($this->shouldModifyForNewBankingErrorCode() === true)
        {
            $this->setBankingErrorDetails($code);
        }
        else
        {
            $this->setErrorDetailsFromCentralRepo($code, $method);
        }

        $this->setDescForLocale();
    }

    protected function setErrorDetailsFromCentralRepo($code, $method = '')
    {
        list($errorCodeJson, $this->errorFolder) = $this->errorMapper->getErrorMapping($code,$method);

        if (isset($errorCodeJson) === false)
        {
            $this->trace->info(TraceCode::INTERNAL_ERROR_CODE_NOT_FOUND_IN_REPO,
                [
                    'payment_method'       => $method,
                    'internal_error_code'  => $code,
                ]
            );
        }

        $this->setErrorParams($errorCodeJson, $code, $method);
    }

    protected function readDescFromCodeMapping($code)
    {
        $readDescErrorsArray = $this->readMappingFromJsonFile(storage_path(self::READ_DESC_ERROR_CODES_FILE_PATH));

        return in_array($code, $readDescErrorsArray, true);
    }

    protected function setErrorParams($errorCodeJson, $code, $method = '')
    {
        $readDescFromCodeMapping = $this->readDescFromCodeMapping($code);

        $reason = $errorCodeJson['reason'] ?: 'NA';
        $source = $errorCodeJson['source'] ?: 'NA';
        $step   = $errorCodeJson['step'] ?: 'NA';

        $this->setReason($reason);

        $this->setFailureType($errorCodeJson['failure_type']);

        if ($readDescFromCodeMapping === false)
        {
            if((isset($errorCodeJson['old_error_description']) === true) and
               ($this->merchant !== null) and
               ($this->merchant->isFeatureEnabled(Features::SHOW_OLD_ERROR_DESC) === true))
            {
                $errorCodeJson['error_description'] = $errorCodeJson['old_error_description'];
            }

            $this->setDesc($errorCodeJson['error_description']);

            $this->setEnglishDescription($errorCodeJson['error_description']);
        }

        $this->setPublicErrorCode($errorCodeJson['public_error_code']);

        $this->setSource($source);

        $this->setNextBestAction($errorCodeJson['next_best_action']);

        $this->setStep($step);

        $this->setRecoverable($errorCodeJson['recoverable']);

        $this->setReasonCode($source, $step, $reason);
    }

    protected function setEnglishDescription($desc)
    {
        $this->setAttribute(self::ENGLISH_DESCRIPTION, $desc);
    }

    public function readVerifiableErrorMappingFromFile(string $method, & $errorCodeMap)
    {
        $filePath = storage_path(sprintf(self::ERROR_CODE_VERIFIABLE_FILE_PATH, $method));

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
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception, null, TraceCode::ERROR_RESPONSE_FILE_READING_FAILED,
                ['payment_method'  => $method]);
        }
        finally
        {
            fclose($handle);
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

    protected function setSource($source)
    {
        $this->setAttribute(self::SOURCE, $source);
    }

    protected function setStep($step)
    {
        $this->setAttribute(self::STEP, $step);
    }

    protected function setNextBestAction($nextBestAction)
    {
        $this->setAttribute(self::NEXT_BEST_ACTION, $nextBestAction);
    }

    protected function setRecoverable($recoverable)
    {
        $this->setAttribute(self::RECOVERABLE, $recoverable);
    }

    protected function setReasonCode($source, $step, $reason)
    {
        $sourceFieldMap = array_flip(DetailedError::$sourceFieldMap);

        $stepFieldMap = array_flip(DetailedError::$stepFieldMap);

        $reasonFieldMap = array_flip(DetailedError::$reasonFieldMap);

        $code =  ($sourceFieldMap[$source] ?? 'NA').($stepFieldMap[$step] ?? 'NA').($reasonFieldMap[$reason] ?? 'NA');

        $this->setAttribute(self::REASON_CODE, $code);
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

    public function getReason()
    {
        return $this->getAttribute(self::REASON);
    }

    public function getSource()
    {
        return $this->getAttribute(self::SOURCE);
    }

    public function getStep()
    {
        return $this->getAttribute(self::STEP);
    }

    public function getGatewayErrorCode()
    {
        return $this->getAttribute(self::GATEWAY_ERROR_CODE);
    }

    public function getGatewayErrorDesc()
    {
        return $this->getAttribute(self::GATEWAY_ERROR_DESC);
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

    public function getEnglishDescription()
    {
        return $this->getAttribute(self::ENGLISH_DESCRIPTION);
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
            case ErrorCode::BAD_REQUEST_CONFLICT_ALREADY_EXISTS:
                $httpStatusCode = 409;
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

        $metadata = $this->getAttribute(self::METADATA);

        if ((isset($metadata) === false) or
            ($this->product === Product::BANKING))
        {
            $metadata = new \ArrayObject([], ArrayObject::STD_PROP_LIST|ArrayObject::ARRAY_AS_PROPS);
        }

        $error = array(
            self::PUBLIC_ERROR_CODE => $this->getPublicErrorCode(),
            self::DESCRIPTION       => $description,
            self::SOURCE            => $this->getAttribute(self::SOURCE),
            self::STEP              => $this->getAttribute(self::STEP),
            self::REASON            => $this->getAttribute(self::REASON),
            self::METADATA          => $metadata,
        );

        if ($this->shouldModifyForNewBankingErrorCode() === true)
        {
            $error[self::STEP]   = null;

            $this->trace->info(TraceCode::NEW_BANKING_ERROR_RESPONSE_DATA,
                [
                    'new_error_response' => $error
                ]
            );
        }
        else if ($this->product === Product::BANKING)
        {
            $error[self::STEP]   = null;
            $error[self::REASON] = null;
            $error[self::SOURCE] = null;
        }

        $data = $this->getAttribute(self::DATA);

        if ($this->shouldExposeReasonCode($data) === true)
        {
            $error[self::REASON_CODE] = $this->getAttribute(self::REASON_CODE);
        }

        $this->trace->info(TraceCode::ERROR_RESPONSE_DATA,
            [
                'error_response'     => $error,
                'http_status_code'   => $this->getHttpStatusCode(),
                'internal_error_code'=> $this->getInternalErrorCode()
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

        $properties = [
            self::BASE_URL          => url("/"),
            self::ERROR_FILE_PATH   => $this->errorFolder
        ];

        $properties = array_merge($array, $properties);

        $metaDetails =[
            'metadata'  => $array,
            'read_key'  => array() ,
            'write_key' => 'trackId',
        ];

        $metaDetails['metadata']['trackId'] = $this->app['req.context']->getTrackId();

        $this->app['diag']->trackPaymentEventV2(EventCode::ERROR_RESPONSE, null,null,$metaDetails,$properties);

        return $array;
    }

    protected function shouldExposeReasonCode($data)
    {
        if ((isset($data['application']) === true) and
            ($data['application'] === 'google_pay'))
        {
            return true;
        }

        return false;
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

    public static function readMappingFromJsonFile($path, $product = null, $bigJson = false)
    {
        if (isset($product) === true)
        {
            $filePath = storage_path(sprintf($path, $product));
        }
        else
        {
            $filePath = $path;
        }

        if (file_exists($filePath) === false)
        {
            return null;
        }

        if ($bigJson === false)
        {
            $fileData = file_get_contents($filePath);

            return json_decode($fileData, true);
        }
        else
        {
            return JsonMachine::fromFile($filePath);
        }
    }

    /**
     * Add details in fields reason and source using errro_reason_{banking/payout} file
     * Here we check the main error_reason_banking file first to check if the
     * code is in global error list. Else we will check with error_reason_payout file
     * for internal_payout_error.
     *
     * @param $code
     */
    protected function setBankingErrorDetails($code)
    {
        $errorCodeMap = $this->readMappingFromJsonFile(self::BANKING_ERROR_CODE_FILE_PATH,Product::BANKING);

        $errorCodeDetails = $errorCodeMap[$code] ?? null;

        if (is_null($errorCodeDetails) === true)
        {
            $errorCodeMap = $this->readMappingFromJsonFile(self::BANKING_ERROR_CODE_FILE_PATH,PayoutEntity::PAYOUT);

            $errorCodeDetails = $errorCodeMap[PayoutError::INTERNAL_PAYOUT_ERROR][$code] ?? null;
        }

        if (is_null($errorCodeDetails) === false)
        {
            $this->setReason($errorCodeDetails[self::REASON]);

            $this->setSource($errorCodeDetails[self::SOURCE]);
        }
        else
        {
            $this->trace->error(TraceCode::BANKING_ERROR_CODE_MAPPING_NOT_FOUND,
                [
                    'internal_error_code'  => $code,
                    'description'          => $this->getDescription()
                ]
            );
        }
    }

    /**
     * Will work only with auth having merchant details.
     * With cron auth and worker this will always return false because
     * getMerchant() will always return null.
     *
     * @return bool
     */
    public function shouldModifyForNewBankingErrorCode()
    {
        if (($this->product === Product::BANKING) and
            (is_null($this->merchant) === false) and
            ($this->merchant->isFeatureEnabled(Features::NEW_BANKING_ERROR) === true))
        {
            return true;
        }

        return false;
    }
}
