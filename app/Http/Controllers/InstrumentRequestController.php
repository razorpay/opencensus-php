<?php


namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;
use RZP\Services\TerminalsService;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Permission;
use Illuminate\Routing\Controller as BaseController;


class InstrumentRequestController extends BaseController
{
    const X_DASHBOARD_ADMIN_EMAIL   = 'X-Dashboard-Admin-Email';
    const X_DASHBOARD_ADMIN_ORG_ID = 'X-Dashboard-Admin-OrgId';
    const X_DASHBOARD_MERCHANT_ID   = "X-Dashboard-Merchant-Id";
    const X_DASHBOARD_MERCHANT_ORG_ID   = "X-Dashboard-Merchant-OrgId";
    const PERMISSION    =   'permission';

    protected $app;

    protected $auth;

    /**
     * InstrumentRequestController constructor.
     */
    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->auth = $this->app['basicauth'];

        $this->adminOrgId = $this->app['basicauth']->getAdminOrgId();
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

    public function pauseInternalInstrumentRequestById(string $id)
    {
        $input = Request::all();

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::PATCH,
            'v2/internal_instrument_request/'. $id.'/toggle_pause',
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

        $input['count'] = (int)($input['count'] ?? 500);

        $input['skip'] = (int)($input['skip'] ?? 0);

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::GET,
            'v2/internal_instrument_request',
            ['timeout' => 2, 'data_format' => 'body'],
            $this->getAdminHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    public function patchInternalInstrumentRequests()
    {
        $input = Request::all();

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::PATCH,
            'v2/internal_instrument_request_v2',
            ['timeout' => 30],
            $this->getAdminHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    public function cancelInternalInstrumentRequests()
    {
        $input = Request::all();

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::PATCH,
            'v2/cancel_internal_instrument_request',
            ['timeout' => 30],
            $this->getAdminHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    public function bulkCopyInternalInstrumentRequest()
    {
        $input = Request::all();

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::POST,
            'v2/internal_instrument_request',
            ['timeout' => 300],
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
        if($this->auth->getAdmin()!== null) {
            $this->trace->info(TraceCode::X_DASHBOARD_ADMIN_ORG_ID, [
                'org_id' => $this->auth->getAdmin()->getPublicOrgId(),
            ]);
            return [
                self::X_DASHBOARD_ADMIN_EMAIL => $this->getAdminEmail(),

                self::X_DASHBOARD_ADMIN_ORG_ID => $this->auth->getAdmin()->getPublicOrgId()
            ];
        }

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
            ['timeout' => 2],
            $this->getMerchantHeadersForInstrumentRequest());

        //For sending 400 Error to FE, we need to throw BadRequestException(not possible to send capture_info json), so send status_code explicitly with capture info json in response
        if(isset($response[TerminalsService::CODE])
            && $response[TerminalsService::CODE] === TerminalsService::CAPTURE_INFO_UPFRONT_ERROR) {
            return ApiResponse::json($response, $response[TerminalsService::STATUS_CODE]);
        }

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
            ['timeout' => 1],
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
            ['timeout' => 2.5],
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

        $input['count'] = (int)($input['count'] ?? 500);

        $input['skip'] = (int)($input['skip'] ?? 0);

        $response = $this->app['terminals_service']->getMerchantInstruments($input, $this->getAdminHeadersForInstrumentRequest());

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
            ['timeout' => 45],
            $this->getKAMHeadersForInstrumentRequest());

        return ApiResponse::json($response);
    }

    // for kam dashboard
    public function createMerchantInstrumentRequestsV2()
    {
        $input = Request::all();

        $this->trace->info(
            TraceCode::CREATE_MERCHANT_INSTRUMENT_REQUEST_BULK_V2,
            [
                'input'          => $input['data'],
            ]);

        $response = $this->app['terminals_service']->proxyTerminalServiceFormRequest(
            $input,
            \Requests::POST,
            'v2/merchant_instrument_requests_v2',
            ['timeout' => 45],
            $this->getKAMHeadersForInstrumentRequest()
        );

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
        $permissionName = Permission\Name::UPDATE_MERCHANT_INSTRUMENT_REQUEST;
        $adminPermissions = $this->auth->getAdmin()->getPermissionsList();

        foreach ($adminPermissions as $adminPermission) {
            if ($adminPermission === Permission\Name::SKIP_ACTIVATION_CHECK_WHILE_RAISING_MIR_FROM_KAM){
                $permissionName = Permission\Name::SKIP_ACTIVATION_CHECK_WHILE_RAISING_MIR_FROM_KAM;
            }
        }

        $this->trace->info(
            TraceCode::SKIP_ACTIVATION_CHECK_PERMISSION,
            [
                'PERMISSION'          => $permissionName,
            ]);

        return [
            self::X_DASHBOARD_ADMIN_EMAIL => $this->getAdminEmail(), // will be empty if not kam
            self::PERMISSION => $permissionName
        ];

    }

}
