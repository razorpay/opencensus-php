<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use Route;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;
use RZP\Exception\BadRequestException;

class TerminalController extends Controller
{
    const X_DASHBOARD_ADMIN_EMAIL   = 'X-Dashboard-Admin-Email';
    const X_DASHBOARD_MERCHANT_ID   = "X-Dashboard-Merchant-Id";

    public function putTerminal(string $id)
    {
        $input = Request::all();

        $data = $this->service()->editTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function getEditableFields()
    {
        $input = Request::all();

        $data = $this->service()->getEditableFields();

        return ApiResponse::json($data);
    }

    public function postBulkAssignBuyPricingPlans()
    {
        $input = Request::all();

        $data = $this->service()->bulkAssignPricingPlans($input);

        return ApiResponse::json($data);
    }

    public function restoreTerminal(string $id)
    {
        $data = $this->service()->restoreTerminal($id);

        return ApiResponse::json($data);
    }

    public function deleteTerminal(string $id)
    {
        $data = $this->service()->deleteTerminal2($id);

        return ApiResponse::json($data);
    }

    public function postCheckTerminalEncryptedValue(string $id)
    {
        $input = Request::all();

        $data = $this->service()->checkTerminalEncryptedValue($id, $input);

        // proxy code
        $mode  = $this->ba->getMode();

        $variantFlag = $this->app->razorx->getTreatment($id, "ROUTE_PROXY_TS_CHECK_SECRETS", $mode);

        if ($variantFlag === 'proxy'){

            $path = "v1/terminals/" . $id . "/secrets";

            $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

            if ($response != $data)
            {
                $traceData = ["api" => $data, "terminals" => $response];

                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TS_CHECK_SECRETS_COMPARISON_FAILED, $traceData);
            }

            return ApiResponse::json($response);
        }

        return ApiResponse::json($data);
    }

