<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Constants\Entity as E;

class DowntimeController extends Controller
{
    public function getMethodDowntimeData()
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT_DOWNTIME)->getMethodDowntimeDataForMerchant($input);

        return ApiResponse::json($data);
    }

    public function fetchOngoingDowntimes()
    {
        $data = $this->service(E::PAYMENT_DOWNTIME)->fetchOngoingDowntimes();

        return ApiResponse::json($data);
    }

    public function fetchResolvedDowntimes()
    {
        $inputs = Request::all();

        $data = $this->service(E::PAYMENT_DOWNTIME)->fetchResolvedDowntimes($inputs);

        return ApiResponse::json($data);
    }

    public function fetchScheduledDowntimes()
    {
        $data = $this->service(E::PAYMENT_DOWNTIME)->fetchScheduledDowntimes();

        return ApiResponse::json($data);
    }

    public function refreshOngoingDowntimesCache()
    {
        $this->service(E::PAYMENT_DOWNTIME)->refreshOngoingDowntimesCache();

        return ApiResponse::json([]);
    }

    public function refreshScheduledDowntimesCache()
    {
        $this->service(E::PAYMENT_DOWNTIME)->refreshScheduledDowntimesCache();

        return ApiResponse::json([]);
    }

    public function refreshHistoricalDowntimeCache()
    {
        $input = Request::all();

        $lookbackPeriod =0;

        if(isset($input['lookbackPeriod']) === true)
        {
            $lookbackPeriod = $input['lookbackPeriod'];
        }

        $this->service(E::PAYMENT_DOWNTIME)->refreshHistoricalDowntimeCache($lookbackPeriod);

        return ApiResponse::json([]);
    }

    public function getMethodDowntimeDataByID($id)
    {
        $input = Request::all();

        try
        {
            $data = $this->service(E::PAYMENT_DOWNTIME)->getPaymentDowntimeByID($input, $id);
        }
        catch (BadRequestException $e)
        {
            return ApiResponse::json(['Status' => 'Failure Invalid ID'], 400);
        }

        return ApiResponse::json($data);
    }

    /**
     * Used via a cron job to grab applicable payment.downtimes and move them:
     * - scheduled -> started
     * - started   -> resolved
     *
     * @return array Summary of actions
     */
    public function triggerDowntimes($status)
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT_DOWNTIME)->triggerDowntimes($input, $status);

        return ApiResponse::json($data);
    }
}
