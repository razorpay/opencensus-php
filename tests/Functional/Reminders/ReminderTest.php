<?php

namespace RZP\Tests\Functional\Reminders;

use Mail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Invoice\Reminder\Status;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Mail\Merchant\NegativeBalanceBreachReminder;

class ReminderTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/ReminderTestData.php';

        parent::setUp();

        $this->fixtures->create('user', ['id' => '1000000000user']);

        $this->ba->appAuth('rzp_test', 'api');

    }

    public function testSendReminderWithReminderCountAndChannels()
    {
        $invoice = $this->createPaymentLink();
        $this->startTest();
    }

    public function testSendReminderWithoutReminderCountWithChannels()
    {
        $invoice = $this->createPaymentLink();
        $this->startTest();
    }

    public function testSendReminderWithReminderCountWithoutChannels()
    {
        $invoice = $this->createPaymentLink();
        $this->startTest();
    }

    //Test Negative Balance Reminder Callbacks Processing
    public function testSendNegativeBalanceReminderWithReminderCountAndChannels()
    {
        Mail::fake();

        $this->createNegativeBalanceReminder();
        $this->startTest();

        Mail::assertQueued(NegativeBalanceBreachReminder::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals('100ghi000ghi00', $viewData['merchant_id']);

            $this->assertEquals('-10 INR' , $viewData['balance']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_breach_reminder', $mail->view);

            return true;
        });

        $nbReminder = $this->getDbEntityById('merchant_reminders', '100mno000mno00');
        $this->assertEquals(1, $nbReminder->getReminderCount());
    }

    public function testSendNegativeBalanceReminderWithoutChannels()
    {
        Mail::fake();

        $this->createNegativeBalanceReminder();
        $this->startTest();

        Mail::assertQueued(NegativeBalanceBreachReminder::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals('100ghi000ghi00', $viewData['merchant_id']);

            $this->assertEquals('-10 INR' , $viewData['balance']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_breach_reminder', $mail->view);

            return true;
        });

        $nbReminder = $this->getDbEntityById('merchant_reminders', '100mno000mno00');
        $this->assertEquals(1, $nbReminder->getReminderCount());
    }

    public function testSendNegativeBalanceReminderBalanceIsPositive()
    {
        Mail::fake();

        $this->createNegativeBalanceReminder();
        $this->fixtures->base->editEntity('balance', '100xy000xy00xy', ['balance' => 1000]);

        $this->startTest();

        Mail::assertNotQueued(NegativeBalanceBreachReminder::class);
        $nbReminder = $this->getDbEntityById('merchant_reminders', '100mno000mno00');
        $this->assertEquals('disabled', $nbReminder->getReminderStatus());
    }

    public function testSendNegativeBalanceReminderReminderCountMax()
    {
        Mail::fake();

        $this->createNegativeBalanceReminder();

        $this->startTest();

        Mail::assertNotQueued(NegativeBalanceBreachReminder::class);
    }

    public function testSendNegativeBalanceReminderDisabled()
    {
        Mail::fake();

        $this->createNegativeBalanceReminder();
        $this->fixtures->base->editEntity('merchant_reminders', '100mno000mno00', ['reminder_status' => 'disabled']);

        $this->startTest();

        Mail::assertNotQueued(NegativeBalanceBreachReminder::class);
    }

    public function testSendNegativeBalanceReminderCompleted()
    {
        Mail::fake();

        $this->createNegativeBalanceReminder();
        $this->fixtures->base->editEntity('merchant_reminders', '100mno000mno00', ['reminder_status' => 'completed']);

        $this->startTest();

        Mail::assertNotQueued(NegativeBalanceBreachReminder::class);
    }

    public function testSendNegativeBalanceReminderWithoutReminderCountWithChannels()
    {
        Mail::fake();

        $this->createNegativeBalanceReminder();
        $this->startTest();

        Mail::assertNotQueued(NegativeBalanceBreachReminder::class);

    }

    public function testSendReminderTerminalCreatedWebhook()
    {
        $terminal = $this->fixtures->create('terminal', [
            'enabled'             => true,
            'status'              => 'pending',
            'gateway'             => 'worldline',
            'merchant_id'         => '10000000000000',
            'gateway_merchant_id' => '90000000002',
            'mc_mpan'             => base64_encode('1234567890123456'),
            'visa_mpan'           => base64_encode('9876543210123456'),
            'rupay_mpan'          => base64_encode('1234123412341234'),
            'notes'               => 'some notes'
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/reminders/send/test/terminal/terminal_created_webhook/' . $terminal->getId();

        $this->startTest();

        $terminal = $this->getDbLastEntity('terminal');

        $this->assertEquals('activated', $terminal->getStatus());
    }

    protected function createNegativeBalanceReminder()
    {
        $this->fixtures->create('merchant', ['id' => '100ghi000ghi00', 'email' => 'test@razorpay.com']);

        $this->fixtures->create('balance',
            [
                'id'                            => '100xy000xy00xy',
                'merchant_id'                   => '100ghi000ghi00',
                'type'                          => 'primary',
                'balance'                       => -1000
            ]
        );

        $nbReminder = [
            'id'                  => '100mno000mno00',
            'merchant_id'        => '100ghi000ghi00',
            'reminder_status'    => Status::IN_PROGRESS,
            'reminder_namespace' => 'negative_balance'
        ];

        $this->fixtures->create('merchant_reminders', $nbReminder);
    }

    protected function createPaymentLink()
    {
        $attributes = [
            'order_id'        => $this->fixtures->create('order')->getId(),
            'type'            => 'link',
        ];

        $invoiceReminder = [
            'invoice_id'      => '1000000invoice',
            'reminder_status' => Status::IN_PROGRESS,
        ];

        $this->fixtures->create('invoice_reminder', $invoiceReminder);

        return $this->fixtures->create('invoice', $attributes);
    }
}
