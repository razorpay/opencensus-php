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
            $requestId = $this->app['request']->getTaskId();

            $merchantId = $requestId;

            return $this->checkSplitzExperimentStatus($merchantId);
        }

        $pgLedgerReverseShadow = $merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW);

        if ($pgLedgerReverseShadow === false) {
            return false;
        }

        return $this->checkSplitzExperimentStatus($merchant->getMerchantId());
    }

    public function checkSplitzExperimentStatus(string $merchantId) : bool
    {
        $properties = [
            "id" => $merchantId,
            "experiment_id" => $this->app['config']->get('app.splitz_merchant_cls_balance_read_experiment_id'),
        ];

        $variant =  (new MerchantCore())->isSplitzExperimentEnable($properties, 'Enable');

        return $variant;
    }

}
