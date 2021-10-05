<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Partner;

class PartnerActivationController extends Controller
{
    protected $service = Partner\Service::class;

    public function savePartnerActivationDetails()
    {
        $input = Request::all();

        $response = $this->service()->savePartnerDetailsForActivation($input);

        return ApiResponse::json($response);
    }

    public function getPartnerActivationDetails()
    {
        $response = $this->service()->getPartnerActivationDetails();

        return ApiResponse::json($response);
    }

    public function updatePartnerActivationStatus(string $id)
    {
        $input = Request::all();

        $response = $this->service()->updatePartnerActivationStatus($id, $input);

        return ApiResponse::json($response);
    }

    public function editPartnerActivationDetails($id)
    {
        $input = Request::all();

        $response = $this->service()->editPartnerActivationDetails($id, $input);

        return ApiResponse::json($response);
    }

    public function performAction($id)
    {
        $input = Request::all();

        $response = $this->service()->performAction($id, $input);

        return ApiResponse::json($response);
    }

    public function bulkAssignReviewer()
    {
        $input = Request::all();

        $response = $this->service()->bulkAssignReviewer($input);

        return ApiResponse::json($response);
    }
}
