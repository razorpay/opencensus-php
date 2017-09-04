<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class ReportController extends Controller
{
    /**
     * 'reports_fetch_multiple' : GET /reports
     *
     * @return ApiResponse
     */
    public function getReports()
    {
        $input = Request::all();

        $reports = $this->service()->fetchMultiple($input);

        return ApiResponse::json($reports);
    }

    /**
     * POST /reports/{entity}/generate
     *
     */
    public function generateReport(string $entity)
    {
        $input = Request::all();

        $this->service()->generateReport($input, $entity);

        return ApiResponse::json([]);
    }
}
