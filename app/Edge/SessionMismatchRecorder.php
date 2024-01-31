<?php

namespace App\Edge;

use App\Http\ApiUrl;
use \App\Providers\GenericUser;
use App\Metrics\Constants;
use App\Trace\TraceCode;

/**
 * compares session data between legacy & new flow
 * and records it on prometheus and logs
 */
class SessionMismatchRecorder
{

    // prometheus metric name used to record mismatches
    const METRIC_SESSION_MISMATCH_COUNT = 'session_mismatch_total';

    // temporary headers sent in response by Edge for mismatch metrics
    // should be small-case due to PHP parsing
    // definition exists in user-session plugin in Edge repo
    const HEADER_KEY_EDGE_MERCHANT_ID = "x-edge-jwt-merchant-id";
    const HEADER_KEY_EDGE_USER_ID = "x-edge-jwt-user-id";

    /**
     * used to set legacy data
     * from Redis sessions
     * @var array
     */
    private $legacyData = [];

    /**
     * used to set new data
     * from Edge
     * @var array
     */
    private $edgeData = [];

    /**
     * concatenates method with path (eg. POST /users/login)
     * @param string $path request path for request to API backend
     * @param string $method HTTP verb for request to API backend
     */
    private function getRoute(string $path, string $method) : string {
        return (strtoupper($method) . ' ' . $path);
    }

    private function getMetricDimensions($request) : array
    {
        $routeName = $request->route() !== null ? $request->route()->getName() : 'unknown_route';
        $apolloClientName = $request->header('apollographql-client-name');

        return [
            Constants::LABEL_HTTP_REQUESTS_ORIGIN         => ApiUrl::getRequestOrigin(),
            Constants::LABEL_HTTP_REQUESTS_DOMAIN         => $request->server->get('SERVER_NAME') ?? 'unknown_domain',
            Constants::LABEL_HTTP_REQUESTS_GRAPHQL_CLIENT => $apolloClientName ?? 'unknown_graphql_client',
            Constants::LABEL_HTTP_REQUESTS_PRODUCT        => ApiUrl::isBankingOriginRequest() ? Constants::BANKING : Constants::PRIMARY,
            Constants::LABEL_HTTP_REQUESTS_ROUTE          => $routeName,
        ];
    }

    /**
     * checks if there are any mismatches between oldData and newData
     * @return bool
     */
    private function hasMismatches() : bool {
        return $this->legacyData["merchant_id"] !== $this->edgeData["merchant_id"] ||
            $this->legacyData["user_id"] !== $this->edgeData["user_id"];
    }

    /**
     * sets newData from API response headers if API route is whitelisted
     * @param string $path request path for request to API backend
     * @param string $method HTTP verb for request to API backend
     * @param array|null $headers all response headers from API backend
     * @return void
     */
    public function setEdgeData(string $path, string $method, array|null $headers) : void
    {
        // if no headers received, exits early
        if (empty($headers))
        {
            app('trace')->error(TraceCode::SESSION_MISMATCH_NEW_DATA_SKIPPED, [
                "api_route" => $this->getRoute($path, $method),
                "message"   => "malformed headers",
            ]);
            return;
        }

        $userHeader = array_key_exists(self::HEADER_KEY_EDGE_USER_ID, $headers)
            ? $headers[self::HEADER_KEY_EDGE_USER_ID] : [];
        $merchantHeader = array_key_exists(self::HEADER_KEY_EDGE_MERCHANT_ID, $headers)
            ? $headers[self::HEADER_KEY_EDGE_MERCHANT_ID] : [];

        // if both merchant and user headers are not present,
        // skip setting edgeData
        if (empty($merchantHeader) && empty($userHeader))
            return;

        // validates that both userHeader and merchantHeader
        // are sequential arrays (with integer indexes)
        if ((!empty($userHeader) && !isset($userHeader[0])) ||
            (!empty($merchantHeader) && !isset($merchantHeader[0])))
        {
            app('trace')->error(TraceCode::SESSION_MISMATCH_NEW_DATA_SKIPPED, [
                "api_route"       => $this->getRoute($path, $method),
                "message"         => "malformed edge headers",
                "user_header"     => $userHeader,
                "merchant_header" => $merchantHeader,
            ]);
            return;
        }

        $this->edgeData = [
            "merchant_id"   => empty($merchantHeader) ? "" : $merchantHeader[0],
            "user_id"       => empty($userHeader) ? "" : $userHeader[0],
        ];

    }

    /**
     * sets oldData from `GenericUser` if API route is whitelisted
     * @param string $path request path for request to API backend
     * @param string $method HTTP verb for request to API backend
     * @param GenericUser|null $user object stored in session/prepared from login response
     * @return void
     */
    public function setLegacyData(string $path, string $method, GenericUser|null $user) : void
    {
        // requires edge data to be set first
        if (empty($this->edgeData))
            return;

        // skips if session user object is not present
        if (empty($user))
        {
            app('trace')->error(TraceCode::SESSION_MISMATCH_OLD_DATA_SKIPPED, [
                "api_route" => $this->getRoute($path, $method),
                "message"   => "malformed generic user",
            ]);
            return;
        }

        // finds current merchant for user session object
        $currentMerchant = $user->currentMerchant();
        $this->legacyData = [
            "merchant_id"   => (empty($currentMerchant) || empty($currentMerchant->id)) ? "" : $currentMerchant->id,
            "user_id"       => $user->id ?? "",
        ];
    }

    /**
     * record mismatches on metrics and logs if oldData and newData doesn't match
     * skips recording mismatches if oldData or newData is empty
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function recordMismatches($request) : void {
        try
        {

            $dimensions = $this->getMetricDimensions($request);

            // if no data is set, no need to compare
            // it can happen for non-whitelisted API routes
            // or some error (check logs for root cause)
            if (empty($this->legacyData) || empty($this->edgeData))
                return;

            // checks if there are any mismatches
            // skips if there is none
            if (!$this->hasMismatches())
                return;

            app('metrics')->count(self::METRIC_SESSION_MISMATCH_COUNT, 1, $dimensions);
            app('trace')->info(TraceCode::SESSION_MISMATCH, [
               "old_data" => $this->legacyData,
               "new_data" => $this->edgeData,
               "dimensions" => $dimensions,
            ]);
        }
        catch (\Throwable $throwable)
        {
            app('trace')->warning(TraceCode::RECORD_SESSION_MISMATCH_FAILED, [
                'message' => $throwable->getMessage() ?? 'unknown_message',
            ]);
        }
    }

}
