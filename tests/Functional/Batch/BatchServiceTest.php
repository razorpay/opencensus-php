<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Constants\Mode;
use RZP\Models\Settings;
use RZP\Models\Batch\Header;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;

class BatchServiceTest extends TestCase
{
    use TestsMetrics;
    use BatchTestTrait;
    use CreatesInvoice;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BatchServiceTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function mockRazorX(string $functionName, string $featureName, string $variant)
    {
        $testData = &$this->testData[$functionName];

        $uniqueLocalId = RazorXClient::getLocalUniqueId('10000000000000', $featureName, Mode::TEST);

        $testData['request']['cookies'] = [RazorXClient::RAZORX_COOKIE_KEY => '{"' . $uniqueLocalId . '":"' . $variant . '"}'];
    }


    public function testBatchServiceIsDown()
    {
        $this->fixtures->create(
            'batch',
            [
                'id'          => 'C7e2YqUIpZ2KwZ',
                'type'        => 'payment_link',
                'total_count' => 4,
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    // Merge the result from batch service and api
    public function testBatchServiceGetAllPaymentLinks()
    {
        $batch = $this->fixtures->create(
            'batch',
            [
                'id'          => '00000000000002',
                'type'        => 'payment_link',
                'total_count' => 4,
            ]);

        Settings\Accessor::for($batch, Settings\Module::BATCH)
                         ->upsert([
                                      'sms_notify'   => 0,
                                      'email_notify' => 0,
                                  ])->save();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBatchServiceDownloadBatch()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBatchServiceIllegalDownloadBatch()
    {
        $merchant = $this->fixtures->create('merchant',
            [
                'id'          => '10000000000001',
            ]);

        $user = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_10000000000001', $user->getId());

        $this->startTest();
    }

    public function testBatchCreateToNewBatchService()
    {
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->mockRazorX(__FUNCTION__,  'batch_service_payment_link_migration', "on");

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPayoutApprovalBatchCreate()
    {
        $entries = $this->getDefaultFileEntriesForPayoutApprovals();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->mockRazorX(__FUNCTION__,  'batch_service_payout_approval_migration', "on");

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateLinkedAccountCreateBatch()
    {
        $entries = $this->getFileEntriesForLinkedAccountCreateBatch();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreatePaymentTransferBatch()
    {
        $entries = $this->getFileEntriesForPaymentTransferBatch();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateTransferReversalBatch()
    {
        $entries = $this->getFileEntriesForTransferReversalBatch();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBatchRawAPIGetAllBatches()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBatchAPIGetAllBatchesWithFilters()
    {
        $batch1 = $this->fixtures->create(
            'batch',
            [
                'id'          => '00000000000010',
                'type'        => 'auth_link',
                'total_count' => 5,
            ]);

        $batch2 = $this->fixtures->create(
            'batch',
            [
                'id'          => '00000000000011',
                'type'        => 'auth_link',
                'total_count' => 5,
            ]);


        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBatchAPIGetBatchWithIdAndFilters()
    {
        $batch1 = $this->fixtures->create(
            'batch',
            [
                'id'          => '00000000000012',
                'type'        => 'auth_link',
                'total_count' => 6,
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBatchRawAPIUpdateSettings()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBatchAdminFetchNoResult()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('merchant',['id' => 'CWIYz6Yfu8tqZv']);

        $this->startTest();
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                Header::INVOICE_NUMBER   => '#1',
                Header::CUSTOMER_NAME    => 'test',
                Header::CUSTOMER_EMAIL   => 'test@test.test',
                Header::CUSTOMER_CONTACT => '9999998888',
                Header::AMOUNT           => 100,
                Header::DESCRIPTION      => 'test payment link',
                Header::EXPIRE_BY        => null,
                Header::PARTIAL_PAYMENT  => 'YES',
                'notes[key1]'            => 'Notes Value 1',
                'notes[key2]'            => 'Notes Value 2',
            ],
        ];
    }

    protected function getDefaultFileEntriesForPayoutApprovals()
    {
        return [
            [
                Header::APPROVE_REJECT_PAYOUT   => 'A',
                Header::P_A_PAYOUT_ID    => 'pout_FJR67w0mLwIAHJ',
                Header::P_A_ACCOUNT_NUMBER => '2224440041626905',
                Header::P_A_AMOUNT => 100,
                Header::P_A_CONTACT_ID => 'FJR67hZcn2PX7A',
                Header::P_A_CONTACT_NAME => 'Some name',
                Header::P_A_CREATED_AT => 1596377458,
                Header::P_A_CURRENCY => 'INR',
                Header::P_A_FEES => 0,
                Header::P_A_FUND_ACCOUNT_ID => 'fa_100000000000',
                Header::P_A_MODE => 'live',
                Header::P_A_NOTES => 'notes blah',
                Header::P_A_PURPOSE => 'refund',
                Header::P_A_STATUS => 'pending',
                Header::P_A_TAX => 0,
                Header::P_A_SCHEDULED_AT => '1596377468'
            ]
        ];
    }

    protected function getFileEntriesForLinkedAccountCreateBatch()
    {
        return [
            [
                Header::ACCOUNT_NAME        => 'LA_1',
                Header::ACCOUNT_EMAIL       => 'la.1@rzp.com',
                Header::DASHBOARD_ACCESS    => 0,
                Header::CUSTOMER_REFUNDS    => 0,
                Header::BUSINESS_NAME       => 'Business',
                Header::BUSINESS_TYPE       => 'ngo',
                Header::IFSC_CODE           => 'SBIN0000002',
                Header::ACCOUNT_NUMBER      => '999888777666',
                Header::BENEFICIARY_NAME    => 'Beneficiary',
            ],
            [
                Header::ACCOUNT_NAME        => 'LA_2',
                Header::ACCOUNT_EMAIL       => 'la.2@rzp.com',
                Header::DASHBOARD_ACCESS    => 1,
                Header::CUSTOMER_REFUNDS    => 1,
                Header::BUSINESS_NAME       => 'Another business',
                Header::BUSINESS_TYPE       => 'individual',
                Header::IFSC_CODE           => 'CNRB0000002',
                Header::ACCOUNT_NUMBER      => '9876543210',
                Header::BENEFICIARY_NAME    => 'Another beneficiary',
            ],
        ];
    }

    protected function getFileEntriesForPaymentTransferBatch()
    {
        return [
            [
                Header::PAYMENT_ID_2            => 'pay_abcdefg1234567',
                Header::ACCOUNT_ID              => 'acc_10000000000001',
                Header::AMOUNT_2                => 1000,
                Header::CURRENCY_2              => 'INR',
                Header::TRANSFER_NOTES          => '{"a":"A","b":"B"}',
                Header::LINKED_ACCOUNT_NOTES    => null,
                Header::ON_HOLD                 => null,
                Header::ON_HOLD_UNTIL           => null,
            ],
            [
                Header::PAYMENT_ID_2            => 'pay_hijklmn7654321',
                Header::ACCOUNT_ID              => 'acc_10000000000002',
                Header::AMOUNT_2                => 2500,
                Header::CURRENCY_2              => 'INR',
                Header::TRANSFER_NOTES          => '{"c":"C","d":"D"}',
                Header::LINKED_ACCOUNT_NOTES    => '["c"]',
                Header::ON_HOLD                 => true,
                Header::ON_HOLD_UNTIL           => 1617116116,
            ],
        ];
    }

    public function getFileEntriesForTransferReversalBatch()
    {
        return [
            [
                Header::TRANSFER_ID_2           => 'trf_abcdefg1234567',
                Header::AMOUNT_2                => 1000,
                Header::REVERSAL_NOTES          => '{"a":"A","b":"B"}',
                Header::LINKED_ACCOUNT_NOTES    => null,
            ],
            [
                Header::TRANSFER_ID_2           => 'trf_hijklmn7654321',
                Header::AMOUNT_2                => 2500,
                Header::REVERSAL_NOTES          => '{"c":"C","d":"D"}',
                Header::LINKED_ACCOUNT_NOTES    => '["c"]',
            ],
        ];
    }

    public function testUserIdAndUserSetting()
    {
        $this->ba->batchAuth();

        //X-Idempotent-Key
        $headers = [
            'HTTP_X_Idempotent_Key'    => 'idempotentId',
            'HTTP_X_Creator_Id'        => 'MerchantUser01',
            'HTTP_X_Creator_Type'      => 'user',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $responseFirst = $this->startTest();

        $this->assertEquals($responseFirst['user_id'], 'MerchantUser01');

        $this->assertTrue(isset($responseFirst['user']) === true);

    }

    public function testUserIdAndUserNotSetting()
    {
        //X-Idempotent-Key
        $headers = [
            'HTTP_X_Idempotent_Key'    => 'idempotentId',
            'HTTP_X_Creator_Id'        => 'MerchantUser01',
        ];

        $this->ba->privateAuth();

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $response = $this->startTest();

        $this->assertTrue(array_key_exists('user_id', $response) === false);

        $this->assertTrue(array_key_exists('user', $response) === false);
    }

}
