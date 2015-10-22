<?php

namespace Services;

use App;
use Config;
use Slack;

trait SlackPoster
{
    /**
     * Posts information to slack
     * method Copied from dashboard
     * @param string $headline headline for slack post
     * @param array  $postdata  array of data to post
     * @param string $pretext  Optional text to appear above the attachment and below the actual message
     * @param array  $settings array of common settings such as channel, color etc
     * @return null
     */
    public function slackPost($headline, array $postdata, $pretext = '', array $settings = [])
    {
        // Note that api uses SLACK_MOCK instead of SLACK_ENABLE which dashboard uses
        if(Config::get('slack.mock') === false)
        {
            $data = $this->getSlackContext();

            //  Fallback text for plaintext clients, like IRC
            $data['fallback']   = $headline.'\n';
            $data['fields']     = [];
            $data['pretext']    = $pretext;

            /**
             * This should be used for username, channel, and color setting
             */
            // foreach ($settings as $key => $value) {
            //     $data[$key] = $value;
            // }
            //

            // If our data is nested, we need to flatten it
            $postdata = flatten_array($postdata);

            /**
             * Attach all the extra fields
             */
            foreach($postdata as $key => $value)
            {
                //  Fallback text for plaintext clients, like IRC
                $data['fallback'] .= $key . ': ' . $value . '\n';
                $data['fields'][] = array(
                    'title' => $key,
                    'value' => $value,
                    'short' => true
                );
            }

            if (isset($settings['channel']))
            {
                Slack::to($settings['channel'])->attach($data)->queue($headline);
            }
            else
            {
                Slack::attach($data)->queue($headline);
            }

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
