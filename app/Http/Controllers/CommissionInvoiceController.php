<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Base\RuntimeManager;
use RZP\Trace\Tracer;
use RZP\Constants\HyperTrace;

class CommissionInvoiceController extends Controller
{
    public function postCreateInvoices()
    {
        $input = Request::all();

        $data = $this->service()->createInvoiceEntities($input);

        return ApiResponse::json($data);
    }

    public function changeStatus($id)
    {
        $input = Request::all();

        $data = Tracer::inspan(['name' => HyperTrace::COMMISSION_INVOICE_CHANGE_STATUS], function () use ($id, $input) {

            return $this->service()->changeStatus($id, $input);
        });

        return ApiResponse::json($data);
    }

    public function clearOnHoldForInvoiceBulk()
    {
        $input = Request::all();

        $data = Tracer::inspan(['name' => HyperTrace::CLEAR_ON_HOLD_COMMISSION_INVOICE_BULK], function () use ($input) {

            return $this->service()->clearOnHoldForInvoiceBulk($input);
        });

        return ApiResponse::json($data);
    }

    public function fetch($id)
    {
        $input = Request::all();

        $data = Tracer::inspan(['name' => HyperTrace::COMMISSION_INVOICE_FETCH], function () use ($id, $input) {

            return $this->service()->fetch($id, $input);
        });

        return ApiResponse::json($data);
    }

    public function fetchBulk()
    {
        $input = Request::all();

        $data = $this->service()->fetchBulk($input);

        return ApiResponse::json($data);
    }

    public function sendInvoiceReminders()
    {
        RuntimeManager::setTimeLimit(120);

        RuntimeManager::setMaxExecTime(120);

        $data = $this->service()->sendInvoiceReminders();

        return ApiResponse::json($data);
    }

    public function fetchPartnerSubMtusCount()
    {
        $input = Request::all();

        $data = $this->service()->fetchPartnerSubMtusCount($input);

        return ApiResponse::json($data);
    }

    public function fetchPartnersWithCommissionInvoiceFeature()
    {
        $input = Request::all();

        $data = $this->service()->fetchPartnersWithCommissionInvoiceFeature($input);

        return ApiResponse::json($data);
    }
}
