<?php

namespace RZP\Tests\Functional\FundTransfer;

use Mail;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use RZP\Models\FundTransfer\Attempt;
use RZP\Mail\Settlement\Reconciliation as ReconciliationMail;

trait AttemptReconcileTrait
{
    protected function generateReconciliationFileForChannel(
        $setlFile,
        string $channel,
        $generateFailedReconciliations = false,
        $prevAttemptId = null)
    {
        $uploadedFile = $this->createUploadedFile($setlFile);

        $request = [
            'url' => '/settlements/reconcile/generate/' . $channel,
            'files' => [
                'file' => $uploadedFile,
            ],
            'content' => [
                'failed_recons'     => $generateFailedReconciliations,
                'prev_attempt_id'   => $prevAttemptId,
            ]
        ];

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('setlReconciliationFile', $content);

        // $this->assertFileNotExists($setlFile);

        return $content['setlReconciliationFile'];
    }

    protected function reconcileSettlements($setlReconciliationFile, string $channel)
    {
        $uploadedFile = $this->createUploadedFile($setlReconciliationFile);

        $request = [
            'url' => '/settlements/h2hreconcile/' . $channel,
            'files' => [
                'file' => $uploadedFile
            ],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertFileNotExists($setlReconciliationFile);

        return $content;
    }

    protected function reconcileSettlementsForChannel(
        $setlFile,
        string $channel,
        bool $markAttemptFailed = false): array
    {
        $setlReconciliationFile = $this->generateReconciliationFileForChannel(
            $setlFile, $channel, $markAttemptFailed);

        $data = $this->reconcileSettlements($setlReconciliationFile, $channel);

        return $data;
    }

    protected function reconcileEntitiesForChannel(string $channel)
    {
        $this->ba->cronAuth();

        $request = [
            'url'       => '/fund_transfer_attempts/reconcile/' . $channel,
            'method'    => 'POST',
            'content'   => [],
        ];

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function assertReconFileProcessSuccessForChannel($setlFile, string $channel, string $sourceType)
    {
        Mail::fake();

        $data = $this->reconcileSettlementsForChannel($setlFile, $channel, false);

        // Match data returned by reconciliation
        $this->assertTestResponse($data, 'matchSummaryForReconFile');
        $this->assertEquals($channel, $data['channel']);

        // Validate settlement attempt entity
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $dataKey = 'matchAttemptForReconSuccess' . ucfirst($channel);
        $this->assertTestResponse($attempt, $dataKey);
        $this->assertNotNull($attempt['utr']);
        $this->assertEquals($channel, $attempt[Attempt\Entity::CHANNEL]);

        $source = $this->getLastEntity($sourceType, true);
        $this->assertNotNull($source['utr']);

        Mail::assertSent(ReconciliationMail::class);
    }


    protected function createUploadedFile($file, $mimeType = 'text/plain')
    {
        $defaultMime = 'text/plain';

        $awsConfig = $this->app['config']->get('aws');

        $s3mock = $awsConfig['mock'];

        if (($s3mock === false) and
            ($mimeType === $defaultMime))
        {
            $key = $this->getKeyForUrl($file);

            $bucket = $awsConfig['settlement_bucket'];

            $s3 = Handler::getClient();

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
