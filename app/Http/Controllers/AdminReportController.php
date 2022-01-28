<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Admin;
use RZP\Constants\Entity as E;

class AdminReportController extends Controller
{
// ------------------------------ Admin Dashboard Reports ----------------------------------------

    /*
     * Get filters list by report type
     */
    public function adminReportsFiltersGetByType($type)
    {
        $input = Request::all();

        $input['type'] = $type;

        $response = $this->service(E::ADMIN_REPORT)->adminReportsFiltersGetByType($input);

        return ApiResponse::json($response);
    }

    /*
     * Get report data for UI by report type + filter params
     */
    public function adminReportsGetReportData($type)
    {
        $input = Request::all();

        $input['type'] = $type;

        $response = $this->service(E::ADMIN_REPORT)->adminReportsGetReportData($input);

        return ApiResponse::json($response);
    }

    /*
     * Initiate generation of downloadable report file by report type + filter params
     */
    public function adminReportsDownloadReportsByType($type)
    {
        $input = Request::all();

        $input['type'] = $type;

        $response = $this->service(E::ADMIN_REPORT)->adminReportsGetReportsByType($input);

        return ApiResponse::json($response);
    }

    /*
     * get list of available reports for an admin
     */
    public function adminReportsGetReportsForAdmin()
    {
        $input = Request::all();

        $response = $this->service(E::ADMIN_REPORT)->adminReportsGetReportsForAdmin($input);

        return ApiResponse::json($response);
    }

    /*
     * Initiate download of a report file
     */
    public function adminReportsDownloadReportById()
    {
        $input = Request::all();

        $response = $this->service(E::ADMIN_REPORT)->adminReportsGetReportById($input);

        return ApiResponse::json($response);
    }

// ---------------------------- END Admin Dashboard Reports ----------------------------------------

}
