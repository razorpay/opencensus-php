<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;


use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Services\MasterOnboardingService;

class MasterOnboardingController extends Controller
{
    protected $masterOnboardingService;

    public function __construct()
    {
        parent::__construct();

        $this->masterOnboardingService = $this->app['master_onboarding'];
    }

    /**
     * Validates and forwards request to MOB.
     * @return mixed
     * @throws \RZP\Exception\BadRequestException
     */

    public function proxyRequest()
    {
        $request = Request::instance();

        $requestUri = substr(Request::path(), 7);

        $method = Request::method();

        $payload = $request->all();

        $response = $this->masterOnboardingService->sendRequestAndParseResponse($requestUri, $method, $payload, false);

        return ApiResponse::json($response);
    }

    public function adminRequestForOneCa($path = null)
    {
        $request = Request::instance();

        $requestUri = $path;

        $oneCaPath = ['intents','save_workflow'];

        if(in_array($requestUri,$oneCaPath) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Does not have permission to submit one ca form'
            );
        }

        $method = $request->method();

        $payload   = $request->all();

        $response = $this->masterOnboardingService->sendRequestAndParseResponse($requestUri, $method, $payload, true);

        return ApiResponse::json($response);
    }

    public function adminRequest($path = null)
    {
        $request = Request::instance();

        $requestUri = $path;

        $method = $request->method();

        $payload   = $request->all();

        $response = $this->masterOnboardingService->sendRequestAndParseResponse($requestUri, $method, $payload, true);

        return ApiResponse::json($response);
    }

    public function mobMigration()
    {
        $input = Request::all();

        $this->trace->info(TraceCode::MASTER_ONBOARDING_MIGRATION_REQUEST, $input);

        $data = $this->masterOnboardingService->mobMigration($input["mids"]);

        return ApiResponse::json($data);
    }
}