    public function toggleTerminal(string $id)
    {
        $input = Request::all();

        $data = $this->service()->toggleTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function toggleTerminalInternal(string $id, string $action)
    {
        $input = [
            'toggle' => ($action == 'disable') ? 0 : 1
        ];

        $data = $this->service()->toggleTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function addMerchant(string $id, string $mid)
    {
        $data = $this->service()->addMerchantToTerminal($id, $mid);

        return ApiResponse::json($data);
    }

    public function removeMerchant(string $id, string $mid)
    {
        $data = $this->service()->removeMerchantFromTerminal($id, $mid);

        return ApiResponse::json($data);
    }

    public function reassignMerchant(string $id)
    {
        $input = Request::all();

        $data = $this->service()->reassignMerchantForTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function fillEnabledWallets()
    {
        $input = Request::all();

        $data = $this->service()->fillEnabledWallets($input);

        return ApiResponse::json($data);
    }

    public function getBanks(string $id)
    {
        $data = $this->service()->getBanks($id);

        $mode  = $this->ba->getMode();

        try
        {
            $variantFlag = $this->app->razorx->getTreatment($id, "ROUTE_PROXY_TS_BANK_FETCH", $mode);

            if ($variantFlag === 'proxy') {

                $path = "v1/terminals/" . $id . "/banks";

                $response = $this->app['terminals_service']->proxyTerminalService('', "GET", $path);

                if ($response != $data) {
                    $traceData = ["api" => $data, "terminals" => $response,];

                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TS_BANK_FETCH_COMPARISON_FAILED, $traceData);
                }

                 return ApiResponse::json($response);
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->info(
                TraceCode::TERMINALS_SERVICE_PROXY_TS_BANK_FETCH_FAILED,
                [
                    'message'             => 'exception',
                    'error'               => $exception->getMessage(),
                ]);
        }

        return ApiResponse::json($data);
    }

    public function setBanks(string $id)
    {
        $input = Request::all();

        $data = $this->service()->setBanks($id, $input);

        return ApiResponse::json($data);
    }

    public function getWallets(string $id)
    {
        $data = $this->service()->getWallets($id);

        $mode  = $this->ba->getMode();

        try
        {
            $variantFlag = $this->app->razorx->getTreatment($id, "ROUTE_PROXY_TS_WALLET_FETCH", $mode);

            if ($variantFlag === 'proxy') {

                $path = "v1/terminals/" . $id . "/wallets";

                $response = $this->app['terminals_service']->proxyTerminalService('', "GET", $path);

                if ($response != $data) {
                    $traceData = ["api" => $data, "terminals" => $response,];

                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TS_WALLET_FETCH_COMPARISON_FAILED, $traceData);
                }

                return ApiResponse::json($response);
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->info(
                TraceCode::TERMINALS_SERVICE_PROXY_TS_WALLET_FETCH_FAILED,
                [
                    'message'             => 'exception',
                    'error'               => $exception->getMessage(),
                ]);
        }

        return ApiResponse::json($data);
    }

    public function setWallets(string $id)
    {
        $input = Request::all();

        $data = $this->service()->setWallets($id, $input);

        return ApiResponse::json($data);
    }

    public function updateTerminalsBank()
    {
        $input = Request::all();

        $response = $this->service()->updateTerminalsBank($input);

        return ApiResponse::json($response);
    }

    public function postTerminalsMigrateCron()
    {
        $input = Request::all();

        $response = $this->service()->terminalsMigrateCron($input);

        return ApiResponse::json($response);
    }

    public function postHitachiTerminalsCurrencyUpdateCron()
    {
        $input = Request::all();

        $response = $this->service()->hitachiTerminalsCurrencyUpdateCron($input['count'] ?? 100);

        return ApiResponse::json($response);
    }

    public function syncDeletedTerminalsOnTerminalService()
    {
        $input = Request::all();

        $response = $this->app['terminals_service']->syncDeletedTerminalsOnTerminalService($input);

        return ApiResponse::json($response);
    }

    public function triggerInstrumentRulesEvent()
    {
        $input = Request::all();

        $data = $this->service()->triggerInstrumentRulesEventBulk($input);

        return ApiResponse::json($data);
    }

    public function proxyV1TerminalService()
    {
        $input = Request::all();

        $method = Request::method();

        $path = Request::path();

        $traceData = ["method" => $method, "path" => $path];

        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $traceData);

        $path = str_replace("v1/terminals/proxy","v1", $path);

        $response = $this->app['terminals_service']->proxyTerminalService($input, $method, $path);

        return ApiResponse::json($response);
    }

    public function validateUniversalAdminCall(string $path)
    {
        $isUniversalRoute = str_contains($path,'universal');
        if ($isUniversalRoute) {
            $this->trace->info(TraceCode::TERMINALS_SERVICE_UNIVERSAL_PROXY, ['universal path' => $path]);
            //it will expect two kind of routes
            //for eg, valid routes: universal/bulk_terminal_disable, universal/terminal/14_digits_id
            //invalid routes: universal/bulk/not_14_digits, universal/more_than_25_characters
            $universalRegex = "/^(\/[[:alnum:]]{4,25})(\/[[:alnum:]]{14})?$/";
            $relevantPath = str_after($path, 'universal');
            $isInvalidUrl = !preg_match($universalRegex, $relevantPath);

            if ($isInvalidUrl)
                throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }

    public function proxyV2TerminalService()
    {
        $input = Request::all();

        $method = Request::method();

        $path = Request::path();

        $traceData = ["method" => $method, "path" => $path, "input" => $input];

        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V2, $traceData);

        $path = str_replace("v1/terminals/proxy","v2", $path);

        $this->validateUniversalAdminCall($path);

        if($path === 'v2/collect_info/merchant/details'){
            $path = 'v2/collect_info/merchant/'.$this->getMerchantId().'/details';
        }

        if ($path == 'v1/admin/merchant/terminals'){
            $path = 'v2/admin/merchant/terminals';
        }

        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V2, ["current Path" => $path]);
        // For some routes exposed on both merchant and admin, we have appended /admin in admin auth endpoints. Simply removing /admin so that it hits same endpoint on TS
        if(str_ends_with($path, '/admin'))
        {
            $path = str_replace('/admin', '', $path);
        }

        if ($path === 'v2/discrepancy_list_merchant')
        {
            $path = 'v2/discrepancy_list'; // both terminals/proxy/discrepancy_list and terminals/proxy/discrepancy_list_merchant calls same route on TS
        }

        if (in_array(Route::currentRouteName(), \RZP\Http\Route::$terminalsServiceFormRequestsRoutes) === true)
        {
            $response = $this->app['terminals_service']->proxyTerminalServiceFormRequest($input, $method, $path, ['timeout' => 0.5], $this->getHeadersForProxyRequest());
        }
        else
        {
            $response = $this->app['terminals_service']->proxyTerminalService($input, $method, $path, [],  $this->getHeadersForProxyRequest());
        }

        return ApiResponse::json($response);
    }

    protected function getHeadersForProxyRequest() : array
    {
        return [
            self::X_DASHBOARD_MERCHANT_ID => $this->getMerchantId(), // will be empty if not merchant auth/private auth
            self::X_DASHBOARD_ADMIN_EMAIL => $this->getAdminEmail(), // will be empty if not kam
        ];
    }

    public function proxyGetTerminalsGatewayStatus()
    {
        $input = Request::all();

        $gateway = $input['gateway'];

        $mid =  $this->ba->getMerchant()->getId();

        $method = Request::method();

        $path = "v2/terminal/onboard/" . $mid . "/status?gateway=" . $gateway;

        $response = $this->app['terminals_service']->proxyTerminalService('', $method, $path, ['timeout' => 10],  []);

        return ApiResponse::json($response);
    }

    public function fetchTerminalTestRun()
    {
        $input = Request::all();

        $query = $input['query'];

        unset($input['query']);

        $query = $query . '&' . http_build_query($input);

        $response = $this->app['terminals_service']->proxyTerminalService(
            [],
            \Requests::GET,
            'v2/terminal_test_run?' . $query,
            [],
            $this->getAdminHeaders());

        return ApiResponse::json($response);
    }

    public function postTerminalTestOtp()
    {
        $input = Request::all();

        $traceInput = $input;

        if (isset($input['message']) === true)
        {
            $traceInput['message'] = substr($input['message'], 0, 20); // first few charaters would be for SMS sender ID, we can log that
        }

        $this->trace->info(TraceCode::SMS_SYNC_SAVE_OTP_CALL_RECEIVED, $traceInput);

        // TODO: do some signature/secret validation here

        $path = "v2/terminal_test_otp/ifttt";

        $data = [
            'data' => $input
        ];

        $response = $this->app['terminals_service']->proxyTerminalService($data, 'POST', $path);

        return ApiResponse::json($response);
    }

    public function fetchTerminalsCredentials()
    {
        $input = Request::all();

        $path = Request::path();

        $path = str_replace("v1","v2", $path);

        $merchant = $this->app['basicauth']->getMerchant();

        $this->trace->info(
            TraceCode::GET_MERCHANT_INSTRUMENT_STATUS,
            [
                'merchant_id'          => $merchant->getId(),
            ]);

        $input['merchant_ids']=[$merchant->getId()];

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::POST,
            $path);

        return ApiResponse::json($response);
    }

