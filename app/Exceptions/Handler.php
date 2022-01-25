<?php

namespace App\Exceptions;

use Response;
use Exception;
use App\Trace\Trace;
use App\Trace\TraceCode;
use App\Http\AppResponse;
use UnexpectedValueException;
use Razorpay\Api\Errors\Error;
use Razorpay\Api\Errors\BadRequestError;
use App\Exceptions\EntityNotFoundException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    const SERVER_ERROR       = 'Internal Server Error';

    const METHOD_NOT_ALLOWED = 'Method not allowed';
    const ENTITY_NOT_FOUND   = 'Not Found';

    const RESPONSE_404 = [
        'success' => false,
        'errors' => [
            self::ENTITY_NOT_FOUND
        ]
    ];

    const RESPONSE_403 = 'Invalid Host Detected';

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
      //  DecryptException::class,
        HttpException::class,
        ModelNotFoundException::class,
        NotFoundHttpException::class,
    //    TokenMismatchException::class,
        \UnexpectedValueException::class,
    ];

    /**
     * A list of exception types that will be reported as INFO.
     *
     * @var  array
     */
    protected $infoReport = [
        BadRequestError::class,
        AuthorizationException::class,
    ];

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
        parent::report($e);

        $level = Trace::CRITICAL;
        $code = TraceCode::ERROR_EXCEPTION;

        if (!$this->isCritical($e))
        {
            return;
        }
        else if ($this->isInfo($e))
        {
            // Change Level to INFO
            $level = Trace::INFO;
        }

        $context = $this->getExceptionDetails($e);

        $app = \App::getFacadeRoot();

        $trace = $app['trace'];

        // TODO: Imrpve so that not everything is critical
        // Use the same checks as in render
        return $trace->addRecord($level, $code, $context);
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
        $app = \App::getFacadeRoot();

        $data = [
            'success' => false,
            'errors'  => [self::SERVER_ERROR]
        ];

        // For Debugging exceptions
        $app['trace']->info(TraceCode::ERROR_EXCEPTION, [
            'context' => $this->getExceptionDetails($e)
        ]);

        $response = null;

        if ($e instanceof ModelNotFoundException)
        {
            $e = new NotFoundHttpException($e->getMessage(), $e);
        }
        else if ($e instanceof NotFoundHttpException)
        {
            $response = Response::json(self::RESPONSE_404, 404);
        }
        else if ($e instanceof MethodNotAllowedHttpException)
        {
            $response = Response::json(['success' => false, 'errors' => [self::METHOD_NOT_ALLOWED]], 405);
        }
        else if ($e instanceof AuthorizationException)
        {
            $response = Response::json(['success' => false, 'errors' => [$e->getMessage()]], 403);
        }
        else if (($e instanceof TokenMismatchException) or
                 ($e instanceof DecryptException))
        {
            $routeName = $request->route()->getName();

            $context = $this->getExceptionDetails($e);

            // Debugging Unauthorized exception
            $app['trace']->info(TraceCode::USER_UNAUTHORIZED_EXCEPTION, ['context' => $context]);

            return AppResponse::unauthorizedResponse('Unauthorized.', $routeName);
        }
        else if ($e instanceof EntityNotFoundException)
        {
            $response = Response::json(self::RESPONSE_404, 404);
        }
        else if ($e instanceof UnexpectedValueException and preg_match('/Untrusted Host/', $e->getMessage()))
        {
            $response = response(self::RESPONSE_403, 403)
                ->header('Content-Type', 'text/plain');
        }
        else if ($e instanceof BadRequestError)
        {
            $response = Response::json([
                'http_status_code' => $e->getHttpStatusCode(),
                'success'          => false,
                'errors'           => [$e->getMessage()]]
            );
        }
        else
        {
            if ($this->isDebug())
            {
                $data['details'] = $this->getExceptionDetails($e);
            }

            $context = $this->getExceptionDetails($e);

            // Debugging Unauthorized exception
            $app['trace']->info(TraceCode::ERROR_EXCEPTION, ['context' => $context]);

            // adding this status to push status code on prometheus
            $data['http_status_code'] = $this->getStatusCodeForUnhandledException($e);

            $response = Response::json($data);
        }

        return $response;
    }

    protected function getStatusCodeForUnhandledException(Exception $e)
    {
        if ($e->getMessage() === 'Unauthorized Access')
        {
            return 401;
        }
        else if ($e instanceof Error)
        {
            return $e->getHttpStatusCode();
        }

        return 500;
    }

    protected function isDebug()
    {
        return config('app.debug');
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

    /**
     * Parses the stack so it becomes better
     */
    protected function parseStack($stack)
    {
        $baseDir = dirname(app_path());
        $baseDirLen = strlen($baseDir);
        return array_map(function ($stackMessage) use ($baseDir, $baseDirLen)
        {
            $ret = preg_match('/#\d+ (.*?)(\((\d+)\))?: (.*?)$/', $stackMessage, $matches);

            if ($ret === 1)
            {
                $file = $matches[1];
                $line = $matches[3];
                $message = $matches[4];

                if (strpos($file, $baseDir) !== false)
                {
                    $file = substr($file, $baseDirLen);
                }

                return [
                    'line'      =>  $line,
                    'message'   =>  $message,
                    'file'      =>  $file
                ];
            }
            else
            {
                return $stackMessage;
            }
        }, $stack);
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

    protected function isCritical(Exception $e)
    {
        foreach ($this->dontReport as $type)
        {
            if ($e instanceof $type)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * isInfo will classify weather a exception should be traced as info/not
     * @param  Exception $e
     * @return boolean      true/false
     */
    protected function isInfo(Exception $e)
    {
        foreach ($this->infoReport as $exceptionClass)
        {
            if ($e instanceof $exceptionClass)
            {
                return true;
            }
        }

        return false;
    }
}
