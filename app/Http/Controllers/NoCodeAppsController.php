<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Illuminate\Http\Request;
use RZP\Services\NoCodeAppsService;
use RZP\Trace\TraceCode;

class NoCodeAppsController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     * @throws \RZP\Exception\ServerErrorException
     */
    public function sendRequest(Request $request): mixed
    {
        [$module, $path] = $this->getModuleAndPath($request);

        $this->trace->info(TraceCode::NO_CODE_APPS_REQUEST_RECEIVED, [
            "method" => $request->method(),
            "module" => $module,
            "path" => $path,
        ]);

        $res = (new NoCodeAppsService($this->app))->forwardNcaRequest($request, $module, $path);

        $this->trace->info(TraceCode::NO_CODE_APPS_RESPONSE_RECEIVED, [
            "method" => $request->method(),
            "module" => $module,
            "path" => $path,
            "code" => $res["code"],
        ]);

        return ApiResponse::json($res['data'], $res['code']);
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    private function getModuleAndPath(Request $request): array
    {
        $ncaModules = ["categories", "catalogs", "orders"];

        $path = str_replace("v1/stores", "", $request->path());
        $path = ltrim($path, "/");
        $exploded = explode("/", $path);
        $module = "stores";

        if (count($exploded) >= 1 && $exploded[0] !== "" && in_array($exploded[0], $ncaModules) === true)
        {
            $module = $exploded[0];

            unset($exploded[0]);

            $path = implode("/", $exploded);

            return [$module, $path];
        }


        $path = str_replace($module, "", $path);

        return [$module, $path];
    }
}
