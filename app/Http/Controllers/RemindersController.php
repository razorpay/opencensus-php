<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use App\Http\AppResponse;
use RZP\Exception\BadRequestException;
use RZP\Models\Reminders\InvoiceReminderProcessor;

class RemindersController extends Controller
{
    protected $reminders;

    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const PATCH  = 'PATCH';
    const DELETE = 'DELETE';

    const WHITELISTED_ROUTES_REGEX = [
        self::GET => [
            '^merchant_config$',
            '^merchant_config\/[[:alnum:]]{14}$',
            '^merchant_settings$',
            '^merchant_settings\/[[:alnum:]]{14}$',
            '^configs$',
            '^configs\/[[:alnum:]]{14}$',
        ],
        self::POST => [
            '^merchant_config$',
            '^merchant_settings$',
            '^configs$',
        ],
        self::PUT => [
            '^merchant_config$',
        ],
        self::PATCH => [
            '^merchant_settings\/[[:alnum:]]{14}$',
        ],
        self::DELETE => [
            '^configs\/[[:alnum:]]{14}$',
        ]
    ];

    const WHITELIST_ADMIN_ROUTES_REGEX = [
        self::GET => [
            '^merchant_settings$',
            '^merchant_settings\/[[:alnum:]]{14}$',
            '^configs$',
            '^configs\/[[:alnum:]]{14}$',
        ],
        self::POST => [
            '^merchant_settings$',
            '^configs$',
        ],
        self::PATCH => [
            '^merchant_settings\/[[:alnum:]]{14}$',
        ],
        self::DELETE => [
            '^configs\/[[:alnum:]]{14}$',
        ]
    ];

    public function __construct()
    {
        parent::__construct();

        $this->reminders = $this->app['reminders'];
    }

    public function sendReminder(string $mode, string $entity, string $namespace, string $id)
    {
        $input = Request::all();

        $mode = ($mode === Mode::TEST) ? Mode::TEST : Mode::LIVE;

        $this->app['basicauth']->setModeAndDbConnection($mode);

        $response = (new InvoiceReminderProcessor)->process($entity, $namespace, $id, $input);

        return ApiResponse::json($response);
    }

    public function handleAny($path = '')
    {
        $method = Request::method();

        if(array_key_exists($method, self::WHITELISTED_ROUTES_REGEX) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $whiteListedRoutesRegex = implode('|', self::WHITELISTED_ROUTES_REGEX[$method]);

        if (preg_match('/' . $whiteListedRoutesRegex . '/', $path, $pathMatches) == false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $method = Request::method();
        $data = Request::all();

        $response = $this->reminders->sendAnyRequest($path, $method, $data);

        $statusCode = $response['status_code'];

        unset($response['status_code']);

        return ApiResponse::json($response, $statusCode);
    }

    public function remindersAdmin($path = '')
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

        $response = $this->reminders->sendAnyRequest($path, $method, $data);

        $statusCode = $response['status_code'];

        unset($response['status_code']);

        return ApiResponse::json($response, $statusCode);
    }

    public function remindersNextRun(string $entity, string $id)
    {
        $response = (new InvoiceReminderProcessor)->nextRunAt($entity, $id);

        return ApiResponse::json($response);
    }
}
