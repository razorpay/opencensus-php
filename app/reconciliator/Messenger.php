<?php

namespace Reconciliator;


use App;
use Trace\TraceCode;
use Services\SlackPoster;


class Messenger
{
    use SlackPoster;

    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    /**
     * Wrapper function to send all reconciliation alerts to slack channels
     * and trace in Splunk.
     *
     * @param bool $critical Critical alerts are sent to different Slack channel.
     * @param array $data
     */
    public function raiseReconAlert($data = [], $critical = false)
    {
        $this->notifySlack($data, $critical);
        $this->traceReconAlert($data, $critical);
    }


    public function traceReconAlert($data, $critical)
    {
        // Default trace code if no trace code is present in data.
        $traceCode = TraceCode::RECONCILIATION_INFO_ALERT;
        if ($critical === true)
        {
            $traceCode = TraceCode::RECONCILIATION_CRITICAL_ALERT;
        }

        // Overrides the default trace code.
        if (isset($data['trace_code']) === true)
        {
            $traceCode = $data['trace_code'];
            unset($data['trace_code']);
        }

        // Log as error if critical, otherwise as info.
        if ($critical === true)
        {
            $this->app['trace']->error($traceCode, $data);
        }
        else
        {
            $this->app['trace']->info($traceCode, $data);
        }
    }


    public function notifySlack($data, $critical)
    {
        if (empty($data) === true)
        {
            return;
        }

        $settings = $this->getSlackSettings($critical);

        $headline = 'Reconciliation alert';

        $this->slackPost($headline, $data, $settings);
    }


    public function getSlackSettings($critical)
    {
        $settings['channel'] = $this->app['config']->get('slack.channels.recon_info');
        $settings['color'] = 'good';

        if ($critical === true)
        {
            $settings['channel'] = $this->app['config']->get('slack.channels.recon_critical');
            $settings['color'] = 'danger';
        }

        return $settings;
    }
}