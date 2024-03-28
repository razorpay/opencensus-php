<?php

namespace App\Edge;

use Config;

use App\Trace\TraceCode;
use App\Admin\ApiRequestAny;
use GuzzleHttp\Client as Guzzle;
use Razorpay\Api\Errors\BadRequestError;
use Razorpay\Api\Errors\ErrorCode;

/**
 * manages response headers received from API
 * which needs to be forwarded
 */
class EdgeClient
{
    const X_EDGE_USER_JTI = 'X-Edge-User-Jti';
    const FEATURE_FLAG    = 'edge_logout_flow_enabled';

    private $revokeApi  = 'user-tokens/revoke';

    /**
     * @var \GuzzleHttp\Client|null
     */
    private $client;

    private $app;

    private $trace;

    public function __construct($httpClient = null)
    {
        $this->app = \App::getFacadeRoot();;

        $this->trace = $this->app['trace'];

        if (empty($httpClient)) {
            $this->client = new Guzzle([
                'base_uri' => Config::get('api.edge_url'),
                'defaults' => [
                    'timeout' => 5,
                ],
                'headers'  => ['apikey' => env('EDGE_APIKEY', '')]
            ]);
        } else {
            $this->client = $httpClient;
        }
    }

    /**
     * revokes user token at edge
     * @param $userIds array
     * @param $exclude_current_session bool
     * @return void
     */
    public function revokeToken($userIds = [], $exclude_current_session = false) {
        // delete token at edge only if experiment is enabled
        if (! $this->getRazorxExperimentResult()) {
            return;
        }

        $jti = \Request::header(self::X_EDGE_USER_JTI);
        $params = [];

        if (! empty($userIds)) {                      // used by other session delete apis like user detach, email update etc
            $params['user_ids'] = $userIds;
            if ($exclude_current_session) {
                $params['exclude_jti'] = $jti;
            }
        } elseif (! empty($jti)) {                   // used by logout api
            $params['jti'] = $jti;
        }

        // skip token deletion if there are no params
        if (!$params) {
            // TODO: throw exceptions when we move out of shadow mode
//            throw new ServerErrorException(
//                "Failed to revoke user session token at edge no jti or user id found",
//                \Razorpay\Api\Errors\ErrorCode::SERVER_ERROR,
//                500);
            return;
        }

        try {
            $this->client->patch($this->revokeApi, [
                'form_params' => $params,
            ]);
            $this->trace->info(TraceCode::EDGE_TOKEN_REVOKE_SUCCESS, [
                "params"   => $params,
            ]);
        } catch (\Throwable $e) {  // Guzzle will raise exceptions for any 4xx/5xx errors hence catch and log
            $this->trace->error(TraceCode::EDGE_TOKEN_REVOKE_FAILED, [
                "message"   => $e->getMessage() ?? 'unknown_message',
                "params"    => $params
            ]);

            return;

            // TODO: throw exceptions when we move out of shadow mode
//            throw new ServerErrorException(
//                "Failed to revoke user session token from edge: " . $e->getMessage(),
//                \Razorpay\Api\Errors\ErrorCode::SERVER_ERROR,
//                500);
        }
    }

    /**
     * gets razorx experiment result
     *
     * @return bool
     * @throws BadRequestError
     */
    public function getRazorxExperimentResult()
    {
        try {
            $request = new ApiRequestAny(['client_type' => 'merchant']);

            list($error, $data) = $request->send("razorx/evaluate/" . self::FEATURE_FLAG, 'GET');

            if (empty($error) === false)
            {
                throw new BadRequestError($error[0], ErrorCode::BAD_REQUEST_ERROR, 400);
            }

            return $data['result'] === 'on';
        } catch (\Throwable $e) {
            $this->trace->error(TraceCode::EDGE_TOKEN_REVOKE_FAILED, [
                "message"   => $e->getMessage() ?? 'unknown_message',
            ]);
            return false;
        }
    }
}
