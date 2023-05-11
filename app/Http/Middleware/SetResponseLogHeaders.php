<?php


namespace App\Http\Middleware;

use Route;
use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SetResponseLogHeaders
{
    protected $app;
    /**
     * Metrics constructor.
     */
    public function __construct()
    {
        $this->app = \App::getFacadeRoot();
    }

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $requestId = app('request')->requestId;

        if($response instanceof StreamedResponse) {
            
            $response->headers->set('X-Request-Id', $requestId);
            
            $response->headers->set('X-Http-Status', $this->getStatusCode($response));
            
            $response->headers->set('X-Amzn-Trace-id', $request->header('X-Amzn-Trace-Id'));
            
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            
            return $response;
        }
        
        $response->header('X-Request-Id', $requestId);

        $response->header('X-Http-Status', $this->getStatusCode($response));

        $response->header('X-Amzn-Trace-id', $request->header('X-Amzn-Trace-Id'));

        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    protected function getStatusCode($response)
    {
        $data = method_exists($response, 'getData') ? $response->getData() : null;

        if (isset($data->http_status_code) === true)
        {
            return $data->http_status_code;
        }

        return  $response->getStatusCode();
    }

}
