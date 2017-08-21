<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Merchant\Invoice\Entity;

class MerchantInvoiceTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantInvoiceTestData.php';

        parent::setUp();

        $this->ba->publicAuth();
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

        $this->assertEquals(3, $entities['count']);

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Entity::TYPE]] = [Entity::AMOUNT => $e[Entity::AMOUNT], Entity::TAX => $e[Entity::TAX]];
        }

        $data = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($invoiceEntities['non_card'], $data['non_card']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_gt_2k'], $data['card_gt_2k']);
        $this->assertArraySelectiveEquals($invoiceEntities['card_lte_2k'], $data['card_lte_2k']);

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

        $this->assertEquals(3, $entities['count']);

        $entities = $entities['items'];

        $invoiceEntities = [];

        foreach ($entities as $e)
        {
            $invoiceEntities[$e[Entity::TYPE]] = [Entity::AMOUNT => $e[Entity::AMOUNT], Entity::TAX => $e[Entity::TAX]];
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

}