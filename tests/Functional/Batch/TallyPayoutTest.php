<?php
namespace RZP\Tests\Functional\Batch;

use Carbon\Carbon;
use Hash;
use RZP\Models\Admin;
use RZP\Models\Admin\Permission\Name as AdminPermission;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Validator;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\TestCase;


class TallyPayoutTest extends TestCase
{
    use BatchTestTrait;
    use TestsBusinessBanking;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/TallyPayoutTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->ba->proxyAuth();

        // Below timestamp is of Jan 1, 2010. This shall ensure that all merchants act as new merchants.
        // The above timestamp is what decides if a merchant is supposed to be considered as a new merchant
        // who got onboarded after Bulk Improvements project or an existing bulk merchant.
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::BULK_PAYOUTS_NEW_MERCHANT_CUTOFF_TIMESTAMP => 1262304000]);

        $this->mockRazorxTreatment();

        $this->merchant = $this->getDbEntityById('merchant', '10000000000000');
    }

    public function testTallyPayoutBatchValidate()
    {
        $entries = [
            [
                Header::RAZORPAYX_ACCOUNT_NUMBER => '2323230041626905',
                Header::PAYOUT_PURPOSE           => 'refund',
                Header::PAYOUT_REFERENCE_ID      => 'test_reference_id',
                Header::PAYOUT_MODE              => 'NEFT',
                Header::PAYOUT_AMOUNT_RUPEES     => '1.78',
                Header::PAYOUT_CURRENCY          => 'INR',
                Header::PAYOUT_DATE              => '12/02/2021',
                Header::PAYOUT_NARRATION         => 'test narration',
                Header::FUND_ACCOUNT_TYPE        => 'bank_account',
                Header::FUND_ACCOUNT_NAME        => 'Test Batch FA',
                Header::FUND_ACCOUNT_IFSC        => 'SBIN0010720',
                Header::FUND_ACCOUNT_NUMBER      => '00100200300400',
                Header::FUND_ACCOUNT_VPA         => '',
                Header::CONTACT_NAME_2           => 'Test Contact Batch',
                Header::CONTACT_TYPE             => 'employee',
                Header::CONTACT_ADDRESS          => '',
                Header::CONTACT_CITY             => '',
                Header::CONTACT_ZIPCODE          => '',
                Header::CONTACT_STATE            => '',
                Header::CONTACT_EMAIL_2          => 'testcontact@batch.com',
                Header::CONTACT_MOBILE_2         => '',
                Header::NOTES_STR_VALUE          => '',
            ],
            [ // this entry will fail because narration has an invalid character
                Header::RAZORPAYX_ACCOUNT_NUMBER => '2323230041626905',
                Header::PAYOUT_PURPOSE           => 'refund',
                Header::PAYOUT_REFERENCE_ID      => 'test_reference_id',
                Header::PAYOUT_MODE              => 'NEFT',
                Header::PAYOUT_AMOUNT_RUPEES     => '100',
                Header::PAYOUT_CURRENCY          => 'INR',
                Header::PAYOUT_DATE              => '12/12/2021',
                Header::PAYOUT_NARRATION         => 'test_narration',
                Header::FUND_ACCOUNT_TYPE        => 'bank_account',
                Header::FUND_ACCOUNT_NAME        => 'Test Batch FA',
                Header::FUND_ACCOUNT_IFSC        => 'SBIN0010720',
                Header::FUND_ACCOUNT_NUMBER      => '00100200300400',
                Header::FUND_ACCOUNT_VPA         => '',
                Header::CONTACT_NAME_2           => 'Test Contact Batch',
                Header::CONTACT_TYPE             => 'employee',
                Header::CONTACT_ADDRESS          => '',
                Header::CONTACT_CITY             => '',
                Header::CONTACT_ZIPCODE          => '',
                Header::CONTACT_STATE            => '',
                Header::CONTACT_EMAIL_2          => 'testcontact@batch.com',
                Header::CONTACT_MOBILE_2         => '',
                Header::NOTES_STR_VALUE          => '',
            ],
            [ // this entry will fail because email is not valid
                Header::RAZORPAYX_ACCOUNT_NUMBER => '2323230041626905',
                Header::PAYOUT_PURPOSE           => 'refund',
                Header::PAYOUT_REFERENCE_ID      => 'test_reference_id',
                Header::PAYOUT_MODE              => 'NEFT',
                Header::PAYOUT_AMOUNT_RUPEES     => '100',
                Header::PAYOUT_CURRENCY          => 'INR',
                Header::PAYOUT_DATE              => '12/12/2021',
                Header::PAYOUT_NARRATION         => 'test narration',
                Header::FUND_ACCOUNT_TYPE        => 'bank_account',
                Header::FUND_ACCOUNT_NAME        => 'Test Batch FA',
                Header::FUND_ACCOUNT_IFSC        => 'SBIN0010720',
                Header::FUND_ACCOUNT_NUMBER      => '00100200300400',
                Header::FUND_ACCOUNT_VPA         => '',
                Header::CONTACT_NAME_2           => 'Test Contact Batch',
                Header::CONTACT_TYPE             => 'employee',
                Header::CONTACT_ADDRESS          => '',
                Header::CONTACT_CITY             => '',
                Header::CONTACT_ZIPCODE          => '',
                Header::CONTACT_STATE            => '',
                Header::CONTACT_EMAIL_2          => 'testcontact',
                Header::CONTACT_MOBILE_2         => '',
                Header::NOTES_STR_VALUE          => '',
            ],
            [ // invalid fa type
                Header::RAZORPAYX_ACCOUNT_NUMBER => '2323230041626905',
                Header::PAYOUT_PURPOSE           => 'refund',
                Header::PAYOUT_REFERENCE_ID      => 'test_reference_id',
                Header::PAYOUT_MODE              => 'NEFT',
                Header::PAYOUT_AMOUNT_RUPEES     => '100',
                Header::PAYOUT_CURRENCY          => 'INR',
                Header::PAYOUT_DATE              => '12/12/2021',
                Header::PAYOUT_NARRATION         => 'test narration',
                Header::FUND_ACCOUNT_TYPE        => 'not_abank_account',
                Header::FUND_ACCOUNT_NAME        => 'Test Batch FA',
                Header::FUND_ACCOUNT_IFSC        => 'SBIN0010720',
                Header::FUND_ACCOUNT_NUMBER      => '00100200300400',
                Header::FUND_ACCOUNT_VPA         => '',
                Header::CONTACT_NAME_2           => 'Test Contact Batch',
                Header::CONTACT_TYPE             => 'employee',
                Header::CONTACT_ADDRESS          => '',
                Header::CONTACT_CITY             => '',
                Header::CONTACT_ZIPCODE          => '',
                Header::CONTACT_STATE            => '',
                Header::CONTACT_EMAIL_2          => 'testcontact@batch.com',
                Header::CONTACT_MOBILE_2         => '',
                Header::NOTES_STR_VALUE          => '',
            ],
            [ // fails because both fa-name and contact-name are empty
                Header::RAZORPAYX_ACCOUNT_NUMBER => '2323230041626905',
                Header::PAYOUT_PURPOSE           => 'refund',
                Header::PAYOUT_REFERENCE_ID      => 'test_reference_id',
                Header::PAYOUT_MODE              => 'NEFT',
                Header::PAYOUT_AMOUNT_RUPEES     => '100',
                Header::PAYOUT_CURRENCY          => 'INR',
                Header::PAYOUT_DATE              => '12/12/2021',
                Header::PAYOUT_NARRATION         => 'test narration',
                Header::FUND_ACCOUNT_TYPE        => 'bank_account',
                Header::FUND_ACCOUNT_NAME        => '',
                Header::FUND_ACCOUNT_IFSC        => 'SBIN0010720',
                Header::FUND_ACCOUNT_NUMBER      => '00100200300400',
                Header::FUND_ACCOUNT_VPA         => '',
                Header::CONTACT_NAME_2           => '',
                Header::CONTACT_TYPE             => 'employee',
                Header::CONTACT_ADDRESS          => '',
                Header::CONTACT_CITY             => '',
                Header::CONTACT_ZIPCODE          => '',
                Header::CONTACT_STATE            => '',
                Header::CONTACT_EMAIL_2          => 'testcontact@batch.com',
                Header::CONTACT_MOBILE_2         => '',
                Header::NOTES_STR_VALUE          => '',
            ],
            [ // fails because phone number is not numeric
                Header::RAZORPAYX_ACCOUNT_NUMBER => '2323230041626905',
                Header::PAYOUT_PURPOSE           => 'refund',
                Header::PAYOUT_REFERENCE_ID      => 'test_reference_id',
                Header::PAYOUT_MODE              => 'NEFT',
                Header::PAYOUT_AMOUNT_RUPEES     => '100',
                Header::PAYOUT_CURRENCY          => 'INR',
                Header::PAYOUT_DATE              => '12/12/2021',
                Header::PAYOUT_NARRATION         => 'test narration',
                Header::FUND_ACCOUNT_TYPE        => 'bank_account',
                Header::FUND_ACCOUNT_NAME        => 'Test Batch FA',
                Header::FUND_ACCOUNT_IFSC        => 'SBIN0010720',
                Header::FUND_ACCOUNT_NUMBER      => '00100200300400',
                Header::FUND_ACCOUNT_VPA         => '',
                Header::CONTACT_NAME_2           => 'Contact',
                Header::CONTACT_TYPE             => 'employee',
                Header::CONTACT_ADDRESS          => '',
                Header::CONTACT_CITY             => '',
                Header::CONTACT_ZIPCODE          => '',
                Header::CONTACT_STATE            => '',
                Header::CONTACT_EMAIL_2          => 'testcontact@batch.com',
                Header::CONTACT_MOBILE_2         => '12nk1n',
                Header::NOTES_STR_VALUE          => '',
            ],
            [ // fails because amount is less than 1 rupee
                Header::RAZORPAYX_ACCOUNT_NUMBER => '2323230041626905',
                Header::PAYOUT_PURPOSE           => 'refund',
                Header::PAYOUT_REFERENCE_ID      => 'test_reference_id',
                Header::PAYOUT_MODE              => 'NEFT',
                Header::PAYOUT_AMOUNT_RUPEES     => '0.90',
                Header::PAYOUT_CURRENCY          => 'INR',
                Header::PAYOUT_DATE              => '12/12/2021',
                Header::PAYOUT_NARRATION         => 'test narration',
                Header::FUND_ACCOUNT_TYPE        => 'bank_account',
                Header::FUND_ACCOUNT_NAME        => 'Test Batch FA',
                Header::FUND_ACCOUNT_IFSC        => 'SBIN0010720',
                Header::FUND_ACCOUNT_NUMBER      => '00100200300400',
                Header::FUND_ACCOUNT_VPA         => '',
                Header::CONTACT_NAME_2           => 'Contact',
                Header::CONTACT_TYPE             => 'employee',
                Header::CONTACT_ADDRESS          => '',
                Header::CONTACT_CITY             => '',
                Header::CONTACT_ZIPCODE          => '',
                Header::CONTACT_STATE            => '',
                Header::CONTACT_EMAIL_2          => 'testcontact@batch.com',
                Header::CONTACT_MOBILE_2         => '',
                Header::NOTES_STR_VALUE          => '',
            ],
            [ // fails because invalid payout date format
                Header::RAZORPAYX_ACCOUNT_NUMBER => '2323230041626905',
                Header::PAYOUT_PURPOSE           => 'refund',
                Header::PAYOUT_REFERENCE_ID      => 'test_reference_id',
                Header::PAYOUT_MODE              => 'NEFT',
                Header::PAYOUT_AMOUNT_RUPEES     => '1.90',
                Header::PAYOUT_CURRENCY          => 'INR',
                Header::PAYOUT_DATE              => '29/02/2021',
                Header::PAYOUT_NARRATION         => 'test narration',
                Header::FUND_ACCOUNT_TYPE        => 'bank_account',
                Header::FUND_ACCOUNT_NAME        => 'Test Batch FA',
                Header::FUND_ACCOUNT_IFSC        => 'SBIN0010720',
                Header::FUND_ACCOUNT_NUMBER      => '00100200300400',
                Header::FUND_ACCOUNT_VPA         => '',
                Header::CONTACT_NAME_2           => 'Contact',
                Header::CONTACT_TYPE             => 'employee',
                Header::CONTACT_ADDRESS          => '',
                Header::CONTACT_CITY             => '',
                Header::CONTACT_ZIPCODE          => '',
                Header::CONTACT_STATE            => '',
                Header::CONTACT_EMAIL_2          => 'testcontact@batch.com',
                Header::CONTACT_MOBILE_2         => '',
                Header::NOTES_STR_VALUE          => '',
            ],
            [ // fails because payout amount has 3 decimals
                Header::RAZORPAYX_ACCOUNT_NUMBER => '2323230041626905',
                Header::PAYOUT_PURPOSE           => 'refund',
                Header::PAYOUT_REFERENCE_ID      => 'test_reference_id',
                Header::PAYOUT_MODE              => 'NEFT',
                Header::PAYOUT_AMOUNT_RUPEES     => '121.901',
                Header::PAYOUT_CURRENCY          => 'INR',
                Header::PAYOUT_DATE              => '12/12/2021',
                Header::PAYOUT_NARRATION         => 'test narration',
                Header::FUND_ACCOUNT_TYPE        => 'bank_account',
                Header::FUND_ACCOUNT_NAME        => 'Test Batch FA',
                Header::FUND_ACCOUNT_IFSC        => 'SBIN0010720',
                Header::FUND_ACCOUNT_NUMBER      => '00100200300400',
                Header::FUND_ACCOUNT_VPA         => '',
                Header::CONTACT_NAME_2           => 'Contact',
                Header::CONTACT_TYPE             => 'employee',
                Header::CONTACT_ADDRESS          => '',
                Header::CONTACT_CITY             => '',
                Header::CONTACT_ZIPCODE          => '',
                Header::CONTACT_STATE            => '',
                Header::CONTACT_EMAIL_2          => 'testcontact@batch.com',
                Header::CONTACT_MOBILE_2         => '',
                Header::NOTES_STR_VALUE          => '',
            ],
        ];

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPayoutDateValidator()
    {
        $tests = [
            "12/12/2019" => true,
            "12/21/2019" => false,
            "12/21/0001" => false,
            "29/02/2012" => true,
            "29/02/2013" => false,
            "01/02/2021" => true,
        ];

        foreach($tests as $date => $value)
        {
            $exceptionExpected = !$value;

            $exceptionOccurred = false;
            try
            {
                (new Validator())->validatePayoutDate("", $date);
            }
            catch(\Exception $e)
            {
                $exceptionOccurred = true;
            }
            finally
            {
                $this->assertEquals($exceptionExpected, $exceptionOccurred);
            }
        }
    }

    public function testCreateAdminBatchWithoutRequiredPermission()
    {
        $org = $this->fixtures->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
            'auth_type'     => 'password',
        ]);

        $admin = $this->fixtures->create('admin', [
            'org_id' => $org->getId(),
            'username' => 'auth admin',
            'password' => 'Heimdall!234',
        ]);

        $role = $this->fixtures->create('role', [
            'org_id' => $org->getId(),
            'name'   => 'Test Role',
        ]);

        $permissionEntity = $this->fixtures->create('permission',[
            'name'   => AdminPermission::ADMIN_BATCH_CREATE,
        ]);

        $role->permissions()->attach($permissionEntity->getId());

        $admin->roles()->attach($role);

        $authToken = $this->getAuthTokenForAdmin($admin);

        $this->ba->adminAuth('test', $authToken, $org->getPublicId());

        $this->startTest();
    }

    public function testCreateAdminBatchWithPermission()
    {

        $org = $this->fixtures->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
            'auth_type'     => 'password',
        ]);

        $admin = $this->fixtures->create('admin', [
            'org_id' => $org->getId(),
            'username' => 'auth admin',
            'password' => 'Heimdall!234',
        ]);

        $role = $this->fixtures->create('role', [
            'org_id' => $org->getId(),
            'name'   => 'Test Role',
        ]);

        $adminBatchPerm = $this->fixtures->create('permission',[
            'name'   => AdminPermission::ADMIN_BATCH_CREATE,
        ]);

        $plBatchPerm = $this->fixtures->create('permission',[
            'name'   => AdminPermission::TALLY_PAYOUT_BULK_CREATE,
        ]);

        $role->permissions()->attach($adminBatchPerm->getId());

        $role->permissions()->attach($plBatchPerm->getId());

        $admin->roles()->attach($role);

        $authToken = $this->getAuthTokenForAdmin($admin);

        $this->ba->adminAuth('test', $authToken, $org->getPublicId());

        $this->startTest();
    }

    protected function getAuthTokenForAdmin($admin)
    {
        $now = Carbon::now();

        $bearerToken = 'ThisIsATokenFORAdmin';

        $adminToken = $this->fixtures->create('admin_token', [
            'admin_id' => $admin->getId(),
            'token' => Hash::make($bearerToken),
            'created_at' => $now->timestamp,
            'expires_at' => $now->addDays(2)->timestamp,
        ]);

        return $bearerToken . $adminToken->getId();
    }
}
