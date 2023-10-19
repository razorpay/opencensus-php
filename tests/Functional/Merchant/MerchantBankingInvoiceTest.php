<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;

use Mockery;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Invoice;
use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Merchant\Invoice as MerchantInvoice;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;


class MerchantBankingInvoiceTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected $eInvoiceClientMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantBankingInvoiceTestData.php';

        parent::setUp();

        $this->setUpEInvoiceClientMock();

        $this->ba->publicAuth();
    }

    public function setUpEInvoiceClientMock()
    {
        $this->app['rzp.mode']= 'test';

        $this->eInvoiceClientMock = Mockery::mock('RZP\Services\EInvoice', [$this->app])->makePartial();

        $this->eInvoiceClientMock->shouldAllowMockingProtectedMethods();

        $this->app['einvoice_client'] = $this->eInvoiceClientMock;
    }

    protected function createDataForBankingInvoiceEntityCreateForGivenMonthYear()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 100000,
                'account_number' => '2224440041626905',
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'                   => '10000000000000',
                'gstin'                         => '29BBYPA2999E1Z0',
                'business_registered_pin'       => '560030',
                'business_registered_address'   => 'abc street',
                'business_registered_city'      => 'abcdef',
                'business_name'                 => 'abcd'
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $x['id'],
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $x['id'],
                'fee_type'          =>      'free_credits',
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        return $x['id'];
    }

    protected function createDataForBankingInvoiceEntityCreateForGivenMonthYearForRblCa()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id'    => '10000000000000',
                'type'           => 'banking',
                'balance'        => 100000,
                'account_number' => '2224440041626905',
                'channel'        => 'rbl',
                'account_type'   => 'direct',
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'                   => '10000000000000',
                'gstin'                         => '29kjsngjk213922',
                'business_registered_pin'       => '123456',
                'business_registered_address'   => 'abc street',
                'business_registered_city'      => 'abcdef',
                'business_name'                 => 'abcd'
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $x['id'],
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $x['id'],
                'fee_type'          =>      'free_credits',
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        return $x['id'];
    }

    protected function createDataForBankingInvoiceEntityCreateForGivenMonthYearForIciciCa()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id'    => '10000000000000',
                'type'           => 'banking',
                'balance'        => 100000,
                'account_number' => '2224440041626905',
                'channel'        => 'icici',
                'account_type'   => 'direct',
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'                   => '10000000000000',
                'gstin'                         => '29BBYPA2999E1Z0',
                'business_registered_pin'       => '560030',
                'business_registered_address'   => 'abc street',
                'business_registered_city'      => 'abcdef',
                'business_name'                 => 'abcd'
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $x['id'],
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $x['id'],
                'fee_type'          =>      'free_credits',
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        return $x['id'];
    }

    protected function createDataForBankingInvoiceEntityCreateForGivenMonthYearForRblCaAndVANonZero()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id'    => '10000000000000',
                'type'           => 'banking',
                'balance'        => 100000,
                'account_number' => '2224440041626905',
                'channel'        => 'rbl',
                'account_type'   => 'direct',
            ]);

        $z = $this->fixtures->create('balance',
            [
                'merchant_id'    => '10000000000000',
                'type'           => 'banking',
                'balance'        => 100000,
                'account_number' => '2224440041626906',
                'account_type'   => 'shared',
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'                   => '10000000000000',
                'gstin'                         => '29kjsngjk213922',
                'business_registered_pin'       => '123456',
                'business_registered_address'   => 'abc street',
                'business_registered_city'      => 'abcdef',
                'business_name'                 => 'abcd'
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $w = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $z['id'],
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $x['id'],
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $x['id'],
                'fee_type'          =>      'free_credits',
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        return [$x['id'], $z['id']];
    }

    protected function createDataForBankingInvoiceEntityCreateForGivenMonthYearForRblCaActivatedAndNonKyc()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 0,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id'    => '10000000000000',
                'type'           => 'banking',
                'balance'        => 100000,
                'account_number' => '2224440041626905',
                'channel'        => 'rbl',
                'account_type'   => 'direct',
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'                   => '10000000000000',
                'gstin'                         => '29kjsngjk213922',
                'business_registered_pin'       => '123456',
                'business_registered_address'   => 'abc street',
                'business_registered_city'      => 'abcdef',
                'business_name'                 => 'abcd'
            ]);

        $this->fixtures->create(
            'banking_account',
            [
                'merchant_id'    => '10000000000000',
                'channel'        => 'rbl',
                'account_type'   => 'current',
                'status'         => 'activated',
            ]);

        $this->fixtures->create('banking_account_statement_details',[
            'id'             => 'xbas0000000002',
            'merchant_id'    => '10000000000000',
            'balance_id'     => $x->getId(),
            'account_number' => '2224440041626905',
            'channel'        => Details\Channel::RBL,
            'status'         => Details\Status::ACTIVE,
        ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $x['id'],
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $x['id'],
                'fee_type'          =>      'free_credits',
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        return $x['id'];
    }

    protected function setupBankingInvoice()
    {
        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYear();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year,'merchant_ids' => ['10000000000000']],
        ];

        $content = $this->makeRequestAndGetContent($request);

        return [$content, $balanceId];
    }

    protected function setupBankingInvoiceForCAandRBL()
    {
        $oldDateTime = Carbon::create(2021, 05, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYear();

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           => 'rbl',
                'amount'            => 1000,
                'balance_id'        => $balanceId,
                'initiated_at' => Carbon::now(Timezone::IST)->timestamp,
                'pricing_rule_id'   => '1zE31zbybacac1',
            ]);

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year,'merchant_ids' => ['10000000000000']],
        ];

        $content = $this->makeRequestAndGetContent($request);

        return [$content, $balanceId];
    }

    protected function createDataForBankingInvoiceWithFailedPayoutsInGivenMonthAndYear()
    {
        $bankingBalanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYear();

        $this->fixtures->create('pricing', [
            'product'        => 'banking',
            'id'             => '1zE31zbybacac1',
            'plan_id'        => '1hDYlICobzOCYt',
            'plan_name'      => 'testDefaultPlan',
            'feature'        => 'fund_account_validation',
            'payment_method' => 'bank_account',
            'percent_rate'   => 900,
            'org_id'         => '100000razorpay',
        ]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'citi',
                'amount'     => 1000,
                'balance_id' => $bankingBalanceId,
                'pricing_rule_id'   =>      '1zE31zbybacac1',
            ]);

        $w = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'citi',
                'amount'     => 1000,
                'balance_id' => $bankingBalanceId,
                'pricing_rule_id'   =>      '1zE31zbybacac1',
            ]);

        $z = $this->fixtures->create(
            'payout',
            [
                'channel'      => 'rbl',
                'amount'       => 1000,
                'status'       => 'failed',
                'balance_id'   => $bankingBalanceId,
                'initiated_at' => Carbon::now(Timezone::IST)->timestamp,
                'pricing_rule_id'   =>      '1zE31zbybacac1',
            ]);

        // this payout should not be included in invoice amount
        $z1 = $this->fixtures->create(
            'payout',
            [
                'channel'      => 'rbl',
                'amount'       => 1000,
                'status'       => 'failed',
                'balance_id'   => $bankingBalanceId,
                'fee_type'     => 'free_credits',
                'initiated_at' => Carbon::now(Timezone::IST)->timestamp,
                'pricing_rule_id'   =>      '1zE31zbybacac1',
            ]);

        $this->fixtures->create(
            'feature', [
            'name'        => 'fund_account_validation',
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $faAttributes = [
            'id'          => '100000000000fa',
            'source_id'   => '1000001contact',
            'source_type' => 'contact',
        ];

        $baAttributes = [
            'id'             => 'xba00000000000',
            'account_number' => '111000',
            'ifsc'           => 'SBIN0007105',
            'name'           => 'kunal sikri'
        ];

        $this->fixtures->fund_account->createBankAccount($faAttributes ,$baAttributes);

        $this->ba->privateAuth();

        $request = [
            'url'     => '/fund_accounts/validations',
            'method'  => 'POST',
            'content' => [
                'fund_account' => [
                    'id'           => 'fa_100000000000fa',
                    'account_type' => 'bank_account',
                    'details'      => [
                        'account_number' => '111000',
                        'ifsc'           => 'SBIN0007105',
                        'name'           => 'kunal sikri',
                    ]
                ],
                'balance_id'   => $bankingBalanceId,
                'amount'       => 100
            ],
        ];

        $this->makeRequestAndGetContent($request);

        return [$y['id'], $w['id'] ,$z['id'], $z1['id']];
    }

    //Basic Banking Invoice Test
    public function testBankingInvoiceEntityCreateForGivenMonthYear()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYear();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::BALANCE_ID => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions']['balance_id'] = $balanceId;

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        Carbon::setTestNow();
    }

    public function setupEInvoiceClientResponse($expectedContent)
    {
        $this->eInvoiceClientMock
            ->shouldReceive('getEInvoice')
            ->times(1)
            ->with(Mockery::on(function (string $mode)  use ($expectedContent)
            {
                if($mode !== Mode::TEST)
                {
                    return false;
                }

                return true;

            }), Mockery::on(function(array $input) use ($expectedContent)
            {
                //$this->assertArraySelectiveEquals($input, $expectedContent);

                return true;
            }))
            ->andReturnUsing(function () {
                return [
                    'status'            => '200',
                    'body'   => [
                        'results' => [
                            'message'   => [
                                'Status'        => 'generated',
                                'Irn'           => 'randomirn',
                                'SignedInvoice' => 'randominvoice',
                                'SignedQRCode'  => 'randomcode',
                                'QRCodeUrl'     => 'randomurl',
                                'EinvoicePdf'   => 'randompdf',
                            ],
                            'status'    => 'Success',
                        ],
                    ],
                ];
            });
    }

    public function testBankingInvoiceEntityCreateWithEInvoice()
    {
        $this->markTestSkipped('The flakiness in the testcase needs to be fixed. Skipping as its impacting dev-productivity.');

        $oldDateTime = Carbon::create(2021, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYear();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $eInvoiceEntities = $this->getEntities('merchant_e_invoice', [], true);

        $eInvoiceEntities = $eInvoiceEntities['items'][0];

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::BALANCE_ID => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions']['balance_id'] = $balanceId;

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        $this->assertEquals('10000000000000', $eInvoiceEntities['merchant_id']);
        $this->assertEquals(7, $eInvoiceEntities['month']);
        $this->assertEquals(2021, $eInvoiceEntities['year']);
        $this->assertEquals('BANKING', $eInvoiceEntities['type']);
        $this->assertEquals('generated', $eInvoiceEntities['status']);

        Carbon::setTestNow();
    }

    public function testBankingInvoiceEntityCreateWithEInvoiceForRblCa()
    {
        $oldDateTime = Carbon::create(2021, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYearForRblCa();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $expectedContent = $this->testData[__FUNCTION__]['expectedContent'];

        $this->setupEInvoiceClientResponse($expectedContent);

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $eInvoiceEntities = $this->getEntities('merchant_e_invoice', [], true);

        $eInvoiceEntities = $eInvoiceEntities['items'][0];

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::BALANCE_ID => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions']['balance_id'] = $balanceId;

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        $this->assertEquals('10000000000000', $eInvoiceEntities['merchant_id']);
        $this->assertEquals(7, $eInvoiceEntities['month']);
        $this->assertEquals(2021, $eInvoiceEntities['year']);
        $this->assertEquals('BANKING', $eInvoiceEntities['type']);
        $this->assertEquals('generated', $eInvoiceEntities['status']);
        $this->assertEquals('randomirn', $eInvoiceEntities['gsp_irn']);
        $this->assertEquals('randominvoice', $eInvoiceEntities['gsp_signed_invoice']);
        $this->assertEquals('randomcode', $eInvoiceEntities['gsp_signed_qr_code']);
        $this->assertEquals('randomurl', $eInvoiceEntities['gsp_qr_code_url']);
        $this->assertEquals('randompdf', $eInvoiceEntities['gsp_e_invoice_pdf']);


        Carbon::setTestNow();
    }

    public function testBankingInvoiceEntityCreateWithEInvoiceWithNewXActivationFlag()
    {
        $oldDateTime = Carbon::create(2023, 1, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYear();

        // Above fixture sets activated as true
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 0,
            'activated_at' => null,
            'live'         => 0
        ]);

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id'   => '10000000000000',
                'product'       => 'banking',
                'group'         => 'products_enabled',
                'type'          => 'X',
                'value'         => 'true',
                'updated_at'    => $oldDateTime->timestamp,
                'created_at'    => $oldDateTime->timestamp
            ]);

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $expectedContent = $this->testData[__FUNCTION__]['expectedContent'];

        $this->setupEInvoiceClientResponse($expectedContent);

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $eInvoiceEntities = $this->getEntities('merchant_e_invoice', [], true);

        $eInvoiceEntities = $eInvoiceEntities['items'][0];

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::BALANCE_ID => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions']['balance_id'] = $balanceId;

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        $this->assertEquals('10000000000000', $eInvoiceEntities['merchant_id']);
        $this->assertEquals(1, $eInvoiceEntities['month']);
        $this->assertEquals(2023, $eInvoiceEntities['year']);
        $this->assertEquals('BANKING', $eInvoiceEntities['type']);
        $this->assertEquals('generated', $eInvoiceEntities['status']);
        $this->assertEquals('randomirn', $eInvoiceEntities['gsp_irn']);
        $this->assertEquals('randominvoice', $eInvoiceEntities['gsp_signed_invoice']);
        $this->assertEquals('randomcode', $eInvoiceEntities['gsp_signed_qr_code']);
        $this->assertEquals('randomurl', $eInvoiceEntities['gsp_qr_code_url']);
        $this->assertEquals('randompdf', $eInvoiceEntities['gsp_e_invoice_pdf']);


        Carbon::setTestNow();
    }

    public function testBankingInvoiceEntityCreateWithEInvoiceWithDeactivatedAttribute()
    {
        $oldDateTime = Carbon::create(2023, 1, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYear();

        // Above fixture sets activated as true
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 0,
            'activated_at' => null,
            'live'         => 0
        ]);

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id'   => '10000000000000',
                'product'       => 'primary',
                'group'         => 'activation',
                'type'          => 'deactivated_at',
                'value'         => $oldDateTime->timestamp,
                'updated_at'    => $oldDateTime->timestamp,
                'created_at'    => $oldDateTime->timestamp
            ]);

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $testData = $this->testData['testBankingInvoiceEntityCreateWithEInvoiceWithNewXActivationFlag'];

        $expectedContent = $testData['expectedContent'];

        $this->setupEInvoiceClientResponse($expectedContent);

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $eInvoiceEntities = $this->getEntities('merchant_e_invoice', [], true);

        $eInvoiceEntities = $eInvoiceEntities['items'][0];

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::BALANCE_ID => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $testData;

        $data['rx_transactions']['balance_id'] = $balanceId;

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        $this->assertEquals('10000000000000', $eInvoiceEntities['merchant_id']);
        $this->assertEquals(1, $eInvoiceEntities['month']);
        $this->assertEquals(2023, $eInvoiceEntities['year']);
        $this->assertEquals('BANKING', $eInvoiceEntities['type']);
        $this->assertEquals('generated', $eInvoiceEntities['status']);
        $this->assertEquals('randomirn', $eInvoiceEntities['gsp_irn']);
        $this->assertEquals('randominvoice', $eInvoiceEntities['gsp_signed_invoice']);
        $this->assertEquals('randomcode', $eInvoiceEntities['gsp_signed_qr_code']);
        $this->assertEquals('randomurl', $eInvoiceEntities['gsp_qr_code_url']);
        $this->assertEquals('randompdf', $eInvoiceEntities['gsp_e_invoice_pdf']);


        Carbon::setTestNow();
    }

    public function testBankingInvoiceEntityCreateWithEInvoiceForIciciCa()
    {
        $oldDateTime = Carbon::create(2022, 4, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYearForIciciCa();

        // Above fixture sets activated as true
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 0,
            'activated_at' => null,
            'live'         => 0
        ]);

        $this->fixtures->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $balanceId,
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::ICICI,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $expectedContent = $this->testData[__FUNCTION__]['expectedContent'];

        $this->setupEInvoiceClientResponse($expectedContent);

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $eInvoiceEntities = $this->getEntities('merchant_e_invoice', [], true);

        $eInvoiceEntities = $eInvoiceEntities['items'][0];

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::BALANCE_ID => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions']['balance_id'] = $balanceId;

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        $this->assertEquals('10000000000000', $eInvoiceEntities['merchant_id']);
        $this->assertEquals(4, $eInvoiceEntities['month']);
        $this->assertEquals(2022, $eInvoiceEntities['year']);
        $this->assertEquals('BANKING', $eInvoiceEntities['type']);
        $this->assertEquals('generated', $eInvoiceEntities['status']);
        $this->assertEquals('randomirn', $eInvoiceEntities['gsp_irn']);
        $this->assertEquals('randominvoice', $eInvoiceEntities['gsp_signed_invoice']);
        $this->assertEquals('randomcode', $eInvoiceEntities['gsp_signed_qr_code']);
        $this->assertEquals('randomurl', $eInvoiceEntities['gsp_qr_code_url']);
        $this->assertEquals('randompdf', $eInvoiceEntities['gsp_e_invoice_pdf']);


        Carbon::setTestNow();
    }

    public function testBankingInvoiceEntityCreateWithEInvoiceForVaAndRblCa()
    {
        $oldDateTime = Carbon::create(2022, 4, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId1 = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYearForRblCaActivatedAndNonKyc();

        $x = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 100000,
                'account_number' => '2224440041626904',
            ]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $x['id'],
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $x['id'],
                'fee_type'          =>      'free_credits',
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        $balanceId2 =  $x['id'];

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id'   => '10000000000000',
                'product'       => 'banking',
                'group'         => 'products_enabled',
                'type'          => 'X',
                'value'         => 'true',
                'updated_at'    => $oldDateTime->timestamp,
                'created_at'    => $oldDateTime->timestamp
            ]);

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $expectedContent = $this->testData[__FUNCTION__]['expectedContent'];

        $this->eInvoiceClientMock
            ->shouldReceive('getEInvoice')
            ->times(2)
            ->with(Mockery::on(function (string $mode)  use ($expectedContent)
            {
                if($mode !== Mode::TEST)
                {
                    return false;
                }

                return true;

            }), Mockery::on(function(array $input) use ($expectedContent)
            {
                $this->assertArraySelectiveEquals($expectedContent, $input);

                return true;
            }))
            ->andReturnUsing(function () {
                return [
                    'status'            => '200',
                    'body'   => [
                        'results' => [
                            'message'   => [
                                'Status'        => 'generated',
                                'Irn'           => 'randomirn',
                                'SignedInvoice' => 'randominvoice',
                                'SignedQRCode'  => 'randomcode',
                                'QRCodeUrl'     => 'randomurl',
                                'EinvoicePdf'   => 'randompdf',
                            ],
                            'status'    => 'Success',
                        ],
                    ],
                ];
            });

        $this->makeRequestAndGetContent($request);

        $vaInvoiceEntities = $this->getEntities('merchant_invoice', ['invoice_number' => '10000000000X0422'], true)['items'];

        $rblInvoiceEntities = $this->getEntities('merchant_invoice', ['invoice_number' => '10000000000R0422'], true)['items'];

        $eInvoiceEntities = $this->getEntities('merchant_e_invoice', [], true);

        $vaEInvoiceEntity = [];
        $rblEInvoiceEntity = [];
        foreach ($eInvoiceEntities['items'] as $eInvoiceEntity)
        {
            if ($eInvoiceEntity['invoice_number'][11] === 'R')
            {
                $rblEInvoiceEntity = $eInvoiceEntity;
            }
            elseif ($eInvoiceEntity['invoice_number'][11] === 'X')
            {
                $vaEInvoiceEntity = $eInvoiceEntity;
            }
        }

        $data = $this->testData[__FUNCTION__];
        $this->assertValuesInInvoiceEInvoiceAndInput($vaInvoiceEntities,$vaEInvoiceEntity,$data['rx_transactions']);
        $this->assertValuesInInvoiceEInvoiceAndInput($rblInvoiceEntities,$rblEInvoiceEntity,$data['rx_transactions']);

        Carbon::setTestNow();
    }

    public function testBankingInvoiceEntityCreateWithEInvoiceForFebruaryMonth()
    {
        $oldDateTime = Carbon::create(2022, 2, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYear();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $expectedContent = $this->testData[__FUNCTION__]['expectedContent'];

        $this->setupEInvoiceClientResponse($expectedContent);

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $eInvoiceEntities = $this->getEntities('merchant_e_invoice', [], true);

        $eInvoiceEntities = $eInvoiceEntities['items'][0];

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::BALANCE_ID => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions']['balance_id'] = $balanceId;

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        $this->assertEquals('10000000000000', $eInvoiceEntities['merchant_id']);
        $this->assertEquals(2, $eInvoiceEntities['month']);
        $this->assertEquals(2022, $eInvoiceEntities['year']);
        $this->assertEquals('BANKING', $eInvoiceEntities['type']);
        $this->assertEquals('generated', $eInvoiceEntities['status']);

        Carbon::setTestNow();
    }
    protected function createDataForBankingInvoiceEntityCreateWithEInvoiceForZeroAmountLineItemForGivenMonthYear()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 100000,
                'account_number' => '2224440041626905',
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'               => '10000000000000',
                'gstin'                     => '29kjsngjk213922',
                'business_registered_pin'   => '123456',
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        return $x['id'];
    }

    public function testBankingInvoiceEntityCreateWithEInvoiceForZeroAmountLineItem()
    {
        $oldDateTime = Carbon::create(2021, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateWithEInvoiceForZeroAmountLineItemForGivenMonthYear();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $eInvoiceEntities = $this->getEntities('merchant_e_invoice', [], true);

        $eInvoiceEntities = $eInvoiceEntities['items'];

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::BALANCE_ID => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions']['balance_id'] = $balanceId;

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        $this->assertEmpty($eInvoiceEntities);

        Carbon::setTestNow();
    }

    protected function createDataForBankingInvoiceEntityCreateWithEInvoiceForNegativeAmountLineItemForGivenMonthYear()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 100000,
                'account_number' => '2224440041626905',
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'               => '10000000000000',
                'gstin'                     => '29kjsngjk213922',
                'business_registered_pin'   => '123456',
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'           =>      'icici',
                'amount'            =>      1000,
                'balance_id'        =>      $x['id'],
                'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $oldDateTime = Carbon::create(2021, 8, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $w = $this->fixtures->reversal->createPayoutReversal(
            [
                'merchant_id'   => '10000000000000',
                'entity_id'     => $y['id'],
                'entity_type'   => 'payout',
                'balance_id'    => $x['id'],
                'amount'        => 1000000,
                'fee'           => 0,
                'tax'           => 0,
            ]);

        return $x['id'];
    }

    protected function createDataForBankingInvoiceEntityCreateWithEInvoiceForNegativeAndPositiveAmountLineItem()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 10000000,
            ]);

        $y = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 100000000,
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'               => '10000000000000',
                'gstin'                     => '29kjsngjk213922',
                'business_registered_pin'   => '123456',
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $w = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000000,
                'balance_id' => $x['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $oldDateTime = Carbon::create(2021, 8, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->fixtures->reversal->createPayoutReversal(
            [
                'merchant_id'   => '10000000000000',
                'entity_id'     => $w['id'],
                'entity_type'   => 'payout',
                'balance_id'    => $x['id'],
                'amount'        => 1000000,
                'fee'           => 0,
                'tax'           => 0,
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000000,
                'balance_id' => $y['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);


        return [ $x['id'] , $y['id'] ];
    }

    protected function createDataForMultipleAccounts()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 1000000,
            ]);

        $y = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 10000000,
            ]);

        $z = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'primary',
                'balance'     => 10000000,
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'gstin'       => '29kjsngjk213922',
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $x['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000000,
                'balance_id' => $y['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $z['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000000,
                'balance_id' => $y['id'],
                'fee_type'   => 'free_credits',
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        return [ $x['id'] , $y['id'] , $z['id'] ];
    }

    //Multiple Accounts of one merchant
    //Multiple accounts mean 2 banking accounts + 1 primary account
    public function testBankingInvoiceEntityCreateForMultipleAccountsForGivenMonthYear()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForMultipleAccounts();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        //$this->assertEquals(9, $entities['count']);

        $entities = $entities['items'];

        $invoiceEntities = [];

        $output = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]][] = [
                Invoice\Entity::BALANCE_ID  => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT      => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX         => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions'][0]['balance_id'] = $balanceId[1];

        $data['rx_transactions'][1]['balance_id'] = $balanceId[0];

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        Carbon::setTestNow();
    }

    protected function createDataForMultipleAccountForMultipleMerchants()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $this->fixtures->edit('merchant', '100000Razorpay', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'abcdef1234',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 1000000,
            ]);

        $y = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 10000000,
            ]);

        $z = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'primary',
                'balance'     => 10000000,
            ]);

        $a = $this->fixtures->create('balance',
            [
                'merchant_id' => '100000Razorpay',
                'type'        => 'banking',
                'balance'     => 1000000,
            ]);

        $b = $this->fixtures->create('balance',
            [
                'merchant_id' => '100000Razorpay',
                'type'        => 'primary',
                'balance'     => 10000000,
            ]);


        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'gstin'       => '29kjsngjk213922',
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $this->fixtures->edit('merchant', '100000Razorpay', ['business_banking' => 1]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $x['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000000,
                'balance_id' => $y['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $z['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'     => 'icici',
                'amount'      => 1000,
                'balance_id'  => $a['id'],
                'merchant_id' => '100000Razorpay',
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $b['id'],
                'merchant_id' => '100000Razorpay',
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        return [ $x['id'], $y['id'], $z['id'], $a['id'], $b['id']];
    }

    //Multiple Merchants Multiple Accounts
    //Merchant 100000Razorpay has 1 banking + 1 primary account . 10000000000000 has 2 banking account + 1 primary account
    public function testBankingInvoiceEntityCreateForMultipleAccountsForGivenMonthYearForMultipleMerchants()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForMultipleAccountForMultipleMerchants();

        Carbon::setTestNow();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year,'merchant_ids' => ['100000Razorpay','10000000000000']],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        //$this->assertEquals(17, $entities['count']);

        $entities = $entities['items'];

        $invoiceEntities = [];

        $output = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]][] = [
                Invoice\Entity::MERCHANT_ID => $e[Invoice\Entity::MERCHANT_ID],
                Invoice\Entity::BALANCE_ID  => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT      => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX         => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions'][0]['balance_id'] = $balanceId[3];

        $data['rx_transactions'][1]['balance_id'] = $balanceId[1];

        $data['rx_transactions'][2]['balance_id'] = $balanceId[0];

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        Carbon::setTestNow();
    }

    protected function createDataForMultipleAccountForGivenMerchantWithNoBankingTransaction()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 1000000,
            ]);

        $y = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 10000000,
            ]);

        $z = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'primary',
                'balance'     => 10000000,
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'gstin'       => '29kjsngjk213922',
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        //Pg side transaction of payout for early settlements / wallet payouts . No transactions from banking balance
        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $z['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        return [ $x['id'], $y['id'], $z['id'] ];
    }

    // Multiple accounts of given merchant with no banking transaction .But payouts from pg balance (early settlement/wallet payout)
    public function testBankingInvoiceEntityCreateForMultipleAccountsForGivenMonthYearForGivenMerchantWithNoBankingTransaction()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForMultipleAccountForGivenMerchantWithNoBankingTransaction();

        Carbon::setTestNow();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year,'merchant_ids' => ['10000000000000']],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        //$this->assertEquals(9, $entities['count']);

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]][] = [
                Invoice\Entity::BALANCE_ID  => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT      => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX         => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions'][0]['balance_id'] = $balanceId[1];

        $data['rx_transactions'][1]['balance_id'] = $balanceId[0];

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        Carbon::setTestNow();
    }

    protected function createDataForMultipleAccountsWithPayoutReversed()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 1000000,
            ]);

        $y = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 10000000,
            ]);

        $z = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'primary',
                'balance'     => 10000000,
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'gstin'       => '29kjsngjk213922',
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $x['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $q = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $y['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $q1 = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $y['id'],
                'fee_type'   => 'free_credits',
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $w = $this->fixtures->reversal->createPayoutReversal(
            [
                'merchant_id'   => '10000000000000',
                'entity_id'     => $q['id'],
                'entity_type'   => 'payout',
                'balance_id'    => $y['id'],
                'amount'        => 1000000,
                'fee'           => 0,
                'tax'           => 0,
            ]);

        $w1 = $this->fixtures->reversal->createPayoutReversal(
            [
                'merchant_id'   => '10000000000000',
                'entity_id'     => $q1['id'],
                'entity_type'   => 'payout',
                'balance_id'    => $y['id'],
                'amount'        => 1000000,
                'fee'           => 0,
                'tax'           => 0,
            ]);

        $w = $w->toArray();

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $z['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        return [ $x['id'] , $y['id'] , $z['id'] , $w['id'] , $w1['id']];
    }

    //3 payouts happen . first payout - from banking account no 1, second payout from banking account no 2 , third
    // payout from parimary account(accounting for early settlement/wallet payouts) .Payout from second banking account
    // is reversed
    public function testBankingInvoiceEntityCreateForMultipleAccountsForGivenMonthYearWithPayoutReversed()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForMultipleAccountsWithPayoutReversed();

        Carbon::setTestNow();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        //$this->assertEquals(9, $entities['count']);

        $entities = $entities['items'];

        $invoiceEntities = [];

        $output = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]][] = [
                Invoice\Entity::BALANCE_ID => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions'][0]['balance_id'] = $balanceId[1];

        $data['rx_transactions'][1]['balance_id'] = $balanceId[0];

        $data['rx_adjustments'][0]['balance_id'] = $balanceId[1];

        $data['rx_adjustments'][1]['balance_id'] = $balanceId[0];

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        $this->assertArraySelectiveEquals($invoiceEntities['rx_adjustments'], $data['rx_adjustments']);

        Carbon::setTestNow();
    }

    protected function createDataForMultipleAccountsWithPayoutReversalInNextMonthAndNoPayoutsNextMonth()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 1000000,
            ]);

        $y = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 10000000,
            ]);

        $z = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'primary',
                'balance'     => 10000000,
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'gstin'       => '29kjsngjk213922',
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $x['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $q = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000000,
                'balance_id' => $y['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $z['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $oldDateTime = Carbon::create(2019, 8, 1, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $w = $this->fixtures->reversal->createPayoutReversal(
            [
                'merchant_id'   => '10000000000000',
                'entity_id'     => $q['id'],
                'entity_type'   => 'payout',
                'balance_id'    => $y['id'],
                'amount'        => 1000000,
                'fee'           => 0,
                'tax'           => 0,
            ]);

        $w = $w->toArray();

        return [ $x['id'] , $y['id'] , $z['id'] , $w['id'] ];
    }

    //Suppose a merchant does somes payouts at end of this month but one payout get reversed next month and also no payouts
    //next month but from another banking balance.First month invoice will include fees of payout that got reversed but it
    //will be credited in next month invoice in corresponding banking balance.
    public function testBankingInvoiceEntityCreateForMultipleAccountsWithPayoutReversalInNextMonthAndNoPayoutsNextMonth()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForMultipleAccountsWithPayoutReversalInNextMonthAndNoPayoutsNextMonth();

        Carbon::setTestNow();

        $this->ba->cronAuth();

        $request1 = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $request2 = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month + 1, 'year' => $oldDateTime->year],
        ];

        $content = $this->makeRequestAndGetContent($request1);

        $content = $this->makeRequestAndGetContent($request2);

        $entities = $this->getEntities('merchant_invoice', [], true);

        //$this->assertEquals(18, $entities['count']);

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]][] = [
                Invoice\Entity::MONTH      => $e[Invoice\Entity::MONTH],
                Invoice\Entity::YEAR       => $e[Invoice\Entity::YEAR],
                Invoice\Entity::BALANCE_ID => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions'][0]['balance_id'] = $balanceId[1];

        $data['rx_transactions'][1]['balance_id'] = $balanceId[0];

        $data['rx_transactions'][2]['balance_id'] = $balanceId[1];

        $data['rx_transactions'][3]['balance_id'] = $balanceId[0];

        $data['rx_adjustments'][0]['balance_id'] = $balanceId[1];

        $data['rx_adjustments'][1]['balance_id'] = $balanceId[0];

        $data['rx_adjustments'][2]['balance_id'] = $balanceId[1];

        $data['rx_adjustments'][3]['balance_id'] = $balanceId[0];

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        $this->assertArraySelectiveEquals($invoiceEntities['rx_adjustments'], $data['rx_adjustments']);

        Carbon::setTestNow();
    }

    protected function createDataForMultipleAccountsWithPayoutReversalInNextMonthAndSomePayoutsNextMonthFromAnotherBankingBalance()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $x = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 1000000,
            ]);

        $y = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 10000000,
            ]);

        $z = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'primary',
                'balance'     => 10000000,
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'gstin'       => '29kjsngjk213922',
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $x['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $q = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000000,
                'balance_id' => $y['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $z['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $oldDateTime = Carbon::create(2019, 8, 1, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $w = $this->fixtures->reversal->createPayoutReversal(
            [
                'merchant_id'   => '10000000000000',
                'entity_id'     => $q['id'],
                'entity_type'   => 'payout',
                'balance_id'    => $y['id'],
                'amount'        => 1000000,
                'fee'           => 0,
                'tax'           => 0,
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $x['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $w = $w->toArray();

        return [ $x['id'] , $y['id'] , $z['id'] , $w['id'] ];
    }

    //Suppose a merchant does somes payouts at end of this month but one payout get reversed next month and also does some payouts
    //next month but from another banking balance.First month invoice will include fees of payout that got reversed but it
    //will be credited in next month invoice in corresponding banking balance.
    public function testBankingInvoiceEntityCreateForMultipleAccountsWithPayoutReversalInNextMonthAndSomePayoutsNextMonthFromAnotherBankingBalance()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForMultipleAccountsWithPayoutReversalInNextMonthAndSomePayoutsNextMonthFromAnotherBankingBalance();

        Carbon::setTestNow();

        $this->ba->cronAuth();

        $request1 = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $request2 = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month + 1, 'year' => $oldDateTime->year],
        ];

        $content = $this->makeRequestAndGetContent($request1);

        $content = $this->makeRequestAndGetContent($request2);

        $entities = $this->getEntities('merchant_invoice', [], true);

        //$this->assertEquals(18, $entities['count']);

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]][] = [
                Invoice\Entity::MONTH      => $e[Invoice\Entity::MONTH],
                Invoice\Entity::YEAR       => $e[Invoice\Entity::YEAR],
                Invoice\Entity::BALANCE_ID => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions'][0]['balance_id'] = $balanceId[1];

        $data['rx_transactions'][1]['balance_id'] = $balanceId[0];

        $data['rx_transactions'][2]['balance_id'] = $balanceId[1];

        $data['rx_transactions'][3]['balance_id'] = $balanceId[0];

        $data['rx_adjustments'][0]['balance_id'] = $balanceId[1];

        $data['rx_adjustments'][1]['balance_id'] = $balanceId[0];

        $data['rx_adjustments'][2]['balance_id'] = $balanceId[1];

        $data['rx_adjustments'][3]['balance_id'] = $balanceId[0];

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        $this->assertArraySelectiveEquals($invoiceEntities['rx_adjustments'], $data['rx_adjustments']);

        Carbon::setTestNow();
    }

    protected function createDataForFetchingBankingInvoices()
    {
        list(
            $bankingBalance1,
            $bankingBalance2,
            $primaryBalance
            ) = $this->createDataForFetchingMultipleBankingInvoicesGivenNoInputsAndNoBankingInvoiceGeneratedYet();

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $bankingBalance1['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000000,
                'balance_id' => $bankingBalance2['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $primaryBalance['id'],
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);
    }

    public function testFetchMultipleBankingInvoices()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createDataForFetchingBankingInvoices();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $this->makeRequestAndGetContent($request);

        $this->ba->proxyAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testFetchMultipleBankingInvoicesWhenMonthIsGivenWithoutYear()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createDataForFetchingBankingInvoices();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $this->makeRequestAndGetContent($request);

        $this->ba->proxyAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testFetchMultipleBankingInvoicesGivenNoInputs()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createDataForFetchingBankingInvoices();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $this->makeRequestAndGetContent($request);

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month + 1, 'year' => $oldDateTime->year],
        ];

        $this->makeRequestAndGetContent($request);

        $this->ba->proxyAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    protected function createDataForFetchingMultipleBankingInvoicesGivenNoInputsAndNoBankingInvoiceGeneratedYet()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
            'business_banking' => 1,
        ]);

        $bankingBalance1 = $this->fixtures->create('balance',
            [
                'merchant_id'    => '10000000000000',
                'type'           => 'banking',
                'balance'        => 1000000,
                'account_number' => '12345',
            ]);

        $bankingBalance2 = $this->fixtures->create('balance',
            [
                'merchant_id'    => '10000000000000',
                'type'           => 'banking',
                'balance'        => 10000000,
                'account_number' => '1234567',
            ]);

        $primaryBalance = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'primary',
                'balance'     => 10000000,
            ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'gstin'       => '29kjsngjk213922',
            ]);

        return [$bankingBalance1, $bankingBalance2, $primaryBalance];
    }

    public function testFetchMultipleBankingInvoicesGivenNoInputsAndNoBankingInvoiceGeneratedYet()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createDataForFetchingMultipleBankingInvoicesGivenNoInputsAndNoBankingInvoiceGeneratedYet();

        $this->ba->proxyAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testFetchMultipleBankingInvoicesWithBusinessBankingNotEnabled()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createDataForFetchingBankingInvoices();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $this->makeRequestAndGetContent($request);

        $this->fixtures->edit('merchant', '10000000000000', ['business_banking' => 0]);

        $this->ba->proxyAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testInvoiceNumberFormat()
    {
        $this->setupBankingInvoice();

        $entities = $this->getEntities('merchant_invoice', [], true);

        $entities = $entities['items'];

        foreach ($entities as $e)
        {
            if ($e[Invoice\Entity::TYPE] === 'rx_transactions')
            {
                $invoiceEntity = $e;
                break;
            }
            else
            {
                continue;
            }
        }

        $this->assertEquals('10000000000X' . '07' . substr(2019, 2, 2),
            $invoiceEntity['invoice_number']);

        Carbon::setTestNow();
    }

    public function testBankingInvoiceDownloadFromMerchantDashboardForCARbl()
    {
        $oldDateTime = Carbon::create(2021, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYearForRblCaAndVANonZero();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $this->makeRequestAndGetContent($request);

        $this->ba->proxyAuth();

        $request = [
            'url'     => '/reports/invoice/banking',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $data = $this->testData[__FUNCTION__];
        $expectedResponse = $data['response']['content'];
        $this->assertEquals($expectedResponse['file_id'], $content['file_id']);
        $this->assertEquals($expectedResponse['error_message'], $content['error_message']);

        Carbon::setTestNow();
    }

    public function testBankingInvoiceDownloadBySeller()
    {
        // 1. override time
        $oldDateTime = Carbon::create(2021, 7, 21, 12, 23,41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        // 2. create CA & VA balance entities
        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYearForRblCaAndVANonZero();

        // 3. create invoice entities
        $this->ba->cronAuth();

        $request = [
            'url'       => '/merchants/invoice/create',
            'method'    => 'POST',
            'content'   => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $this->makeRequestAndGetContent($request);

        // 4. set up E-Invoice entity for assert
        $invoiceNumber = (new MerchantInvoice\Entity)->generateInvoiceNumberForX('10000000000000', '0721', false);

        $eInvoice = (new MerchantInvoice\EInvoice\Repository)->fetchByInvoiceNumber('10000000000000', $invoiceNumber)->first();

        $this->fixtures->edit('merchant_e_invoice', $eInvoice->getId(), [
            'status'            => 'generated',
            'gsp_irn'           => 'dummy-irn',
            'gsp_qr_code_url'   => 'dummy-qr-code'
        ]);

        // 5. download invoice for RSPL (Razorpay) and assert response
        $this->ba->proxyAuth();

        $this->mockPdfGeneratorAndUfhService([
            'e_invoice_details' => [
                'INV' => [
                    'Irn'       => 'dummy-irn',
                    'QRCodeUrl' => 'dummy-qr-code'
                ]
            ],
        ]);

        $request = [
            'url'       => '/reports/invoice/banking',
            'method'    => 'POST',
            'content'   => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year, 'seller' => 'RSPL'],
        ];

        $content = $this->makeRequestAndGetContent($request);
        $this->assertEquals('file_MQgsR8C9eceTxZ', $content['file_id']);
        $this->assertNull($content['error_message']);

        Carbon::setTestNow();
    }

    public function testMerchantInvoiceGenerationWhenNonKycAndRblCaActive(){
        $oldDateTime = Carbon::create(2021, 5, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYearForRblCaActivatedAndNonKyc();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];
        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $entities = $entities['items'];

        $this->assertEquals(10000000000000, $entities[0]['merchant_id']);
    }

    public function testBankingInvoiceDownloadFromAdminDashboard()
    {
        $this->markTestSkipped("failing intermittently on drone");

        $this->setupBankingInvoice();

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testBankingInvoiceWithFailedPayoutsInGivenMonthAndYear()
    {
        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createDataForBankingInvoiceWithFailedPayoutsInGivenMonthAndYear();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year,'merchant_ids' => ['10000000000000']],
        ];

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);
        $this->assertArraySelectiveEquals($invoiceEntities['rx_adjustments'], $data['rx_adjustments']);

        Carbon::setTestNow();
    }

    protected function setUpForMerchantInvoiceFetch()
    {
        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYear();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year,'merchant_ids' => ['10000000000000']],
        ];

        $this->makeRequestAndGetContent($request);
    }

    public function testMerchantInvoiceFetchFromAdminDashboardWhenMonthIsGivenWithYearAndMerchantId()
    {
        $this->setUpForMerchantInvoiceFetch();

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $request = [
            'url'     => '/admin/merchant_invoice',
            'method'  => 'GET',
            'content' => [
                'month'       => 7,
                'year'        => 2019,
                'merchant_id' => '10000000000000',
            ],
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        foreach ($content['items'] as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::MONTH       => $e[Invoice\Entity::MONTH],
                Invoice\Entity::YEAR        => $e[Invoice\Entity::YEAR],
                Invoice\Entity::MERCHANT_ID => $e[Invoice\Entity::MERCHANT_ID],
                Invoice\Entity::AMOUNT      => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX         => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        Carbon::setTestNow();
    }

    public function testBankingInvoiceWithFailedPayoutsInGivenMonthButInitiatedPreviousMonthAndNoPayoutsInGivenMonth()
    {
        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $payoutIds = $this->createDataForBankingInvoiceWithFailedPayoutsInGivenMonthAndYear();

        $oldDateTime = Carbon::create(2019, 8, 2, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->fixtures->edit('payout', $payoutIds[2], ['failed_at' => Carbon::now(Timezone::IST)->timestamp]);

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year,'merchant_ids' => ['10000000000000']],
        ];

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);
        $this->assertArraySelectiveEquals($invoiceEntities['rx_adjustments'], $data['rx_adjustments']);

        Carbon::setTestNow();
    }

    public function testMerchantInvoiceFetchFromAdminDashboardWhenMonthIsGivenWithoutYearButWithMerchantId()
    {
        $this->setUpForMerchantInvoiceFetch();

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testMerchantInvoiceFetchFromAdminDashboardWhenMonthIsGivenWithoutYearAndMerchantId()
    {
        $this->setUpForMerchantInvoiceFetch();

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testBankingInvoiceEmailFromAdminDashboard()
    {
        $this->markTestSkipped();

        $this->setupBankingInvoice();

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testMerchantInvoiceFetchFromAdminDashboardWhenYearIsGivenWithMerchantId()
    {
        $this->setUpForMerchantInvoiceFetch();

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $request = [
            'url'     => '/admin/merchant_invoice',
            'method'  => 'GET',
            'content' => [
                'year' => 2019,
                'merchant_id' => '10000000000000',
            ],
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        foreach ($content['items'] as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::YEAR        => $e[Invoice\Entity::YEAR],
                Invoice\Entity::MERCHANT_ID => $e[Invoice\Entity::MERCHANT_ID],
                Invoice\Entity::AMOUNT      => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX         => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        Carbon::setTestNow();
    }

    public function testMerchantInvoiceFetchFromAdminDashboardWhenYearIsGivenWithoutMerchantId()
    {
        $this->setUpForMerchantInvoiceFetch();

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testBulkCreate()
    {
        $this->ba->adminAuth();

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYear();

        $request = [
            'url'     => '/merchants/invoice/bulk',
            'method'  => 'POST',
            'content' => [
                'invoice_entities' => [
                    [
                        'merchant_id'   => '10000000000000',
                        'gstin'         => '29kjsngjk213900',
                        'amount'        => 50000,
                        'tax'           => 18,
                        'description'   => 'adding invoice for something from primary balance',
                        'month'         => 8,
                        'year'          => 2017,
                    ],
                    [
                        'merchant_id'   => '10000000000000',
                        'gstin'         => '29kjsngjk213900',
                        'amount'        => -51100,
                        'tax'           => 18,
                        'description'   => 'adding invoice for something from banking balance',
                        'month'         => 8,
                        'year'          => 2017,
                        'balance_id'    => $balanceId,
                    ],
                ],
                'force' => 1,
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(2, $entities['count']);

        $this->assertEquals(substr($entities['items'][0]['invoice_number'], -4), '0817');

        $this->assertEquals($entities['items'][0]['balance_id'], $balanceId);

        $this->assertEquals($entities['items'][1]['balance_id'], '10000000000000');
    }

    public function detachAdminPermission(string $permissionName)
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $permissionId = (new Permission\Repository)->retrieveIdsByNames([$permissionName])[0];

        $role->permissions()->detach($permissionId);
    }


    public function testFetchMerchantInvoiceWithoutPermissionFromAdminDashboard()
    {
        $this->setupBankingInvoice();

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->detachAdminPermission(Permission\Name::VIEW_MERCHANT_REPORT);

        $this->startTest();

        Carbon::setTestNow();
    }

    protected function createDataForPayoutFailedOrReversedScenarios()
    {
        $currentMonth = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($currentMonth);

        $this->fixtures->edit('merchant', '10000000000000', [
            'activated'    => 1,
            'activated_at' => Carbon::now(Timezone::IST)->timestamp,
            'invoice_code' => 'hello1234567',
        ]);

        $balance = $this->fixtures->create('balance',
            [
                'merchant_id' => '10000000000000',
                'type'        => 'banking',
                'balance'     => 1000000,
            ]);

        $balanceId = $balance['id'];

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'gstin'       => '29kjsngjk213922',
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $payout1 = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $balanceId,
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $payout2 = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $balanceId,
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $payout3 = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $balanceId,
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $payout4 = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $balanceId,
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $payout5 = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $balanceId,
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $payout6 = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $balanceId,
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $payout7 = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $balanceId,
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $payout8 = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $balanceId,
                'pricing_rule_id'   => '1nvp2XPMmaRLxb',
            ]);

        $this->fixtures->edit('payout', $payout2['id'], ['failed_at' => Carbon::now(Timezone::IST)->timestamp, 'status' => 'failed']);

        $this->fixtures->reversal->createPayoutReversal(
            [
                'merchant_id'   => '10000000000000',
                'entity_id'     => $payout4['id'],
                'entity_type'   => 'payout',
                'balance_id'    => $balanceId,
                'amount'        => 1000,
                'fee'           => 0,
                'tax'           => 0,
            ]);

        $this->fixtures->edit('payout', $payout6['id'], ['failed_at' => Carbon::now(Timezone::IST)->timestamp, 'status' => 'failed']);

        $this->fixtures->reversal->createPayoutReversal(
            [
                'merchant_id'   => '10000000000000',
                'entity_id'     => $payout6['id'],
                'entity_type'   => 'payout',
                'balance_id'    => $balanceId,
                'amount'        => 1000,
                'fee'           => 0,
                'tax'           => 0,
            ]);

        $this->fixtures->edit('payout', $payout8['id'], ['failed_at' => Carbon::now(Timezone::IST)->timestamp, 'status' => 'failed']);

        $nextMonth = Carbon::create(2019, 8, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($nextMonth);

        $this->fixtures->edit('payout', $payout3['id'], ['failed_at' => Carbon::now(Timezone::IST)->timestamp, 'status' => 'failed']);

        $this->fixtures->reversal->createPayoutReversal(
            [
                'merchant_id'   => '10000000000000',
                'entity_id'     => $payout5['id'],
                'entity_type'   => 'payout',
                'balance_id'    => $balanceId,
                'amount'        => 1000,
                'fee'           => 0,
                'tax'           => 0,
            ]);

        $this->fixtures->edit('payout', $payout7['id'], ['failed_at' => Carbon::now(Timezone::IST)->timestamp, 'status' => 'failed']);

        $this->fixtures->reversal->createPayoutReversal(
            [
                'merchant_id'   => '10000000000000',
                'entity_id'     => $payout7['id'],
                'entity_type'   => 'payout',
                'balance_id'    => $balanceId,
                'amount'        => 1000,
                'fee'           => 0,
                'tax'           => 0,
            ]);

        $this->fixtures->reversal->createPayoutReversal(
            [
                'merchant_id'   => '10000000000000',
                'entity_id'     => $payout8['id'],
                'entity_type'   => 'payout',
                'balance_id'    => $balanceId,
                'amount'        => 1000,
                'fee'           => 0,
                'tax'           => 0,
            ]);

        return $balanceId;
    }

    /**
     * Case1: No Failure/Reversal - M1: P1 +x
     * Case2: Only Failure, same month - M1: P1 0
     * Case3: Only Failure, next month - M1: P1 +x, M2: P1 -x
     * Case4: Only Reversal, same month - M1: P1 +x, M1: R1 -x
     * Case5: Only Reversal, next month - M1: P1 +x, M2: R1 -x
     * Case6: Failure/Reversal same month - M1: P1 0, M1: R1 0
     * Case7: Failure/Reversal next month - M1: P1 +x, M2: P1 -x, M2: R1 0
     * Case8: Failure M1/Reversal M2 - M1: P1 0, M2: R1 0
     */
    public function testMerchantInvoicePayoutFailedOrReversedScenarios()
    {
        $balanceId = $this->createDataForPayoutFailedOrReversedScenarios();

        Carbon::setTestNow();

        $this->ba->cronAuth();

        $firstMonth = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        $request1 = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $firstMonth->month, 'year' => $firstMonth->year,'merchant_ids' => ['10000000000000']],
        ];

        $request2 = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $firstMonth->month + 1, 'year' => $firstMonth->year],
        ];

        $this->makeRequestAndGetContent($request1);

        $this->makeRequestAndGetContent($request2);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]][] = [
                Invoice\Entity::MERCHANT_ID => $e[Invoice\Entity::MERCHANT_ID],
                Invoice\Entity::BALANCE_ID  => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT      => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX         => $e[Invoice\Entity::TAX],
            ];
        }

        $data = $this->testData[__FUNCTION__];

        $data['rx_transactions'][0]['balance_id'] = $balanceId;

        $data['rx_transactions'][1]['balance_id'] = $balanceId;

        $data['rx_adjustments'][0]['balance_id'] = $balanceId;

        $data['rx_adjustments'][1]['balance_id'] = $balanceId;

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

        $this->assertArraySelectiveEquals($invoiceEntities['rx_adjustments'], $data['rx_adjustments']);

        Carbon::setTestNow();
    }

    private function assertValuesInInvoiceEInvoiceAndInput(array $invoiceEntity, array $eInvoiceEntity, array $input)
    {
        $invoiceEntities = [];

        foreach ($invoiceEntity as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::BALANCE_ID => $e[Invoice\Entity::BALANCE_ID],
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }


        $this->assertArraySelectiveEquals($input,$invoiceEntities['rx_transactions']);

        $this->assertEquals('10000000000000', $eInvoiceEntity['merchant_id']);
        $this->assertEquals(4, $eInvoiceEntity['month']);
        $this->assertEquals(2022, $eInvoiceEntity['year']);
        $this->assertEquals('BANKING', $eInvoiceEntity['type']);
        $this->assertEquals('generated', $eInvoiceEntity['status']);
        $this->assertEquals('randomirn', $eInvoiceEntity['gsp_irn']);
        $this->assertEquals('randominvoice', $eInvoiceEntity['gsp_signed_invoice']);
        $this->assertEquals('randomcode', $eInvoiceEntity['gsp_signed_qr_code']);
        $this->assertEquals('randomurl', $eInvoiceEntity['gsp_qr_code_url']);
        $this->assertEquals('randompdf', $eInvoiceEntity['gsp_e_invoice_pdf']);
    }

    // mocks PDF and UFH service
    protected function mockPdfGeneratorAndUfhService($expectedData)
    {
        $tempFile = fopen("testFile.txt", "w");
        fclose($tempFile);

        $pdfMock = \Mockery::mock('RZP\Models\Invoice\PdfGenerator')->makePartial();
        $this->app->instance('invoice_pdf_generator', $pdfMock);
        
        if (empty($expectedData))
        {
            $pdfMock->shouldReceive('generateBankingInvoice')->andReturn('testFile.txt');
        }
        else
        {
            $pdfMock->shouldReceive('generateBankingInvoice')
                ->withArgs(function($data) use ($expectedData) {
                    $this->assertArraySelectiveEquals($expectedData, $data);
                    return true;
                })->andReturn('testFile.txt');
        }      

        $ufhService = \Mockery::mock('RZP\Services\UfhService')->makePartial();
        $this->app->instance('ufh.service', $ufhService);
        $ufhService->shouldReceive('uploadFileAndGetUrl')->times(1)->andReturn([
            'file_id'   => 'file_MQgsR8C9eceTxZ',
        ]);
    }
}
