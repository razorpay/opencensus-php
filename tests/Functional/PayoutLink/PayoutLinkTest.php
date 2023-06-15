<?php

namespace RZP\Tests\Functional\PayoutLink;

use App;
use Mail;
use Hash;
use Config;
use Mockery;
use Exception;
use Carbon\Carbon;
use ReflectionClass;
use RZP\Models\Payout;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Error\ErrorCode;
use RZP\Constants\Metric;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Models\PayoutLink\Core;
use RZP\Mail\PayoutLink\Failed;
use RZP\Mail\PayoutLink\Success;
use RZP\Models\Feature\Constants;
use RZP\Models\Currency\Currency;
use RZP\Models\PayoutLink\Status;
use RZP\Mail\PayoutLink\SendLink;
use RZP\Models\PayoutLink\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\PayoutLink\CustomerOtp;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Admin\Permission\Name as AdminPermission;
use RZP\Exception\BadRequestException;
use RZP\Models\PayoutLink\TokenService;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Mail\PayoutLink\SuccessInternal;
use RZP\Mail\PayoutLink\SendLinkInternal;
use RZP\Mail\PayoutLink\SendReminderInternal;
use Razorpay\Metrics\Manager as MetricManager;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Models\PayoutLink\Entity as PayoutLink;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Mail\PayoutLink\SendProcessingExpiredInternal;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;

class PayoutLinkTest extends TestCase
{

    use TestsBusinessBanking;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use EntityActionTrait;
    use WebhookTrait;
    //use TestsWebhookEvents;

    protected $config;

    const TEST_PAYOUT_LINK_PAYLOAD = [
        'contact_id'   => '1000010contact',
        'amount'       => 1000,
        'merchant_id'  => '10000000000000',
        'user_id'      => null,
        'currency'     => 'INR',
        'description'  => 'This is a test payout',
        'purpose'      => 'refund',
        'receipt'      => 'Test Payout Receipt',
        'notes'        => [
            'hi' => 'hello'
        ],
        'short_url'    => 'http=>//76594130.ngrok.io/i/mGs4ehe',
        'status'       => 'issued',
        'created_at'   => 1575367399,
        'cancelled_at' => null
    ];

    const TEST_CONTACT = [
        'id'      => '1000010contact',
        'email'   => 'contact@razorpay.com',
        'contact' => '8888888888',
        'name'    => 'test user'
    ];

    const FIXTURE_ENTITY        = 'payout_link';
    const GENERATE_CUSTOMER_OTP = 'generate-customer-otp';
    const VERIFY_CUSTOMER_OTP   = 'verify-customer-otp';
    const CANCEL                = 'cancel';
    const FUND_ACCOUNTS         = 'fund-accounts';
    const INITIATE              = 'initiate';
    const STATUS                = 'status';
    const TIMELINE              = 'timeline';
    const RESEND_NOTIFICATION   = 'resend';

    const SKIP_REASON = 'Skipping this since moved to microservice';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutLinkTestData.php';

        parent::setUp();

        $this->createContact();

        $this->createFundAccount();

        $this->setUpMerchantForBusinessBanking(true, 10000000);

        $this->bankAccount->setIfsc('YESBB000000');

        $this->bankAccount->saveOrFail();

        $this->config = App::getFacadeRoot()['config'];

