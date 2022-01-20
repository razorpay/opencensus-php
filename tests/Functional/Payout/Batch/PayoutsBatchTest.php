<?php

namespace RZP\Tests\Functional\Payout\Batch;

use App;
use RZP\Models\Batch;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Http\RequestHeader;
use RZP\Models\Pricing\Fee;
use RZP\Models\User\BankingRole;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Payout\Batch as PayoutsBatch;
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

        $this->mockRazorxTreatment();

        $this->app['config']->set('applications.batch.mock', true);
        $this->app['config']->set('applications.fts.mock', true);

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

    public function testPayoutWebhooks()
    {
        $customTestCase = $this->testData[__FUNCTION__];

        $headers = [
            'HTTP_' . RequestHeader::X_Batch_Id     => 'C3fzDCb4hA4F6b',
            'HTTP_' . RequestHeader::X_Creator_Type => null,
            'HTTP_' . RequestHeader::X_Creator_Id   => null,
        ];

        // Add idempotency header to test data
        $customTestCase['request']['server'] = $headers;

        $this->ba->batchAuth();

        $this->expectWebhookEventWithContents('payout.creation.failed', 'testFiringOfWebhookOnPayoutCreationFailure');

        $this->expectWebhookEventWithContents('payout.initiated', 'testFiringOfWebhookOnPayoutCreation');

        $this->fixtures->create('payouts_batch', [
            'batch_id'     => 'C3fzDCb4hA4F6b',
            'reference_id' => 'whu2i2830923ieni',
            'merchant_id'  => '10000000000000',
            'status'       => 'processed',
        ]);

        $this->startTest($customTestCase);
    }

    // This is to test if existing proxy auth based bulk payouts are not breaking in the new validations flow
    public function testBatchesCreateWithProxyAuthWhenOtpIsSent()
    {
        // To remove errors due to rupee/paise header validations
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

        //create the file by replicating payouts_batch core code
        $refId = $this->testData['testCreatePayoutsBatchWithoutIdemKey']['request']['content']['reference_id'];
        $payouts = $this->testData['testCreatePayoutsBatchWithoutIdemKey']['request']['content']['payouts'];

        $merchant = $this->getDbEntity('merchant', ['id' => '10000000000000']);

        $fileDetailsArray = (new PayoutsBatch\Core())->createFileForBatchService($refId, $payouts, $merchant);

        $generatedFileName
            = substr($fileDetailsArray[FileStore\Entity::NAME],
                     0, -1 * (strlen('.' . PayoutsBatch\Constants::EXTENSION_CSV)));

        // Add file ID to test data request contents
        $customTestData = $this->testData[__FUNCTION__];

        $customTestData['request']['content']['name'] = $generatedFileName;

        $customTestData['request']['content']['file_id'] = $fileDetailsArray['file_id'];

        $this->ba->proxyAuth();

        $this->startTest($customTestData);
    }

    // This is to test that OTP validations are not checked in private auth during batch create
    public function testBatchesCreateWithPrivateAuthWhenOtpIsSent()
    {
        // Pre-initialising App facade to mock a private auth
        $app = App::getfacadeRoot();

        $app['basicauth']->init();
        $app['basicauth']->privateAuth();

        //create the file by replicating payouts_batch core code
        $refId = $this->testData['testCreatePayoutsBatchWithoutIdemKey']['request']['content']['reference_id'];

        $payouts = $this->testData['testCreatePayoutsBatchWithoutIdemKey']['request']['content']['payouts'];

        $merchant = $this->getDbEntity('merchant', ['id' => '10000000000000']);

        $fileDetailsArray = (new PayoutsBatch\Core())->createFileForBatchService($refId, $payouts, $merchant);

        $generatedFileName
            = substr($fileDetailsArray[FileStore\Entity::NAME],
                     0, -1 * (strlen('.' . PayoutsBatch\Constants::EXTENSION_CSV)));

        $this->expectException(\RZP\Exception\ExtraFieldsException::class);
        $this->expectExceptionMessage('otp is/are not required and should not be sent');

        $batchServiceResponse = (new Batch\Core())->create(
            [
                Batch\Entity::TYPE    => Batch\Type::PAYOUT,
                Batch\Entity::NAME    => $generatedFileName,
                Batch\Entity::FILE_ID => $fileDetailsArray['file_id'],
                Batch\Entity::CONFIG  => ['batch_reference_id' => $refId],
                Batch\Entity::OTP     => '0007',
                Batch\Entity::TOKEN   => 'RandomTokenRzp',
            ],
            $merchant
        )->toArrayPublic();
    }

    // to test if the webhooks for a dashboard based bulk payout works normally with mfn and payouts_batch feature flags
    public function testPayoutWebhooksForDashboardBasedBulkPayouts()
    {
        $customTestCase = $this->testData[__FUNCTION__];

        $user = $this->fixtures->create('user',['id'  => '20000000000006']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => BankingRole::FINANCE_L1,
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $headers = [
            'HTTP_' . RequestHeader::X_Batch_Id     => 'C3fzDCb4hA4F6b',
            'HTTP_' . RequestHeader::X_Creator_Type => 'user',
            'HTTP_' . RequestHeader::X_Creator_Id   => $user['id'],
        ];

        // Add idempotency header to test data
        $customTestCase['request']['server'] = $headers;

        $this->ba->batchAuth();

        $this->expectWebhookEventWithContents('payout.initiated',
                                              'testFiringOfWebhookOnPayoutCreationForDashboardBasedBulkPayouts');

        // We are not creating a payouts batch entity, to simulate a dashboard based bulk payout request

        $this->startTest($customTestCase);
    }

    public function testCreatePayoutBatchesXDemoCron()
    {
        $merchant_id = \RZP\Models\Merchant\Account::X_DEMO_PROD_ACCOUNT;

        $x_demo_bank_account = \RZP\Constants\BankingDemo::BANK_ACCOUNT;

        $this->fixtures->merchant->createAccount($merchant_id);

        $this->fixtures->merchant->edit($merchant_id, ['business_banking' => 1]);

        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            400000,$merchant_id);

        $this->fixtures->on('test')->edit('balance',$bankingBalance['id'],[
            'account_number' => $x_demo_bank_account
        ]);

        $this->fixtures->on('test')->merchant->addFeatures([Features::PAYOUTS_BATCH,Features::PAYOUT], $merchant_id);

        $this->ba->cronAuth();

        $this->startTest();
    }

    protected function assertFileContents($fileContent)
    {
        $expectedHeaderRow
            = 'RazorpayX Account Number,Payout Amount,Payout Currency,Payout Mode,Payout Purpose,' .
              'Payout Narration,Payout Reference Id,Fund Account Id,Fund Account Type,Fund Account Name,' .
              'Fund Account Ifsc,Fund Account Number,Fund Account Vpa,Fund Account Phone Number,Fund Account Email,' .
              'Contact Name,Contact Email,Contact Mobile,Contact Type,Contact Reference Id,notes[batch_reference_id]';

        $expectedDataRows = [
            '2224440041626905,1000,INR,NEFT,payout,Acme Corp Fund Transfer,MFN1234,,bank_account,Gaurav Kumar,' .
            'HDFC0001234,1121431121541121,,,,Gaurav Kumar,gaurav.kumar@example.com,9876543210,vendor,' .
            'Acme Contact ID 12345,whu2i2830923ieni',

            '2224440041626905,1000,INR,IMPS,payout,Acme Corp Fund Transfer,Acme Transaction ID 12345,,bank_account,' .
            'Gaurav Kumar,HDFC0001234,1121431121541121,,,,Gaurav Kumar,gaurav.kumar@example.com,9999999999,vendor,' .
            'Acme Contact ID 12345,whu2i2830923ieni',

            '2224440041626905,1000,INR,NEFT,payout,Acme Corp Fund Transfer,MFN12345,fa_TheTestFundAcc,,,,,,,,,,,,,' .
            'whu2i2830923ieni',

            '2224440041626905,1000,INR,amazonpay,refund,Acme Corp Fund Transfer,Acme Transaction ID 12345,,wallet,' .
            'Gaurav Kumar,,,,+919876543210, gaurav.kumar@example.com,Gaurav Kumar,gaurav.kumar@example.com,' .
            '9876543210,employee,Acme Contact ID 12345,whu2i2830923ieni',

            '2224440041626905,1000,INR,UPI,refund,Acme Corp Fund Transfer,Acme Transaction ID 12345,,vpa,,,,' .
            'gauravkumar@exampleupi,,,Gaurav Kumar,gaurav.kumar@example.com,9876543210,self,Acme Contact ID 12345,' .
            'whu2i2830923ieni',
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
