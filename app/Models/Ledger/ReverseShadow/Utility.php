<?php

namespace RZP\Models\Ledger\ReverseShadow;

use App;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Core as MerchantCore;

class Utility
{
    protected $trace;

    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }

    public function isBalanceReadFromClsExperimentEnabled( $merchant, bool $isAdmin = false):bool
    {
        if ($isAdmin === true)
        {
            return true;
        }

        $pgLedgerReverseShadow = $merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW);

        if ($pgLedgerReverseShadow === false) {
            return false;
        }

        return true;
    }

}
