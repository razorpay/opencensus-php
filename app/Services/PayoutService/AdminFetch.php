<?php

namespace RZP\Services\PayoutService;

use Requests;
use Razorpay\Edge\Passport\Passport;


class AdminFetch extends Base
{
    const FETCH_PAYOUTS_BASE_URI = '/admin';

    const FETCH_PAYOUTS_URI = self::FETCH_PAYOUTS_BASE_URI . '/payouts';

    const FETCH_REVERSALS_URI = self::FETCH_PAYOUTS_BASE_URI . '/reversals';

    const FETCH_PAYOUT_LOGS_URI = self::FETCH_PAYOUTS_BASE_URI . '/payout_logs';

    const FETCH_PAYOUT_SOURCES_URI = self::FETCH_PAYOUTS_BASE_URI . '/payout_sources';

    // payout create service name for singleton class
    const PAYOUT_SERVICE_ADMIN_FETCH = 'payout_service_admin_fetch';

    public function fetch(string $entity, string $id, array $input)
    {
        $input += [ 'id' => $id ];

        return $this->getEntity($entity, $input);
    }

    public function fetchMultiple(string $entity, array $input)
    {
        return $this->getEntity($entity, $input);
    }

    protected function getEntity(string $entity, array $input)
    {
        $urlConstantName = 'FETCH_' . strtoupper($entity) . "_URI";

        $urlConstant = __CLASS__ . '::' . strtoupper($urlConstantName);

        if (defined($urlConstant) === true)
        {
            return $this->modifyAndSendRequestAndGetContent($input, constant($urlConstant));
        }

        return [];
    }

    protected function modifyAndSendRequestAndGetContent(array $input, $url)
    {
        $this->modifyUriAndContentIfApplicable($input, $url);

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        $response = $this->makeRequestAndGetContent(
            $input,
            $url,
            Requests::POST,
            $headers
        );

        return $response;
    }

    protected function modifyUriAndContentIfApplicable(array & $input, & $url)
    {
        if ((isset($input['id']) === true) and
             (empty($input['id']) === false))
        {
            $url = $url . '/' . $input['id'];

            unset($input['id']);
        }
    }
}
