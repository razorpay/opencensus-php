<?php


namespace RZP\Http\Controllers;

use Request;
use RZP\Http\Request\Requests;
use ApiResponse;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class CommissionServiceController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function handleAdminRequests($path = null)
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === false) {

                $response = $this->app->commissionService->sendAdminRequest($parameters, $path, Request::method());

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the request', ErrorCode::SERVER_ERROR_COMMISSION_SERVICE_FAILURE, null, $e);
        }

        return $response;
    }

    public function createRuleGroup()
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === false) {

                $response = $this->app->commissionService->createRuleGroup($parameters);

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the createRuleGroup request', ErrorCode::SERVER_ERROR_COMMISSION_SERVICE_FAILURE, null, $e);
        }

        return $response;
    }

    public function createRule()
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === false) {

                $response = $this->app->commissionService->createRule($parameters);

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the createRule request', ErrorCode::SERVER_ERROR_COMMISSION_SERVICE_FAILURE, null, $e);
        }

        return $response;
    }

    public function getAllRuleGroup()
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === true) {

                $response = $this->app->commissionService->getAllRuleGroup($parameters);

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the getAllRuleGroup request', ErrorCode::SERVER_ERROR_COMMISSION_SERVICE_FAILURE, null, $e);
        }

        return $response;
    }

    public function getRuleGroupById()
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === false) {

                $response = $this->app->commissionService->getRuleGroupById($parameters);

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the getRuleGroupById request', ErrorCode::SERVER_ERROR_COMMISSION_SERVICE_FAILURE, null, $e);
        }

        return $response;
    }

    public function updateRuleGroup()
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === false) {

                $response = $this->app->commissionService->updateRuleGroup($parameters);

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the updateRuleGroup request', ErrorCode::SERVER_ERROR_COMMISSION_SERVICE_FAILURE, null, $e);
        }

        return $response;
    }

    public function updateRule()
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === false) {

                $response = $this->app->commissionService->updateRule($parameters);

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the updateRule request', ErrorCode::SERVER_ERROR_COMMISSION_SERVICE_FAILURE, null, $e);
        }

        return $response;
    }

    public function getRule()
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === false) {

                $response = $this->app->commissionService->getRule($parameters);

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the getRule request', ErrorCode::SERVER_ERROR_COMMISSION_SERVICE_FAILURE, null, $e);
        }

        return $response;
    }

    public function getRuleByRuleGroupId()
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === false) {

                $response = $this->app->commissionService->getRuleByRuleGroupId($parameters);

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the getRuleByRuleGroupId request', ErrorCode::SERVER_ERROR_COMMISSION_SERVICE_FAILURE, null, $e);
        }

        return $response;
    }

    public function createRuleConfigMapping()
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === false) {

                $response = $this->app->commissionService->createRuleConfigMapping($parameters);

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the createRuleConfigMapping request', ErrorCode::SERVER_ERROR_COMMISSION_SERVICE_FAILURE, null, $e);
        }

        return $response;
    }

    public function updateRuleConfigMapping()
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === false) {

                $response = $this->app->commissionService->updateRuleConfigMapping($parameters);

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the updateRuleConfigMapping request', ErrorCode::SERVER_ERROR_COMMISSION_SERVICE_FAILURE, null, $e);
        }

        return $response;
    }
}
