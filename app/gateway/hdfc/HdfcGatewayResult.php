<?php

namespace Gateway\HdfcGateway;

final class HdfcGatewayResult
{
    /**
     * Result codes received in response for card enrollment
     */
    const ENROLLED = 1;
    const NOT_ENROLLED = 2;
    const FAIL_ENROLLED = 0;
    const FSS0001_ENROLLED = -1;
    const UNKNOWN_ERROR_ENROLLED = -2;

    const FAIL_REFUND = 3;

    /**
     * Result codes received in response for transaction
     * authorization
     */
    const CAPUTRED = 'CAPTURED';
    const APPROVED = 'APPROVED';
    const NOT_CAPTURED = 'NOT CAPTURED';
    const NOT_APPROVED = 'NOT APPROVED';
    const DENIED_BY_RISK = 'DENIED BY RISK';
    const HOST_TIMEOUT = 'HOST TIMEOUT';

    /**
     * Returns whether the enroll response is a success
     * or not.
     * 
     * @param  [type]  $response [description]
     * @return boolean           [description]
     */
    public static function isEnrollSuccess($response)
    {
        Assert($response['type'] === 'enroll');

        if ((isset($response['error']['code'])) or
            (isset($response['data']['eci']) <= 0))
        {
            return false;
        }

    }

    public static function resultCode($result)
    {
    	if ($result === 'ENROLLED') $result = HdfcGatewayResult::ENROLLED;
        else if ($result === 'NOT ENROLLED') $result = HdfcGatewayResult::NOT_ENROLLED;
        else if ($result === 'FSS0001') $result = HdfcGatewayResult::FSS0001;
        else $result = HdfcGatewayResult::UNKNOWN_ERROR_ENROLLED;

        return $result;
    }
}