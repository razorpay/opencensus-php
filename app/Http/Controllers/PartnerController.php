<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Partner;

class PartnerController extends Controller
{
    protected $service = Partner\Service::class;

    /**
     * API to migrate referred application based sub-merchants to managed application
     */
    public function migrateReferredSubMToManagedSubM() {
        $input = Request::all();

        $data = $this->service()->migrateReferredSubMToManagedSubM($input);

        return ApiResponse::json($data);
    }

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

    public function sendEventsOfPartnersWithCommissionPending()
    {
        $data = $this->service()->sendEventsOfPartnersWithPendingCommissionAndIncompleteKYC();

        return ApiResponse::json($data);
    }

    public function sendPartnerWeeklyActivationSummaryEmails()
    {
        $input = Request::all();

        $response = $this->service()->sendPartnerWeeklyActivationSummaryEmails($input);

        return ApiResponse::json($response);
    }


    public function sendSubmerchantFirstTransactionSegmentEvents()
    {
        $input = Request::all();

        $response = $this->service()->sendSubmerchantFirstTransactionSegmentEvents($input);

        return ApiResponse::json($response);
    }

    public function bulkMigrateResellerToAggregatorPartner()
    {
        $input = Request::all();

        $this->service()->bulkMigrateResellerToAggregatorPartner($input);

        return ApiResponse::json([]);
    }

    public function migrateResellerToAggregatorPartner()
    {
        $input = Request::all();

        $response = $this->service()->migrateResellerToAggregatorPartner($input);

        return ApiResponse::json([$response]);
    }
}
