<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\Tracer;
use RZP\Constants\HyperTrace;

class CommissionController extends Controller
{
    public function list()
    {
        $input = Request::all();

        $data  = $this->service()->list($input);

        return ApiResponse::json($data);
    }

    public function get(string $id)
    {
        $entity = $this->service()->fetch($id);

        return ApiResponse::json($entity);
    }

    public function capture(string $id)
    {
        $entity = Tracer::inspan(['name' => HyperTrace::COMMISSIONS_CAPTURE], function () use ($id) {

            return $this->service()->capture($id);
        });

        return ApiResponse::json($entity);
    }

    public function captureByPartner(string $partnerId)
    {
        $count = $this->service()->captureByPartner($partnerId);

        $response = ['count' => $count];

        return ApiResponse::json($response);
    }

    public function bulkCaptureByPartner()
    {
        $input = Request::all();

        $count = Tracer::inspan(['name' => HyperTrace::BULK_CAPTURE_BY_PARTNER], function () use ($input) {

            return $this->service()->bulkCaptureByPartner($input);
        });

        $response = ['count' => $count];

        return ApiResponse::json($response);
    }

    public function clearOnHoldForPartner(string $partnerId)
    {
        $input = Request::all();

        $data = Tracer::inspan(['name' => HyperTrace::CLEAR_ON_HOLD_FOR_PARTNER], function () use ($partnerId, $input) {

            return $this->service()->clearOnHoldForPartner($partnerId, $input);
        });

        return ApiResponse::json($data);
    }

    public function fetchAnalytics()
    {
        $input = Request::all();

        $response = $this->service()->fetchAnalytics($input);

        return ApiResponse::json($response);
    }

    public function fetchAggregateCommissionDetails(string $partnerId)
    {
        $input = Request::all();

        $data = $this->service()->fetchAggregateCommissionDetails($partnerId, $input);

        return ApiResponse::json($data);
    }

    public function fetchCommissionConfigsForPayment()
    {
        $input = Request::all();

        $response = $this->service()->fetchCommissionConfigsForPayment($input);

        return ApiResponse::json($response);
    }
}
