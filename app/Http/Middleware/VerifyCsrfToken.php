<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        // This is posted from razorpay.com
        '/contact',

        // This is posted from Slack
        '/slack',

        // Posted from API
        '/test/transactions/*',
        '/live/transactions/*',

        // Aggregation requests
        '/test/analytics/aggregations/day',
        '/test/analytics/aggregations/week',
        '/test/analytics/aggregations/month',
        '/test/analytics/aggregations/year',
        '/test/analytics/payment/aggregations',

        '/live/analytics/aggregations/day',
        '/live/analytics/aggregations/week',
        '/live/analytics/aggregations/month',
        '/live/analytics/aggregations/year',
        '/live/analytics/payment/aggregations'
    ];

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		return parent::handle($request, $next);
	}
}
