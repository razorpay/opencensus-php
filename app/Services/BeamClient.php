<?php

namespace RZP\Services;

use App;

use RZP\Constants\Mode;
use RZP\Jobs\BeamJob;
use RZP\Constants\Beam;
use RZP\Trace\TraceCode;
use RZP\Foundation\Application;

class BeamClient
{
    const HTTP_POST         = 'POST';

    const PUSH_ROUTE        = 'push';

    const TEST_ROUTE        = 'test';

    const BEAM_PUSH_FILES   = 'files';

    const BEAM_PUSH_JOBNAME = 'job_name';

    const BEAM_TEST_JOBNAME = 'test_pass';

    protected $trace;

    protected $config;

    protected $mode;

    public function __construct(Application $app)
    {
        $this->trace  = $app['trace'];

        $this->config = $app['config'];

        $this->mode   = $app['rzp.mode'];
    }

    /**
     * Sends a beam push request.
     * Here push data accepts an array for 'files' key
     * @param array $pushData
     * @param array $intervalInfo
     * @param array $mailInfo
     */
    public function beamPush(array $pushData, array $intervalInfo, array $mailInfo)
    {
        $data[self::BEAM_PUSH_FILES]   = $pushData[self::BEAM_PUSH_FILES];

        $data[self::BEAM_PUSH_JOBNAME] = $pushData[self::BEAM_PUSH_JOBNAME];

        $route = self::PUSH_ROUTE;

        if ($this->mode === Mode::TEST)
        {
           $route = self::TEST_ROUTE;

           $data[self::BEAM_PUSH_JOBNAME] = self::BEAM_TEST_JOBNAME;
        }

        $data = json_encode($data);

        $request = [
            'options' => [],
            'content' => $data,
            'method'  => self::HTTP_POST,
            'headers' => [
                'Content-Type'=> 'application/json'
            ],
            'url'     => $this->getUrl($route)
        ];

        $this->trace->info(
            TraceCode::BEAM_PUSH,
            [
                'request'  => $request,
                'interval' => $intervalInfo,
                'mailInfo' => $mailInfo
            ]
        );

        BeamJob::dispatch($request, $intervalInfo, $mailInfo);
    }

    /**
     * Get URL
     * @param $route
     * @return string
     */
    protected function getUrl($route)
    {
        return trim($this->config->get('applications.beam')['url']) . '/' . $route;
    }
}