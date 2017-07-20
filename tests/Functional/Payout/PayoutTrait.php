<?php

namespace RZP\Tests\Functional\Payout;

use Symfony\Component\HttpFoundation\File\UploadedFile;

trait PayoutTrait
{
    protected function initiatePayouts($channel = 'kotak', $testTimeStamp = null)
    {
        $content = ['all' => 1];

        if ($testTimeStamp !== null)
        {
            $content['testSettleTimeStamp'] = $testTimeStamp;
        }

        $request = [
            'url'       => '/payouts/initiate/' . $channel,
            'method'    => 'POST',
            'content'   => $content,
        ];

        $this->ba->appAuthMode();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }
}
