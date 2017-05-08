<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Report;

class ReportController extends Controller
{
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = new Report\Service;
    }

    /**
     * 'reports_fetch_multiple' : GET /reports
     */
    public function getReports()
    {
        $input = Request::all();

        $reports = $this->service->fetchMultiple($input);

        return ApiResponse::json($reports);
    }
}
