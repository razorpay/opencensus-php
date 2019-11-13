<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Merchant\Invoice;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;


class MerchantBankingInvoiceTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantBankingInvoiceTestData.php';

        parent::setUp();

        $this->ba->publicAuth();
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
                                     ]);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'gstin'       => '29kjsngjk213922',
            ]);

        $this->fixtures->edit('merchant', 10000000000000, ['business_banking' => 1]);

        $y = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $x['id'],
            ]);

        return $x['id'];
    }

    //Basic Banking Invoice Test
    public function testBankingInvoiceEntityCreateForGivenMonthYear()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYear();

        Carbon::setTestNow();

        $this->ba->appAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $entities = $this->getEntities('merchant_invoice', [], true);

        //$this->assertEquals(8, $entities['count']);

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
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000000,
                'balance_id' => $y['id'],
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $z['id'],
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

        $this->ba->appAuth();

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
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000000,
                'balance_id' => $y['id'],
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $z['id'],
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'     => 'icici',
                'amount'      => 1000,
                'balance_id'  => $a['id'],
                'merchant_id' => '100000Razorpay',
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $b['id'],
                'merchant_id' => '100000Razorpay',
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

        $this->ba->appAuth();

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

        $this->ba->appAuth();

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
            ]);

        $q = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000,
                'balance_id' => $y['id'],
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

        $w = $w->toArray();

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $z['id'],
            ]);

        return [ $x['id'] , $y['id'] , $z['id'] , $w['id'] ];
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

        $this->ba->appAuth();

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

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

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
            ]);

        $q = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000000,
                'balance_id' => $y['id'],
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $z['id'],
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

        $this->ba->appAuth();

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

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

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
            ]);

        $q = $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000000,
                'balance_id' => $y['id'],
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $z['id'],
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

        $this->ba->appAuth();

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

        $this->assertArraySelectiveEquals($invoiceEntities['rx_transactions'], $data['rx_transactions']);

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
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 1000000,
                'balance_id' => $bankingBalance2['id'],
            ]);

        $this->fixtures->create(
            'payout',
            [
                'channel'    => 'icici',
                'amount'     => 100000,
                'balance_id' => $primaryBalance['id'],
            ]);
    }

    public function testFetchMultipleBankingInvoices()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createDataForFetchingBankingInvoices();

        $this->ba->appAuth();

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

    public function testFetchMultipleBankingInvoicesGivenAccountNumber()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createDataForFetchingBankingInvoices();

        $this->ba->appAuth();

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

        $this->ba->appAuth();

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

        $this->ba->appAuth();

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
        $oldDateTime = Carbon::create(2019, 07, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $balanceId = $this->createDataForBankingInvoiceEntityCreateForGivenMonthYear();

        $this->ba->appAuth();

        $request = [
            'url'     => '/merchants/invoice/create',
            'method'  => 'POST',
            'content' => ['month' => $oldDateTime->month, 'year' => $oldDateTime->year,'merchant_ids' => ['10000000000000']],
        ];

        $content = $this->makeRequestAndGetContent($request);

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

        $this->assertEquals('10000000000-' . '07' . substr($oldDateTime->year,2,2),
                            $invoiceEntity['invoice_number']);

        Carbon::setTestNow();
    }
}
