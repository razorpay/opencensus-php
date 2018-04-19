<?php

namespace RZP\Tests\Functional\OAuth;

use Mail;

use Razorpay\OAuth\Application;

use RZP\Models\Feature;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Mail\OAuth\AppAuthorized as OAuthAppAuthorizedMail;
use RZP\Mail\OAuth\CompetitorAppAuthorized as OAuthCompetitorAuthorizedMail;

class OAuthMailTest extends OAuthTestCase
{
    use OAuthTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

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

    public function testOAuthCompetitorAppAuthorizedMail()
    {
        Mail::fake();

        $appData = [
            Application\Entity::ID   => Feature\Type::JUSPAY_APP_ID,
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
            $this->assertEquals($merchant->getId(), $mail->viewData['merchant']['id']);

            $this->assertEquals($application->name, $mail->viewData['application']['name']);

            return true;
        });
    }
}
