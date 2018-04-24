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
                Header::EMAIL           => 'test@razorpay.com',
                Header::PHONE           => 9876543210,
                Header::CARD            => '4111111111111111',
                Header::EXPIRY_MONTH    => '12',
                Header::EXPIRY_YEAR     => '25',
                Header::CARDHOLDER_NAME => 'John Doe',
                Header::CURRENCY        => 'INR',
                Header::AMOUNT          => 9900,
                Header::RECEIPT         => '123456',
                Header::NOTES1          => '123456',
                Header::NOTES2          => '123456',
                Header::NOTES3          => '123456',
            ],
        ];
    }
}
