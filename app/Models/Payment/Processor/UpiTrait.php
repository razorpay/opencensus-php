<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Payment\Flow;
use function GuzzleHttp\Psr7\uri_for;

trait UpiTrait
{
    public function getUpiFlow($input)
    {
        return $input['upi']['flow'] ?? null;
    }

    public function isFlowCollect($input): bool
    {
        if ((isset($input['upi']['flow']) === false) or
            ($input['upi']['flow'] === Flow::COLLECT))
        {
            return true;
        }

        return false;
    }

    public function isFlowIntent($input): bool
    {
        if ((isset($input['upi']['flow']) === true) and
            ($input['upi']['flow'] === Flow::INTENT))
        {
            return true;
        }

        return false;
    }

    public function getUpiExpiryTime($input)
    {
        return $input['upi']['expiry_time'] ?? null;
    }
}
