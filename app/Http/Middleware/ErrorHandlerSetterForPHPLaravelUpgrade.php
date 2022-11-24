<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;

class ErrorHandlerSetterForPHPLaravelUpgrade
{
    /**
     * Application instance
     *
     * @var Application
     */
    protected $app;


    /**
     * Create a new filter instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        set_error_handler(function($errno, $errstr) {
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false;
            }

            // $errstr may need to be escaped:
            $errstr = htmlspecialchars($errstr);

            if ($errstr === "Trying to access array offset on value of type null") {
                // log to sumo here, so we can fix over time.
                return true;
            }

            return false;
        }, E_WARNING);

        return $next($request);
    }
}
