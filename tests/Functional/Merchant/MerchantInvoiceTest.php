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
        $this->createData();

        $this->ba->appAuth();

        $currentTime = Carbon::today(Timezone::IST)->addMonth();

        Carbon::setTestNow($currentTime);

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(15, $entities['count']);

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

        $this->assertArraySelectiveEquals($invoiceEntities['non_card'], $data['non_card']);
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
        $this->createData();

        $this->ba->appAuth();

        $currentTime = Carbon::today(Timezone::IST);

        $futureTime = Carbon::today(Timezone::IST)->addMonths(3);

        Carbon::setTestNow($currentTime);

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $currentTime->month, 'year' => $currentTime->year],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(15, $entities['count']);

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

        $this->assertArraySelectiveEquals($invoiceEntities['non_card'], $data['non_card']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_gt_2k'], $data['card_gt_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_lte_2k'], $data['card_lte_2k']);

        Carbon::setTestNow();
    }

    public function testInvoiceEntityCreateForGivenMerchant()
    {
        $this->createData();

        $this->ba->appAuth();

        $currentTime = Carbon::today(Timezone::IST)->addMonth();

        Carbon::setTestNow($currentTime);

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['merchant_ids' => ['10000000000000']],
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

        $this->assertArraySelectiveEquals($invoiceEntities['non_card'], $data['non_card']);
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

        $md1 = $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'gstin' => '29kjsngjk213922',
            ]);

        // Card payment less than 2k
        $p1  = $this->getDefaultPaymentArray();

        $p1['amount'] = 50000;

        $p1 = $this->doAuthAndCapturePayment();

        // Card payment greater than 2k
        $p2 = $this->getDefaultPaymentArray();

        $p2['amount'] = 234000;

        $p2 = $this->doAuthAndCapturePayment($p2);

        // NB payment
        $this->fixtures->create('terminal:shared_netbanking_indusind_terminal');

        $p3 = $this->getDefaultNetbankingPaymentArray('INDB');

        $p3['amount'] = 40000;

        $p3 = $this->doAuthAndCapturePayment($p3);
    }

    protected function setAdminForInternalAuth()
    {
        $this->org = $this->fixtures->create('org');

        $this->authToken = $this->getAuthTokenForOrg($this->org);
    }

}
