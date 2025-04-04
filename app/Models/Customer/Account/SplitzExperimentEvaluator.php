<?php

namespace RZP\Models\Customer\Account;

use RZP\Trace\TraceCode;
use RZP\Models\Base;

class SplitzExperimentEvaluator extends Base\Core
{
    public function isCreateOverrideToCmsEnabled($merchant): bool
    {
        // Temporary hack to use existing flow in test cases.
        // This will be fixed in subsequent PR
        if ($this->app->runningUnitTests())
            return false;

        $experimentId = $this->mode == "test" ? "app.cms_create_override_test_experiment_id" : "app.cms_create_override_live_experiment_id";
        return $this->isOverrideToCmsEnabled($experimentId, $merchant);
    }

    public function isReadOverrideToCmsEnabled($merchant): bool
    {
        // Temporary hack to use existing flow in test cases.
        // This will be fixed in subsequent PR
        if ($this->app->runningUnitTests())
            return false;

        $experimentId = $this->mode == "test" ? "app.cms_read_override_test_experiment_id" : "app.cms_read_override_live_experiment_id";
        return $this->isOverrideToCmsEnabled($experimentId, $merchant);
    }

    protected function isOverrideToCmsEnabled($experimentId, $merchant): bool
    {
        $properties = [
            'id'            => $merchant ? $merchant -> getId() : 'unknown',
            'experiment_id' => $this->app['config']->get($experimentId),
            'request_data'  => json_encode([
                'merchantId' => $merchant ? $merchant -> getId() : 'unknown' ,
                'internal_app_name' => app('request.ctx')->getInternalAppName() ?? 'unknown',
                'mode' => $this->mode,
                'route_name'  => $this->app['api.route']->getCurrentRouteName() ?? 'unknown',
                'country' => $merchant ? $merchant -> getCountry() : 'unknown'])
        ];
        try
        {
            $response = $this->app['splitzService']->evaluateRequest($properties);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::CMS_REQUEST_SPLITZ_ERROR);
        }

        $variant = $response['response']['variant']['name'] ?? 'enabled';

        return  $variant == "enabled";
    }
}
