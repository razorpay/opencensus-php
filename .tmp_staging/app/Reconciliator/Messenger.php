<?php

namespace RZP\Reconciliator;

use App;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base\InfoCode;

class Messenger
{
    protected $app;

    public $batch;

    protected $skipSlack = false;

    const ALERT           = 'alert';
    const INFO            = 'info';
    const WARN            = 'warn';
    const TRACE_CODE      = 'trace_code';
    const INFO_CODE       = 'info_code';
    const EXPECTED_AMOUNT = 'expected_amount';
    const RECON_AMOUNT    = 'recon_amount';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    public function setSkipSlack(bool $skipSlack)
    {
        $this->skipSlack = $skipSlack;

        return $this;
    }

    /**
     * Wrapper function to send all critical reconciliation alerts to slack channels
     * and trace in Splunk.
     *
     * @param array $data
     */
    public function raiseReconAlert($data = [])
    {
        //
        // For recon summary alerts, batch_id will already be present. hence not replacing here.
        //
        if (empty($data['batch_id']) === true)
        {
            $data['batch_id'] = (empty($this->batch) === false) ? $this->batch->getId() : null;
        }

        if ($this->shouldSkipSlack($data) === true)
        {
            $this->setSkipSlack(true);
        }

        $this->notifySlack($data, self::ALERT);
        $this->traceReconAlert($data);
    }

    /**
     * Since, all reconciliation slack alerts don't require action from backend team,
     * so, adding conditions here to check if some of the alerts should be skipped.
     * @param array $data
     * @return bool
     */
    public function shouldSkipSlack($data = [])
    {
        $flag = false;

        if ((isset($data[self::TRACE_CODE]) === true)                 and
            ($data[self::TRACE_CODE] === TraceCode::RECON_INFO_ALERT) and
            (isset($data[self::INFO_CODE]) === true))
        {
            switch ($data[self::INFO_CODE])
            {
                case InfoCode::AMOUNT_MISMATCH:

                    if ((isset($data[self::EXPECTED_AMOUNT]) === true) and
                        (isset($data[self::RECON_AMOUNT]) === true))
                    {
                        $amountDiff = abs($data[self::EXPECTED_AMOUNT] - $data[self::RECON_AMOUNT]);

                        if ($amountDiff < Base\Foundation\SubReconciliate::THRESHOLD[$data[self::INFO_CODE]])
                        {
                            $flag = true;
                        }
                    }
            }
        }

        return $flag;
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

    public function raiseReconWarn($data)
    {
        $this->notifySlack($data, self::WARN);
        $this->traceReconAlert($data);
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

        if ($this->skipSlack === true)
        {
            return;
        }

        $settings = $this->getSlackSettings($level);

        $headline = $this->getSlackHeadline($level);

        if (isset($data['headLine']) === true)
        {
            $headline = $data['headLine'];

            unset($data['headLine']);
        }

        $this->app['slack']->queue($headline, $data, $settings);
    }

    protected function getSlackSettings($level)
    {
        $slackChannel = $this->getSlackChannel($level);

        $settings['channel']    = $this->app['config']->get($slackChannel);
        $settings['color']      = $this->getSlackColor($level);

        return $settings;
    }

    /**
     * Get slack notification color based on level
     *
     * @param $level
     * @return string
     */
    protected function getSlackColor($level)
    {
        switch ($level)
        {
            case self::ALERT:
                return 'danger';
            case self::INFO:
                return 'good';
            case self::WARN:
                return 'danger';

            default:
                return 'danger';
        }
    }

    /**
     * Get slack headline text based on level
     *
     * @param $level
     * @return string
     */
    protected function getSlackHeadline($level)
    {
        switch ($level)
        {
            case self::ALERT:
                return 'Reconciliation alert';
            case self::INFO:
                return 'Reconciliation info';
            case self::WARN:
                return 'Reconciliation alert';

            default:
                return 'Reconciliation alert';
        }
    }

    /**
     * Get slack channel based on level
     *
     * @param $level
     * @return string
     */
    protected function getSlackChannel($level)
    {
        switch ($level)
        {
            case self::ALERT:
                return 'slack.channels.reconciliation2';
            case self::INFO:
                return 'slack.channels.reconciliation_info';
            case self::WARN:
                return 'slack.channels.recon_alerts';

            default:
                return 'slack.channels.reconciliation2';
        }
    }
}
