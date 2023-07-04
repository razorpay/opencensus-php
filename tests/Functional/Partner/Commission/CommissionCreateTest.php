<?php

namespace RZP\Tests\Functional\Partner\Commission;

use DB;

use App;
use Mail;
use Mockery;
use Carbon\Carbon;
use RZP\Models\Pricing\Repository as PricingRepo;
use RZP\Services\Mock\DataLakePresto;
use Illuminate\Database\Eloquent\Factory;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Partner;
use RZP\Constants\Timezone;
use RZP\Models\Partner\Config;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Models\FileStore\Service;
use RZP\Models\Merchant\FeeBearer;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Channel;
use RZP\Models\Partner\Commission;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Mail\Merchant\CommissionInvoice;
use RZP\Models\Partner\Commission\Invoice;
use RZP\Mail\Merchant\CommissionOpsInvoice;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Mail\Merchant\CommissionInvoiceReminder;
use RZP\Models\Merchant\Constants as MeConstants;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Tests\Functional\Merchant\CommissionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Partner\Commission\Type as CommissionType;
use RZP\Models\Partner\Commission\Constants as CommissionConstants;

class CommissionCreateTest extends TestCase
{
    use MocksSplitz;
    use CommissionTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/CommissionCreateTestData.php';

        parent::setUp();


    }

    private function enableVirtualAccountQrcodeAndMethods(string $merchantId, string $appId) {
        $this->fixtures->merchant->enableMethod($merchantId, 'bank_transfer');
        $this->fixtures->merchant->enableMethod($merchantId, 'upi');

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'qr_codes', 'bharat_qr_v2', 'bharat_qr'], $merchantId);
        $this->fixtures->merchant->addFeatures(['virtual_accounts'], $appId, 'application');

        $this->fixtures->create('terminal:shared_bank_account_terminal');
        $this->fixtures->create('terminal:shared_sharp_terminal');
        $this->fixtures->create('terminal:bharat_qr_terminal_upi');
        $this->fixtures->create('terminal:vpa_shared_terminal_icici');
    }

    private function createVirtualAccountWithPartnerAuth(string $subMerchantId, string $clientId, string $clientSecret, string $type) {
        $this->ba->partnerAuth($subMerchantId, 'rzp_test_partner_' . $clientId, $clientSecret);

        switch($type) {
            case 'bank_transfer':
                $testDataName = 'createVirtualAccountBankTransferReceiver';
                break;
            case 'qr_code':
                $testDataName = 'createVirtualAccountQrCodeReceiver';
                break;
            default:
                throw new \Exception('Type not implemented!');
        }

        $virtualAccount = $this->makeRequestAndGetContent($this->testData[$testDataName]);

        $this->fixtures->stripSign($virtualAccount['id']);
        $this->fixtures->stripSign($virtualAccount['receivers'][0]['id']);

        $this->ba->deleteAccountAuth();

        return $virtualAccount;
    }

    private function createBankTransferPayment(array $virtualAccount) {
        $paymentAttributes = $this->testData['createBankTransferPayment'];
        $paymentAttributes['content']['payee_account'] = $virtualAccount['receivers'][0]['account_number'];
        $paymentAttributes['content']['payee_ifsc'] = $virtualAccount['receivers'][0]['ifsc'];

        $this->ba->proxyAuth();
        $this->makeRequestAndGetContent($paymentAttributes);

        $payment = $this->getLastEntity('payment', true);
        $this->fixtures->stripSign($payment['id']);

        return $payment;
    }

    private function createQRCodePayment(array $qrCode) {
        $paymentAttributes = $this->testData['createQRCodePayment'];
        $paymentAttributes['content']['merchantTranId'] = $qrCode['id'] . 'qrv2';
        $paymentAttributes['raw'] = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($paymentAttributes['content']);

        $this->makeRequestAndGetContent($paymentAttributes);

        $payment = $this->getLastEntity('payment', true);
        $this->fixtures->stripSign($payment['id']);

        return $payment;
    }

    /**
     * This testcase validates the following,
     *    (in this we create virtual account with partner auth (bank transfer) and payment from bank transfer)
     *
     * 1. If payment is created for virtual account from partner auth has,
     *     same partner auth propagated
     * 2. by checking valid entityOrigin for payment (here partner application)
     * 3. validates if commission is generated and belongs to made payment
     * 4. also verifies the partner to which commission is granted
     */
    public function testVirtualAccountForAggregatorCommissionForBankTransfer() {
        $partnerId = Constants::DEFAULT_MERCHANT_ID;
        $subMerchantId = Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;

        $client = $this->setUpNonPurePlatformPartnerAndSubmerchant($partnerId, $subMerchantId);

        $this->enableVirtualAccountQrcodeAndMethods($subMerchantId, $client->getApplicationId());

        $this->fixtures->pricing->createBankTransferPercentPricingPlan([
           'plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
           'percent_rate' => 200,
        ]);
        $this->fixtures->pricing->createBankTransferPercentPricingPlan([
            'plan_id' => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            'percent_rate' => 100,
        ]);
        $this->createConfigForPartnerApp($client->getApplicationId(), null, [
            'implicit_plan_id' => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
        ]);

        $virtualAccount = $this->createVirtualAccountWithPartnerAuth(
            $subMerchantId,
            $client->getId(),
            $client->getSecret(),
            'bank_transfer'
        );

        $vaEntityOrigin = $this->getDbEntity('entity_origin', ['entity_id' => $virtualAccount['id']]);

        $this->assertEquals('application', $vaEntityOrigin['origin_type']);
        $this->assertEquals($client->getApplicationId(), $vaEntityOrigin['origin_id']);

        $payment = $this->createBankTransferPayment($virtualAccount);

        $paymentEntityOrigin = $this->getDbEntity('entity_origin', ['entity_id' => $payment['id']]);

        $this->assertEquals('application', $paymentEntityOrigin['origin_type']);
        $this->assertEquals($client->getApplicationId(), $paymentEntityOrigin['origin_id']);

        $commission = $this->getLastEntity('commission', true);

        $this->assertEquals($commission['source_id'], $payment['id']);
        $this->assertEquals('captured', $commission['status']);
        $this->assertEquals($partnerId, $commission['partner_id']);
    }

    /**
     * This testcase validates the following,
     *    (in this we create virtual account with partner auth (qr_code type) and payment upi)
     *
     * 1. If payment is created for virtual account from partner auth has,
     *     same partner auth propagated
     * 2. check valid entityOrigin for payment (here partner application)
     * 3. validates if commission is generated and belongs to made payment
     * 4. also verifies the partner to which commission is granted
     */
    public function testVirtualAccountForAggregatorCommissionForQrCode() {
        $partnerId = Constants::DEFAULT_MERCHANT_ID;
        $subMerchantId = Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;

        $client = $this->setUpNonPurePlatformPartnerAndSubmerchant($partnerId, $subMerchantId);

        $this->enableVirtualAccountQrcodeAndMethods($subMerchantId, $client->getApplicationId());

        $this->fixtures->pricing->createUpiTransferPricingPlan([
           'plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
           'percent_rate' => 200,
           'receiver_type' => 'qr_code',
        ]);
        $this->fixtures->pricing->createUpiTransferPricingPlan([
            'plan_id' => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            'percent_rate' => 100,
            'receiver_type' => 'qr_code',
        ]);
        $this->createConfigForPartnerApp($client->getApplicationId(), null, [
            'implicit_plan_id' => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
        ]);

        $virtualAccount = $this->createVirtualAccountWithPartnerAuth(
            $subMerchantId,
            $client->getId(),
            $client->getSecret(),
            'qr_code'
        );

        $vaEntityOrigin = $this->getDbEntity('entity_origin', ['entity_id' => $virtualAccount['id']]);

        $this->assertEquals('application', $vaEntityOrigin['origin_type']);
        $this->assertEquals($client->getApplicationId(), $vaEntityOrigin['origin_id']);

        $payment = $this->createQRCodePayment($virtualAccount['receivers'][0]);

        $paymentEntityOrigin = $this->getDbEntity('entity_origin', ['entity_id' => $payment['id']]);

        $this->assertEquals('application', $paymentEntityOrigin['origin_type']);
        $this->assertEquals($client->getApplicationId(), $paymentEntityOrigin['origin_id']);

        $commission = $this->getLastEntity('commission', true);

        $this->assertEquals($commission['source_id'], $payment['id']);
        $this->assertEquals('captured', $commission['status']);
        $this->assertEquals($partnerId, $commission['partner_id']);
    }

    public function testImplicitVariableOnPaymentCapture()
    {
        $testData = $this->setUpCommissionCreate();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $this->checkClearOnHoldAndSettlement($commission, Config\Entity::DEFAULT_TDS_PERCENTAGE/100);

        $commissionComponent = $this->getDbEntity('commission_component');

        $this->assertEquals($commission[Commission\Entity::ID], $commissionComponent->getCommissionId());

        $this->assertEquals($commission[Commission\Entity::FEE] - $commission[Commission\Entity::TAX], $commissionComponent->getMerchantPricingAmount() - $commissionComponent->getCommissionPricingAmount());
    }


    public function testImplicitVariableOnNONINRPaymentCapture()
    {
        $testData = $this->setUpCommissionCreate(['currency' => 'USD']);

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $this->checkClearOnHoldAndSettlement($commission, Config\Entity::DEFAULT_TDS_PERCENTAGE/100);

        $commissionComponent = $this->getDbEntity('commission_component');

        $this->assertEquals($commission[Commission\Entity::ID], $commissionComponent->getCommissionId());

        $this->assertEquals($commission[Commission\Entity::FEE] - $commission[Commission\Entity::TAX], $commissionComponent->getMerchantPricingAmount() - $commissionComponent->getCommissionPricingAmount());
    }

    public function testCommissionTransactionChannelOnPaymentCaptureForMalaysainMerchants()
    {
        $testData = $this->setupCommissionCreateForMalaysianMerchant();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->startTest($testData);

        $pricing = (new PricingRepo)->getPlanByIdOrFailPublic(Pricing::DEFAULT_PRICING_PLAN_ID, Org::CURLEC_ORG);

        $this->assertEquals(Constants::DEFAULT_IMPLICIT_PRICING_PLAN, $pricing->getId());

        $this->assertCommisionAndTransactionData(CommissionType::IMPLICIT);
    }

    public function testInvoiceOnHoldClear()
    {
        Mail::fake();

        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => 1770,
            'debit'  => 0,
            'fee'    => 1770,
            'tax'    => 270,
        ]);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->mockPartnerSubMtuDatalakeQuery($partner->getId());

        $this->runRequestResponseFlow($testData);

        $invoice = $this->getDbLastEntity('commission_invoice');

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['invoice_ids'] = [$invoice->getId()];
        $this->runRequestResponseFlow($testData);

        // check that invoice status isn't updated
        $invoice = $this->getDbLastEntity('commission_invoice');
        $this->assertEquals('issued', $invoice->getStatus());

        // get commission transactions and verify on hold flag is cleared
        $commission = $this->getDbEntityById('commission', $commission['id']);
        $commTransaction = $this->getDbEntityById('transaction', $commission['transaction_id']);
        $this->assertEquals(0, $commTransaction->getOnHold());

        // check that no adjustment entries are created
        $tdsAdjustment = $this->getDbLastEntity('adjustment');
        $this->assertNull($tdsAdjustment);
    }

    public function testSendCommissionInvoiceRemindersSuccess()
    {
        Mail::fake();

        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => 1770,
            'debit'  => 0,
            'fee'    => 1770,
            'tax'    => 270,
        ]);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->mockPartnerSubMtuDatalakeQuery($partner->getId());

        $this->runRequestResponseFlow($testData);

        $invoice = $this->getDbLastEntity('commission_invoice');

        $testData = $this->testData['testInvoiceOnHoldClear'];
        $testData['request']['content']['invoice_ids'] = [$invoice->getId()];
        $this->runRequestResponseFlow($testData);

        // check that invoice status isn't updated
        $invoice = $this->getDbLastEntity('commission_invoice');
        $this->assertEquals('issued', $invoice->getStatus());

        $input = [
            "experiment_id" => "JbUKeDS8uXGQBI",
            "id"            => $partner->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        (new Invoice\Service)->sendInvoiceReminders();

        Mail::assertSent(CommissionInvoiceReminder::class, function($mail) use($invoice, $partner, $now)
        {
            $timestamps    = (new Invoice\Core())->convertMonthAndYearToTimeStamp($now->month, $now->year);
            $startDate     = Carbon::createFromTimestamp($timestamps['from'], Timezone::IST)->format('d-M-y');
            $endDate       = Carbon::createFromTimestamp($timestamps['to'], Timezone::IST)->format('d-M-y');

            $expectedInvoiceData [] = [
                'id'                     => $invoice->getId(),
                'gross_amount_spread'    => "17.70",
                'period'                 => $startDate.' to '.$endDate,
            ];

            $merchant = $this->getDbEntity('merchant', ['id' => $partner->getId()]);
            $activationStatus = $merchant->merchantDetail->getActivationStatus();
            $template = Invoice\Constants::DEFAULT_PARTNER_INVOICE_REMINDER_EMAIL_TEMPLATE_PREFIX.'.'.MeConstants::DEFAULT;

            $expectedData = [
                'merchant'              => $merchant->toArray(),
                'activation_status'     => $activationStatus,
                'invoices'              => $expectedInvoiceData,
                'invoice_count'         => 1,
                'view'                  => $template,
                'country_code'          => 'IN'
            ];

            $this->assertSame($expectedData, $mail->viewData);

            return true;
        });
    }

    public function testSendCommissionInvoiceRemindersSuccessForReseller()
    {
        Mail::fake();

        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission(['partner_type' => 'reseller'],[],[],[
            'credit' => 1770,
            'debit'  => 0,
            'fee'    => 1770,
            'tax'    => 270,
        ]);

        $this->fixtures->create('partner_activation',[
            'merchant_id'       => $partner->getId(),
            'activation_status' => 'activated'
        ]);


        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->mockPartnerSubMtuDatalakeQuery($partner->getId());

        $this->runRequestResponseFlow($testData);

        $invoice = $this->getDbLastEntity('commission_invoice');

        $testData = $this->testData['testInvoiceOnHoldClear'];
        $testData['request']['content']['invoice_ids'] = [$invoice->getId()];
        $this->runRequestResponseFlow($testData);

        // check that invoice status isn't updated
        $invoice = $this->getDbLastEntity('commission_invoice');
        $this->assertEquals('issued', $invoice->getStatus());

        $input = [
            "experiment_id" => "JbUKeDS8uXGQBI",
            "id"            => $partner->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        (new Invoice\Service)->sendInvoiceReminders();

        Mail::assertSent(CommissionInvoiceReminder::class, function($mail) use($invoice, $partner, $now)
        {
            $timestamps    = (new Invoice\Core())->convertMonthAndYearToTimeStamp($now->month, $now->year);
            $startDate     = Carbon::createFromTimestamp($timestamps['from'], Timezone::IST)->format('d-M-y');
            $endDate       = Carbon::createFromTimestamp($timestamps['to'], Timezone::IST)->format('d-M-y');

            $expectedInvoiceData [] = [
                'id'                     => $invoice->getId(),
                'gross_amount_spread'    => "17.70",
                'period'                 => $startDate.' to '.$endDate,
            ];

            $merchant = $this->getDbEntity('merchant', ['id' => $partner->getId()]);
            $activationStatus = (new Invoice\Core())->getApplicablePartnerActivationStatus($merchant);
            $template = Invoice\Constants::RESELLER_PARTNER_INVOICE_REMINDER_EMAIL_TEMPLATE_PREFIX.'.'.DetailStatus::ACTIVATED;

            $expectedData = [
                'merchant'              => $merchant->toArray(),
                'activation_status'     => $activationStatus,
                'invoices'              => $expectedInvoiceData,
                'invoice_count'         => 1,
                'view'                  => $template,
                'country_code'          => 'IN'
            ];

            $this->assertSame($expectedData, $mail->viewData);

            return true;
        });
    }

    public function testSendCommissionInvoiceRemindersForRejectedPartners()
    {
        Mail::fake();

        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => 1770,
            'debit'  => 0,
            'fee'    => 1770,
            'tax'    => 270,
        ]);

        $this->fixtures->merchant_detail->edit($partner->getId(), ['activation_status' => 'rejected']);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->mockPartnerSubMtuDatalakeQuery($partner->getId());

        $this->runRequestResponseFlow($testData);

        $invoice = $this->getDbLastEntity('commission_invoice');

        $testData = $this->testData['testInvoiceOnHoldClear'];
        $testData['request']['content']['invoice_ids'] = [$invoice->getId()];
        $this->runRequestResponseFlow($testData);

        // check that invoice status isn't updated
        $invoice = $this->getDbLastEntity('commission_invoice');
        $this->assertEquals('issued', $invoice->getStatus());

        $input = [
            "experiment_id" => "JbUKeDS8uXGQBI",
            "id"            => $partner->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        (new Invoice\Service)->sendInvoiceReminders();

        Mail::assertNotSent(CommissionInvoiceReminder::class);
    }

    private function createTaxes()
    {
        DB::connection('test')->table('taxes')->insert(
            [
                'id' => '9nDpYjuyZsOlMK',
                'rate' => 90000,
                'rate_type' => 'percentage',
                'name' => 'CGST 9%',
                'merchant_id' => '100000Razorpay',
                'created_at' => '1548745646',
                'updated_at' => '1548745646',
            ]
        );
        DB::connection('test')->table('taxes')->insert(
            [
                'id' => '9nDpYqgYcqpr8q',
                'rate' => 90000,
                'rate_type' => 'percentage',
                'name' => 'SGST 9%',
                'merchant_id' => '100000Razorpay',
                'created_at' => '1548745646',
                'updated_at' => '1548745646',
            ]
        );

        DB::connection('test')->table('taxes')->insert(
            [
                'id' => '9nDpYf1tTUs2Vh',
                'rate' => 180000,
                'rate_type' => 'percentage',
                'name' => 'IGST 18%',
                'merchant_id' => '100000Razorpay',
                'created_at' => '1548745646',
                'updated_at' => '1548745646',
            ]
        );
    }

    public function testInvoiceCompleteFlow()
    {
        Mail::fake();

        $testData = $this->setUpCommissionCreateWith3MTU();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->runRequestResponseFlow($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [Constants::DEFAULT_PLATFORM_MERCHANT_ID];

        $this->createTaxes();

        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->mockAllSplitzResponseDisable();

        $this->runRequestResponseFlow($testData);

        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');

        $invoiceExpectedData = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'month' => $now->month,
            'year' => $now->year,
            'status' => 'issued',
            'gross_amount' => 944,
            'tax_amount' => 144,
        ];

        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());

        $lineItemExpectedData = [
            [
                'amount' => 944,
                'gross_amount' => 944,
                'tax_amount' => 144,
                'net_amount' => 944,
                'tax_inclusive' => true,
            ]
        ];

        $this->assertArraySelectiveEquals($lineItemExpectedData, $invoice->lineItems->toArray());

        $this->fixtures->merchant->addFeatures('automated_comm_payout', Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $testData = $this->testData['testInvoiceAction'];

        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();

        $this->ba->proxyAuth('rzp_test_' . Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        $invoice = $this->getDbLastEntity('commission_invoice');

        $this->assertEquals('under_review', $invoice['status']);

        $app = App::getFacadeRoot();

        $app['workflow']->setMethod('DELETE');

        $testData = $this->testData['testInvoiceActionApproved'];

        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();

        $this->ba->proxyAuth('rzp_test_' . Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        // Check that when the CommissionTdsSettlement job is triggered,
        // the dirty data set in the workflow singleton should be reset or get updated as per the job flow.
        // Taking an example of workflow data gets resetted as the HTTP method wouldn't be DELETE in the flow above
        $this->assertFalse($app['workflow']->getMethod() === 'DELETE');

        $invoice = $this->getDbLastEntity('commission_invoice');

        $this->assertEquals('processed', $invoice['status']);
    }


    /**
     * The following testcase validates the following
     * 1. Create commission for a single subM
     * 2. create commission_invoice
     * 3. validate partner approval for invoice created before 3 months
     */
    public function testInvoiceApprovalFor3MonthOld()
    {
        Mail::fake();

        $testData = $this->setUpCommissionCreate();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [Constants::DEFAULT_PLATFORM_MERCHANT_ID];

        $this->createTaxes();

        $this->runRequestResponseFlow($testData);


        $month = $now->month > 3 ? $now->month-3 : $now->month+9;
        $year = $now->month > 3 ? $now->year : $now->year-1;

        $invoice = $this->getDbLastEntity('commission_invoice');

        $this->fixtures->base->editEntity('commission_invoice',  $invoice['id'],
              ['month' => $month ,'year'=> $year]);

        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');

        $invoiceExpectedData = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'month' => $month,
            'year' => $year,
            'status' => 'issued',
            'gross_amount' => 944,
            'tax_amount' => 144,
        ];

        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());

        $lineItemExpectedData = [
            [
                'amount' => 944,
                'gross_amount' => 944,
                'tax_amount' => 144,
                'net_amount' => 944,
                'tax_inclusive' => true,
            ]
        ];

        $this->assertArraySelectiveEquals($lineItemExpectedData, $invoice->lineItems->toArray());

        $this->fixtures->merchant->addFeatures('automated_comm_payout', Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $testData = $this->testData['testInvoiceAction'];

        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();

        $this->ba->proxyAuth('rzp_test_' . Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

    }
    /**
     * The following testcase validates the following
     * 1. Create commission for a single subM
     * 2. Validate create commission_invoice should be skipped without exception
     * since partner do not have three transacting mtus and partner created after tnc update timestamp
     */
    public function testInvoiceCreateWithout3SubMtusAfterUpdatedTnc()
    {
        Mail::fake();

        $testData = $this->setUpCommissionCreate();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->merchant->edit(Constants::DEFAULT_PLATFORM_MERCHANT_ID, ['created_at' => Invoice\Constants::INVOICE_TNC_UPDATED_TIMESTAMP]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->runRequestResponseFlow($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [Constants::DEFAULT_PLATFORM_MERCHANT_ID];

        $this->createTaxes();

        $this->mockAllSplitzTreatment();

        $this->runRequestResponseFlow($testData);

        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');

        $this->assertNull($invoice);
    }

    /**
     * The following testcase validates the following
     * 1. Create commission for a single subM
     * 2. Validate create commission_invoice should not be skipped even if
     *  partner do not have three transacting mtus as created_at is before tnc timestamp
     */
    public function testInvoiceCreateWithout3SubMtusBeforeUpdatedTnc()
    {
        Mail::fake();

        $testData = $this->setUpCommissionCreate();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->merchant->edit(Constants::DEFAULT_PLATFORM_MERCHANT_ID, ['created_at' => (Invoice\Constants::INVOICE_TNC_UPDATED_TIMESTAMP-1000)]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->runRequestResponseFlow($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [Constants::DEFAULT_PLATFORM_MERCHANT_ID];

        $this->createTaxes();

        $this->mockAllSplitzTreatment();

        $invoiceExpectedData = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'month' => $now->month,
            'year' => $now->year,
            'status' => 'issued',
            'gross_amount' => 944,
            'tax_amount' => 144,
        ];

        $this->runRequestResponseFlow($testData);

        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');

        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());
    }


    /**
     * The following testcase validates the following
     * 1. Create commission for a single subM
     * 2. Create commission_invoice
     * 3. No exception should be thrown and should work as before as the experiment is disabled.
     */
    public function testInvoiceFetchWithLessSubMExpDisabled()
    {
        $this->createInvoiceDataForLessSubM();

        $testData = $this->testData['testInvoiceFetchWithLessSubMTestDataExpDisabled'];

        $this->ba->proxyAuth('rzp_test_' . Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);
    }

    public function testInvoiceFetchForResellerActivatedPartner()
    {
        $testData = $this->setUpCommissionCreateWith3MTU();
        $merchant = ['partner_type' => 'reseller'];
        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID];
        $partnerActivation = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,'activation_status' => 'activated'];
        $testData = $this->setupForInvoiceAutoApproval($testData, $merchantDetail, $partnerActivation, $merchant);

        $now = Carbon::now(Timezone::IST);

        $this->mockPartnerInvoiceAutoApproval(Constants::DEFAULT_PLATFORM_MERCHANT_ID, $now->year, 'enable');
        $this->mockAutoApprovalFinanceExp(Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceFetchWithLessSubMTestDataExpDisabled'];

        $testData['response']['content'] = ['can_approve' => true];

        $this->ba->proxyAuth('rzp_test_' . Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);
    }

    public function testInvoiceFetchForActivatedResellerPartnerWithMerchantKYC()
    {
        $testData = $this->setUpCommissionCreateWith3MTU();
        $merchant = ['partner_type' => 'reseller'];
        $merchantDetail    = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'activation_status'=> 'activated'];
        $partnerActivation = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,'activation_status' => 'activated'];
        $testData = $this->setupForInvoiceAutoApproval($testData, $merchantDetail, $partnerActivation, $merchant);

        $now = Carbon::now(Timezone::IST);

        $this->mockPartnerInvoiceAutoApproval(Constants::DEFAULT_PLATFORM_MERCHANT_ID, $now->year, 'enable');
        $this->mockAutoApprovalFinanceExp(Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceFetchWithLessSubMTestDataExpDisabled'];

        $testData['response']['content'] = ['can_approve' => true];

        $this->ba->proxyAuth('rzp_test_' . Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);
    }


    private function createInvoiceDataForLessSubM()
    {
        Mail::fake();

        $testData = $this->setUpCommissionCreate();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [Constants::DEFAULT_PLATFORM_MERCHANT_ID];

        $this->createTaxes();

        $this->ba->adminAuth();

        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);
    }

    public function testImplicitVariableOnHoldClearForHighTdsPercentage()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $this->checkClearOnHoldAndSettlement($commission, Config\Entity::TDS_PERCENTAGE_FOR_MISSING_DETAILS/100);
    }

    public function testCommissionSettlementForNonActivePartner()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        // capture payment
        $this->startTest($testData);

        list($_, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $testData = $this->testData['testClearOnHoldForCommission'];

        $testData['request']['url'] = '/commissions/partner/'.$commission['partner_id'].'/on_hold/clear';

        // clear on_hold for partner
        $this->runRequestResponseFlow($testData);

        // non-active partner details
        $merchantDetailAttributes = [
            Entity::ACTIVATION_STATUS  => Partner\Activation\Constants::UNDER_REVIEW,
            Entity::LOCKED             => false,
            Entity::SUBMITTED          => true,
        ];

        // add activation details in merchant_detail entity
        $this->fixtures->merchant_detail->edit(Constants::DEFAULT_PLATFORM_MERCHANT_ID, $merchantDetailAttributes);

        $this->ba->cronAuth();

        Carbon::setTestNow(Holidays::getNthWorkingDayFrom(Carbon::now(), 5)->addHour(10));

        $testData = $this->testData['testInitiateCommissionSettlement'];

        // commission settlement initiate
        $this->runRequestResponseFlow($testData);

        $settlementTransaction = $this->getDbLastEntity('transaction');

        // check that settlement is not created
        $this->assertNotEquals('settlement', $settlementTransaction->getType());

        $this->assertEquals(Channel::YESBANK, $settlementTransaction->getChannel());

        $settlement = $this->getDbLastEntity('settlement');

        $this->assertNull($settlement);

        $this->initiateTransfer(Channel::YESBANK, 'settlement', 'settlement');

        // check that fund transfer attempt is created
        $attempt = $this->getDbLastEntity('fund_transfer_attempt');

        // check that fund transfer didn't happen
        $this->assertNull($attempt);
    }

    public function testInvoiceGenerate()
    {
        $this->markTestSkipped();
        Mail::fake();

        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => 1770,
            'debit'  => 0,
            'fee'    => 1770,
            'tax'    => 270,
        ]);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->mockPartnerSubMtuDatalakeQuery($partner->getId());

        $this->startTest($testData);

        // calling generate invoice twice should still create only one invoice
        $this->startTest($testData);

        $invoices = $this->getDbEntities('commission_invoice');
        $this->assertCount(1, $invoices);

        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');

        $invoiceExpectedData = [
            'merchant_id' => 'DefaultPartner',
            'month' => $now->month,
            'year' => $now->year,
            'status' => 'issued',
            'gross_amount' => 1770,
            'tax_amount' => 270,
        ];

        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());

        $lineItemExpectedData = [
            [
                'amount' => 1770,
                'gross_amount' => 1770,
                'tax_amount' => 270,
                'net_amount' => 1770,
                'tax_inclusive' => true,
            ]
        ];

        $this->assertArraySelectiveEquals($lineItemExpectedData, $invoice->lineItems->toArray());

        $this->fixtures->merchant->addFeatures('automated_comm_payout', $partner->getId());

        $testData = $this->testData['testInvoiceAction'];

        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();

        $this->ba->proxyAuth('rzp_test_' . $partner->getId());

        $this->runRequestResponseFlow($testData);

        $invoice = $this->getDbLastEntity('commission_invoice');

        $this->assertEquals('under_review', $invoice['status']);

        Mail::assertSent(CommissionOpsInvoice::class, 1);
        Mail::assertSent(CommissionInvoice::class, 1);

        $testData = $this->testData['testInvoiceFetch'];

        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();

        $this->runRequestResponseFlow($testData);
    }

    /**
     * Check that when invoice gross amount is less than Entity::MAX_AUTO_APPROVAL_AMOUNT,
     * invoice is auto approved and marked as processed after partner approves the invoice.
     * Invoice doesn't go to finance for approval.
     */
    public function testInvoiceAutoApprovedWhenExpEnabled()
    {
        Mail::fake();

        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => 1770,
            'debit'  => 0,
            'fee'    => 1770,
            'tax'    => 270,
        ]);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];
        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';
        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];
        $now = Carbon::now(Timezone::IST);
        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->mockPartnerSubMtuDatalakeQuery($partner->getId());

        $this->startTest($testData);

        // calling generate invoice twice should still create only one invoice
        $this->startTest($testData);

        $invoices = $this->getDbEntities('commission_invoice');
        $this->assertCount(1, $invoices);

        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');
        $invoiceExpectedData = [
            'merchant_id'   => 'DefaultPartner',
            'month'         => $now->month,
            'year'          => $now->year,
            'status'        => 'issued',
            'gross_amount'  => 1770,
            'tax_amount'    => 270,
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());

        $lineItemExpectedData = [
            [
                'amount'        => 1770,
                'gross_amount'  => 1770,
                'tax_amount'    => 270,
                'net_amount'    => 1770,
                'tax_inclusive' => true,
            ]
        ];
        $this->assertArraySelectiveEquals($lineItemExpectedData, $invoice->lineItems->toArray());

        $this->fixtures->merchant->addFeatures('automated_comm_payout', $partner->getId());
        $this->mockAutoApprovalFinanceExp($partner->getId());
        $testData = $this->testData['testInvoiceAction'];

        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();

        $this->ba->proxyAuth('rzp_test_' . $partner->getId());

        $this->runRequestResponseFlow($testData);

        $invoice = $this->getDbLastEntity('commission_invoice');

        $this->assertEquals('processed', $invoice['status']);
        Mail::assertNotSent(CommissionOpsInvoice::class);
        Mail::assertNotSent(CommissionInvoice::class);

        $testData = $this->testData['testInvoiceFetchAfterAutoApproved'];
        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();
        $this->runRequestResponseFlow($testData);
    }

    /**
     * Check that when invoice gross amount is greater than Entity::MAX_AUTO_APPROVAL_AMOUNT,
     * invoice goes to under_review status after partner approves the invoice.
     */
    public function testInvoiceMarkUnderReviewWhenExpEnabled()
    {
        Mail::fake();

        $grossAmount = ( Invoice\Entity::MAX_AUTO_APPROVAL_AMOUNT + 100 );
        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => $grossAmount,
            'debit'  => 0,
            'fee'    => $grossAmount,
            'tax'    => 762727,
        ]);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];
        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';
        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];
        $now = Carbon::now(Timezone::IST);
        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->mockPartnerSubMtuDatalakeQuery($partner->getId());

        $this->startTest($testData);

        // calling generate invoice twice should still create only one invoice
        $this->startTest($testData);
        $invoices = $this->getDbEntities('commission_invoice');
        $this->assertCount(1, $invoices);

        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');
        $invoiceExpectedData = [
            'merchant_id'   => 'DefaultPartner',
            'month'         => $now->month,
            'year'          => $now->year,
            'status'        => 'issued',
            'gross_amount'  => $grossAmount,
            'tax_amount'    => 762727,
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());

        $lineItemExpectedData = [
            [
                'amount'        => $grossAmount,
                'gross_amount'  => $grossAmount,
                'tax_amount'    => 762727,
                'net_amount'    => $grossAmount,
                'tax_inclusive' => true,
            ]
        ];
        $this->assertArraySelectiveEquals($lineItemExpectedData, $invoice->lineItems->toArray());

        $this->fixtures->merchant->addFeatures('automated_comm_payout', Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->mockAutoApprovalFinanceExp($partner->getId());

        $testData = $this->testData['testInvoiceAction'];
        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();
        $this->ba->proxyAuth('rzp_test_' . $partner->getId());
        $this->runRequestResponseFlow($testData);

        $invoice = $this->getDbLastEntity('commission_invoice');
        $this->assertEquals('under_review', $invoice['status']);

        $app = App::getFacadeRoot();
        $app['workflow']->setMethod('DELETE');
        $testData = $this->testData['testInvoiceActionApproved'];
        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();
        $this->ba->proxyAuth('rzp_test_' . $partner->getId());
        $this->runRequestResponseFlow($testData);

        // Check that when the CommissionTdsSettlement job is triggered,
        // the dirty data set in the workflow singleton should be reset or get updated as per the job flow.
        // Taking an example of workflow data gets resetted as the HTTP method wouldn't be DELETE in the flow above
        $this->assertFalse($app['workflow']->getMethod() === 'DELETE');

        $invoice = $this->getDbLastEntity('commission_invoice');
        $this->assertEquals('processed', $invoice['status']);
    }

    /**
     * Check that when invoice created after dec-2022,
     * invoice should be auto approved
     */

    public function testInvoiceFinanceAutoApproveMarkProcessedForInvoiceGeneratedOnDec()
    {
        Mail::fake();

        $grossAmount = ( Invoice\Entity::MAX_AUTO_APPROVAL_AMOUNT - 100 );
        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => $grossAmount,
            'debit'  => 0,
            'fee'    => $grossAmount,
            'tax'    => 762697,
        ]);


        $this->ba->adminAuth();
        $now = Carbon::now(Timezone::IST);
        $testData = $this->testData['testCaptureCommission'];
        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';
        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];
        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->mockPartnerSubMtuDatalakeQuery($partner->getId());

        $this->startTest($testData);

        // calling generate invoice twice should still create only one invoice
        $this->startTest($testData);
        $invoices = $this->getDbEntities('commission_invoice');
        $this->assertCount(1, $invoices);

        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');
        $invoiceExpectedData = [
            'merchant_id'   => 'DefaultPartner',
            'month'         => $now->month,
            'year'          => $now->year,
            'status'        => 'issued',
            'gross_amount'  => $grossAmount,
            'tax_amount'    => 762697,
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());

        $lineItemExpectedData = [
            [
                'amount'        => $grossAmount,
                'gross_amount'  => $grossAmount,
                'tax_amount'    => 762697,
                'net_amount'    => $grossAmount,
                'tax_inclusive' => true,
            ]
        ];
        $this->assertArraySelectiveEquals($lineItemExpectedData, $invoice->lineItems->toArray());

        $this->fixtures->merchant->addFeatures('automated_comm_payout', Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->mockAutoApprovalFinanceExp($partner->getId());

        $testData = $this->testData['testInvoiceAction'];
        $testData['request']['url'] = '/commissions/invoice/' . $invoice->getId();
        $this->ba->proxyAuth('rzp_test_' . $partner->getId());
        $this->runRequestResponseFlow($testData);

        $invoice = $this->getDbLastEntity('commission_invoice');
        $this->assertEquals('processed', $invoice['status']);

    }

    public function testMigrateInvoiceBucketByInvoiceId()
    {
        $invoice = $this->createCommissionInvoice();

        // creating a invoice file with bucket name- rzp-test-bucket and region- us-east-1

        $this->fixtures->create('file_store', [
            'id'            => '100000Razorpay',
            'type'          => 'commission_invoice',
            'entity_id'     => $invoice->getId(),
            'entity_type'   => 'commission_invoice',
            'extension'     => 'pdf',
            'name'          => 'xyz.pdf',
            'bucket'        => 'rzp-test-bucket',
            'region'        => 'us-east-1'
        ]);

        (new Service())->updateFileBucketAndRegion([
            'invoice_ids' => [
                $invoice->getId()
            ],
            'merchant_ids'  => [],
            'bucket_config' => [
                'name'      => 'rzp-1012-nonprod-test-bucket',
                'region'    => 'ap-south-1'
            ]
        ]);

        $file = $this->getDbLastEntity('file_store');

        $this->assertEquals('rzp-1012-nonprod-test-bucket', $file->getBucket());
    }

    public function testMigrateAllInvoiceBucket()
    {
        $invoice = $this->createCommissionInvoice();

        $this->fixtures->create('file_store', [
            'id'            => '100000Razorpay',
            'type'          => 'commission_invoice',
            'entity_id'     => $invoice->getId(),
            'entity_type'   => 'commission_invoice',
            'extension'     => 'pdf',
            'name'          => 'xyz.pdf',
            'bucket'        => 'rzp-test-bucket',
            'region'        => 'us-east-1'
        ]);

        (new Service())->updateFileBucketAndRegion([
            'invoice_ids' => [],
            'merchant_ids'  => [],
            'bucket_config' => [
                'name'      => 'rzp-1012-nonprod-test-bucket',
                'region'    => 'ap-south-1'
            ]
        ]);

        $file = $this->getDbEntityById('file_store','100000Razorpay' );

        $this->assertEquals('rzp-1012-nonprod-test-bucket',$file->getBucket());
    }

    public function testMigrateInvoiceBucketByLimit()
    {
        $invoice = $this->createCommissionInvoice();

        $this->fixtures->create('file_store', [
            'id'            => '100000Razorpay',
            'type'          => 'commission_invoice',
            'entity_id'     => $invoice->getId(),
            'entity_type'   => 'commission_invoice',
            'extension'     => 'pdf',
            'name'          => 'xyz.pdf',
            'bucket'        => 'invoices',
            'region'        => 'us-east-1'
        ]);

        (new Service())->updateFileBucketAndRegion([
            'invoice_ids' => [],
            'merchant_ids'  => [],
            'total_count'   => 0,
            'bucket_config' => [
                'name'      => 'rzp-1012-nonprod-test-bucket',
                'region'    => 'ap-south-1'
            ]
        ]);

        $file = $this->getDbEntityById('file_store','100000Razorpay' );

        $this->assertEquals('invoices',$file->getBucket());
    }

    public function testCaptureCommissionWithCommissionSyncEnabled()
    {
        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission();

        $this->mockAllSplitzTreatment();

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);
    }

    public function testCaptureCommissionWithCommissionSyncDisabled()
    {
        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission();

        $outbox = \Mockery::mock(Outbox::class);

        $this->app->instance('outbox', $outbox);

        $outbox->shouldReceive('send')->andReturn();

        $this->mockSplitzTreatment([
                                       "experiment_id" => "Jrx3ffnqavDK7U",
                                       "id" => "DefaultPartner",
                                   ], []);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);

        // should not send outbox event with feature disabled
        $outbox->shouldNotHaveReceived('send');
    }

    public function createCommissionInvoice()
    {
        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => 1770,
            'debit'  => 0,
            'fee'    => 1770,
            'tax'    => 270,
        ]);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->mockPartnerSubMtuDatalakeQuery($partner->getId());

        $this->startTest($testData);

        return  $this->getDbLastEntity('commission_invoice');
    }

    public function testInvoiceGenerateForLineItemsLessThanRupee()
    {
        Mail::fake();

        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => 17,
            'debit'  => 0,
            'fee'    => 17,
            'tax'    => 2,
        ]);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->mockPartnerSubMtuDatalakeQuery($partner->getId());

        $this->runRequestResponseFlow($testData);

        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');

        $this->assertNull($invoice);
    }

    public function testCaptureCommission()
    {
        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission();

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';

        $this->runRequestResponseFlow($testData);
    }

    public function testFetchCommissionConfigByPayment()
    {
        $this->createPurePlatFormMerchantAndSubMerchant();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID);

        $paymentAttributes = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'amount'      => 1000,
            'fee'         => 4
        ];

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->createEntityOrigin('payment', $payment->getId());

        $this->ba->partnershipServiceAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/commission_configs?payment_id='.$payment->getId();

        $this->runRequestResponseFlow($testData);
    }

    public function testFetchCommissionConfigsWithInvalidPayment()
    {
        $this->ba->partnershipServiceAuth();

        $this->startTest();
    }

    public function testPartnerFetchWithCommissionInvoiceFeature()
    {
        $this->fixtures->merchant->addFeatures('generate_partner_invoice', Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->addFeatures('generate_partner_invoice', Constants::DEFAULT_MERCHANT_ID);

         $this->fixtures->merchant->addFeatures('generate_partner_invoice', Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->ba->partnershipServiceAuth();

        $this->startTest();
    }

    public function testPartnerFetchWithCommissionInvoiceFeatureWithOffset()
    {
        $this->fixtures->merchant->addFeatures('generate_partner_invoice', Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->fixtures->merchant->addFeatures('generate_partner_invoice', Constants::DEFAULT_MERCHANT_ID);

         $this->fixtures->merchant->addFeatures('generate_partner_invoice', Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID);

        $this->ba->partnershipServiceAuth();

        $this->startTest();
    }

    public function testFetchCommissionConfigByPaymentForMerchant()
    {
        $this->createPurePlatFormMerchantAndSubMerchant();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID);

        $paymentAttributes = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'amount'      => 1000,
            'fee'         => 4
        ];

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->ba->partnershipServiceAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/commission_configs?payment_id='.$payment->getId();

        $this->runRequestResponseFlow($testData);
    }

    public function testCaptureCommissionByPartner()
    {
        list($partner) = $this->createSampleCommission();

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/commissions/partner/'.$partner->getId().'/capture';

        $this->runRequestResponseFlow($testData);
    }

    public function testBulkCaptureByPartner()
    {
        list($partner) = $this->createSampleCommission();

        $this->createSampleCommission(
            ['id' => 'SampleMerchant'],
            ['id' => 'SampleAppIdOne'],
            ['id' => 'SubmerchantOne']);

        $this->ba->adminAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['partner_ids'] = [$partner->getId(), 'SampleMerchant'];

        $this->runRequestResponseFlow($testData);
    }

    public function testBulkCaptureByPartnerInvalidInput()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testImplicitFixedOnPaymentCapture()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'implicit_plan_id'    => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ]);

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);

        $commissionComponent = $this->getDbEntity('commission_component');

        $this->assertEquals($commission[Commission\Entity::ID], $commissionComponent->getCommissionId());

        $this->assertEquals($commission[Commission\Entity::FEE] - $commission[Commission\Entity::TAX], $commissionComponent->getCommissionPricingAmount());
    }

    /**
     * checks that explicit commission is created on capture along with fee break up
     */
    public function testExplicitOnPaymentCapture()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);

        $commissionComponent = $this->getDbEntity('commission_component');

        $this->assertEquals($commission[Commission\Entity::ID], $commissionComponent->getCommissionId());

        $this->assertEquals($commission[Commission\Entity::FEE] - $commission[Commission\Entity::TAX], $commissionComponent->getCommissionPricingAmount());
    }

    /**
     * checks that explicit commission is created on capture along with fee break up for international payments
     */
    public function testExplicitOnInternationalPayment()
    {
        list($application) = $this->createPurePlatFormMerchantAndSubMerchant();

        $client = $this->getAppClientByEnv($application);

        $this->generateOAuthAccessTokenForClient(
            [
                'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                'scopes' => ['read_write'],
            ],
            $client);

        $this->ba->oauthPublicTokenAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['amount']   = 4000;
        $payment['currency'] = 'USD';

        $this->fixtures->merchant->edit(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID, ['convert_currency' => 1]);

        $response = $this->doAuthPaymentOAuth($payment);

        $payment = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->setSubmerchantPrivateAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['amount'] = $payment->getAmount();
        $testData['request']['content']['currency'] = $payment->getCurrency();

        $testData['request']['url'] = '/payments/'.$response['razorpay_payment_id'].'/capture';

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);
    }

    /**
     * checks that explicit commission and fee break up are not created on capture but commission entity is created
     */
    public function testExplicitForRecordOnly()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'    => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ]);

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT);

        $feeBreakups = $this->getExplicitCommissionFeeBreakup($payment);

        $this->assertTrue($feeBreakups->isEmpty());

        $this->assertTrue($commission['record_only']);
    }

    /**
     * checks that capture works fine even when there is a missing rule when calculating explicit commission
     */
    public function testExplicitPricingRuleAbsent()
    {
        $testData = $this->setUpCommissionCreate();

        $this->fixtures->create('pricing', [
            'plan_id'      => '180PartnerPlan',
            'percent_rate' => '180',
            'feature'      => 'transfer', // no rule for payment
        ]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => '180PartnerPlan',
                'explicit_should_charge' => 1,
            ]);

        $this->startTest($testData);

        $payment = $this->getLastEntity('payment', true);

        $commissions = $this->getCommissionsForSourceEntity($payment['id'])->toArray();

        $this->assertCount(0, $commissions);

        $feeBreakups = $this->getExplicitCommissionFeeBreakup($payment);

        $this->assertTrue($feeBreakups->isEmpty());
    }

    /**
     * checks that both implicit and explicit commissions are created
     * if both implicit and explicit plans are present
     */
    public function testImplicitVariableAndExplicit()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'implicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->startTest($testData);

        list($payment, $implicitCommission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT, 2);

        list($payment, $explicitCommission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT, 2);

        $commissionComponents = $this->getDbEntities("commission_component");

        $this->assertEquals(2, count($commissionComponents));

        $implicitCommissionComponent = $commissionComponents->filter(function($component) use ($implicitCommission) {
            return ($component->getCommissionId() === $implicitCommission[Commission\Entity::ID]);
        })->first();

        $explicitCommissionComponent =  $commissionComponents->filter(function($component) use ($explicitCommission) {
            return ($component->getCommissionId() === $explicitCommission[Commission\Entity::ID]);
        })->first();

        $this->assertEquals($implicitCommission[Commission\Entity::FEE] - $implicitCommission[Commission\Entity::TAX], $implicitCommissionComponent->getCommissionPricingAmount());

        $this->assertEquals($explicitCommission[Commission\Entity::FEE] - $explicitCommission[Commission\Entity::TAX], $explicitCommissionComponent->getCommissionPricingAmount());

        $this->assertExplicitCommissionFeeBreakUp($payment, $explicitCommission);
    }

    /**
     * Assert that payment is getting created using fees fetched for customer bearer merchant
     * which includes explicit partner charges when both fees fetch and payment create are on bearer auth
     */
    public function testCustomerBearerPaymentCreateBearerAuth()
    {
        list($application) = $this->createPurePlatFormMerchantAndSubMerchant();

        $client = $this->getAppClientByEnv($application);

        $this->generateOAuthAccessTokenForClient(
            [
                'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                'scopes' => ['read_write'],
            ],
            $client);

        $this->ba->oauthPublicTokenAuth();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);

        $this->fixtures->merchant->edit(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'fee_bearer' => 'customer',
            ]);

       $this->fixtures->pricing->editDefaultCommissionPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->fixtures->pricing->editTwoPercentPricingPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $requestData = $this->testData['testCustomerBearerExplicitBearerAuth'];

        $feesData = $this->runRequestResponseFlow($requestData);

        $payment = $this->getDefaultPaymentArray();
        $payment['fee']    = $feesData['display']['fees'] * 100;
        $payment['amount'] = $feesData['display']['amount'] * 100;

        $response = $this->doAuthPaymentOAuth($payment);

        $paymentEntity = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

        $this->assertEquals($payment['fee'], $paymentEntity->getFee());
    }

    /**
     * Asserts that the commission must not be added since the request is not through the partner / bearer auth
     */
    public function testCustomerBearerExplicitPublicAuth()
    {
        list($partner, $app) = $this->createPartnerAndApplication();

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $this->fixtures->merchant->edit($subMerchant->getId(),
            [
                'fee_bearer' => 'customer',
            ]);

        $this->createConfigForPartnerApp(
            $app->getId(),
            $subMerchant->getId(),
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->fixtures->pricing->editDefaultPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->setSubmerchantPublicAuth($subMerchant->getId());

        $this->startTest();
    }

    /**
     * Check that when payment fees is fetched using bearer auth and includes commission fees
     * then payment authorization fails if for payment create, merchant auth is used
     */
    public function testCustomerBearerPaymentCreateBearerAndPublicAuth()
    {
        list($application) = $this->createPurePlatFormMerchantAndSubMerchant();

        $client = $this->getAppClientByEnv($application);

        $this->generateOAuthAccessTokenForClient(
            [
                'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                'scopes' => ['read_write'],
            ],
            $client);

        $this->ba->oauthPublicTokenAuth();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];

        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);

        $this->fixtures->merchant->edit(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'fee_bearer' => 'customer',
            ]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->fixtures->pricing->editTwoPercentPricingPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->fixtures->pricing->editDefaultCommissionPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $requestData = $this->testData['testCustomerBearerExplicitBearerAuth'];

        $feesData = $this->runRequestResponseFlow($requestData);

        $payment = $this->getDefaultPaymentArray();
        $payment['fee']    = $feesData['display']['fees'] * 100;
        $payment['amount'] = $feesData['display']['amount'] * 100;

        $key = $this->fixtures->create('key', ['merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID]);

        $key = 'rzp_test_' . $key->getKey();

        $this->app->forgetInstance('basicauth');

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment, $key)
        {
            $this->doAuthPayment($payment, null, $key);
        });
    }

    /**
     * Asserts that explicit commission gets created when customer bearer payment is captured
     */
    public function testCustomerBearerExplicitOnPaymentCapture()
    {
        $this->createPurePlatFormMerchantAndSubMerchant();

        $this->fixtures->merchant->edit(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'fee_bearer' => 'customer',
            ]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $paymentAttributes = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'amount'      => (4000 * 100 + (4000 * 2) + (4000 * 2 * 18 / 100) + (4000 * 0.2) + (4000 * 0.2 * 18 / 100)),
            'fee'         => ((4000 * 2) + (4000 * 2 * 18 / 100) + (4000 * 0.2) + (4000 * 0.2 * 18 / 100)),
            'fee_bearer'  => FeeBearer::CUSTOMER,
        ];

        $this->fixtures->pricing->editTwoPercentPricingPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->fixtures->pricing->editDefaultCommissionPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->createEntityOrigin('payment', $payment->getId());

        $this->setSubmerchantPrivateAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['amount'] = 400000;

        $testData['request']['url'] = '/payments/' . $payment->getPublicId() . '/capture';

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);
    }

    /**
     * Checks that capture works on an already authorized payment even after editing the explicit pricing plan.
     * Also checks that commission and fee break ups are created for explicit commission
     */
    public function testCustomerBearerOnExistingAuthorizedPayment()
    {
        $this->createPurePlatFormMerchantAndSubMerchant();

        $this->fixtures->merchant->edit(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'fee_bearer' => 'customer',
            ]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $paymentAttributes = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'amount'      => (4000 * 100 + (4000 * 2) + (4000 * 2 * 18 / 100) + (4000 * 0.2) + (4000 * 0.2 * 18 / 100)),
            'fee'         => ((4000 * 2) + (4000 * 2 * 18 / 100) + (4000 * 0.2) + (4000 * 0.2 * 18 / 100)),
            'fee_bearer'  => FeeBearer::CUSTOMER,
        ];

        $this->fixtures->pricing->editTwoPercentPricingPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $this->fixtures->pricing->editDefaultCommissionPlan(['fee_bearer' => FeeBearer::CUSTOMER]);

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->createEntityOrigin('payment', $payment->getId());

        $this->setSubmerchantPrivateAuth();

        $this->fixtures->pricing->edit('C6rNP4gZXcnZWM',
            [
                'percent_rate' => 10,
            ]);

        $this->fixtures->pricing->edit('C6rNP7QE0mIzpW',
            [
                'percent_rate' => 10,
            ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['amount'] = 400000;

        $testData['request']['url'] = '/payments/' . $payment->getPublicId() . '/capture';

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);
    }

    public function testImplicitVariableAndExplicitForSubvention()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'commission_model'       => Config\CommissionModel::SUBVENTION,
                'implicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->startTest($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT, 2);

        $this->assertEquals(Config\CommissionModel::SUBVENTION, $commission['model']);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT, 2);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);

        $commissionComponents = $this->getDbEntities("commission_component");

        $this->assertEquals(2, count($commissionComponents));
    }

    /**
     * checks that both implicit and explicit commissions are created
     * if both implicit and explicit plans are present and the fee model is postpaid
     */
    public function testImplicitVariableAndExplicitPostpaid()
    {
        $testData = $this->setUpCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'implicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
            ]);

        $this->setPostpaidFeeModel(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID);

        $this->startTest($testData);

        $this->assertAndGetCommissionByType(CommissionType::IMPLICIT, 2);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT, 2);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);

        $commissionComponents = $this->getDbEntities("commission_component");

        $this->assertEquals(2, count($commissionComponents));
    }

    /**
     * checks that tax is charged on commissions even when tax is not charged on merchant fee
     * when payment less than 2k
     */
    public function testGSTForPaymentsLessThan2K()
    {
        $testData = $this->setUpCommissionCreate(['amount' => 1000 * 100]);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 1,
                'implicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ]);

        $this->startTest($testData);

        $this->assertAndGetCommissionByType(CommissionType::IMPLICIT, 2);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::EXPLICIT, 2);

        $this->assertExplicitCommissionFeeBreakUp($payment, $commission);
    }

    protected function setUpCommissionCreate($paymentAttributes = [])
    {
        $this->createPurePlatFormMerchantAndSubMerchant();

        $this->createImplicitPricingPlan();

        $defaultPaymentAttributes = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'amount'      => 4000 * 100,
        ];

        $paymentAttributes = array_merge($defaultPaymentAttributes, $paymentAttributes);

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->createEntityOrigin('payment', $payment->getId());

        $this->setSubmerchantPrivateAuth();

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $testData['request']['content']['amount'] = $payment->getAmount();

        $testData['request']['url'] = '/payments/' . $payment->getPublicId() . '/capture';

        return $testData;
    }

    protected function setupCommissionCreateForMalaysianMerchant($paymentAttributes = [])
    {
        $this->createAggregatorMalaysianMerchantAndSubMerchant();

        $this->createImplicitPricingPlanWithOrgId(Constants::DEFAULT_CURLEC_IMPLICIT_PRICING_PLAN, Org::CURLEC_ORG);

        $defaultPaymentAttributes = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'amount'      => 4000 * 100,
        ];

        $paymentAttributes = array_merge($defaultPaymentAttributes, $paymentAttributes);

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->createEntityOrigin('payment', $payment->getId());

        $this->setSubmerchantPrivateAuth();

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $testData['request']['content']['amount'] = $payment->getAmount();

        $testData['request']['url'] = '/payments/' . $payment->getPublicId() . '/capture';

        return $testData;
    }

    /**
     * set up all the entities required in commission creation flow
     * Add 3 transacting sub merchants for the month to enable invoice creation
     */
    protected function setUpCommissionCreateWith3MTU($paymentAttributes = [])
    {
        [$app, $accessMap, $partner] = $this->createPurePlatFormMerchantAndSubMerchant();

        for($i =0; $i < Invoice\Constants::GENERATE_INVOICE_MIN_SUB_MTU_COUNT; $i++)
        {
            $subMerchantAttributes['id'] = random_alphanum_string(14);
            list($subMerchant) = $this->createSubMerchant($partner, $app, $subMerchantAttributes);
            $this->createPaymentEntities(1, $subMerchantAttributes['id'],Carbon::today(Timezone::IST));
        }
        $this->createImplicitPricingPlan();

        $defaultPaymentAttributes = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'amount'      => 4000 * 100,
        ];

        $paymentAttributes = array_merge($defaultPaymentAttributes, $paymentAttributes);

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $this->createEntityOrigin('payment', $payment->getId());

        $this->setSubmerchantPrivateAuth();

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $testData['request']['content']['amount'] = $payment->getAmount();

        $testData['request']['url'] = '/payments/' . $payment->getPublicId() . '/capture';

        return $testData;
    }

    protected function assertAndGetCommissionByType(string $type, int $totalCount = 1)
    {
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        $commissions = $this->getCommissionsForSourceEntity($payment['id'])->toArray();

        $this->assertCount($totalCount, $commissions);

        $commissionByType = null;

        foreach ($commissions as $commission)
        {
            if ($commission['type'] === $type)
            {
                $commissionByType = $commission;
                break;
            }
        }

        $this->assertNotEmpty($commissionByType);

        $this->assertTransactionData($commissionByType);

        if ($type === CommissionType::IMPLICIT)
        {
            $this->assertFalse($commissionByType['record_only']);
        }
        else
        {
            // explicit commission can never be of subvention model
            $this->assertEquals(Config\CommissionModel::COMMISSION, $commissionByType['model']);
        }

        return [$payment, $commissionByType];
    }

    protected function assertCommisionAndTransactionData(string $type, int $totalCount = 1)
    {
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        $commissions = $this->getCommissionsForSourceEntity($payment['id'])->toArray();

        $this->assertCount($totalCount, $commissions);

        $commissionByType = null;

        foreach ($commissions as $commission)
        {
            if ($commission['type'] === $type)
            {
                $commissionByType = $commission;
                break;
            }
        }

        $this->assertNotEmpty($commissionByType);

        $transaction = $this->getDbEntityById('transaction', $commissionByType['transaction_id']);

        $this->assertEquals(Channel::RHB, $transaction->getChannel());
    }

    protected function assertTransactionData(array $commission)
    {
        if (($commission['record_only'] === true) or ($commission['model'] === Config\CommissionModel::SUBVENTION))
        {
            return;
        }

        $transaction = $this->getDbEntityById('transaction', $commission['transaction_id']);
        $this->assertEquals($commission['credit'], $transaction->getCredit());
        $this->assertEquals($commission['credit'], $transaction->getAmount());

        $this->assertEquals(0, $transaction->getFee());
        $this->assertEquals(0, $transaction->getTax());

        $this->assertTrue($transaction->isOnHold());

        // channel should always be yes_bank for commission settlement
        $this->assertEquals(Channel::YESBANK, $transaction->getChannel());
    }

    protected function assertExplicitCommissionFeeBreakUp($payment, $commission)
    {
        $feeBreakups = $this->getExplicitCommissionFeeBreakup($payment);

        $this->assertNotEmpty($feeBreakups);

        $totalFee = 0;
        $totalTax = 0;

        foreach ($feeBreakups as $breakup)
        {
            $totalFee += $breakup->getAmount();

            if ($breakup->getName() === CommissionConstants::COMMISSION_BREAK_UP_PREFIX . 'tax')
            {
                $totalTax += $breakup->getAmount();
            }
        }

        $this->assertEquals($totalFee, $commission['fee']);
        $this->assertEquals($totalTax, $commission['tax']);

        $this->assertFalse($commission['record_only']);
    }

    protected function checkClearOnHoldAndSettlement($commission, $tdsPercentage)
    {
        $testData = $this->testData['testClearOnHoldForCommission'];

        $testData['request']['url'] = '/commissions/partner/'.$commission['partner_id'].'/on_hold/clear';

        $this->runRequestResponseFlow($testData);

        // check that adjustment is created for tds
        $tdsAdjustment = $this->getDbLastEntity('adjustment');

        $baseCommission = $commission['credit'] - $commission['tax'];

        $tds = $this->getFeeWithoutTax($baseCommission, $tdsPercentage);

        $this->assertEquals(-1 * $tds, $tdsAdjustment['amount']);
        $this->assertEquals(Channel::YESBANK, $tdsAdjustment['channel']);

        // check adjustment transaction data
        $tdsTransaction = $this->getDbLastEntity('transaction');

        $this->assertEquals($tds, $tdsTransaction->getDebit());
        $this->assertEquals('adjustment', $tdsTransaction->getType());
        $this->assertEquals(Channel::YESBANK, $tdsTransaction->getChannel());

        // get commission transactions and verify on hold flag is cleared
        $commTransaction = $this->getDbEntityById('transaction', $commission['transaction_id']);
        $this->assertEquals(0, $commTransaction->getOnHold());

        // trigger settlement on this commission

        $merchantDetailAttributes = [
            Entity::ACTIVATION_STATUS  => Partner\Activation\Constants::ACTIVATED,
            Entity::LOCKED             => false,
            Entity::SUBMITTED          => true,
        ];

        $this->fixtures->merchant_detail->edit(Constants::DEFAULT_PLATFORM_MERCHANT_ID, $merchantDetailAttributes);

        $this->ba->cronAuth();

        Carbon::setTestNow(Holidays::getNthWorkingDayFrom(Carbon::now(), 5)->addHour(10));

        $testData = $this->testData['testInitiateCommissionSettlement'];

        $this->runRequestResponseFlow($testData);

        // check that settlement transaction is created
        $settlementTransaction = $this->getDbLastEntity('transaction');

        $this->assertEquals('settlement', $settlementTransaction->getType());
        $this->assertEquals(Channel::YESBANK, $settlementTransaction->getChannel());

        $this->assertEquals($commission['credit'] - $tds, $settlementTransaction->getAmount());

        $this->initiateTransfer(Channel::YESBANK, 'settlement', 'settlement');

        // check that fund transfer attempt is created
        $attempt = $this->getDbLastEntity('fund_transfer_attempt');
        $this->assertEquals('NEFT', $attempt->getMode());
    }


    private function createQrCode($subMerchantId, $clientId, $clientSecret)
    {
        $this->ba->partnerAuth($subMerchantId, 'rzp_test_partner_' . $clientId, $clientSecret);

        $QrCode =  $this->makeRequestAndGetContent($this->testData['createBharatQrCode']);

        $this->fixtures->stripSign($QrCode['id']);

        $this->ba->deleteAccountAuth();

        return $QrCode;
    }

    /**
     * This testcase validates the following,
     *    (in this we create non virtual account qrcode with partner auth
     *          and payment from qrcode)
     *
     * 1. If payment is created for non virtual account qrcode from partner auth with
     *      entity origin as partner application
     * 2. validates if commission is generated and belongs to made payment
     * 3. verifies if the partner to which commission is granted is same as partner who created qrcode
     */
    public function testCreateCommissionForBharatQrCode()
    {
        $partnerId = Constants::DEFAULT_MERCHANT_ID;
        $subMerchantId = Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;

        $client = $this->setUpNonPurePlatformPartnerAndSubmerchant($partnerId, $subMerchantId);

        $this->enableVirtualAccountQrcodeAndMethods($subMerchantId,$client->getApplicationId());
        $response = $this->createQrCode($subMerchantId,$client->getId(),$client->getSecret());

        // set up implict pricing plan
        $this->fixtures->pricing->createUpiTransferPricingPlan([
                                                                   'plan_id' => Constants::DEFAULT_SUBMERCHANT_PRICING_PLAN,
                                                                   'percent_rate' => 200,
                                                                   'receiver_type' => 'qr_code',
                                                               ]);
        $this->fixtures->pricing->createUpiTransferPricingPlan([
                                                                   'plan_id' => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
                                                                   'percent_rate' => 100,
                                                                   'receiver_type' => 'qr_code',
                                                               ]);
        $this->createConfigForPartnerApp($client->getApplicationId(), null, [
            'implicit_plan_id' => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
        ]);
        // create payment for qr code
        $qrPaymentAttributes = $this->testData['createBharatQrCodePayment'];
        $qrPaymentAttributes['content']['merchantTranId'] =  $response['id'] . 'qrv2';
        $qrPaymentAttributes['raw'] = $this->getMockServer('upi_icici')->getAsyncCallbackContentForBharatQr($qrPaymentAttributes['content']);
        $this->makeRequestAndGetContent($qrPaymentAttributes);

        // entity origin should be application
        $payment = $this->getDbLastEntity('payment');
        $paymentEntityOrigin = $this->getDbEntity('entity_origin', ['entity_id' => $payment['id']]);
        $this->assertEquals('application', $paymentEntityOrigin['origin_type']);

        $commission = $this->getDbLastEntity('commission');
        // assert commission for payment id
        $this->assertEquals($commission['source_id'], $payment['id']);
        $this->assertEquals('captured', $commission['status']);
        $this->assertEquals($partnerId, $commission['partner_id']);
    }

    public function testInvoiceCreateWithAutoApproval()
    {
        $testData = $this->setUpCommissionCreateWith3MTU();
        $merchant = ['partner_type' => 'reseller'];
        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID];
        $partnerActivation = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,'activation_status' => 'activated'];
        $testData = $this->setupForInvoiceAutoApproval($testData, $merchantDetail, $partnerActivation, $merchant);

        $now = Carbon::now(Timezone::IST);

        $this->mockPartnerInvoiceAutoApproval(Constants::DEFAULT_PLATFORM_MERCHANT_ID, $now->year, 'enable');
        $this->mockAutoApprovalFinanceExp(Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        // check that invoice is created
        $invoice = $this->getDbLastEntity('commission_invoice');

        $invoiceExpectedData = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'month' => $now->month,
            'year' => $now->year,
            'status' => 'processed',
            'gross_amount' => 800
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());
    }

    public function testInvoiceCreateWithAutoApprovalDisabled()
    {
        $testData = $this->setUpCommissionCreateWith3MTU();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID];
        $partnerActivation = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,'activation_status' => 'activated'];
        $testData = $this->setupForInvoiceAutoApproval($testData, $merchantDetail, $partnerActivation);

        $now = Carbon::now(Timezone::IST);

        $this->mockPartnerInvoiceAutoApproval(Constants::DEFAULT_PLATFORM_MERCHANT_ID, $now->year, 'enable');
        $this->fixtures->merchant->addFeatures('auto_comm_inv_disabled', Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        // check that invoice is created
        $invoice = $this->getDbLastEntity('commission_invoice');

        $invoiceExpectedData = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'month' => $now->month,
            'year' => $now->year,
            'status' => 'issued',
            'gross_amount' => 800
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());
    }

    public function testInvoiceCreateAutoApprovalFailedGSTINPresent()
    {
        $testData = $this->setUpCommissionCreateWith3MTU();

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];
        $partnerActivation = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,'activation_status' => 'activated'];
        $testData = $this->setupForInvoiceAutoApproval($testData, $merchantDetail, $partnerActivation);

        $now = Carbon::now(Timezone::IST);

        $this->mockPartnerInvoiceAutoApproval(Constants::DEFAULT_PLATFORM_MERCHANT_ID, $now->year, 'enable');
        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        // check that invoice is created
        $invoice = $this->getDbLastEntity('commission_invoice');

        $invoiceExpectedData = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'month' => $now->month,
            'year' => $now->year,
            'status' => 'issued',
            'gross_amount' => 944,
            'tax_amount' => 144,
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());
    }

    public function testInvoiceCreateAutoApprovalWithGSTINPresentResellerFailed()
    {
        $testData = $this->setUpCommissionCreateWith3MTU();

        // update partner type to reseller
        $this->fixtures->on(Mode::TEST)->edit('merchant', Constants::DEFAULT_PLATFORM_MERCHANT_ID, ['partner_type'=> 'reseller']);

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];
        $partnerActivation = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,'activation_status' => 'activated'];
        $testData = $this->setupForInvoiceAutoApproval($testData, $merchantDetail, $partnerActivation);
        $testData['request']['content']['month'] = 4;
        $commission = $this->getLastEntity('commission', true);

        $this->fixtures->edit('commission', $commission['id'], ['created_at'=> Carbon::createFromDate(2023,4,3,Timezone::IST)->timestamp]);

        $now = Carbon::now(Timezone::IST);

        $this->mockPartnerInvoiceAutoApproval(Constants::DEFAULT_PLATFORM_MERCHANT_ID, 2023, 'enable');
        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        // check that invoice is created
        $invoice = $this->getDbLastEntity('commission_invoice');

        $invoiceExpectedData = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'month' => 4,
            'year' => 2023,
            'status' => 'issued',
            'gross_amount' => 944,
            'tax_amount' => 144,
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());
    }

    public function testInvoiceCreateAutoApprovalWithGSTINPresentResellerSuccess()
    {
        $testData = $this->setUpCommissionCreateWith3MTU();

        // update partner type to reseller
        $this->fixtures->on(Mode::TEST)->edit('merchant', Constants::DEFAULT_PLATFORM_MERCHANT_ID, ['partner_type'=> 'reseller']);

        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'gstin' => '27APIPM9598J1ZW'];
        $partnerActivation = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,'activation_status' => 'activated'];
        $testData = $this->setupForInvoiceAutoApproval($testData, $merchantDetail, $partnerActivation);
        $testData['request']['content']['month'] = 5;
        $commission = $this->getLastEntity('commission', true);

        $this->fixtures->edit('commission', $commission['id'], ['created_at'=> Carbon::createFromDate(2023,5,3,Timezone::IST)->timestamp]);

        $now = Carbon::now(Timezone::IST);

        $this->mockPartnerInvoiceAutoApproval(Constants::DEFAULT_PLATFORM_MERCHANT_ID, 2023, 'enable');
        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->mockAutoApprovalFinanceExp(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);


        // check that invoice is created
        $invoice = $this->getDbLastEntity('commission_invoice');

        $invoiceExpectedData = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'month' => 5,
            'year' => 2023,
            'status' => 'processed',
            'gross_amount' => 944,
            'tax_amount' => 144,
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());
    }

    public function testInvoiceCreateAutoApprovalFailedResellerKYCNotApproved()
    {
        $testData = $this->setUpCommissionCreateWith3MTU();

        $merchant = ['partner_type' => 'reseller'];
        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID];
        $partnerActivation = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID, 'activation_status' => 'pending'];
        $testData = $this->setupForInvoiceAutoApproval($testData, $merchantDetail, $partnerActivation, $merchant);
        $now = Carbon::now(Timezone::IST);

        $this->mockPartnerInvoiceAutoApproval(Constants::DEFAULT_PLATFORM_MERCHANT_ID, $now->year,'enable');
        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        // check that invoice is created
        $invoice = $this->getDbLastEntity('commission_invoice');

        $invoiceExpectedData = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'month' => $now->month,
            'year' => $now->year,
            'status' => 'issued',
            'gross_amount' => 800
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());
    }

    public function testInvoiceCreateAutoApprovalSuccessForNonReseller()
    {
        $testData = $this->setUpCommissionCreateWith3MTU();

        $merchant = ['activated' => true];
        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID];
        $partnerActivation = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID];
        $testData = $this->setupForInvoiceAutoApproval($testData, $merchantDetail, $partnerActivation, $merchant);

        $now = Carbon::now(Timezone::IST);
        $this->mockPartnerInvoiceAutoApproval(Constants::DEFAULT_PLATFORM_MERCHANT_ID, $now->year, 'enable');
        $this->mockAutoApprovalFinanceExp(Constants::DEFAULT_PLATFORM_MERCHANT_ID);
        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        // check that invoice is created
        $invoice = $this->getDbLastEntity('commission_invoice');
        $invoiceExpectedData = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'month' => $now->month,
            'year' => $now->year,
            'status' => 'processed',
            'gross_amount' => 800
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());
    }

    public function testInvoiceCreateAutoApprovalFailedForNonResellerKYCStatus()
    {
        $testData = $this->setUpCommissionCreateWith3MTU();
        $merchant = ['activated' => false];
        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID];
        $partnerActivation = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID];
        $testData = $this->setupForInvoiceAutoApproval($testData, $merchantDetail, $partnerActivation, $merchant);

        $now = Carbon::now(Timezone::IST);
        $this->mockPartnerInvoiceAutoApproval(Constants::DEFAULT_PLATFORM_MERCHANT_ID, $now->year, 'enable');
        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        // check that invoice is created
        $invoice = $this->getDbLastEntity('commission_invoice');
        $invoiceExpectedData = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'month' => $now->month,
            'year' => $now->year,
            'status' => 'issued',
            'gross_amount' => 800
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());
    }

    public function testInvoiceCreateAutoApprovalFailedExpNotEnabled()
    {
        $testData = $this->setUpCommissionCreateWith3MTU();
        $merchantDetail = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID];
        $partnerActivation = ['merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,'activation_status' => 'pending'];
        $testData = $this->setupForInvoiceAutoApproval($testData, $merchantDetail, $partnerActivation);

        $now = Carbon::now(Timezone::IST);
        $this->mockPartnerInvoiceAutoApproval(Constants::DEFAULT_PLATFORM_MERCHANT_ID, $now->year, 'disable');
        $this->mockPartnerSubMtuDatalakeQuery(Constants::DEFAULT_PLATFORM_MERCHANT_ID);

        $this->runRequestResponseFlow($testData);

        // check that invoice is created
        $invoice = $this->getDbLastEntity('commission_invoice');

        $invoiceExpectedData = [
            'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            'month' => $now->month,
            'year' => $now->year,
            'status' => 'issued',
            'gross_amount' => 800
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());
    }

    public function testInvoiceCreateAutoApprovalFailedMaxAmountCheckBreached()
    {
        Mail::fake();

        $grossAmount = ( Invoice\Entity::MAX_AUTO_APPROVAL_AMOUNT + 100 );
        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => $grossAmount,
            'debit'  => 0,
            'fee'    => $grossAmount,
            'tax'    => 762727,
        ]);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];
        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';
        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];
        $now = Carbon::now(Timezone::IST);
        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->mockPartnerInvoiceAutoApproval($partner->getId(), $now->year, 'enable');
        $this->mockPartnerSubMtuDatalakeQuery($partner->getId());

        $this->startTest($testData);
        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');
        $invoiceExpectedData = [
            'merchant_id'   => 'DefaultPartner',
            'month'         => $now->month,
            'year'          => $now->year,
            'status'        => 'issued',
            'gross_amount'  => $grossAmount,
            'tax_amount'    => 762727,
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());
    }

    public function testInvoiceCreateAutoApprovalFailedInvoiceYearCheckFailed()
    {
        Mail::fake();

        $grossAmount = ( Invoice\Entity::MAX_AUTO_APPROVAL_AMOUNT + 100 );
        list($partner, $subMerchant, $payment, $config, $commission) = $this->createSampleCommission([],[],[],[
            'credit' => $grossAmount,
            'debit'  => 0,
            'fee'    => $grossAmount,
            'tax'    => 762727,
            'created_at' => '1669135053',
            'updated_at' => '1669135053',
        ]);

        $this->ba->adminAuth();

        $testData = $this->testData['testCaptureCommission'];
        $testData['request']['url'] = '/commissions/'.$commission->getPublicId().'/capture';
        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testInvoiceGenerate'];
        $now = Carbon::now(Timezone::IST);
        $testData['request']['content']['month']        = 11;
        $testData['request']['content']['year']         = 2022;
        $testData['request']['content']['merchant_ids'] = [$partner->getId()];

        $this->createTaxes();

        $this->mockPartnerSubMtuDatalakeQuery($partner->getId());

        $this->startTest($testData);
        // check that invoice is created with line items and amounts
        $invoice = $this->getDbLastEntity('commission_invoice');
        $invoiceExpectedData = [
            'merchant_id'   => 'DefaultPartner',
            'month'         => 11,
            'year'          => 2022,
            'status'        => 'issued',
            'gross_amount'  => $grossAmount,
            'tax_amount'    => 762727,
        ];
        $this->assertArraySelectiveEquals($invoiceExpectedData, $invoice->toArray());
    }

    private function setupForInvoiceAutoApproval($testData, array $merchantDetail, array $partnerActivation, $merchant = null) {
        Mail::fake();
        if ($merchant) {
            $this->fixtures->on(Mode::TEST)->edit('merchant', Constants::DEFAULT_PLATFORM_MERCHANT_ID, $merchant);
            $this->fixtures->on(Mode::LIVE)->edit('merchant', Constants::DEFAULT_PLATFORM_MERCHANT_ID, $merchant);
        }
        $this->fixtures->on(Mode::TEST)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('merchant_detail:sane', $merchantDetail);
        $this->fixtures->on(Mode::LIVE)->create('partner_activation', $partnerActivation);
        $this->fixtures->on(Mode::TEST)->create('partner_activation', $partnerActivation);

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            null,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->runRequestResponseFlow($testData);

        list($payment, $commission) = $this->assertAndGetCommissionByType(CommissionType::IMPLICIT);
        $testData = $this->testData['testInvoiceGenerate'];

        $now = Carbon::now(Timezone::IST);

        $testData['request']['content']['month']        = $now->month;
        $testData['request']['content']['year']         = $now->year;
        $testData['request']['content']['merchant_ids'] = [Constants::DEFAULT_PLATFORM_MERCHANT_ID];

        $this->createTaxes();
        return $testData;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    private function mockAutoApprovalFinanceExp(string $merchantId)
    {
        $input = [
            "experiment_id" => "KjN1fFEK7MA7r3",
            "id"            => $merchantId,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);
    }

    private function mockPartnerSubMtuDatalakeQuery(string $partnerId, string $mtuCount=Invoice\Constants::GENERATE_INVOICE_MIN_SUB_MTU_COUNT )
    {
        $datalakeMock = Mockery::mock(DataLakePresto::class)->makePartial();

        $this->app->instance('datalake.presto', $datalakeMock);

        $datalakeMock->shouldReceive('getDataFromDataLake')->andReturn([
            json_decode(json_encode(['partner_id' => $partnerId, 'mtu_count' => $mtuCount]))
        ]);
    }

    private function mockPartnerInvoiceAutoApproval(string $merchantId, int $invoiceYear, string $output) {
        $input = [
            "experiment_id" => "L08J6QilO9olL5",
            "id"            => $merchantId,
            "request_data"  => json_encode([
                'invoice_year' => strval($invoiceYear),
                'mid' => $merchantId,
            ]),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => $output,
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);
    }
}
