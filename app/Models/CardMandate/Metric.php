<?php

namespace RZP\Models\CardMandate;

use RZP\Trace\TraceCode;
/**
 * List of metrics for CardMandate
 */
final class Metric
{
    const CARD_RECURRING_TOKENISATION_AND_HUB_UPDATES       = 'card_recurring_tokenisation_and_hub_update';
    const CARD_RECURRING_TOKENISATION_AND_HUB_UPDATES_ERROR = 'card_recurring_tokenisation_and_hub_update_error';
    const CARD_MANDATE_CREATE_PDN                           = 'card_mandate_create_pdn';
    const CARD_MANDATE_CREATE_PDN_ERROR                     = 'card_mandate_create_pdn_error';

    public function generateMetric(string $metricName, array $metricDimensions=[])
    {
        $app = \App::getFacadeRoot();

        try {
            $app['trace']->count($metricName, $this->getMetricDimensions($metricDimensions));
        }
        catch (\Exception $ex) {
            $app['trace']->info(TraceCode::CARD_RECURRING_METRIC_PUSH_FAILED,
                ["metric error" => $ex,
                 "metric name" => $metricName]);
        }
    }

    public function getMetricDimensions(array $extra = []): array
    {
        $app = \App::getFacadeRoot();

        return $extra + [
                'mode'              => $app['rzp.mode']
            ];
    }
}

