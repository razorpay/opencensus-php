<?php

namespace RZP\Tests\Functional\FundTransfer;

use Mail;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Attempt;
use RZP\Mail\Settlement\Reconciliation as ReconciliationMail;

trait AttemptReconcileTrait
{
    protected function generateReconciliationFileForChannel(
        $setlFile,
        string $channel,
        $generateFailedReconciliations = false,
        $prevAttemptId = null,
        $generateReturnSettledReconciliation = false)
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
                'return_settled'    => $generateReturnSettledReconciliation,
            ]
        ];

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('setlReconciliationFile', $content);

        // $this->assertFileNotExists($setlFile);

        return $content['setlReconciliationFile'];
    }

    protected function reconcileOnlineSettlements(string $channel, string $failureTest = '')
    {
        $request = [
            'url'       => '/settlements/reconcile/api/' . $channel,
            'content'   => [
                'failed_response' => $failureTest
            ]
        ];

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
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

        $this->ba->h2hAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertFileNotExists($setlReconciliationFile);

        return $content;
    }

    protected function reconcileSettlementsUsingLambda($setlReconciliationFile, string $channel, bool $newLambda = true)
    {
        $this->ba->h2hAuth();

        $content = [
            'source' => 'lambda',
            'prefix' => 'axis/poweraccess/settlements/axis_reversefeed_razorpay_20',
            'key'    =>  $setlReconciliationFile,
        ];

        if ($newLambda === true)
        {
            $content['bucket'] = 'rzp-test-bucket';
            $content['region'] = 'ap-south-1';
        }

        $request = [
            'url' => '/settlements/h2hreconcile/' . $channel,
            'method' => 'POST',
            'content'=> $content,
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

    protected function assertReconFileProcessFlipStatusForChannel($setlFile, string $channel, string $sourceType)
    {
        Mail::fake();

        $data = $this->reconcileSettlementsForChannel($setlFile, $channel, true);

        // Match data returned by reconciliation
        $this->assertTestResponse($data, 'matchSummaryForReconFile');
        $this->assertEquals($channel, $data['channel']);

        // Validate settlement attempt entity
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        $dataKey = 'matchAttemptForReconFlipStatus' . ucfirst($channel);
        $this->assertTestResponse($attempt, $dataKey);
        $this->assertNotNull($attempt['utr']);
        $this->assertEquals($channel, $attempt[Attempt\Entity::CHANNEL]);

        $source = $this->getLastEntity($sourceType, true);
        $this->assertNotNull($source['utr']);

        Mail::assertQueued(ReconciliationMail::class);
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

        Mail::assertQueued(ReconciliationMail::class);
    }

    protected function assertReconProcessSuccessForChannel(string $channel, string $sourceType, bool $failureTest)
    {
        $this->reconcileOnlineSettlements($channel, $failureTest);

        // Validate settlement attempt entity
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        if ($failureTest === false)
        {
            $dataKey = 'matchAttemptForReconSuccess' . ucfirst($channel);
        }
        else
        {
            $dataKey = 'matchAttemptForReconFailure' . ucfirst($channel);
        }

        $this->assertTestResponse($attempt, $dataKey);

        $this->assertEquals($channel, $attempt[Attempt\Entity::CHANNEL]);

        $source = $this->getLastEntity($sourceType, true);

        if ($failureTest === true)
        {
            $this->assertNull($attempt['utr']);
            $this->assertNull($source['utr']);
        }
        else
        {
            $this->assertNotNull($attempt['utr']);
            $this->assertNotNull($source['utr']);
        }
    }

    protected function assertReconProcessSuccessForChannelVpa(string $channel, string $sourceType, bool $failureTest)
    {
        $data = $this->reconcileOnlineSettlements($channel, $failureTest);

        // Validate settlement attempt entity
        $attempt = $this->getLastEntity('fund_transfer_attempt', true);

        if ($failureTest === false)
        {
            $dataKey = 'matchAttemptForReconSuccess' . ucfirst($channel) . 'Vpa';
        }
        else
        {
            $dataKey = 'matchAttemptForReconFailure' . ucfirst($channel) . 'Vpa';
        }

        $this->assertTestResponse($attempt, $dataKey);

        $this->assertEquals($channel, $attempt[Attempt\Entity::CHANNEL]);

        $source = $this->getLastEntity($sourceType, true);

        if ($failureTest === true)
        {
            $this->assertNull($attempt['utr']);
            $this->assertNull($source['utr']);
        }
        else
        {
            $this->assertNotNull($attempt['utr']);

            if (($source['status'] === 'processed') or
                ($source['status'] === 'reversed'))
            {
                $this->assertNotNull($source['utr']);
            }
            else
            {
                $this->assertNull($source['utr']);
            }
        }
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
            null,
            true);

        return $uploadedFile;
    }

    protected function getKeyForUrl($url)
    {
        $ix = strrpos($url, '/');

        $key = substr($url, $ix + 1);

        return $key;
    }

    protected function verifyProcessedSettlements(string $channel, $failureTest)
    {
        $from = Carbon::today(Timezone::IST)->getTimestamp();

        $to = Carbon::tomorrow(Timezone::IST)->getTimestamp() - 1;

        $request = [
            'url'       => '/settlements/verify/' . $channel,
            'content'   => [
                'status'            => Attempt\Status::PROCESSED,
                'failed_response'   => (int) $failureTest,
                'from'              => $from,
                'to'                => $to
            ]
        ];

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }
}
