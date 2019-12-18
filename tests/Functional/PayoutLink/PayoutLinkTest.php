<?php

namespace RZP\Tests\Functional\PayoutLink;

use Mail;
use Redis;
use Mockery;
use Exception;
use RZP\Error\ErrorCode;
use RZP\Models\P2p\Entity;
use RZP\Models\Currency\Currency;
use RZP\Models\PayoutLink\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\PayoutLink\CustomerOtp;
use RZP\Exception\BadRequestException;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Models\PayoutLink\Entity as PayoutLink;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class PayoutLinkTest extends TestCase
{

    use TestsBusinessBanking;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    const TEST_PAYOUT_LINK_PAYLOAD = [
        'id'           => 'DnhDjMDHlQEjgM',
        'contact_id'   => '1000010contact',
        'amount'       => 1000,
        'merchant_id'  => '10000000000000',
        'user_id'      => null,
        'currency'     => 'INR',
        'description'  => 'This is a test payout',
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

    const FIXTURE_ENTITY = 'payout_link';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutLinkTestData.php';

        parent::setUp();

        $this->createContact();

        $this->createFundAccount();

        $this->setUpMerchantForBusinessBanking(true, 10000000);
    }

    /**
     * This is to Test the BUILD on Payout Link Entity
     */
    public function testPayoutLinkCreation()
    {
        $input = [
            PayoutLink::AMOUNT     => 1000,
            PayoutLink::CURRENCY   => Currency::INR,
            PayoutLink::DESCRIPTION   => 'TEST DESCRIPTION',
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

        $this->startTest();
    }

    public function testPostRequestForCreatingPayoutLinkWithContactId()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetPayoutLinkById()
    {
        $payoutLink = $this->fixtures->create(self::FIXTURE_ENTITY,
                                              self::TEST_PAYOUT_LINK_PAYLOAD);

        $this->ba->privateAuth();

        $url = $this->testData['testGetPayoutLinkById']['request']['url'];

        $urlWithId = $url . $payoutLink->getPublicId();

        $this->testData['testGetPayoutLinkById']['request']['url'] = $urlWithId;

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
            $id = Entity::generateUniqueId();

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

        $payoutLink = $this->getDbEntity('payout_link', ['id' => 'DnhDjMDHlQEjgM']);

        $associatedPayout = $payoutLink->payouts[0];

        $this->assertEquals($associatedPayout->getId(), $payout->getId());
    }

    public function testShortUrlGenerationSuccessful()
    {
        $this->ba->privateAuth();

        $testHardCodedShortUrl = 'www.this_is_when_elfin_works.com';

        $elfin = $this->createMock(ElfinService::class);

        $elfin->method('shorten')
              ->willReturn($testHardCodedShortUrl);

        $this->app->instance('elfin', $elfin);

        $response = $this->startTest();

        $this->assertEquals($response['short_url'], $testHardCodedShortUrl);
    }

    public function testShortUrlGenerationExceptionThrown()
    {
        $urlFormat = '%s/payout-links/%s/view';

        $this->ba->privateAuth();

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

        $this->startTest();
    }

    public function testContactAddFailsWhenEmailAndPhoneNumberBothMissing()
    {
        $this->ba->privateAuth();

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

        $this->fixtures->create('payout_link',
                                [
                                    'contact_id'           => $contact->getId(),
                                    'contact_name'         => $contact->getName(),
                                    'contact_phone_number' => $contact->getContact(),
                                    'contact_email'        => $contact->getEmail()
                                ]);

        $this->startTest();

        Mail::assertNotQueued(CustomerOtp::class);
    }

    public function testGenerateOtpForOnlyEmailContact()
    {
        Mail::fake();

        $contact = $this->fixtures->create('contact',
                                           [
                                               'id'      => '1000011contact',
                                               'email' => 'test@razorpay.com',
                                               'name'    => 'test user'
                                           ]);

        $this->fixtures->create('payout_link',
                                [
                                    'contact_id'           => $contact->getId(),
                                    'contact_name'         => $contact->getName(),
                                    'contact_phone_number' => $contact->getContact(),
                                    'contact_email'        => $contact->getEmail()
                                ]);

        $this->startTest();

        Mail::assertQueued(CustomerOtp::class);
    }

    public function testVerifyOtpSuccessful()
    {
        $this->fixtures->create('payout_link');

        $response = $this->startTest();

        $token = $response['token'];

        $this->assertRegExp('/poutlk_DnhDjMDHlQEjgM.*/', $token);
    }

    public function testVerifyOtpFailedByInvalidOtp()
    {
        $this->fixtures->create('payout_link');

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

        $this->fixtures->create('payout_link',
                                [
                                    'contact_id'           => $contact->getId(),
                                    'contact_name'         => $contact->getName(),
                                    'contact_phone_number' => $contact->getContact(),
                                    'contact_email'        => $contact->getEmail()
                                ]);

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
        $this->fixtures->create('payout_link',
                                [
                                    'contact_id'           => $contact->getId(),
                                    'contact_name'         => $contact->getName(),
                                    'contact_phone_number' => $contact->getContact(),
                                    'contact_email'        => $contact->getEmail()
                                ]);
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
        $this->fixtures->create('payout_link',
                                [
                                    'contact_id'           => $contact->getId(),
                                    'contact_name'         => $contact->getName(),
                                    'contact_phone_number' => $contact->getContact(),
                                    'contact_email'        => $contact->getEmail()
                                ]);

        $this->startTest();
    }

    public function testOtpGenerationWithContext()
    {
        $this->fixtures->create('payout_link',
                                [
                                    'contact_id' => $this->contact->getId()
                                ]);

        $this->startTest();
    }

    public function testOtpVerificationWithContext()
    {
        $this->fixtures->create('payout_link',
                                [
                                    'contact_id' => $this->contact->getId()
                                ]);

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

        $this->fixtures->create('payout_link',
                                [
                                    'contact_id' => $this->contact->getId()
                                ]);

        $this->startTest();
    }

    public function testPayoutLinkCancelApiSuccess()
    {
        $this->fixtures->create('payout_link');

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCancellingPayoutLinkFromProcessingStatusShouldThrowException()
    {
        $payoutLink = $this->fixtures->create('payout_link');

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
        $this->fixtures->create('payout_link');

        $this->ba->privateAuth();

        $this->startTest();

        $this->startTest();
    }

    public function testGetFundAccountWithValidTokenReturnsFundAccountArray()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get'])
                          ->getMock();

        Redis::shouldReceive('connection')->andReturn($redisMock);

        $redisMock->method('get')
                  ->will($this->returnValue('some value'));

        // call fund-account, assuming OTP verification will pass as redis is mocked to return non-null value,
        // which signifies OTP is present
        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $this->contact->getId()
                                              ]);

        $this->startTest();
    }

    public function testGetFundAccountWithInvalidTokenRaisesException()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get'])
                          ->getMock();

        Redis::shouldReceive('connection')->andReturn($redisMock);

        $redisMock->method('set')
                  ->will($this->returnValue(true));

        $redisMock->method('get')
                  ->will($this->returnValue(null));

        // call fund-account, assuming OTP verification will pass as redis is mocked to return Null,
        // which means token is not found
        $payoutLink = $this->fixtures->create('payout_link',
                                              [
                                                  'contact_id' => $this->contact->getId()
                                              ]);
        $this->startTest();
    }

    public function testInitiateApiBankAccountRequiredWhenTypeIsBankAccount()
    {
        $payoutLink = $this->fixtures->create('payout_link');

        $this->startTest();
    }

    public function testInitiateApiVpaRequiredWhenTypeIsVpa()
    {
        $payoutLink = $this->fixtures->create('payout_link');

        $this->startTest();
    }

    public function testInitiateApiWithInvalidAccountTypeRaisesException()
    {
        $payoutLink = $this->fixtures->create('payout_link');

        $this->startTest();
    }

    public function testInitiateApiWhenTokenIsAbsent()
    {
        $payoutLink = $this->fixtures->create('payout_link');

        $this->startTest();
    }

    public function testInitiateApiWithInvalidTokenRaiseException()
    {
        $this->fixtures->create('payout_link');

        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get'])
                          ->getMock();

        Redis::shouldReceive('connection')->andReturn($redisMock);

        $redisMock->method('get')
                  ->will($this->returnValue(null));

        $this->startTest();
    }

    public function testInitiateApiWithInvalidFundAccountIdThrowException()
    {
        $this->startTest();
    }

    public function testInitiateApiSuccessWhenValidVpaPassed()
    {
        $this->mockRedisSuccess();

        $payoutLink = $this->fixtures->create('payout_link');

        $fd = $this->fixtures->create('fund_account:vpa', [
            'id'            => '100000000003fa',
            'source_type'   => 'contact',
            'source_id'     => $this->contact->getId(),
            'merchant_id'  => $this->contact->merchant->getId()
        ]);

        $this->startTest();
    }

    public function testInitiateApiSuccessWhenValidBankAccountPassed()
    {
        $this->mockRedisSuccess();

        $payoutLink = $this->fixtures->create('payout_link');

        $fd = $this->fixtures->create('fund_account:bank_account', [
            'id'            => '100000000003fa',
            'source_type'   => 'contact',
            'source_id'     => $this->contact->getId(),
            'merchant_id'  => $this->contact->merchant->getId()
        ]);

        $this->startTest();
    }

    // test success when bank account fund-account is added
    // test success when vpa fund-account is added
    // test exception when the fund-account-id passed if is not that of the contact then exception is thrown
    //

    protected function mockRedisSuccess()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get'])
                          ->getMock();

        Redis::shouldReceive('connection')->andReturn($redisMock);

        $redisMock->method('get')
                  ->will($this->returnValue('Token valid as a non-null value is being returned'));
    }

    protected function mockRedisFail()
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get'])
                          ->getMock();

        Redis::shouldReceive('connection')->andReturn($redisMock);

        $redisMock->method('get')
                  ->will($this->returnValue(null));
    }


}
