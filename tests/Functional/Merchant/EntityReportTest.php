<?php

namespace RZP\Tests\Functional\Merchant;

use Mockery;
use Carbon\Carbon;

use Razorpay\IFSC\Bank;
use mikehaertl\wkhtmlto\Pdf;
use PhpParser\Node\Scalar\MagicConst\Dir;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Invoice;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Services\Scrooge;


class EntityReportTest extends TestCase
{
    use PaymentTrait;
    use SettlementTrait;
    use DbEntityFetchTrait;
    use CustomBrandingTrait;

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

    public function testRefundReportWithExtraAttributesExposedWithManualRefund()
    {

        $scroogeMock = Mockery::mock('RZP\Services\Scrooge');

        $scroogeMock->shouldReceive('getRefund')->withAnyArgs()->andReturn([
            'body' => [
                'initiation_type' => ['Merchant Initiated'],
            ],
            'code' => 200
        ]);

        $scroogeMock->shouldReceive('fetchRefundCreateData')->withAnyArgs()->andReturn([
            RefundConstants::MODE => 'IMPS',
            RefundConstants::GATEWAY_REFUND_SUPPORT => true,
            RefundConstants::INSTANT_REFUND_SUPPORT => true,
            RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND => null,
        ]);

        $this->app->instance('scrooge', $scroogeMock);

        $org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', [
            'org_id' => $org->getId(),
            'hostname' => 'rfnd.razorpay.com',
        ]);

        $this->fixtures->feature->create([
            'entity_type'   => 'org',
            'entity_id'     => $org->getId(),
            'name'          => 'show_refnd_lateauth_param',
        ]);

        $this->fixtures->edit('merchant', '10000000000000', [
            'org_id'    => $org->getId(),
        ]);

        $this->doAuthAndCapturePayment();
        $this->doAuthCaptureAndRefundPayment();

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year' => $dt->year,
            'month' => $dt->month,
            'day' => $dt->day
        ];


        $refundReport =  $this->fetchReport('refund', $input);

        $this->assertArrayHasKey('processed_at', $refundReport[0]);
        $this->assertArrayHasKey('refund_type', $refundReport[0]);
        $this->assertEquals('manual', $refundReport[0]['refund_type']);
    }

    public function testRefundReportWithExtraAttributesExposedWithAutoRefund()
    {
        $scroogeMock = Mockery::mock('RZP\Services\Scrooge');

        $scroogeMock->shouldReceive('getRefund')->withAnyArgs()->andReturn([
            'body' => [
                'initiation_type' => ['Razorpay Initiated'],
            ],
            'code' => 200
        ]);

        $scroogeMock->shouldReceive('fetchRefundCreateData')->withAnyArgs()->andReturn([
                RefundConstants::MODE => 'IMPS',
                RefundConstants::GATEWAY_REFUND_SUPPORT => true,
                RefundConstants::INSTANT_REFUND_SUPPORT => true,
                RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND => null,
        ]);

        $this->app->instance('scrooge', $scroogeMock);

        $org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', [
            'org_id' => $org->getId(),
            'hostname' => 'rfnd.razorpay.com',
        ]);

        $this->fixtures->feature->create([
            'entity_type'   => 'org',
            'entity_id'     => $org->getId(),
            'name'          => 'show_refnd_lateauth_param',
        ]);

        $this->fixtures->edit('merchant', '10000000000000', [
            'org_id'    => $org->getId(),
        ]);

        $this->doAuthAndCapturePayment();
        $this->doAuthCaptureAndRefundPayment();

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year' => $dt->year,
            'month' => $dt->month,
            'day' => $dt->day
        ];



        $refundReport =  $this->fetchReport('refund', $input);

        $this->assertArrayHasKey('processed_at', $refundReport[0]);
        $this->assertArrayHasKey('refund_type', $refundReport[0]);
        $this->assertEquals('auto', $refundReport[0]['refund_type']);
    }

    public function testRefundReportWithoutExtraAttributesExposed()
    {
        $this->doAuthAndCapturePayment();
        $this->doAuthCaptureAndRefundPayment();

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year' => $dt->year,
            'month' => $dt->month,
            'day' => $dt->day
        ];

        $refundReport =  $this->fetchReport('refund', $input);

        $this->assertArrayNotHasKey('processed_at', $refundReport[0]);
        $this->assertArrayNotHasKey('refund_type', $refundReport[0]);
    }

    public function testPaymentReportWithoutExtraAttributesExposed()
    {
        $this->doAuthAndCapturePayment();
        $this->doAuthCaptureAndRefundPayment();

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year' => $dt->year,
            'month' => $dt->month,
            'day' => $dt->day
        ];

        $paymentReport =  $this->fetchReport('payment', $input);

        $this->assertArrayNotHasKey('authorized_at', $paymentReport[0]);
        $this->assertArrayNotHasKey('captured_at', $paymentReport[0]);
        $this->assertArrayNotHasKey('late_authorized', $paymentReport[0]);
        $this->assertArrayNotHasKey('auto_captured', $paymentReport[0]);
    }

    public function testTransactionReport()
    {
        $this->markTestSkipped();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

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

    public function testSettlementReconReport()
    {
        $now = Carbon::create(2018, 8, 14, 10, 0, 0, Timezone::IST);

        Carbon::setTestNow($now);

        $this->createPaymentEntities();

        $txn = $this->getLastEntity('transaction', true);

        $this->initiateSettlements($txn['channel']);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('settlement', $txn['type']);

        $dt = Carbon::today(Timezone::IST);

        $input = [
            'year'  => $dt->year,
            'month' => $dt->month,
            'day'   => $dt->day
        ];

        $data = $this->fetchSettlementReconReport($input);

        $this->assertEquals('collection', $data['entity']);
        $this->assertEquals(5, $data['count']);
        $this->assertEquals(5, count($data['items']));
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

    public function testInvoiceNew()
    {
        $this->markTestSkipped("marking skipped because PRs are not getting merged");

        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->fixtures->create('merchant_invoice', ['type' => Invoice\Type::CARD_LTE_2K, 'balance_id' => 10000000000000, 'month' => 7, 'year' => 2019]);
        $this->fixtures->create('merchant_invoice', ['type' => Invoice\Type::CARD_GT_2K, 'balance_id' => 10000000000000, 'month' => 7, 'year' => 2019]);
        $this->fixtures->create('merchant_invoice', ['type' => Invoice\Type::OTHERS, 'balance_id' => 10000000000000, 'month' => 7, 'year' => 2019]);
        $this->fixtures->create('merchant_invoice',
            [
                'type' => Invoice\Type::ADJUSTMENT, 'amount' => -45000,
                'tax' => -1800, 'Description' => 'Adjustment against extra commission',
                'balance_id' => 10000000000000,
                'month' => 7,
                'year' => 2019,
            ]);

        $this->fixtures->create('merchant_invoice',
            [
                'type' => Invoice\Type::ADJUSTMENT, 'amount' => 25000,
                'tax' => 800, 'Description' => 'Adjustment against uncharged fee',
                'balance_id' => 10000000000000,
                'month' => 7,
                'year' => 2019
            ]);

        $input = [
            'year'      => $oldDateTime->year,
            'month'     => $oldDateTime->month,
            'format'    => 'new',
        ];

        $md1 = $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'               => '10000000000000',
                'gstin'                     => null,
                'business_registered_state' => 'Karnataka',
            ]);

        $invoiceEntries = $this->fetchInvoice($input);
        $file = $this->getLastEntity('file_store', true);

        $this->assertContains($file['location'], $invoiceEntries['signed_url']);
        $this->assertEquals(NULL, $invoiceEntries['error']);

        Carbon::setTestNow();
    }

    public function testInvoiceReportForMerchantWithoutGstinRegisteredInKarnatakaWithNewFlow()
    {
        // currently this flow is not enabled so skipping test will fix this
        $this->markTestSkipped("settlements team will fix this test case");

        $oldDateTime = Carbon::create(2019, 5, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->fixtures->create('merchant_invoice',
            [
                'type'       => Invoice\Type::CARD_LTE_2K,
                'gstin'      => null,
                'balance_id' => 10000000000000,
                'month'      => 5,
                'year'       => 2019
            ]);

        $md1 = $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'               => '10000000000000',
                'gstin'                     => null,
                'business_registered_state' => 'Karnataka',
            ]);

        $input = [
            'year'      => $oldDateTime->year,
            'month'     => $oldDateTime->month,
            'format'    => 'new',
        ];

        $invoiceEntries = $this->fetchInvoice($input);

        $file = $this->getLastEntity('file_store', true);

        $this->assertEquals('merchant_invoice', $file['type']);
        $this->assertEquals('application/pdf', $file['mime']);
        $this->assertEquals('merchant_pg_invoices/2019/5/10000000000000', $file['name']);
        $this->assertEquals('invoices', $file['bucket']);
        $this->assertEquals('10000000000000', $file['merchant_id']);
        $this->assertEquals('s3', $file['store']);

        $this->assertContains($file['location'], $invoiceEntries['signed_url']);
        $this->assertEquals(NULL, $invoiceEntries['error']);

        Carbon::setTestNow();
    }

    public function testInvoiceReportForMerchantWhenThereIsNoInvoiceGenerated()
    {
        $oldDateTime = Carbon::create(2019, 5, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $md1 = $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'               => '10000000000000',
                'gstin'                     => null,
                'business_registered_state' => 'Karnataka',
            ]);

        $input = [
            'year'      => $oldDateTime->year,
            'month'     => $oldDateTime->month,
            'format'    => 'new',
        ];

        $invoiceEntries = $this->fetchInvoice($input);

        $this->assertEquals(NULL, $invoiceEntries['signed_url']);
        $this->assertEquals('Invoice not generated yet for merchant 10000000000000 for year 2019 and month 5',
            $invoiceEntries['error']);

        Carbon::setTestNow();
    }

    public function testInvoiceReportForMerchantWhenTheMerchantInvoiceOfZeroAmountIsGenerated()
    {
        $oldDateTime = Carbon::create(2019, 5, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->fixtures->create('merchant_invoice',
            [
                'type'       => Invoice\Type::CARD_LTE_2K,
                'gstin'      => null,
                'balance_id' => 10000000000000,
                'month'      => 5,
                'year'       => 2019,
                'amount'     => 0,
                'tax'        => 0,
            ]);

        $md1 = $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'               => '10000000000000',
                'gstin'                     => null,
                'business_registered_state' => 'Karnataka',
            ]);

        $input = [
            'year'      => $oldDateTime->year,
            'month'     => $oldDateTime->month,
            'format'    => 'new',
        ];

        $invoiceEntries = $this->fetchInvoice($input);

        $this->assertEquals(NULL, $invoiceEntries['signed_url']);
        $this->assertEquals('Invoice not generated yet for merchant 10000000000000 for year 2019 and month 5 since the amount is zero',
            $invoiceEntries['error']);

        Carbon::setTestNow();
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
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_ebs_terminal');
        $this->fixtures->merchant->addFeatures(['broking_report']);

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::KARB);
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
            'Bank Name'          => 'Karnataka Bank',
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

    protected function fetchSettlementReconReport($content)
    {
        $request = array(
            'url'     => '/settlements/recon/combined',
            'method'  => 'get',
            'content' => $content);

        $this->ba->privateAuth();

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
