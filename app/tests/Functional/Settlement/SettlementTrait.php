<?php

namespace Tests\Functional\Settlement;

use Symfony\Component\HttpFoundation\File\UploadedFile;

trait SettlementTrait
{
    protected function deleteSetlFiles()
    {
        $deleteUrls = [
            '/settlements/file/hdfc_mpr',
            '/settlements/file/setl_initiate',
            '/settlements/file/reconcile',
            '/settlements/file/return',
        ];

        $this->ba->appAuth();

        // Delete setl files first in case they already exist
        foreach ($deleteUrls as $deleteUrl)
        {
            $request = ['url' => $deleteUrl, 'method' => 'delete'];

            $response = $this->makeRequest($request);

            $this->assertResponseStatus(200);
        }
    }

    protected function generateMpr($from = null, $to = null)
    {
        $this->app['config']->set('mail.pretend', true);

        $this->ba->appAuth();

        $data = [];
        if ($from !== null)
            $data['from'] = $from;
        if ($to !== null)
            $data['to'] = $to;

        $request = array(
            'url' => '/gateway/mpr/generate',
            'content' => $data);

        $mprFile = $this->makeRequestAndGetContent($request);

        return $mprFile;
    }

    protected function reconcileMpr($mprFile, $settledAt = null)
    {
        $uploadedFile = $this->createUploadedFile($mprFile, 'application/vnd.ms-excel');

        $request = &$this->testData['testUploadMpr']['request'];
        $request['content']['recipient'] = 'hdfc_mpr_testing_test@mg.razorpay.com';
        $request['content']['attachment-count'] = '1';

        $request['files']['attachment-1'] = $uploadedFile;

        if ($settledAt !== null)
        {
            $request['content']['settled_at'] = $settledAt;
        }

        $this->ba->appAuth();

        $this->runRequestResponseFlow($this->testData['testUploadMpr']);

        $this->assertFileNotExists($mprFile);
    }

    protected function initiateSettlements($channel = 'kotak')
    {
        $request = [
            'url' => '/settlements/initiate/'.$channel,
            'method' => 'POST',
            'content' => ['all' => 1],
        ];

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function generateSetlReconciliationFile($setlFile)
    {
        $uploadedFile = $this->createUploadedFile($setlFile);

        $request = [
            'url' => '/settlements/reconcile/generate',
            'files' => [
//                'setlFile' => $uploadedFile
                'file' => $uploadedFile,
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
//                'setlReconciliationFile' => $uploadedFile
                'file' => $uploadedFile
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
//                'setlReturnFile' => $uploadedFile
                'file' => $uploadedFile,
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
        $defaultMime = 'text/plain';

        $awsConfig = $this->app['config']->get('aws::config');

        $s3mock = $awsConfig['mock'];

        if (($s3mock === false) and
            ($mimeType === $defaultMime))
        {
            $key = $this->getKeyForUrl($file);

            $bucket = $awsConfig['settlement_bucket'];

            $s3 = $this->app->make('aws')->get('s3');

            $this->assertEquals(true, $s3->doesObjectExist($bucket, $key));

            $file = storage_path('files/tmp/'.random_alpha_string(10));

            $res = fopen($file, 'w');

            $result = $s3->getObject(array(
                'Bucket' => $bucket,
                'Key'    => $key,
                'SaveAs' => $res)
            );
        }
        else
        {
            $this->assertFileExists($file);
        }

        $uploadedFile = new UploadedFile(
                                $file,
                                $file,
                                $mimeType,
                                filesize($file),
                                null,
                                true);

        return $uploadedFile;
    }

    protected function getKeyForUrl($url)
    {
        $ix = strrpos($url, '/');

        $key = substr($url, $ix+1);

        return $key;
    }
}
