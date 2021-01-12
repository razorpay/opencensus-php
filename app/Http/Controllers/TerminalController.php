<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Trace\TraceCode;

class TerminalController extends Controller
{
    public function putTerminal(string $id)
    {
        $input = Request::all();

        $data = $this->service()->editTerminal($id, $input);

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

    public function syncDeletedTerminalsOnTerminalService()
    {
        $input = Request::all();

        $response = $this->app['terminals_service']->syncDeletedTerminalsOnTerminalService($input);

        return ApiResponse::json($response);
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

    public function proxyV2TerminalService()
    {
        $input = Request::all();

        $method = Request::method();

        $path = Request::path();

        $traceData = ["method" => $method, "path" => $path];

        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V2, $traceData);

        $path = str_replace("v1/terminals/proxy","v2", $path);

        $response = $this->app['terminals_service']->proxyTerminalService($input, $method, $path);

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
}
