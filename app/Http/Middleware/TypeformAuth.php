<?php

namespace RZP\Http\Middleware;

use Closure;
use Request;
use ApiResponse;
use RZP\Http\Route;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request as HttpRequest;

const SIGNATURE_PREFIX = "sha256=";

class TypeformAuth
{
    protected $router;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->ba = $app['basicauth'];

        $this->router = $this->app['api.route'];
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle(HttpRequest $request, Closure $next)
    {
        $ret = null;

        $route = $this->router->getCurrentRouteName();

        if (in_array($route, Route::TYPEFORM_SECURITY, true) === true)
        {
            $ret = $this->authenticateTypeformWebhook($request);
        }

        if ($ret !== null)
        {
            return $ret;
        }

        return $next($request);
    }

    protected function authenticateTypeformWebhook(HttpRequest $request)
    {
        $actualSignature = $request->headers->get(RequestHeader::TYPEFORM_SIGNATURE);

        if (empty($actualSignature) === true or
            (hash_equals($actualSignature, $this->fetchExpectedSignature($request)) === false))
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        return null;
    }

    private function fetchExpectedSignature(HttpRequest $request)
    {
        $expectedSecret = $this->app['config']->get('applications.typeform.typeform_webhook_secret');

        $encryptionAlgo = $this->app['config']->get('applications.typeform.typeform_encryption_algo');

        $hashedSecret = hash_hmac($encryptionAlgo, $request->getContent(), $expectedSecret);

        $expectedSignature = SIGNATURE_PREFIX . base64_encode($hashedSecret);

        return $expectedSignature;
    }
}
