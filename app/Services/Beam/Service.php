<?php

namespace RZP\Services\Beam;

use App;

use RZP\Jobs\BeamJob;
use RZP\Constants\Mode;
use RZP\Constants\Beam;
use RZP\Encryption\Type;
use RZP\Trace\TraceCode;
use RZP\Foundation\Application;
use Illuminate\Support\Facades\Config;

class Service
{
    const HTTP_POST         = 'POST';

    const PUSH_ROUTE        = 'push';

    const TEST_ROUTE        = 'test';

    const BEAM_PUSH_FILES   = 'files';

    const BEAM_PUSH_JOBNAME = 'job_name';

    const CHOTABEAM_FLAG    = 'chotabeam';

    const BEAM_TEST_JOBNAME = 'test_pass';

    const BEAM_PUSH_BUCKET_NAME = 'bucket_name';

    const BEAM_PUSH_BUCKET_REGION = 'bucket_region';

    const BEAM_PUSH_DECRYPTION  = 'decryption';

    const BEAM_PUSH_DECRYPTION_TYPE = 'type';
    const BEAM_PUSH_DECRYPTION_MODE = 'mode';
    const BEAM_PUSH_DECRYPTION_KEY  = 'key';
    const BEAM_PUSH_DECRYPTION_IV   = 'iv';

    const BEAM_PUSH_DECRYPTION_TYPE_AES256 = 'aes256';
    const BEAM_PUSH_DECRYPTION_MODE_GCM    = 'gcm';

    protected $mode;

    protected $trace;

    protected $config;

    protected $jobName;

    protected $mailInfo;

    public function __construct(Application $app)
    {
        $this->trace  = $app['trace'];

        $this->config = $app['config']->get('applications.beam');

        $this->mode   = $app['rzp.mode'];
    }

    /**
     * Here, we create a request to be pushed to beam
     * @param array $pushData
     * @param array $intervalInfo
     * @param array $mailInfo
     * @param bool $synchronous
     * @return array
     */
    public function beamPush(array $pushData, array $intervalInfo, array $mailInfo, $synchronous = false)
    {
        $request = $this->getBeamRequest($pushData, $intervalInfo, $mailInfo);

        // For some cases, we need to parse the response from beam and proceed
        if ($synchronous === true)
        {
            $beam = new BeamJob($request, $intervalInfo, $mailInfo, $this->config['mock']);

            return dispatch_now($beam);
        }

        BeamJob::dispatch($request, $intervalInfo, $mailInfo, $this->config['mock']);

        return [];
    }

    /**
     * Get URL
     * @param $route
     * @return string
     */
    protected function getUrl($route)
    {
        return trim($this->config['url']) . '/' . $route;
    }

    protected function getBeamRequest(array $pushData, array $intervalInfo, array $mailInfo)
    {
        $this->trace->info(
            TraceCode::BEAM_METHOD_CALL,
            [
                'push_data'     => $pushData,
                'interval_info' => $intervalInfo,
                'mail_info'     => $mailInfo
            ]);

        $data[self::BEAM_PUSH_FILES]   = $pushData[self::BEAM_PUSH_FILES];

        $data[self::BEAM_PUSH_JOBNAME] = $pushData[self::BEAM_PUSH_JOBNAME];

        if (isset($pushData[self::BEAM_PUSH_BUCKET_NAME]) === true)
        {
            $data[self::BEAM_PUSH_BUCKET_NAME] = $pushData[self::BEAM_PUSH_BUCKET_NAME];
        }

        if (isset($pushData[self::BEAM_PUSH_BUCKET_REGION]) === true)
        {
            $data[self::BEAM_PUSH_BUCKET_REGION] = $pushData[self::BEAM_PUSH_BUCKET_REGION];
        }

        $traceData = $data;

        if (isset($pushData[self::BEAM_PUSH_DECRYPTION]) === true)
        {
            $data[self::BEAM_PUSH_DECRYPTION] = $pushData[self::BEAM_PUSH_DECRYPTION];

            $traceData[self::BEAM_PUSH_DECRYPTION] = $pushData[self::BEAM_PUSH_DECRYPTION];

            if (isset($traceData[self::BEAM_PUSH_DECRYPTION][self::BEAM_PUSH_DECRYPTION_KEY]) === true)
            {
                unset($traceData[self::BEAM_PUSH_DECRYPTION][self::BEAM_PUSH_DECRYPTION_KEY]);
            }
        }

        $route = self::PUSH_ROUTE;

        if ($this->mode === Mode::TEST)
        {
            $route = self::TEST_ROUTE;

            $data[self::BEAM_PUSH_JOBNAME] = self::BEAM_TEST_JOBNAME;
        }

        $data = json_encode($data);

        $traceData = json_encode($traceData);

        $url = $this->getUrl($route);

        if(isset($pushData[self::CHOTABEAM_FLAG]) === true and $pushData[self::CHOTABEAM_FLAG] === true)
        {
            $url = Config::get('applications.chota_beam.url') . '/push';
        }

        $request = $traceRequest = [
            'options' => [
                'timeout' => 300
            ],
            'content' => $data,
            'method'  => self::HTTP_POST,
            'headers' => [
                'Content-Type'=> 'application/json'
            ],
            'url'     => $url
        ];

        // Don't set encryption key when tracing
        $traceRequest['content'] = $traceData;

        $this->trace->info(
            TraceCode::BEAM_PUSH,
            [
                'request'  => $traceRequest,
                'interval' => $intervalInfo
            ]
        );

        return $request;
    }
}
