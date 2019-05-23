<?php


namespace RZP\Services\Mock;

use RZP\Services;
use RZP\Jobs\BeamJob;


class BeamService extends Services\Beam\Service
{
    protected $mockService = null;

    public function content(& $content, $action = '')
    {
        return $content;
    }

    public function beamPush(array $pushData, array $intervalInfo, array $mailInfo, $synchronous = false)
    {
        if ($this->mockService !== null)
        {
            return $this->mockService->beamPush($pushData, $intervalInfo, $mailInfo, $synchronous);
        }

        $request = $this->getBeamRequest($pushData, $intervalInfo, $mailInfo);

        if ($synchronous === true)
        {
            $content = [
                'failed' => null,
                'success' => [],
            ];

            $this->content($content, 'beam_push_sync');

            return $content;
        }

        BeamJob::dispatch($request, $intervalInfo, $mailInfo, $this->config['mock']);

        return [];
    }

    public function setMockService(BeamService $beamService)
    {
        $this->mockService = $beamService;
    }
}
