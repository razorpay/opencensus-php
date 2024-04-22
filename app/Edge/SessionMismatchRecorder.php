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
    const METRIC_LOGIN_SESSION_MISMATCH_COUNT = 'login_session_mismatch_total';

    const METRIC_POST_LOGIN_SESSION_MISMATCH_COUNT = 'post_login_session_mismatch_total';

    // temporary headers sent in response by Edge for mismatch metrics
    // should be small-case due to PHP parsing
    // definition exists in user-session plugin in Edge repo
    const HEADER_KEY_EDGE_MERCHANT_ID = "x-edge-jwt-merchant-id";
    const HEADER_KEY_EDGE_USER_ID = "x-edge-jwt-user-id";

    // Headers used by JWE post login verification flows to ensure
    // user_id and merchant_id identified by edge matches the user id and merchant id
    // verified by dashboard backend from the session token.
    const HEADER_KEY_EDGE_VERIFIED_MERCHANT_ID = "x-edge-verified-merchant-id";
    const HEADER_KEY_EDGE_VERIFIED_USER_ID = "x-edge-verified-user-id";


    const LOGIN_FLOW = 'login';

    const POST_LOGIN = 'post_login';

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
     * used to set verified data
     * from dashboard session token.
     * @var array
     */
    private $dashboardVerified = [];

    /**
     * used to set verified data
     * from edge headers for post login verification.
     * @var array
     */
    private $edgeVerified = [];

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
    private function hasLoginMismatches() : bool {
        if (empty($this->edgeData) || empty($this->legacyData)) {
            return false;
        }

        return $this->legacyData["merchant_id"] !== $this->edgeData["merchant_id"] ||
            $this->legacyData["user_id"] !== $this->edgeData["user_id"];
    }

    /**
     * checks if there are any mismatches between edge verified user and merchant info and
     * dashboard verified user and merchant info
     * @return bool
     */
    private function hasPostLoginMismatches() : bool {
        if (empty($this->dashboardVerified) && empty($this->edgeVerified)) {
            return false;
        }

        if (empty($this->dashboardVerified)) {
            return true;
        }

        return $this->dashboardVerified["merchant_id"] !== $this->edgeVerified["merchant_id"] ||
            $this->dashboardVerified["user_id"] !== $this->edgeVerified["user_id"];
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
        if (empty($headers)) {
            app('trace')->error(TraceCode::SESSION_MISMATCH_NEW_DATA_SKIPPED, [
                "api_route" => $this->getRoute($path, $method),
                "message" => "malformed headers",
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
            (!empty($merchantHeader) && !isset($merchantHeader[0]))) {
            app('trace')->error(TraceCode::SESSION_MISMATCH_NEW_DATA_SKIPPED, [
                "api_route" => $this->getRoute($path, $method),
                "message" => "malformed edge headers",
                "user_header" => $userHeader,
                "merchant_header" => $merchantHeader,
            ]);
            return;
        }

        $this->edgeData = [
            "merchant_id" => empty($merchantHeader) ? "" : $merchantHeader[0],
            "user_id" => empty($userHeader) ? "" : $userHeader[0],
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
        if (empty($user)) {
            app('trace')->error(TraceCode::SESSION_MISMATCH_OLD_DATA_SKIPPED, [
                "api_route" => $this->getRoute($path, $method),
                "message" => "malformed generic user",
            ]);
            return;
        }

        // finds current merchant for user session object
        $currentMerchant = $user->currentMerchant();
        $this->legacyData = [
            "merchant_id" => (empty($currentMerchant) || empty($currentMerchant->id)) ? "" : $currentMerchant->id,
            "user_id" => $user->id ?? "",
        ];
    }

    /**
     * sets dashboardVerified data from session token in Authenticate middleware
     * @param string $userId user id from session token
     * @param string $merchantId merchant id from session token
     * @return void
     */
    public function setDashboardVerifiedData(string $userId, string $merchantId) : void {

         if (empty($this->edgeVerified)) {
             return;
         }

         if (empty($userId) && empty($merchantId)) {
             return;
         }

         $this->dashboardVerified = [
             "merchant_id" => $merchantId,
             "user_id" => $userId,
         ];
    }

    /**
     * sets edgeVerified data from headers added by edge to match edge verified data
     * with dashboard verified data.
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function setEdgeVerifiedData($request) : void {

        $userId = $request->headers->get(self::HEADER_KEY_EDGE_VERIFIED_USER_ID);
        $merchantId = $request->headers->get(self::HEADER_KEY_EDGE_VERIFIED_MERCHANT_ID);

        if (empty($userId) && empty($merchantId)) {
            return;
        }

        $this->edgeVerified = [
            "merchant_id"   => $merchantId,
            "user_id"       => $userId,
        ];
    }

    /**
     * record mismatches on metrics and logs if oldData and newData doesn't match
     * skips recording mismatches if oldData or newData is empty
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function recordMismatches($request, string $flow) : bool {

        $dimensions = $this->getMetricDimensions($request);

        if ($flow === self::LOGIN_FLOW  && $this->hasLoginMismatches()) {
            app('metrics')->count(self::METRIC_LOGIN_SESSION_MISMATCH_COUNT, 1, $dimensions);
            app('trace')->info(TraceCode::SESSION_MISMATCH, [
                "flow" => self::LOGIN_FLOW,
                "old_data" => $this->legacyData,
                "new_data" => $this->edgeData,
                "dimensions" => $dimensions,
            ]);
            return true;
        }

        if ($flow === self::POST_LOGIN && $this->hasPostLoginMismatches()) {
            app('metrics')->count(self::METRIC_POST_LOGIN_SESSION_MISMATCH_COUNT, 1, $dimensions);
            app('trace')->info(TraceCode::SESSION_MISMATCH, [
                "flow" => self::POST_LOGIN,
                "dashboard_verified" => $this->dashboardVerified,
                "edge_verified" => $this->edgeVerified,
                "dimensions" => $dimensions,
            ]);
            return true;
        }

        return false;
    }
}
