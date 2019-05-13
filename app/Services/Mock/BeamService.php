<?php


namespace RZP\Services\Mock;

use RZP\Services;


class BeamService extends Services\Beam\Service
{
    public function content(& $content, $action = '')
    {
        return $content;
    }

    public function beamPush(array $pushData, array $intervalInfo, array $mailInfo, $synchronous = false)
    {
        $request = $this->getBeamRequest($pushData, $intervalInfo, $mailInfo);

        if ($synchronous === true)
        {
            $content = [
                'failed' => 'null'
            ];

            $content = $this->content($content, 'beam_push');

            return $content;
        }

        BeamJob::dispatch($request, $intervalInfo, $mailInfo, $this->config['mock']);

        return [];
    }
}