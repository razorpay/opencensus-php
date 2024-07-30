<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Response;
use Request;
use Trace;

use App\Trace\TraceCode;
use GraphQL\Language\Parser;

class GraphRequestAuthCheckV2
{
    const OPERATION_NAME = 'operationName';

    const WHITELISTED_QUERY_SELECTORS = [
        'userAuthentication',
        'organisationInformation',
        'organisationInformationByDomain',
        'registerEmail',
        'loginEmail',
        'loginTwoFactor',
        'loginOAuth',
        'resetPasswordEmail',
        'resendEmailOtp',
        'registerEmailVerify',
        'couponValidate',
        'registerOAuth',
        'registerMobileVerify',
        'registerMerchant',
        'loginOtp',
        'loginOtpVerify',
        'loginOtpResend',
        'loginEmailVerify',
        'accountVerify',
        'accountVerificationOtpResend',
        'resendTwoFactorLoginOtp',
        'loginTwoFactorPassword',
        'accountVerificationOtp',
        'refreshAccessToken',
        'sendEmailVerificationOtp',
        'setNewPassword',
        'verifyEmailOtp',
        'setEmailPassword',
        'optInForWhatsapp',
        'userExistsByEmailOrPhone',
        'getInvitation',
        'acceptInvitation',
    ];

    public function handle($request, Closure $next)
    {
        $start_time = microtime(true);


        $user = Auth::guard('user')->user();

        if (empty($user) === true)
        {
            $input = Request::all();

            if($input == null || !isset($input['query']))
            {
                return Response::json(
                    $this->getErrorResponseForInvalidQuery(),400);
            }

            if ($this->isValidQuerySelector($input['query']) === false)
            {
                // If user session does not exist
                // Then only operations part of whitelist will be allowed
                // If operation is not part of whitelist then 401 will be returned
                return Response::json(
                    $this->getErrorResponseForGraphQlClients());
            }
        }

        $end_time =  microtime(true);

        $time_taken = $end_time - $start_time;

        $operationName = $request->input(self::OPERATION_NAME);

        Trace::info(TraceCode::GRAPH_REQUEST_AUTH_VALIDATION_TIME, [
            'auth_validation_time' => $time_taken,
            'operation_name'    => $operationName ?? null,
        ]);

        return $next($request);

    }

    /* This function will first check for the definition kind. All definition kind should be OperationDefinition.
    Then it will check for the selector names of first node in graph AST tree.
    It will consider query as a valid if all the selector names of first node will be part of WHITELISTED_QUERY_SELECTORS.
    If any selector is not part of whitelist then it will return as invalid query. */
    private function isValidQuerySelector($query)
    {
        try {
            $queryData = Parser::parse($query);

            $definitions = json_decode($queryData)->definitions;

            $isValidQuerySelector = true;

            foreach($definitions as $definition) {
                $definitionKind = $definition->kind;
                if($definitionKind === "OperationDefinition") {
                    $selectionSet = $definition->selectionSet;
                    $selections = $selectionSet->selections;
                    foreach($selections as $selection) {
                        $selectorName = $selection->name->value;
                        if ($this->isSelectorPartOfWhitelist($selectorName) === false){
                            $isValidQuerySelector = false;
                        }
                    }
                } else {
                    $isValidQuerySelector = false;
                }
            }
            return $isValidQuerySelector;
        } catch (\Throwable $th) {
            return false;
        }
    }


    private function isSelectorPartOfWhitelist($operation)
    {
        return in_array(
            $operation,
            self::WHITELISTED_QUERY_SELECTORS,
            true);
    }

    private function getErrorResponseForGraphQlClients()
    {
        app('trace')->info(TraceCode::UNAUTHORISED_BACKTRACE, [
            'backtrace' => debug_backtrace(10),
        ]);

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

    private function getErrorResponseForInvalidQuery()
    {
        return [
            'errors'    => [
                [
                    'message'   => 'Invalid query request',
                ]
            ],

            'data'      => null,
        ];
    }
}
