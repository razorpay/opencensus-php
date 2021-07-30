<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Permission\Name;
use RZP\Exception\BadRequestException;

class CareProxyController extends Controller
{

    //proxy
    const CHECK_ELIGIBILITY = 'twirp/rzp.care.callback.v1.CallbackService/CheckEligibility';
    const GET_SLOTS         = 'twirp/rzp.care.callback.v1.CallbackService/GetSlots';
    const CREATE_CALLBACK   = 'twirp/rzp.care.callback.v1.CallbackService/CreateCallback';
    const GET_CALLBACK      = 'twirp/rzp.care.callback.v1.CallbackService/GetCallback';
    const CHAT_INIT         = 'twirp/rzp.care.chat.v1.ChatService/Init';

    //cron
    const INIT_SLOTS             = 'twirp/rzp.care.callback.v1.CallbackService/InitSlots';
    const PUSH_CALLBACK_TO_QUEUE = 'twirp/rzp.care.callback.v1.CallbackService/PushCallbacksToQueue';

    //MyOperator
    const IN_CALL    = 'twirp/rzp.care.callback.v1.CallbackService/InCallWebhook';
    const AFTER_CALL = 'twirp/rzp.care.callback.v1.CallbackService/AfterCallWebhook';

    //admin
    const UPSERT_OPERATOR = 'twirp/rzp.care.callback.v1.CallbackService/UpsertOperator';

    //chat
    const CHAT_GET_MERCHANT  = 'twirp/rzp.care.chat.v1.ChatService/GetMerchant';
    const CHAT_FETCH_TICKETS = 'twirp/rzp.care.chat.v1.ChatService/FetchTickets';

    const CALLBACK_GET_DATE_CONFIG = 'twirp/rzp.care.admin.v1.CallbackConfigService/getDateSlotConfig';
    const CALLBACK_GET_WEEK_CONFIG = 'twirp/rzp.care.admin.v1.CallbackConfigService/getWeekSlotConfig';

    const CALLBACK_EDIT_DATE_CONFIG = 'twirp/rzp.care.admin.v1.CallbackConfigService/editDateSlotConfig';
    const CALLBACK_EDIT_WEEK_CONFIG = 'twirp/rzp.care.admin.v1.CallbackConfigService/editWeekSlotConfig';

    const ROUTE_VS_PERMISSION = [
        self::CALLBACK_GET_DATE_CONFIG  => Name::CALLBACK_SLOT_CONFIG_VIEW,
        self::CALLBACK_EDIT_DATE_CONFIG => Name::CALLBACK_SLOT_CONFIG_EDIT,
        self::CALLBACK_GET_WEEK_CONFIG  => Name::CALLBACK_SLOT_CONFIG_VIEW,
        self::CALLBACK_EDIT_WEEK_CONFIG => Name::CALLBACK_SLOT_CONFIG_EDIT,
        self::UPSERT_OPERATOR           => Name::MANAGE_CARE_SERVICE_CALLBACK,
    ];

    const MERCHANT_ROUTES = [
        self::CHECK_ELIGIBILITY,
        self::GET_SLOTS,
        self::CREATE_CALLBACK,
        self::GET_CALLBACK,
        self::CHAT_INIT,
    ];

    const CRON_ROUTES = [
        self::INIT_SLOTS,
        self::PUSH_CALLBACK_TO_QUEUE,
    ];

    const MYOPERATOR_ROUTES = [
        self::IN_CALL,
        self::AFTER_CALL
    ];

    const ADMIN_ROUTES = [
        self::UPSERT_OPERATOR,
        self::CALLBACK_GET_DATE_CONFIG,
        self::CALLBACK_EDIT_DATE_CONFIG,
        self::CALLBACK_GET_WEEK_CONFIG,
        self::CALLBACK_EDIT_WEEK_CONFIG,
    ];

    const CHAT_ROUTES = [
        self::CHAT_GET_MERCHANT,
        self::CHAT_FETCH_TICKETS,
    ];

    public function postDashboardProxyRequest($path)
    {
        $this->validatePathForRequest(self::MERCHANT_ROUTES, $path);

        $input = Request::all();

        $response = $this->app['care_service']->dashboardProxyRequest($path, $input);

        return ApiResponse::json($response);
    }

    public function postCronProxyRequest($path)
    {
        $this->validatePathForRequest(self::CRON_ROUTES, $path);

        $input = Request::all();

        $response = $this->app['care_service']->cronProxyRequest($path, $input);

        return ApiResponse::json($response);
    }

    public function postMyOperatorWebhookProxyRequest($path)
    {
        $this->validatePathForRequest(self::MYOPERATOR_ROUTES, $path);

        $input = Request::all();

        $response = $this->app['care_service']->myOperatorWebhookProxyRequest($path, $input);

        return ApiResponse::json($response);
    }

    public function postAdminProxyRequest($path)
    {
        $this->validatePathForRequest(self::ADMIN_ROUTES, $path);

        $this->validatePermissionForRequest($path);

        $input = Request::all();

        $response = $this->app['care_service']->adminProxyRequest($path, $input);

        return ApiResponse::json($response);
    }

    public function postChatProxyRequest($path)
    {
        $this->validatePathForRequest(self::CHAT_ROUTES, $path);

        $input = Request::all();

        $respponse = $this->app['care_service']->chatProxyRequest($path, $input);

        return ApiResponse::json($respponse);
    }

    protected function validatePathForRequest($routes, $path)
    {
        if (in_array($path, $routes) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }

    protected function validatePermissionForRequest($path)
    {
        $this->ba->getAdmin()->hasPermissionOrFail(self::ROUTE_VS_PERMISSION[$path]);
    }
}
