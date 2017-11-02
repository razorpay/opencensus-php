<?php

namespace RZP\Exception;

use App;
use Response;
use Exception;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Contracts\Container\Container;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use RZP\Exception\EarlyWorkflowResponse;

class Handler extends ExceptionHandler
{
    protected $throwExceptionInTesting = true;

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        HttpException::class,
        ValidationException::class,
    ];

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->throwExceptionInTesting = $this->app['config']->get('app.throw_exception_in_testing');

        $this->route = $this->app['api.route'];
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        // Nothing to do here.
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
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

            case $e instanceof ThrottleException:
                $response = $this->throttleExceptionHandler($e);
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

            case $e instanceof \Razorpay\OAuth\Exception\BaseException:
                $response = $this->oauthRecoverableErrorResponse($this->isDebug(), $e);
                break;
        }

        if ($response !== null)
        {
            return $response;
        }

        return $this->genericExceptionHandler($e);
    }

    public function oauthRecoverableErrorResponse(bool $debug, \Exception $exception = null)
    {
        $this->traceException($exception, Trace::WARNING, TraceCode::RECOVERABLE_EXCEPTION);

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

    protected function genericExceptionHandler(Exception $exception)
    {
        if ($this->isToStringException($exception))
        {
            return $this->toStringExceptionResponse($this->isDebug(), $exception);
        }

        $this->traceException($exception);

        $this->ifTestingThenRethrowException($exception);

        return $this->generateServerErrorResponse($this->isDebug(), $exception);
    }

    protected function throttleExceptionHandler(ThrottleException $exception)
    {
        //
        // TODO: Trace different level for different auths here
        // Take auth as one of the params for ThrottleException
        //
        // Currently using level ALERT, as we're only throttling
        // admin auth, which should never be rate-limited at all
        //
        $this->traceException(
            $exception,
            Trace::ALERT,
            TraceCode::REQUEST_THROTTLED);

        $response = ApiResponse::rateLimitExceeded();

        return $response;
    }

    protected function baseExceptionHandler(BaseException $exception)
    {
        // ServerError is fatal error and shoudn't be encountered
        // Let the higher-ups handle it. This function handles
        // known/expected exceptions
        if ($exception instanceof ServerErrorException)
        {
            return;
        }

        $this->trace->info(
            TraceCode::RECOVERABLE_EXCEPTION,
            $this->getExceptionDetails($exception));

        return $this->recoverableErrorResponse($this->isDebug(), $exception);
    }

    protected function gatewayExceptionHandler(GatewayErrorException $exception)
    {
        $level = Trace::INFO;
        $code = TraceCode::RECOVERABLE_EXCEPTION;

        if ($exception->isCritical() === true)
        {
            $level = Trace::CRITICAL;
            $code = TraceCode::ERROR_EXCEPTION;
        }

        $this->traceException($exception, $level, $code);

        return $this->recoverableErrorResponse($this->isDebug(), $exception);
    }

    protected function gatewayFileExceptionHandler(GatewayFileException $exception)
    {
        $level = $exception->getTraceLevel();
        $code = $exception->getTraceCode();

        $this->traceException($exception, $level, $code, $exception->getData());

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

        if ($exception instanceof \Razorpay\OAuth\Exception\BaseException === true)
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

        $this->trace->warn(
            TraceCode::MISC_TOSTRING_ERROR,
            $this->getExceptionDetails($exception));

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
        $this->ifTestingThenRethrowException($exception);

        $error = $exception->getError();

        return ApiResponse::generateErrorResponse($error, $debug);
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

    protected function isDebug()
    {
        return config('app.debug');
    }
}
