<?php

namespace RZP\Services\PayoutService;

use Requests;
use Razorpay\Edge\Passport\Passport;


class AdminFetch extends Base
{
    const ADMIN_FETCH_PAYOUTS_BASE_URI = '/admin';

    const ADMIN_FETCH_PAYOUTS_URI = self::ADMIN_FETCH_PAYOUTS_BASE_URI . '/payouts';

    const ADMIN_FETCH_REVERSALS_URI = self::ADMIN_FETCH_PAYOUTS_BASE_URI . '/reversals';

    const ADMIN_FETCH_PAYOUT_LOGS_URI = self::ADMIN_FETCH_PAYOUTS_BASE_URI . '/payout_logs';

    const ADMIN_FETCH_PAYOUT_SOURCES_URI = self::ADMIN_FETCH_PAYOUTS_BASE_URI . '/payout_sources';

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
        $urlConstantName = 'ADMIN_FETCH_' . strtoupper($entity) . "_URI";

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
        else
        {
            $query = http_build_query($input);

            $url = $url . '?' . $query;

            $input = [];
        }
    }
}
