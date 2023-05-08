<?php

namespace RZP\Http\Middleware;

use App;
use Closure;
use RZP\Constants\HyperTrace;
use RZP\Trace\Tracer;
use Throwable;
use Carbon\Carbon;

use RZP\Http\Route;
use RZP\Constants\Mode;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Base\RepositoryManager;
use RZP\Models\Admin\ConfigKey;
use Illuminate\Foundation\Application;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\RequestLog\Entity as LogEntity;

class RequestLogHandler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    protected $app;
    protected $repo;
    protected $basicauth;
    protected $route;
    protected $trace;

    const DEFAULT_REQUEST_LOG_STATE = 'on';

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->repo = $app['repo'];

        $this->basicauth = $app['basicauth'];

        $this->route = $app['api.route'];

        $this->trace = $app['trace'];
    }

    public function handle($request, Closure $next)
    {
        // Pass the request further (to next Middleware) and get the response
        $response = $next($request);

        // For Razorx handling and saving in the entity
        $merchantId = $this->basicauth->getMerchantId() ?? null;

        $mode = $this->app['rzp.mode'];

        // If the request isn't on live mode, then there's no need of logging
        // Also, if the redis key is not 'on', then we don't log as well.
        if ($mode !== Mode::LIVE)
        {
            $this->trace->info(
                TraceCode::REQUEST_LOG_SKIPPED,
                [
                    'mode' => $mode,
                ]
            );
            return $response;
        }

        try
        {
            $this->trace->info(
                TraceCode::REQUEST_LOG_HANDLER_INITIATED,
                [
                    'request_url' => $request->getRequestUri(),
                    'route'       => $this->route->getCurrentRouteName(),
                ]
            );

            // For Finding proxy IP
            $dashboardHeaders = $this->basicauth->getDashboardHeaders();

            /*
             * If the route has been called by the client from the dashboard
                and If dashBoardHeaders['ip'] is set and is not empty
             * Then extract $clientIp from the dashboard headers and $proxyIp
                from Laravel's Request::ip() method
             * else Laravel's Request::ip() method returns true IP,
                and can be directly set to $clientIP, and $proxyIp is null.
             */
            if (empty($dashboardHeaders['ip']) === false)
            {
                $clientIp = $dashboardHeaders['ip'];
                $proxyIp  = $request->ip();
            }
            else
            {
                $clientIp = $request->ip();
                $proxyIp  = null;
            }

            // Use json_decode to convert Response Content JSON string to PHP stdClass object
            $responseContent = json_decode($response->getContent());

            $logEntry = new LogEntity();

            $logEntry->setMerchantId($merchantId);

            $logEntry->setClientIp($clientIp);

            $logEntry->setProxyIp($proxyIp);

            $logEntry->setRequestMethod($request->method() ?? null);

            $logEntry->setRouteName($this->route->getCurrentRouteName());

            $logEntry->setEntityType($responseContent->entity ?? null);

            $logEntry->setEntityId($responseContent->id ?? null);

            $this->repo->saveOrFail($logEntry);

            $this->trace->info(
                TraceCode::REQUEST_LOG_HANDLER_ENTITY_SAVED,
                [
                    'log_ID'      => $logEntry->getId(),
                    'request_url' => $request->getRequestUri(),
                    'route_name'  => $this->route->getCurrentRouteName(),
                ]
            );

            return $response;
        }
        catch(Throwable $t)
        {
            $this->trace->traceException(
                $t,
                null,
                TraceCode::REQUEST_LOG_HANDLER_UNEXPECTED_EXCEPTION,
                [
                    'request_url' => $request->getRequestUri(),
                    'route_name'  => $this->route->getCurrentRouteName(),
                ]
            );

            Tracer::startSpanWithAttributes(HyperTrace::REQUEST_LOG_HANDLER_UNEXPECTED_EXCEPTION,
                                            [
                                                'request_url' => $request->getRequestUri(),
                                                'route_name'  => $this->route->getCurrentRouteName(),
                                            ]);

            return $response;
        }
    }
}
