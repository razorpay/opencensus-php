<?php

namespace RZP\Tests\Unit\Models\Payment;

use Carbon\Carbon;
use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Tests\Unit\Mock\ProcessorMock;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;

class EntityBuildTest extends TestCase
{
    use PaymentTrait;

    protected $processorMock;

    protected $input;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = 'test';

        $merchant = $this->fixtures->create('merchant', [Merchant\Entity::MAX_PAYMENT_AMOUNT => 100000000]);

        $this->processorMock = new ProcessorMock($merchant);

        $this->input = $this->getDefaultUpiBlockPaymentArray();
    }

    public function testPaymentEntityForVpa()
    {
        $this->markTestSkipped();

        $this->input['upi']['type'] = 'otm';

        $this->processorMock->processInputForUpi($this->input);

        $payment = $this->processorMock->runBuildPayment($this->input);

        $this->assertSame($this->input['upi']['vpa'], $payment->getVpa());
    }

    public function testPayloadForInvalidVpa()
    {
        $this->expectException(BadRequestException::class);

        $this->input['upi']['vpa'] = 'someinvalidicici';

        $this->processorMock->processInputForUpi($this->input);

        $this->processorMock->runBuildPayment($this->input);
    }

    public function testPayloadForInvalidOTMDates()
    {
        $this->markTestSkipped();

        $this->expectException(BadRequestValidationFailureException::class);

        $this->input['upi']['type'] = 'otm';

        $this->input['upi']['vpa'] = 'some@icici';

        $this->input['upi']['start_date'] = Carbon::now()->addDays(2)->getTimestamp();

        $this->input['upi']['end_date'] = Carbon::now()->addDays(1)->getTimestamp();

        $this->processorMock->processInputForUpi($this->input);

        $this->processorMock->runBuildPayment($this->input);
    }
}
