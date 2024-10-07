<?php

namespace RZP\Tests\Functional\BankingUser;

use Mail;
use Mockery;
use RZP\Mail\User\PasswordReset;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Feature\Constants as FeatureConstant;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Mail\User\PasswordChange;
use RZP\Models\User\Entity as UserEntity;
use RZP\Tests\Functional\Fixtures\Entity\User as UserFixture;
use RZP\Mail\User\Otp;

class BankingUserTest extends TestCase
{
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;
    use MocksSplitz;

    protected $coreMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/UserTestData.php';

        parent::setUp();

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->createAndFetchMocks();
    }

    protected function createAndFetchMocks()
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        // Core Mocking Partial
        $this->coreMock = Mockery::mock('RZP\Models\User\Core', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        return [
            "merchantCoreMock"    => $mockMC
        ];
    }

    public function testPasswordResetMailWithCustomOnboadingViaStork()
    {
        Mail::fake();

        $this->mockSplitzExperiment(["response" => ["variant" => ["name" => 'enable', ]]]);

        $this->fixtures->create('user', ['email' => 'resetpass@razorpay.com']);
        $this->ba->dashboardGuestAppAuth();
        $this->fixtures->org->addFeatures([FeatureConstant::CUSTOM_ONBOARDING_EMAILS],"100000razorpay");

        $this->startTest();

        Mail::assertSent(PasswordReset::class, function ($mail)
        {
            $shouldSendEmailViaStork = $mail->shouldSendEmailViaStork();
            $getParamsForStork = $mail->getParamsForStork();

            $this->assertEquals($shouldSendEmailViaStork, true);

            $this->assertArrayHasKey('template_name', $getParamsForStork);
            $this->assertArrayHasKey('template_namespace', $getParamsForStork);
            $this->assertArrayHasKey('params', $getParamsForStork);
            $this->assertArrayHasKey('params', $getParamsForStork);
            $this->assertArrayHasKey('password_reset_url', $getParamsForStork['params']);
            $this->assertArrayHasKey('showContactUs', $getParamsForStork['params']);
            $this->assertArrayHasKey('org', $getParamsForStork['params']);
            $this->assertArrayHasKey('hostname', $getParamsForStork['params']['org']);
            $this->assertArrayHasKey('display_name', $getParamsForStork['params']['org']);
            $this->assertArrayHasKey('showAxisSupportUrl', $getParamsForStork['params']['org']);
            $this->assertArrayHasKey('isCustomOnboardingEmail', $getParamsForStork['params']['org']);
            $this->assertArrayHasKey('login_logo_url', $getParamsForStork['params']['org']);

            $this->assertEquals($getParamsForStork['params']['org']['isCustomOnboardingEmail'], true);
            $this->assertEquals('emails.user.password_reset', $mail->view);
            return true;
        });
    }

    public function testPasswordResetMailWithoutCustomOnboadingViaStork()
    {
        Mail::fake();

        $this->mockSplitzExperiment(["response" => ["variant" => ["name" => 'enable', ]]]);

        $this->fixtures->create('user', ['email' => 'resetpass@razorpay.com']);
        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        Mail::assertSent(PasswordReset::class, function ($mail)
        {
            $shouldSendEmailViaStork = $mail->shouldSendEmailViaStork();
            $getParamsForStork = $mail->getParamsForStork();

            $this->assertEquals($shouldSendEmailViaStork, true);

            $this->assertArrayHasKey('template_name', $getParamsForStork);
            $this->assertArrayHasKey('template_namespace', $getParamsForStork);
            $this->assertArrayHasKey('params', $getParamsForStork);
            $this->assertArrayHasKey('params', $getParamsForStork);
            $this->assertArrayHasKey('password_reset_url', $getParamsForStork['params']);
            $this->assertArrayHasKey('showContactUs', $getParamsForStork['params']);
            $this->assertArrayHasKey('org', $getParamsForStork['params']);
            $this->assertArrayHasKey('hostname', $getParamsForStork['params']['org']);
            $this->assertArrayHasKey('display_name', $getParamsForStork['params']['org']);
            $this->assertArrayHasKey('showAxisSupportUrl', $getParamsForStork['params']['org']);
            $this->assertArrayHasKey('isCustomOnboardingEmail', $getParamsForStork['params']['org']);
            $this->assertArrayHasKey('login_logo_url', $getParamsForStork['params']['org']);

            $this->assertEquals($getParamsForStork['params']['org']['isCustomOnboardingEmail'], false);
            $this->assertEquals('emails.user.password_reset', $mail->view);

            return true;
        });
    }
    public function testChangePasswordEmailViaStork()
    {
        Mail::fake();
        $this->mockSplitzExperiment(['response' => ['variant' => ['name' => 'enable',]]]);
        $user = $this->fixtures->create('user', ['password' => '12345']);
        $testData = &$this->testData[__FUNCTION__];
        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        Mail::assertQueued(PasswordChange::class, function ($mail) {
            $shouldSendEmailViaStork = $mail->shouldSendEmailViaStork();
            $getParamsForStork = $mail->getParamsForStork();

            $this->assertEquals($shouldSendEmailViaStork, true);
            $this->assertArrayHasKey('template_name', $getParamsForStork);
            $this->assertArrayHasKey('template_namespace', $getParamsForStork);
            $this->assertArrayHasKey('params', $getParamsForStork);
            $this->assertArrayHasKey('changed_at', $getParamsForStork['params']);
            $this->assertArrayHasKey('resetPasswordUrl', $getParamsForStork['params']);
            $this->assertArrayHasKey('org', $getParamsForStork['params']);
            $this->assertArrayHasKey('display_name', $getParamsForStork['params']['org']);
            $this->assertArrayHasKey('login_logo_url', $getParamsForStork['params']['org']);
            $this->assertEquals('emails.user.password_change', $mail->view);

            return true;
        });
    }

    public function testChangePasswordEmailViaMailgun()
    {
        Mail::fake();
        $this->mockSplitzExperiment(['response' => ['variant' => ['name' => 'off',]]]);
        $user = $this->fixtures->create('user', ['password' => '12345']);
        $testData = &$this->testData[__FUNCTION__];
        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        Mail::assertQueued(PasswordChange::class, function ($mail) {
            $shouldSendEmailViaStork = $mail->shouldSendEmailViaStork();

            $this->assertEquals($shouldSendEmailViaStork, false);
            $this->assertEquals('emails.user.password_change', $mail->view);

            return true;
        });
    }

    public function testResendOtpVerificationMailViaStork()
    {
        Mail::fake();
        $this->mockSplitzExperiment(['response' => ['variant' => ['name' => 'enable',]]]);

        $user = $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [UserEntity::CONFIRM_TOKEN => 'testing123456789',
                UserEntity::EMAIL => 'abc@rzp.com']);

        $merchant = $this->fixtures->create('merchant',
            ['id'    => '10000000000002',
                'email' => 'abc@rzp.com']);

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->proxyAuth();

        $response=$this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(Otp::class, function ($mail)
        {
            $shouldSendEmailViaStork = $mail->shouldSendEmailViaStork();
            $getParamsForStork = $mail->getParamsForStork();

            $this->assertEquals($shouldSendEmailViaStork, true);
            $this->assertArrayHasKey('template_name', $getParamsForStork);
            $this->assertArrayHasKey('template_namespace', $getParamsForStork);
            $this->assertArrayHasKey('params', $getParamsForStork);
            $this->assertArrayHasKey('otp', $getParamsForStork['params']);
            $this->assertArrayHasKey('otp', $getParamsForStork['params']['otp']);
            $this->assertArrayHasKey('expires_at', $getParamsForStork['params']['otp']);
            $this->assertEquals('verify_email', $mail->input['action']);
            $this->assertNotEmpty($mail->user);
            $this->assertNotEmpty($mail->otp);
            $this->assertEquals('emails.user.otp_email_verify', $mail->view);

            return true;
        });
    }

    public function testResendOtpVerificationMailViaMailgun()
    {
        Mail::fake();
        $this->mockSplitzExperiment(['response' => ['variant' => ['name' => 'off',]]]);

        $user = $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [UserEntity::CONFIRM_TOKEN => 'testing123456789',
                UserEntity::EMAIL => 'abc@rzp.com']);

        $merchant = $this->fixtures->create('merchant',
            ['id'    => '10000000000002',
                'email' => 'abc@rzp.com']);

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->proxyAuth();

        $response=$this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(Otp::class, function ($mail)
        {
            $shouldSendEmailViaStork = $mail->shouldSendEmailViaStork();

            $this->assertEquals($shouldSendEmailViaStork, actual: false);
            $this->assertEquals('verify_email', $mail->input['action']);
            $this->assertNotEmpty($mail->user);
            $this->assertNotEmpty($mail->otp);
            $this->assertEquals('emails.user.otp_email_verify', $mail->view);

            return true;
        });
    }
}