<?php

namespace Tests\Functional\Settlement;

use Symfony\Component\HttpFoundation\File\UploadedFile;

trait SettlementTrait
{
    protected function generateMpr()
    {
        \Config::set('mail.pretend', true);

        $this->ba->appAuth();

        $request = array(
            'url' => '/gateway/mpr/generate');

        $mprFile = $this->makeRequestAndGetContent($request);

        return $mprFile;
    }

    protected function reconcileMpr($mprFile)
    {
        $uploadedFile = $this->createUploadedFile($mprFile, 'application/vnd.ms-excel');

        $request = &$this->testData['testUploadMpr']['request'];
        $request['content']['recipient'] = 'hdfc_mpr_testing_test@mg.razorpay.com';
        $request['content']['attachment-count'] = '1';

        $request['files']['attachment-1'] = $uploadedFile;

        $this->ba->appAuth();

        $this->runRequestResponseFlow($this->testData['testUploadMpr']);

        $this->assertFileNotExists($mprFile);
    }

    protected function initiateSettlements($txns, $channel = 'kotak')
    {
        $request = [
            'url' => '/settlements/initiate/'.$channel,
            'method' => 'POST',
            'content' => ['all' => 1],
        ];

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('kotak', $content);
        $this->assertArrayHasKey('setlFile', $content['kotak']);

        return $content['kotak']['setlFile'];
    }

    protected function generateSetlReconciliationFile($setlFile)
    {
        $uploadedFile = $this->createUploadedFile($setlFile);

        $request = [
            'url' => '/settlements/reconcile/generate',
            'files' => [
                'setlFile' => $uploadedFile
            ],
        ];

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('setlReconciliationFile', $content);

        // $this->assertFileNotExists($setlFile);

        return $content['setlReconciliationFile'];
    }

    protected function reconcileSettlements($setlReconciliationFile)
    {
        $uploadedFile = $this->createUploadedFile($setlReconciliationFile);

        $request = [
            'url' => '/settlements/reconcile',
            'files' => [
                'setlReconciliationFile' => $uploadedFile
            ],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertFileNotExists($setlReconciliationFile);

        return $content;
    }

    protected function generateSetlReturnFile($setlData)
    {
        $items = $setlData['items'];

        $content = [];

        foreach ($items as $item)
        {
            $content[] = [
                'id' => $item['id'],
                'refer_utr' => '1'
            ];
        }

        $request = [
            'url' => '/settlements/return/generate',
            'content' => $content,
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('setlReturnFile', $content);

        return $content['setlReturnFile'];
    }

    protected function processSetlReturns($setlReturnFile)
    {
        $uploadedFile = $this->createUploadedFile($setlReturnFile);

        $request = [
            'url' => '/settlements/return',
            'files' => [
                'setlReturnFile' => $uploadedFile
            ],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertFileNotExists($setlReturnFile);

        return $content;
    }

    protected function unlinkFile($file)
    {
         $this->assertTrue(
            unlink($file),
            'Could not delete file generated during testing. Filename: ' . $file);
    }

    protected function createUploadedFile($file, $mimeType = 'text/plain')
    {
        $this->assertFileExists($file);

        $uploadedFile = new UploadedFile(
                                $file,
                                $file,
                                $mimeType,
                                filesize($file),
                                null,
                                true);

        return $uploadedFile;
    }
}
