<?php

namespace RZP\Tests\Functional\Batch;

use Hash;
use Mail;

use RZP\Mail\Batch\PayoutApproval;
use RZP\Models\Vpa;
use RZP\Models\Payout;
use RZP\Models\BankAccount;
use RZP\Mail\Batch\PaymentLink;
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

    public function testPayoutApprovalSendMailFromBatchService()
    {

        Mail::fake();

        $this->ba->appAuth();

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
}
