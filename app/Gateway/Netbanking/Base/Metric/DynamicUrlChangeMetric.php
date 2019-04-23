<?php

namespace RZP\Gateway\Netbanking\Base\Metric;

class DynamicUrlChangeMetric
{
    // This tells which statsd server we are pushing data to. Here its gateway statsd server.
    const DOGSTATSD_DRIVER       = 'dogstatsd_gateway';

    // This is the metric name in which we are pushing the data
    const NETBANKING_DYNAMIC_URL = 'dynamic_netbanking_url';

    // Following are the dimensions that we push in above given metric.
    const BANK_ID                = 'bank_id';

    const BANK_OLD_URL           = 'bank_old_url';

    const BANK_NEW_URL           = 'bank_new_url';

    const GATEWAY                = 'gateway';

    public function getDimensions($input, $gateway, $oldUrl, $newUrl)
    {
        $bank = $this->getBank($input);

        $changed = $oldUrl != $newUrl;

        $dimensions = [
            self::BANK_ID       => $bank,
            self::BANK_OLD_URL  => $oldUrl,
            self::BANK_NEW_URL  => $newUrl,
            self::GATEWAY       => $gateway,
        ];

        return $dimensions;
    }

    protected function getBank($input)
    {
        if (isset($input['payment']['bank']))
        {
            return $input['payment']['bank'];
        }

        return '';
    }

    public function pushDimensions($input, $gateway, $oldUrl, $newUrl)
    {
        $dimensions = $this->getDimensions($input, $gateway, $oldUrl, $newUrl);

//        $dynamicUrlMetric = app('trace')->metricsDriver(self::DOGSTATSD_DRIVER);

        app('trace')->count(self::NETBANKING_DYNAMIC_URL, $dimensions);
    }
}