<?php

namespace Services;

use App;
use Slack;

trait Slack
{
    /**
     * Posts information to slack
     * method Copied from dashboard
     * @param  string $headline headline for slack post
     * @param  array $postdata  array of data to post
     * @param  string $channel  Name of channel to post in
     * @param  string $pretext  Pretext
     * @param  string $color    good|bad
     * @return null
     */
    public function slackPost($headline, $postdata, $pretext = '', array $settings)
    {
        // Note that api uses SLACK_MOCK instead of SLACK_ENABLE which dashboard uses
        if($_ENV['SLACK_MOCK'] === false)
        {
            $data = $this->getSlackContext();
            $data['fallback']   = $headline.'\n';
            $data['fields']     = array();
            $data['pretext']    = $pretext;

            /**
             * This should be used for username, channel, and color setting
             */
            foreach ($settings as $key => $value) {
                $data[$key] = $value;
            }

            /**
             * Attach all the extra fields
             */
            foreach($postdata as $key => $value)
            {
                $data['fallback'] .= $key . ': ' . $value . '\n';
                $data['fields'][] = array(
                    'title' => $key,
                    'value' => $value,
                    'short' => false
                );
            }

            Slack::to($channel)->attach($data)->send($headline);
        }
    }

    /**
     * Returns the default variable set we attach with every slack post
     * @return array an array of some config options that help us trace the request
     */
    protected function getSlackContext()
    {
        $app = App::getFacadeRoot();
        $cloud = $app['config']->get('app.cloud');
        $data = [
            'env'           =>  $app['env'],
            'context'       =>  $app['config']->get('app.context'),
            'cloud'         =>  $cloud
        ];

        if($cloud)
        {
            $data['instance'] = $app['instance']->getInstanceId();
        }

        return $data;
    }
}
