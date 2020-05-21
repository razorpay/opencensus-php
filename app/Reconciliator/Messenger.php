<?php

namespace RZP\Reconciliator;

use App;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\RequestProcessor\Base as RPBase;

class Messenger
{
    protected $app;

    public $batch;

    /**
     * For recon request coming from batch service,
     * we do not have batch object,  so we will use
     * this $batchId variable in traces.
     */
    public $batchId;

    protected $skipSlack = false;

    const TXN_ALERT       = 'txn_alert';
    const FILE_ALERT      = 'file_alert';
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
            $data['batch_id'] = (empty($this->batch) === false) ? $this->batch->getId() : $this->batchId;
        }

        if ($this->shouldSkipSlack($data) === true)
        {
            $this->setSkipSlack(true);
        }

        $alertLevel = $this->getAlertLevelBasedOnTraceCode($data);

        $this->notifySlack($data, $alertLevel);
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

        if ((isset($data[self::TRACE_CODE]) === true)                      and
            (($data[self::TRACE_CODE] === TraceCode::RECON_INFO_ALERT)     or
            ($data[self::TRACE_CODE] === TraceCode::RECON_CRITICAL_ALERT)) and
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

                    // Temporarily disabling slack alert for Olamoney as
                    // we are getting too many alerts. Will enable once
                    // the issue is fixed.
                    if ($data[RPBase::GATEWAY] === RPBase::OLAMONEY)
                    {
                        $flag = true;
                    }
                    break;

                case InfoCode::MIS_FILE_PAYMENT_FAILED:
                    //Disabling slack alert for VirtualAccYesBank,
                    //as trans_status column now contains "pending credit"
                    //for some rows, which get marked as recon failure.
                    //Have been getting many alerts of this sort, so
                    //scheduling mail for this, and removing alerts.
                    if ((isset($data[RPBase::GATEWAY]) === true)            and
                        ($data[RPBase::GATEWAY] === RPBase::VIRTUAL_ACC_YESBANK))
                    {
                        $flag = true;
                    }
            }
        }

        return $flag;
    }

    /**
     * Returns alert level as file_alert, If the trace code or
     * info code is related to Batch file level error.
     * Otherwise returns txn_alert. This further decides the
     * slack channel to which this alert will be sent to.
     *
     * @param array $data
     * @return bool
     */
    public function getAlertLevelBasedOnTraceCode($data = [])
    {
        if (((isset($data[self::TRACE_CODE]) === true)                                                      and
            (in_array($data[self::TRACE_CODE], TraceCode::$fileBasedReconTraceCodes, true) === true)) or
            ((isset($data[self::INFO_CODE]) === true)                                                       and
            (in_array($data[self::INFO_CODE], InfoCode::$fileBasedReconInfoCodes, true) === true)))
        {
            return self::FILE_ALERT;
        }

        return self::TXN_ALERT;
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
            case self::INFO:
                return 'good';

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
            case self::INFO:
                return 'Reconciliation info';

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
            case self::FILE_ALERT:
                return 'slack.channels.metrics-payments-recon';
            case self::INFO:
                return 'slack.channels.reconciliation_info';
            case self::WARN:
                return 'slack.channels.recon_alerts';

            default:
                return 'slack.channels.reconciliation2';
        }
    }
}
