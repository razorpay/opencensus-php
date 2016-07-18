<?php

namespace RZP\Reconciliator;

use App;
use RZP\Trace\TraceCode;

class Messenger
{
    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    /**
     * Wrapper function to send all critical reconciliation alerts to slack channels
     * and trace in Splunk.
     *
     * @param array $data
     */
    public function raiseReconAlert($data = [])
    {
        $this->notifySlack($data);
        $this->traceReconAlert($data);
    }

    public function traceReconAlert($data)
    {
        // Default trace code if no trace code is present in data.
        $traceCode = TraceCode::RECON_CRITICAL_ALERT;

        // Overrides the default trace code.
        if (isset($data['trace_code']) === true)
        {
            $traceCode = $data['trace_code'];
            unset($data['trace_code']);
        }

        $this->app['trace']->error($traceCode, $data);
    }

    public function notifySlack($data)
    {
        if (empty($data) === true)
        {
            return;
        }

        $settings = $this->getSlackSettings();

        $headline = 'Reconciliation alert';

        $this->app['slack']->queue($headline, $data, $settings);
    }

    public function getSlackSettings()
    {
        $settings['channel'] = $this->app['config']->get('slack.channels.reconciliation');
        $settings['color'] = 'danger';

        return $settings;
    }
}
