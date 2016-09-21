<?php
namespace App\Http\Middleware;

use Closure;
use Config;

class TrustedProxy
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Set trusted header names
        foreach ($this->getTrustedHeaders() as $headerKey => $headerName)
        {
            $request->setTrustedHeaderName($headerKey, $headerName);
        }
        $request->setTrustedProxies($this->getTrustedProxies($request->getClientIps()));

        return $next($request);
    }
    /**
     * Return an array of trusted proxy IP addresses.
     *
     * @param array $clientIpAddresses Array of client IP addresses retrieved
     *                                 *prior* to setting trusted proxy
     *
     * @return array
     */
    protected function getTrustedProxies(array $clientIpAddresses = [])
    {
        $trustedProxies = Config::get('trustedproxy.proxies');

        return (array) $trustedProxies;
    }
    /**
     * Get trusted header names.
     *
     * @return array
     */
    protected function getTrustedHeaders()
    {
        $trustedHeaderNames = Config::get('trustedproxy.headers');
        /*
         * In case the user does not pass an array of header names we
         * will default to an empty array. This will force defaults from
         * class \Symfony\Component\HttpFoundation\Request::$trustedHeaders
         */
        $trustedHeaderNames = is_array($trustedHeaderNames) ? $trustedHeaderNames : [];
        return $trustedHeaderNames;
    }
}