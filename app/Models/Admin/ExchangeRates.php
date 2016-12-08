<?php

namespace RZP\Models\Admin;

use RZP\Exception;
use RZP\Models\Base;

class ExchangeRates extends Base\Core
{
    protected $exchange;

    const EXCHANGE_RATES_KEY = 'exchange_rates_';

    public function __construct()
    {
        $this->exchange = $this->app['exchange'];

        $this->redis = $this->app['redis'];
    }

    public function updateRates($currency)
    {
        $rates = $this->exchange->latest($currency);

        $key = self::EXCHANGE_RATES_KEY . $currency;

        $this->redis->set($key, $rates);

        return ['success' => true];
    }
}
