<?php

namespace RZP\Reconciliator;

use App;
use RZP\Trace\TraceCode;

class Messenger
{
    protected $app;

    const ALERT = 'alert';
    const INFO  = 'info';

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
        $this->notifySlack($data, self::ALERT);
        $this->traceReconAlert($data);
    }

    /**
     * Wrapper function to send all reconciliation info
     * @param array $data
     */
    public function raiseReconInfo($data = [])
    {
        $this->notifySlack($data, self::INFO);
        $this->traceReconInfo($data);
    }

    protected function traceReconAlert($data)
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

    protected function traceReconInfo($data)
    {
        // Default trace code.
        $traceCode = TraceCode::RECON_INFO_SUMMARY;

        // Overrides the default trace code.
        if (isset($data['trace_code']) === true)
        {
            $traceCode = $data['trace_code'];
            unset($data['trace_code']);
        }

        $this->app['trace']->info($traceCode, $data);
    }

    protected function notifySlack($data, string $level)
    {
        if (empty($data) === true)
        {
            return;
        }

        $settings = $this->getSlackSettings($level);

        $headline = $this->getSlackHeadline($level);

        $this->app['slack']->queue($headline, $data, $settings);
    }

    protected function getSlackSettings($level)
    {
        $settings['channel']    = $this->app['config']->get('slack.channels.reconciliation2');
        $settings['color']      = $this->getSlackColor($level);

        return $settings;
    }

    // Get slack notification color based on level
    protected function getSlackColor($level)
    {
        switch ($level)
        {
            case self::ALERT:
                return 'danger';
            case self::INFO:
                return 'good';
        }
    }

    // Get slack headline text based on level
    protected function getSlackHeadline($level)
    {
        switch ($level)
        {
            case self::ALERT:
                return 'Reconciliation alert';
            case self::INFO:
                return 'Reconciliation info';
        }
    }
}
