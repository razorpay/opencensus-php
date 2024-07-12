<?php

namespace RZP\Tests\Functional\BankingUser;

use Mail;
use Mockery;
use RZP\Mail\User\PasswordReset;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Feature\Constants as FeatureConstant;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class BankingUserTest extends TestCase
{
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

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

        $razorxFeature = RazorxTreatment::API_STORK_BANKING_EMAIL .'_reset_password';
        $this->setMockRazorxTreatment([$razorxFeature => 'on']);

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

        $razorxFeature = RazorxTreatment::API_STORK_BANKING_EMAIL .'_reset_password';
        $this->setMockRazorxTreatment([$razorxFeature => 'on']);

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
}