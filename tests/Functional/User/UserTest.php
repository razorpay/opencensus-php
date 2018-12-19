<?php

namespace RZP\Tests\Functional\User;

use DB;
use Mail;
use Hash;
use Carbon\Carbon;

use RZP\Mail\User\Otp;
use RZP\Models\User\Constants;
use RZP\Mail\User\PasswordReset;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\User\AccountVerification;
use RZP\Models\User\Entity as UserEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Fixtures\Entity\User as UserFixture;

class UserTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/UserTestData.php';

        parent::setUp();
    }

    public function testCreate()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGet()
    {
        $user = $this->fixtures->create('user');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . $user['id'];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testLogin()
    {
        $user = $this->fixtures->create('user', ['password' => 'hello123']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'     => $user['email'],
            'password'  => 'hello123'
        ];

        $testData['request']['content'] = $content;

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testFailedLogin()
    {
        $user = $this->fixtures->create('user', ['password' => 'hello123']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello1234'
        ];

        $testData['request']['content'] = $content;

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testConfirmByToken()
    {
        $user = $this->fixtures->create('user', ['confirm_token' => 'confirm_token']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'confirm_token' => 'confirm_token'
        ];

        $testData['request']['content'] = $content;

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testConfirmByInvalidToken()
    {
        $user = $this->fixtures->create('user', ['confirm_token' => 'confirm_token']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'confirm_token' => ''
        ];

        $testData['request']['content'] = $content;

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testConfirmByEmail()
    {
        $this->ba->adminAuth();

        $user = $this->fixtures->create('user', ['confirm_token' => 'confirm_token']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email' => $user['email']
        ];

        $testData['request']['content'] = $content;

        $this->startTest();
    }

    /**
     * Asserts usual edit operation. Additionally asserts that editing mobile causes verified flag to be marked false.
     */
    public function testEdit()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE_VERIFIED => 1]);

        $this->ba->proxyAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertFalse($user->isContactMobileVerified());
    }

    public function testChangePassword()
    {
        $user = $this->fixtures->create('user', ['password' => '12345']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'password'              => 'hello123',
            'password_confirmation' => 'hello123',
            'old_password'          => '12345',
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/password';

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testChangeInvalidPassword()
    {
        $user = $this->fixtures->create('user', ['password' => '12345']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'password'              => 'hello1234',
            'password_confirmation' => 'hello123',
            'old_password'          => '12345',
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/password';

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testAttachMerchant()
    {
        $user = $this->fixtures->create('user');

        $ownerUser = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $mappingData['user_id'] = $user['id'];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'        => 'manager',
            'merchant_id' => $merchant['id']
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/attach';

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $ownerUser['id'];

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();

        $merchants = DB::table('merchant_users')
                       ->where('user_id', '=', $user['id'])
                       ->pluck('merchant_id', 'role');

        $this->assertEquals(count($merchants), 2);

        $this->assertEquals($merchants['manager'], $merchant['id']);
    }

    public function testDetachMerchant()
    {
        $user = $this->fixtures->create('user');

        $ownerUser = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->createUserMerchantMapping($user['id'], $merchant['id'], 'owner');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'        => 'owner',
            'merchant_id' => $merchant['id']
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/detach';

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $ownerUser['id'];

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();

        $merchants = DB::table('merchant_users')
                        ->where('user_id', '=', $user['id'])
                        ->pluck('merchant_id', 'role');

        $this->assertEquals(count($merchants), 1);
    }

    public function testUpdateMerchant()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $ownerUser = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->createUserMerchantMapping($user['id'], $merchant['id'], 'owner');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'        => 'manager',
            'merchant_id' => $merchant['id']
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/update';

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $ownerUser['id'];

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();

        $merchants = DB::table('merchant_users')
                        ->where('user_id', '=', $user['id'])
                        ->pluck('merchant_id', 'role');

        $this->assertEquals(count($merchants), 2);

        $this->assertEquals($merchants['manager'], $merchant['id']);
    }

    protected function createUserMerchantMapping(string $userId, string $merchantId, string $role)
    {
        DB::table('merchant_users')
            ->insert(
                [
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'role'        => $role,
                'created_at'  => 1493805150,
                'updated_at'  => 1493805150
                ]
            );
    }

    public function testResendVerificationMail()
    {
        Mail::fake();

        $user = $this->fixtures->create('user', [
            'id' => '12398102831231',
            'confirm_token' => 'testingtestingtesting',
        ]);

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $this->ba->appAuth();

        $this->startTest();

        Mail::assertQueued(AccountVerification::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertArrayHasKey('org', $viewData);
            $this->assertArrayHasKey('token', $viewData);

            $this->assertEquals('emails.user.account_verification', $mail->view);

            return true;
        });
    }

    public function testPasswordResetMail()
    {
        Mail::fake();

        $this->fixtures->create('user', ['email' => 'resetpass@razorpay.com']);

        $this->ba->appAuth();

        $this->startTest();

        Mail::assertQueued(PasswordReset::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertArrayHasKey('org', $viewData);
            $this->assertArrayHasKey('token', $viewData);
            $this->assertArrayHasKey('email', $viewData);

            $this->assertEquals('emails.user.password_reset', $mail->view);

            return true;
        });
    }

    public function testPasswordResetByToken()
    {
        $user = $this->fixtures->create('user', [
                    'email'                 => 'resetpass@razorpay.com',
                    'password_reset_token'  => str_random(50),
                    'password_reset_expiry' => Carbon::now()->timestamp + Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME,
                ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['email']      = $user->getEmail();
        $testData['request']['content']['token']      = $user->getPasswordResetToken();

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testPasswordResetByExpiredToken()
    {
        $user = $this->fixtures->create('user', [
            'email'                 => 'resetpass@razorpay.com',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp - 1,
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['email']      = $user->getEmail();
        $testData['request']['content']['token']      = $user->getPasswordResetToken();

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testPasswordResetByUsedToken()
    {
        $user = $this->fixtures->create('user', [
            'email'                 => 'resetpass@razorpay.com',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp + Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME,
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['email']      = $user->getEmail();
        $testData['request']['content']['token']      = $user->getPasswordResetToken();

        $this->ba->appAuth();

        $this->makeRequestAndGetContent($testData['request']);

        $this->startTest();
    }

    public function testSendOtp()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testSendOtpViaMail()
    {
        Mail::fake();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(Otp::class, function ($mail)
        {
            $this->assertEquals('create payout', $mail->action);
            $this->assertNotEmpty($mail->user);
            $this->assertNotEmpty($mail->otp);
            $this->assertEquals('emails.user.otp', $mail->view);
            return true;
        });
    }

    public function testSendOtpWithInvalidAction()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSendOtpViaMailToVerifyContact()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSendOtpToVerifyContactWhenAlreadyVerified()
    {
        $this->fixtures->edit(
            'user',
            UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE          => '123456789',
                UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSendOtpViaSmsWhenContactDoesNotExist()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSendOtpViaSmsWhenContactIsNotVerified()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testVerifyContactWithOtp()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $this->ba->proxyAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertTrue($user->isContactMobileVerified());
    }

    public function testVerifyContactWithInvalidOtp()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testVerifyContactWithInvalidToken()
    {
        $this->markTestSkipped('Todo: Not possible with current implementation!');
    }
}
