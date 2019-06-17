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
