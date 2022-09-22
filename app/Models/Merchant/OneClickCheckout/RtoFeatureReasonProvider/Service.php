<?php

namespace RZP\Models\Merchant\OneClickCheckout\RtoFeatureReasonProvider;

class Service {

    protected $app;

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }

    public function getRTOReasons($reasonCodes): ?array
    {
        $rtoReasons = array();

        foreach ($reasonCodes as $code)
        {
            if (array_key_exists($code, Constants::FeatureReasonMap))
            {
                $reason = Constants::FeatureReasonMap[$code];

                if (!in_array($reason, $rtoReasons))
                {
                    $rtoReasons[] = $reason;
                }
            }
        }

        return count($rtoReasons) == 0 ? null : $rtoReasons;
    }
}
