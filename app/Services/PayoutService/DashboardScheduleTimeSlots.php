<?php

namespace RZP\Services\PayoutService;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

class DashboardScheduleTimeSlots extends Base
{
    const DASHBOARD_SCHEDULE_TIME_SLOTS_PAYOUT_SERVICE_URI = '/payouts/schedule/timeslots';

    const PAYOUT_SERVICE_DASHBOARD_TIME_SLOTS = 'payout_service_dashboard_time_slots';

    /**
     * @return array
     *
     */
    public function getScheduleTimeSlotsViaMicroservice()
    {
        $this->trace->info(TraceCode::DASHBOARD_SCHEDULE_TIME_SLOTS_VIA_MICROSERVICE_REQUEST);

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        $response = $this->makeRequestAndGetContent(
            [],
            self::DASHBOARD_SCHEDULE_TIME_SLOTS_PAYOUT_SERVICE_URI,
            Requests::GET,
            $headers
        );

        $this->trace->info(
            TraceCode::DASHBOARD_SCHEDULE_TIME_SLOTS_VIA_MICROSERVICE_RESPONSE,
            [
                'payouts service response' => $response,
            ]);

        return $response;
    }
}


