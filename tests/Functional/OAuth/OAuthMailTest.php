<?php

namespace RZP\Tests\Functional\OAuth;

use Mail;

use Razorpay\OAuth\Application;

use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Mail\OAuth\AppAuthorized as OAuthAppAuthorizedMail;

class OAuthMailTest extends OAuthTestCase
{
    use RequestResponseFlowTrait;
    use OAuthTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/OAuthMailTestData.php';

        parent::setUp();

        $this->ba->appAuth();
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

        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['client_id'] = $clients[0]->id;

        $testData['request']['content']['user_id'] = $user->id;

        $testData['request']['content']['merchant_id'] = $merchant->id;

        $this->startTest();

        Mail::assertSent(OAuthAppAuthorizedMail::class, function ($mail) use ($user, $application)
        {
            $this->assertEquals($user->getPublicId(), $mail->viewData['user']['id']);

            $this->assertEquals($application->id, $mail->viewData['application']['id']);

            return true;
        });
    }
}
