<?php

namespace RZP\Services\PayoutService;

use Requests;
use Razorpay\Edge\Passport\Passport;

class Fetch extends Base
{
    const FETCH_PAYOUT_URI = '/payouts';

    // payout fetch service name for singleton class
    const PAYOUT_SERVICE_FETCH = 'payout_service_fetch';

    const ARRAY_ENCODING_REGEX_PATTERN_TO_BE_REPLACED = '/%5B[0-9]+%5D/simU';

    const ARRAY_ENCODING_REGEX_PATTERN_REPLACEMENT = '%5B%5D';

    const FETCH_BY_ID_PAYOUT_ID = 'fetch_by_id_payout_id';

    public function fetch(string $entity, string $id, array $input)
    {
        $input += [self::FETCH_BY_ID_PAYOUT_ID => $id];

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

        $headers = $this->getHeadersWithJwt();

        $response = $this->makeRequestAndGetContent(
            $input,
            $url,
            Requests::GET,
            $headers
        );

        return $response;
    }

    protected function modifyUriAndContentIfApplicable(array &$input, &$url)
    {
        if ((isset($input[self::FETCH_BY_ID_PAYOUT_ID]) === true) and
            (empty($input[self::FETCH_BY_ID_PAYOUT_ID]) === false))
        {
            $url = $url . '/' . $input[self::FETCH_BY_ID_PAYOUT_ID];

            unset($input[self::FETCH_BY_ID_PAYOUT_ID]);
        }

        $query = $this->buildQueryFromInput($input);

        if (empty($query) === false)
        {
            $url = $url . '?' . $query;

        }

        $input = [];
    }

    public function buildQueryFromInput(array $input) : string
    {
        $query = http_build_query($input);

        /*
         * Doing this because http_build_query replaces arrays with encoding like arr[0]={v0}&arr[1]={v1} whereas in
         * service we expect the encoding to be like arr[]={v0}&arr[]={v1}. This is done for expand params which we send
         * as an array.
         */
        $query = preg_replace(self::ARRAY_ENCODING_REGEX_PATTERN_TO_BE_REPLACED,
                              self::ARRAY_ENCODING_REGEX_PATTERN_REPLACEMENT,
                              $query);

        return $query;
    }
}
