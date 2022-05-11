<?php


namespace RZP\Http\Controllers;

use Request;
use RZP\Http\Request\Requests;
use ApiResponse;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class GrowthController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function handleAdminRequests($path = null)
    {
        $parameters = Request::all();

        try {

            $response = $this->app->growthService->sendRequest($parameters, $path, Request::method());
            $response = ApiResponse::json($response);

        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing admin request', ErrorCode::SERVER_ERROR_GROWTH_FAILURE, null, $e);
        }

        return $response;
    }

    public function getAssetDetails()
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === false) {

                $response = $this->app->growthService->getAssetDetails($parameters);

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the getAssetDetails request', ErrorCode::SERVER_ERROR_GROWTH_FAILURE, null, $e);
        }

        return $response;
    }

        public function getTemplateByIdDetails($id = null)
        {
            $response = [];

            try {
                if (empty($id) === false) {
                    $parameters = [
                        "template_id" => $id
                    ];

                    $response = $this->app->growthService->getTemplateByIdDetails($parameters);

                    $response = ApiResponse::json($response);

                }
            } catch (\Throwable $e) {
                throw new Exception\ServerErrorException('Error completing the getTemplateByIdDetails request', ErrorCode::SERVER_ERROR_GROWTH_FAILURE, null, $e);
            }

            return $response;
        }

    public function getPublicAssetDetails()
    {
        $parameters = Request::all();
        $response = [];
        try {
            if (empty($parameters) === false) {
                $response = $this->app->growthService->getPublicAssetDetails($parameters);
                $response = ApiResponse::json($response);
            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the getPublicAssetDetails request', ErrorCode::SERVER_ERROR_GROWTH_FAILURE, null, $e);
        }
        return $response;
    }


    public function enableDowntimeNotificationForXDashboard()
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === false) {

                $response = $this->app->growthService->editTemplateAndEnableDowntimeNotificationForXDashboard($parameters);

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the request', ErrorCode::SERVER_ERROR_GROWTH_FAILURE, null, $e);
        }

        return $response;
    }

    public function filterAndSyncEventsFromPinot()
    {
        try {
            $parameters = Request::all();
            $response = $this->app->growthService->filterAndSyncEventsFromPinot($parameters);
            $response = ApiResponse::json($response);

        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the request', ErrorCode::SERVER_ERROR_GROWTH_FAILURE, null, $e);

        }
        return $response;
    }
}