    public function fetchMerchantsTerminals()
    {
        $input = Request::all();

        $orgId = $this->app['basicauth']->getOrgId();

        $input['org_id'] = Org\Entity::verifyIdAndSilentlyStripSign($orgId);
        if(isset($input['merchant_id']))
        {
            $input['merchant_ids'] = [$input['merchant_id']];
        }

        if(isset($input['terminal_id']))
        {
            $input['terminal_ids'] = [$input['terminal_id']];
        }

        $response = $this->app['terminals_service']->proxyTerminalService(
            $input,
            \Requests::POST,
            'v1/merchants/terminals'
        );

        return ApiResponse::json($response);
    }


    public function updateTerminalsBulk()
    {
        $input = Request::all();

        $response = $this->service()->updateTerminalsBulk($input);

        return $response;
    }

    // used by batch service
    public function postTerminalsBulk()
    {
        $input = Request::all();

        $response = $this->service()->postTerminalsBulk($input);

        return ApiResponse::json($response->toArrayWithItems());
    }

    public function fetchTerminalById(string $id)
    {
        $data = $this->service()->fetchTerminalById($id);

        return ApiResponse::json($data);
    }

    public function postTokenizeMpans()
    {
        $input = Request::all();

        $cronResponse = $this->service()->tokenizeExistingMpans($input);

        return ApiResponse::json($cronResponse);
    }

    protected function getAdminHeaders() : array
    {
        $adminEmail = $this->getAdminEmail();
        if (empty($adminEmail) === true)
        {
            return [];
        }

        return [
            self::X_DASHBOARD_ADMIN_EMAIL => $this->getAdminEmail(),
        ];
    }

    protected function getAdminEmail() : string
    {
        return $this->app['basicauth']->getDashboardHeaders()['admin_email'] ?? '';
    }

    protected function getMerchantId() : string
    {
        $merchant = $this->app['basicauth']->getMerchant();

        if (is_null($merchant) === false)
        {
            return $merchant->getId();
        }
        return '';
    }
}
