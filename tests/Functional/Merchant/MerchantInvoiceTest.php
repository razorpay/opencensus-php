<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;

use Mockery;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Invoice;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountValidationTrait;

class MerchantInvoiceTest extends TestCase
{
    use AttemptTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use AttemptReconcileTrait;
    use FundAccountValidationTrait;

    protected $eInvoiceClientMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantInvoiceTestData.php';

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

    public function testBulkCreate()
    {
        $this->ba->adminAuth();

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
                        'description'   => 'adding invoice for something',
                        'month'         => 8,
                        'year'          => 2017,
                    ],
                    [
                        'merchant_id'   => '10000000000000',
                        'gstin'         => '29kjsngjk213900',
                        'amount'        => -51100,
                        'tax'           => 18,
                        'description'   => 'adding invoice for something',
                        'month'         => 8,
                        'year'          => 2017,
                    ],
                ],
                'force' => 1,
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(2, $entities['count']);

        $this->assertEquals(substr($entities['items'][0]['invoice_number'], -4), '0817');

        $this->assertEquals('-9198', $entities['items'][0]['tax']);

        $this->assertEquals('9000', $entities['items'][1]['tax']);
    }

    public function testBulkCreateWithForceFlagFalse()
    {
        $this->ba->adminAuth();

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
                        'description'   => 'adding invoice for something',
                        'month'         => 8,
                        'year'          => 2017,
                    ],
                    [
                        'merchant_id'   => '10000000000000',
                        'gstin'         => '29kjsngjk213900',
                        'amount'        => -51100,
                        'tax'           => 0,
                        'description'   => 'adding invoice for something',
                        'month'         => 8,
                        'year'          => 2017,
                    ],
                ],
                'force' => 0,
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(1, $entities['count']);

        $this->assertEquals(substr($entities['items'][0]['invoice_number'], -4), '0817');
    }

    public function testEditGstin()
    {
        $md1 = $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'   => '10000000000000',
                'gstin'         => '29kjsngjk213900',
            ]);

        $invoiceNumber = '100820171111';

        $this->fixtures->create('merchant_invoice',
            [
                Invoice\Entity::TYPE => Invoice\Type::CARD_LTE_2K,
                Invoice\Entity::INVOICE_NUMBER => $invoiceNumber
            ]);

        $this->fixtures->create('merchant_invoice',
            [
                Invoice\Entity::TYPE => Invoice\Type::CARD_GT_2K,
                Invoice\Entity::INVOICE_NUMBER => $invoiceNumber
            ]);

        $this->ba->adminAuth();

        $request = [
            'url'     => '/merchants/10000000000000/invoice/gstin',
            'method'  => 'PUT',
            'content' => ['invoice_number' => $invoiceNumber],
        ];

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        foreach ($entities['items'] as $entity)
        {
            $this->assertEquals('29kjsngjk213900', $entity[Invoice\Entity::GSTIN]);
        }
    }

    public function testEditGstinFailure()
    {
        $md1 = $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'   => '10000000000000',
                'gstin'         => '29kjsngjk213900',
            ]);

        $invoiceNumber = '100820171111';

        $this->fixtures->create('merchant_invoice',
            [
                Invoice\Entity::TYPE => Invoice\Type::CARD_LTE_2K,
                Invoice\Entity::INVOICE_NUMBER => $invoiceNumber
            ]);

        $this->ba->adminAuth();

        $request = [
            'url'     => '/merchants/10000000000000/invoice/gstin',
            'method'  => 'PUT',
            'content' => ['invoice_number' => '1234'],
        ];

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($request)
        {
            $this->makeRequestAndGetContent($request);
        });
    }

    public function testInvoiceEntityCreateForPrevMonth()
    {
        $oldDateTime = Carbon::create(2018, 1, 27, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createData();

        Carbon::setTestNow();

        $this->ba->cronAuth();

        $currentTime = $oldDateTime = Carbon::create(2018, 2, 1, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($currentTime);

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(6, $entities['count']);

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

        $this->assertArraySelectiveEquals($invoiceEntities['others'], $data['others']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_gt_2k'], $data['card_gt_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_lte_2k'], $data['card_lte_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['validation'], $data['validation']);
        $this->assertArraySelectiveEquals($invoiceEntities['instant_refunds'], $data['instant_refunds']);
        $this->assertArraySelectiveEquals($invoiceEntities['pricing_bundle'], $data['pricing_bundle']);

        $dateString = Carbon::createFromDate(
            $entities[0]['year'],
            $entities[0]['month'],
            1,
            Timezone::IST
        )->format('my');

        $this->assertEquals(substr($entities[0]['invoice_number'], -4), $dateString);

        Carbon::setTestNow();
    }

    public function testInvoiceEntityCreateForGivenMonthYear()
    {
        $oldDateTime = Carbon::create(2017, 8, 27, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createData();

        Carbon::setTestNow();

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        // checking for 3 because other merchants are inactive during this $oldDateTime
        $this->assertEquals(6, $entities['count']);

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

        $this->assertArraySelectiveEquals($invoiceEntities['others'], $data['others']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_gt_2k'], $data['card_gt_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_lte_2k'], $data['card_lte_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['validation'], $data['validation']);
        $this->assertArraySelectiveEquals($invoiceEntities['instant_refunds'], $data['instant_refunds']);
        $this->assertArraySelectiveEquals($invoiceEntities['pricing_bundle'], $data['pricing_bundle']);

        Carbon::setTestNow();
    }

    public function testMerchantInvoiceWithLateAuth()
    {
        $knownDate = Carbon::create(2018, 1, 27, 12,0, 0, Timezone::IST);

        Carbon::setTestNow($knownDate);

        $authPayment = $this->createData();

        $knownDate = Carbon::create(2018, 2, 1, 12,0, 0, Timezone::IST);

        Carbon::setTestNow($knownDate);

        $this->capturePayment(
            $authPayment['id'],
            $authPayment['amount'], 'INR', $authPayment['amount']);

        $this->fixtures->edit('payment', $authPayment['id'], [
            'captured_at' => Carbon::create(2018, 2, 1, 6, 0, 0, Timezone::IST)->timestamp
        ]);

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
        ];

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(6, $entities['count']);

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

        $this->assertArraySelectiveEquals($invoiceEntities['others'], $data['others']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_gt_2k'], $data['card_gt_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_lte_2k'], $data['card_lte_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['validation'], $data['validation']);
        $this->assertArraySelectiveEquals($invoiceEntities['instant_refunds'], $data['instant_refunds']);
        $this->assertArraySelectiveEquals($invoiceEntities['pricing_bundle'], $data['pricing_bundle']);

        $dateString = Carbon::createFromDate(
            $entities[0]['year'],
            $entities[0]['month'],
            1,
            Timezone::IST)->format('my');

        $this->assertEquals(substr($entities[0]['invoice_number'], -4), $dateString);

        Carbon::setTestNow();
    }

    public function testFeeAdjustment()
    {
        $md1 = $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'gstin' => '29kjsngjk213922',
            ]);

        $adjustmentData =[
            'merchant_id'   => '10000000000000',
            'fees'          => -1300,
            'tax'           => 123,
            'currency'      => 'INR',
            'description'   => 'Fee adjustment',
        ];

        $request = [
            'method'    => 'POST',
            'url'       => '/adjustments',
            'content'   => $adjustmentData
        ];

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->setAdminForInternalAuth();

        $this->ba->addAdminAuthHeaders('org_'.$this->org->id, $this->authToken);

        $content = $this->makeRequestAndGetContent($request);

        $this->ba->addAdminAuthHeaders(null, null);

        // Check adjustment entity
        $data = $this->getLastEntity('adjustment', true);

        $this->assertArraySelectiveEquals($content, $data);

        // Check invoice entity
        $merchantInvoice = $this->getLastEntity('merchant_invoice', true);

        $this->assertTestResponse($merchantInvoice);

        $dateString = Carbon::createFromDate(
            $merchantInvoice['year'],
            $merchantInvoice['month'],
            1,
            Timezone::IST
        )->format('my');

        $this->assertEquals(substr($merchantInvoice[Invoice\Entity::INVOICE_NUMBER], -4), $dateString);
    }

    public function testFeeAdjustmentFailure()
    {
        $md1 = $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'gstin' => '29kjsngjk213922',
            ]);

        $adjustmentData =[
            'merchant_id'   => '10000000000000',
            'fees'          => -1300,
            'tax'           => 123,
            'amount'        => 1200,
            'currency'      => 'INR',
            'description'   => 'Fee adjustment',
        ];

        $request = [
            'method'    => 'POST',
            'url'       => '/adjustments',
            'content'   => $adjustmentData
        ];

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($request)
        {
            $this->makeRequestAndGetContent($request);
        });

    }

    public function testInvoiceEntityCreateForGivenMerchant()
    {
        $oldDateTime = Carbon::create(2017, 7, 27, 12, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createData();

        // Card payment greater than 2k
        // Created in last month captured in next month
        $p4 = $this->getDefaultPaymentArray();

        $p4['amount'] = 234000;

        $p4 = $this->doAuthAndCapturePayment($p4);

        $this->fixtures->edit('payment', $p4['id'], [
            'captured_at' => Carbon::create(2017, 8, 1, 2, 0, 0, 0, Timezone::IST)->timestamp
        ]);

        Carbon::setTestNow();

        $this->ba->cronAuth();

        $currentTime = $oldDateTime = Carbon::create(2017, 8, 1, 12, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($currentTime);

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['merchant_ids' => ['10000000000000']],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        // checking for 3 because invoice are generated only for one merchant
        $this->assertEquals(6, $entities['count']);

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

        $this->assertArraySelectiveEquals($invoiceEntities['others'], $data['others']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_gt_2k'], $data['card_gt_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_lte_2k'], $data['card_lte_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['validation'], $data['validation']);
        $this->assertArraySelectiveEquals($invoiceEntities['instant_refunds'], $data['instant_refunds']);
        $this->assertArraySelectiveEquals($invoiceEntities['pricing_bundle'], $data['pricing_bundle']);

        Carbon::setTestNow();
    }

    public function testInstantRefundsInvoiceEntityCreateForGivenMerchant()
    {
        $oldDateTime = Carbon::create(2017, 7, 27, 12, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createData();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === RazorxTreatment::MERCHANTS_REFUND_CREATE_V_1_1 or $feature === 'store_empty_value_for_non_exempted_card_metadata')
                    {
                        return 'off';
                    }

                    if ($feature === RazorxTreatment::STORE_EMPTY_VALUE_FOR_NON_EXEMPTED_CARD_METADATA)
                    {
                        return 'off';
                    }

                    return 'control';
                }));

        // Card payment greater than 2k
        // Created in last month captured in next month
        $p4 = $this->getDefaultPaymentArray();

        $p4['amount'] = 234000;

        $p4 = $this->doAuthAndCapturePayment($p4);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($p4['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->fixtures->edit('payment', $p4['id'], [
            'captured_at' => Carbon::create(2017, 8, 1, 2, 0, 0, 0, Timezone::IST)->timestamp
        ]);

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        // Adding specific amount to refund - this is meant to test successful instant refunds on scrooge -
        $this->refundPayment($p4['id'], 3471, ['speed' => 'optimum', 'is_fta' => true]);

        // This is a failed instant refund in which case the fee is reversed -
        // added two refunds to check that invoice has only one refund's fee calculated
        $this->refundPayment($p4['id'], 3470, ['speed' => 'optimum', 'is_fta' => true]);

        Carbon::setTestNow();

        $this->ba->cronAuth();

        $currentTime = $oldDateTime = Carbon::create(2017, 8, 1, 12, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($currentTime);

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['merchant_ids' => ['10000000000000']],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        // checking for 3 because invoice are generated only for one merchant
        $this->assertEquals(6, $entities['count']);

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

        $this->assertArraySelectiveEquals($invoiceEntities['others'], $data['others']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_gt_2k'], $data['card_gt_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_lte_2k'], $data['card_lte_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['validation'], $data['validation']);
        $this->assertArraySelectiveEquals($invoiceEntities['instant_refunds'], $data['instant_refunds']);
        $this->assertArraySelectiveEquals($invoiceEntities['pricing_bundle'], $data['pricing_bundle']);

        Carbon::setTestNow();
    }

    public function testInvoicePdfBackfill()
    {
        $oldDateTime = Carbon::create(2018, 1, 27, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createData();

        $oldDateTime = Carbon::create(2018, 2, 27, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createData(true);

        Carbon::setTestNow();

        $this->ba->cronAuth();

        $currentTime = $oldDateTime = Carbon::create(2018, 2, 1, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($currentTime);

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $currentTime = $oldDateTime = Carbon::create(2018, 3, 1, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($currentTime);

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(12, $entities['count']);

        $entities = $entities['items'];

        $amount = 0;

        $tax = 0;

        foreach ($entities as $e)
        {
            $amount  += $e[Invoice\Entity::AMOUNT];
            $tax     += $e[Invoice\Entity::TAX];
        }

        //this is to verify the invoice created are of non zero amount
        $this->assertNotEquals(0, $amount);
        $this->assertNotEquals(0, $tax);

        $result =  $this->merchantInvoicePdfControlBackfill(
            'backfill',
            ['10000000000000'],
            1,
            2018,
            2,
            2018,
            'generating bulk pdf'
        );

        $expectedResponse = [
            '1-2018' =>  [
                'success_count' => 1,
                'failed_count'  => 0,
            ],
            '2-2018' => [
                'success_count' =>  1,
                'failed_count'  => 0,
            ]
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $result);

        Carbon::setTestNow();
    }

    public function testInvoicePdfCreate()
    {
        // currently skipping the test case to find the issue will fix and reenable this
        $this->markTestSkipped("settlements team will fix this test case");

        $oldDateTime = Carbon::create(2018, 1, 27, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createData();

        Carbon::setTestNow();

        $this->ba->cronAuth();

        $currentTime = $oldDateTime = Carbon::create(2018, 2, 1, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($currentTime);

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(5, $entities['count']);

        $entities = $entities['items'];

        $amount = 0;

        $tax = 0;

        foreach ($entities as $e)
        {
            $amount  += $e[Invoice\Entity::AMOUNT];
            $tax     += $e[Invoice\Entity::TAX];
        }

        //this is to verify the invoice created are of non zero amount
        $this->assertNotEquals(0, $amount);
        $this->assertNotEquals(0, $tax);

        $result =  $this->merchantInvoicePdfControl(
            'create',
            ['10000000000000'],
            1,
            2018,
            'creating new pdf'
        );

        // this is because in the merchant invoice creation flow the pdf is already created
        $this->assertEquals('10000000000000', $result['failed_mids'][0]);
        $this->assertEmpty($result['success_mids']);

        $result = $this->merchantInvoicePdfControl(
            'delete',
            ['10000000000000'],
            1,
            2018,
            'deleting old pdf'
        );

        $this->assertEmpty($result['failed_mids']);
        $this->assertEquals('10000000000000', $result['success_mids'][0]);
        $file = $this->getLastEntity('file_store', true);
        $this->assertEmpty($file);

        $result =  $this->merchantInvoicePdfControl(
            'create',
            ['10000000000000'],
            1,
            2018,
            'creating new pdf'
        );

        $this->assertEmpty($result['failed_mids']);
        $this->assertEquals('10000000000000', $result['success_mids'][0]);

        $file = $this->getLastEntity('file_store', true);

        $this->assertEquals('10000000000000', $file['merchant_id']);
        $this->assertEquals('merchant_pg_invoices/2018/1/10000000000000', $file['name']);

        Carbon::setTestNow();
    }

    public function testMerchantInvoiceSkippedListEdit()
    {
        $oldDateTime = Carbon::create(2018, 1, 27, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createData();

        Carbon::setTestNow();

        $currentTime = $oldDateTime = Carbon::create(2018, 2, 1, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($currentTime);

        $result =  $this->merchantInvoiceControl(
            'add',
            'adding to skip automatic merchant invoice creation',
            ['10000000000000']
        );

        $this->assertEmpty($result['failed_mids']);
        $this->assertEquals('10000000000000', $result['success_mids'][0]);

        $result = $this->merchantInvoiceControl('show');
        $this->assertEquals('10000000000000', $result[0]);

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
        ];

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(0, $entities['count']);

        $result =  $this->merchantInvoiceControl(
            'remove',
            'removing from automatic merchant invoice creation list',
            ['10000000000000']
        );

        $this->assertEmpty($result['failed_mids']);
        $this->assertEquals('10000000000000', $result['success_mids'][0]);

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
        ];

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(6, $entities['count']);

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

        $this->assertArraySelectiveEquals($invoiceEntities['others'], $data['others']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_gt_2k'], $data['card_gt_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_lte_2k'], $data['card_lte_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['validation'], $data['validation']);
        $this->assertArraySelectiveEquals($invoiceEntities['instant_refunds'], $data['instant_refunds']);

        $dateString = Carbon::createFromDate(
            $entities[0]['year'],
            $entities[0]['month'],
            1,
            Timezone::IST
        )->format('my');

        $this->assertEquals(substr($entities[0]['invoice_number'], -4), $dateString);

        Carbon::setTestNow();
    }

    protected function createData($merchantAlreadyCreated = false)
    {
        if($merchantAlreadyCreated === false)
        {
            $this->fixtures->edit('merchant', '10000000000000', [
                'activated' => 1,
                'activated_at' => Carbon::now(Timezone::IST)->timestamp,
                'invoice_code' => 'hello1234567',
            ]);

            $this->fixtures->on('live')->create('methods:default_methods', [
                'merchant_id' => '1cXSLlUU8V9sXl'
            ]);

            $this->fixtures->create(
                'merchant_detail',
                [
                    'merchant_id' => '10000000000000',
                    'gstin' => '29kjsngjk213922',
                    'business_registered_pin' => '123456',
                    'business_registered_address'   => 'FILM CENTRE BUILDING, MUMBAI, 68, TARDEO ROAD, 2B ii, Mumbai City, Maharashtra, GROUND FLOOR, 400034,',
                    'business_registered_city'      => 'abcdef',
                    'business_name'                 => 'abcd',
                ]);

            $this->fixtures->create(
                'payout',
                [
                    'channel'           => 'icici',
                    'amount'            => 1000,
                    'pricing_rule_id'   => '1nvp2XPMmaRLxb',
                ]);

            $this->ba->privateAuth();

            $this->createValidationWithFundAccountEntity();

            // NB payment
            $this->fixtures->create('terminal:shared_netbanking_pnb_terminal');
        }

        // Card payment less than 2k
        $p1  = $this->getDefaultPaymentArray();

        $p1['amount'] = 50000;

        $p1 = $this->doAuthAndCapturePayment();

        $this->fixtures->edit('payment', $p1['id'], [
            'captured_at' => Carbon::now(Timezone::IST)->timestamp + 5,
        ]);

        // Card payment greater than 2k
        $p2 = $this->getDefaultPaymentArray();

        $p2['amount'] = 234000;

        $p2 = $this->doAuthAndCapturePayment($p2);

        $this->fixtures->edit('payment', $p2['id'], [
            'captured_at' => Carbon::now(Timezone::IST)->timestamp + 5,
        ]);

        $p3 = $this->getDefaultNetbankingPaymentArray('PUNB_R');

        $p3['amount'] = 40000;

        $p3 = $this->doAuthAndCapturePayment($p3);

        $this->fixtures->edit('payment', $p3['id'], [
            'captured_at' => Carbon::now(Timezone::IST)->timestamp + 5,
        ]);

        // Creating authorized transaction which shouldn't be part of invoice as its not captured
        return $this->doAuthAndGetPayment();
    }

    protected function setAdminForInternalAuth()
    {
        $this->org = $this->fixtures->create('org');

        $this->authToken = $this->getAuthTokenForOrg($this->org);
    }

    protected function merchantInvoicePdfControlBackfill($action, $merchantIds, $fromMonth,
                                                         $fromYear, $toMonth, $toYear, $reason)
    {
        $request = [
            'url'     => '/merchants/invoice/pdf_control',
            'method'  => 'POST',
            'content' => [
                'action'       => $action,
                'merchant_ids' => $merchantIds,
                'from_month'   => $fromMonth,
                'from_year'    => $fromYear,
                'to_month'     => $toMonth,
                'to_year'      => $toYear,
                'reason'       => $reason,
            ]
        ];

        return  $this->makeRequestAndGetContent($request);
    }

    protected function merchantInvoicePdfControl($action, $merchantIds, $month, $year, $reason)
    {
        $this->ba->adminAuth();

        $request = [
            'url'     => '/merchants/invoice/pdf_control',
            'method'  => 'POST',
            'content' => [
                'action'       => $action,
                'merchant_ids' => $merchantIds,
                'month'        => $month,
                'year'         => $year,
                'reason'       => $reason,
            ]
        ];

        return  $this->makeRequestAndGetContent($request);
    }

    protected function merchantInvoiceControl($action, $reason = null, $merchantIds = [])
    {
        $this->ba->adminAuth();

        $content = [
            'action'       => $action,
        ];

        if(empty($reason) == false)
        {
            $content['reason'] = $reason;
        }

        if(empty($merchantIds) == false)
        {
            $content['merchant_ids'] = $merchantIds;
        }

        $request = [
            'url'     => '/merchants/invoice/control',
            'method'  => 'POST',
            'content' => $content,
        ];

       return  $this->makeRequestAndGetContent($request);
    }

    public function testPgInvoiceEntityCreateWithEInvoice()
    {
        $oldDateTime = Carbon::create(2021, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createData();

        $this->createCreditNoteDataForEinvoice();

        $this->createDebitNoteDataForEinvoice();

        $this->setupEInvoiceClientResponse(3, $this->testData[__FUNCTION__]);

        $this->ba->cronAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(8, $entities['count']);

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Invoice\Entity::TYPE]] = [
                Invoice\Entity::AMOUNT  => $e[Invoice\Entity::AMOUNT],
                Invoice\Entity::TAX     => $e[Invoice\Entity::TAX],
            ];
        }

        $eInvoiceEntities = $this->getEntities('merchant_e_invoice', [], true);

        foreach ($eInvoiceEntities['items'] as $eInvoiceEntity)
        {
            $this->assertDocumentTypeEinvoiceSuccess($eInvoiceEntity);
        }

        Carbon::setTestNow();
    }

    protected function assertDocumentTypeEinvoiceSuccess($eInvoiceEntity)
    {
        $this->assertEquals('10000000000000', $eInvoiceEntity['merchant_id']);
        $this->assertEquals(7, $eInvoiceEntity['month']);
        $this->assertEquals(2021, $eInvoiceEntity['year']);
        $this->assertEquals('PG', $eInvoiceEntity['type']);
        $this->assertEquals('generated', $eInvoiceEntity['status']);
        $this->assertEquals('randomirn', $eInvoiceEntity['gsp_irn']);
        $this->assertEquals('randominvoice', $eInvoiceEntity['gsp_signed_invoice']);
        $this->assertEquals('randomcode', $eInvoiceEntity['gsp_signed_qr_code']);
        $this->assertEquals('randomurl', $eInvoiceEntity['gsp_qr_code_url']);
        $this->assertEquals('randompdf', $eInvoiceEntity['gsp_e_invoice_pdf']);
    }

    protected function createCreditNoteDataForEinvoice()
    {
        $adjustmentData =[
            'merchant_id'   => '10000000000000',
            'fees'          => 1300,
            'tax'           => 0,
            'currency'      => 'INR',
            'description'   => 'Fee adjustment',
        ];

        $request = [
            'method'    => 'POST',
            'url'       => '/adjustments',
            'content'   => $adjustmentData
        ];

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->setAdminForInternalAuth();

        $this->ba->addAdminAuthHeaders('org_'.$this->org->id, $this->authToken);

        $content = $this->makeRequestAndGetContent($request);

        $this->ba->addAdminAuthHeaders(null, null);

        $data = $this->getLastEntity('adjustment', true);

        $this->assertArraySelectiveEquals($content, $data);
    }

    protected function createDebitNoteDataForEinvoice()
    {
        $adjustmentData =[
            'merchant_id'   => '10000000000000',
            'fees'          => -1300,
            'tax'           => 0,
            'currency'      => 'INR',
            'description'   => 'Fee adjustment',
        ];

        $request = [
            'method'    => 'POST',
            'url'       => '/adjustments',
            'content'   => $adjustmentData
        ];

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->setAdminForInternalAuth();

        $this->ba->addAdminAuthHeaders('org_'.$this->org->id, $this->authToken);

        $content = $this->makeRequestAndGetContent($request);

        $this->ba->addAdminAuthHeaders(null, null);

        $data = $this->getLastEntity('adjustment', true);

        $this->assertArraySelectiveEquals($content, $data);
    }

    public function setupEInvoiceClientResponse(int $documentCount, $expectedContent)
    {
        $this->eInvoiceClientMock
            ->shouldReceive('getEInvoice')
            ->times($documentCount)
            ->with(Mockery::on(function (string $mode)  use ($expectedContent)
            {
                if($mode !== Mode::TEST)
                {
                    return false;
                }

                return true;

            }), Mockery::on(function(array $input) use ($expectedContent)
                {
                    $documentType = $input['document_details']['document_type'];

                    $this->assertArraySelectiveEquals($input, $expectedContent[$documentType]);

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

}
