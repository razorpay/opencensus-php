<?php

namespace App\Http\Controllers;

use App\Trace\Trace;
use App\Trace\TraceCode;
use App\Http\AppResponse;
use Illuminate\Http\Request;
use App\Services\MerchantExperienceService\MerchantExperienceServiceRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

class MerchantExperienceController extends Controller
{
    /**
     * @var \App\Trace\Trace
     */
    private Trace $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();
        $this->trace = $app["trace"];
    }

    /**
     * Handles all MES admin requests.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function mesGenericHandler(Request $request): JsonResponse
    {
        $this->trace->info(TraceCode::MES_ADMIN_GENERIC_REQUEST_RECEIVED, [
            "method" => $request->method(),
            "path" => $request->route()->parameter("path"),
        ]);

        $service = new MerchantExperienceServiceRequest(
            $request->route()->parameter("path"),
            $request->method()
        );

        $body = $request->getContent();

        $data = json_decode($body, true);

        $successResponse = [];
        $errorResponse = [];

        try {
            [$httpCode, $response] = $service->send($data);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::MES_SERVICE_REQUEST_FAILED, ["message" => $e->getMessage()]);
            return AppResponse::jsonResponse(
                ["error" => "Failed to process the request"],
                [],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }


        if ($httpCode >= Response::HTTP_OK && $httpCode < Response::HTTP_BAD_REQUEST) {
            return AppResponse::jsonResponse([], $response, $httpCode);
        }

        return AppResponse::jsonResponse($response, [], $httpCode);
    }
}
