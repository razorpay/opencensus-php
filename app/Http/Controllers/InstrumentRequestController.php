<?php


namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;
use RZP\Trace\TraceCode;
use Illuminate\Routing\Controller as BaseController;


class InstrumentRequestController extends BaseController
{
    const X_DASHBOARD_ADMIN_EMAIL   = 'X-Dashboard-Admin-Email';
    const X_DASHBOARD_MERCHANT_ID   = "X-Dashboard-Merchant-Id";
    const X_DASHBOARD_MERCHANT_ORG_ID   = "X-Dashboard-Merchant-OrgId";

    // razorx flags
    const RAZORX_FLAG_SWITCH_BULK_PATCH_ROUTE = 'terminals_service_bulk_patch_instrument_request';

    protected $app;

    /**
     * InstrumentRequestController constructor.
     */
    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }

    public function getInternalInstrumentRequestById(string $id)
    {
        $response = $this->app['terminals_service']->proxyTerminalService(
            [],
            \Requests::GET,
            'v2/internal_instrument_request/' . $id,
            [],
            $this->getAdminHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    public function patchInternalInstrumentRequestById(string $id)
    {
        $input = Request::all();

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::PATCH,
            'v2/internal_instrument_request/' . $id,
            [],
            $this->getAdminHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    public function deleteInternalInstrumentRequestById(string $id)
    {
        $response = $this->app['terminals_service']->proxyTerminalService(
            [],
            \Requests::DELETE,
            'v2/internal_instrument_request/' . $id,
            [],
            $this->getAdminHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    public function fetchInternalInstrumentRequests()
    {
        $input = Request::all();

        $query = $input['query'];

        unset($input['query']);

        $query = $query . '&' . http_build_query($input);

        $response = $this->app['terminals_service']->proxyTerminalService(
            [],
            \Requests::GET,
            'v2/internal_instrument_request?' . $query,
            [],
            $this->getAdminHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    public function patchInternalInstrumentRequests()
    {
        $input = Request::all();

        $treatment = $this->app['razorx']->getTreatment(
            $this->app['request']->getTaskId(),
            self::RAZORX_FLAG_SWITCH_BULK_PATCH_ROUTE,
            $this->app['rzp.mode']
        );

        if ($treatment === 'on')
        {
            $response = $this->app['terminals_service']->proxyTerminalService(
                $input,
                \Requests::PATCH,
                'v2/internal_instrument_request_v2',
                ['timeout' => 300],
                $this->getAdminHeadersForInstrumentRequest());
        }
        else
        {
            $body = $input['body'];

            $query = $input['query'];

            $response = $this->app['terminals_service']->proxyTerminalService(
                $body,
                \Requests::PATCH,
                'v2/internal_instrument_request?' . $query,
                [],
                $this->getAdminHeadersForInstrumentRequest());
        }

        return ApiResponse::json($response);
    }

    public function bulkCopyInternalInstrumentRequest()
    {
        $input = Request::all();

        $query = $input['query'];

        $response = $this->app['terminals_service']->proxyTerminalService(
            null,
            \Requests::POST,
            'v2/internal_instrument_request?' . $query,
            [],
            $this->getAdminHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }


    /**
     * @param string $dashboard
     * This is a proxy for calling TS and returning the aspects of instrument requests that need to be shown to the current
     * admin user
     */
    public function getRazorxForAdminDashboard()
    {
        $response = $this->app['terminals_service']->proxyTerminalService(
            [],
            \Requests::GET,
            'v2/instrument_request/razorx/admin',
            [],
            $this->getAdminHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    protected function getAdminHeadersForInstrumentRequest() : array
    {
        return [
            self::X_DASHBOARD_ADMIN_EMAIL => $this->getAdminEmail(),
        ];
    }

    protected function getAdminEmail() : string
    {
        return $this->app['basicauth']->getDashboardHeaders()['admin_email'] ?? '';
    }

    // Below methods are for merchant dashboard
    public function createMerchantInstrumentRequest()
    {
        $input = Request::all();

        $merchant = $this->app['basicauth']->getMerchant();

        $this->trace->info(
            TraceCode::CREATE_MERCHANT_INSTRUMENT_REQUEST,
            [
                'input'          => $input,
                'merchant_id'    => $merchant->getId(),
            ]);

        $input['merchant_id'] = $merchant->getId();

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::POST,
            'v2/merchant_instrument_request',
            [],
            $this->getMerchantHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    public function getMerchantInstrumentRequest()
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $this->trace->info(
            TraceCode::GET_MERCHANT_INSTRUMENT_REQUESTS,
            [
                'merchant_id'          => $merchant->getId(),
            ]);

        $response = $this->app['terminals_service']->proxyTerminalService(
            [],
            \Requests::GET,
            'v2/merchant_instrument_request?merchant_id=' . $merchant->getId(),
            [],
            $this->getMerchantHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    public function getMerchantInstrumentStatus()
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $this->trace->info(
            TraceCode::GET_MERCHANT_INSTRUMENT_STATUS,
            [
                'merchant_id'          => $merchant->getId(),
            ]);

        $response = $this->app['terminals_service']->proxyTerminalService(
            [],
            \Requests::GET,
            'v2/merchant_instrument_status?merchant_id=' . $merchant->getId(),
            ['timeout' => 2],
            $this->getMerchantHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    public function getMIRInstruments()
    {
        $response = $this->app['terminals_service']->proxyTerminalService(
            [],
            \Requests::GET,
            'v2/merchant_instruments',
            [],
            $this->getAdminHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    public function fetchInstrumentCommentList()
    {
        $response = $this->app['terminals_service']->proxyTerminalService(
            [],
            \Requests::GET,
            'v2/instrument_request_comment_list',
            [],
            $this->getAdminHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    public function setMIRInstrument()
    {
        $input = Request::all();

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::PATCH,
            'v2/merchant_instrument',
            [],
            $this->getAdminHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    public function getMerchantInstrumentRequestById(string $id)
    {
        $this->trace->info(
            TraceCode::GET_MERCHANT_INSTRUMENT_REQUEST_BY_ID,
            [
                'id'          => $id,
            ]);

        $response = $this->app['terminals_service']->proxyTerminalService(
            [],
            \Requests::GET,
            'v2/merchant_instrument_request/' . $id,
            [],
            $this->getMerchantHeadersForInstrumentRequest()
            );

        return ApiResponse::json($response);
    }

    public function patchMerchantInstrumentRequestById(string $id)
    {
        $input = Request::all();

        $this->trace->info(
            TraceCode::PATCH_MERCHANT_INSTRUMENT_REQUEST,
            [
                'id'          => $id,
                'input'       => $input,
            ]);

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::PATCH,
            'v2/merchant_instrument_request/' . $id,
            [],
            $this->getMerchantHeadersForInstrumentRequest()
            );

        return ApiResponse::json($response);
    }

    public function getMerchantInstruments()
    {
        $input = Request::all();

        $query = $input['query'];

        $merchantIds = $input['merchant_ids'];

        unset($input['query'], $input['merchant_ids']);

        $query = $query . '&' . http_build_query($input);

        $instrumentHeaders = $this->getAdminHeadersForInstrumentRequest();

        $response = $this->app['terminals_service']->getMerchantInstruments($merchantIds, $query, $instrumentHeaders);

        return ApiResponse::json($response);

    }

    // for kam dashboard
    public function createMerchantInstrumentRequests()
    {
        $input = Request::all();

        $this->trace->info(
            TraceCode::CREATE_MERCHANT_INSTRUMENT_REQUEST_BULK,
            [
                'input'          => $input,
            ]);

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::POST,
            'v2/merchant_instrument_requests',
            [],
            $this->getKAMHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    protected function getMerchantHeadersForInstrumentRequest() : array
    {
        $merchant = $this->app['basicauth']->getMerchant();

        return [
            self::X_DASHBOARD_MERCHANT_ID => $merchant->getId(),
            self::X_DASHBOARD_MERCHANT_ORG_ID=>$merchant->getOrgId(),
            self::X_DASHBOARD_ADMIN_EMAIL => $this->getAdminEmail(), // will be empty if not kam
        ];

    }

    protected function getKAMHeadersForInstrumentRequest() : array
    {
        return [
            self::X_DASHBOARD_ADMIN_EMAIL => $this->getAdminEmail(), // will be empty if not kam
        ];

    }

}
