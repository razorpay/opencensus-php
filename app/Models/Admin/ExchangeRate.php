<?php

namespace RZP\Models\Admin;

use RZP\Exception;
use RZP\Models\Base;

class ExchangeRate extends Base\Core
{
    protected $exchange;

    const EXCHANGE_RATE_KEY = 'exchange_rates_';

    public function __construct()
    {
        parent::__construct();

        $this->exchange = $this->app['exchange'];

        $this->redis = $this->app['redis'];
    }

    public function updateRates($currency)
    {
        $rates = $this->exchange->latest($currency);

        $key = self::EXCHANGE_RATE_KEY . $currency;

        $this->redis->set($key, $rates);

        return ['success' => true];
    }
}
