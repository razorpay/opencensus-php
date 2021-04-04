<?php

namespace RZP\Tests\Unit\Models\Payment;


use Carbon\Carbon;
use RZP\Models\Merchant;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Unit\Mock\ProcessorMock;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class ProcessorTest extends TestCase
{
    use PaymentTrait;

    use DbEntityFetchTrait;

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

    public function testUpiBlock()
    {
        $this->processorMock->processInputForUpi($this->input);

        $this->assertNotNull($this->input['vpa']);
        $this->assertSame($this->input['vpa'], $this->input['upi']['vpa']);
    }

    public function testUpiBlockWithMetaInput()
    {
        unset($this->input['upi']);

        $this->input['_']['flow'] = 'intent';

        $this->processorMock->processInputForUpi($this->input);

        $this->assertNotNull($this->input['upi']['flow']);
    }

    public function testUpiBlockMetaDefault()
    {
        $this->input['upi']['flow'] = 'intent';

        $this->input['upi']['vpa'] = 'some@icici';

        $this->processorMock->processInputForUpi($this->input);

        $this->assertSame('intent', $this->input['_']['flow']);

        $this->assertSame('some@icici', $this->input['vpa']);
    }

    public function testUpiBlockForDefaultFlow()
    {
        unset($this->input['upi']);

        $this->processorMock->processInputForUpi($this->input);

        $this->assertSame('collect', $this->input['upi']['flow']);
    }

    public function testUpiBlockForPriority()
    {
        $this->input['vpa'] = 'someother@icici';

        $this->input['upi']['vpa'] = 'higherpriority@icici';

        $this->processorMock->processInputForUpi($this->input);

        $this->assertSame('higherpriority@icici', $this->input['upi']['vpa']);

        $this->assertSame('higherpriority@icici', $this->input['vpa']);
    }


    public function testUpiBlockForDefaultValuesOTM()
    {
        $this->input['upi']['type'] = 'otm';

        $this->processorMock->processInputForUpi($this->input);

        $this->assertNotNull($this->input['upi']['start_time']);

        $this->assertNotNull($this->input['upi']['end_time']);
    }

    public function testUpiBlockForDefaultValuesOTMGivenEndDate()
    {
        $this->input['upi']['type'] = 'otm';

        $this->input['upi']['end_time'] = Carbon::now()->addDay(1)->getTimestamp();

        $this->processorMock->processInputForUpi($this->input);

        $this->assertNotNull($this->input['upi']['start_time']);
    }

    public function testUpiBlockForDefaultValuesOTMGivenStartDate()
    {
        $this->input['upi']['type'] = 'otm';

        $this->input['upi']['start_time'] = Carbon::now()->addDay(1)->getTimestamp();

        $this->processorMock->processInputForUpi($this->input);

        $this->assertNotNull($this->input['upi']['end_time']);
    }

    public function testUpiBlockForOTMGivenDatesUnchanged()
    {
        $this->input['upi']['type'] = 'otm';

        $now = Carbon::now();

        $startDate = $now->getTimestamp();

        $endDate = $now->addDays(3)->getTimestamp();

        $this->input['upi']['start_time'] = $startDate;

        $this->input['upi']['end_time'] = $endDate;

        $this->processorMock->processInputForUpi($this->input);

        $this->assertSame($startDate, $this->input['upi']['start_time']);

        $this->assertSame($endDate, $this->input['upi']['end_time']);
    }
}
