<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Invoice;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class EntityReportTest extends TestCase
{
    use PaymentTrait;

    public function __construct()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/EntityReportTestData.php';

        parent::__construct();
    }

    public function testEntityReports()
    {
        $this->doAuthAndCapturePayment();
        $this->doAuthCaptureAndRefundPayment();

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year' => $dt->year,
            'month' => $dt->month,
            'day' => $dt->day
        ];

        $paymentReport = $this->fetchReport('payment', $input);
        $refundReport =  $this->fetchReport('refund', $input);
        $combinedReport = $this->fetchReport('transaction', $input);

        assert(count($paymentReport) === 2);
        assert(count($refundReport) === 1);
        assert(count($combinedReport) === 3);
    }

    public function testTransactionReport()
    {
        $this->doAuthAndCapturePayment();
        $this->doAuthCaptureAndRefundPayment();

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year'  => $dt->year,
            'month' => $dt->month,
            'day'   => $dt->day
        ];

        $combinedReport = $this->fetchMonthlyTransactionsReport($input);

        assert(count($combinedReport) === 3);
    }

    public function testOrderReport()
    {
        $order = $this->createOrder();

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $rzpPayment = $this->doAuthPayment($payment);

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year'  => $dt->year,
            'month' => $dt->month,
            'day'   => $dt->day
        ];

        $orderReport = $this->fetchReport('order', $input);

        assert(count($orderReport) === 1);
    }

    public function testLinkedAccountExportReport()
    {
        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->fixtures->create('merchant:marketplace_account');

        $params = [
            'year'  => '2017',
            'month' => '1',
        ];

        $response = $this->fetchReportAsFile('account', $params);

        $this->assertNotNull($response['url']);
    }

    public function testLinkedAccountExportNonMarketplace()
    {
        $params = [
            'year'  => '2017',
            'month' => '1',
        ];

        $data = $this->testData[__FUNCTION__];

        $data['request']['content'] = $params;

        $this->ba->proxyAuth();

        $this->runRequestResponseFlow($data);
    }

    /**
     * Data for this test case needs to imported separately
     */
    public function testEntityReportTLE()
    {
        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year'  => 2017,
            'month' => 2,
            'day'   => 3
        ];

        $data = $this->fetchReportAsFile('transaction', $input);

        $this->assertNotNull($data['url']);
    }

    public function testEntityReportFile()
    {
        $this->testEntityReports();

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year'  => $dt->year,
            'month' => $dt->month,
            'day'   => $dt->day
        ];

        $data = $this->fetchReportAsFile('transaction', $input);

        $this->assertNotNull($data['url']);
    }

    public function testInvoice()
    {
        // We need to setup a terminal for the netbanking payment
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->doAuthAndCapturePayment();
        $this->doAuthCaptureAndRefundPayment();

        // We need an auth-refunded payment and make sure
        // that it doesn't appear in the invoice

        $payment = $this->defaultAuthPayment($this->getDefaultNetbankingPaymentArray());
        $input['force'] = '1';
        $this->refundAuthorizedPayment($payment['id'], $input);

        $dt = Carbon::today(Timezone::IST);
        $input = [
            'year'  => $dt->year,
            'month' => $dt->month
        ];

        $invoice = $this->fetchInvoice($input);

        $this->assertEquals(2000, $invoice['total_fee']);
        $this->assertEquals(0, $invoice['tax']);
        $this->assertEquals(2000, $invoice['razorpay_fee']);
        $this->assertEquals(0, $invoice['taxes']['IGST']);
    }

    public function testInvoiceNew()
    {
        $this->fixtures->create('merchant_invoice', ['type' => Invoice\Type::CARD_LTE_2K]);
        $this->fixtures->create('merchant_invoice', ['type' => Invoice\Type::CARD_GT_2K]);
        $this->fixtures->create('merchant_invoice', ['type' => Invoice\Type::NON_CARD]);
        $this->fixtures->create('merchant_invoice',
            [
                'type' => Invoice\Type::ADJUSTMENT, 'amount' => -45000,
                'tax' => -1800, 'Description' => 'Adjustment against extra commission'
            ]);

        $this->fixtures->create('merchant_invoice',
            [
                'type' => Invoice\Type::ADJUSTMENT, 'amount' => 25000,
                'tax' => 800, 'Description' => 'Adjustment against uncharged fee'
            ]);

        $dt = Carbon::today(Timezone::IST);
        $input = [
            'year'      => $dt->year,
            'month'     => $dt->month,
            'format'    => 'new',
        ];

        $invoiceEntries = $this->fetchInvoice($input);

        $this->assertNotEmpty($invoiceEntries['invoice_number']);
        $this->assertNotEmpty($invoiceEntries['invoice_date']);
        $this->assertArrayHasKey('gstin', $invoiceEntries);

        $data = $this->testData[__FUNCTION__];

        $requiredFields = ['Tax Invoice', 'Tax Debit Note', 'Tax Credit Note'];
        $keyedEntries = [];

        foreach ($requiredFields as $key)
        {
            $this->assertNotEmpty($invoiceEntries['pages'][$key]);
            $this->assertNotEmpty($invoiceEntries['pages'][$key]['rows']);

            foreach ($invoiceEntries['pages'][$key]['rows'] as $entry)
            {
                $description = $entry['Description'];

                $keyedEntries[$description] = $entry;

                $this->assertArraySelectiveEquals($keyedEntries[$description], $data[$key][$description]);
            }
        }

        $requiredFields = ['Document No.', 'Document Date', 'Description', 'Amount'];
        foreach ($invoiceEntries['Summary']['Invoice Summary']['rows'] as $row)
        {
            foreach ($requiredFields as $key)
            {
                $this->assertNotNull($row[$key]);
            }
        }

        $lastRowOfSummary = array_pop($invoiceEntries['Summary']['Invoice Summary']['rows']);

        $this->assertEquals(177600, $lastRowOfSummary['Amount']);
    }

    public function testInvoiceReportForMerchantWithoutGstinWithBusinessState()
    {
        $this->fixtures->create('merchant_invoice',
            [
                'type'      => Invoice\Type::CARD_LTE_2K,
                'gstin'     => null,
            ]);

        $md1 = $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'               => '10000000000000',
                'gstin'                     => null,
                'business_registered_state' => ' kerala',
            ]);

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year'      => $dt->year,
            'month'     => $dt->month,
            'format'    => 'new',
        ];

        $invoiceEntries = $this->fetchInvoice($input);

        $this->assertTestResponse($invoiceEntries);
    }

    public function testInvoiceReportForMerchantWithoutGstinRegisteredInKarnataka()
    {
        $this->fixtures->create('merchant_invoice',
            [
                'type'      => Invoice\Type::CARD_LTE_2K,
                'gstin'     => null,
            ]);

        $md1 = $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'               => '10000000000000',
                'gstin'                     => null,
                'business_registered_state' => 'Karnataka',
            ]);

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year'      => $dt->year,
            'month'     => $dt->month,
            'format'    => 'new',
        ];

        $invoiceEntries = $this->fetchInvoice($input);

        $this->assertTestResponse($invoiceEntries);
    }

    public function testPaymentReportWithoutAcquirerData()
    {
        $this->doAuthAndCapturePayment();

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year' => $dt->year,
            'month' => $dt->month,
            'day' => $dt->day
        ];

        $paymentReport = $this->fetchReport('payment', $input);

        $this->assertEquals(1, count($paymentReport));
        $this->assertArrayNotHasKey('acquirer_data', $paymentReport[0]);
    }

    public function testDspReport()
    {
        $this->fixtures->merchant->addFeatures(['dsp_report']);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $order = $this->fixtures->create('order',
            [
                'amount' => 50000,
                'currency' => 'INR',
                'receipt' => 'randon string',
                'notes' => [
                    'ref_1' => 'random 1',
                    'ref_2' => 'random 2',
                    'ref_3' => 'random 3',
                    'ref_5' => 'random 5',
                    'ref_6' => 'random 6',
                    'ref_7' => 'random 7',
                    'ref_8' => 'random 8',
                    'ref_9' => 'random 9'
                ]

            ]);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['order_id'] = $order->getPublicId();

        $payment = $this->doAuthAndCapturePayment($payment);

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'day'         => 'today',
            'merchant_id' => '10000000000000',
            'email'       => 'test1@razorpay.com',
        ];

        $data = $this->fetchDSPReport($input);
    }

    public function testBrokingReport()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_terminal');
        $this->fixtures->merchant->addFeatures(['broking_report']);

        $payment = $this->getDefaultNetbankingPaymentArray();
        $this->doAuthAndCapturePayment($payment);
        $this->doAuthCaptureAndRefundPayment($payment);

        $dt = Carbon::today(Timezone::IST);

        $input = array(
            'year' => $dt->year,
            'month' => $dt->month,
            'day' => $dt->day);

        $combinedReport = $this->fetchBrokingReport($input);

        $this->assertEquals(count($combinedReport), 3);

        $expectedContent = [
            // 'Merchant Name'     => 'ut',
            'Merchant ID'        => '10000000000000',
            // 'Txn Id'            => 'pay_6w6bmFIqLiOGVj',
            'Txn State'          => 'Sale',
            // 'Txn Date'          => '2016-12-23 03:28',
            'Client Code'        => null,
            'Merchant Txn Id'    => null,
            'Product'            => 'NSE',
            'Discriminator'      => 'NB',
            'Bank Name'          => 'Indian Bank',
            'Card Type'          => null,
            'Card No'            => null,
            'Card Issuing Bank'  => null,
            // 'Bank Ref No'       => 'GJZMBHNV9O',
            'Gross Txn Amount'   => 500,
            'Txn Charges'        => 0,
            'Service Tax'        => 0,
            'SB Cess'            => 0,
            'Krishi Kalyan Cess' => 0,
            'Total Chargeable'   => 0,
            'Net Amount'         => 500,
            'Payment Status'     => null,
            'Settlement Date'    => null,
            'Refund Reference'   => null,
            'Refund Status'      => null,
        ];

        $saleTxnReports = array_filter($combinedReport, function ($obj)
        {
            return $obj['Txn State'] === 'Sale';
        });

        $this->assertArraySelectiveEquals($expectedContent, array_pop($saleTxnReports));
    }

    protected function fetchBrokingReport($content)
    {
        $request = array(
            'url' => '/reports/transaction/broking',
            'method' => 'get',
            'content' => $content);

        $this->ba->proxyAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function fetchDSPReport($content)
    {
        $request = array(
            'url' => '/reports/transaction/dsp',
            'method' => 'get',
            'content' => $content);

        $this->ba->proxyAuth();

        return $this->makeRequestAndGetContent($request);
    }

    public function testGenerateReportCombined()
    {
        $entity = 'transaction';

        $this->generateReportAndFetch($entity);
    }

    public function testGenerateReportSettlement()
    {
        $entity = 'settlement';

        $this->generateReportAndFetch($entity);
    }

    public function testGenerateReportPayment()
    {
        $entity = 'payment';

        $this->generateReportAndFetch($entity);
    }

    protected function generateReportAndFetch(string $entity)
    {
        $this->doAuthAndCapturePayment();
        $this->doAuthCaptureAndRefundPayment();

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year' => $dt->year,
            'month' => $dt->month,
            'day' => $dt->day
        ];

        $this->generateEntityReport($entity, $input);

        $reports = $this->fetchReports(['type' => $entity]);

        assert($reports['count'] === 1);
    }
}
