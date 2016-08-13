<?php

namespace App\Exception;

use App\Trace\Trace;
use Exception;
use Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

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

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        HttpException::class,
        ModelNotFoundException::class,
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

        if (!$this->isCritical($e))
        {
            return;
        }

        $context = $this->getExceptionDetails($e);

        $app = \App::getFacadeRoot();

        $trace = $app['trace'];

        // TODO: Imrpve so that not everything is critical
        // Use the same checks as in render
        return $trace->addRecord(Trace::CRITICAL, 'ERROR_EXCEPTION', $context);
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
        $data = [
            'success' => false,
            'errors'  => [self::SERVER_ERROR]
        ];

        $response = null;

        if ($e instanceof ModelNotFoundException)
        {
            $e = new NotFoundHttpException($e->getMessage(), $e);
        }
        else if ($e instanceof NotFoundHttpException)
        {
            $response = Response::json(self::RESPONSE_404, 404);
        }

        else if ($e instanceof MethodNotFoundException)
        {
            $response = Response::json(array('success' => false, 'errors' => [self::METHOD_NOT_ALLOWED]));
        }

        else
        {
            if ($this->isDebug())
            {
                $data['details'] = $this->getExceptionDetails($e);
            }

            $response = Response::json($data);
        }

        return $response;
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
            'stack'     => $this->parseStack($stack),
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
        if (($e instanceof ModelNotFoundException) or
            ($e instanceof NotFoundHttpException) or
            ($e instanceof MethodNotFoundException))
        {
            return false;
        }

        return true;
    }
}
