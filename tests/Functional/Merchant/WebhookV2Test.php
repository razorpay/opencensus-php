<?php

namespace RZP\Tests\Functional\Merchant;

use Illuminate\Http\UploadedFile;

use Mail;
use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsWebhookEvents;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Mail\Merchant\Webhook as WebhookMail;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class WebhookV2Test extends TestCase
{
    use RequestResponseFlowTrait;
    use OAuthTrait;
    use TestsBusinessBanking;
    use TestsWebhookEvents;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/WebhookV2Data.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures(['payout']);
    }

    /*
     * Partner type reseller, cannot create webhook
     */
    public function testCreateAppWebhookInvalidPartnerType()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'reseller']);

        $this->startTest();
    }

    /*
     * Partner type pure platform, can create webhook
     */
    public function testCreateAppWebhookPurePlatform()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'pure_platform']);
        $this->addOAuthTag();
        $this->createOAuthApplication(['id' => '10000000000App', 'merchant_id' => '10000000000000']);

        $this->startTest();
    }

    /*
     * Not partner yet but tagged OAuth, can create webhook
     */
    public function testCreateAppWebhookOAuthTag()
    {
        $this->addOAuthTag();
        $this->createOAuthApplication(['id' => '10000000000App', 'merchant_id' => '10000000000000']);

        $this->startTest();
    }

    /*
     * Partner type bank, also tagged OAuth, cannot create webhook
     * as this should ideally not happen and we should prevent by default
     */
    public function testCreateAppWebhookBankWithOAuthTag()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'bank']);
        $this->addOAuthTag();
        $this->createOAuthApplication(['id' => '10000000000App', 'merchant_id' => '10000000000000']);

        $this->startTest();
    }

    /*
     * Partner type fully managed, can create webhook irrespective
     * of the oauth tag
     */
    public function testCreateAppWebhookFullyManagedWithOAuthTag()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);
        $this->addOAuthTag();
        $this->createOAuthApplication(['id' => '10000000000App', 'merchant_id' => '10000000000000']);

        $this->startTest();
    }

    public function testEditWebhookByNonOwnerUser()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user->id,
            'merchant_id' => '10000000000000',
            'role'        => 'support',
        ]);

        $this->dontExpectAnyStorkServiceRequest();

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->toArrayPublic());

        $this->startTest();
    }

    public function testGetWebhookEvents()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $response = $this->startTest();

        $this->assertContains('order.paid', $response);
        $this->assertContains('virtual_account.credited', $response);
        $this->assertNotContains('subscription.charged', $response);

        // Events of other products (e.g. banking) should not come in response.
        $this->assertNotContains('transaction.created', $response);
    }

    public function testGetWebhookEventsForProductBanking()
    {

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);
        // This is required, because this is going to on board the merchant on X on the test mode
        // which requires the terminal entity to be present
        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
            ['merchant_id' => '100000Razorpay']);

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    public function testCreateWebhookForPartner()
    {
        $this->assignMerchantAsPartnerAggregator();
        $this->addMerchantApplicationMapping();

        $this->expectStorkServiceRequestForAction('createWebhookForPartner');

        $this->startTest();
    }

    public function testCreateWebhookForPartnerMerchantNoAppAccessFailure()
    {
        $this->assignMerchantAsPartnerAggregator();

        $this->dontExpectAnyStorkServiceRequest();

        $this->startTest();
    }

    public function testCreateWebhookForOAuth()
    {
        $this->addOAuthTag();
        $this->addMerchantApplicationMapping();

        $this->expectStorkServiceRequestForAction('createWebhookForOAuth');

        $this->startTest();
    }

    public function testCreateWebhookForOAuthFailure()
    {
        $this->dontExpectAnyStorkServiceRequest();

        $this->startTest();
    }

    public function testCreateWebhookForBanking()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->expectStorkServiceRequestForAction('listWebhookForBankingWhenReturnsNoWebhooks');
        $this->expectStorkServiceRequestForAction('createWebhookForBanking');

        $this->startTest();
    }

    public function testCreateWebhookForBankingAlreadyExistsFailure()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->expectStorkServiceRequestForAction('listWebhookForBankingBeforeCreate');
        $this->dontExpectStorkServiceRequest('/twirp/rzp.stork.webhook.v1.WebhookAPI/Create');

        $this->startTest();
    }

    public function testCreateWebhookForPrimary()
    {
        $this->expectStorkServiceRequestForAction('createWebhookForPrimary');

        $this->startTest();
    }

    //event is not valid for the product
    public function testCreateWebhookInvalidProductEventFailure()
    {
        $this->startTest();
    }

    public function testGetWebhookForHosted()
    {
        $this->expectStorkServiceRequestForAction('getWebhookWithSecret');

        $this->ba->hostedProxyAuth();
        $this->startTest();
    }

    public function testGetWebhookForBanking()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->expectStorkServiceRequestForAction('getWebhookForBanking');

        $this->startTest();
    }

    public function testGetWebhookForPrimary()
    {
        $this->expectStorkServiceRequestForAction('getWebhookForPrimary');

        $this->startTest();
    }

    public function testListWebhookForHosted()
    {
        $this->expectStorkServiceRequestForAction('listWebhookWithSecret');

        $this->ba->hostedProxyAuth();
        $this->startTest();
    }

    public function testListWebhookWithPrivateAuth()
    {
        $this->expectStorkServiceRequestForAction('listWebhookWithPrivateAuth');

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testListWebhookForBanking()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->expectStorkServiceRequestForAction('listWebhookForBanking');

        $this->startTest();
    }

    public function testListWebhookForPrimary()
    {
        $this->expectStorkServiceRequestForAction('listWebhookForPrimary');

        $this->startTest();
    }

    public function testListWebhookForPartner()
    {
        $this->assignMerchantAsPartnerAggregator();
        $this->addMerchantApplicationMapping();

        $this->expectStorkServiceRequestForAction('listWebhookForPartner');

        $this->startTest();
    }

    public function testListWebhookForPartnerMerchantNotPartnerFailure()
    {
        $this->startTest();
    }

    public function testListWebhookForPartnerMerchantNoAppAccessFailure()
    {
        $this->assignMerchantAsPartnerAggregator();

        $this->startTest();
    }

    public function testUpdateWebhookForBanking()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 4
        ]);
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->expectStorkServiceRequestForAction('updateWebhookForBanking');

        $this->startTest();
    }

    public function testUpdateWebhookForPrimary()
    {
        $this->expectStorkServiceRequestForAction('updateWebhookForPrimary');

        $this->startTest();
    }

    public function testUpdateWebhookForOAuth()
    {
        $this->addOAuthTag();
        $this->addMerchantApplicationMapping();

        $this->expectStorkServiceRequestForAction('updateWebhookForOAuth');

        $this->startTest();
    }

    public function testUpdateWebhookForOAuthMerchantNotPartnerFailure()
    {
        $this->dontExpectAnyStorkServiceRequest();

        $this->startTest();
    }

    public function testUpdateWebhookForOAuthMerchantNoAppAccessFailure()
    {
        $this->addOAuthTag();

        $this->dontExpectAnyStorkServiceRequest();

        $this->startTest();
    }

    //event is not valid for the product
    public function testUpdateWebhookInvalidProductEventFailure()
    {
        $this->dontExpectAnyStorkServiceRequest();

        $this->startTest();
    }

    public function testSendDisableWebhookEmailForStork()
    {
        $this->ba->storkAppAuth();

        Mail::fake();

        $this->startTest();

        $testData = $this->testData[__FUNCTION__.'Data'];
        // test mail sent
        Mail::assertQueued(WebhookMail::class, function ($mail) use ($testData)
        {
            $this->assertEquals($mail->viewData['url'], $testData['url']);

            $this->assertEquals($mail->viewData['mode'], $testData['mode']);

            $this->assertEquals($mail->viewData['subject'], $testData['subject']);

            return ($mail->hasFrom('alerts@razorpay.com') and ($mail->hasTo($testData['alert_email'])));
        });
    }

    public function testProcessWebhookEventsFromCsv()
    {
        $filepath = __DIR__.'/helpers/webhook_events.csv';
        $file = new UploadedFile($filepath, 'webhook_events.csv', 'text/csv', filesize($filepath), null, true);
        $this->testData[__FUNCTION__]['request']['files']['file'] = $file;

        $this->expectWebhookEvent('payment.created');
        $this->expectWebhookEvent('payment.failed');
        $this->expectWebhookEvent('payment.captured');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testProcessWebhookEventsFromCsvWhenInvalidPayload()
    {
        $contents = file_get_contents(__DIR__.'/helpers/webhook_events.csv');
        $contents = str_replace('payment.failed', 'unknown.event', $contents);
        $filepath = '/tmp/webhook_events.csv';
        file_put_contents($filepath, $contents);
        $file = new UploadedFile($filepath, 'webhook_events.csv', 'text/csv', filesize($filepath), null, true);
        $this->testData[__FUNCTION__]['request']['files']['file'] = $file;

        $this->dontExpectAnyWebhookEvent();

        $this->ba->adminAuth();

        $this->startTest();
    }

    protected function addOAuthTag(string $merchantId = '10000000000000')
    {
        $merchant = Merchant\Entity::find($merchantId);
        $merchant->reTag(["oauth"]);
        $merchant->saveOrFail();
    }

    protected function assignMerchantAsPartnerAggregator($merchantId = '10000000000000')
    {
        $this->fixtures->merchant->edit($merchantId, ['partner_type' => 'aggregator']);
    }

    protected function addMerchantApplicationMapping(string $appId = '10000000000App', string $merchantId = '10000000000000')
    {
        $this->createOAuthApplication(
            [
                'id'          => $appId,
                'merchant_id' => $merchantId,
            ]
        );
    }

    public function testGetP2pWebhookEvents()
    {
        $this->fixtures->merchant->addFeatures(['p2p_upi']);

        $response = $this->startTest();

        $this->assertContains('customer.transaction.created', $response);
        $this->assertContains('customer.transaction.completed', $response);
        $this->assertContains('customer.transaction.failed', $response);
        $this->assertContains('customer.vpa.created', $response);
        $this->assertContains('customer.vpa.deleted', $response);
        $this->assertContains('customer.verification.completed', $response);
        $this->assertContains('customer.deregistration.completed', $response);
    }
}
