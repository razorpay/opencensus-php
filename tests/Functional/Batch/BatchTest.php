<?php

namespace RZP\Tests\Functional\Batch;

use Hash;
use Mail;
use Closure;
use Mockery;
use RZP\Models\Item;
use RZP\Exception\AssertionException;
use RZP\Models\Vpa;
use RZP\Models\Feature\Constants;
use RZP\Models\Batch;
use RZP\Models\Payout;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Services\RazorXClient;
use RZP\Mail\Batch\PaymentLink;
use RZP\Mail\Batch\PayoutApproval;
use RZP\Tests\Functional\Assertion\Assertion;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\RazorxTreatment;
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
    use WebhookTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BatchTestData.php';

        parent::setUp();

    }

    public function testValidateFileName()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id' => '10000000000000',
            'business_type' => '2',
        ]);

        $this->mockBatchService();

        $this->startTest();
    }

    public function testValidateFileNameWithWrongBatchTypeId()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id' => '10000000000000',
            'business_type' => '2',
        ]);

        $this->mockBatchService();

        $this->startTest();
    }

    public function testValidateFileNameWithIncorrectMerchantAuth()
    {

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id' => '10000000000000',
            'business_type' => '2',
        ]);

        $this->mockBatchService();

        $this->startTest();
    }

    public function testValidateFileNameWithoutBatchTypePassed()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id' => '10000000000000',
            'business_type' => '2',
        ]);

        $this->mockBatchService();

        $this->startTest();
    }

    public function testValidateFileNameWithoutFileNamePassed()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id' => '10000000000000',
            'business_type' => '2',
        ]);

        $this->mockBatchService();

        $this->startTest();
    }

    protected function mockBatchService()
    {
        $response = ['file_exists' => true];
        $mock = Mockery::mock(BatchMicroService::class)->makePartial();
        $this->app->instance('batchService', $mock);

        $mock->shouldAllowMockingMethod('validateFileName')
            ->shouldReceive('validateFileName')
            ->andReturn($response);
    }

    public function testSendMailFromBatchService()
    {
        $this->ba->proxyAuth();

        Mail::fake();

        $this->ba->batchAppAuth();

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

    public function testSendSMSFromBatchService()
    {
        $this->ba->proxyAuth();

        $this->mockStorkService();

        $this->ba->batchAppAuth();

        $merchant = $this->fixtures->create('merchant', ['id' => 'CVuOcOYoUiAqNY']);
        $this->fixtures->create('merchant_detail', [
            'contact_mobile' => '9876543210',
            'merchant_id' => $merchant->getId(),
        ]);

        $this->startTest();

    }

    public function testSendSMSFromBatchServiceInvalidTemplate()
    {
        $this->ba->proxyAuth();

        $this->mockStorkService();

        $this->ba->batchAppAuth();

        $this->fixtures->create('merchant', ['id' => 'CVuOcOYoUiAqNY']);

        $this->startTest();

    }

    public function testPayoutApprovalSendMailFromBatchService()
    {
        $this->ba->proxyAuth();

        Mail::fake();

        $this->ba->batchAppAuth();

        $this->fixtures->create('merchant', ['id' => 'CVuOcOYoUiAqNY']);

        $this->writeToCsvFile([], 'payment', null, 'files/filestore/batch/download');

        $this->startTest();

        Mail::assertSent(PayoutApproval::class, function ($mail)
        {
            $this->assertNotEmpty($mail->attachments);

            $body = 'Please find attached processed payouts file.';

            $this->assertEquals($body, $mail->viewData['body']);

            return true;
        });
    }


    protected function getFileEntries(string $callee): array
    {
        return $this->testData["{$callee}RequestFileEntries"];
    }

    public function testCreateAdminBatchWithRequiredPermission()
    {
        $this->ba->proxyAuth();

        /** @var PublicCollection $permissions */
        $permissions = $this->getDbEntities('permission', [
                                'name'  => 'admin_batch_create' ,
                            ]);

        $role = $this->fixtures->create('role', [
            'id'     => 'rzpMngerRoleId',
            'org_id' => '100000razorpay',
            'name'   => 'random',
        ]);

        $permissions->push($this->getDbEntities('permission', [
                                    'name'  => 'adjustment_batch_upload' ,
                                ])->first());

        $role->permissions()->attach($permissions->pluck('id'));

        $admin = $this->fixtures->create('admin', [
            'org_id'    => '100000razorpay',
        ]);

        $admin->roles()->attach($role);

        $adminToken = $this->fixtures->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->createAndPutCsvFileInRequest([
            ['reference_id' => 'ref_21',
            'merchant_id'   => '100000razorpay',
            'amount'        => -1200,
            'balance_type'  => '  ', // defaults to primary
            'description'   => 'loan payment'],
        ], __FUNCTION__);

        $this->ba->adminAuth('test', $token);

        $this->startTest();
    }

    public function testCreateAdminBatchWithoutRequiredPermission()
    {
        $this->ba->proxyAuth();

        // adjustment batch requires admin to have `adjustment_batch_upload`.
        // in this test, admin does not has that permission.
        // batch is not created and error is return while creating batch.

        $permissions = $this->getDbEntities('permission', [
                                'name'  => 'admin_batch_create',
                            ])->pluck('id');

        $role = $this->fixtures->create('role', [
            'id'     => 'rzpMngerRoleId',
            'org_id' => '100000razorpay',
            'name'   => 'random',
        ]);

        $role->permissions()->attach($permissions);

        $admin = $this->fixtures->create('admin', [
            'org_id'    => '100000razorpay',
        ]);

        $admin->roles()->attach($role);

        $adminToken = $this->fixtures->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->createAndPutTxtFileInRequest('file', 'some data', __FUNCTION__);

        $this->ba->adminAuth('test', $token);

        $this->startTest();
    }

    public function testPartnerSubmerchantInviteCapitalBulkCSVFileValidate()
    {
        $this->ba->proxyAuth();

        $entries = $this->getPartnerSubmerchantInviteCapitalBulkEntries();

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testPartnerSubmerchantInviteCapitalBulkCSVWithCommaFileValidate()
    {
        $this->ba->proxyAuth();

        $entries = $this->getPartnerSubmerchantInviteCapitalBulkEntries();

        $testData = $this->testData["testPartnerSubmerchantInviteCapitalBulkCSVFileValidate"];

        $testData["response"]["content"]["parsed_entries"][0]["company_address_line_2"] = "\"Major Industry Area, Major Industry Place\"";

        $entries[0][Batch\Header::COMPANY_ADDRESS_LINE_2] = "Major Industry Area, Major Industry Place";

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest($testData);
    }


    public function setupPaymentPageWithItemsForBatchValidate()
    {
        $this->fixtures->merchant->addFeatures([Constants::FILE_UPLOAD_PP]);

        $testData = $this->testData['setUpPaymentPageForFileUpload'];

        $testData['request']['content']['payment_page_items'] = [
            [
                'item' => [
                    'name'        =>  'item1',
                    Item\Entity::AMOUNT => 5000,
                    'currency'    => 'INR',
                ],
                'mandatory'         => true,
            ],
            [
                'item' => [
                    'name'        =>  'testName2',
                    Item\Entity::AMOUNT => 10000,
                    'currency'    => 'INR',
                ],
                'mandatory'         => false,
            ]
        ];

        $resp = $this->startTest($testData);

        return $resp["id"];
    }

    // send all fields
    public function testFormBuilderBatchValidatePositive1()
    {
        $this->ba->proxyAuth();

        $id = $this->setupPaymentPageWithItemsForBatchValidate();

        $entries = $this->getFormBuilderBatchEntries();

        $this->testData[__FUNCTION__]['request']['content']['config']['payment_page_id'] = $id;

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    // send only required fields
    public function testFormBuilderBatchValidatePositive2()
    {
        $this->ba->proxyAuth();

        $id = $this->setupPaymentPageWithItemsForBatchValidate();

        $entries = $this->getFormBuilderBatchEntries();

        unset($entries[0]['testName2'], $entries[0]['Address 2']);

        $this->testData[__FUNCTION__]['request']['content']['config']['payment_page_id'] = $id;

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    // missing required field from usf_schema
    public function testFormBuilderBatchValidateNegative1()
    {
        $this->ba->proxyAuth();

        $id = $this->setupPaymentPageWithItemsForBatchValidate();

        $entries = $this->getFormBuilderBatchEntries();

        unset($entries[0]['contact']);

        $this->testData[__FUNCTION__]['request']['content']['config']['payment_page_id'] = $id;

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    // missing required field from payment_page_items
    public function testFormBuilderBatchValidateNegative2()
    {
        $this->ba->proxyAuth();

        $id = $this->setupPaymentPageWithItemsForBatchValidate();

        $entries = $this->getFormBuilderBatchEntries();

        unset($entries[0]['item1']);

        $this->testData[__FUNCTION__]['request']['content']['config']['payment_page_id'] = $id;

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testPLBulkBatchCreateForValidUserRoles()
    {
        $this->ba->proxyAuth();

        $entries = [
            [
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME        => 'Amit',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER      => '9876543210',
                Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL       => 'amit@razorpay.com',
                Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC         => 'testing',
                Batch\Header::CONTACT_TYPE                         => 'employee',
                Batch\Header::PAYOUT_LINK_BULK_AMOUNT              => 1000,
                Batch\Header::PAYOUT_LINK_BULK_SEND_SMS            => 'Yes',
                Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL          => 'Yes',
                Batch\Header::PAYOUT_PURPOSE                       => 'refund',
                Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID        => 'REFERENCE01',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE         => 'test key',
                Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC          => 'test value',
            ],
        ];

        $this->mockRazorXTreatmentAccessDenyUnauthorised('on');

        $validRoles = ['owner', 'admin', 'finance_l1', 'operations'];

        foreach ($validRoles as $role)
        {
            $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

            $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], $role);

            $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

            $this->ba->addXOriginHeader();

            $this->startTest();
        }
    }

    protected function mockRazorXTreatmentAccessDenyUnauthorised($value = 'on')
    {
        $this->ba->proxyAuth();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) use ($value)
                {
                    if ($feature === Merchant\RazorxTreatment::RAZORPAY_X_ACL_DENY_UNAUTHORISED)
                    {
                        return $value;
                    }

                    return 'off';
                }));
    }
    public function testRecuringAxisChargeBatch()
    {
        $this->enableRazorxBatchAxisValidation();
        $this->ba->proxyAuth();
        $entries = $this->getRecurringAxisChargeBatch();
        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);
        $this->startTest();
    }

    public function testInvalidRecuringAxisChargeBatch()
    {
        $this->enableRazorxBatchAxisValidation();
        $this->ba->proxyAuth();
        $entries = $this->getRecurringAxisChargeBatch();
        $this->createAndPutTwoSheetsExcelFileInRequest($entries, __FUNCTION__);

        $testData=$this->testData[__FUNCTION__];
        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->startTest($testData);
            },
            AssertionException::class,
            'More than one Excel Sheet Found');

    }

    protected function enableRazorxBatchAxisValidation()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function($mid, $feature, $mode) {
                    if ($feature === RazorxTreatment::DUPLICATE_SHEET_VALIDATION_BATCH)
                    {
                        return 'on';
                    }

                    return 'off';
                }));
    }
    protected function makeRequestAndCatchException(
        Closure $closure,
        string $exceptionClass = AssertionException::class,
        string $exceptionMessage = null)
    {
        try
        {
            $closure();
        }
        catch (AssertionException $e)
        {
            $this->assertExceptionClass($e, $exceptionClass);

            if ($exceptionMessage !== null)
            {
                $this->assertSame($exceptionMessage, $e->getMessage());
            }

            return;
        }
        $this->fail('Expected exception ' . $exceptionClass . ' was not thrown');
    }
}
