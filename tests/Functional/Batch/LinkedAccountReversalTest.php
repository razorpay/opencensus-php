<?php

namespace RZP\Tests\Functional\Batch;

use Mail;
use Illuminate\Support\Facades\Queue;

use RZP\Models\User\Role;
use RZP\Models\FileStore;
use RZP\Models\Batch\Header;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Mail\Batch\LinkedAccountReversal as BatchLinkedAccountReversalFileMail;

class LinkedAccountReversalTest extends TestCase
{
    use BatchTestTrait;

    protected $linkedAccountId;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/LinkedAccountReversalTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $account = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);

        $merchantDetailAttributes =  [
            'merchant_id'   => $account['id'],
            'contact_email' => $account['email'],
            'activation_status' => "activated",
            'bank_details_verification_status'  => 'verified'
        ];

        $this->fixtures->create('merchant_detail:associate_merchant', $merchantDetailAttributes);

        $this->linkedAccountId = $account['id'];
    }

    public function testCreateBatchForLAReversal()
    {
        Queue::fake();

        $entries = $this->getDefaultLAReversalFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->setAuthForLinkedAccount();

        $response = $this->startTest();

        // This attribute(derived) is only exposed in admin auth at the moment
        $this->assertArrayNotHasKey('processed_percentage', $response);
        $this->assertArrayNotHasKey('processed_count', $response);

        $batch = $this->getDbLastEntity('batch');

        $this->assertEquals(3, $batch->getProcessedCount());
    }

    public function testCreateBatchForLAReversalWithoutPermission()
    {
        $entries = $this->getDefaultLAReversalFileEntries(1, false);

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->setAuthForLinkedAccount();

        $this->startTest();
    }

    public function testCreateBatchForLAReversaDulplicateEntry()
    {
        $entries = $this->getDefaultLAReversalFileEntries(1);

        $entries[] = $entries[0];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->setAuthForLinkedAccount();

        $this->startTest();
    }

    public function testUploadLAReversalFileException()
    {
        $entries = $this->getDefaultLAReversalFileEntries(1);

        // Put improper format data
        $entries[0][Header::AMOUNT_IN_PAISE] = '';

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->setAuthForLinkedAccount();

        $this->startTest();
    }

    public function testGetLAReversalFiles()
    {
        $entries = $this->getDefaultLAReversalFileEntries();

        $batch = $this->fixtures->create('batch:linked_account_reversal', $entries);

        $this->setAuthForLinkedAccount();

        $this->startTest();
    }

    public function testGetLAReversalFileWithId()
    {
        // Some class variables in Batch base file are messing with the numbers will refactor that and fix this test.
        $this->markTestSkipped();

        $entries = $this->getDefaultLAReversalFileEntries();

        $batch = $this->fixtures->create('batch:linked_account_reversal', $entries);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/batches/' . $batch->getPublicId();

        $this->setAuthForLinkedAccount();

        $this->startTest();
    }

    public function testProcessLAReversalFileWithValidAndInvalidTransfers()
    {
        Mail::fake();

        $entries = $this->getDefaultLAReversalFileEntries(1);

        $entries[] = [
            Header::TRANSFER_ID         => '12345679abcde',
            Header::AMOUNT_IN_PAISE     => 200,
        ];

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->setAuthForLinkedAccount();

        $response = $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $batch['total_count']);

        $this->assertEquals(1, $batch['success_count']);

        $this->assertEquals(1, $batch['failure_count']);

        $file = FileStore\Entity::where(FileStore\Entity::TYPE, FileStore\Type::BATCH_OUTPUT)
                                ->first();

        $this->assertNotNull($file);

        $fileName = substr($batch['id'], strpos($batch['id'], "_") + 1);

        $this->assertEquals('batch/download/' . $fileName . '.xlsx', $file->getLocation());

        $this->assertEquals('batch/download/' . $fileName, $file->getName());

        Mail::assertSent(BatchLinkedAccountReversalFileMail::class);

        // Validate notes
        $reversals = $this->getEntities('reversal', [], true);

        $expectedReversalsWithNotes = [
            [
                'transfer_id' => $entries[0][Header::TRANSFER_ID],
                'amount'     => 1000,
                'notes'      => [
                    'key_1' => 'Notes Value 1',
                    'key_2' => 'Notes Value 2'
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedReversalsWithNotes, $reversals['items']);
    }

    protected function getDefaultLAReversalFileEntries(int $noOfEntries = 3, $allowReversals = true)
    {
        if ($allowReversals === true)
        {
            $this->fixtures->merchant->addFeatures([FeatureConstants::ALLOW_REVERSALS_FROM_LA], $this->linkedAccountId);
        }

        $entries = [];

        for ($index = 0; $index < $noOfEntries; $index++)
        {
            $payment = $this->doAuthAndCapturePayment();

            $transfers = [
                [
                    'account'  => 'acc_' . $this->linkedAccountId,
                    'amount'   => 1000,
                    'currency' => 'INR',
                ]
            ];

            $transfers = $this->transferPayment($payment['id'], $transfers);

            $transferId = $transfers['items'][0]['id'];

            $entries[] = [
                Header::TRANSFER_ID     => $transferId,
                Header::AMOUNT_IN_PAISE => 1000,
                'notes[key_1]'          => 'Notes Value 1',
                'notes[key_2]'          => 'Notes Value 2',
            ];
        }

        return $entries;
    }

    protected function setAuthForLinkedAccount($mid = null)
    {
        $mid = $mid ?? $this->linkedAccountId;

        $user = $this->fixtures->user->createUserForMerchant($mid, [], Role::LINKED_ACCOUNT_OWNER);

        $this->ba->proxyAuth('rzp_test_' . $mid , $user->getId());
    }
}
