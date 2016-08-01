<?php

namespace RZP\Exception;

use App;
use Response;
use Exception;
use RZP\Http\ApiResponse;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use Psr\Log\LoggerInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        HttpException::class,
        ValidationException::class,
    ];

    public function __construct(LoggerInterface $log)
    {
        parent::__construct($log);

        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
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
        }

        if ($response !== null)
        {
            return $response;
        }

        return $this->genericExceptionHandler($e);
    }

    public function traceException(Exception $exception, $level = null, $code = null)
    {
        $traceData = $this->getExceptionDetails($exception);

        if (($level === null) and
            ($code === null))
        {
            if ($exception instanceof RecoverableException)
            {
                $level = Trace::INFO;
                $code = TraceCode::RECOVERABLE_EXCEPTION;
            }
            else
            {
                $level = Trace::ERROR;
                $code = TraceCode::ERROR_EXCEPTION;
            }
        }

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

    protected function getExceptionDetails(Exception $exception, $level = 0)
    {
        $previousException = $exception->getPrevious();

        $previous = null;

        if ($previousException !== null)
        {
            $previous = $this->getExceptionDetails($previousException, $level + 1);
        }

        $data = $this->getDataArrayPropertyFromException($exception);

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

        //
        // @note: Always call function 'getTraceAsSring' to get stack trace
        //        since it doesn't include function arguments.
        //        Function arguments can contain sensitive data so should
        //        never be logged. Never call 'getTrace' directly.
        //
        // @note: Don't remove this comment.
        //
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

    protected function getDataArrayPropertyFromException($e)
    {
        $data = null;

        if (method_exists($e, 'getData'))
        {
            $data = $e->getData();

            if (method_exists($data, 'toArray'))
            {
                $data = $data->toArray();
            }

            if ((is_resource($data) === true) or
                (is_array($data) === false))
            {
                $data = null;
            }
        }

        return $data;
    }

    protected function ifTestingThenRethrowException($e)
    {
        if ($this->isTesting())
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
