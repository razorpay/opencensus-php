<?php
namespace RZP\Tests\Functional\Batch;

use Illuminate\Support\Facades\Queue;

use RZP\Models\Batch;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\Fixtures\Entity\Feature;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Batch\Header;

class DirectDebitTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/DirectDebitTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures(['skip_payment_auth']);

    }

    public function testCreateDirectDebitBatch()
    {
        Queue::fake();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Just asserting that job is being pushed on creation of batch entity
        // for payment link type.

        Queue::assertPushed(BatchJob::class);
    }

    public function getDefaultFileEntries()
    {
        return [
            [
                Header::DIRECT_DEBIT_EMAIL           => 'test@razorpay.com',
                Header::DIRECT_DEBIT_PHONE           => 9876543210,
                Header::DIRECT_DEBIT_CARD            => '4111111111111111',
                Header::DIRECT_DEBIT_EXPIRY_MONTH    => '12',
                Header::DIRECT_DEBIT_EXPIRY_YEAR     => '25',
                Header::DIRECT_DEBIT_CARDHOLDER_NAME => 'John Doe',
                Header::DIRECT_DEBIT_CURRENCY        => 'INR',
                Header::DIRECT_DEBIT_AMOUNT          => 9900,
                Header::DIRECT_DEBIT_RECEIPT         => '123456',
                Header::DIRECT_DEBIT_NOTES1          => '123456',
                Header::DIRECT_DEBIT_NOTES2          => '123456',
                Header::DIRECT_DEBIT_NOTES3          => '123456',
            ],
        ];
    }
}
