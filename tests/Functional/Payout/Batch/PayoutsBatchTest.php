<?php

namespace RZP\Tests\Functional\Payout\Batch;

use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Feature\Constants as Features;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class PayoutsBatchTest extends TestCase
{
    use PayoutTrait;
    use WebhookTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/PayoutsBatchTestData.php';

        parent::setUp();

        $this->contact = $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fundAccount = $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $fundAccountId = $this->fixtures->fund_account->createBankAccount()->getId();

        $this->fixtures->edit('fund_account', $fundAccountId, ['id' => 'TheTestFundAcc']);

        $this->mockStorkService();

        $this->mockRazorxTreatment(
            'yesbank',
            'off',
            'off',
            'off',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'off',
            'control'
        );

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->fixtures->merchant->addFeatures([Features::PAYOUTS_BATCH, Features::MFN]);
    }

    public function testCreatePayoutsBatchWithoutIdemKey()
    {
        $this->ba->privateAuth();

        $initialPayoutsBatchCount = $this->getDbEntities('payouts_batch')->count();

        $initialIdemKeyCount = $this->getDbEntities('idempotency_key')->count();

        $initialFileCount = $this->getDbEntities('file_store')->count();

        $this->startTest();

        $finalPayoutsBatchCount = $this->getDbEntities('payouts_batch')->count();

        $finalIdemKeyCount = $this->getDbEntities('idempotency_key')->count();

        $finalFileCount = $this->getDbEntities('file_store')->count();

        $this->assertEquals($initialPayoutsBatchCount + 1, $finalPayoutsBatchCount);

        $this->assertEquals($initialIdemKeyCount, $finalIdemKeyCount);

        $this->assertEquals($initialFileCount + 1, $finalFileCount);

        $entity = $this->getDbLastEntity('payouts_batch')->toArray();

        $this->assertEquals('whu2i2830923ieni', $entity['reference_id']);
        $this->assertEquals('accepted', $entity['status']);

        $file = $this->getDbLastEntity('file_store')->toArray();

        $this->assertEquals('payout_sample', $file['type']);
        $this->assertEquals('batch', $file['entity_type']);
        $this->assertEquals('csv', $file['extension']);
        $this->assertEquals('text/csv', $file['mime']);

        $fileContent = file('storage/files/filestore/' . $file['location']);

        $this->assertFileContents($fileContent);
    }

    public function testCreatePayoutsBatchWithIdemKey()
    {
        $customTestData = $this->testData['testCreatePayoutsBatchWithoutIdemKey'];

        $headers = [
            'HTTP_' . RequestHeader::X_PAYOUT_BATCH_IDEMPOTENCY => 'testIdemKey',
        ];

        $customTestData['request']['server'] = $headers;

        $this->ba->privateAuth();

        $initialPayoutsBatchCount = $this->getDbEntities('payouts_batch')->count();

        $initialIdemKeyCount = $this->getDbEntities('idempotency_key')->count();

        $initialFileCount = $this->getDbEntities('file_store')->count();

        $this->startTest($customTestData);

        $finalPayoutsBatchCount = $this->getDbEntities('payouts_batch')->count();

        $finalIdemKeyCount = $this->getDbEntities('idempotency_key')->count();

        $finalFileCount = $this->getDbEntities('file_store')->count();

        $this->assertEquals($initialPayoutsBatchCount + 1, $finalPayoutsBatchCount);

        $this->assertEquals($initialIdemKeyCount + 1, $finalIdemKeyCount);

        $this->assertEquals($initialFileCount + 1, $finalFileCount);

        $entity = $this->getDbLastEntity('payouts_batch')->toArray();

        $this->assertEquals('whu2i2830923ieni', $entity['reference_id']);
        $this->assertEquals('accepted', $entity['status']);

        $idemEntity = $this->getDbLastEntity('idempotency_key')->toArray();

        $this->assertEquals('payouts_batch', $idemEntity['source_type']);
        $this->assertEquals($entity['batch_id'], $idemEntity['source_id']);

        $file = $this->getDbLastEntity('file_store')->toArray();

        $this->assertEquals('payout_sample', $file['type']);
        $this->assertEquals('batch', $file['entity_type']);
        $this->assertEquals('csv', $file['extension']);
        $this->assertEquals('text/csv', $file['mime']);

        $fileContent = file('storage/files/filestore/' . $file['location']);

        $this->assertFileContents($fileContent);
    }

    public function testCreateDuplicatePayoutsBatchWithSameIdemKey()
    {
        $customTestData = $this->testData['testCreatePayoutsBatchWithoutIdemKey'];

        $headers = [
            'HTTP_' . RequestHeader::X_PAYOUT_BATCH_IDEMPOTENCY => 'testIdemKey',
        ];

        $customTestData['request']['server'] = $headers;

        $this->ba->privateAuth();

        $firstResponse = $this->startTest($customTestData);

        $initialPayoutsBatchCount = $this->getDbEntities('payouts_batch')->count();

        $initialIdemKeyCount = $this->getDbEntities('idempotency_key')->count();

        $initialFileCount = $this->getDbEntities('file_store')->count();

        $secondResponse = $this->startTest($customTestData);

        $this->assertEquals($firstResponse['batch_id'], $secondResponse['batch_id']);

        $finalPayoutsBatchCount = $this->getDbEntities('payouts_batch')->count();

        $finalIdemKeyCount = $this->getDbEntities('idempotency_key')->count();

        $finalFileCount = $this->getDbEntities('file_store')->count();

        $this->assertEquals($initialPayoutsBatchCount, $finalPayoutsBatchCount);

        $this->assertEquals($initialIdemKeyCount, $finalIdemKeyCount);

        $this->assertEquals($initialFileCount, $finalFileCount);
    }

    public function testCreateDuplicatePayoutsBatchWithDifferentIdemKey()
    {
        $customTestData = $this->testData['testCreatePayoutsBatchWithoutIdemKey'];

        $headers = [
            'HTTP_' . RequestHeader::X_PAYOUT_BATCH_IDEMPOTENCY => 'testIdemKey',
        ];

        $customTestData['request']['server'] = $headers;

        $this->ba->privateAuth();

        $this->startTest($customTestData);

        $initialPayoutsBatchCount = $this->getDbEntities('payouts_batch')->count();

        $initialIdemKeyCount = $this->getDbEntities('idempotency_key')->count();

        $initialFileCount = $this->getDbEntities('file_store')->count();

        // Change the idem key now
        $customTestData['request']['server']['HTTP_' . RequestHeader::X_PAYOUT_BATCH_IDEMPOTENCY]
            = 'differentTestIdemKey';

        $this->startTest($customTestData);

        $finalPayoutsBatchCount = $this->getDbEntities('payouts_batch')->count();

        $finalIdemKeyCount = $this->getDbEntities('idempotency_key')->count();

        $finalFileCount = $this->getDbEntities('file_store')->count();

        $this->assertEquals($initialPayoutsBatchCount + 1, $finalPayoutsBatchCount);

        $this->assertEquals($initialIdemKeyCount + 1, $finalIdemKeyCount);

        $this->assertEquals($initialFileCount + 1, $finalFileCount);

        $file = $this->getDbLastEntity('file_store')->toArray();

        $this->assertEquals('payout_sample', $file['type']);
        $this->assertEquals('batch', $file['entity_type']);
        $this->assertEquals('csv', $file['extension']);
        $this->assertEquals('text/csv', $file['mime']);

        $fileContent = file('storage/files/filestore/' . $file['location']);

        $this->assertFileContents($fileContent);
    }

    public function testCreateDuplicatePayoutsBatchWithSameIdemKeyButDifferentRequestBody()
    {
        // To be used to modify test data later
        $expectedResponseAndException = [
            'exception' => [
                'class'               => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_SAME_IDEM_KEY_DIFFERENT_REQUEST,
            ],
            'response'  => [
                'content'     => [
                    'error' => [
                        'description' => PublicErrorDescription::BAD_REQUEST_SAME_IDEM_KEY_DIFFERENT_REQUEST,
                    ],
                ],
                'status_code' => 400,
            ],
        ];

        $customTestCase = $this->testData['testCreatePayoutsBatchWithoutIdemKey'];

        $headers = [
            'HTTP_' . RequestHeader::X_PAYOUT_BATCH_IDEMPOTENCY => 'testIdemKey',
        ];

        // Add idempotency header to test data
        $customTestCase['request']['server'] = $headers;

        $this->ba->privateAuth();

        // create a payouts batch entity normally using an idem key
        $this->startTest($customTestCase);

        $initialPayoutsBatchCount = $this->getDbEntities('payouts_batch')->count();

        $initialIdemKeyCount = $this->getDbEntities('idempotency_key')->count();

        $initialFileCount = $this->getDbEntities('file_store')->count();

        // Change the request body now
        $customTestCase['request']['content']['reference_id'] = 'testReferenceId';

        // Since exceptions are expected now, as we are using same idem key with different response
        $customTestCase['response'] = $expectedResponseAndException['response'];
        $customTestCase['exception'] = $expectedResponseAndException['exception'];

        $this->startTest($customTestCase);

        $finalPayoutsBatchCount = $this->getDbEntities('payouts_batch')->count();

        $finalIdemKeyCount = $this->getDbEntities('idempotency_key')->count();

        $finalFileCount = $this->getDbEntities('file_store')->count();

        // No new idem key or payouts batch record should be created
        $this->assertEquals($initialPayoutsBatchCount, $finalPayoutsBatchCount);

        $this->assertEquals($initialIdemKeyCount, $finalIdemKeyCount);

        $this->assertEquals($initialFileCount, $finalFileCount);
    }

    public function testPayoutCreationFailedWebhook()
    {
        $customTestCase = $this->testData[__FUNCTION__];

        $headers = [
            'HTTP_' . RequestHeader::X_Batch_Id => 'HjTgKBno3owAgv',
        ];

        // Add idempotency header to test data
        $customTestCase['request']['server'] = $headers;

        $this->ba->batchAuth();

        $eventTestDataKey = 'testFiringOfWebhookOnPayoutCreationFailure';

        $this->expectWebhookEventWithContents('payout.creation.failed', $eventTestDataKey);

        $this->startTest($customTestCase);
    }

    protected function assertFileContents($fileContent)
    {
        $expectedHeaderRow
            = 'RazorpayX Account Number,Payout Amount,Payout Currency,Payout Mode,Payout Purpose,' .
              'Payout Narration,Payout Reference Id,Fund Account Id,Fund Account Type,Fund Account Name,' .
              'Fund Account Ifsc,Fund Account Number,Fund Account Vpa,Fund Account Phone Number,Fund Account Email,' .
              'Contact Name,Contact Email,Contact Mobile,Contact Type,Contact Reference Id,notes[batch_reference_id],' .
              'notes[correlation_id],notes[fund_account_name],notes[fund_account_number]';

        $expectedDataRows = [
            '2224440041626905,1000,INR,NEFT,payout,Acme Corp Fund Transfer,MFN1234,,bank_account,Gaurav Kumar,' .
            'HDFC0001234,1121431121541121,,,,Gaurav Kumar,gaurav.kumar@example.com,9876543210,vendor,' .
            'Acme Contact ID 12345,whu2i2830923ieni,67d30314-f9b7-11eb-ab60-acde48001122,Gaurav Kumar,1121431121541121',

            '2224440041626905,1000,INR,IMPS,payout,Acme Corp Fund Transfer,Acme Transaction ID 12345,,bank_account,' .
            'Gaurav Kumar,HDFC0001234,1121431121541121,,,,Gaurav Kumar,gaurav.kumar@example.com,9999999999,vendor,' .
            'Acme Contact ID 12345,whu2i2830923ieni,67d30314-f9b7-11eb-ab60-acde48001122,Gaurav Kumar,1121431121541121',

            '2224440041626905,1000,INR,NEFT,payout,Acme Corp Fund Transfer,MFN12345,fa_TheTestFundAcc,,,,,,,,,,,,,' .
            'whu2i2830923ieni,67d30314-f9b7-11eb-ab60-acde48001122,,',

            '2224440041626905,1000,INR,amazonpay,refund,Acme Corp Fund Transfer,Acme Transaction ID 12345,,wallet,' .
            'Gaurav Kumar,,,,+919876543210, gaurav.kumar@example.com,Gaurav Kumar,gaurav.kumar@example.com,' .
            '9876543210,employee,Acme Contact ID 12345,whu2i2830923ieni,67d30314-f9b7-11eb-ab60-acde48001122,,',

            '2224440041626905,1000,INR,UPI,refund,Acme Corp Fund Transfer,Acme Transaction ID 12345,,vpa,,,,' .
            'gauravkumar@exampleupi,,,Gaurav Kumar,gaurav.kumar@example.com,9876543210,self,Acme Contact ID 12345,' .
            'whu2i2830923ieni,67d30314-f9b7-11eb-ab60-acde48001122,,',
        ];

        $this->assertEquals(count($expectedDataRows) + 1, count($fileContent));

        $this->assertEquals($expectedHeaderRow, trim($fileContent[0]));

        $this->assertArraySelectiveEquals(
            $expectedDataRows,
            array_map(
                function($element) {
                    return trim($element);
                },
                array_slice($fileContent, 1)
            )
        );
    }
}