        $this->mockStorkService();
    }

    public function testBoolCastingInPayoutLinkNotification()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->testCreatePayoutLinkPassesWithoutOtpWhenPrivateAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/payout-links/' . $payoutLink['id'];

        // running GET on this should return casted values of send_email and send_sms
        $this->startTest();
    }

    public function testWebhooksEnabled()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $this->ba->addXOriginHeader();

        $this->mockServiceStorkRequest(
            function ($path, $payload)
            {
                switch ($path)
                {
                    case '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create':
                        return $this->getStorkCreateResponse('rx-test', 'https://www.example.com',
                                                             [
                                                                 'payout.created',
                                                                 'payout_link.issued',
                                                                 'payout_link.processing',
                                                                 'payout_link.processed',
                                                                 'payout_link.attempted',
                                                                 'payout_link.cancelled',
                                                             ]);
                        break;

                    case '/twirp/rzp.stork.webhook.v1.WebhookAPI/List':
                        $this->getStorkListResponseEmpty();
                        break;
                }

                return new \WpOrg\Requests\Response();
            });

        $this->startTest();
    }

    public function testWebhooksUpdate()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $this->ba->addXOriginHeader();

        $this->testData[__FUNCTION__]['request']['url'] = '/webhooks/EZ4ezgl4124qKu';

        $this->mockServiceStorkRequest(
            function ($path, $payload)
            {
                switch ($path)
                {
                    case '/twirp/rzp.stork.webhook.v1.WebhookAPI/Get':
                        return $this->getStorkCreateResponse('rx-test', 'https://www.example.com',
                            [
                                'payout.created',
                                'payout_link.issued',
                                'payout_link.processing',
                                'payout_link.processed',
                                'payout_link.attempted',
                                'payout_link.cancelled',
                            ]);
                        break;

                    case '/twirp/rzp.stork.webhook.v1.WebhookAPI/Update':
                        return $this->getStorkCreateResponse('rx-test', 'https://www.example.com',
                            [
                                'payout.created',
                                'payout_link.processing',
                                'payout_link.processed',
                                'payout_link.cancelled',
                            ]);

                        break;
                }

                return new \WpOrg\Requests\Response();
            });

        $this->startTest();
    }

    public function testWebhooksEnabledPartial()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $this->ba->addXOriginHeader();

        $this->mockServiceStorkRequest(
            function ($path, $payload)
            {
                switch ($path)
                {
                    case '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create':
                        return $this->getStorkCreateResponse('rx-test', 'https://www.example.com',
                            [
                                'payout.created',
                                'payout_link.processing',
                                'payout_link.attempted',
                                'payout_link.cancelled',
                            ]);
                        break;

                    case '/twirp/rzp.stork.webhook.v1.WebhookAPI/List':
                        $this->getStorkListResponseEmpty();
                        break;
                }

                return new \WpOrg\Requests\Response();
            });

        $this->startTest();
    }

    public function testExceptionOnCreatePayoutLinkWithoutOtpOnProxyAuth()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testPayoutLinkFetchExpandsByUser()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->testPostRequestForCreatingPayoutLinkOnProxyAuth();

        $this->ba->proxyAuth();

        $resp = $this->startTest();

        $this->assertNotEmpty($resp['items']);

        $this->assertTrue(in_array('user' , $resp['items'][0]));

        $this->assertNotEmpty($resp['items'][0]['user']);
    }

    public function testExceptionOnCreatePayoutLinkWithInvalidOtpOnProxyAuth()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth('rzp_test_10000000000000' ,  'MerchantUser01');

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testExceptionOnCreatePayoutLinkWithoutTokenOnProxyAuth()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testCreatePayoutLinkPassesWithoutOtpWhenPrivateAuth()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        return $this->startTest();
    }

    public function testExceptionWhenSendSmsEnabledWithNoPhoneInContact()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testSendLinkEmailQueuedWhenOnPayoutLinkCreate()
    {
        self::markTestSkipped(self::SKIP_REASON);

        Mail::fake();

        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();

        Mail::assertQueued(SendLink::class);
    }

    public function testExceptionWhenSendEmailEnabledWithNoEmailInContact()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    /**
     * This is to Test the BUILD on Payout Link Entity
     */
    public function testPayoutLinkCreation()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $input = [
            PayoutLink::AMOUNT       => 1000,
            PayoutLink::CURRENCY     => Currency::INR,
            PayoutLink::DESCRIPTION  => 'TEST DESCRIPTION',
            PayoutLink::PURPOSE      => 'refund',
            PayoutLink::BALANCE_ID   => $this->bankingBalance->getId(),
            PayoutLink::CONTACT_NAME => $this->contact->getName()
        ];

        $payout_link = (new PayoutLink)->build($input);

        $payout_link->merchant()->associate($this->contact->merchant);

        $payout_link->contact()->associate($this->contact);

        $payout_link->setStatus(Status::ISSUED);

        $payout_link->saveOrFail();
    }

    public function testPostRequestForCreatingPayoutLink()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testPostRequestForCreatingPayoutLinkOnProxyAuth()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testPostRequestForCreatingPayoutLinkWithContactId()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testGetPayoutLinkById()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $testData = self::TEST_PAYOUT_LINK_PAYLOAD;

        $testData['balance_id'] = $this->bankingBalance->getId();

        $payoutLink = $this->fixtures->create(self::FIXTURE_ENTITY,
                                              $testData
                                              );

        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId());

        $this->startTest();

    }

    public function testListPayoutLink()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->privateAuth();

        $i = 0;

        $payoutLinkCount = 5;

        // creating a new variable so that the original one is not altered during the test
        $testPayload = self::TEST_PAYOUT_LINK_PAYLOAD;

        $testPayload['balance_id'] = $this->bankingBalance->getId();

        $testPayoutLinkIds = [];

        while ($i < $payoutLinkCount)
        {
            $id = PayoutLink::generateUniqueId();

            $testPayload['id'] = $id;

            $payoutLink = $this->fixtures->create(self::FIXTURE_ENTITY,
                                                  $testPayload);

            array_push($testPayoutLinkIds, $payoutLink->getPublicId());

            $i++;
        }

        $response = $this->startTest();

        $this->assertEquals($payoutLinkCount , $response['count']);

        $fetchedPayoutLinks = $response['items'];

        foreach($fetchedPayoutLinks as $pl)
        {
            $this->assertContains($pl['id'], $testPayoutLinkIds);
        }
    }

    public function testListPayoutLinkWithSearchParameter()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $testPayload = self::TEST_PAYOUT_LINK_PAYLOAD;

        $testPayload['balance_id'] = $this->bankingBalance->getId();

        $payoutLink = $this->fixtures->create(self::FIXTURE_ENTITY,
                                              $testPayload);

        $this->ba->privateAuth();

        $url = $this->testData['testListPayoutLinkWithSearchParameter']['request']['url'];

        $urlWithId = $url . '?contact_id=' . $this->contact->getPublicId();

        $this->testData['testListPayoutLinkWithSearchParameter']['request']['url'] = $urlWithId;

        $response = $this->startTest();

        $fetchedPayoutLinkId = $response['items'][0]['id'];

        $this->assertEquals($fetchedPayoutLinkId , $payoutLink->getPublicId());
    }

    public function testPayoutWithPayoutLinkRelationship()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payout = $this->fixtures->create('payout');

        $payoutLink = $this->fixtures->create(self::FIXTURE_ENTITY,
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $payout->payoutLink()->associate($payoutLink);

        $payout->saveOrFail();

        $payoutLink = $this->getDbEntity('payout_link', ['id' => $payoutLink->getId()]);

        $associatedPayout = $payoutLink->payouts[0];

        $this->assertEquals($associatedPayout->getId(), $payout->getId());
    }

    public function testShortUrlGenerationSuccessful()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->privateAuth();

        $testHardCodedShortUrl = 'www.this_is_when_elfin_works.com';

        $this->addAccountNumberParameter(__FUNCTION__);

        $elfin = $this->createMock(ElfinService::class);

        $elfin->method('shorten')
              ->willReturn($testHardCodedShortUrl);

        $this->app->instance('elfin', $elfin);

        $response = $this->startTest();

        $this->assertEquals($response['short_url'], $testHardCodedShortUrl);
    }

    public function testShortUrlGenerationExceptionThrown()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $urlFormat = '%s/v1/payout-links/%s/view';

        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        // mock elfin and make it throw an error, check the right exception is thrown on our end
        $elfin = $this->createMock(ElfinService::class);

        $elfin->method('shorten')
              ->willThrowException(new Exception('Elfin failed because I want it to'));

        $this->app->instance('elfin', $elfin);

        $response = $this->startTest();

        $payoutLinkId = $response['id'];

        $expectedTargetUrl = sprintf($urlFormat,
                                     $this->app['config']['applications.payout_links.url'],
                                     $payoutLinkId);

        // asserting that the short_url is same as the full target url, because elfin failed
        $this->assertEquals($response['short_url'], $expectedTargetUrl);

    }

    public function testPayoutLinkFailedDueToContactCreationFailure()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testContactAddFailsWhenEmailAndPhoneNumberBothMissing()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testPayoutLinkCreationFailsWhenContactIdIsMissingBothEmailAndPhone()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $contact = $this->fixtures->create('contact',
                                           [
                                               'name'    => 'Test Contact',
                                               'email'   => '',
                                               'contact' => ''
                                           ]);

        $testData = $this->testData['testPayoutLinkCreationFailsWhenContactIdIsMissingBothEmailAndPhone'];

        $testData['request']['content']['contact']['id'] = $contact->getPublicId();

        $this->testData['testPayoutLinkCreationFailsWhenContactIdIsMissingBothEmailAndPhone'] = $testData;

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGenerateOtpForOnlyPhoneContact()
    {
        self::markTestSkipped(self::SKIP_REASON);

        Mail::fake();

        $contact = $this->fixtures->create('contact',
                                           [
                                               'id'      => '1000011contact',
                                               'contact' => '8888888888',
                                               'email' => '',
                                               'name'    => 'test user'
                                           ]);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id'           => $contact->getId(),
                                                  'contact_name'         => $contact->getName(),
                                                  'contact_phone_number' => $contact->getContact(),
                                                  'contact_email'        => $contact->getEmail(),
                                                  'balance_id'           => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__ , $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();

        Mail::assertNotQueued(CustomerOtp::class);
    }

    public function testGenerateOtpForOnlyEmailContact()
    {
        self::markTestSkipped(self::SKIP_REASON);

        Mail::fake();

        $contact = $this->fixtures->create('contact',
                                           [
                                               'id'    => '1000011contact',
                                               'email' => 'test@razorpay.com',
                                               'name'  => 'test user'
                                           ]);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id'           => $contact->getId(),
                                                  'contact_name'         => $contact->getName(),
                                                  'contact_email'        => $contact->getEmail(),
                                                  'balance_id'           => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__ , $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();

        Mail::assertQueued(CustomerOtp::class);
    }

    public function testVerifyOtpSuccessful()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::VERIFY_CUSTOMER_OTP);

        $response = $this->startTest();

        $token = $response['token'];

        $this->assertMatchesRegularExpression('/' . $payoutLink->getPublicId() . '.*/', $token);
    }

    public function testVerifyOtpFailedByInvalidOtp()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::VERIFY_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testExceptionWhenOtpGeneratedWithoutEmailAndPhoneNumber()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $contact = $this->fixtures->create('contact',
                                           [
                                               'id'      => '1000011contact',
                                               'email'   => '',
                                               'contact' => '',
                                               'name'    => 'test user'
                                           ]);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id'           => $contact->getId(),
                                                  'contact_name'         => $contact->getName(),
                                                  'contact_phone_number' => $contact->getContact(),
                                                  'contact_email'        => $contact->getEmail(),
                                                  'balance_id'           => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__ , $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testExceptionWhenOnlyPhoneIsPresentAndSmsFails()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $raven = Mockery::mock('RZP\Services\Raven');

        $raven->shouldReceive('sendSms')
              ->andThrow(new Exception('I failed for the sake of testing'));

        $raven->shouldReceive('generateOtp')
              ->andReturn(['otp' => '1234']);

        $this->app->instance('raven', $raven);

        $contact = $this->fixtures->create('contact',
                                           [
                                               'id'      => '1000011contact',
                                               'email'   => '',
                                               'contact' => '1231231231',
                                               'name'    => 'test user'
                                           ]);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id'           => $contact->getId(),
                                                  'contact_name'         => $contact->getName(),
                                                  'contact_phone_number' => $contact->getContact(),
                                                  'contact_email'        => $contact->getEmail(),
                                                  'balance_id'           => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__ , $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testOtpGenerationWithContext()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $this->contact->getId(),
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__ , $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testOtpVerificationWithContext()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $this->contact->getId(),
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::VERIFY_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testWhenRavenFailsWhileOtpGenerationExceptionIsThrown()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $raven = Mockery::mock('RZP\Services\Raven');

        $raven->shouldReceive('sendSms')
              ->andReturn('sms_1234');

        $raven->shouldReceive('generateOtp')
              ->andreturn([]);

        $this->app->instance('raven', $raven);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $this->contact->getId(),
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__ , $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testPayoutLinkCancelApiSuccess()
    {
        self::markTestSkipped('Skipping this till feature storing is resolved');

        $this->expectWebhookEventWithContents('payout_link.cancelled', 'PayoutLinkCancelledWebHook');

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::CANCEL);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['cancelled_at']);
    }

    public function testCancellingPayoutLinkFromProcessingStatusShouldThrowException()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::CANCEL);

        $payoutLink->setStatus(Status::PROCESSING);

        $payoutLink->saveOrFail();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testMerchantSettingsUpdateApiForNonPayoutModeSettings()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $slackMock = Mockery::mock();

        $this->startTest();

        $slackMock->shouldNotHaveReceived('queue');
    }

    public function testMerchantSettingsUpdateApiForIMPSDisabled()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $queueMethodOutput = [
            'Payout Mode IMPS disabled for 10000000000000 by DASHBOARD_INTERNAL',
            [],
            [
                'channel'  => Config::get('slack.channels.operations_log'),
                'username' => 'Jordan Belfort',
                'icon'     => ':boom:'
            ]
        ];

        $slackMock = Mockery::mock();

        $slackMock->shouldReceive('queue');

        $this->app->instance('slack', $slackMock);

        $this->ba->proxyAuth();

        $this->startTest();

        $slackMock->shouldHaveReceived('queue')->withArgs($queueMethodOutput);
    }

    public function testMerchantSettingsUpdateApiForUPIDisabled()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $queueMethodOutput = [
            'Payout Mode UPI disabled for 10000000000000 by DASHBOARD_INTERNAL',
            [],
            [
                'channel'  => Config::get('slack.channels.operations_log'),
                'username' => 'Jordan Belfort',
                'icon'     => ':boom:'
            ]
        ];

        $slackMock = Mockery::mock();

        $slackMock->shouldReceive('queue');

        $this->app->instance('slack', $slackMock);

        $this->ba->proxyAuth();

        $this->startTest();

        $slackMock->shouldHaveReceived('queue')->withArgs($queueMethodOutput);
    }

    public function testMerchantSettingsGetApi()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->testMerchantSettingsUpdateApiForIMPSDisabled();

        $this->startTest();
    }

    public function testSettingPayoutLinkToInvalidStatusShouldThrowException()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_PAYOUT_LINK_INVALID_STATUS);

        $this->expectException(BadRequestException::class);

        $payoutLink->setStatus('An Invalid State');
    }

    public function testCancelIdempotencyByCallingTheCancelApiTwice()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::CANCEL);

        $this->ba->privateAuth();

        $this->startTest();

        $this->startTest();
    }

    public function testGetFundAccountWithValidTokenReturnsFundAccountArray()
    {
        self::markTestSkipped(self::SKIP_REASON);

        // call fund-account, assuming OTP verification will pass as redis is mocked to return non-null value,
        // which signifies OTP is present
        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $this->contact->getId(),
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::FUND_ACCOUNTS);

        $this->startTest();
    }

    public function testGetFundAccountWithInvalidTokenRaisesException()
    {
        self::markTestSkipped(self::SKIP_REASON);

        // call fund-account, assuming OTP verification will pass as redis is mocked to return Null,
        // which means token is not found
        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $this->contact->getId(),
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);
        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::FUND_ACCOUNTS);

        $this->startTest();
    }

    public function testInitiateApiBankAccountRequiredWhenTypeIsBankAccount()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();
    }

    public function testInitiateApiVpaRequiredWhenTypeIsVpa()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]
        );

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();
    }

    public function testInitiateApiWithInvalidAccountTypeRaisesException()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();
    }

    public function testInitiateApiWhenTokenIsAbsent()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();
    }

    public function testInitiateApiWithInvalidTokenRaiseException()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();
    }

    public function testInitiateApiWithInvalidFundAccountIdThrowException()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();
    }

    public function testInitiateApiSuccessWhenValidVpaPassed($payoutLink = null, $createFa = true)
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->markTestSkipped('Only IMPS on Yesbank');

        if ($payoutLink === null)
        {
            $payoutLink = $this->fixtures->create('payout_link',
                                                  [
                                                      'balance_id' => $this->bankingBalance->getId(),
                                                      'user_id'    => 'MerchantUser01'
                                                  ]);
        }

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        if ($createFa === true)
        {
            $this->fixtures->create('fund_account:vpa',
                                    [
                                        'id'          => '100000000003fa',
                                        'source_type' => 'contact',
                                        'source_id'   => $this->contact->getId(),
                                        'merchant_id' => $this->contact->merchant->getId()
                                    ]);
        }

        return $this->startTest();
    }

    public function testInitiateApiSuccessWhenValidBankAccountPassed()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->markTestSkipped('Only IMPS on Yesbank');

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $fd = $this->fixtures->create('fund_account:bank_account',
                                      [
                                          'id'          => '100000000003fa',
                                          'source_type' => 'contact',
                                          'source_id'   => $this->contact->getId(),
                                          'merchant_id' => $this->contact->merchant->getId()
                                      ]);

        $resp = $this->startTest();

        // assert that a payout entity was create
        $this->assertEquals($payoutLink->payouts->count(), 1);

        //assert there is only one payout, and that belongs to the above payout-link
        $payout = $this->getDbEntities('payout')[0];

        $this->assertEquals($payout->payoutLink->getId(), $payoutLink->getId());

        $payoutLink->refresh();

        // assert payout link is now in processing state
        $this->assertEquals($payoutLink->getStatus(), Status::PROCESSING);

        return $resp;
    }

    public function testInitiateApiFailsWhenFundAccountIdPassedBelongsToAnotherContact()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $contact1 = $this->contact;

        $contact2 = $this->fixtures->create('contact',
                                            [
                                                'name'    => 'Test Contact 2',
                                                'email'   => 'test2@rzp.com',
                                                'contact' => '9876543210'
                                            ]);
        // create the payout-link that is associated with the first contact
        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id'           => $contact1->getId(),
                                                  'contact_name'         => $contact1->getName(),
                                                  'contact_phone_number' => $contact1->getContact(),
                                                  'contact_email'        => $contact1->getEmail(),
                                                  'balance_id'           => $this->bankingBalance->getId()
                                              ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        // create a fund account that belongs to the second contact
        $fundAccount = $this->fixtures->create('fund_account:bank_account',
                                               [
                                                   'id'          => '100000000003fa',
                                                   'source_type' => 'contact',
                                                   'source_id'   => $contact2->getId(),
                                                   'merchant_id' => $contact2->merchant->getId()
                                               ]);

        $this->startTest();
    }

    public function testPayoutStatusCreatedMakesLinkStatusProcessing()
    {
        self::markTestSkipped('Skipping this till feature storing is resolved');

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => '100000000003fa',
                                    'source_type' => 'contact',
                                    'source_id'   => $this->contact->getId(),
                                    'merchant_id' => $this->contact->merchant->getId()
                                ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->expectWebhookEventWithContents('payout_link.processing', 'PayoutLinkProcessingWebHook');

        $this->ba->noAuth();

        $this->startTest();

        $payoutLink->refresh();

        $this->assertEquals(Status::PROCESSING, $payoutLink->getStatus());
    }

    // removing this test case temporarily,
    // until we handle dispatching of update events once the transaction is completed
    public function _testPayoutStatusReversedMakesLinkStatusAttempted()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => '100000000003fa',
                                    'source_type' => 'contact',
                                    'source_id'   => $this->contact->getId(),
                                    'merchant_id' => $this->contact->merchant->getId()
                                ]);

        $this->expectWebhookEventWithContents('payout_link.attempted', 'PayoutLinkAttemptedWebHook');

        $this->startTest();

        $payout = $payoutLink->payouts()->first();

        $payout->setStatus(Payout\Status::INITIATED);

        $payout->save();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'        => 'failed',
            'failure_reason'    => 'This is a test failure',
        ]);

        $payoutLink->refresh();

        $this->assertEquals(Status::ATTEMPTED, $payoutLink->getStatus());

        $this->assertEquals($payout->getStatus() , Payout\Status::REVERSED);
    }

    // todo , pl need to first handle dispatch of event after transaction completion
    public function _testPayoutStatusProcessedMakesLinkStatusProcessed()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => '100000000003fa',
                                    'source_type' => 'contact',
                                    'source_id'   => $this->contact->getId(),
                                    'merchant_id' => $this->contact->merchant->getId()
                                ]);

        $this->expectWebhookEventWithContents('payout_link.processed', 'PayoutLinkProcessedWebHook');

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();

        $payout = $payoutLink->payouts()->first();

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'        => 'processed',
            'failure_reason'    => 'This is a test failure',
        ]);

        $payoutLink->refresh();

        $this->assertEquals(Status::PROCESSED, $payoutLink->getStatus());

        $this->assertEquals($payout->getStatus() , Payout\Status::PROCESSED);
    }


    public function testPayoutLinkSettingsApiSuccess()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->adminAuth();

        $this->startTest();

        $merchant = $this->contact->merchant;

        $settingAccessor = Settings\Accessor::for($merchant, Settings\Module::PAYOUT_LINK);

        $this->assertEquals($settingAccessor->get(Payout\Mode::IMPS) , 1);

        $this->assertEquals($settingAccessor->get(Payout\Mode::UPI) , 1);
    }

    public function testPayoutLinkSettingsGetApiSuccess()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $merchant = $this->contact->merchant;

        $settingAccessor = Settings\Accessor::for($merchant, Settings\Module::PAYOUT_LINK);

        $settingAccessor->upsert([
                                     Payout\Mode::UPI  => true,
                                     Payout\Mode::IMPS => 0
                                 ])
                        ->save();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpiPayoutModeWhenVpaFundAccountAdded()
    {
        $this->markTestSkipped('Only IMPS on Yesbank');

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $merchant = $this->contact->merchant;

        //enable UPI
        $settingAccessor = Settings\Accessor::for($merchant, Settings\Module::PAYOUT_LINK);

        $settingAccessor->upsert(Payout\Mode::UPI , 1);

        $this->startTest();

        $payoutLink->refresh();

        $this->assertEquals(Payout\Mode::UPI, $payoutLink->payouts()->first()->getMode());
    }

    public function testImpsPayoutModeWhenBankFundAccountAndAmountLessThanTwoLacs()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId(),
                                                  'amount'     => '100000'
                                              ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $merchant = $this->contact->merchant;

        //enable IMPS
        $settingAccessor = Settings\Accessor::for($merchant, Settings\Module::PAYOUT_LINK);

        $settingAccessor->upsert(Payout\Mode::IMPS , true)->save();

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => '100000000003fa',
                                    'source_type' => 'contact',
                                    'source_id'   => $this->contact->getId(),
                                    'merchant_id' => $this->contact->merchant->getId()
                                ]);

        $this->startTest();

        $payoutLink->refresh();

        $this->assertEquals(Payout\Mode::IMPS, $payoutLink->payouts()->first()->getMode());
    }

    public function testNeftPayoutModeWhenBankFundAccountAndAmountMoreThanTwoLacs()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->markTestSkipped('Only IMPS on Yesbank');

        $this->bankingBalance->balance = '300000000';

        $this->bankingBalance->save();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId(),
                                                  'amount'     => 30000000
                                              ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $merchant = $this->contact->merchant;

        //enable IMPS
        $settingAccessor = Settings\Accessor::for($merchant, Settings\Module::PAYOUT_LINK);

        $settingAccessor->upsert('IMPS' , true)->save();

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => '100000000003fa',
                                    'source_type' => 'contact',
                                    'source_id'   => $this->contact->getId(),
                                    'merchant_id' => $this->contact->merchant->getId()
                                ]);

        $this->startTest();

        $payoutLink->refresh();

        $this->assertEquals(Payout\Mode::NEFT, $payoutLink->payouts()->first()->getMode());
    }

    public function testPayoutLinkIssuedWebhookTriggered()
    {
        self::markTestSkipped('Skipping this till feature storing is resolved');

        $this->expectWebhookEventWithContents('payout_link.issued', 'PayoutLinkIssuedWebHook');

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => '100000000003fa',
                                    'source_type' => 'contact',
                                    'source_id'   => $this->contact->getId(),
                                    'merchant_id' => $this->contact->merchant->getId()
                                ]);

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testPayoutLinkThrowsExceptionWhenInitiateCalledWithInvalidState()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);
        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $payoutLink->setStatus(Status::PROCESSING);

        $payoutLink->saveOrFail();

        $this->startTest();
    }

    public function testGenerateOtpOnCancelledLinkThrowsException()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $payoutLink->setStatus(Status::CANCELLED);

        $payoutLink->saveOrFail();

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testVerifyOtpOnCancelledLinkThrowsException()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $payoutLink->setStatus(Status::CANCELLED);

        $payoutLink->saveOrFail();

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::VERIFY_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testInitiateApiWithInvalidFundAccountTypeThrowsException()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->mockRedisSuccess(__FUNCTION__, $payoutLink->getPublicId());

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $fd = $this->fixtures->create('fund_account',
                                      [
                                          'id'          => '100000000003fa',
                                          'source_type' => 'contact',
                                          'source_id'   => $this->contact->getId(),
                                          'merchant_id' => $this->contact->merchant->getId(),
                                          'account_type' => 'card',
                                          'account_id' => $card->getId()
                                      ]);

        $fd->saveOrFail();

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();
    }

    public function testInitiateApiHasPayoutInfo()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();
    }

    public function testPayoutLinkStatusApi()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->publicAuth();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::STATUS);

        $this->startTest();

    }

    public function testPayoutAmountAboveLimitFailsCreation()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testInvalidPurposeThrowsException()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();

    }

    /**
     * test branding true when both are there
     * test branding false when color is not there
     * test branding false when logo is not there
     * test payout_processed false
     * test link_processed true
     * test link created false
     * test link_created true
     */

    public function testOnBoardingApiAllFalseInDefaultState()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testOnBoardingApiBrandingTrue()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $this->fixtures->edit('merchant',
                              '10000000000000',
                              [
                                  'brand_color' => '#1234',
                                  'logo_url'    => 'http://testurl.com',
                              ]);

        $this->startTest();
    }

    public function testOnBoardingApiPayoutLinkCreatedTrue()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $testData = self::TEST_PAYOUT_LINK_PAYLOAD;

        $testData['balance_id'] = $this->bankingBalance->getId();

        $this->fixtures->create(self::FIXTURE_ENTITY, $testData);

        $this->startTest();
    }

    public function testOnBoardingApiPayoutLinkProcessedTrue()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $testData = self::TEST_PAYOUT_LINK_PAYLOAD;

        $testData['balance_id'] = $this->bankingBalance->getId();

        $testData['status'] = Status::PROCESSED;

        $this->fixtures->create(self::FIXTURE_ENTITY, $testData);

        $this->startTest();
    }


    // test that success email is in the queue, when we call the updater with success thingy
    // test that failure email is in the queue, when we call the updater with failed thingy
    public function testSuccessEmailQueuedWhenPayoutLinkGetsSuccessFromPayout()
    {
        self::markTestSkipped(self::SKIP_REASON);

        Mail::fake();

        $fa = $this->fixtures->create('fund_account:bank_account',
                                      [
                                          'id'          => '100000000003fa',
                                          'source_type' => 'contact',
                                          'source_id'   => $this->contact->getId(),
                                          'merchant_id' => $this->contact->merchant->getId()
                                      ]);

        $testData = self::TEST_PAYOUT_LINK_PAYLOAD;

        $testData += [
            Entity::BALANCE_ID      => $this->bankingBalance->getId(),
            Entity::SEND_SMS        => true,
            Entity::SEND_EMAIL      => true,
            Entity::FUND_ACCOUNT_ID => $fa->getId(),
        ];

        $testData[Entity::STATUS] = Status::PROCESSING;

        $payoutLink = $this->fixtures->create('payout_link', $testData);

        $payout = $this->fixtures->create('payout',
                                          [
                                              Payout\Entity::PAYOUT_LINK_ID => $payoutLink->getId(),
                                              Payout\Entity::STATUS         => Payout\Status::PROCESSED
                                          ]);
        $core = (new Core());

        $core->setModeAndDefaultConnection();

        $core->payoutUpdateListener($payoutLink, $payout);

        Mail::assertQueued(Success::class);
    }

    public function testFailedEmailQueuedWhenPayoutLinkGetsCancelledFromPayout()
    {
        self::markTestSkipped(self::SKIP_REASON);

        Mail::fake();

        $fa = $this->fixtures->create('fund_account:bank_account',
                                      [
                                          'id'          => '100000000003fa',
                                          'source_type' => 'contact',
                                          'source_id'   => $this->contact->getId(),
                                          'merchant_id' => $this->contact->merchant->getId()
                                      ]);

        $testData = self::TEST_PAYOUT_LINK_PAYLOAD;

        $testData += [
            Entity::BALANCE_ID      => $this->bankingBalance->getId(),
            Entity::SEND_SMS        => true,
            Entity::SEND_EMAIL      => true,
            Entity::FUND_ACCOUNT_ID => $fa->getId(),
        ];

        $testData[Entity::STATUS] = Status::PROCESSING;

        $payoutLink = $this->fixtures->create('payout_link', $testData);

        $payout = $this->fixtures->create('payout',
                                          [
                                              Payout\Entity::PAYOUT_LINK_ID => $payoutLink->getId(),
                                              Payout\Entity::STATUS         => Payout\Status::CANCELLED
                                          ]);
        $core = (new Core());

        $core->setModeAndDefaultConnection();

        $core->payoutUpdateListener($payoutLink, $payout);

        Mail::assertQueued(Failed::class);
    }

    public function testResendApiQueuesEmail()
    {
        self::markTestSkipped(self::SKIP_REASON);

        Mail::fake();

        $this->ba->proxyAuth();

        $testData = self::TEST_PAYOUT_LINK_PAYLOAD;

        $testData['send_email'] = true;

        $testData['balance_id'] = $this->bankingBalance->getId();

        // create payout link, and call the api
        $payoutLink = $this->fixtures->create('payout_link',
                                              $testData);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::RESEND_NOTIFICATION);

        $this->startTest();

        Mail::assertQueued(SendLink::class);
    }

    public function testResendApiThrowsErrorForSendSmsWithoutContact()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_phone_number' => '',
                                                  'balance_id'            => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::RESEND_NOTIFICATION);

        $this->startTest();
    }

    public function testResendApiThrowsErrorForSendEmailWithoutEmail()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_email' => '',
                                                  'balance_id'     => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::RESEND_NOTIFICATION);

        $this->startTest();
    }

    public function testResendApiSendSmsWithSmsPassed()
    {
        self::markTestSkipped(self::SKIP_REASON);

        Mail::fake();

        $this->ba->proxyAuth();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_phone_number' => '',
                                                  'balance_id'           => $this->bankingBalance->getId(),
                                                  'send_sms'             => true,
                                                  'send_email'           => true
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::RESEND_NOTIFICATION);

        $this->startTest();

        Mail::assertQueued(SendLink::class);
    }

    public function testResendApiSendEmailWithEmailPassed()
    {
        self::markTestSkipped(self::SKIP_REASON);

        Mail::fake();

        $this->ba->proxyAuth();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_email' => '',
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::RESEND_NOTIFICATION);

        $this->startTest();

        Mail::assertQueued(SendLink::class);
    }

    public function testResendApiUpdateContactThrowExceptionWhenContactPresent()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $payoutLink = $this->fixtures->create('payout_link',
            [
                'balance_id' => $this->bankingBalance->getId()
            ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::RESEND_NOTIFICATION);

        $this->startTest();
    }

    public function testResendApiUpdateEmailThrowExceptionWhenEmailPresent()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $this->ba->proxyAuth();

        $payoutLink = $this->fixtures->create('payout_link',
            [
                'balance_id' => $this->bankingBalance->getId()
            ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::RESEND_NOTIFICATION);

        $this->startTest();
    }

    protected function mockRedisSuccess($funcName, $payoutLinkId)
    {
        $token = $this->app['token_service']->generate($payoutLinkId);

        $this->testData[$funcName]['request']['content']['token'] = $token;
    }

    protected function addAccountNumberParameter($funcName)
    {
        $this->testData[$funcName]['request']['content']['account_number'] =
            $this->virtualAccount->bankAccount->getAccountNumber();
    }

    protected function setUrl($funcName, $payoutLinkId, $path = '')
    {
        $this->testData[$funcName]['request']['url'] = '/payout-links/' . $payoutLinkId . '/' . $path;
    }

    protected function setCreatedBy($funcName, $payoutLink)
    {
        if ($payoutLink->user !== null)
        {
            $this->testData[$funcName]['response']['content']['timeline'][0]['created_by'] =
                $payoutLink->user->getName();

        }
        else
        {
            $this->testData[$funcName]['response']['content']['timeline'][0]['created_by'] = null;
        }
    }

    public function testGetFundAccountsOfContact()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $contact = $this->fixtures->create('contact',
            [
                'id'      => '1000020contact',
                'contact' => '8888888888',
                'email'   => '',
                'name'    => 'test user'
            ]);

        $this->fixtures->create('fund_account:bank_account',
            [
                'id'          => '100000000010fa',
                'source_type' => 'contact',
                'source_id'   => '1000020contact',
                'merchant_id' => $this->contact->merchant->getId()
            ]);

        $this->fixtures->create('fund_account:bank_account',
            [
                'id'          => '100000000011fa',
                'source_type' => 'contact',
                'source_id'   => '1000020contact',
                'merchant_id' => $this->contact->merchant->getId()
            ]);

        $this->fixtures->create('fund_account:bank_account',
            [
                'id'          => '100000000012fa',
                'source_type' => 'contact',
                'source_id'   => '1000020contact',
                'merchant_id' => $this->contact->merchant->getId()
            ]);

        $payoutLink = $this->fixtures->create('payout_link',
            [
                'contact_id'  => $contact->getId(),
                'balance_id'  => $this->bankingBalance->getId()
            ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), 'fund-accounts');

        $this->startTest();
    }

    public function testGetFundAccountsOfContactWithInactiveFundAccount()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $contact = $this->fixtures->create('contact',
            [
                'id'      => '1000021contact',
                'contact' => '8888888888',
                'email'   => '',
                'name'    => 'test user'
            ]);

        $this->fixtures->create('fund_account:bank_account',
            [
                'id'          => '100000000010fa',
                'source_type' => 'contact',
                'source_id'   => '1000021contact',
                'merchant_id' => $this->contact->merchant->getId()
            ]);

        $this->fixtures->create('fund_account:bank_account',
            [
                'id'          => '100000000011fa',
                'source_type' => 'contact',
                'source_id'   => '1000021contact',
                'merchant_id' => $this->contact->merchant->getId(),
                'active'      => 0
            ]);

        $this->fixtures->create('fund_account:bank_account',
            [
                'id'          => '100000000012fa',
                'source_type' => 'contact',
                'source_id'   => '1000021contact',
                'merchant_id' => $this->contact->merchant->getId()
            ]);

        $payoutLink = $this->fixtures->create('payout_link',
            [
                'contact_id'  => $contact->getId(),
                'balance_id'  => $this->bankingBalance->getId()
            ]);

        $this->mockRedisSuccess(__FUNCTION__ , $payoutLink->getPublicId());

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), 'fund-accounts');

        $this->startTest();
    }

    public function testPayoutLinkPaymentMode()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $payout_mode = 'IMPS';

        $contact = $this->fixtures->create('contact',
                                           [
                                               'id'      => '1000011contact',
                                               'contact' => '8888888888',
                                               'email'   => '',
                                               'name'    => 'test user'
                                           ]);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $contact->getId(),
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $bankingAccount = $this->fixtures->create('banking_account',
                                                  [
                                                      'channel'      => 'hdfc',
                                                      'account_type' => 'card'
                                                  ]);

        $payoutLink->balance->bankingAccount =$bankingAccount;

        $payout = $this->fixtures->create('payout',
                                          [
                                              'payout_link_id' => $payoutLink->getId(),
                                              'mode'           => $payout_mode
                                          ]);

        $merchant = $this->contact->merchant;

        $core = (new Core());

        $reflection = new ReflectionClass($core);

        $method = $reflection->getMethod('getDataForHostedPage');

        $method->setAccessible(true);

        $reflectionProperty = $reflection->getProperty('merchant');

        $reflectionProperty->setAccessible(true);

        $reflectionProperty->setValue($core, $merchant);

        $response = $method->invokeArgs($core, [$payoutLink]);

        $this->assertEquals($payout_mode , $response['payout_mode']);
    }

    public function testPayoutLinkGetPayoutSuccess()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $contact = $this->fixtures->create('contact',
                                           [
                                               'id'      => '1000011contact',
                                               'contact' => '8888888888',
                                               'email'   => '',
                                               'name'    => 'test user'
                                           ]);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $contact->getId(),
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->assertEquals($payoutLink->getPayoutMode(), null);

    }

    public function testPayoutLinkGetPayoutFailed()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $contact = $this->fixtures->create('contact',
                                           [
                                               'id'      => '1000011contact',
                                               'contact' => '8888888888',
                                               'email'   => '',
                                               'name'    => 'test user'
                                           ]);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $contact->getId(),
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->fixtures->create('payout',
                                [
                                    'payout_link_id' => $payoutLink->getId(),
                                    'mode'           => Payout\Mode::UPI
                                ]);

        $this->assertEquals($payoutLink->getPayoutMode(), Payout\Mode::UPI);

    }

    public function testPayoutLinkPaymentModeWithoutPayout()
    {
        self::markTestSkipped(self::SKIP_REASON);

        $contact = $this->fixtures->create('contact',
                                           [
                                               'id'      => '1000011contact',
                                               'contact' => '8888888888',
                                               'email'   => '',
                                               'name'    => 'test user'
                                           ]);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $contact->getId(),
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $bankingAccount = $this->fixtures->create('banking_account',
                                                  [
                                                      'channel'      => 'hdfc',
                                                      'account_type' => 'card'
                                                  ]);

        $payoutLink->balance->bankingAccount =$bankingAccount;

        $payout = $this->fixtures->create('payout' , [

        ]);

        $merchant = $this->contact->merchant;

        $core = (new Core());

        $reflection = new ReflectionClass($core);

        $method = $reflection->getMethod('getDataForHostedPage');

        $method->setAccessible(true);

        $reflectionProperty = $reflection->getProperty('merchant');

        $reflectionProperty->setAccessible(true);

        $reflectionProperty->setValue($core, $merchant);

        $response = $method->invokeArgs($core, [$payoutLink]);

        $this->assertNull($response['payout_mode']);
    }

    public function testBccEmailForSendLinkInternal()
    {
        Mail::fake();

        $this->ba->payoutLinksAppAuth();

        $this->startTest();

        Mail::assertQueued(SendLinkInternal::class, function ($mail)
        {
            $this->assertEquals('test@rzp.com', $mail->bcc[0]['address']);

            return true;
        });
    }

    public function testNoBccEmailForSendLinkInternal()
    {
        Mail::fake();

        $this->ba->payoutLinksAppAuth();

        $this->startTest();

        Mail::assertQueued(SendLinkInternal::class, function ($mail)
        {
            $this->assertEmpty($mail->bcc);

            return true;
        });
    }

    public function testBccEmailForSuccessInternal()
    {
        Mail::fake();

        $this->ba->payoutLinksAppAuth();

        $this->startTest();

        Mail::assertQueued(SuccessInternal::class, function ($mail)
        {
            $this->assertEquals('test@rzp.com', $mail->bcc[0]['address']);

            return true;
        });
    }

    public function testNoBccEmailForSuccessInternal()
    {
        Mail::fake();

        $this->ba->payoutLinksAppAuth();

        $this->startTest();

        Mail::assertQueued(SuccessInternal::class, function ($mail)
        {
            $this->assertEmpty($mail->bcc);

            return true;
        });
    }

    public function testCreatePLForMissingContactDetails()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('create')->andThrow(new BadRequestException(ErrorCode::BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED, null, null, 'Invalid request payload'));

        $this->app->instance('payout-links', $plMock);

        $auth = $this->app['basicauth'];

        $this->app->instance('basicauth', $auth);

        $merchant = $this->fixtures->create('merchant',
            [
                'id'    => '12345678901234'
            ]);

        $auth->setMerchant($merchant);

        // use razorx feature
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === Merchant\RazorxTreatment::RX_PAYOUT_LINK_MICROSERVICE)
                    {
                        return 'on';
                    }

                    return 'off';
                }));

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testBulkResendNotification()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $resendNotificaitonResponse = [
            "success_payout_link_ids" => "poutlk_4eWc1vLJKgR2hE,poutlk_8QmI1vLJKgQBgq,poutlk_8bLC1vLJKfYTMq,poutlk_8gcr1vLJKftwqO,poutlk_4p841vLJKhV7qy",
            "failed_payout_link_ids" => "poutlk_4KGz1vLJNkQ79k,poutlk_4ZBX1vLJP4B03I,poutlk_8Rjb1vLJYAOBZg"
        ];

        $plMock->shouldReceive('bulkResendNotification')->andReturn($resendNotificaitonResponse);

        $this->app->instance('payout-links', $plMock);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testShopifyAppInstallation()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $redirectUri = "https://test.myshopify.com/admin/oauth/authorize?client_id=CLIENT_ID&scope=CLIENT_SECRET&redirect_uri=REDIRECT_URI&state=STATE";

        $plMock->shouldReceive('getShopifyAppInstallRedirectURI')->andReturn($redirectUri);

        $this->app->instance('payout-links', $plMock);

        $this->ba->directAuth();

        $request = [
            'method' => 'GET',
            'url'    => '/payout-links/shopify/install',
            'content' => [
                'shop' => 'test.myshopify.com',
                'timestamp' => '1610405523',
                'hmac' => 'some-hmac',
            ]
        ];

        $response = $this->sendRequest($request);

        // redirection request
        $this->assertEquals(302, $response->getStatusCode());

        $this->assertContentTypeForResponse('text/html; charset=UTF-8', $response);
    }

    public function testIntegrateApp()
    {
        $response = ['is_integration_success' => true];

        $plMock = $this->mockPLServiceMakeRequestMethod($response);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testFetchShopifyOrderDetails()
    {
        $response = [
            'shop' => 'test.myshopify.com',
            'order_id' => '123456789',
            'order_amount' => 1234.5,
        ];

        $plMock = $this->mockPLServiceMakeRequestMethod($response);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testFetchIntegrationDetails()
    {
        $response = [
            'id' => '4ZAH1sxYJ0RGrU',
            'source' => 'shopify',
            'source_identifier' => 'test.myshopify.com',
            'merchant_id' => '10000000000000',
            'integration_status' => 'success'
        ];

        $plMock = $this->mockPLServiceMakeRequestMethod($response);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testUninstallShopifyApp()
    {
        $response = array();

        $plMock = $this->mockPLServiceMakeRequestMethod($response);

        $this->app->instance('payout-links', $plMock);

        $this->ba->directAuth();

        $request = [
            'method' => 'POST',
            'url'    => '/payout-links/shopify/uninstall',
            'headers' => [
                'X-Shopify-Topic' => 'app/uninstalled',
                'X-Shopify-Hmac-Sha256' => 'some-hmac',
                'X-Shopify-Shop-Domain' => 'test.myshopify.com',
                'X-Shopify-API-Version' => '2020-21',
                'X-Shopify-Webhook-Id' => 'webhook-id-1'
            ],
            'content' => [
                'data-1' => 'value-1',
                'data-2' => 'value-2',
            ]
        ];

        $response = $this->sendRequest($request);

        // redirection request
        $this->assertResponseOk($response);
    }

    protected function mockPLServiceMakeRequestMethod($response)
    {
        $plMock = $this->getMockBuilder("RZP\Services\PayoutLinks")
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array("makeRequest"))
            ->getMock();

        $plMock->method('makeRequest')->willReturn($response);

        return $plMock;
    }

    public function testCreateDemoPayoutLink()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('createDemoPayoutLink')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->directAuth();

        $request = [
            'method' => 'POST',
            'url'    => '/demo/payout-links',
            'content' => []
        ];

        $response = $this->sendRequest($request);

        // redirection request
        $this->assertResponseOk($response);
    }

    public function testGetDemoHostedPageData()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks')->makePartial()->shouldAllowMockingProtectedMethods();

        $plMock->shouldReceive('makeRequest')->andReturn($this->mockedDemoPLData());

        $plMock->shouldReceive('getEnvironment')->andReturn('testing');

        $this->app->instance('payout-links', $plMock);

        $this->ba->directAuth();

        $request = [
            'method' => 'GET',
            'url'    => '/demo/payout-links/poutlk_HQ9ddkWljqA2Q8/view',
        ];

        $response = $this->sendRequest($request);

        // redirection request
        $this->assertResponseOk($response);

        $this->assertContentTypeForResponse('text/html; charset=UTF-8', $response);
    }

    protected function mockedDemoPLData()
    {
        return [
            'payout_link_response' => [
                'id' => 'poutlk_HQ9ddkWljqA2Q8',
                'status' => 'issued',
                'amount' => 1,
                'currency' => 'INR',
                'description' => 'testing demp pl',
                'contact_name' => 'test contact',
                'contact_email' => 'test.email@rzp.com',
                'contact_phone_number' => '9999988888',
                'receipt' => 'receipt'
            ],
            'merchant_info' => [
                'brand_logo' => 'logo-url',
                'brand_color' => 'color',
                'billing_label' => 'test merchant',
            ],
            'settings' => []
        ];
    }

    public function testGenerateDemoOTP()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('generateAndSendCustomerOtpDemo')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->directAuth();

        $request = [
            'method' => 'POST',
            'url'    => '/demo/payout-links/poutlk_HQ9ddkWljqA2Q8/generate-customer-otp',
        ];

        $response = $this->sendRequest($request);

        // redirection request
        $this->assertResponseOk($response);
    }

    public function testVerifyDemoOTP()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('verifyCustomerOtpDemo')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->directAuth();

        $request = [
            'method' => 'POST',
            'url'    => '/demo/payout-links/poutlk_HQ9ddkWljqA2Q8/verify-customer-otp',
        ];

        $response = $this->sendRequest($request);

        // redirection request
        $this->assertResponseOk($response);
    }

    public function testInitiateDemoPayoutLink()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('initiateDemo')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->directAuth();

        $request = [
            'method' => 'POST',
            'url'    => '/demo/payout-links/poutlk_HQ9ddkWljqA2Q8/initiate',
        ];

        $response = $this->sendRequest($request);

        // redirection request
        $this->assertResponseOk($response);
    }

    public function testDemoEmailInternal()
    {
        Mail::fake();

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('sendDemoEmailInternal')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->payoutLinksAppAuth();

        $request = [
            'method' => 'POST',
            'url'    => '/payout-links/demo/send-email',
        ];

        $response = $this->sendRequest($request);

        // redirection request
        $this->assertResponseOk($response);
    }

    public function testCreateWithUserIdInInputPrivateAuth()
    {
        $this->ba->privateAuth();

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        // user_id is there in the request parameter, but will be unset
        // final payout-link created will not have any user_id populated
        $plMock->shouldReceive('create')->andReturn([
            'user_id'=> '',
            'id' => 'poutlk_DWivysHLcspTNI',
            'amount' => 10,
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->startTest();
    }

    public function testCreateWithUserIdInInputProxyAuth()
    {
        $user = $this->fixtures->create('user', [
            'id'       => '20000000000000',
        ]);

        $this->fixtures->create('user:user_merchant_mapping', [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
            'product'     => 'primary',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('create')->andReturn([
            'user_id'=> '20000000000000',
            'id' => 'poutlk_DWivysHLcspTNI',
            'amount' => 10,
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->startTest();
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
            'name'   => AdminPermission::PAYOUT_LINKS_ADMIN_BULK_CREATE,
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

    public function testSendReminderCallbackForCancellingReminder()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks')->makePartial()->shouldAllowMockingProtectedMethods();

        $plMock->shouldReceive('makeRequest')->andReturn($this->mockedCancelReminderResponse());

        $this->app->instance('payout-links', $plMock);

        $this->ba->reminderAppAuth();

        $this->startTest();
    }

    public function testSendReminderCallbackForContinueReminder()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks')->makePartial()->shouldAllowMockingProtectedMethods();

        $plMock->shouldReceive('makeRequest')->andReturn($this->mockedContinueReminderResponse());

        $this->app->instance('payout-links', $plMock);

        $this->ba->reminderAppAuth();

        $this->startTest();
    }

    public function testExpireCallbackForCancellingReminder()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks')->makePartial()->shouldAllowMockingProtectedMethods();

        $plMock->shouldReceive('makeRequest')->andReturn($this->mockedCancelReminderResponse());

        $this->app->instance('payout-links', $plMock);

        $this->ba->reminderAppAuth();

        $this->startTest();
    }

    public function testExpireCallbackForContinueReminder()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks')->makePartial()->shouldAllowMockingProtectedMethods();

        $plMock->shouldReceive('makeRequest')->andReturn($this->mockedContinueReminderResponse());

        $this->app->instance('payout-links', $plMock);

        $this->ba->reminderAppAuth();

        $this->startTest();
    }

    public function testUpdatePayoutLink()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('updatePayoutLink')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->privateAuth();

        $this->startTest();
    }

    private function mockedCancelReminderResponse()
    {
        return [
            'status_code' => 400,
            'error_response' => [
                '_internal' => [
                    'error_code' => 'BAD_REQUEST_REMINDER_NOT_APPLICABLE',
                ]
            ],
        ];
    }

    private function mockedContinueReminderResponse()
    {
        return [
            'status_code' => 200,
            'success_response' => [
                'success' => true,
            ],
        ];
    }

    public function testSendReminderMail()
    {
        Mail::fake();

        $this->ba->payoutLinksAppAuth();

        $this->startTest();

        Mail::assertQueued(SendReminderInternal::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertArrayHasKey('expire_by_date', $data);

            $this->assertArrayHasKey('expire_by_time', $data);

            $this->assertEquals('20-Jul-2021', $data['expire_by_date']);

            $this->assertEquals('10:10:10', $data['expire_by_time']);

            return true;
        });
    }

    public function testSendProcessingExpiredMail()
    {
        Mail::fake();

        $this->ba->payoutLinksAppAuth();

        $this->startTest();

        Mail::assertQueued(SendProcessingExpiredInternal::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertArrayHasKey('expire_by_date', $data);

            $this->assertArrayHasKey('expire_by_time', $data);

            $this->assertArrayHasKey('support_contact', $data);

            $this->assertArrayHasKey('support_email', $data);

            $this->assertArrayHasKey('support_url', $data);

            $this->assertEquals('20-Jul-2021', $data['expire_by_date']);

            $this->assertEquals('10:10:10', $data['expire_by_time']);

            return true;
        });
    }

    public function testExpiryCronJob()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('expireCronjob');

        $this->app->instance('payout-links', $plMock);

        $this->ba->cronAuth();

        $request = [
            'method' => 'POST',
            'url'    => '/payout-links/expire-cron-job',
        ];

        $response = $this->sendRequest($request);

        $this->assertResponseOk($response);
    }

    /*
     * In case of Private Auth, expiry keys will be there in the response iff expiry settings is enabled,
     * even if the payout-link has some expiry
     */
    public function testPrivateAuthFetchPlForExpiryEnabledMerchantNoExpiryDataInPlMSResponse()
    {
        $response = [
            'id' => 'poutlk_link-id',
            'amount' => 1000,
            'send_sms' => false,
            'send_email' => false,
            'contact' => [
                'name' => 'test',
                'email' => 'test@gmail.com',
            ],
            'is_expiry_enabled' => true,
        ];

        $plMock = $this->mockPLServiceMakeRequestMethod($response);

        $this->app->instance('payout-links', $plMock);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $request = [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_link-id'
        ];

        $response = $this->sendRequest($request);

        $this->assertResponseOk($response);

        $responseData = json_decode(json_encode($response->getData()), true);

        // is_expiry_enabled key should not be there
        $this->assertArrayNotHasKey('is_expiry_enabled', $responseData);

        // expire_by and expired_at keys should be there with values 0
        $this->assertArrayHasKey('expire_by', $responseData);
        $this->assertEquals(0, $responseData['expire_by']);

        $this->assertArrayHasKey('expired_at', $responseData);
        $this->assertEquals(0, $responseData['expired_at']);

        // reminders key should not be there
        $this->assertArrayNotHasKey('reminders', $responseData);
    }

    public function testPrivateAuthFetchPlForExpiryEnabledMerchantExpiryDataPresentInPlMSResponse()
    {
        $response = [
            'id' => 'poutlk_link-id',
            'amount' => 1000,
            'send_sms' => false,
            'send_email' => false,
            'contact' => [
                'name' => 'test',
                'email' => 'test@gmail.com',
            ],
            'expire_by' => Carbon::now()->getTimestamp(),
            'is_expiry_enabled' => true,
        ];

        $plMock = $this->mockPLServiceMakeRequestMethod($response);

        $this->app->instance('payout-links', $plMock);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $request = [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_link-id'
        ];

        $response = $this->sendRequest($request);

        $this->assertResponseOk($response);

        $responseData = json_decode(json_encode($response->getData()), true);

        // is_expiry_enabled key should not be there
        $this->assertArrayNotHasKey('is_expiry_enabled', $responseData);

        // expire_by and expired_at keys should be there
        $this->assertArrayHasKey('expire_by', $responseData);

        $this->assertArrayHasKey('expired_at', $responseData);

        // reminders key should not be there
        $this->assertArrayNotHasKey('reminders', $responseData);
    }

    public function testPrivateAuthFetchPlForExpiryDisabledMerchantExpiryDataPresentInPlMSResponse()
    {
        // in case expiry is disabled, the boolean flag is_expiry_enabled won't be in PL-MS response
        $response = [
            'id' => 'poutlk_link-id',
            'amount' => 1000,
            'send_sms' => false,
            'send_email' => false,
            'contact' => [
                'name' => 'test',
                'email' => 'test@gmail.com',
            ],
            'expire_by' => Carbon::now()->getTimestamp(),
        ];

        $plMock = $this->mockPLServiceMakeRequestMethod($response);

        $this->app->instance('payout-links', $plMock);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $request = [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_link-id'
        ];

        $response = $this->sendRequest($request);

        $this->assertResponseOk($response);

        $responseData = json_decode(json_encode($response->getData()), true);

        // is_expiry_enabled key should not be there
        $this->assertArrayNotHasKey('is_expiry_enabled', $responseData);

        // expire_by and expired_at keys should not be there
        $this->assertArrayNotHasKey('expire_by', $responseData);

        $this->assertArrayNotHasKey('expired_at', $responseData);

        // reminders key should not be there
        $this->assertArrayNotHasKey('reminders', $responseData);
    }

    /*
     * In case of Proxy Auth, expiry keys will be there in the response even if expiry settings is disabled
     */
    public function testProxyAuthFetchPlForExpiryEnabledMerchantNoExpiryDataInPlMSResponse()
    {
        $response = [
            'id' => 'poutlk_link-id',
            'amount' => 1000,
            'send_sms' => false,
            'send_email' => false,
            'contact' => [
                'name' => 'test',
                'email' => 'test@gmail.com',
            ],
            'is_expiry_enabled' => true,
        ];

        $plMock = $this->mockPLServiceMakeRequestMethod($response);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $request = [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_link-id'
        ];

        $response = $this->sendRequest($request);

        $this->assertResponseOk($response);

        $responseData = json_decode(json_encode($response->getData()), true);

        // is_expiry_enabled key should not be there
        $this->assertArrayNotHasKey('is_expiry_enabled', $responseData);

        // expire_by and expired_at keys should be there with values 0
        $this->assertArrayHasKey('expire_by', $responseData);
        $this->assertEquals(0, $responseData['expire_by']);

        $this->assertArrayHasKey('expired_at', $responseData);
        $this->assertEquals(0, $responseData['expired_at']);

        // reminders key should be there
        $this->assertArrayHasKey('reminders', $responseData);
    }

    public function testProxyAuthFetchPlForExpiryEnabledMerchantExpiryDataPresentInPlMSResponse()
    {
        $response = [
            'id' => 'poutlk_link-id',
            'amount' => 1000,
            'send_sms' => false,
            'send_email' => false,
            'contact' => [
                'name' => 'test',
                'email' => 'test@gmail.com',
            ],
            'expire_by' => Carbon::now()->getTimestamp(),
            'is_expiry_enabled' => true,
        ];

        $plMock = $this->mockPLServiceMakeRequestMethod($response);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $request = [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_link-id'
        ];

        $response = $this->sendRequest($request);

        $this->assertResponseOk($response);

        $responseData = json_decode(json_encode($response->getData()), true);

        // is_expiry_enabled key should not be there
        $this->assertArrayNotHasKey('is_expiry_enabled', $responseData);

        // expire_by and expired_at keys should be there
        $this->assertArrayHasKey('expire_by', $responseData);

        $this->assertArrayHasKey('expired_at', $responseData);

        // reminders key should be there
        $this->assertArrayHasKey('reminders', $responseData);
    }

    public function testProxyAuthFetchPlForExpiryDisabledMerchantExpiryDataPresentInPlMSResponse()
    {
        // in case expiry is disabled, the boolean flag is_expiry_enabled won't be in PL-MS response
        $response = [
            'id' => 'poutlk_link-id',
            'amount' => 1000,
            'send_sms' => false,
            'send_email' => false,
            'contact' => [
                'name' => 'test',
                'email' => 'test@gmail.com',
            ],
            'expire_by' => Carbon::now()->getTimestamp(),
        ];

        $plMock = $this->mockPLServiceMakeRequestMethod($response);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $request = [
            'method' => 'GET',
            'url'    => '/payout-links/poutlk_link-id'
        ];

        $response = $this->sendRequest($request);

        $this->assertResponseOk($response);

        $responseData = json_decode(json_encode($response->getData()), true);

        // is_expiry_enabled key should not be there
        $this->assertArrayNotHasKey('is_expiry_enabled', $responseData);

        // expire_by and expired_at keys should not be there
        $this->assertArrayHasKey('expire_by', $responseData);

        $this->assertArrayHasKey('expired_at', $responseData);

        // reminders key should be there
        $this->assertArrayHasKey('reminders', $responseData);
    }

    public function testGetHostedPageDataForAppAuth()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('viewHostedPageData')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->payoutLinksCustomerPageAuth('live');

        $this->startTest();
    }

    private function prepareBankingAccountData($merchantId, $mode = 'live')
    {
        $xBalance1 = $this->fixtures->on($mode)->create('balance',
            [
                'merchant_id'       => $merchantId,
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '2224440041626905',
                'balance'           => 300,
            ]);

        return $this->fixtures->on($mode)->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => $merchantId,
            'balance_id'            => $xBalance1->getId(),
            'channel'               => 'yesbank',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);
    }

    public function testGetHostedPageDataWithEmptySupportEmailForAppAuth()
    {
        $mid = '10000000000000';

        $ba = $this->prepareBankingAccountData($mid);

        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'payout_link_response' => [
                'id' => '12345',
                'merchant_id' => $mid,
                'account_number' => $ba['account_number'],
                'status' => 'expired',
                'contact' => [
                    'name' => 'test',
                    'email' => 'test@gmail.com',
                ],
                'amount' => 100,
                'currency' => 'INR',
            ],
            'settings' => [
                'merchant_id' => $mid,
            ]
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->payoutLinksCustomerPageAuth('live');

        $this->startTest();
    }

    public function testGetHostedPageDataWithIssuedLinkForAppAuth()
    {
        $mid = '10000000000000';

        $ba = $this->prepareBankingAccountData($mid);

        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'payout_link_response' => [
                'id' => '12345',
                'merchant_id' => $mid,
                'account_number' => $ba['account_number'],
                'status' => 'issued',
                'contact' => [
                    'name' => 'test',
                    'email' => 'test@gmail.com',
                ],
                'amount' => 100,
                'currency' => 'INR',
            ],
            'settings' => [
                'merchant_id' => $mid,
                'mode' => [
                    'support_email' => 'support@gmail.com'
                ]
            ]
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->payoutLinksCustomerPageAuth('live');

        $this->startTest();
    }

    public function testGetHostedPageDataWithProcessingLinkForAppAuth()
    {
        $mid = '10000000000000';

        $ba = $this->prepareBankingAccountData($mid);

        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'payout_link_response' => [
                'id' => '12345',
                'merchant_id' => $mid,
                'account_number' => $ba['account_number'],
                'status' => 'processing',
                'contact' => [
                    'name' => 'test',
                    'email' => 'test@gmail.com',
                ],
                'amount' => 100,
                'currency' => 'INR',
            ],
            'settings' => [
                'merchant_id' => $mid,
                'mode' => [
                    'support_email' => 'support@gmail.com'
                ]
            ]
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->payoutLinksCustomerPageAuth('live');

        $this->startTest();
    }

    public function testGetHostedPageDataWithProcessedLinkForAppAuth()
    {
        $mid = '10000000000000';

        $ba = $this->prepareBankingAccountData($mid);

        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'payout_link_response' => [
                'id' => '12345',
                'merchant_id' => $mid,
                'account_number' => $ba['account_number'],
                'status' => 'processed',
                'contact' => [
                    'name' => 'test',
                    'email' => 'test@gmail.com',
                ],
                'amount' => 100,
                'currency' => 'INR',
            ],
            'settings' => [
                'merchant_id' => $mid,
                'mode' => [
                    'support_email' => 'support@gmail.com'
                ]
            ]
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->payoutLinksCustomerPageAuth('live');

        $this->startTest();
    }

    public function testGetHostedPageDataWithCancelledLinkForAppAuth()
    {
        $mid = '10000000000000';

        $ba = $this->prepareBankingAccountData($mid);

        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'payout_link_response' => [
                'id' => '12345',
                'merchant_id' => $mid,
                'account_number' => $ba['account_number'],
                'status' => 'cancelled',
                'contact' => [
                    'name' => 'test',
                    'email' => 'test@gmail.com',
                ],
                'amount' => 100,
                'currency' => 'INR',
            ],
            'settings' => [
                'merchant_id' => $mid,
                'mode' => [
                    'support_email' => 'support@gmail.com'
                ]
            ]
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->payoutLinksCustomerPageAuth('live');

        $this->startTest();
    }

    public function testGetHostedPageDataForExpiredLinkForAppAuth()
    {
        $mid = '10000000000000';

        $ba = $this->prepareBankingAccountData($mid);

        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'payout_link_response' => [
                'id' => '12345',
                'merchant_id' => $mid,
                'account_number' => $ba['account_number'],
                'status' => 'expired',
                'contact' => [
                    'name' => 'test',
                    'email' => 'test@gmail.com',
                ],
                'amount' => 100,
                'currency' => 'INR',
            ],
            'settings' => [
                'merchant_id' => $mid,
                'mode' => [
                    'support_email' => 'support@gmail.com'
                ]
            ]
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->payoutLinksCustomerPageAuth('live');

        $this->startTest();
    }

    public function testGetHostedPageDataForPendingLinkForAppAuth()
    {
        $mid = '10000000000000';

        $ba = $this->prepareBankingAccountData($mid);

        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'payout_link_response' => [
                'id' => '12345',
                'merchant_id' => $mid,
                'account_number' => $ba['account_number'],
                'status' => 'pending',
                'contact' => [
                    'name' => 'test',
                    'email' => 'test@gmail.com',
                ],
                'amount' => 100,
                'currency' => 'INR',
            ],
            'settings' => [
                'merchant_id' => $mid,
                'mode' => [
                    'support_email' => 'support@gmail.com'
                ]
            ]
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->payoutLinksCustomerPageAuth('live');

        $this->startTest();
    }

    public function testGetHostedPageDataForRejectedLinkForAppAuth()
    {
        $mid = '10000000000000';

        $ba = $this->prepareBankingAccountData($mid);

        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'payout_link_response' => [
                'id' => '12345',
                'merchant_id' => $mid,
                'account_number' => $ba['account_number'],
                'status' => 'rejected',
                'contact' => [
                    'name' => 'test',
                    'email' => 'test@gmail.com',
                ],
                'amount' => 100,
                'currency' => 'INR',
            ],
            'settings' => [
                'merchant_id' => $mid,
                'mode' => [
                    'support_email' => 'support@gmail.com'
                ]
            ]
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->payoutLinksCustomerPageAuth('live');

        $this->startTest();
    }

    public function testGetDemoHostedPageDataForAppAuth()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('viewDemoHostedPageData')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->payoutLinksCustomerPageAuth();

        $this->startTest();
    }

    protected function getMockedServiceMakeRequestErrorResponse(string $message)
    {
        $plMock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest'))
            ->getMock();

        $plMock->method('makeRequest')->willThrowException(new BadRequestException(
            ErrorCode::BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED,
            null,
            null,
            $message));

        return $plMock;
    }

    protected function getPlMockForTestMode(string $message)
    {
        return $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest'))
            ->getMock();
    }

    protected function getMockedServiceMakeRequestSuccessResponse(array $successData)
    {
        $plMock = $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest'))
            ->getMock();

        $plMock->method('makeRequest')->willReturn($successData);

        return $plMock;
    }

    protected function getMockedPlForTestMode()
    {
        return $this->getMockBuilder('RZP\Services\PayoutLinks')
            ->enableOriginalConstructor()
            ->setConstructorArgs([$this->app])
            ->setMethods(array('makeRequest'))
            ->getMock();
    }

    public function testApprovePayoutLinkInternalServerError()
    {
        $plMock = $this->getMockedServiceMakeRequestErrorResponse('The server encountered an unexpected condition which prevented it from fulfilling the request.');

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testApprovePayoutLinkWorkflowAlreadyProcessed()
    {
        $plMock = $this->getMockedServiceMakeRequestErrorResponse('workflow-already-processed');

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testApprovePayoutLinkPayoutLinkNotPendingOnCurrentUser()
    {
        $plMock = $this->getMockedServiceMakeRequestErrorResponse('workflow-not-pending-on-current-user');

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testApprovePayoutLinkUserAlreadyActedInSameGroup()
    {
        $plMock = $this->getMockedServiceMakeRequestErrorResponse('USER_ALREADY_TAKEN_ACTION_ON_STATE_GROUP');

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testApprovePayoutLinkSuccess()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testApprovePayoutLinkInvalidPayoutLinkId()
    {
        $plMock = $this->getMockedServiceMakeRequestErrorResponse('payout_link_id: Public id format is incorrect : poutl_12345.');

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testApprovePayoutLinkNoWorkflowForPayoutLink()
    {
        $plMock = $this->getMockedServiceMakeRequestErrorResponse('no-workflow-for-payout-link');

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testWorkflowSummaryInternalServerError()
    {
        $plMock = $this->getMockedServiceMakeRequestErrorResponse('The server encountered an unexpected condition which prevented it from fulfilling the request.');

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testWorkflowSummaryZeroPendingPLs()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testWorkflowSummaryWithPendingPLsLiveMode()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'count'        => 2,
            'total_amount' => 1000,
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testWorkflowSummaryTestMode()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'count'        => 0,
            'total_amount' => 0,
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testApproveOtpSuccess()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'token' => 'test.token',
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testRejectPayoutLinkSuccess()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testBulkApproveSuccess()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testBulkApproveOtpSuccess()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testBulkRejectSuccess()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testBulkRejectTestMode()
    {
        $plMock = $this->getMockedPlForTestMode();

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testBulkApproveOtpTestMode()
    {
        $plMock = $this->getMockedPlForTestMode();

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testBulkApproveTestMode()
    {
        $plMock = $this->getMockedPlForTestMode();

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testApprovePayoutLinkTestMode()
    {
        $plMock = $this->getMockedPlForTestMode();

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testRejectPayoutLinkTestMode()
    {
        $plMock = $this->getMockedPlForTestMode();

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testApproveOtpTestMode()
    {
        $plMock = $this->getMockedPlForTestMode();

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testGetSettingsTestMode()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'merchant_id' => '10000000000000',
            'mode' => [
                'IMPS' => '0',
            ]
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->adminAuth('test');

        $this->startTest();
    }

    public function testUpdateSettingsTestMode()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'merchant_id' => '10000000000000',
            'mode' => [
                'IMPS' => '1',
                'UPI' => '1',
            ]
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->adminAuth('test');

        $this->startTest();
    }

    public function testCancelTestModeWithProxyAuth()
    {
        $testData = self::TEST_PAYOUT_LINK_PAYLOAD;

        $testData['send_sms'] = '1';

        $testData['send_email'] = '0';

        $testData['status'] = 'cancelled';

        $testData['cancelled_at'] = 1575367499;

        $plMock = $this->getMockedServiceMakeRequestSuccessResponse($testData);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testFetchPayoutLinkByIdTestMode()
    {
        $testData = self::TEST_PAYOUT_LINK_PAYLOAD;

        $testData['send_sms'] = '0';

        $testData['send_email'] = '0';

        $testData['status'] = 'issued';

        $plMock = $this->getMockedServiceMakeRequestSuccessResponse($testData);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testBulkResendNotificationTestMode()
    {
        $plMock = $this->getMockedPlForTestMode();

        $this->app->instance('payout-links', $plMock);

        $this->ba->adminAuth('test');

        $this->startTest();
    }

    public function testBulkResendNotificationLiveMode()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            "success_payout_link_ids" => "poutlk_4eWc1vLJKgR2hE",
            "failed_payout_link_ids" => "poutlk_4KGz1vLJNkQ79k"
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    public function testCancelTestModeWithPrivateAuth()
    {
        $testData = self::TEST_PAYOUT_LINK_PAYLOAD;

        $testData['send_sms'] = '1';

        $testData['send_email'] = '0';

        $testData['status'] = 'cancelled';

        $testData['cancelled_at'] = 1575367499;

        $plMock = $this->getMockedServiceMakeRequestSuccessResponse($testData);

        $this->app->instance('payout-links', $plMock);

        $this->ba->privateAuth('rzp_test_TheTestAuthKey');

        $this->startTest();
    }

    public function testGenerateAndSendCustomerOtpTestMode()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'merchant_id' => '10000000000000'
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->directAuth();

        $this->fixtures->on('test')->create('merchant',
            [
                'id' => '12345678954321'
            ]);

        $this->startTest();
    }

    public function testGenerateAndSendCustomerOtpLiveModeWithOutModeHeader()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'merchant_id' => '10000000000000',
            'success' => 'ok',
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->directAuth();

        $this->fixtures->on('live')->create('merchant',
            [
                'id' => '12345678954321'
            ]);

        $this->startTest();
    }

    public function testGenerateAndSendCustomerOtpLiveModeWithModeHeader()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'merchant_id' => '10000000000000',
            'success' => 'ok',
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->directAuth();

        $this->fixtures->on('live')->create('merchant',
            [
                'id' => '12345678954321'
            ]);

        $this->startTest();
    }

    public function testGetHostedPageDataWithIssuedLinkForAppAuthInTestMode()
    {
        $mid = '10000000000000';

        $ba = $this->prepareBankingAccountData($mid, 'test');

        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'payout_link_response' => [
                'id' => '12345',
                'merchant_id' => $mid,
                'account_number' => $ba['account_number'],
                'status' => 'issued',
                'contact' => [
                    'name' => 'test',
                    'email' => 'test@gmail.com',
                ],
                'amount' => 100,
                'currency' => 'INR',
            ],
            'settings' => [
                'merchant_id' => $mid,
                'mode' => [
                    'support_email' => 'support@gmail.com'
                ]
            ],
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->payoutLinksCustomerPageAuth('test');

        $this->startTest();
    }

    public function testGetStatusPayoutLinkTestMode()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'status' => 'issued',
            'send_sms' => '0',
            'send_email' => '0',
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->publicAuth('rzp_test_TheTestAuthKey');

        $this->startTest();

    }

    public function testGetStatusPayoutLinkLiveMode()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'status' => 'issued',
            'send_sms' => '0',
            'send_email' => '0',
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->publicAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();

    }

    public function testExpireCallbackForCancellingReminderTestMode()
    {
        $plMock = $this->getMockedServiceMakeRequestErrorResponse('BAD_REQUEST_REMINDER_NOT_APPLICABLE');

        $this->app->instance('payout-links', $plMock);

        $this->ba->reminderAppAuth();

        $this->startTest();
    }

    public function testExpireCallbackForContinueReminderTestMode()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse($this->mockedContinueReminderResponse());

        $this->app->instance('payout-links', $plMock);

        $this->ba->reminderAppAuth();

        $this->startTest();
    }

    public function testFetchIntegrationDetailsTestMode()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest();
    }

    public function testUpdateAttachmentsForPayoutLinkSuccessProxyAuth()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testUpdateAttachmentsForPayoutLinkFailPrivateAuth()
    {
        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testUploadAttachmentFailPrivateAuth()
    {
        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testGetSignedUrlSuccessProxyAuth()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'signed_url' => 'https://ufh-deafult/ndckd',
            'mime' => 'application/pdf'
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testGetSignedUrlFailPrivateAuth()
    {
        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testGenerateAndSendCustomerOtpSuccessForCAActivatedMerchant()
    {
        $plMock = $this->getMockedServiceMakeRequestSuccessResponse([
            'merchant_id' => '12345678912345',
            'success' => 'ok'
        ]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->directAuth();

        $this->fixtures->on('live')->create('merchant',
            [
                'id' => '12345678912345',
                'activated' => 0,
            ]);

        $this->fixtures->on('live')->create('banking_account',
            [
                'channel'      => 'rbl',
                'account_type' => 'current',
                'status'       => 'activated',
                'merchant_id'  => '12345678912345',
            ]);

        $this->startTest();
    }

    protected function setMockRazorxTreatment(array $razorxTreatment, string $defaultBehaviour = 'off')
    {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode) use ($razorxTreatment, $defaultBehaviour)
                              {
                                  if (array_key_exists($feature, $razorxTreatment) === true)
                                  {
                                      return $razorxTreatment[$feature];
                                  }

                                  return strtolower($defaultBehaviour);
                              }));
    }

    protected function mockRavenVerifyOtp($expectedContext, $receiver = null, $source = 'api')
    {
        $ravenMock = Mockery::mock(\RZP\Services\Raven::class, [$this->app])->makePartial();

        $ravenMock->shouldReceive('verifyOtp')
                  ->andReturnUsing(function(array $request) use ($expectedContext, $receiver, $source) {
                      try
                      {
                          self::assertEquals($request['receiver'], $receiver);
                          self::assertEquals($request['context'], $expectedContext);
                          self::assertEquals($request['source'], $source);
                      }
                      catch(\Exception $e)
                      {
                          throw new BadRequestException(ErrorCode::BAD_REQUEST_INCORRECT_OTP);
                      }

                      return [
                          'success' => true
                      ];
                  });

        $this->app->instance('raven', $ravenMock);
    }

    protected function mockRavenGenerateAndVerifyOtp()
    {
        $ravenMock = Mockery::mock(\RZP\Services\Raven::class, [$this->app])->makePartial();

        $ravenMock->shouldReceive('generateOtp')->andReturn(['otp' => '0007']);

        $ravenMock->shouldReceive('verifyOtp')->andReturn(['success' => true]);

        $this->app->instance('raven', $ravenMock);
    }

    protected function mockRavenSendRequest()
    {
        app('config')->get('applications.raven')['mock'] = false;

        $this->expectException(BadRequestException::class);

        $ravenMock = Mockery::mock(\RZP\Services\Raven::class, [$this->app])->makePartial();

        $ravenMock->shouldReceive('sendRequest')
            ->andReturnUsing(function() {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_RESPONSE_OTP_GENERATE_RAVEN);
            });

        $this->app->instance('raven', $ravenMock);
    }

    protected function createMetricsMock(array $methods = ['count', 'gauge', 'histogram', 'summary'])
    {
        $mock = $this->getMockBuilder(MetricManager::class)
            ->setMethods($methods)
            ->getMock();

        $this->app['trace']->setMetricsManager($mock);

        return $mock;
    }

    protected function validateTraceMock()
    {
        $traceMock = $this->createMetricsMock();
        $traceMock->expects($this->once())
            ->method('count')
            ->will($this->returnCallback(function(string $metric, array $dimensions) {
                if ($metric === Metric::RAVEN_REQUEST_FAILED)
                {
                    if (isset($dimensions[Metric::LABEL_ROUTE]) and isset($dimensions[Metric::LABEL_ACTION]) and isset($dimensions[Metric::LABEL_MESSAGE]))
                    {
                        return true;
                    }
                    // to make the test fail
                    throw new \Exception("dimensions not set correctly in metric");
                }
                return true;
            }));
    }

    public function testMetricSentInGenerateOtpFailedForCreatePayoutLinkWithSecureOtpContext()
    {
        // mocking payout links service
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $this->app->instance('payout-links', $plMock);

        // mocking raven external request
        $this->mockRavenSendRequest();

        // generate-otp flow
        $this->ba->proxyAuthLive();

        $this->sendRequest($this->testData['testGenerateOtpForCreatePayoutLinkWithSecureOtpContext']['request']);

        $this->validateTraceMock();

        $plMock->shouldNotHaveReceived('create');

        app('config')->get('applications.raven')['mock'] = true;
    }

    public function testMetricSentInGenerateOtpFailedForCreatePayoutLinkWithSecureOtpContextNotSet()
    {
        // mocking payout links service
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $this->app->instance('payout-links', $plMock);

        // mocking raven
        $this->mockRavenSendRequest();

        // generate-otp flow
        $this->ba->proxyAuth();

        $this->sendRequest($this->testData['testGenerateOtpForCreatePayoutLinkWithSecureOtpContext']['request']);

        $this->validateTraceMock();

        $plMock->shouldNotHaveReceived('create');

        app('config')->get('applications.raven')['mock'] = true;
    }

    public function testMetricSentInVerifyOtpFailedForCreatePayoutLinkWithSecureOtpContextSet()
    {
        // mocking payout links service
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $this->app->instance('payout-links', $plMock);

        // mocking raven
        $this->mockRavenSendRequest();

        // verify otp flow
        $createPlTestData = $this->testData['testPayoutLinkCreationWithSecureOtpContext'];
        $createPlTestData['request']['content']['otp'] = '1234';

        $this->ba->proxyAuth();

        $this->sendRequest($createPlTestData['request']);

        $this->validateTraceMock();

        $plMock->shouldNotHaveReceived('create');

        app('config')->get('applications.raven')['mock'] = true;
    }

    public function testMetricSentInVerifyOtpFailedForCreatePayoutLinkWithSecureOtpContextNotSet()
    {
        // mocking payout links service
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $this->app->instance('payout-links', $plMock);

        // mocking raven
        $this->mockRavenSendRequest();

        // verify otp flow
        $createPlTestData = $this->testData['testPayoutLinkCreationWithSecureOtpContext'];
        $createPlTestData['request']['content']['otp'] = '1234';

        $this->ba->proxyAuth();

        $this->sendRequest($createPlTestData['request']);

        $this->validateTraceMock();

        $plMock->shouldNotHaveReceived('create');

        app('config')->get('applications.raven')['mock'] = true;
    }

    public function testGenerateAndVerifyOtpForCreatePayoutLinkWithSecureOtpContext()
    {
        // mocking payout links service
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('create')->andReturn(['id' => 'poutlk_ABCDE12345']);

        $this->app->instance('payout-links', $plMock);

        // mocking raven
        $this->mockRavenGenerateAndVerifyOtp();

        // generate-otp flow
        $this->ba->proxyAuth();

        $generateOtpResponse = $this->sendRequest($this->testData['testGenerateOtpForCreatePayoutLinkWithSecureOtpContext']['request']);

        $this->assertEquals(200, $generateOtpResponse->getStatusCode());

        $generateOtpResponseContent = json_decode($generateOtpResponse->getContent(), true);

        $this->assertArrayHasKey('token', $generateOtpResponseContent);

        // verify otp flow
        $createPlTestData = $this->testData['testPayoutLinkCreationWithSecureOtpContext'];
        $createPlTestData['request']['content']['token'] = $generateOtpResponseContent['token'];

        $this->ba->proxyAuth();

        $createPlResponse = $this->sendRequest($createPlTestData['request']);

        $this->assertEquals(200, $createPlResponse->getStatusCode());

        $createPlResponseContent = json_decode($createPlResponse->getContent(), true);

        $this->assertEquals(['id' => 'poutlk_ABCDE12345'], $createPlResponseContent);

        $plMock->shouldHaveReceived('create');
    }

    public function testPayoutLinkCreationWithSecureOtpContext()
    {
        // mocking payout links service
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('create')->andReturn(['id' => 'poutlk_ABCDE12345']);

        $this->app->instance('payout-links', $plMock);

        // mocking raven
        $expectedContext = sprintf('%s:%s:%s:%s:%s:%s:%s',
                                   '10000000000000',
                                   'MerchantUser01',
                                   'create_payout_link',
                                   '4564563559247998',
                                   'BUIj3m2Nx2VvVj',
                                   100,
                                   "9090909090");

        $expectedContext = hash('sha3-512', $expectedContext);

        $user = $this->getDbEntity('user', ['id' => 'MerchantUser01']);

        $this->mockRavenVerifyOtp($expectedContext, $user->getEmail());

        $this->ba->proxyAuth();

        $this->startTest();

        $plMock->shouldHaveReceived('create');
    }

    public function testFetchPendingPayoutLinksAsOwnerSSWF()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => '10000000000000',
            'user_id'     => $user->getId(),
            'product'     => 'banking',
            'role'        => 'owner',
        ]);

        $this->ba->proxyAuth('rzp_live_10000000000000', $user->getId());

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPayoutLinksSummaryForMerchant')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->startTest();

        $plMock->shouldHaveReceived('fetchPayoutLinksSummaryForMerchant');
    }

    public function testFetchPendingPayoutLinksAsOwnerSSWFWithPLResponse()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => '10000000000000',
            'user_id'     => $user->getId(),
            'product'     => 'banking',
            'role'        => 'owner',
        ]);

        $this->ba->proxyAuth('rzp_live_10000000000000', $user->getId());

        $samplePlResponse = [
            "id" => "sample_pl_id12",
            "amount" =>  1000,
        ];

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPayoutLinksSummaryForMerchant')->andReturn($samplePlResponse);

        $this->app->instance('payout-links', $plMock);

        $response = $this->startTest();

        $this->assertNotEmpty($response);

        $this->assertCount(2, $response);

        $plMock->shouldHaveReceived('fetchPayoutLinksSummaryForMerchant');
    }

    public function testFetchPendingPayoutLinksAsOwnerSSWFValidationError()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => '10000000000000',
            'user_id'     => $user->getId(),
            'product'     => 'banking',
            'role'        => 'owner',
        ]);

        $this->ba->proxyAuth('rzp_live_10000000000000', $user->getId());

        $this->startTest();
    }

    public function testBulkRejectPayoutLinksAsOwner()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => '10000000000000',
            'user_id'     => $user->getId(),
            'product'     => 'banking',
            'role'        => 'owner',
        ]);

        $this->fixtures->merchant->addFeatures([Constants::PAYOUT_WORKFLOWS]);

        $this->ba->proxyAuth('rzp_live_10000000000000', $user->getId());

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('adminActions')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->startTest();

        $plMock->shouldHaveReceived('adminActions');
    }

    public function testBulkRejectPayoutLinksAsOwnerError()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => '10000000000000',
            'user_id'     => $user->getId(),
            'product'     => 'banking',
            'role'        => 'owner',
        ]);

        $this->fixtures->merchant->addFeatures([Constants::PAYOUT_WORKFLOWS]);

        $this->ba->proxyAuth('rzp_live_10000000000000', $user->getId());

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('adminActions')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->startTest();

        $plMock->shouldNotHaveReceived('adminActions');
    }

    /*
     * Verify OTP for Payout Link create
     * 1. OTP not present in payload
     * 2. Invalid OTP present in payload
     * 4. Token not present in payout
     * 5. validateInput() should be called
     * 6a. Valid action 'create_payout_link' & 6b.Raven verify-otp success
     *
    */

    public function testPayoutLinkVerifyOtpWithoutOtp()
    {
        // mocking payout links service
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth();

        $this->startTest();

        $plMock->shouldNotHaveReceived('create');
    }

    public function testPayoutLinkVerifyOtpWithInvalidOtp()
    {
        // mocking payout links service
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth();

        $this->startTest();

        $plMock->shouldNotHaveReceived('create');
    }

    public function testPayoutLinkVerifyOtpWithoutToken()
    {
        // mocking payout links service
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth();

        $this->startTest();

        $plMock->shouldNotHaveReceived('create');
    }

    public function testPayoutLinkVerifyOtpWithUserValidator()
    {
        // mocking payout links service
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('create')->andReturn(['id' => 'poutlk_ABCDE12345']);

        $this->app->instance('payout-links', $plMock);

        // mocking user validator
        $userValidator = Mockery::mock('RZP\Models\User\Validator');

        $this->app->instance('user', $userValidator);

        $this->ba->proxyAuth();

        $this->startTest();

        $plMock->shouldHaveReceived('create');

        $userValidator->shouldReceive('validateInput');
    }

    public function testPayoutLinkVerifyOtpWithValidAction()
    {
        // mocking payout links service
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('create')->andReturn(['id' => 'poutlk_ABCDE12345']);

        $this->app->instance('payout-links', $plMock);

        $expectedPayload = [
            'context' => 'cd5398fc703ddffbee86526824243063cfb6b3cdcc4fba70cefe5b6c0f2c5df530ef2cc079c85144ae64f5dc89308e0d93fff6aa01e9d6fd0b74640388b42452',
            'receiver' => 'merchantuser01@razorpay.com',
            'source' => 'api',
            'otp' => '0007',
        ];

        // mocking raven
        $raven = Mockery::mock('RZP\Services\Raven');

        $raven->shouldReceive('verifyOtp')
            ->with($expectedPayload, true)
            ->andReturn(['success' => true]);

        $this->app->instance('raven', $raven);

        $this->ba->proxyAuth();

        $this->startTest();

        $plMock->shouldHaveReceived('create');
    }

    /*
     * Generate and Send OTP for Payout Link create
     * 1. Invalid action - 'create_payout_link'
     * 2. Raven generate-otp success
     * 3. send-sms Payload contains amount, account_number, purpose
    */

    public function testPayoutLinkGenerateOtpWithValidActionAndWithToken()
    {
        $expectedPayload = [
            'context' => 'cd5398fc703ddffbee86526824243063cfb6b3cdcc4fba70cefe5b6c0f2c5df530ef2cc079c85144ae64f5dc89308e0d93fff6aa01e9d6fd0b74640388b42452',
            'receiver' => 'merchantuser01@razorpay.com',
            'source' => 'api'
        ];

        // mocking raven
        $raven = Mockery::mock('RZP\Services\Raven');

        $raven->shouldReceive('generateOtp')
            ->with($expectedPayload, true)
            ->andReturn([
                'otp' => '0007',
                'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            ]);

        $this->app->instance('raven', $raven);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPayoutLinkGenerateOtpWithValidActionAndDynamicToken()
    {
        // mocking payout links service
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('create')->andReturn(['id' => 'poutlk_ABCDE12345']);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPayoutLinkGenerateOtpWithValidActionRavenSuccess()
    {
        $expectedPayload = [
            'context' => 'cd5398fc703ddffbee86526824243063cfb6b3cdcc4fba70cefe5b6c0f2c5df530ef2cc079c85144ae64f5dc89308e0d93fff6aa01e9d6fd0b74640388b42452',
            'receiver' => 'merchantuser01@razorpay.com',
            'source' => 'api',
        ];

        // mocking raven
        $raven = Mockery::mock('RZP\Services\Raven');

        $raven->shouldReceive('generateOtp')
            ->with($expectedPayload, true)
            ->andReturn(['success' => true]);

        $this->app->instance('raven', $raven);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPayoutLinkSendOtpValidatePayload()
    {
        $expiryTimestamp = Carbon::now()->addMinutes(30)->timestamp;

        $expectedGenerateOtpPayload = [
            'context' => 'cd5398fc703ddffbee86526824243063cfb6b3cdcc4fba70cefe5b6c0f2c5df530ef2cc079c85144ae64f5dc89308e0d93fff6aa01e9d6fd0b74640388b42452',
            'receiver' => 'merchantuser01@razorpay.com',
            'source' => 'api'
        ];

        $expectedSendOtpPayload = [
            'context' => 'cd5398fc703ddffbee86526824243063cfb6b3cdcc4fba70cefe5b6c0f2c5df530ef2cc079c85144ae64f5dc89308e0d93fff6aa01e9d6fd0b74640388b42452',
            'receiver' => null,
            'source' => 'api.user.create_payout_link',
            'template' => 'sms.user.create_payout_link_v2',
            'params' => [
                'otp' => '0007',
                'amount' => '1.00',
                'validity' => Carbon::createFromTimestamp($expiryTimestamp, Timezone::IST)->format('H:i:s'),
                'account_number' => 'XXXXXXXXXXXX7998',
                'purpose' => 'refund'
            ]
        ];

        // mocking raven
        $raven = Mockery::mock('RZP\Services\Raven');

        $raven->shouldReceive('generateOtp')
            ->with($expectedGenerateOtpPayload, true)
            ->andReturn([
                'otp' => '0007',
                'expires_at' => $expiryTimestamp,
            ]);

        $raven->shouldReceive('sendOtp')
            ->with($expectedSendOtpPayload, true)
            ->andReturn([
                'otp' => '0007',
                'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            ]);

        $this->app->instance('raven', $raven);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    function testPayoutStatusPushForPayoutLinkAsSource()
    {
        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('pushPayoutStatus');

        $this->app->instance('payout-links', $plMock);

        $gaiMock = Mockery::mock('RZP\Services\GenericAccountingIntegration\Service');

        $gaiMock->shouldReceive('pushPayoutStatusUpdate');

        $this->app->instance('accounting-integration-service', $gaiMock);

        $payout = $this->fixtures->create('payout', [
            'status' => 'processed'
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout->getId(),
            'source_id' => 'poutlk_1',
            'source_type' => 'payout_links',
            'priority' => 1
        ]);

        SourceUpdater::update($payout);

        $plMock->shouldHaveReceived('pushPayoutStatus');

    }
}

