<?php

namespace RZP\Tests\Functional\Payout;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use AWS;

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
            'url' => '/payouts/initiate/'.$channel,
            'method' => 'POST',
            'content' => $content,
        ];

        $this->ba->appAuthMode();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function generatePayoutReconciliationFile($setlFile)
    {
        $uploadedFile = $this->createUploadedFile($setlFile);

        $request = [
            'url' => '/payouts/reconcile/generate',
            'files' => [
                'file' => $uploadedFile,
            ],
        ];

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('payoutReconciliationFile', $content);

        // $this->assertFileNotExists($setlFile);

        return $content['payoutReconciliationFile'];
    }

    protected function reconcilePayouts($payoutReconciliationFile)
    {
        $uploadedFile = $this->createUploadedFile($payoutReconciliationFile);

        $request = [
            'url' => '/payouts/h2hreconcile',
            'files' => [
                'file' => $uploadedFile
            ],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertFileNotExists($payoutReconciliationFile);

        return $content;
    }

    protected function generatePayoutReturnFile($payoutData)
    {
        $items = $payoutData;

        $content = [];

        foreach ($items as $item)
        {
            $content[] = [
                'id' => 'pout_' . $item['id'],
                'refer_utr' => '1'
            ];
        }

        $request = [
            'url' => '/payouts/return/generate',
            'content' => $content,
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('payoutReturnFile', $content);

        return $content['payoutReturnFile'];
    }
}
