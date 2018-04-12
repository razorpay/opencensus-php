<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Merchant\Invoice;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class MerchantInvoiceTest extends TestCase
{
    use PaymentTrait;
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantInvoiceTestData.php';

        parent::setUp();

        $this->ba->publicAuth();
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
                        'tax'           => 400,
                        'description'   => 'adding invoice for something',
                        'month'         => 8,
                        'year'          => 2017,
                    ],
                    [
                        'merchant_id'   => '10000000000000',
                        'gstin'         => '29kjsngjk213900',
                        'amount'        => -51100,
                        'tax'           => -600,
                        'description'   => 'adding invoice for something',
                        'month'         => 8,
                        'year'          => 2017,
                    ],
                ]
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(2, $entities['count']);

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

        $entities = $this->getEntities('merchant_invoice', ['merchant_id' => '10000000000000'], true);

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

        $this->ba->appAuth();

        $currentTime = $oldDateTime = Carbon::create(2018, 2, 1, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($currentTime);

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(3, $entities['count']);

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

        $this->ba->appAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        // checking for 3 because other merchants are inactive during this $oldDateTime
        $this->assertEquals(3, $entities['count']);

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

        $this->ba->appAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
        ];

        $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(3, $entities['count']);

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

        $this->ba->appAuth();

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
        $this->assertEquals(3, $entities['count']);

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

        Carbon::setTestNow();
    }

    protected function createData()
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
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel' => 'icici',
                'amount' => 1000,
            ]);

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

        // NB payment
        $this->fixtures->create('terminal:shared_netbanking_indusind_terminal');

        $p3 = $this->getDefaultNetbankingPaymentArray('INDB');

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
}
