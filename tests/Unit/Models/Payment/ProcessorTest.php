<?php

namespace RZP\Tests\Unit\Models\Payment;


use Carbon\Carbon;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;
use RZP\Exception\GatewayErrorException;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant;
use RZP\Models\Payment\Analytics\Metadata;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Processor\Processor;
use RZP\Models\Payment\Status;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Unit\Mock\ProcessorMock;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Trace\ApiTraceProcessor;
use function GuzzleHttp\Promise\queue;

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

    public function testPaypalAsBackupForInternationalPayments(){

        $ex = new GatewayErrorException(ErrorCode::GATEWAY_ERROR_TRANSACTION_NOT_PERMITTED);
        $paypal = [
            'paypal' => true
        ];

        $payment = \Mockery::mock(Entity::class);
        $merchant = \Mockery::mock(Merchant\Entity::class);

        $processor = \Mockery::mock(Processor::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $payment->shouldReceive('isCard')->andReturn(true);
        $payment->shouldReceive('isInternational')->andReturn(true);
        $payment->shouldReceive('getMetadata')->withAnyArgs()->andReturn(Metadata::CHECKOUTJS);

        $merchant->shouldReceive('isFeatureEnabled')->with(Constants::DISABLE_PAYPAL_AS_BACKUP)->andReturn(false);
        $merchant->shouldReceive('getMethods->getEnabledWallets')->andReturn($paypal);

        $processor->shouldReceive('updatePaymentFailed')->withAnyArgs()->andReturnNull();

        $processor->addBackupMethodForRetry($payment, $merchant, $ex);
        $data = $ex->getData();

        self::assertArrayHasKey('error', $data);
        self::assertArrayHasKey('metadata', $data['error']);
        self::assertArrayHasKey('next', $data['error']['metadata']);

        $retryBlock = array();
        foreach ($data['error']['metadata']['next'] as $block){
            if(isset($block['action']) && $block['action'] === 'suggest_retry'){
                $retryBlock = $block;
            }
        }

        self::assertEquals('suggest_retry', $retryBlock['action']);
        self::assertArrayHasKey('instruments', $retryBlock);

        $instruments = array();
        foreach ($retryBlock['instruments'] as $i){
            if(isset($i['instrument']) && $i['instrument'] === \RZP\Models\Merchant\Methods\Entity::PAYPAL){
                $instruments = $i;
            }
        }
        self::assertEquals(\RZP\Models\Merchant\Methods\Entity::PAYPAL, $instruments['instrument']);
        self::assertEquals('wallet', $instruments['method']);
    }

    public function testPaypalAsBackupForNonInternationalPayments(){

        $ex = new GatewayErrorException(ErrorCode::GATEWAY_ERROR_TRANSACTION_NOT_PERMITTED);
        $paypal = [
            'paypal' => true
        ];

        $payment = \Mockery::mock(Entity::class);
        $merchant = \Mockery::mock(Merchant\Entity::class);

        $processor = \Mockery::mock(Processor::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $payment->shouldReceive('isCard')->andReturn(true);
        $payment->shouldReceive('isInternational')->andReturn(false);
        $payment->shouldReceive('getMetadata')->withAnyArgs()->andReturn(Metadata::CHECKOUTJS);

        $merchant->shouldReceive('isFeatureEnabled')->with(Constants::DISABLE_PAYPAL_AS_BACKUP)->andReturn(false);
        $merchant->shouldReceive('getMethods->getEnabledWallets')->andReturn($paypal)->zeroOrMoreTimes();

        $processor->shouldReceive('updatePaymentFailed')->withAnyArgs()->andReturnNull();

        $processor->addBackupMethodForRetry($payment, $merchant, $ex);
        if(isset($ex->getData()['error']['metadata'])) {
            self::assertArrayNotHasKey('next', $ex->getData()['error']['metadata']);
        }
    }

    public function testPaypalAsBackupForNonCheckoutJSLibs(){

        $ex = new GatewayErrorException(ErrorCode::GATEWAY_ERROR_TRANSACTION_NOT_PERMITTED);
        $paypal = [
            'paypal' => true
        ];

        $payment = \Mockery::mock(Entity::class);
        $merchant = \Mockery::mock(Merchant\Entity::class);

        $processor = \Mockery::mock(Processor::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $payment->shouldReceive('isCard')->andReturn(true);
        $payment->shouldReceive('isInternational')->andReturn(false);
        $payment->shouldReceive('getMetadata')->withAnyArgs()->andReturn(Metadata::RAZORPAYJS);

        $merchant->shouldReceive('isFeatureEnabled')->with(Constants::DISABLE_PAYPAL_AS_BACKUP)->andReturn(false);
        $merchant->shouldReceive('getMethods->getEnabledWallets')->andReturn($paypal)->zeroOrMoreTimes();

        $processor->shouldReceive('updatePaymentFailed')->withAnyArgs()->andReturnNull();

        $processor->addBackupMethodForRetry($payment, $merchant, $ex);
        if(isset($ex->getData()['error']['metadata'])) {
            self::assertArrayNotHasKey('next', $ex->getData()['error']['metadata']);
        }
    }

    public function testPaypalAsBackupForInternationalPaymentsWithNonPaypalMerchants(){

        $ex = new GatewayErrorException(ErrorCode::GATEWAY_ERROR_TRANSACTION_NOT_PERMITTED);

        $payment = \Mockery::mock(Entity::class);
        $merchant = \Mockery::mock(Merchant\Entity::class);

        $processor = \Mockery::mock(Processor::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $payment->shouldReceive('isCard')->andReturn(true);
        $payment->shouldReceive('isInternational')->andReturn(true);
        $payment->shouldReceive('getMetadata')->withAnyArgs()->andReturn(Metadata::CHECKOUTJS);

        $merchant->shouldReceive('isFeatureEnabled')->with(Constants::DISABLE_PAYPAL_AS_BACKUP)->andReturn(false);
        $merchant->shouldReceive('getMethods->getEnabledWallets')->andReturn(array());

        $processor->shouldReceive('updatePaymentFailed')->withAnyArgs()->andReturnNull();

        $processor->addBackupMethodForRetry($payment, $merchant, $ex);
        if(isset($ex->getData()['error']['metadata'])) {
            self::assertArrayNotHasKey('next', $ex->getData()['error']['metadata']);
        }
    }

    public function testPaypalAsBackupForNon3dsInternational(){

        $ex = new GatewayErrorException(ErrorCode::BAD_REQUEST_NON_3DS_INTERNATIONAL_NOT_ALLOWED);
        $paypal = [
            'paypal' => true
        ];

        $payment = \Mockery::mock(Entity::class);
        $merchant = \Mockery::mock(Merchant\Entity::class);

        $processor = \Mockery::mock(Processor::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $payment->shouldReceive('isCard')->andReturn(true);
        $payment->shouldReceive('isInternational')->andReturn(true);
        $payment->shouldReceive('getMetadata')->withAnyArgs()->andReturn(Metadata::CHECKOUTJS);

        $merchant->shouldReceive('isFeatureEnabled')->with(Constants::DISABLE_PAYPAL_AS_BACKUP)->andReturn(false);
        $merchant->shouldReceive('getMethods->getEnabledWallets')->andReturn($paypal);

        $processor->shouldReceive('updatePaymentFailed')->withAnyArgs()->andReturnNull();

        $processor->addBackupMethodForRetry($payment, $merchant, $ex);
        $data = $ex->getData();
        self::assertArrayHasKey('error', $data);
        self::assertArrayHasKey('metadata', $data['error']);
        self::assertArrayHasKey('next', $data['error']['metadata']);

        $retryBlock = array();
        foreach ($data['error']['metadata']['next'] as $block){
            if(isset($block['action']) && $block['action'] === 'suggest_retry'){
                $retryBlock = $block;
            }
        }

        self::assertEquals('suggest_retry', $retryBlock['action']);
        self::assertArrayHasKey('instruments', $retryBlock);

        $instruments = array();
        foreach ($retryBlock['instruments'] as $i){
            if(isset($i['instrument']) && $i['instrument'] === \RZP\Models\Merchant\Methods\Entity::PAYPAL){
                $instruments = $i;
            }
        }
        self::assertEquals(\RZP\Models\Merchant\Methods\Entity::PAYPAL, $instruments['instrument']);
        self::assertEquals('wallet', $instruments['method']);
    }

    public function testUpdatePaymentWithOptimizerGatewayDataSuccess()
    {
        $payment = \Mockery::mock(Entity::class)->makePartial();
        $merchant = \Mockery::mock(Merchant\Entity::class)->makePartial();
        $terminal = \Mockery::mock(Terminal\Entity::class);

        $merchant->shouldReceive('isFeatureEnabled')->with(Constants::RAAS)->andReturn(true);
        $terminal->shouldReceive('isOptimizer')->andReturn(true);
        $response['data']['optimizer_gateway_data']['notes'] = '{"bankcode":"Visa","mode":"CC","PG-TYPE":"CC"}';

        $payment->shouldReceive('getAttribute')->with('merchant')->andReturn($merchant);
        $payment->shouldReceive('getAttribute')->with('terminal')->andReturn($terminal);
        $payment->setNotes(['a' => 'b']);

        $this->processorMock->runUpdatePaymentWithOptimizerGatewayData($payment, $response);

        self::assertEquals('Visa', $payment->getNotes()->toArray()['bankcode']);
        self::assertEquals('CC', $payment->getNotes()->toArray()['PG-TYPE']);
        self::assertEquals('CC', $payment->getNotes()->toArray()['mode']);
        self::assertEquals('b', $payment->getNotes()->toArray()['a']);
    }

    public function testUpdatePaymentWithOptimizerGatewayData_NonOptimizer1()
    {
        $payment = \Mockery::mock(Entity::class)->makePartial();
        $merchant = \Mockery::mock(Merchant\Entity::class)->makePartial();
        $terminal = \Mockery::mock(Terminal\Entity::class);

        $merchant->shouldReceive('isFeatureEnabled')->with(Constants::RAAS)->andReturn(false);
        $terminal->shouldReceive('isOptimizer')->andReturn(true);
        $response['data']['optimizer_gateway_data']['notes']= '{"bankcode":"Visa","mode":"CC","PG-TYPE":"CC"}';

        $payment->shouldReceive('getAttribute')->with('merchant')->andReturn($merchant);
        $payment->shouldReceive('getAttribute')->with('terminal')->andReturn($terminal);
        $payment->setNotes(['a' => 'b']);

        $this->processorMock->runUpdatePaymentWithOptimizerGatewayData($payment, $response);

        self::assertEquals('b', $payment->getNotes()->toArray()['a']);
        self::assertEmpty($payment->getNotes()->toArray()['bankcode']);
        self::assertEmpty($payment->getNotes()->toArray()['PG-TYPE']);
        self::assertEmpty($payment->getNotes()->toArray()['mode']);
    }

    public function testUpdatePaymentWithOptimizerGatewayData_NonOptimizer2()
    {
        $payment = \Mockery::mock(Entity::class)->makePartial();
        $merchant = \Mockery::mock(Merchant\Entity::class)->makePartial();
        $terminal = \Mockery::mock(Terminal\Entity::class);

        $merchant->shouldReceive('isFeatureEnabled')->with(Constants::RAAS)->andReturn(true);
        $terminal->shouldReceive('isOptimizer')->andReturn(false);
        $response['data']['optimizer_gateway_data']['notes']= '{"bankcode":"Visa","mode":"CC","PG-TYPE":"CC"}';

        $payment->shouldReceive('getAttribute')->with('merchant')->andReturn($merchant);
        $payment->shouldReceive('getAttribute')->with('terminal')->andReturn($terminal);
        $payment->setNotes(['a' => 'b']);

        $this->processorMock->runUpdatePaymentWithOptimizerGatewayData($payment, $response);

        self::assertEquals('b', $payment->getNotes()->toArray()['a']);
        self::assertEmpty($payment->getNotes()->toArray()['bankcode']);
        self::assertEmpty($payment->getNotes()->toArray()['PG-TYPE']);
        self::assertEmpty($payment->getNotes()->toArray()['mode']);
    }

    public function testUpdatePaymentWithOptimizerGatewayData_EmptyNotesData()
    {
        $payment = \Mockery::mock(Entity::class)->makePartial();
        $merchant = \Mockery::mock(Merchant\Entity::class)->makePartial();
        $terminal = \Mockery::mock(Terminal\Entity::class);

        $merchant->shouldReceive('isFeatureEnabled')->with(Constants::RAAS)->andReturn(true);
        $terminal->shouldReceive('isOptimizer')->andReturn(true);
        $response['data']['optimizer_gateway_data']['notes']= '';

        $payment->shouldReceive('getAttribute')->with('merchant')->andReturn($merchant);
        $payment->shouldReceive('getAttribute')->with('terminal')->andReturn($terminal);
        $payment->setNotes(['a' => 'b']);

        $this->processorMock->runUpdatePaymentWithOptimizerGatewayData($payment, $response);

        self::assertEquals('b', $payment->getNotes()->toArray()['a']);
        self::assertEmpty($payment->getNotes()->toArray()['bankcode']);
        self::assertEmpty($payment->getNotes()->toArray()['PG-TYPE']);
        self::assertEmpty($payment->getNotes()->toArray()['mode']);
    }

    public function testCheckMerchantPermissionsForNonActivatedMerchantLiveMode()
    {
        // prepare basicAuth mock
        $authMock = $this->getMockBuilder(BasicAuth::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['isProductBanking','isProxyAuth'])
            ->getMock();

        $authMock->method('isProductBanking')
            ->willReturn(true);

        $authMock->method('isProxyAuth')
            ->willReturn(true);

        $this->app->instance('basicauth', $authMock);

        // set app mode
        $this->app['rzp.mode'] = 'live';

        // create required fixtures
        $merchant = $this->fixtures->create('merchant', [
            'activated' => false
        ]);

        $payment = $this->fixtures->create('payment');

        $this->fixtures->create('banking_account', [
            'merchant_id'   =>  $merchant->getId(),
            'account_type'  => \RZP\Models\BankingAccount\AccountType::CURRENT,
            'channel'       => \Rzp\Models\BankingAccount\Channel::RBL,
            'status'        => \RZP\Models\BankingAccount\Status::ACTIVATED
        ]);

        // create an object & access functions to assert that checkMerchantPermissions has not failed
        $processor = new ProcessorMock($merchant);
        $processor->setPayment($payment);
        self::assertEquals($payment, $processor->getPayment());
    }
}
