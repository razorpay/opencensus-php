<?php

namespace RZP\Exception;

use App;
use Response;
use Exception;
use ApiResponse;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Illuminate\Support\Arr;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\RazorxTreatment;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $throwExceptionInTesting = true;

    /**
     * A list of the exception types that should not be logged
     *
     * @var array
     */
    protected $dontReport = [
        ProcessTimedOutException::class,
        MethodNotAllowedHttpException::class,
        EarlyWorkflowResponse::class,
        TwirpException::class,
    ];

    /**
     * A list of the exception types that should not be reported to Sentry
     *
     * @var array
     */
    protected $dontReportToSentry = [
        RecoverableException::class,
        MethodNotAllowedHttpException::class,
        ThrottleException::class,
        EarlyWorkflowResponse::class,
        \Razorpay\OAuth\Exception\BadRequestException::class,
        TwirpException::class,
    ];

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->throwExceptionInTesting = $this->app['config']->get('app.throw_exception_in_testing');

        $this->route = $this->app['api.route'];

        $this->ba = $this->app['basicauth'];

        if (($this->app['config']['app.sentry_mock'] === false) and
            ($this->app->bound('sentry') === true))
        {
            $this->sentry = $this->app['sentry'];
        }
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sumologic, Sentry, Bugsnag, etc.
     *
     * @param Exception $e
     * @return void
     */
    public function report(Exception |\Throwable $e)
    {
        if ($this->shouldntReportToSentry($e) === false)
        {
            $this->logExceptionInSentry($e);
        }

        $level = null;
        $code  = null;
        $extraData = [];

        switch (true)
        {
            // Order should not be changed,
            // GatewayErrorException, GatewayFileException extends
            // RecoverableException
            case $e instanceof GatewayErrorException:
                $level = Trace::INFO;
                $code = TraceCode::RECOVERABLE_EXCEPTION;

                if ($e->isCritical() === true)
                {
                    $level = Trace::CRITICAL;
                    $code = TraceCode::ERROR_EXCEPTION;
                }
                break;

            case $e instanceof GatewayFileException:
                $level = $e->getTraceLevel();
                $code = $e->getTraceCode();
                $extraData = $e->getData();
                break;

            case $e instanceof ServerErrorException:
                if ($this->isToStringException($e) === true)
                {
                    $level = Trace::WARNING;
                    $code = TraceCode::MISC_TOSTRING_ERROR;
                    $extraData = $this->getExceptionDetails($e);
                }
                // Else condition handled in default case;
                break;

            case $e instanceof BaseException:
            case $e instanceof RecoverableException:
                $level = Trace::INFO;
                $code = TraceCode::RECOVERABLE_EXCEPTION;
                $extraData = $this->getExceptionDetails($e);
                break;

            case $e instanceof BlockException:
                $level = Trace::ALERT;
                $code = TraceCode::THROTTLE_REQUEST_BLOCKED;
                break;

            case $e instanceof ThrottleException:
                $level = Trace::ALERT;
                $code = TraceCode::THROTTLE_REQUEST_THROTTLED;
                break;

            case $e instanceof \Razorpay\OAuth\Exception\BadRequestException:
                $level = Trace::WARNING;
                $code = TraceCode::RECOVERABLE_EXCEPTION;
                break;
        }

        $this->traceException($e, $level, $code, $extraData);
    }

    protected function shouldntReportToSentry(Throwable $e)
    {
        $dontReport = array_merge($this->dontReportToSentry, $this->internalDontReport);

        return (is_null(Arr::first($dontReport, fn ($type) => $e instanceof $type)) === false);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param Throwable $e
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        $response = null;

        switch (true)
        {
            // Order should not be changed,
            // GatewayErrorException extends RecoverableException
            case $e instanceof GatewayErrorException:
                $response = $this->gatewayExceptionHandler($e);
                break;

            case $e instanceof GatewayFileException:
                $response = $this->gatewayFileExceptionHandler($e);
                break;

            case $e instanceof MethodInstrumentsTerminalsSyncException:
                $response = ApiResponse::json($e->getData());
                $response->setStatusCode($e->getStatusCode());
                break;

            case $e instanceof BaseException:
            case $e instanceof RecoverableException:
                $response = $this->baseExceptionHandler($e);
                break;

            case $e instanceof ProcessTimedOutException:
                $response = ApiResponse::json(['error' => 'Process timed out']);
                break;

            case $e instanceof MethodNotAllowedHttpException:
                $response = ApiResponse::httpMethodNotAllowed();
                break;

            case $e instanceof BlockException:
                $response = ApiResponse::requestBlocked();
                break;

            case $e instanceof ThrottleException:
                $response = ApiResponse::rateLimitExceeded();
                break;

            case $e instanceof EarlyWorkflowResponse:
                $workflowActionData = json_decode($e->getMessage(), true);

                // Although we're doing a re-assignment
                // the returned array will be exactly similar
                // to the $e->getMessage()
                $workflowActionData = $this->app['workflow']
                                           ->saveActionIfTransactionFailed(
                                                $workflowActionData);

                $response = ApiResponse::json($workflowActionData);

                break;

            case $e instanceof \Razorpay\OAuth\Exception\BadRequestException:
                $response = $this->oauthRecoverableErrorResponse($this->isDebug(), $e);
                break;
        }

        if ($response !== null)
        {
            return $response;
        }

        return $this->genericExceptionHandler($e);
    }

    public function oauthRecoverableErrorResponse(bool $debug, Exception $exception = null)
    {
        $this->ifTestingThenRethrowException($exception);

        $httpStatusCode = $exception->getHttpStatusCode();

        $data = $debug ? $exception->toDebugArray() : $exception->toPublicArray();

        return response()->json($data, $httpStatusCode);
    }

    public function traceException(
        $exception,
        $level = null,
        $code = null,
        array $extraData = [])
    {
        $traceData = $this->getExceptionDetails($exception, 0, $extraData);

        // Gets default level and code based on exception

        $defaultLevel = $this->route->isCriticalRoute() ? Trace::CRITICAL : Trace::ERROR;
        $defaultCode  = TraceCode::ERROR_EXCEPTION;

        switch (true)
        {
            case $exception instanceof GatewayFileException:
                $defaultLevel = $exception->getTraceLevel();
                $defaultCode = $exception->getTraceCode();
                break;

            case $exception instanceof RecoverableException:
                $defaultLevel = Trace::INFO;
                $defaultCode = TraceCode::RECOVERABLE_EXCEPTION;
                break;
        }

        // Use default level and code if not sent as part of arguments

        $level = $level ?: $defaultLevel;
        $code  = $code ?: $defaultCode;

        $this->trace->addRecord($level, $code, $traceData);
    }

    protected function genericExceptionHandler(Exception|\Throwable $exception)
    {
        if ($this->isToStringException($exception))
        {
            return $this->toStringExceptionResponse($this->isDebug(), $exception);
        }

        $this->ifTestingThenRethrowException($exception);

        return $this->generateServerErrorResponse($this->isDebug(), $exception);
    }

    protected function logExceptionInSentry($exception)
    {
        // Sentry is mocked
        if (isset($this->sentry) === false)
        {
            return;
        }

        try
        {
            $this->sentry->captureException($exception);
        }
        catch (\Throwable $e)
        {
            $this->traceException($e);
        }
    }

    public function baseExceptionHandler(BaseException $exception)
    {
        // ServerError is fatal error and shoudn't be encountered
        // Let the higher-ups handle it. This function handles
        // known/expected exceptions
        if ($exception instanceof ServerErrorException)
        {
            return;
        }

        return $this->recoverableErrorResponse($this->isDebug(), $exception);
    }

    protected function gatewayExceptionHandler(GatewayErrorException $exception)
    {
        $data = $exception->getData();

        if (Payment\Gateway::isNachNbResponseFlow($data) === true)
        {
            return $this->recoverableNachNbErrorResponse($this->isDebug(), $exception);
        }

        if(((isset($data['method']) === true) and ($data['method'] === 'emandate')) and
            ((isset($data['recurring_type']) === true) and ($data['recurring_type'] === 'initial')) and
            ((isset($data['gateway']) === true) and ($data['gateway'] === 'enach_npci_netbanking')) and
            ((isset($data['merchant_id']) === true) and ($this->isNpciFeedbackPopupAllowed($data['merchant_id']) ===true)))
        {
            $this->trace->info(
                TraceCode::EMANDATE_NPCI_PAYMENT_FAILURE_CALLBACK,
                [
                    'payment_id'            => $data['payment_id'],
                ]);
            return $this->recoverableEmandateErrorResponse($this->isDebug(), $exception);
        }

        return $this->recoverableErrorResponse($this->isDebug(), $exception);
    }

    protected function gatewayFileExceptionHandler(GatewayFileException $exception)
    {
        return $this->recoverableErrorResponse($this->isDebug(), $exception);
    }

    protected function getExceptionDetails(
        $exception,
        $level = 0,
        array $extraData = [])
    {
        $previousException = $exception->getPrevious();

        $previous = null;

        if ($previousException !== null)
        {
            $previous = $this->getExceptionDetails($previousException, $level + 1);
        }

        $data = $this->getDataArrayPropertyFromException($exception, $extraData);

        if ($exception instanceof \Razorpay\OAuth\Exception\BadRequestException === true)
        {
            unset($data['token']);
        }

        /**
         * @note getTraceAsString logs function arguments, contrary to the older comment here
         * TODO: Write a wrapper over getTrace that drops function arguments instead
         *
         * Ideally: we should use reflection to drop sensitive arguments only.
         */
        $stack = explode("\n", $exception->getTraceAsString());

        if ($exception->getCode() === ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED)
        {
            $stack = $this->hideSensitiveInformationFromStack($stack);
        }

        if ($level === 0)
        {
            // Only trace 30 stack function calls if it's a zero level exception
            $stack = array_slice($stack, 0, 30);
        }

        if ($level > 0)
        {
            // Only trace 5 stack function calls if it's a 'previous' exception.
            $stack = array_slice($stack, 0, 5);
        }

        if ($exception instanceof \PDOException)
        {
            $stack = null;

            $previous = null;
        }

        $traceData = array(
            'class'     => get_class($exception),
            'code'      => $exception->getCode(),
            'message'   => $exception->getMessage(),
            'data'      => $data,
            'stack'     => $stack,
            'previous'  => $previous);

        return $traceData;
    }

    protected function isToStringException($exception)
    {
        $message = $exception->getMessage();

        $str = 'Swift_Message::__toString()';

        if (strpos($message, $str) === false)
        {
            return false;
        }

        return true;
    }

    protected function generateServerErrorResponse($debug, $exception)
    {
        list($publicError, $httpStatusCode) =
                ApiResponse::getErrorResponseFields(ErrorCode::SERVER_ERROR);

        if (($debug) and
            ($exception !== null))
        {
            $publicError['exception'] = $this->getExceptionData($exception);

            $publicError['data'] = $this->getDataArrayPropertyFromException($exception);

            if($exception instanceof \PDOException)
            {
                unset($publicError['exception']['trace'], $publicError['exception']['previous']);
            }
        }

        return ApiResponse::generateResponse($publicError, $httpStatusCode);
    }

    protected function toStringExceptionResponse($debug, $exception)
    {
        list($publicError, $httpStatusCode) =
            ApiResponse::getErrorResponseFields(ErrorCode::SERVER_ERROR_TO_STRING_EXCEPTION);

        if ($debug)
        {
            $publicError['error']['internal_error_code'] =
                ErrorCode::SERVER_ERROR_TO_STRING_EXCEPTION;
        }

        return ApiResponse::generateResponse($publicError, $httpStatusCode);
    }

    protected function recoverableErrorResponse($debug, $exception = null)
    {
        $this->setErrorMetadataIfApplicable($exception);

        $error = $exception->getError();

        $data = $exception->getData();

        $this->ifTestingThenRethrowException($exception);

        return ApiResponse::generateErrorResponse($error, $debug);
    }

    protected function recoverableNachNbErrorResponse($debug, $exception = null)
    {
        $this->setErrorMetadataIfApplicable($exception);

        $error = $exception->getError();

        $data = $exception->getData();

        $this->ifTestingThenRethrowException($exception);

        return ApiResponse::generateNachNbErrorResponse($error, $data, $debug);
    }

    protected function recoverableEmandateErrorResponse($debug, $exception = null)
    {
        $this->setErrorMetadataIfApplicable($exception);

        $error = $exception->getError();

        $data = $exception->getData();

        $this->ifTestingThenRethrowException($exception);

        return ApiResponse::generateEmandateNpciErrorResponse($error, $data, $debug);
    }

    protected function setErrorMetadataIfApplicable($exception)
    {
        $error = $exception->getError();

        $data = $exception->getData();

        $metadata = null;

        if(isset($data['error']) === true && isset($data['error']['metadata']) === true)
        {
            $metadata = $data['error']['metadata'];
        }
        if (isset($data['payment_id']) === true)
        {
            $metadata['payment_id'] = $data['payment_id'];
        }
        if (isset($data['order_id']) === true)
        {
            $metadata['order_id'] = $data['order_id'];
        }
        if (isset($data['method']) === true)
        {
            $error->setPaymentMethod($data['method']);
        }

        $error->setMetadata($metadata);
    }

    protected function getExceptionData($exception)
    {
        $previous = $exception->getPrevious();
        $previousData = null;

        if ($previous !== null)
        {
            $previousData = self::getExceptionData($previous);
        }

        $data = array(
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'previous' => $previousData,
        );

        $data['trace'] = str_replace('/', "\\", $data['trace']);
        $data['file'] = str_replace('/', "\\", $data['file']);

        return $data;
    }

    protected function getDataArrayPropertyFromException(
        $e,
        array $extraData = [])
    {
        $data = null;

        if (method_exists($e, 'getData'))
        {
            $data = $e->getData();

            if ((is_object($data) === true) and
                (method_exists($data, 'toArray') === true))
            {
                $data = $data->toArray();
            }

            if ((is_resource($data) === true) or
                (is_array($data) === false))
            {
                $data = null;
            }
        }

        if ($data !== null)
        {
            $data = array_merge($data, $extraData);
        }
        else
        {
            $data = $extraData;
        }

        return $data;
    }

    protected function ifTestingThenRethrowException($e)
    {
        if (($this->isTesting()) and ($this->throwExceptionInTesting))
        {
            throw $e;
        }
    }

    public function isTesting()
    {
        return ($this->app->runningUnitTests());
    }

    //
    // Returns true if env debug is set to true, or if the client
    // app is a debug app. Such apps may required extra information
    // like internal_error_code to correctly handle exceptions
    //
    protected function isDebug()
    {
        return ((config('app.debug') === true) or
                ($this->ba->isDebugApp() === true));
    }

    protected function hideSensitiveInformationFromStack(array $stackArr)
    {
        $hideParams = [
            [
                'regex'     => '/(.*getUserByEmailAndVerifyPassword\(\')(.*)(\'\)$)/i',
                'replace'   => '******\', \'******',
            ]
        ];

        $obscuredStack = [];

        foreach ($stackArr as $stackLine)
        {
            foreach ($hideParams as $key => $patternArr)
            {
                try
                {
                    $regex = $patternArr['regex'];
                    $replace = $patternArr['replace'];

                    $found = preg_match($regex, $stackLine, $matches);

                    if ($found === 1)
                    {
                        $obscuredStack[] = $matches[1] . $replace . $matches[3];
                    }
                    else
                    {
                        $obscuredStack[] = $stackLine;
                    }
                }
                catch (\Throwable $e)
                {
                    return $stackArr;
                }
            }
        }

        return $obscuredStack;
    }

    /**
     * Function to add payment retry methods to error payload in case of failed payments
     *
     * @param string $instrument
     * @param string $method
     * @param BaseException $e
     */
    public static function constructErrorWithRetryMetadata(string $instrument, string $method, BaseException &$e)
    {
        $data = $e->getData();
        $newInstrument = [
            'instrument'  => $instrument,
            'method'      => $method
        ];
        $retryAction = [
            'action'      => 'suggest_retry',
            'instruments' => array($newInstrument)
        ];
        $nextBlock = [
            'next' => array($retryAction)
        ];

        if(isset($data['error']['metadata']))
        {
            if(isset($data['error']['metadata']['next']))
            {
                $_set = false;
                $data['error']['metadata']['next'] = array_map(function($next) use ($newInstrument, &$_set){
                   if($next['action'] === 'suggest_retry'){
                       array_push($next['instruments'], $newInstrument);
                       $_set = true;
                   }
                   return $next;
                }, $data['error']['metadata']['next']);
                if($_set === false)
                {
                    array_push($data['error']['metadata']['next'], $retryAction);
                }
            }
            else
            {
                $data['error']['metadata']['next'] = array($retryAction);
            }
        }
        else
        {
            $data['error']['metadata'] = $nextBlock;
        }
        $e->setData($data);
    }

    protected function isNpciFeedbackPopupAllowed($merchantId)
    {
        try
        {
            $variantFlag = $this->app['razorx']->getTreatment($merchantId,
                RazorxTreatment::ALLOW_NPCI_FEEDBACK_POPUP_EMANDATE_FAILURE,
                $this->app['rzp.mode']);

            $this->trace->info(
                TraceCode::EMANDATE_ALLOW_NPCI_FEEDBACK_RAZORX_SUCCESS,
                [
                    'variant' => $variantFlag
                ]);

            return (strtolower($variantFlag) === 'on');
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::EMANDATE_ALLOW_NPCI_FEEDBACK_RAZORX_FAILURE,
                [
                    'error' => $e,
                ]);

            return false;
        }
    }
}
