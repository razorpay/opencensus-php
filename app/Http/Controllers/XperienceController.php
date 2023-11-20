<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use Redirect;
use ApiResponse;
use RZP\Services\Xperience;
use RZP\Trace\TraceCode;
use RZP\Constants\Mode;

class XperienceController extends Controller
{

    protected $app;

    /** @var Xperience $xperience  */
    protected $xperience;

    public function __construct()
    {
        parent::__construct();

        $this->xperience = $this->app['xperience'];
    }

    public function getBulkPayoutById(string $bulkPayoutId)
    {
        $response = $this->xperience->getBulkPayoutById($bulkPayoutId);

        return ApiResponse::json($response);
    }

    public function getBulkPayouts()
    {
        $input = Request::all();

        $response = $this->xperience->getBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function ownerBulkRejectBulkPayouts()
    {
        $input = Request::all();

        $response = $this->xperience->ownerBulkRejectBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function getPendingBulkPayouts()
    {
        $input = Request::all();

        $response = $this->xperience->getPendingBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function getBulkPayoutsMetaSummary()
    {
        $input = Request::all();

        $response = $this->xperience->getBulkPayoutsMetaSummary($input);

        return ApiResponse::json($response);
    }

    public function approveBulkPayouts()
    {
        $input = Request::all();

        $response = $this->xperience->approveBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function createBulkPayout()
    {
        $input = Request::all();

        $response = $this->xperience->createBulkPayout($input);

        return ApiResponse::json($response);
    }

    public function getBulkPayoutRows(string $bulkPayoutId)
    {
        $input = Request::all();

        $response = $this->xperience->getBulkPayoutRows($bulkPayoutId, $input);

        return ApiResponse::json($response);
    }

    public function processBulkPayout(string $bulkPayoutId)
    {
        $input = Request::all();

        $response = $this->xperience->processBulkPayout($bulkPayoutId, $input);

        return ApiResponse::json($response);
    }

    public function rejectBulkPayouts()
    {
        $input = Request::all();

        $response = $this->xperience->rejectBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function workflowSummary()
    {
        $response = $this->xperience->workflowSummary();

        return ApiResponse::json($response);
    }

    public function migrateBulkPayouts()
    {
        $input = Request::all();

        $response = $this->xperience->migrateBulkPayouts($input);

        return ApiResponse::json($response);
    }

    public function createCostCenters()
    {
        $input = Request::all();

        $response = $this->xperience->createCostCenters($input);

        return ApiResponse::json($response);
    }

    public function getCostCenters()
    {
        $input = Request::all();

        $response = $this->xperience->getCostCenters($input);

        return ApiResponse::json($response);
    }

    public function getCostCenter(string $costCenterId)
    {

        $response = $this->xperience->getCostCenter($costCenterId);

        return ApiResponse::json($response);
    }

    public function updateCostCenter(string $costCenterId, array $input)
    {
        $input = Request::all();

        $response = $this->xperience->updateCostCenter($costCenterId, $input);

        return ApiResponse::json($response);
    }

    public function deleteCostCenter(string $costCenterId)
    {

        $response = $this->xperience->deleteCostCenter($costCenterId);

        return ApiResponse::json($response);
    }

    public function disableCostCenter(string $costCenterId)
    {

        $response = $this->xperience->disableCostCenter($costCenterId);

        return ApiResponse::json($response);
    }
}
