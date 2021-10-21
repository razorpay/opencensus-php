<?php

namespace RZP\Tests\Functional\Batch;

use Hash;
use Mail;
use Mockery;
use RZP\Models\Vpa;
use RZP\Models\Batch;
use RZP\Models\Payout;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Services\RazorXClient;
use RZP\Mail\Batch\PaymentLink;
use RZP\Mail\Batch\PayoutApproval;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\PublicCollection;
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
}
