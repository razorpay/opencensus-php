<?php namespace App\Http\Middleware;

use Closure;
use Response;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{

    /**
     * Constants
     */
    const OPERATION_NAME            = 'operationName';
    const ORGANISATION_INFORMATION  = 'organisationInformation';

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
        '/live/analytics/payment/aggregations',
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
        $routeName = $request->route()->getName();

        if ($routeName === 'graph_request')
        {

            $input = $request->input();

            // If the graph query is to seek org information
            // skip CSRF token check
            if(isset($input[self::OPERATION_NAME]) and
                $input[self::OPERATION_NAME] === self::ORGANISATION_INFORMATION)
            {
                return $next($request);
            }
            else
            {
                if (
                    $this->isReading($request) or
                    $this->runningUnitTests() or
                    $this->shouldPassThrough($request) or
                    $this->tokensMatch($request)
                )
                {
                    return $next($request);
                }
                else
                {
                    return Response::json(
                        $this->getErrorResponseForGraphQlClients());
                }
            }
        }
        else
        {
            return parent::handle($request, $next);
        }

    }

    private function getErrorResponseForGraphQlClients()
    {
        $baseAppUrl = config('app.url');

        return [
            'errors'    => [
                [
                    'message'   => 'Unauthorized',
                    'extensions'    => [
                        'code'          => 'UNAUTHENTICATED',
                        'url'           => $baseAppUrl.'/user/signin',
                        'status'        => 401,
                        'statusText'    => 'Unauthorized',
                    ]
                ]
            ],

            'data'      => null,
        ];
    }
}
