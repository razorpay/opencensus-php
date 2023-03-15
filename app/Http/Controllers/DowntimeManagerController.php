<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use App\Http\AppResponse;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Downtime\DowntimeManagerService;
use RZP\Trace\TraceCode;

class DowntimeManagerController extends Controller
{
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';

    const OPTIMIZER_TERMINAL_DOWNTIME_AUTO_RESOLVE_CRON = '/v1/downtimes/resolve';

    const OPTIMIZER_TERMINAL_DOWNTIME_MANUAL_RESOLVE = '/v1/downtimes/manual/resolve';

    const OPTIMIZER_MANUAL_TERMINAL_DOWNTIME_FETCH = '/v1/downtimes/manual/fetch';

    const WHITELIST_ADMIN_ROUTES_REGEX = [
        self::GET => [
            '^instruments',
            '^instruments\/[[:alnum:]]{14}$',
            '^instruments\/[[:alnum:]]{14}$\/configs$',
            '^subscriptions$',
            '^merchant_subscriptions\/[[:alnum:]]{14}$',
            '^merchant_subscriptions\/[[:alnum:]]{14}\/email_preferences$',
            '^merchant_subscriptions\/[[:alnum:]]{14}\/method_preferences$',
            '^metadata\/channels$',
            '^metadata\/methods$',
            '^metadata\/[[:alnum:]]{14}\/methods$',
            '^metadata\/methodmapping$',
        ],
        self::POST => [
            '^instruments$',
            '^instruments\/[[:alnum:]]{14}\/configs$',
            '^subscriptions$',
            '^merchant_subscriptions$',
        ],
        self::PUT => [
            '^instruments\/[[:alnum:]]{14}\/configs$',
            '^subscriptions\/[[:alnum:]]{14}$',
            '^merchant_subscriptions\/[[:alnum:]]{14}$',
            '^merchant_subscriptions\/[[:alnum:]]{14}\/toggle_notification$',
            '^merchant_subscriptions\/[[:alnum:]]{14}\/email_preferences$',
            '^merchant_subscriptions\/[[:alnum:]]{14}\/method_preferences$',
        ],
        self::DELETE => [
            '^instruments\/[[:alnum:]]{14}$',
            '^subscriptions\/[[:alnum:]]{14}$',
        ]
    ];

    const WHITELIST_MERCHANT_ROUTES_REGEX = [
        self::POST => [
            '^sr$',
            '^error$',
        ],
    ];


    public function downtimeManagerAdmin($path = '')
    {
        $method = Request::method();

        if (array_key_exists($method, self::WHITELIST_ADMIN_ROUTES_REGEX) === false) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $whiteListedAdminRoutesRegex = implode('|', self::WHITELIST_ADMIN_ROUTES_REGEX[$method]);

        if (preg_match('/' . $whiteListedAdminRoutesRegex . '/', $path, $pathMatches) == false) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $method = Request::method();
        $data = Request::all();

        $response = (new DowntimeManagerService($this->app))->sendAnyRequest($path, $method, $data);

        $statusCode = $response['status_code'];

        unset($response['status_code']);

        return ApiResponse::json($response, $statusCode);
    }

    public function FetchSRForMerchant($path = '')
    {
        $method = Request::method();

        if (array_key_exists($method, self::WHITELIST_MERCHANT_ROUTES_REGEX) === false) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $whiteListedMerchantRoutesRegex = implode('|', self::WHITELIST_MERCHANT_ROUTES_REGEX[$method]);

        if (preg_match('/' . $whiteListedMerchantRoutesRegex . '/', $path, $pathMatches) == false) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        if ($this->app['rzp.mode'] !== Mode::LIVE) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }


        $method = Request::method();
        $data = Request::all();

        $this->trace->info(TraceCode::DOWNTIME_MANAGER_REQUEST, [
            'path' => $path,
            'data' => $data
        ]);

        $response = (new DowntimeManagerService($this->app))->sendRequest($path, $method, $data, 'SR');

        $statusCode = $response['status_code'];

        unset($response['status_code']);

        return ApiResponse::json($response, $statusCode);
    }

    public function runTerminalDowntimeAutoresolve($path = '')
    {

        $method = Request::method();

        $data = Request::all();

        $path = self::OPTIMIZER_TERMINAL_DOWNTIME_AUTO_RESOLVE_CRON;

        $this->trace->info(TraceCode::DOWNTIME_MANAGER_REQUEST, [
            'path' => $path,
            'data' => $data
        ]);

        $response = (new DowntimeManagerService($this->app))->sendAnyRequest($path, $method, $data);

        $statusCode = $response['status_code'];

        $this->trace->info(TraceCode::DOWNTIME_MANAGER_RESPONSE, [
            'response' => $response,
        ]);

        unset($response['status_code']);

        return ApiResponse::json($response, $statusCode);
    }

    public function fetchManualTerminalDowntimes($path = '')
    {

        $method = Request::method();

        $data = Request::all();

        $path = self::OPTIMIZER_MANUAL_TERMINAL_DOWNTIME_FETCH;

        $this->trace->info(TraceCode::DOWNTIME_MANAGER_REQUEST, [
            'path' => $path,
            'data' => $data
        ]);

        $response = (new DowntimeManagerService($this->app))->sendAnyRequest($path, $method, $data);

        $statusCode = $response['status_code'];

        $this->trace->info(TraceCode::DOWNTIME_MANAGER_RESPONSE, [
            'response' => $response,
        ]);

        unset($response['status_code']);

        return ApiResponse::json($response, $statusCode);
    }

    public function terminalDowntimeManualResolve($path = '')
    {

        $method = Request::method();

        $data = Request::all();

        $path = self::OPTIMIZER_TERMINAL_DOWNTIME_MANUAL_RESOLVE;

        $this->trace->info(TraceCode::DOWNTIME_MANAGER_REQUEST, [
            'path' => $path,
            'data' => $data
        ]);

        $response = (new DowntimeManagerService($this->app))->sendAnyRequest($path, $method, $data);

        $statusCode = $response['status_code'];

        $this->trace->info(TraceCode::DOWNTIME_MANAGER_RESPONSE, [
            'response' => $response,
        ]);

        unset($response['status_code']);

        return ApiResponse::json($response, $statusCode);
    }
}
