<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Response;

class GraphRequestAuthCheck
{
    const OPERATION_NAME = 'operationName';

    const WHITELISTED_OPERATIONS = [
        'userAuthentication',
        'organisationInformation',
        'registerEmail',
        'loginEmail',
        'loginTwoFactor',
        'loginOAuth',
        'resetPasswordEmail',
        'resendEmailOtp',
        'registerEmailVerify',
        'couponValidate',
    ];

    public function handle($request, Closure $next)
    {
        $user = Auth::guard('user')->user();

        $operationName = $request->input(self::OPERATION_NAME);

        if (empty($user) === true)
        {
            if ($this->operationPartOfWhitelist($operationName) === false)
            {
                // If user session does not exist
                // Then only operations part of whitelist will be allowed
                // If operation is not part of whitelist then 401 will be returned
                return Response::json(
                    $this->getErrorResponseForGraphQlClients());
            }
        }

        return $next($request);

    }

    private function operationPartOfWhitelist($operation)
    {
        return in_array(
            $operation,
            self::WHITELISTED_OPERATIONS,
            true);
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
