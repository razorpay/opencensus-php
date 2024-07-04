<?php

namespace App\Http\Controllers;

use App\Trace\Trace;
use App\Trace\TraceCode;
use App\Http\AppResponse;
use Illuminate\Http\Request;
use App\Admin\UcsRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

class UCSAdminController extends Controller
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
     * Handles all UCS admin requests.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function ucsGenericHandler(Request $request): JsonResponse
    {
        $this->trace->info(TraceCode::UCS_ADMIN_GENERIC_REQUEST_RECIEVED, [
            "method" => $request->method(),
            "path" => $request->route()->parameter("path"),
        ]);

        $service = new UcsRequest(
            $request->route()->parameter("path"),
            $request->method()
        );

        $body = $request->getContent();

        $data = json_decode($body, true);

        $successResponse = [];
        $errorResponse = [];

        [$httpCode, $response] = $service->send($data);


        if ($httpCode >= Response::HTTP_OK && $httpCode < Response::HTTP_BAD_REQUEST) {
            $successResponse = $response;
        }

        if ($httpCode >= Response::HTTP_BAD_REQUEST) {
            $errorResponse = $response;
        }

        return AppResponse::jsonResponse($errorResponse, $successResponse, $httpCode);
    }
}
