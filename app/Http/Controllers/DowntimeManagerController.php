<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use App\Http\AppResponse;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Downtime\DowntimeManagerService;

class DowntimeManagerController extends Controller
{
    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const DELETE = 'DELETE';

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

    public function downtimeManagerAdmin($path = '')
    {
        $method = Request::method();

        if(array_key_exists($method, self::WHITELIST_ADMIN_ROUTES_REGEX) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $whiteListedAdminRoutesRegex = implode('|', self::WHITELIST_ADMIN_ROUTES_REGEX[$method]);

        if (preg_match('/' . $whiteListedAdminRoutesRegex . '/', $path, $pathMatches) == false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $method = Request::method();
        $data = Request::all();

        $response = (new DowntimeManagerService($this->app))->sendAnyRequest($path, $method, $data);

        $statusCode = $response['status_code'];

        unset($response['status_code']);

        return ApiResponse::json($response, $statusCode);
    }
}
