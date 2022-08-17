<?php

namespace RZP\Services\PayoutService;

use Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Http\RequestHeader;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\BadRequestException;

class Get extends Base
{
    const GET_PAYOUT_BY_ID_SERVICE_URI = '/payouts/';

    const GET_PAYOUT_ANALYTICS_SERVICE_URI = '/payouts/analytics';

    const ADMIN_GET_FREE_PAYOUT_PAYOUTS_SERVICE_URI = '/admin/payouts/';

    const X_DASHBOARD_GET_FREE_PAYOUT_PAYOUTS_SERVICE_URI = '/payouts/free_payout/';

    // payout get service name for singleton class
    const PAYOUT_SERVICE_GET = 'payout_service_get';

    /**
     * @param array $input
     * @param string $merchantId
     * @return array
     */
    public function GetPayoutByIdViaMicroservice(string $id, string $merchantId)
    {
        $this->trace->info(TraceCode::PAYOUT_GET_REQUEST_FROM_MICROSERVICE,
            [
                'id' => $id,
                'merchant_id' => $merchantId
            ]);

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        $response = $this->makeRequestAndGetContent([],
            self::GET_PAYOUT_BY_ID_SERVICE_URI . $id,
            Requests::GET,
            $headers
        );

        return $response;
    }

    /**
     * @param array $input
     * @param string $merchantId
     * @return array
     */
    public function GetPayoutsAnalyticsViaMicroservice(string $merchantId)
    {
        $this->trace->info(TraceCode::PAYOUT_GET_REQUEST_FROM_MICROSERVICE,
            [
                'merchant_id' => $merchantId
            ]);

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        $response = $this->makeRequestAndGetContent([],
            self::GET_PAYOUT_ANALYTICS_SERVICE_URI,
            Requests::GET,
            $headers
        );

        return $response;
    }

    /**
     * @param string $balanceId
     *
     */
    public function getFreePayoutAttributesViaMicroservice(string $balanceId)
    {
        $uri = $this->getFreePayoutAttributesViaMicroserviceURI($balanceId);

        $this->trace->info(TraceCode::GET_FREE_PAYOUT_VIA_MICROSERVICE_REQUEST,
                           [
                               'id'  => $balanceId,
                               'uri' => $uri
                           ]);

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        $response = $this->makeRequestAndGetContent(
            [],
            $uri,
            Requests::GET,
            $headers
        );

        $this->trace->info(TraceCode::GET_FREE_PAYOUT_VIA_MICROSERVICE_RESPONSE,
                           [
                               'payouts_service_response' => $response,
                           ]);

        return $response;
    }

    protected function getFreePayoutAttributesViaMicroserviceURI(string $balanceId)
    {
        if ($this->auth->isAdminAuth() === true)
        {
            return self::ADMIN_GET_FREE_PAYOUT_PAYOUTS_SERVICE_URI . $balanceId . '/free_payout';
        }
        else if ($this->auth->isProxyAuth() === true)
        {
            return self::X_DASHBOARD_GET_FREE_PAYOUT_PAYOUTS_SERVICE_URI . $balanceId;
        }
        else
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_AUTH_TYPE);
        }
    }
}
