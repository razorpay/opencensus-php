<?php

namespace RZP\Tests\Functional\Reminders;

use RZP\Models\Invoice\ReminderStatus;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;

class ReminderTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
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



    protected function createPaymentLink()
    {
        $attributes = [
            'order_id'        => $this->fixtures->create('order')->getId(),
            'type'            => 'link',
            'reminder_status' => ReminderStatus::IN_PROGRESS
        ];

        return $this->fixtures->create('invoice', $attributes);
    }
}
