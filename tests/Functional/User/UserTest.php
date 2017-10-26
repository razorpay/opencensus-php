<?php

namespace RZP\Tests\Functional\User;

use DB;
use Mail;
use RZP\Tests\Functional\TestCase;
use Illuminate\Hashing\BcryptHasher;
use RZP\Mail\User\AccountVerification;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class UserTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/UserTestData.php';

        parent::setUp();
    }

    public function testCreate()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testGet()
    {
        $user = $this->fixtures->create('user');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . $user['id'];

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testLogin()
    {
        $password = (new BcryptHasher)->make('hello123');

        $user = $this->fixtures->create('user', ['password' => $password]);

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
        $password = (new BcryptHasher)->make('hello123');

        $user = $this->fixtures->create('user', ['password' => $password]);

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
        $user = $this->fixtures->create('user', ['confirm_token' => 'confirm_token']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email' => $user['email']
        ];

        $testData['request']['content'] = $content;

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testEdit()
    {
        $user = $this->fixtures->create('user');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'name' => 'hello'
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'];

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testChangePassword()
    {
        $user = $this->fixtures->create('user');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'password'              => 'hello123',
            'password_confirmation' => 'hello123'
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/password';

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testChangeInvalidPassword()
    {
        $user = $this->fixtures->create('user');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'password'              => 'hello1234',
            'password_confirmation' => 'hello123'
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/password';

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testAttachMerchant()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'        => 'owner1',
            'merchant_id' => $merchant['id']
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/attach';

        $this->ba->appAuth();

        $this->startTest();

        $merchants = DB::table('merchant_users')
                       ->where('user_id', '=', $user['id'])
                       ->pluck('merchant_id', 'role');

        $this->assertEquals(count($merchants), 2);

        $this->assertEquals($merchants['owner1'], $merchant['id']);
    }

    public function testDetachMerchant()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $this->createUserMerchantMapping($user['id'], $merchant['id'], 'owner1');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'        => 'owner1',
            'merchant_id' => $merchant['id']
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/detach';

        $this->ba->appAuth();

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

        $this->createUserMerchantMapping($user['id'], $merchant['id'], 'owner');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'        => 'owner1',
            'merchant_id' => $merchant['id']
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/update';

        $this->ba->appAuth();

        $this->startTest();

        $merchants = DB::table('merchant_users')
                        ->where('user_id', '=', $user['id'])
                        ->pluck('merchant_id', 'role');

        $this->assertEquals(count($merchants), 2);

        $this->assertEquals($merchants['owner1'], $merchant['id']);
    }

    public function testGetUserByEmail()
    {
        $user = $this->fixtures->create('user');

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url , $user['email']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $user['id'];

        $this->startTest();
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

        Mail::assertSent(AccountVerification::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertArrayHasKey('org', $viewData);
            $this->assertArrayHasKey('token', $viewData);

            $this->assertEquals('emails.user.account_verification', $mail->view);

            return true;
        });
    }
}
