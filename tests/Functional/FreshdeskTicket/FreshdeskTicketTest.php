<?php

namespace RZP\Tests\Functional\FreshdeskTicket;

use Mockery;
use Illuminate\Http\UploadedFile;
use Mail;
use RZP\Services\RazorXClient;
use RZP\Mail\Support\CustomerSupportTicketOtp;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class FreshdeskTicketTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $ticketService;

    protected $ravenMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FreshdeskTicketTestData.php';

        parent::setUp();
    }

    protected function mockRazorxTreatment(string $returnValue = 'On')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($returnValue);
    }

    public function testStoreReserveBalanceTicketDetails()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetReserveBalanceTicketStatusForNonExistingTicket()
    {
        $this->fixtures->on('test')->create('merchant', ['id' => '100cq000cq00cq']);

        $user = $this->fixtures->user->createUserForMerchant('100cq000cq00cq', [], 'owner', 'test');

        $this->ba->proxyAuth('rzp_test_100cq000cq00cq', $user->getId());

        $this->startTest();
    }

    public function testGetReserveBalanceTicketStatusForExistingTicket()
    {
        $this->fixtures->create('merchant_freshdesk_tickets');

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner', 'test');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->startTest();
    }

    protected function mockRaven()
    {
        $this->ravenMock = Mockery::mock('RZP\Services\Raven', [$this->app])->makePartial();

        $this->app->instance('raven', $this->ravenMock);
    }

    protected function expectRavenSendSmsRequest($ravenMock, $templateName, $receiver = '1234567890')
    {
        $ravenMock->shouldReceive('sendRequest')
              ->with('otp/generate', 'post', Mockery::type('array'))
                  ->andReturnUsing(function ()
                  {
                      return [
                          'otp' => '123456',
                          'attempts' => 0,
                          'expires_at' => '1235456789',
                      ];
                  });
        $ravenMock->shouldReceive('sendSms')
                  ->times(1)
                  ->with(
                      Mockery::on(function ($actualPayload) use ($templateName, $receiver)
                      {
                          if (($templateName !== $actualPayload['template']) or
                              ($receiver !== $actualPayload['receiver']))
                          {
                              return false;
                          }

                          return true;
                      }),  true)
                  ->andReturnUsing(function ()
                  {
                      return ['success' => true];
                  });
    }

    public function testOtpGenerateAndSendForMobile()
    {
        $this->ba->directAuth();

        $this->mockRaven();

        $this->expectRavenSendSmsRequest($this->ravenMock, 'sms.support.account_recovery_otp', '+919876543210');

        $response = $this->startTest();

        $this->assertEquals(True, $response['success']);
    }

    public function testOtpGenerateAndSendForMail()
    {
        Mail::fake();

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals(True, $response['success']);

        Mail::assertQueued(CustomerSupportTicketOtp::class, function ($mail) {
            $viewData = $mail->viewData;

            $this->assertArrayHasKey('otp', $viewData);

            $this->assertEquals('emails.support.customer_otp', $mail->view);

            return true;
        });
    }

    public function testPostTicketMissingField()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testPostTicketPaymentId()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $payment = $this->fixtures->create('payment:captured');

        $this->ba->publicAuth();

        $testData = &$this->testData['testPostTicketPaymentId'];

        $this->generateOtp($testData['request']['content']['email']);

        $id = 'pay_' . $payment->toArray()['id'];

        $testData['request']['content']['custom_fields']['cf_transaction_id'] = $id;

        $testData['request']['content']['custom_fields']['cf_razorpay_payment_id'] = $id;

        $this->startTest();
    }

    public function testPostTicketForAccountRecoveryForEmail()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->directAuth();

        $merchantDetail = $this->fixtures->create('merchant_detail', ['company_pan' => 'ABCCD1234A']);

        $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id'], ['email' => '123@gmail.com']);

        $testData = $this->testData[__FUNCTION__];

        $this->generateOtp($testData['request']['content']['email']);

        $this->startTest();
    }

    public function testPostTicketForAccountRecoveryForMobile()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->directAuth();

        $merchantDetail = $this->fixtures->create('merchant_detail', ['company_pan' => 'ABCCD1234A']);

        $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id'], ['contact_mobile' => '1234567890']);

        $testData = $this->testData[__FUNCTION__];

        $this->generateOtpForMobile($testData['request']['content']['phone']);

        $this->startTest();
    }

    public function testGetFreshdeskTicketsForCustomer()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->directAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->generateOtp($testData['request']['content']['email']);

        $this->startTest();
    }

    public function testGetFreshdeskTicketsFailureIncorrectOtp()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->directAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->generateOtp($testData['request']['content']['email']);

        $this->startTest();
    }

    public function testGetFreshdeskTicketsFailureTicketsNotFound()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->directAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->generateOtp($testData['request']['content']['email']);

        $this->startTest();
    }

    public function testPostTicketPaymentIdInvalidOtp()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $payment = $this->fixtures->create('payment:captured');

        $this->ba->publicAuth();

        $testData = &$this->testData['testPostTicketPaymentIdInvalidOtp'];

        $this->generateOtp($testData['request']['content']['email']);

        $id = 'pay_' . $payment->toArray()['id'];

        $testData['request']['content']['custom_fields']['cf_transaction_id'] = $id;

        $testData['request']['content']['custom_fields']['cf_razorpay_payment_id'] = $id;

        $this->startTest();
    }

    protected function generateOtpForMobile($phone)
    {
        $request = [
            'url'       => '/freshdesk/tickets/otp',
            'method'    => 'POST',
            'content'   => [
                'phone' => $phone,
            ]
        ];

        $this->ba->directAuth();

        $response = $this->sendRequest($request);

        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(True, $responseContent['success']);
    }

    protected function generateOtp($email)
    {
        $request = [
            'url'       => '/freshdesk/tickets/otp',
            'method'    => 'POST',
            'content'   => [
                'email' => $email,
            ]
        ];

        $this->ba->publicAuth();

        $response = $this->sendRequest($request);

        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals(True, $responseContent['success']);
    }

    public function testPostTicketInvalidId()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->publicAuth();

        $testData = &$this->testData['testPostTicketInvalidId'];

        $id = 'pay_' . 'ABcYZ';

        $testData['request']['content']['custom_fields']['cf_transaction_id'] = $id;

        $testData['request']['content']['custom_fields']['cf_razorpay_payment_id'] = $id;

        $this->startTest();
    }

    public function testPostTicketCustomerNoTransactionId()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testPostTicketPartnerSuccess()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->publicAuth();

        $testData = &$this->testData['testPostTicketPartnerSuccess'];

        $this->generateOtp($testData['request']['content']['email']);

        $this->startTest();
    }

    public function testRaiseGrievanceAgainstTicket()
    {
        $testcases = [
            [
                'description' => 'some description',
            ],
            [
                'description' => '      some description     ',
            ],
            ];

        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->directAuth();

        foreach ($testcases as $testcase)
        {
            $this->testData[__FUNCTION__]['request']['content']['description'] = $testcase['description'];

            $this->startTest();
        }
    }

    public function testRaiseGrievanceAgainstTicketFdIndiaInstance()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['description'] = 'some description';

        $this->startTest();
    }

    public function testRaiseGrievanceAgainstTicketUpdateFailure()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->directAuth();

        $this->startTest();
    }

    public function testRaiseGrievanceAgainstTicketInvalidEmail()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $this->ba->directAuth();

        $this->startTest();
    }

    public function testCreateTicketAttachments()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $payment = $this->fixtures->create('payment:captured');

        $this->ba->publicAuth();

        $testData = &$this->testData['testCreateTicketAttachments'];

        $this->generateOtp($testData['request']['content']['email']);

        $id = 'pay_' . $payment->toArray()['id'];

        $testData['request']['content']['custom_fields']['cf_transaction_id'] = $id;

        $testData['request']['content']['custom_fields']['cf_razorpay_payment_id'] = $id;

        $file1 = new UploadedFile(
            __DIR__ . '/../Storage/a.png',
            'a.png',
            'image/png',
            filesize(__DIR__ . '/../Storage/a.png'),
            null,
            true);

        $file2 = new UploadedFile(
            __DIR__ . '/../Storage/a.png',
            'a.png',
            'image/png',
            filesize(__DIR__ . '/../Storage/a.png'),
            null,
            true);

        $testData['request']['files']['attachments'] = [$file1];

        $this->mockRazorxTreatment('on');

        $this->startTest();
    }

    public function testCreateTicketAttachmentsInvalidExtensions()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $payment = $this->fixtures->create('payment:captured');

        $this->ba->publicAuth();

        $testData = &$this->testData[__FUNCTION__];

        $this->generateOtp($testData['request']['content']['email']);

        $id = 'pay_' . $payment->toArray()['id'];

        $testData['request']['content']['custom_fields']['cf_transaction_id'] = $id;

        $testData['request']['content']['custom_fields']['cf_razorpay_payment_id'] = $id;

        $file1 = new UploadedFile(
            __DIR__ . '/../Storage/a.png',
            'a.exe',
            'image/png',
            filesize(__DIR__ . '/../Storage/a.png'),
            null,
            true);

        $testData['request']['files']['attachments'] = [$file1];

        $this->ba->directAuth();

        $this->mockRazorxTreatment('on');

        $this->startTest();
    }

    public function testPostTicketWithExperimentFreshdeskCustomerTicketCreationServerPickOn()
    {
        $this->app['config']->set('applications.freshdesk.mock', true);

        $payment = $this->fixtures->create('payment:captured');

        $this->ba->directAuth();

        $testData = &$this->testData['testPostTicketWithExperimentFreshdeskCustomerTicketCreationServerPickOn'];

        $this->generateOtp($testData['request']['content']['email']);

        $this->mockRazorxTreatment('on');

        $id = 'pay_' . $payment->toArray()['id'];

        $testData['request']['content']['custom_fields']['cf_transaction_id'] = $id;

        $testData['request']['content']['custom_fields']['cf_razorpay_payment_id'] = $id;

        $response = $this->startTest();

        $this->assertEquals($testData['response']['content']['fd_instance'], $response['fd_instance']);

        $this->assertEquals($testData['response']['content']['subject'], $response['subject']);

        $this->assertEquals($testData['response']['content']['id'], $response['id']);

        $this->assertEquals($testData['response']['content']['description_text'], $response['description_text']);
    }
}
