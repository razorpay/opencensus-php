<?php

namespace RZP\Tests\Functional\PayoutLink;

use App;
use Mail;
use Redis;
use Mockery;
use Closure;
use Exception;
use RZP\Models\Payout;
use RZP\Models\Settings;

use RZP\Error\ErrorCode;
use RZP\Models\Currency\Currency;
use RZP\Models\PayoutLink\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\PayoutLink\CustomerOtp;
use RZP\Exception\BadRequestException;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Models\PayoutLink\Entity as PayoutLink;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class PayoutLinkTest extends TestCase
{

    use TestsBusinessBanking;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use EntityActionTrait;
    use MocksDnsTrait;
    use WebhookTrait;

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

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutLinkTestData.php';

        parent::setUp();

        $this->createContact();

        $this->createFundAccount();

        $this->setUpMerchantForBusinessBanking(true, 10000000);

        $this->bankAccount->setIfsc('YESBB000000');

        $this->bankAccount->saveOrFail();

        $this->config = App::getFacadeRoot()['config'];
    }

    /**
     * This is to Test the BUILD on Payout Link Entity
     */
    public function testPayoutLinkCreation()
    {
        $input = [
            PayoutLink::AMOUNT       => 1000,
            PayoutLink::CURRENCY     => Currency::INR,
            PayoutLink::DESCRIPTION  => 'TEST DESCRIPTION',
            PayoutLink::PURPOSE      => 'refund',
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
        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testPostRequestForCreatingPayoutLinkWithContactId()
    {
        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testGetPayoutLinkById()
    {
        $payoutLink = $this->fixtures->create(self::FIXTURE_ENTITY,
                                              self::TEST_PAYOUT_LINK_PAYLOAD);

        $this->ba->privateAuth();

        $url = $this->testData['testGetPayoutLinkById']['request']['url'];

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId());

        $this->startTest();

    }

    public function testListPayoutLink()
    {
        $this->ba->privateAuth();

        $i = 0;

        $payoutLinkCount = 5;

        // creating a new variable so that the original one is not altered during the test
        $testPayload = self::TEST_PAYOUT_LINK_PAYLOAD;

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
        $payoutLink = $this->fixtures->create(self::FIXTURE_ENTITY,
                                              self::TEST_PAYOUT_LINK_PAYLOAD);

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
        $payout = $this->fixtures->create('payout');

        $payoutLink = $this->fixtures->create(self::FIXTURE_ENTITY);

        $payout->payoutLink()->associate($payoutLink);

        $payout->saveOrFail();

        $payoutLink = $this->getDbEntity('payout_link', ['id' => $payoutLink->getId()]);

        $associatedPayout = $payoutLink->payouts[0];

        $this->assertEquals($associatedPayout->getId(), $payout->getId());
    }

    public function testShortUrlGenerationSuccessful()
    {
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
                             $this->app['config']['url.api.production'],
                             $payoutLinkId);

        // asserting that the short_url is same as the full target url, because elfin failed
        $this->assertEquals($response['short_url'], $expectedTargetUrl);

    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPayoutLinkFailedDueToContactCreationFailure()
    {
        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testContactAddFailsWhenEmailAndPhoneNumberBothMissing()
    {
        $this->ba->privateAuth();

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->startTest();
    }

    public function testPayoutLinkCreationFailsWhenContactIdIsMissingBothEmailAndPhone()
    {
        $contact = $this->fixtures->create('contact',
                                           [
                                               'name'    => 'Test Contact',
                                               'email'   => '',
                                               'contact' => ''
                                           ]);

        $testData = $this->testData['testPayoutLinkCreationFailsWhenContactIdIsMissingBothEmailAndPhone'];

        $testData['request']['content']['contact']['id'] = $contact->getId();

        $this->testData['testPayoutLinkCreationFailsWhenContactIdIsMissingBothEmailAndPhone'] = $testData;

        $this->addAccountNumberParameter(__FUNCTION__);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGenerateOtpForOnlyPhoneContact()
    {
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
                                                  'contact_email'        => $contact->getEmail()
                                              ]);

        $this->setUrl(__FUNCTION__ , $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();

        Mail::assertNotQueued(CustomerOtp::class);
    }

    public function testGenerateOtpForOnlyEmailContact()
    {
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
                                                  'contact_phone_number' => $contact->getContact(),
                                                  'contact_email'        => $contact->getEmail()
                                              ]);

        $this->setUrl(__FUNCTION__ , $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();

        Mail::assertQueued(CustomerOtp::class);
    }

    public function testVerifyOtpSuccessful()
    {
        $payoutLink = $this->fixtures->create('payout_link');

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::VERIFY_CUSTOMER_OTP);

        $response = $this->startTest();

        $token = $response['token'];

        $this->assertRegExp('/' . $payoutLink->getPublicId() . '.*/', $token);
    }

    public function testVerifyOtpFailedByInvalidOtp()
    {
        $payoutLink = $this->fixtures->create('payout_link');

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::VERIFY_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testExceptionWhenOtpGeneratedWithoutEmailAndPhoneNumber()
    {
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
                                                  'contact_email'        => $contact->getEmail()
                                              ]);

        $this->setUrl(__FUNCTION__ , $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExceptionWhenOnlyEmailIsPresentAndEmailSendingFails()
    {
        $mail = Mockery::mock('overload:Mail');

        $mail->shouldReceive('queue')
             ->andThrow(new Exception('I failed for the sake of testing'));

        $contact = $this->fixtures->create('contact',
                                           [
                                               'id'      => '1000011contact',
                                               'email'   => 'test@razorpay.com',
                                               'contact' => '',
                                               'name'    => 'test user'
                                           ]);
        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id'           => $contact->getId(),
                                                  'contact_name'         => $contact->getName(),
                                                  'contact_phone_number' => $contact->getContact(),
                                                  'contact_email'        => $contact->getEmail()
                                              ]);

        $this->setUrl(__FUNCTION__ , $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testExceptionWhenOnlyPhoneIsPresentAndSmsFails()
    {
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
                                                  'contact_email'        => $contact->getEmail()
                                              ]);

        $this->setUrl(__FUNCTION__ , $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testOtpGenerationWithContext()
    {
        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $this->contact->getId()
                                              ]);

        $this->setUrl(__FUNCTION__ , $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testOtpVerificationWithContext()
    {
        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $this->contact->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::VERIFY_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testWhenRavenFailsWhileOtpGenerationExceptionIsThrown()
    {
        $raven = Mockery::mock('RZP\Services\Raven');

        $raven->shouldReceive('sendSms')
              ->andReturn('sms_1234');

        $raven->shouldReceive('generateOtp')
              ->andreturn([]);

        $this->app->instance('raven', $raven);

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $this->contact->getId()
                                              ]);

        $this->setUrl(__FUNCTION__ , $payoutLink->getPublicId(), self::GENERATE_CUSTOMER_OTP);

        $this->startTest();
    }

    public function testPayoutLinkCancelApiSuccess()
    {
        $this->createWebhook(['events' => ['payout_link.cancelled' => '1']],
                             ['HTTP_X-Request-Origin' => $this->config['applications.banking_service_url']]);

        $this->setInfernoExpectations(['PayoutLinkCancelledWebHook']);

        $payoutLink = $this->fixtures->create('payout_link');

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::CANCEL);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCancellingPayoutLinkFromProcessingStatusShouldThrowException()
    {
        $payoutLink = $this->fixtures->create('payout_link');

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::CANCEL);

        $payoutLink->setStatus(Status::PROCESSING);

        $payoutLink->saveOrFail();
    }

    public function testSettingPayoutLinkToInvalidStatusShouldThrowException()
    {
        $payoutLink = $this->fixtures->create('payout_link');

        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_PAYOUT_LINK_INVALID_STATUS);

        $this->expectException(BadRequestException::class);

        $payoutLink->setStatus('An Invalid State');
    }

    public function testCancelIdempotencyByCallingTheCancelApiTwice()
    {
        $payoutLink = $this->fixtures->create('payout_link');

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::CANCEL);

        $this->ba->privateAuth();

        $this->startTest();

        $this->startTest();
    }

    public function testGetFundAccountWithValidTokenReturnsFundAccountArray()
    {
        $this->mockRedisSuccess();

        // call fund-account, assuming OTP verification will pass as redis is mocked to return non-null value,
        // which signifies OTP is present
        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $this->contact->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::FUND_ACCOUNTS);

        $this->startTest();
    }

    public function testGetFundAccountWithInvalidTokenRaisesException()
    {
        $this->mockRedisFail();

        // call fund-account, assuming OTP verification will pass as redis is mocked to return Null,
        // which means token is not found
        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $this->contact->getId()
                                              ]);
        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::FUND_ACCOUNTS);

        $this->startTest();
    }

    public function testInitiateApiBankAccountRequiredWhenTypeIsBankAccount()
    {
        $this->mockRedisSuccess();

        $payoutLink = $this->fixtures->create('payout_link');

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();
    }

    public function testInitiateApiVpaRequiredWhenTypeIsVpa()
    {
        $this->mockRedisSuccess();

        $payoutLink = $this->fixtures->create('payout_link');

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();
    }

    public function testInitiateApiWithInvalidAccountTypeRaisesException()
    {
        $payoutLink = $this->fixtures->create('payout_link');

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();
    }

    public function testInitiateApiWhenTokenIsAbsent()
    {
        $payoutLink = $this->fixtures->create('payout_link');

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();
    }

    public function testInitiateApiWithInvalidTokenRaiseException()
    {
        $payoutLink = $this->fixtures->create('payout_link');

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->mockRedisFail();

        $this->startTest();
    }

    public function testInitiateApiWithInvalidFundAccountIdThrowException()
    {
        $this->mockRedisSuccess();

        $payoutLink = $this->fixtures->create('payout_link');

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->startTest();
    }

    public function testInitiateApiSuccessWhenValidVpaPassed()
    {
        $this->mockRedisSuccess();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $fd = $this->fixtures->create('fund_account:vpa',
                                      [
                                          'id'          => '100000000003fa',
                                          'source_type' => 'contact',
                                          'source_id'   => $this->contact->getId(),
                                          'merchant_id' => $this->contact->merchant->getId()
                                      ]);

        $this->startTest();
    }

    public function testInitiateApiSuccessWhenValidBankAccountPassed()
    {
        $this->mockRedisSuccess();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $fd = $this->fixtures->create('fund_account:bank_account',
                                      [
                                          'id'          => '100000000003fa',
                                          'source_type' => 'contact',
                                          'source_id'   => $this->contact->getId(),
                                          'merchant_id' => $this->contact->merchant->getId()
                                      ]);

        $this->startTest();

        // assert that a payout entity was create
        $this->assertEquals($payoutLink->payouts->count(), 1);

        //assert there is only one payout, and that belongs to the above payout-link
        $payout = $this->getDbEntities('payout')[0];

        $this->assertEquals($payout->payoutLink->getId(), $payoutLink->getId());

        $payoutLink->refresh();

        // assert payout link is now in processing state
        $this->assertEquals($payoutLink->getStatus(), Status::PROCESSING);

    }

    public function testInitiateApiFailsWhenFundAccountIdPassedBelongsToAnotherContact()
    {
        $this->mockRedisSuccess();

        $contact1 = $this->contact;

        $contact2 = $this->fixtures->create('contact',
                                           [
                                               'name'    => 'Test Contact 2',
                                               'email'   => 'test2@rzp.com',
                                               'contact' => '9876543210'
                                           ]);
        // create the payoutlink that is associated with the first contact
        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id'           => $contact1->getId(),
                                                  'contact_name'         => $contact1->getName(),
                                                  'contact_phone_number' => $contact1->getContact(),
                                                  'contact_email'        => $contact1->getEmail()
                                              ]);

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
        $this->mockRedisSuccess();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => '100000000003fa',
                                    'source_type' => 'contact',
                                    'source_id'   => $this->contact->getId(),
                                    'merchant_id' => $this->contact->merchant->getId()
                                ]);

        $this->createWebhook(['events' => ['payout_link.processing' => '1']],
                             ['HTTP_X-Request-Origin' => $this->config['applications.banking_service_url']]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->setInfernoExpectations(['PayoutLinkProcessingWebHook']);

        $this->ba->noAuth();

        $this->startTest();

        $payoutLink->refresh();

        $this->assertEquals(Status::PROCESSING, $payoutLink->getStatus());
    }

    public function testPayoutStatusReversedMakesLinkStatusAttempted()
    {
        $this->mockRedisSuccess();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => '100000000003fa',
                                    'source_type' => 'contact',
                                    'source_id'   => $this->contact->getId(),
                                    'merchant_id' => $this->contact->merchant->getId()
                                ]);

        $this->startTest();

        $payout = $payoutLink->payouts()->first();

        $this->createWebhook(['events' => ['payout_link.attempted' => '1']],
                             ['HTTP_X-Request-Origin' => $this->config['applications.banking_service_url']]);

        $this->setInfernoExpectations(['PayoutLinkAttemptedWebHook']);

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'        => 'failed',
            'failure_reason'    => 'This is a test failure',
        ]);

        $payoutLink->refresh();

        $this->assertEquals(Status::ATTEMPTED, $payoutLink->getStatus());

        $this->assertEquals($payout->getStatus() , Payout\Status::REVERSED);
    }

    public function testPayoutStatusProcessedMakesLinkStatusProcessed()
    {
        $this->mockRedisSuccess();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => '100000000003fa',
                                    'source_type' => 'contact',
                                    'source_id'   => $this->contact->getId(),
                                    'merchant_id' => $this->contact->merchant->getId()
                                ]);

        $this->startTest();

        $this->createWebhook(['events' => ['payout_link.processed' => '1']],
                             ['HTTP_X-Request-Origin' => $this->config['applications.banking_service_url']]);

        $this->setInfernoExpectations(['PayoutLinkProcessedWebHook']);

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
        $this->ba->adminAuth();

        $this->startTest();

        $merchant = $this->contact->merchant;

        $settingAccessor = Settings\Accessor::for($merchant, Settings\Module::PAYOUT_LINK);

        $this->assertEquals($settingAccessor->get(Payout\Mode::IMPS) , 1);

        $this->assertEquals($settingAccessor->get(Payout\Mode::UPI) , 1);
    }

    public function testPayoutLinkSettingsGetApiSuccess()
    {
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
        $this->mockRedisSuccess();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);
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
        $this->mockRedisSuccess();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId(),
                                                  'amount'     => '100000'
                                              ]);

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
        $this->mockRedisSuccess();

        $this->bankingBalance->balance = '300000000';

        $this->bankingBalance->save();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId(),
                                                  'amount'     => 30000000
                                              ]);

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
        $this->createWebhook(['events' => ['payout_link.issued' => '1']],
                             ['HTTP_X-Request-Origin' => $this->config['applications.banking_service_url']]);

        $this->setInfernoExpectations(['PayoutLinkIssuedWebHook']);

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

    protected function mockInfernoFire(Closure $closure)
    {
        $inferno = Mockery::mock(Webhook\Inferno::class, [])->makePartial();

        $inferno->shouldReceive('fire')
                ->once()
                ->with(
                    Mockery::type('RZP\Jobs\WebHook'),
                    Mockery::on($closure));

        $this->app->instance('webhook.inferno', $inferno);
    }

    public function testPayoutLinkThrowsExceptionWhenInitiateCalledWithInvalidState()
    {
        $this->mockRedisSuccess();

        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->setUrl(__FUNCTION__, $payoutLink->getPublicId(), self::INITIATE);

        $payoutLink->setStatus(Status::PROCESSING);

        $payoutLink->saveOrFail();

        $this->startTest();
    }

    protected function mockRedisSuccess()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get', 'del'])
                          ->getMock();

        Redis::shouldReceive('zadd')->andReturn($redisMock);

        Redis::shouldReceive('connection')->andReturn($redisMock);

        $redisMock->method('del')
                  ->will($this->returnValue(1));

        $redisMock->method('get')
                  ->will($this->returnValue('Token valid as a non-null value is being returned'));
    }

    protected function mockRedisFail()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get', 'del'])
                          ->getMock();

        Redis::shouldReceive('connection')->andReturn($redisMock);

        $redisMock->method('get')
                  ->will($this->returnValue(null));

        $redisMock->method('del')
                  ->will($this->returnValue(1));
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

}
