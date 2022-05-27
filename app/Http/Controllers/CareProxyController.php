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
    const CHECK_ELIGIBILITY                  = 'twirp/rzp.care.callback.v1.CallbackService/CheckEligibility';
    const CHECK_INSTANT_CALLBACK_ELIGIBILITY = 'twirp/rzp.care.callback.v1.CallbackService/CheckInstantCallbackEligibility';
    const CHECK_ELIGIBILITY_V2               = 'twirp/rzp.care.callback.v1.CallbackService/CheckEligibilityV2';
    const GET_SLOTS                          = 'twirp/rzp.care.callback.v1.CallbackService/GetSlots';
    const CREATE_CALLBACK                    = 'twirp/rzp.care.callback.v1.CallbackService/CreateCallback';
    const CREATE_INSTANT_CALLBACK            = 'twirp/rzp.care.callback.v1.CallbackService/CreateInstantCallback';
    const GET_CALLBACK                       = 'twirp/rzp.care.callback.v1.CallbackService/GetCallback';
    const CHAT_INIT                          = 'twirp/rzp.care.chat.v1.ChatService/Init';

    //cron
    const INIT_SLOTS             = 'twirp/rzp.care.callback.v1.CallbackService/InitSlots';
    const PUSH_CALLBACK_TO_QUEUE = 'twirp/rzp.care.callback.v1.CallbackService/PushCallbacksToQueue';
    const HANDLE_CHANGE_VISIBLE_SLOT_SIZE  = 'twirp/rzp.care.callback.v1.CallbackService/HandleChangeInVisibleSlotSize';

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

    //Click to call Timings config
    const CLICK_TO_CALL_GET_TIMINGS_CONFIG = 'twirp/rzp.care.admin.v1.CallbackConfigService/GetClickToCallTimingConfig';
    const CLICK_TO_CALL_PUT_TIMINGS_CONFIG = 'twirp/rzp.care.admin.v1.CallbackConfigService/PutClickToCallTimingConfig';
    const CLICK_TO_CALL_GET_HOLIDAYS       = 'twirp/rzp.care.admin.v1.CallbackConfigService/GetClickToCallHolidays';
    const CLICK_TO_CALL_PUT_HOLIDAYS       = 'twirp/rzp.care.admin.v1.CallbackConfigService/PutClickToCallHolidays';

    //TicketConfig
    const TICKET_CONFIG_ADD_SUBCATEGORY                 = 'twirp/rzp.care.ticket.v1.TicketConfigService/CreateSubCategory';
    const TICKET_CONFIG_ADD_ITEM                        = 'twirp/rzp.care.ticket.v1.TicketConfigService/CreateItem';
    const TICKET_CONFIG_FETCH_SUBCATEGORY_ITEM_MERCHANT = 'twirp/rzp.care.ticket.v1.TicketConfigService/SubcategoryItemListM';
    const TICKET_CONFIG_FETCH                           = 'twirp/rzp.care.ticket.v1.TicketConfigService/SubcategoryItemList';
    const TICKET_CONFIG_FETCH_SUBCATEGORY               = 'twirp/rzp.care.ticket.v1.TicketConfigService/FetchSubCategory';
    const TICKET_CONFIG_FETCH_ITEM                      = 'twirp/rzp.care.ticket.v1.TicketConfigService/FetchItem';
    const TICKET_CONFIG_EDIT_SUBCATEGORY                = 'twirp/rzp.care.ticket.v1.TicketConfigService/EditSubCategory';
    const TICKET_CONFIG_EDIT_ITEM                       = 'twirp/rzp.care.ticket.v1.TicketConfigService/EditItem';
    const TICKET_CONFIG_UPDATE_SUBCATEGORY_STATUS = 'twirp/rzp.care.ticket.v1.TicketConfigService/UpdateSubCategoryStatus';
    const TICKET_CONFIG_UPDATE_ITEM_STATUS        = 'twirp/rzp.care.ticket.v1.TicketConfigService/UpdateItemStatus';
    const TICKET_CONFIG_DELETE_SUBCATEGORY        = 'twirp/rzp.care.ticket.v1.TicketConfigService/DeleteSubCategory';
    const TICKET_CONFIG_DELETE_ITEM               = 'twirp/rzp.care.ticket.v1.TicketConfigService/DeleteItem';
    const TICKET_CONFIG_UPDATE_SUBCATEGORY_RANK   = 'twirp/rzp.care.ticket.v1.TicketConfigService/UpdateRankSubCategory';
    const TICKET_CONFIG_UPDATE_ITEM_RANK          = 'twirp/rzp.care.ticket.v1.TicketConfigService/UpdateRankItem';

    //FaqConfig
    const FAQ_CONFIG_ADD_FAQ               = 'twirp/rzp.care.faq.v1.FaqConfigService/CreateFaq';
    const FAQ_CONFIG_FETCH_FAQS            = 'twirp/rzp.care.faq.v1.FaqConfigService/FetchFaqs';
    const FAQ_CONFIG_UPDATE_FAQ_STATUS     = 'twirp/rzp.care.faq.v1.FaqConfigService/UpdateFaqStatus';
    const FAQ_CONFIG_UPDATE_FAQ            = 'twirp/rzp.care.faq.v1.FaqConfigService/UpdateFaq';
    const FAQ_CONFIG_DELETE_FAQ            = 'twirp/rzp.care.faq.v1.FaqConfigService/DeleteFaq';
    const FAQ_CONFIG_ADD_FAQ_RANKING       = 'twirp/rzp.care.faq.v1.FaqConfigService/AddFaqRanking';
    const FAQ_CONFIG_DELETE_FAQ_RANKING    = 'twirp/rzp.care.faq.v1.FaqConfigService/DeleteFaqRanking';
    const FAQ_CONFIG_UPDATE_FAQ_RANKING    = 'twirp/rzp.care.faq.v1.FaqConfigService/UpdateFaqRanking';
    const FAQ_CONFIG_CREATE_DASHBOARD      = 'twirp/rzp.care.faq.v1.FaqConfigService/CreateDashboardGuide';
    const FAQ_CONFIG_EDIT_DASHBOARD        = 'twirp/rzp.care.faq.v1.FaqConfigService/UpdateDashboardGuide';
    const FAQ_CONFIG_DELETE_DASHBOARD_ID   = 'twirp/rzp.care.faq.v1.FaqConfigService/DeleteDashboardGuide';
    const FAQ_CONFIG_FETCH_DASHBOARD_ID    = 'twirp/rzp.care.faq.v1.FaqConfigService/FetchDashboardGuide';



    const ROUTE_VS_PERMISSION = [
        self::CALLBACK_GET_DATE_CONFIG                => Name::CALLBACK_SLOT_CONFIG_VIEW,
        self::CALLBACK_EDIT_DATE_CONFIG               => Name::CALLBACK_SLOT_CONFIG_EDIT,
        self::CALLBACK_GET_WEEK_CONFIG                => Name::CALLBACK_SLOT_CONFIG_VIEW,
        self::CALLBACK_EDIT_WEEK_CONFIG               => Name::CALLBACK_SLOT_CONFIG_EDIT,
        self::UPSERT_OPERATOR                         => Name::MANAGE_CARE_SERVICE_CALLBACK,
        self::CLICK_TO_CALL_GET_TIMINGS_CONFIG        => Name::CLICK_TO_CALL_TIMING_CONFIG_VIEW,
        self::CLICK_TO_CALL_PUT_TIMINGS_CONFIG        => Name::CLICK_TO_CALL_TIMING_CONFIG_EDIT,
        self::CLICK_TO_CALL_GET_HOLIDAYS              => Name::CLICK_TO_CALL_TIMING_CONFIG_VIEW,
        self::CLICK_TO_CALL_PUT_HOLIDAYS              => Name::CLICK_TO_CALL_TIMING_CONFIG_EDIT,
        self::TICKET_CONFIG_ADD_SUBCATEGORY           => Name::TICKET_CONFIG_EDIT,
        self::TICKET_CONFIG_ADD_ITEM                  => Name::TICKET_CONFIG_EDIT,
        self::FAQ_CONFIG_ADD_FAQ                      => Name::FAQ_CONFIG_EDIT,
        self::FAQ_CONFIG_FETCH_FAQS                   => Name::FAQ_CONFIG_VIEW,
        self::FAQ_CONFIG_UPDATE_FAQ_STATUS            => Name::FAQ_CONFIG_EDIT,
        self::FAQ_CONFIG_UPDATE_FAQ                   => Name::FAQ_CONFIG_EDIT,
        self::FAQ_CONFIG_DELETE_FAQ                   => Name::FAQ_CONFIG_EDIT,
        self::TICKET_CONFIG_UPDATE_SUBCATEGORY_STATUS => Name::TICKET_CONFIG_EDIT,
        self::TICKET_CONFIG_UPDATE_ITEM_STATUS        => Name::TICKET_CONFIG_EDIT,
        self::TICKET_CONFIG_DELETE_SUBCATEGORY        => Name::TICKET_CONFIG_EDIT,
        self::TICKET_CONFIG_DELETE_ITEM               => Name::TICKET_CONFIG_EDIT,
        self::TICKET_CONFIG_FETCH                     => Name::FAQ_CONFIG_VIEW,
        self::TICKET_CONFIG_FETCH_SUBCATEGORY         => Name::TICKET_CONFIG_VIEW,
        self::TICKET_CONFIG_FETCH_ITEM                => Name::TICKET_CONFIG_VIEW,
        self::TICKET_CONFIG_EDIT_SUBCATEGORY          => Name::TICKET_CONFIG_EDIT,
        self::TICKET_CONFIG_EDIT_ITEM                 => Name::TICKET_CONFIG_EDIT,
        self::TICKET_CONFIG_UPDATE_SUBCATEGORY_RANK   => Name::TICKET_CONFIG_EDIT,
        self::TICKET_CONFIG_UPDATE_ITEM_RANK          => Name::TICKET_CONFIG_EDIT,
        self::FAQ_CONFIG_ADD_FAQ_RANKING              => Name::FAQ_CONFIG_EDIT,
        self::FAQ_CONFIG_DELETE_FAQ_RANKING           => Name::FAQ_CONFIG_EDIT,
        self::FAQ_CONFIG_UPDATE_FAQ_RANKING           => Name::FAQ_CONFIG_EDIT,
        self:: FAQ_CONFIG_CREATE_DASHBOARD            => Name::FAQ_CONFIG_EDIT,
        self:: FAQ_CONFIG_EDIT_DASHBOARD              => Name::FAQ_CONFIG_EDIT,
        self:: FAQ_CONFIG_DELETE_DASHBOARD_ID         => Name::FAQ_CONFIG_EDIT,
        self:: FAQ_CONFIG_FETCH_DASHBOARD_ID          => Name::FAQ_CONFIG_VIEW,
    ];

    const MERCHANT_ROUTES = [
        self::CHECK_ELIGIBILITY,
        self::CHECK_INSTANT_CALLBACK_ELIGIBILITY,
        self::CHECK_ELIGIBILITY_V2,
        self::GET_SLOTS,
        self::CREATE_CALLBACK,
        self::CREATE_INSTANT_CALLBACK,
        self::GET_CALLBACK,
        self::CHAT_INIT,
        self::TICKET_CONFIG_FETCH_SUBCATEGORY_ITEM_MERCHANT,
    ];

    const CRON_ROUTES = [
        self::INIT_SLOTS,
        self::PUSH_CALLBACK_TO_QUEUE,
        self::HANDLE_CHANGE_VISIBLE_SLOT_SIZE,
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
        self::CLICK_TO_CALL_PUT_HOLIDAYS,
        self::CLICK_TO_CALL_GET_HOLIDAYS,
        self::CLICK_TO_CALL_PUT_TIMINGS_CONFIG,
        self::CLICK_TO_CALL_GET_TIMINGS_CONFIG,
        self::TICKET_CONFIG_ADD_SUBCATEGORY,
        self::TICKET_CONFIG_ADD_ITEM,
        self::FAQ_CONFIG_ADD_FAQ,
        self::FAQ_CONFIG_FETCH_FAQS,
        self::FAQ_CONFIG_UPDATE_FAQ_STATUS,
        self::FAQ_CONFIG_UPDATE_FAQ,
        self::FAQ_CONFIG_DELETE_FAQ,
        self::TICKET_CONFIG_FETCH,
        self::TICKET_CONFIG_FETCH_SUBCATEGORY,
        self::TICKET_CONFIG_FETCH_ITEM,
        self::TICKET_CONFIG_EDIT_SUBCATEGORY,
        self::TICKET_CONFIG_EDIT_ITEM,
        self::TICKET_CONFIG_UPDATE_SUBCATEGORY_STATUS,
        self::TICKET_CONFIG_UPDATE_ITEM_STATUS,
        self::TICKET_CONFIG_DELETE_SUBCATEGORY,
        self::TICKET_CONFIG_DELETE_ITEM,
        self::TICKET_CONFIG_UPDATE_SUBCATEGORY_RANK,
        self::TICKET_CONFIG_UPDATE_ITEM_RANK,
        self::FAQ_CONFIG_DELETE_FAQ_RANKING,
        self::FAQ_CONFIG_ADD_FAQ_RANKING,
        self::FAQ_CONFIG_UPDATE_FAQ_RANKING,
        self:: FAQ_CONFIG_CREATE_DASHBOARD,
        self:: FAQ_CONFIG_EDIT_DASHBOARD,
        self:: FAQ_CONFIG_DELETE_DASHBOARD_ID,
        self:: FAQ_CONFIG_FETCH_DASHBOARD_ID,
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

    public function postDarkProxyRequest()
    {
        $input = Request::all();

        $response = $this->app['care_service']->darkProxyRequest($input['path'], $input['body']);

        return ApiResponse::json($response);
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
