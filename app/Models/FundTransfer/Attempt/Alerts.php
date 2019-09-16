<?php

namespace RZP\Models\FundTransfer\Attempt;

use App;

class Alerts
{
    protected $app;

    const ALERT = 'alert';
    const INFO  = 'info';
    const WARN  = 'warn';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    public function notifySlack($data, string $level)
    {
        if (empty($data) === true)
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
                return 'FTA alert';
            case self::INFO:
                return 'FTA info';
            case self::WARN:
                return 'FTA alert';

            default:
                return 'FTA alert';
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
                return 'slack.channels.fta_alerts';
            case self::INFO:
                return 'slack.channels.fta_alerts';
            case self::WARN:
                return 'slack.channels.fta_alerts';

            default:
                return 'slack.channels.fta_alerts';
        }
    }
}
