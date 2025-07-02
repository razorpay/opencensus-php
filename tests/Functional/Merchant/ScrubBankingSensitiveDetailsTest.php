<?php

namespace Functional\Merchant;

use Illuminate\Routing\Router;
use Monolog\DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use RZP\Trace\ApiTraceProcessor;
use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccountTpv\Status;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class ScrubBankingSensitiveDetailsTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function mockRouter($route)
    {
        $routerMock = $this->getMockBuilder(Router::class)
            ->setConstructorArgs([$this->app['events'], $this->app])
            ->onlyMethods(['currentRouteName'])
            ->getMock();

        $routerMock->method('currentRouteName')
            ->willReturn($route);

        $this->app->instance('router', $routerMock);
    }

    public function testSensitiveDataFundAccountCreate()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('fund_account_create');

        $context = [
                'contact_id' => 'cont_BXV5GAmaJEcGr1',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'ifsc' => 'BKDN0611454',
                    'account_number' => '145410038647',
                    'name' => '4687796724004887',
                ],
        ];

        $record = new LogRecord(datetime: new DateTimeImmutable(true),
            channel: 'test',
            level: Level::Debug,
            message: 'test',
            context: $context,
            extra: []);

        $updatedRecord = $trace($record);

        $expectedContext = [
                'contact_id' => 'cont_BXV5GAmaJEcGr1',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'ifsc' => 'BKDN0611454',
                    'account_number' => 'SCRUBBED(12)',
                    'name' => 'SCRUBBED(24)',
                ],
        ];
        $this->assertArraySelectiveEquals($expectedContext, $updatedRecord->context);
    }

    public function testSensitiveDataContactCreate()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('contact_create');

        $context = [
                'name' => '51037205',
                'contact' => '8979253299',
                'email' => 'sachingangwarbly123@gmail.com',
                'type' => 'customer',
        ];

        $record = new LogRecord(datetime: new DateTimeImmutable(true),
            channel: 'test',
            level: Level::Debug,
            message: 'test',
            context: $context,
            extra: []);

        $updatedRecord = $trace($record);

        $expectedContext = [
                'name' => 'SCRUBBED(8)',
                'contact' => 'PHONE_NUMBER_SCRUBBED(10)',
                'email' => 'EMAIL_SCRUBBED(29)',
                'type' => 'customer',
        ];
        $this->assertArraySelectiveEquals($expectedContext, $updatedRecord->context);
    }

    public function testSensitiveDataBankingAccountCreate()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('banking_account_create');

        $context = [
                'pincode' => '51037205',
                'channel' => 'rbl',
                'account_ifsc' => 'IFSC2345',
                'beneficiary_email' => 'raj@gmail.com',
                'beneficiary_mobile' => '9177278077',
                'beneficiary_name' => 'raj',
                'beneficiary_address1' => 'Koramangala',
                'bank_reference_number' => 'YesBank123',
        ];

        $record = new LogRecord(datetime: new DateTimeImmutable(true),
            channel: 'test',
            level: Level::Debug,
            message: 'test',
            context: $context,
            extra: []);

        $updatedRecord = $trace($record);

        $expectedContext = [
                'pincode' => '51037205',
                'channel' => 'rbl',
                'account_ifsc' => 'SCRUBBED(8)',
                'beneficiary_email' => 'EMAIL_SCRUBBED(13)',
                'beneficiary_mobile' => 'PHONE_NUMBER_SCRUBBED(10)',
                'beneficiary_name' => 'SCRUBBED(3)',
                'beneficiary_address1' => 'SCRUBBED(11)',
                'bank_reference_number' => 'YesBank123'
        ];
        $this->assertArraySelectiveEquals($expectedContext, $updatedRecord->context);
    }

    public function testUnSensitiveDataFundAccountCreate()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('fund_account_create');

        $context = [
                'contact_id' => 'cont_BXV5GAmaJEcGr1',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'ifsc' => 'BKDN0611454',
                    'bank_name' => 'YESBank',
                ],
                'merchantId' => '10000000000000',
        ];

        $record = new LogRecord(datetime: new DateTimeImmutable(true),
            channel: 'test',
            level: Level::Debug,
            message: 'test',
            context: $context,
            extra: []);

        $updatedRecord = $trace($record);

        $expectedContext = [
                'contact_id' => 'cont_BXV5GAmaJEcGr1',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'ifsc' => 'BKDN0611454',
                    'bank_name' => 'YESBank',
                ],
                'merchantId' => '10000000000000',
        ];
        $this->assertArraySelectiveEquals($expectedContext, $updatedRecord->context);
    }

    public function testMultipleAccountsScrubbingFundAccountCreate()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('fund_account_create');

        $context = [
                'contact_id' => 'cont_BXV5GAmaJEcGr1',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'account_number' => [
                        '145410038647',
                        '743697163173631',
                    ],
                ],
        ];

        $record = new LogRecord(datetime: new DateTimeImmutable(true),
            channel: 'test',
            level: Level::Debug,
            message: 'test',
            context: $context,
            extra: []);

        $updatedRecord = $trace($record);

        $expectedContext = [
                'contact_id' => 'cont_BXV5GAmaJEcGr1',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'account_number' => [
                        'SCRUBBED(12)',
                        'SCRUBBED(15)',
                    ],
                ],
        ];
        $this->assertArraySelectiveEquals($expectedContext, $updatedRecord->context);
    }

    public function testingForNonBankingRouteCheckout()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('checkout');

        $context = [
                'account_number' => '4012888888881881',
        ];

        $record = new LogRecord(datetime: new DateTimeImmutable(true),
            channel: 'test',
            level: Level::Debug,
            message: 'test',
            context: $context,
            extra: []);

        $updatedRecord = $trace($record);

        $expectedContext = [
                'account_number' => "4012888888881881"
        ];

        $this->assertArraySelectiveEquals($expectedContext, $updatedRecord->context);
    }

    public function testSensitiveDataTpvCreate()
    {
        /** @var ApiTraceProcessor $trace */
        $trace = new ApiTraceProcessor($this->app);

        $this->mockRouter('admin_tpv_create');

        $context = [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::APPROVED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'created_by'           => 'OPS_A',
        ];

        $record = new LogRecord(datetime: new DateTimeImmutable(true),
            channel: 'test',
            level: Level::Debug,
            message: 'test',
            context: $context,
            extra: []);

        $updatedRecord = $trace($record);

        $expectedContext = [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::APPROVED,
                'payer_name'           => 'SCRUBBED(8)',
                'payer_account_number' => 'SCRUBBED(14)',
                'payer_ifsc'           => 'CITI0000006',
                'created_by'           => 'OPS_A',
        ];

        $this->assertArraySelectiveEquals($expectedContext, $updatedRecord->context);
    }
}
