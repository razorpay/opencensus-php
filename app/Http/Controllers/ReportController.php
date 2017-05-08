<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Report;

class ReportController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 'reports_fetch_multiple' : GET /reports
     *
     * @return ApiResponse
     */
    public function getReports()
    {
        $input = Request::all();

        $reports = (new Report\Service)->fetchMultiple($input);

        return ApiResponse::json($reports);
    }
}
