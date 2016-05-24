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
     * Wrapper function to send all reconciliation alerts to slack channels.
     * @param bool $critical Critical alerts are sent to different Slack channel.
     */
    public function raiseReconAlert($data = [], $critical = false)
    {
        $this->notifySlack($data, $critical);
        $this->traceReconAlert($data);
    }


    public function traceReconAlert($data)
    {
        $traceCode = TraceCode::RECONCILIATION_ALERT;

        if (isset($data['trace_code']) === true)
        {
            $traceCode = $data['trace_code'];
        }

        $this->app['trace']->info($traceCode, $data);
    }


    public function notifySlack($data, $critical)
    {
        if (empty($data) === true)
        {
            return;
        }

        $settings = $this->getSlackSettings($critical);

        $headline = 'Reconciliation alert';
        if (isset($data['message']) === true)
        {
            $headline = $data['message'];
            unset($data['message']);
        }

        $this->slackPost($headline, $data, $settings);
    }


    public function getSlackSettings($critical)
    {
        $settings['channel'] = $this->app['config']->get('slack.channels.recon_info');
        $settings['color'] = 'good';

        if ($critical === true)
        {
            $settings['channel'] = $this->app['config']->get('slack.channels.recon_info');
            $settings['color'] = 'danger';
        }

        return $settings;
    }
}