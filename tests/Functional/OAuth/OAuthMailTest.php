<?php

namespace RZP\Tests\Functional\OAuth;

use Mail;

use Razorpay\OAuth\Application;

use RZP\Models\Feature;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Mail\OAuth\AppAuthorized as OAuthAppAuthorizedMail;
use RZP\Mail\OAuth\TallyAuthOtp as TallyAuthOtpMail;
use RZP\Mail\OAuth\CompetitorAppAuthorized as OAuthCompetitorAuthorizedMail;

class OAuthMailTest extends OAuthTestCase
{
    use OAuthTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/OAuthMailTestData.php';

        parent::setUp();

        $this->ba->authServiceAuth();
    }

    public function testOAuthAppAuthorizedMail()
    {
        Mail::fake();

        $appData = [
            Application\Entity::ID   => '10000000000App',
            Application\Entity::NAME => 'Test App'
        ];

        $application = $this->createOAuthApplication($appData);

        $clients = $application->clients()->get()->all();

        $user = $this->getDbLastEntity('user', 'test');

        $merchant = $this->getDbLastEntity('merchant', 'test');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['client_id'] = $clients[0]->id;

        $testData['request']['content']['user_id'] = $user->id;

        $testData['request']['content']['merchant_id'] = $merchant->id;

        $this->startTest();

        Mail::assertQueued(OAuthAppAuthorizedMail::class, function ($mail) use ($user, $application)
        {
            $this->assertEquals($user->getPublicId(), $mail->viewData['user']['id']);

            $this->assertEquals($application->id, $mail->viewData['application']['id']);

            return true;
        });
    }

    public function testOAuthSkipNotification()
    {
        Mail::fake();

        $appData = [
            Application\Entity::ID   => '10000000000App',
            Application\Entity::NAME => 'Test App'
        ];

        $application = $this->createOAuthApplication($appData);

        $this->fixtures->create('feature', [
            'entity_id'     => $application['id'],
            'entity_type'   => 'application',
            'name'          => Feature\Constants::SKIP_OAUTH_NOTIFICATION]);

        $clients = $application->clients()->get()->all();

        $user = $this->getDbLastEntity('user', 'test');

        $merchant = $this->getDbLastEntity('merchant', 'test');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['client_id'] = $clients[0]->id;

        $testData['request']['content']['user_id'] = $user->id;

        $testData['request']['content']['merchant_id'] = $merchant->id;

        $this->startTest();

        Mail::assertNotQueued(OAuthAppAuthorizedMail::class);
    }

    public function testOAuthTallyOTPMail()
    {
        Mail::fake();

        $appData = [
            Application\Entity::ID          => '10000000000App',
            Application\Entity::NAME        => 'Test App',
            Application\Entity::LOGO_URL    => '/logos/10000000000App.png'
        ];

        $application = $this->createOAuthApplication($appData);

        $clients = $application->clients()->get()->all();

        $user = $this->getDbLastEntity('user', 'test');

        $merchant = $this->getDbLastEntity('merchant', 'test');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['client_id'] = $clients[0]->id;

        $testData['request']['content']['user_id'] = $user->id;

        $testData['request']['content']['merchant_id'] = $merchant->id;

        $testData['request']['content']['otp'] = '0007';

        $testData['request']['content']['email'] = $user->email;

        $this->startTest();

        Mail::assertQueued(TallyAuthOtpMail::class, function ($mail) use ($user, $application)
        {
            $this->assertEquals($user['name'], $mail->viewData['user']['name']);

            $this->assertEquals($application->name, $mail->viewData['application']['name']);

            $this->assertEquals('0007', $mail->viewData['otp']);

            $this->assertEquals('https://betacdn.razorpay.com/logos/' . $application->id . '_large.png', $mail->viewData['application']['logo_url']);

            return true;
        });
    }

    public function testOAuthCompetitorAppAuthorizedMail()
    {
        Mail::fake();

        $appData = [
            Application\Entity::ID   => Feature\Type::TEST_APP_ID,
            Application\Entity::NAME => 'Test App'
        ];

        $application = $this->createOAuthApplication($appData);

        $clients = $application->clients()->get()->all();

        $user = $this->getDbLastEntity('user', 'test');

        $merchant = $this->getDbLastEntity('merchant', 'test');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['client_id'] = $clients[0]->id;

        $testData['request']['content']['user_id'] = $user->id;

        $testData['request']['content']['merchant_id'] = $merchant->id;

        $this->startTest();

        Mail::assertQueued(OAuthAppAuthorizedMail::class, function ($mail) use ($user, $application)
        {
            $this->assertEquals($user->getPublicId(), $mail->viewData['user']['id']);

            $this->assertEquals($application->id, $mail->viewData['application']['id']);

            return true;
        });

        Mail::assertQueued(OAuthCompetitorAuthorizedMail::class, function ($mail) use ($merchant, $application)
        {
            $emails = array_column($mail->cc, 'address');

            $expectedEmails = [
                MailConstants::MAIL_ADDRESSES[MailConstants::PARTNERSHIPS],
                MailConstants::MAIL_ADDRESSES[MailConstants::APPROVALS_OAUTH],
            ];

            $this->assertEquals($emails, $expectedEmails);

            $this->assertEquals($merchant->getId(), $mail->viewData['merchant']['id']);

            $this->assertEquals($application->name, $mail->viewData['application']['name']);

            return true;
        });
    }

    public function testOAuthBlockResendingCompetitorAppAuthorizedMail()
    {
        Mail::fake();

        $appData = [
            Application\Entity::ID   => Feature\Type::TEST_APP_ID,
            Application\Entity::NAME => 'Test App'
        ];

        $application = $this->createOAuthApplication($appData);

        $clients = $application->clients()->get()->all();

        $user = $this->getDbLastEntity('user', 'test');

        $merchant = $this->getDbLastEntity('merchant', 'test');

        $this->fixtures->create('merchant_access_map', [
            'merchant_id' => $merchant->getId(),
            'entity_type' => 'application',
            'entity_id'   => $application->getId(),
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['client_id'] = $clients[0]->id;

        $testData['request']['content']['user_id'] = $user->id;

        $testData['request']['content']['merchant_id'] = $merchant->id;

        $this->startTest();

        Mail::assertQueued(OAuthAppAuthorizedMail::class, function ($mail) use ($user, $application)
        {
            $this->assertEquals($user->getPublicId(), $mail->viewData['user']['id']);

            $this->assertEquals($application->id, $mail->viewData['application']['id']);

            return true;
        });

        Mail::assertNotQueued(OAuthCompetitorAuthorizedMail::class);
    }
}
