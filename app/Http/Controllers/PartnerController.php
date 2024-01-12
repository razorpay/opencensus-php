<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Partner;

class PartnerController extends Controller
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

    public function migrateResellerToPurePlatformPartner()
    {
        $input = Request::all();

        $response = $this->service()->migrateResellerToPurePlatformPartner($input);

        return ApiResponse::json($response);
    }

    public function migratePurePlatformToResellerPartner()
    {
        $input = Request::all();

        $response = $this->service()->migratePurePlatformToResellerPartner($input);

        return ApiResponse::json($response);
    }

    public function getPartnerSalesPOC()
    {
        $input = Request::all();

        $response = $this->service()->getPartnerSalesPOC();

        return ApiResponse::json($response);
    }

    public function raisePartnerMigrationRequest()
    {
        $input = Request::all();

        $response = $this->service()->raisePartnerMigrationRequest($input);

        return ApiResponse::json($response);
    }

    public function fetchPartnerRelatedEntitiesForPRTS()
    {
        $input = Request::all();

        $response = $this->service()->fetchPartnerRelatedEntitiesForPRTS($input);

        return ApiResponse::json($response);
    }

    public function updateNcOptOutForPartner()
    {
        $input = Request::all();

        $response = $this->service()->updateNcOptOutForPartner($input);

        return ApiResponse::json($response);

    }

    public function getOauthApplicationDetails()
    {
        $input = Request::all();

        $response = $this->service()->getApplicationsDetailsForPayment($input);

        return ApiResponse::json($response);
    }

    public function fetchEarningsSectionStatusForPartner()
    {
        $input = Request::all();

        $response = $this->service()->fetchEarningsSectionStatusForPartner($input);

        return ApiResponse::json($response);
    }

    public function createPOSDeviceConfig(string $id)
    {
        $input = Request::all();

        $response = $this->service()->createPOSDeviceConfig($id, $input);

        return ApiResponse::json($response);
    }

    public function updatePOSDeviceConfig(string $id)
    {
        $input = Request::all();

        $response = $this->service()->updatePOSDeviceConfig($id, $input);

        return ApiResponse::json($response);
    }
}
