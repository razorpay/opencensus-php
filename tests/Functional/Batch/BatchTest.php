<?php

namespace RZP\Tests\Functional\Batch;

use Mail;
use RZP\Models\Vpa;
use RZP\Models\Payout;
use RZP\Models\BankAccount;
use RZP\Mail\Batch\PaymentLink;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

/**
 * Class: BatchTest
 * Include only one success test case per type.
 * If requires multiple set of tests per type consider adding specific class.
 */
class BatchTest extends TestCase
{
    use BatchTestTrait;
    use TestsBusinessBanking;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/BatchTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testSendMailFromBatchService()
    {
        Mail::fake();

        $this->ba->appAuth();

        $this->fixtures->create('merchant', ['id' => 'CVuOcOYoUiAqNY']);

        $this->writeToCsvFile([], 'payment', null, 'files/filestore/batch/download');

        $this->startTest();

        Mail::assertSent(PaymentLink::class, function ($mail)
        {
            $this->assertNotEmpty($mail->attachments);

            $body = 'Please find attached processed payment link file';

            $this->assertEquals($body, $mail->viewData['body']);

            return true;
        });
    }

    protected function getFileEntries(string $callee): array
    {
        return $this->testData["{$callee}RequestFileEntries"];
    }
}
