<?php

namespace RZP\Tests\Functional\Invoice;

use Carbon\Carbon;
use Mail;
use Queue;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception;
use Illuminate\Support\Facades\DB;
use RZP\Jobs\Invoice\Job as InvoiceJob;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Payment\Processor\Processor;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Mail\Invoice\Issued as InvoiceIssuedMail;
use RZP\Tests\Functional\Partner\Commission\CommissionTrait;
use RZP\Mail\Invoice\Payment\Authorized as InvoiceAuthorizedMail;
use RZP\Mail\Invoice\Payment\Captured as InvoiceCapturedMail;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Invoice\Entity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;

class InvoiceTest extends TestCase
{
    use MocksRazorx;
    use TestsMetrics;
    use PaymentTrait;
    use CreatesInvoice;
    use CommissionTrait;
    use InvoiceTestTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;

    const TEST_INV_ID = 'inv_1000000invoice';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/InvoiceTestData.php';

        parent::setUp();

        // Merchant detail entity for default test merchant
        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'                 => '10000000000000',
                'business_registered_address' => '#1205, Rzp, Outer Ring Road, Bangalore',
                'gstin'                       => '29kjsngjk213922',
            ]);

        $this->fixtures->create('user', ['id' => '1000000000user']);

        $this->ba->privateAuth();
    }

    // ------------------------------------------------------------
    // Tests around creation and payment of invoice
    // ------------------------------------------------------------

    public function testCreateInvoiceWithNewCustomer()
    {
        $this->fixtures->merchant->addFeatures(['invoice_receipt_mandatory']);

        $response = $this->startTest();

        $this->assertInvoiceCreateResponse($response);

        $customer = $this->getLastEntity('customer', true);

        $this->assertEquals($customer['id'], $response['customer_id']);
        $this->assertEquals('10000000000000', $customer['merchant_id']);

        // Asserts if order.receipt = invoice.receipt
        $order = $this->getLastEntity('order', true);

        $this->assertEquals($response['receipt'], $order['receipt']);

        // Asserts if have assigned default value to invoices.date
        $this->assertNotNull($response['date']);

        // Asserts that proper value for merchant label & merchant gstin is set (not exposed in public response)
        $invoice = $this->getDbLastEntity('invoice');

        $this->assertEquals('Test Merchant', $invoice->getMerchantLabel());
        $this->assertEquals('29kjsngjk213922', $invoice->getMerchantGstin());
    }

    public function testCreateInvoiceWithPartnerAuth()
    {
        $subMerchantId = Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID;
        $client = $this->setUpPartnerSubMerchantConfig(Constants::DEFAULT_MERCHANT_ID, $subMerchantId);

        $this->ba->partnerAuth($subMerchantId, 'rzp_test_partner_' . $client->getId(), $client->getSecret());

        $this->startTest();

        $invoice = $this->getDbLastEntity('invoice');

        $entityOrigin = $this->getDbEntity('entity_origin', [
            'entity_id' => $invoice->getId(),
            'origin_type' => 'application',
        ]);
        $this->assertNotNull($entityOrigin);

        $this->doPaymentForInvoiceCreatedWithPartnerAuth($invoice);

        $payment = $this->getDbLastEntity('payment');

        $this->assertNotNull($payment->entityOrigin);
        $this->assertEquals('application', $payment->entityOrigin->origin->getEntityName());

        $commission = $this->getDbEntity('commission', ['source_id' => $payment->getId()]);

        $this->assertNotNull($commission);
    }

    private function setUpPartnerSubMerchantConfig($partnerId, $subMerchantId)
    {
        $client = $this->setUpNonPurePlatformPartnerAndSubmerchant($partnerId, $subMerchantId);
        $this->fixtures->merchant->addFeatures(['invoice_receipt_mandatory'], $subMerchantId);

        $this->fixtures->pricing->createImplicitPartnerPricingPlan([
            'plan_id' => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            'percent_rate' => 100,
        ]);
        $this->createConfigForPartnerApp($client->getApplicationId(), null, [
            'implicit_plan_id' => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
        ]);

        return $client;
    }

    private function doPaymentForInvoiceCreatedWithPartnerAuth($invoice)
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = 'order_'.$invoice['order_id'];
        $payment['amount']   = $invoice['amount'];

        return $this->doAuthAndGetPayment(
            $payment,
            [
                'status'   => 'captured',
                'order_id' => 'order_'.$invoice['order_id'],
            ]
        );
    }

    public function testCreateInvoiceWithBatchIdInHeader()
    {
        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testCreateInvoiceLinkWithIdempotentId()
    {
        $response = $this->startTest();

        $invoice = $this->getDbLastEntity('invoice');

        $this->assertEquals('B24Y8gjypHOVOm', $response['idempotency_key']);

        $this->assertEquals('B24Y8gjypHOVOm', $invoice['idempotency_key']);
    }

    public function testCreateInvoiceLinkWithIdempotentIdAndGetTheResponse()
    {
        $attributes = [
            'receipt'         => '1',
            'order_id'        => $this->fixtures->create('order')->getId(),
            'idempotency_key' => 'B24Y8gjypHOVOm',
            'type'            => 'link'
        ];

        $this->fixtures->create('invoice', $attributes);

        $response = $this->startTest();

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($response['receipt'], $invoice['receipt']);
    }

    public function testCreateInvoiceWithExistingCustomer()
    {
        $response = $this->startTest();

        $this->assertInvoiceCreateResponse($response);

        $this->assertEquals('cust_100000customer', $response['customer_id']);
    }

    public function testCreateBulkInvoices()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        // once idempotent PR merges
        // https://github.com/razorpay/api/pull/11830
        // then assert with idempotent key

    }

    public function testCreateInvoiceWithCustomerIdAndDetails()
    {
        $this->startTest();
    }


    public function testCreateInvoiceWithInternationalCurrency()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $this->startTest();
    }

    public function testCreateInvoiceWithInternationalCurrencyTax()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $this->startTest();
    }

    public function testCreateInvoiceLinkWithAutoReminders()
    {
        $this->ba->privateAuth();

        $response = $this->startTest();

        Entity::verifyIdAndSilentlyStripSign($response['id']);

        $reminderStatus = $this->getDbEntity('invoice_reminder', ['invoice_id' => $response['id']]);

        $this->assertEquals('in_progress', $reminderStatus['reminder_status']);
    }

    public function testCreateInvoiceWithDefinedDisplayName()
    {
        $merchantLabel = 'Awesome and Co';

        $merchantAttrs = [
            'name'                => 'ASD Enterprise',
            'invoice_label_field' => 'business_dba',
        ];

        $this->fixtures->merchant->edit('10000000000000', $merchantAttrs);

        $this->fixtures->merchant_detail->edit('10000000000000', ['business_dba' => $merchantLabel]);

        $response = $this->startTest();

        // supply_state_code should not be in private auth response
        $this->assertArrayNotHasKey('supply_state_code', $response);

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($merchantLabel, $invoice['merchant_label']);
        $this->assertEquals('29', $invoice['supply_state_code']);
    }

    public function testCreateInvoiceWithNestedCustomerIdAndDetails()
    {
        $this->fixtures->create(
            'customer',
            [
                'id'      => '100001customer',
                'name'    => 'Test Old',
                'email'   => 'testold@razorpay.com',
                'contact' => '1234567890',
            ]);

        $this->startTest();
    }

    public function testCreateInvoiceAndPay()
    {
        Mail::fake();

        $order = $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        $metrics = $this->createMetricsMock();

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        Mail::assertQueued(InvoiceAuthorizedMail::class, function ($mail) use ($invoice)
        {
            $this->assertEquals($mail->originProduct, 'primary');

            $this->assertEquals($invoice->getPublicId(), $mail->viewData['invoice']['id']);

            return true;
        });

        Mail::assertQueued(InvoiceCapturedMail::class, function ($mail) use ($invoice)
        {
            $this->assertEquals($invoice->getPublicId(), $mail->viewData['invoice']['id']);

            return true;
        });
    }

    public function testPayInvoiceWithCallbackUrl()
    {
        $order = $this->createOrder();

        $invoice = $this->createIssuedInvoice([
                        'callback_url'    => 'http://localhost/works',
                        'callback_method' => 'get',
                        ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $invoice->getAmount();

        $response = $this->doAuthPayment($payment);

        $actualSignature = $response['razorpay_signature'];

        $signatureData = [
            'razorpay_invoice_id'      => $invoice->getPublicId(),
            'razorpay_invoice_receipt' => $invoice->getReceipt(),
            'razorpay_invoice_status'  => 'paid',
            'razorpay_payment_id'      => $response['razorpay_payment_id'],
        ];

        $exceptedSignature = $this->getSignature($signatureData, 'TheKeySecretForTests');

        $this->assertEquals($exceptedSignature, $actualSignature);
    }

    public function testCreateLinkWithSource()
    {
        $this->startTest();
    }

    public function testCreateLinkWithExpiryRequiredFeature()
    {
        $this->fixtures->merchant->addFeatures(['invoice_expire_by_reqd']);

        $this->startTest();
    }

    public function testCreateLinkWithInvalidSource()
    {
        $this->startTest();
    }

    public function testCreateLinkWithoutReceipt()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        // Assert corresponding order.receipt = null
        $order = $this->getLastEntity('order', true);

        $this->assertNull($order['receipt']);

        Entity::verifyIdAndSilentlyStripSign($response['id']);

        $reminderStatus  = $this->getDbEntity('invoice_reminder', ['invoice_id' => $response['id']]);

        $this->assertNull($reminderStatus);
    }

    public function testCreateLinkAndPayAndCheckCustomerDetailsInInvoice()
    {
        $this->createOrder();

        $invoice = $this->fixtures->create('invoice',
            [
                'customer_id'      => null,
                'customer_name'    => null,
                'customer_email'   => null,
                'customer_contact' => null,
                'type'             => 'link',
                'description'      => 'Sample description',
            ]);

        //
        // While making payment, we pull customer data from payment and fill in
        // invoice columns.
        //

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($invoice['customer_details']['email'], 'a@b.com');
        $this->assertEquals($invoice['customer_details']['contact'], '+919918899029');
    }

    public function testCreateLinkReminderEnable()
    {
        $response = $this->startTest();

        Entity::verifyIdAndSilentlyStripSign($response['id']);

        $invoiceReminder = $this->getDbEntity('invoice_reminder', ['invoice_id' => $response['id']]);

        $this->assertNotNull($invoiceReminder['reminder_id']);
    }

    public function testCreateLinkReminderDisable()
    {
        $this->startTest();

        $invoice = $this->getLastEntity('invoice');

        $id = $invoice['id'];

        $id = Entity::stripDefaultSign($id);

        $invoiceObj = $this->getDbEntityById('invoice', $id);

        $this->assertNull($invoiceObj['reminder_id']);
    }

    public function testCreateLinkReminderFieldNotThere()
    {
        $this->startTest();

        $invoice = $this->getLastEntity('invoice');

        $id = $invoice['id'];

        $id = Entity::stripDefaultSign($id);

        $invoiceObj = $this->getDbEntityById('invoice', $id);

        $this->assertNull($invoiceObj['reminder_id']);
    }

    public function testCreateLinkCustomerContactEmailNullOldMerchantFlagDisabled()
    {
        $this->fixtures->merchant->editCreatedAt(1565865984);
        $customer = $this->fixtures->create(
            'customer',
            [
                'id'          => '100022customer',
                'contact'     => null,
                'email'       => null,
                'merchant_id' => '10000000000000',
            ]);
        $this->startTest();
        $invoice = $this->getLastEntity('invoice');

        $this->assertNotEquals('cust_100022customer', $invoice['customer_id']);
    }

    public function testCreateLinkCustomerContactEmailNullOldMerchantFlagEnabled()
    {
        $this->fixtures->merchant->addFeatures(['cust_contact_email_null']);
        $this->fixtures->merchant->editCreatedAt(1565865984);
        $customer = $this->fixtures->create(
            'customer',
            [
                'id'          => '100022customer',
                'contact'     => null,
                'email'       => null,
                'merchant_id' => '10000000000000',
            ]);
        $this->startTest();
        $invoice = $this->getLastEntity('invoice');

        $this->assertNotEquals('cust_100022customer', $invoice['customer_id']);
    }

    public function testCreateLinkCustomerContactEmailNullNewMerchant()
    {
        $this->fixtures->merchant->editCreatedAt(1566559717);
        $customer = $this->fixtures->create(
            'customer',
            [
                'id'          => '100022customer',
                'contact'     => null,
                'email'       => null,
                'merchant_id' => '10000000000000',
            ]);
        $this->startTest();
        $invoice = $this->getLastEntity('invoice');

        $this->assertNotEquals('cust_100022customer', $invoice['customer_id']);
    }

    public function testCreateInvoiceWithMultipleLineItems()
    {
        $response = $this->startTest();

        $order = $this->getLastEntity('order', true);

        $this->assertEquals(500000, $order['amount']);
        $this->assertEquals('created', $order['status']);
        $this->assertEquals('cust_100000customer', $response['customer_id']);

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(2, $lineItems['count']);

        //
        // Now, item wouldn't be getting created via line_items. line_items has
        // item related fields and the sent input will be consumed there. item_id
        // for all such line_items will be null.
        // Keeping this one test to just ensure that, as preeviously it used
        // to happen.
        //
        $items = $this->getEntities('item', [], true);
        $this->assertEquals(0, $items['count']);

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][0]['entity_id']);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][1]['entity_id']);
    }

    public function testCreateInvoiceWithMultipleLineItemsAndUsingExistingItem()
    {
        $this->fixtures->create('item', ['tax_rate' => 120]);

        $response = $this->startTest();

        $order = $this->getLastEntity('order', true);

        $this->assertEquals(600000, $order['amount']);
        $this->assertEquals('created', $order['status']);
        $this->assertEquals('cust_100000customer', $response['customer_id']);

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(2, $lineItems['count']);

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][0]['entity_id']);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][1]['entity_id']);
    }

    public function testCreateInvoiceWithUsingInactiveItem()
    {
        $this->fixtures->create('item', ['active' => 0]);

        $this->startTest();
    }

    public function testCreateInvoiceWithItemOfTypeNonInvoice()
    {
        $this->fixtures->create('item', ['type' => 'plan']);

        $this->startTest();
    }

    public function testCreateInvoiceWithNewCustomerAndAddress()
    {
        $response = $this->startTest();

        $this->assertInvoiceCreateResponse($response);

        $addresses = $this->getEntities('address', [],true);

        foreach ($addresses['items'] as $address)
        {
            $this->assertEquals('1', $address['primary']);
            $this->assertEquals($response['customer_id'], $address['entity_id']);
            $this->assertEquals('customer', $address['entity_type']);
        }
    }

    public function testCreateInvoiceWithSmsNotifyFalseAndEmailNotifyTrue()
    {
        $this->startTest();
    }

    public function testCreateDraftInvoiceWithNoData()
    {
        //
        // This asserts Invoice.getPaymentIdAttribute is working fine.
        //
        $this->fixtures->create('payment:captured');

        $this->startTest();

        $order = $this->getLastEntity('order', true);
        $this->assertNull($order);
    }

    public function testCreateDraftInvoiceWithSomeData()
    {
        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['customer_id']);
        $this->assertNotEmpty($response['line_items'][0]['id']);

        $order = $this->getLastEntity('order', true);
        $this->assertNull($order);
    }

    /**
     * Creates invoice with few line items such that the total invoice amount
     * exceeds the allowed payment amount for merchant.
     *
     * @return
     */
    public function testCreateDraftInvoiceWithLineItemsAndMaxAllowedAmount()
    {
        $skipReason = 'Draft invoice currently does not create order. We are piggy backing on order for max amount check.
                       Have to port the order max amount check to invoices';

        $this->markTestSkipped($skipReason);

        $this->startTest();
    }

    public function testCreateIssuedInvoice()
    {
        Mail::fake();

        $response = $this->startTest();

        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['customer_id']);
        $this->assertNotEmpty($response['line_items'][0]['id']);
        $this->assertNotEmpty($response['order_id']);
        $this->assertNotEmpty($response['short_url']);

        $order = $this->getLastEntity('order', true);
        $this->assertNotNull($order);

        Mail::assertSent(InvoiceIssuedMail::class, function ($mail)
        {
            return $mail->hasTo('test@rzp.com');
        });
    }


    public function testCreateIssuedInvoiceForMYMerchant()
    {
        $org = $this->fixtures->create('org:curlec_org');

        $this->fixtures->org->addFeatures([FeatureConstants::ORG_CUSTOM_BRANDING],$org->getId());

        $this->fixtures->merchant->edit('10000000000000', ['country_code' => 'MY','org_id'    => $org->getId()]);

        Mail::fake();

        $response = $this->startTest();

        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['customer_id']);
        $this->assertNotEmpty($response['line_items'][0]['id']);
        $this->assertNotEmpty($response['order_id']);
        $this->assertNotEmpty($response['short_url']);

        $order = $this->getLastEntity('order', true);
        $this->assertNotNull($order);

        Mail::assertSent(InvoiceIssuedMail::class, function ($mail)
        {
            return $mail->hasTo('test@rzp.com') && $mail->hasFrom('no-reply@curlec.com');
        });
    }

    public function testCreateDraftInvoiceForMYMerchant()
    {
        $org = $this->fixtures->create('org:curlec_org');

        $this->fixtures->org->addFeatures([FeatureConstants::ORG_CUSTOM_BRANDING],$org->getId());

        $this->fixtures->merchant->edit('10000000000000', ['country_code' => 'MY','org_id'    => $org->getId()]);

        Mail::fake();

        $response = $this->startTest();

        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['customer_id']);
        $this->assertNotEmpty($response['line_items'][0]['id']);
    }

    public function testCreateIssuedInvoiceAndPay()
    {
        $testData = $this->testData['testCreateIssuedInvoice'];
        $response = $this->startTest($testData);

        $order = $this->getLastEntity('order', true);
        $this->assertNotNull($order);
        $this->assertEquals($order['id'], $response['order_id']);

        $this->makePaymentForInvoiceAndAssert($response);
    }


    public function testCreateIssuedInvoiceAndPayForMyMerchant()
    {
        $org = $this->fixtures->create('org:curlec_org');

        $this->fixtures->org->addFeatures([FeatureConstants::ORG_CUSTOM_BRANDING],$org->getId());
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->fixtures->merchant->edit('10000000000000', ['country_code' => 'MY','org_id'    => $org->getId()]);

        $testData = $this->testData['testCreateIssuedInvoiceForMYMerchant'];
        $response = $this->startTest($testData);

        $order = $this->getLastEntity('order', true);
        $this->assertNotNull($order);
        $this->assertEquals($order['id'], $response['order_id']);

        $this->makePaymentForInvoiceAndAssertForMyMerchant($response);
    }

    public function testCreateInvoiceWithDuplicateReceiptFails()
    {
        // Case 1: Issued invoice with same receipt already exists
        $attributes = [
            'receipt'  => '00000000000001',
            'order_id' => $this->fixtures->create('order')->getId(),
        ];
        $this->fixtures->create('invoice', $attributes);

        $this->startTest();

        // Case 2: Paid invoice with same receipt already exists
        $attributes = ['status' => 'paid', 'paid_at' => Carbon::now(Timezone::IST)->getTimestamp()];
        $this->fixtures->invoice->edit('1000000invoice', $attributes);

        $this->startTest();

        // Case 3: Partially paid invoice with same receipt already exists
        $attributes = ['status' => 'partially_paid', 'paid_at' => Carbon::now(Timezone::IST)->getTimestamp()];
        $this->fixtures->invoice->edit('1000000invoice', $attributes);

        $this->startTest();

        // Case 4: Draft invoice with same receipt already exists
        $attributes = ['status' => 'draft', 'issued_at' => null];
        $this->fixtures->invoice->edit('1000000invoice', $attributes);

        $this->startTest();
    }

    public function testCreateInvoiceWithDuplicateReceiptSucceedsIfAllowed()
    {
        $this->fixtures->merchant->addFeatures(['invoice_no_receipt_unique']);

        // Case 1: Issued invoice with same receipt already exists
        $attributes = [
            'receipt'  => '00000000000001',
            'order_id' => $this->fixtures->create('order')->getId(),
        ];
        $this->fixtures->create('invoice', $attributes);

        $this->startTest();

        // Case 2: Paid invoice with same receipt already exists
        $attributes = ['status' => 'paid', 'paid_at' => Carbon::now(Timezone::IST)->getTimestamp()];
        $this->fixtures->invoice->edit('1000000invoice', $attributes);

        $this->startTest();

        // Case 3: Partially paid invoice with same receipt already exists
        $attributes = ['status' => 'partially_paid', 'paid_at' => Carbon::now(Timezone::IST)->getTimestamp()];
        $this->fixtures->invoice->edit('1000000invoice', $attributes);

        $this->startTest();

        // Case 4: Draft invoice with same receipt already exists
        $attributes = ['status' => 'draft', 'issued_at' => null];
        $this->fixtures->invoice->edit('1000000invoice', $attributes);

        $this->startTest();
    }

    public function testCreateInvoiceWithReceiptMandatoryFailure()
    {
        $this->fixtures->merchant->addFeatures(['invoice_receipt_mandatory']);

        $this->startTest();
    }

    public function testCreateInvoiceWithDuplicateReceiptSucceeds()
    {
        // Case 1: Issued invoice with same receipt doesn't exists
        $this->startTest();

        $testData = & $this->testData[__FUNCTION__];

        // Case 2: Cancelled invoice with same receipt already exists
        $attributes = [
            'id'           => '1000001invoice',
            'receipt'      => '00000000000002',
            'status'       => 'cancelled',
            'cancelled_at' => Carbon::now(Timezone::IST)->getTimestamp(),
            'order_id'     => $this->fixtures->create('order')->getId(),
        ];
        $this->fixtures->create('invoice', $attributes);

        $testData['request']['content']['receipt']  = '00000000000002';
        $testData['response']['content']['receipt'] = '00000000000002';

        $this->startTest();

        // Case 3: Expired invoice with same receipt already exists
        $attributes = [
            'id'         => '1000002invoice',
            'receipt'    => '00000000000003',
            'status'     => 'expired',
            'expired_at' => Carbon::now(Timezone::IST)->getTimestamp(),
            'order_id'   => $this->fixtures->create('order')->getId(),
        ];
        $this->fixtures->create('invoice', $attributes);

        $testData['request']['content']['receipt']  = '00000000000003';
        $testData['response']['content']['receipt'] = '00000000000003';

        $this->startTest();
    }

    public function testCreateInvoiceWithMultipleLineItemsAndDifferentCurrency()
    {
        $skipReason = 'Only allowed currency is INR in validators of invoice, line_item and item for now.';

        $this->markTestSkipped($skipReason);

        $this->createOrder();

        $this->fixtures->create('item', ['currency' => 'USD']);

        $this->fixtures->create('invoice', ['receipt' => '00000000000001']);

        $this->startTest();
    }

    public function testCreateDraftLinkWithAmountAndDesc()
    {
        $this->startTest();
    }

    public function testCreateIssuedLinkWithAmountAndDesc()
    {
        $this->startTest();
    }

    public function testCreateDraftLinkWithAmount()
    {
        $this->startTest();
    }

    public function testCreateIssuedLinkWithAmount()
    {
        $this->startTest();
    }

    public function testCreateIssuedLinkWithoutLineItemsAmount()
    {
        $this->startTest();
    }

    public function testCreateDraftLinkWithLineItemsAndAmount()
    {
        $this->startTest();
    }

    public function testCreateIssuedInvoiceWithLineItemsAndAmount()
    {
        $this->startTest($this->testData['testCreateDraftLinkWithLineItemsAndAmount']);
    }

    public function testCreateInvoiceWithNullCurrency()
    {
        $this->startTest();
    }

    public function testCreateInvoiceWithAmount()
    {
        $this->startTest();
    }

    public function testCreateInvoiceWithAmountIntCurrency()
    {
        $this->startTest();
    }

    public function testCreateInvoiceWithBadExpiredBy()
    {
        $this->startTest();
    }

    public function testCreateInvoiceAndAssertEsSync()
    {
        $esMock = $this->createEsMock(['bulkUpdate']);

        $expected = $this->getExpectedUpsertIndexParams();

        // Asserting notes values differently as bulkUpdate gets notes
        // as object of stdClass. And that is not asserted by
        // assertArraySelectiveEquals() method.
        // We declare the expected notes separately and then assert it
        // against the actual value by typecasting the later to array.

        $expectedNotes = [];

        $esMock->expects($this->once())
               ->method('bulkUpdate')
               ->with(
                    $this->callback(
                        function ($actual) use ($expected, $expectedNotes)
                        {
                            $this->assertArraySelectiveEquals($expected, $actual);

                            $this->assertEquals($expectedNotes, (array) $actual['body'][1]['notes']);

                            $this->assertNotEmpty($actual['body'][0]['index']['_id']);
                            $this->assertNotEmpty($actual['body'][1]['id']);

                            return true;
                        }));

        $this->startTest();
    }



    // ------------------------------------------------------------
    // Tests around updation of invoice
    // ------------------------------------------------------------

    public function testUpdateDraftInvoiceWithAmount()
    {
        $this->createDraftInvoice(['supply_state_code' => '29']);

        $this->startTest();
    }

    public function testUpdateDraftInvoiceWithBasicFields()
    {
        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateDraftInvoiceAndIssue()
    {
        $this->createDraftInvoice(['amount' => 100000]);

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        //
        // On sending 'draft'='0', it should just issue the invoice
        //

        $response = $this->startTest();

        $this->assertNotEmpty($response['short_url']);
        $this->assertNotEmpty($response['order_id']);
        $this->assertNotEmpty($response['issued_at']);
    }

    public function testUpdateDraftInvoiceWithBasicFieldsAndLineItems()
    {
        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->fixtures->create('item', ['id' => '1000000001item']);
        $this->fixtures->create('line_item', ['id' => '100001lineitem', 'item_id' => '1000000001item']);

        $this->fixtures->create('item', ['id' => '1000000002item']);
        $this->fixtures->create('line_item', ['id' => '100002lineitem', 'item_id' => '1000000002item']);

        $this->fixtures->create('item', ['id' => '1000000003item']);
        $this->fixtures->create('line_item', ['id' => '100003lineitem', 'item_id' => '1000000003item']);

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(4, $lineItems['count']);

        $lineItemIds = collect($lineItems['items'])->pluck('id')->all();

        $this->assertNotContains('1000000002item', $lineItemIds);
        $this->assertNotContains('1000000003item', $lineItemIds);

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateDraftInvoiceWithLineItemsTooLargeAmount()
    {
        $skipReason = 'Draft invoice currently does not create order. We are piggy backing on order for max amount check.
                       Have to port the order max amount check to invoices';

        $this->markTestSkipped($skipReason);

        $this->testAddManyLineItemsToInvoice();

        $this->startTest();
    }

    public function testUpdateDraftInvoiceAmountWhenLineItemsExists()
    {
        $this->createDraftInvoice(['type' => 'link']);

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();
    }

    public function testUpdateDraftInvoiceWithCustomerId()
    {
        $this->createDraftInvoice();

        $this->fixtures->create(
            'customer',
            [
                'id'      => '100001customer',
                'name'    => 'test 2',
                'email'   => 'test2@razorpay.com',
                'contact' => null,
            ]);

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateDraftInvoiceWithCustomerDetails()
    {
        $this->createDraftInvoice();

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateDraftInvoiceWithCustomerIdAndDetails()
    {
        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateDraftInvoiceWithNestedCustomerIdAndDetails()
    {
        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateDraftInvoiceWithCustomerBillingAddressId()
    {
        $this->fixtures->create(
            'address',
            [
                'id'      => '1000000address',
                'type'    => 'billing_address',
                'primary' => false,
            ]);

        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateDraftInvoiceWithCustomerBillingAndShippingAddressIds()
    {
        $this->fixtures->create(
            'address',
            [
                'id'      => '1000000address',
                'type'    => 'billing_address',
                'primary' => false,
            ]);

        $this->fixtures->create(
            'address',
            [
                'id'      => '1000001address',
                'type'    => 'shipping_address',
                'zipcode' => '560080',
                'primary' => false,
            ]);

        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateDraftInvoiceWithSameBillingAndShippingAddressIds()
    {
        $this->fixtures->create(
            'address',
            [
                'id'      => '1000000address',
                'type'    => 'billing_address',
                'primary' => false,
            ]);

        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateDraftInvoiceExpireBy()
    {
        $past = Carbon::create(2018, 2, 1, 12, null, null, Timezone::IST);

        Carbon::setTestNow($past);

        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateDraftInvoiceInvalidExpireBy()
    {
        $past = Carbon::create(2018, 2, 1, 12, null, null, Timezone::IST);

        Carbon::setTestNow($past);

        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateIssuedInvoiceExpireBy()
    {
        $past = Carbon::create(2018, 2, 1, 12, null, null, Timezone::IST);

        Carbon::setTestNow($past);

        $this->createInvoice();

        $this->startTest();
    }

    public function testUpdateIssuedInvoiceInvalidExpireBy()
    {
        $past = Carbon::create(2018, 2, 1, 12, null, null, Timezone::IST);

        Carbon::setTestNow($past);

        $this->createInvoice();

        $this->startTest();
    }

    public function testUpdatePartiallyPaidInvoiceExpireBy()
    {
        $past = Carbon::create(2018, 2, 1, 12, null, null, Timezone::IST);

        Carbon::setTestNow($past);

        $this->createInvoice(['status' => 'partially_paid']);

        $this->startTest();
    }

    public function testUpdatePartiallyPaidInvoiceInvalidExpireBy()
    {
        $past = Carbon::create(2018, 2, 1, 12, null, null, Timezone::IST);

        Carbon::setTestNow($past);

        $this->createInvoice(['status' => 'partially_paid']);

        $this->startTest();
    }

    public function testUpdateDraftInvoiceWithInvalidCustomerBillingAddressId()
    {
        //
        // Creates a different customer and it's billing_address and that follows
        // attempt to update invoice's customer's biling address with this id(of
        // another customer) which should fail.
        //
        $this->fixtures->create(
            'customer',
            [
                'id'      => '100001customer',
                'name'    => 'test 2',
                'email'   => 'test2@razorpay.com',
                'contact' => null,
            ]);

        $this->fixtures->create(
            'address',
            [
                'id'        => '1000001address',
                'entity_id' => '100001customer',
                'type'      => 'billing_address',
            ]);

        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateDraftInvoiceUnsetCustomer()
    {
        $this->createDraftInvoice();

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateDraftInvoiceUnsetCustomerWithNestedCustomerId()
    {
        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testUpdateIssuedInvoice()
    {
        $this->createOrder();
        $this->fixtures->create('invoice');

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateIssuedInvoiceWithOrderAttributes()
    {
        $this->createOrder();

        $this->fixtures->create('invoice');

        $this->fixtures->merchant->addFeatures(['invoice_partial_payments']);

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);

        // Updates partial_payment attribute in request and asserts again.

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['partial_payment'] = '0';
        $testData['response']['content']['partial_payment'] = false;

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateIssuedInvoiceWithExtraFields()
    {
        $this->createOrder();
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testUpdateInvoiceAndAssertEsSync()
    {
        $invoice = $this->createDraftInvoice();

        $esMock = $this->createEsMock(['bulkUpdate']);

        $expected = $this->getExpectedUpsertIndexParams(
            [
                'id'      => $invoice->getId(),
                'receipt' => 'inv_receipt_0001',
                'terms'   => 'Updated terms & conditions',
            ]);

        // Ref to testCreateInvoiceAndAssertEsSync method of this file
        // for why this is being asserted differently.

        $expectedNotes = [
            [
                'key'   => 'key',
                'value' => 'new value',
            ],
        ];

        $esMock->expects($this->once())
               ->method('bulkUpdate')
               ->with(
                    $this->callback(
                        function ($actual) use ($expected, $expectedNotes)
                        {
                            $this->assertArraySelectiveEquals($expected, $actual);

                            $this->assertEquals($expectedNotes, (array) $actual['body'][1]['notes']);

                            return true;
                        }));

        $this->startTest();
    }

    public function testUpdateInvoiceAndAssertEsNoSync()
    {
        //
        // Case:
        // When dirtied fields are not in index, es sync must not happen
        // unnecessarily.
        //

        $invoice = $this->createDraftInvoice();

        $esMock = $this->createEsMock(['bulkUpdate']);

        $esMock->expects($this->never())
               ->method('bulkUpdate');

        $this->startTest();
    }

    public function testIssueInvoiceWithAmountAndDesc()
    {
        $this->fixtures->create(
            'invoice',
            [
                'status'      => 'draft',
                'order_id'    => null,
                'short_url'   => null,
                'description' => 'For test item',
                'type'        => 'link',
            ]
        );

        $response = $this->startTest();

        $this->assertNotEmpty($response['short_url']);
        $this->assertNotEmpty($response['order_id']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['id'], $response['order_id']);
        $this->assertEquals($order['amount'], $response['amount']);
    }

    public function testIssueInvoiceWithLineItems()
    {
        $this->fixtures->create(
            'invoice',
            [
                'status'    => 'draft',
                'order_id'  => null,
                'short_url' => null,
                'amount'    => 200000,
            ]
        );

        $this->fixtures->create('item');
        $this->fixtures->create('line_item', ['quantity' => 2]);

        $response = $this->startTest();

        $this->assertNotEmpty($response['short_url']);
        $this->assertNotEmpty($response['order_id']);

        $order = $this->getLastEntity('order');
        $this->assertEquals($order['id'], $response['order_id']);
        $this->assertEquals($order['amount'], $response['amount']);
        $this->assertEquals(200000, $order['amount']);
    }

    public function testIssueInvoiceWithoutLineItems()
    {
        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testIssueInvoiceWithoutCustomer()
    {
        $this->createDraftInvoice([
                'customer_id'      => null,
                'customer_name'    => null,
                'customer_email'   => null,
                'customer_contact' => null,
            ]);

        $this->fixtures->create('item');
        $this->fixtures->create('line_item', ['quantity' => 2]);

        $this->startTest();
    }

    public function testDeleteInvoice()
    {
        $this->createOrder();

        $this->createDraftInvoice();

        $this->startTest();

        $invoice = $this->getLastEntity('invoice');
        $this->assertNull($invoice);
    }

    public function testDeletePaidInvoice()
    {
        $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->proxyAuth();

        $this->startTest();

        $invoice = $this->getLastEntity('invoice');
        $this->assertNotNull($invoice);
    }

    public function testDeleteInvoiceAndAssertEsSync()
    {
        $this->createDraftInvoice();

        $esMock = $this->createEsMock(['delete']);

        $esMock->expects($this->once())
               ->method('delete')
               ->with(
                    [
                        'index' => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
                        'type'  => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
                        'id'    => '1000000invoice',
                    ]);

        $testData = $this->testData['testDeleteInvoice'];

        $this->startTest($testData);
    }

    public function testAddLineItemToInvoice()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        $response = $this->startTest();

        // ----

        // Replaying the same request with new line item content
        // Response:
        // - Should have new line item
        // - Updated amount data

        $this->fixtures->create('item');

        $testData = $this->testData[__FUNCTION__];

        $input = [
            [
                'item_id'  => 'item_1000000000item',
                'quantity' => 2,
            ]
        ];

        $testData['request']['content'] = $input;

        $testData['response']['content']['line_items'][] =[
            'quantity'         => 2,
            'name'             => 'Some item name',
            'description'      => 'Some item description',
            'amount'           => 100000,
            'currency'         => 'INR'
        ];

        $testData['response']['content']['amount'] += 200000;

        $this->runRequestResponseFlow($testData);
    }

    public function testAddManyLineItemsToInvoice()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testAddTooManyLineItemsToInvoice()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        foreach (range(1, 18) as $i)
        {
            $item = $this->fixtures->create('item',
                [
                    'id' => UniqueIdEntity::generateUniqueId(),
                ]);

            $this->fixtures->create('line_item',
                [
                    'id'      => UniqueIdEntity::generateUniqueId(),
                    'item_id' => $item->getId(),
                ]);
        }

        $this->startTest();

        $invoice = $this->getLastEntity('invoice');

        $this->assertCount(18, $invoice['line_items']);
    }

    public function testAddLineItemToInvoiceWithBadData()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        $response = $this->startTest();
    }

    public function testAddManyLineItemsToInvoiceWithBadData()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(0, $lineItems['count']);
    }

    public function testAddLineItemsToIssuedInvoice()
    {
        $this->ba->proxyAuth();

        $this->createOrder();

        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testAddManyLineItemsToIssuedInvoice()
    {
        $this->ba->proxyAuth();

        $this->createOrder();

        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testAddLineItemsToInvoiceAndIssueAndPay()
    {
        $this->ba->proxyAuth();

        // Steps:
        // - Creates a draft invoice
        // - Adds 2 line items to it
        // - Issues the invoice
        // - Makes the payment

        // Re-using some existing test data

        $this->createDraftInvoice();

        $testData = $this->testData['testAddLineItemToInvoice'];

        $response = $this->startTest($testData);

        // Replaying the same request with new line item content

        $this->fixtures->create('item');

        $input = [
            [
                'item_id'  => 'item_1000000000item',
                'quantity' => 2,
            ],
        ];

        $testData['request']['content'] = $input;

        $testData['response']['content']['line_items'][] = [
            'quantity'         => 2,
            'name'             => 'Some item name',
            'description'      => 'Some item description',
            'amount'           => 100000,
            'currency'         => 'INR'
        ];

        $testData['response']['content']['amount'] += 200000;

        $response = $this->startTest($testData);

        // ---
        // Issue the invoice

        $testData = [
            'request' => [
                'url'       => '/invoices/' . $response['id'] . '/issue',
                'method'    => 'post',
                'content'   => [],
            ],
            'response' => [
                'content' => [],
            ]
        ];

        $response = $this->startTest($testData);

        // Make payment to invoice now

        $this->makePaymentForInvoiceAndAssert($response);
    }

    public function testUpdateLineItemOfInvoice()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateLineItemOfInvoiceWithExistingItem()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->fixtures->create(
            'item',
            [
                'id'     => '1000000001item',
                'amount' => 5000,
                'name'   => 'A different item',
            ]
        );

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateLineItemOfInvoiceWithBadData()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();
    }

    public function testUpdateLineItemOfIssuedInvoice()
    {
        $this->ba->proxyAuth();

        $this->createOrder();

        $this->fixtures->create('invoice');
        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();
    }

    public function testRemoveLineItemOfInvoice()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(0, $lineItems['count']);

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testRemoveManyLineItemsOfInvoice()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        $this->createFewLineItems();

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(1, $lineItems['count']);

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testRemoveManyLineItemsOfInvoiceWithBadData()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();

        $this->createFewLineItems();

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(3, $lineItems['count']);
    }

    public function testRemoveLineItemOfIssuedInvoice()
    {
        $this->ba->proxyAuth();

        $this->createOrder();

        $this->fixtures->create('invoice');
        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();
    }

    public function testRemoveManyLineItemsOfIssuedInvoice()
    {
        $this->ba->proxyAuth();

        $this->createOrder();

        $this->fixtures->create('invoice');

        $this->createFewLineItems();

        $this->startTest();

        $lineItems = $this->getEntities('line_item', [], true);
        $this->assertEquals(3, $lineItems['count']);
    }

    public function testSendNotificationWithSmsMode()
    {
        $this->ba->publicAuth();

        $this->createOrder();
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testSendNotificationWithEmailMode()
    {
        $this->ba->publicAuth();

        $this->createOrder();
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testSendNotificationWithSmsModeForDraftInvoice()
    {
        $this->ba->publicAuth();

        $this->createDraftInvoice();

        $this->startTest();
    }

    public function testSendNotificationWithInvalidMode()
    {
        $this->ba->publicAuth();

        $this->createOrder();
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testSendNotificationWithEmailModeByPrivateAuthRoute()
    {
        $this->createOrder();
        $this->createIssuedInvoice();

        $this->startTest();
    }

    // ------------------------------------------------------------
    // Tests around get invoice
    // ------------------------------------------------------------

    public function testGetInvoice()
    {
        $this->createOrder();

        $this->fixtures->create('invoice');

        $this->fixtures->create('item');

        $this->fixtures->create('line_item');

        $response = $this->startTest();

        //
        // Asserts that the response doesn't contain 'payments' which should
        // be asked for with 'expands' query parameter in GET requests.
        //

        $this->assertArrayNotHasKey('payments', $response);
    }

    public function testGetInvoiceByReceipt()
    {
        $order = $this->fixtures->create('order');

        $this->createIssuedInvoice(
            [
                'id'       => '1000001invoice',
                'order_id' => $order->getId(),
                'receipt'  => '00000000000001'
            ]);

        $this->fixtures->create(
                            'line_item',
                            [
                                'entity_id' => '1000001invoice',
                                'item_id' => null,
                            ]);

        $order = $this->fixtures->create('order');

        $this->createIssuedInvoice(
            [
                'id'       => '1000002invoice',
                'order_id' => $order->getId(),
                'receipt'  => '00000000000002'
            ]);

        $this->fixtures->create(
                            'line_item',
                            [
                                'id' => '100002lineitem',
                                'entity_id' => '1000002invoice',
                                'item_id' => null,
                            ]);

        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->startTest();
    }

    public function testGetInvoiceByOrderAndPayment()
    {
        $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        $payment = $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->adminAuth();

        //
        // Test data usage, query params with sign, and combination for which there
        // is no results.
        //

        $this->startTest();

        //
        // Test data usage, query params without sign, and combination for which
        // there is a result.
        //

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = 'order_100000000order';
        $testData['request']['content']['payment_id'] = $payment['id'];

        $testData['response']['content']['count'] = 1;

        $this->startTest($testData);
    }

    public function testGetInvoiceWithPayments()
    {
        $this->createOrder();

        $invoice = $this->createIssuedInvoice();

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['payment_id']);
    }

    public function testGetInvoiceWithPaymentsCard()
    {
        $this->createOrder();

        $invoice = $this->createIssuedInvoice();

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['payment_id']);

        $this->assertNotEmpty($response['payments']['items'][0]['card_id']);
    }

    public function testGetMultipleInvoices()
    {
        $this->createOrder();
        $this->createOrder(['id' => '10000000order2']);

        $invoice1 = $this->fixtures->create('invoice', ['order_id' => '100000000order']);
        $invoice2 = $this->fixtures->create('invoice', ['id' => '100000invoice2', 'order_id' => '10000000order2']);

        $item1 = $this->fixtures->create('item');
        $item2 = $this->fixtures->create('item', ['id' => '1000000001item', 'name' => 'Item 2']);

        $this->fixtures->create('line_item', ['entity_id' => $invoice1->getId()]);
        $this->fixtures->create('line_item', [
            'id' => '10000lineitem2',
            'entity_id' => $invoice2->getId(),
            'item_id' => $item2->getId()]);

        $response = $this->startTest();

        //
        // Asserts that the response doesn't contain 'payments' which should
        // be asked for with 'expands' query parameter in GET requests.
        //

        foreach ($response['items'] as $entity)
        {
            $this->assertArrayNotHasKey('payments', $entity);
        }
    }

    public function testGetMultipleInvoicesWithPayments()
    {
        $this->createOrder();

        $invoice1 = $this->createIssuedInvoice();

        $this->createOrder(['id' => '10000000order2']);

        $invoice2 = $this->createIssuedInvoice(['id' => '100000invoice2', 'order_id' => '10000000order2']);

        $this->makePaymentForInvoiceAndAssert($invoice2->toArrayPublic());

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetMultipleInvoicesByTypes()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice();
        $this->createDraftInvoice(['id' => '1000001invoice', 'type' => 'link', 'supply_state_code' => '29']);
        $this->createDraftInvoice(['id' => '1000002invoice', 'type' => 'ecod', 'supply_state_code' => '29']);
        $this->createDraftInvoice(['id' => '1000003invoice', 'type' => 'ecod', 'supply_state_code' => '29']);

        $this->startTest();
    }

    public function testInvoiceNotifyForBatch()
    {
        $this->createBatchInvoices();

        $this->startTest();

        $invoices = $this->getEntities('invoice', [], true)['items'];

        $this->assertEquals('sent', $invoices[0]['email_status']);
        $this->assertEquals('sent', $invoices[0]['sms_status']);
        $this->assertEquals('sent', $invoices[1]['email_status']);
        $this->assertEquals('sent', $invoices[1]['sms_status']);
    }

    public function testInvoiceSmsNotifyForBatch()
    {
        $this->createBatchInvoices();

        $this->startTest();

        $invoices = $this->getEntities('invoice', [], true)['items'];

        $this->assertNull($invoices[0]['email_status']);
        $this->assertEquals('sent', $invoices[0]['sms_status']);
        $this->assertNull($invoices[1]['email_status']);
        $this->assertEquals('sent', $invoices[1]['sms_status']);
    }

    // -------------------------------------------------------------------------
    // Following tests asserts working of es fetch in various cases.
    //

    public function testGetMultipleInvoicesOnlyEsFields()
    {
        $this->ba->proxyAuth();

        $this->createManyInvoicesForFetchTests();

        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->startTest();
    }

    public function testGetMultipleInvoicesByQ()
    {
        $this->ba->proxyAuth();

        $this->createManyInvoicesForFetchTests();

        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->startTest();
    }

    public function testGetMultipleInvoicesByEsFeildAndFrom()
    {
        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->startTest();
    }

    public function testGetMultipleInvoicesByEsFeildFromAndTo()
    {
        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->startTest();
    }

    public function testGetMultipleInvoicesOnlyMysqlFields()
    {
        $this->ba->proxyAuth();

        $this->createDraftInvoice([
                'id'      => '1000000invoice',
                'user_id' => '1000000000user',
                'type'    => 'link',
            ]);

        $this->createDraftInvoice([
                'id'   => '1000001invoice',
                'type' => 'link',
            ]);

        $this->createDraftInvoice([
                'id'      => '1000002invoice',
                'user_id' => '1000000000user',
                'type'    => 'invoice',
            ]);

        $esMock = $this->createEsMock(['search']);

        $esMock->expects($this->never())
               ->method('search');

        $this->startTest();
    }

    public function testGetMultipleInvoicesByOnlyCommonFields()
    {
        $this->ba->proxyAuth();

        $esMock = $this->createEsMock(['search']);

        $esMock->expects($this->never())->method('search');

        $this->startTest();
    }

    public function testGetMultipleInvoicesByCommonAndMysqlFields()
    {
        $this->ba->proxyAuth();

        $esMock = $this->createEsMock(['search']);

        $esMock->expects($this->never())->method('search');

        $this->startTest();
    }

    public function testGetMultipleInvoicesByCommonAndEsFields()
    {
        $this->ba->proxyAuth();

        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->startTest();
    }

    public function testGetMultipleInvoicesMixedFields()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetMultipleInvoicesSearchHitsOnly()
    {
        $this->markTestSkipped(
            'Temporarily disabled, waiting for one other pr
            which handles eager loading of relations to go out.');

        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->startTest();
    }

    // -------------------------------------------------------------------------

    public function testGetInvoicesOfCapturedPaymentId()
    {
        $order = $this->createOrder();

        $invoice = $this->fixtures->create('invoice', ['order_id' => '100000000order']);

        $this->fixtures->create('item');

        $this->fixtures->create('line_item', ['entity_id' => $invoice->getId()]);

        // Serializes invoice entity
        $invoice = $invoice->toArrayPublic();

        $payment = $this->makePaymentForInvoiceAndAssert($invoice);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'] = ['payment_id' => $payment['id']];

        $this->ba->privateAuth();
        $response = $this->startTest($testData);

        $this->assertEquals(1, count($response['items']));
        $this->assertEquals($payment['id'], $response['items'][0]['payment_id']);
        $this->assertEquals($invoice['id'], $payment['invoice_id']);
    }

    public function testGetInvoicesLineItemsWithTaxableAmount()
    {
        $order = $this->createOrder();

        $invoice = $this->fixtures->create('invoice', ['order_id' => '100000000order']);

        $this->fixtures->create('item');

        $this->fixtures->create('line_item', ['entity_id' => $invoice->getId()]);

        $this->startTest();
    }

    public function testGetInvoicesAfterCreatingMultipleInvoicesAndPaying()
    {
        $order1 = $this->createOrder();

        $invoice1 = $this->fixtures->create('invoice', ['order_id' => '100000000order']);

        $payment1 = $this->makePaymentForInvoiceAndAssert($invoice1->toArrayPublic());

        // -----

        $order2 = $this->createOrder(['id' => '10000000order2']);

        $invoice2 = $this->fixtures->create('invoice', ['id' => '100000invoice2', 'order_id' => '10000000order2']);

        $payment2 = $this->makePaymentForInvoiceAndAssert($invoice2->toArrayPublic());

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'] = ['payment_id' => $payment1['id']];

        $this->ba->privateAuth();
        $response = $this->startTest($testData);

        $this->assertEquals(1, count($response['items']));
        $this->assertEquals($payment1['id'], $response['items'][0]['payment_id']);
        $this->assertEquals($invoice1->getPublicId(), $response['items'][0]['id']);

        $testData['request']['content'] = ['payment_id' => $payment2['id']];

        $response = $this->startTest($testData);

        $this->assertEquals(1, count($response['items']));
        $this->assertEquals($payment2['id'], $response['items'][0]['payment_id']);
        $this->assertEquals($invoice2->getPublicId(), $response['items'][0]['id']);
    }

    public function testGetInvoiceStatus()
    {
        $this->ba->publicAuth();

        $this->createOrder();
        $this->fixtures->create('invoice');

        $this->startTest();
    }

    public function testGetInvoiceStatusAfterPayment()
    {
        $order = $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        $payment = $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertEquals($payment['id'], $response['razorpay_payment_id']);
    }

    public function testGetInvoiceStatusAfterOneWeek()
    {
        $this->markTestSkipped();

        $this->ba->publicAuth();

        $this->createOrder();
        $this->fixtures->create('invoice');

        $currentTime = Carbon::now(Timezone::IST);
        $currentTime->addDays(18);
        Carbon::setTestNow($currentTime);

        $this->startTest();

        // Clear the mock.
        Carbon::setTestNow();
    }

    //
    // Following 2 tests, just test if we're getting OK status for the view endpoint.
    //

    public function testGetLinkView()
    {
        config(['app.query_cache.mock' => false]);

        // TODO: Very brittle testcase around metrics, should refactor we test this before enabling again

//        $this->createMetricsMock()
//             ->expects($this->at(9))
//             ->method('count')
//             ->with(
//                'invoice_view_total',
//                1,
//                [
//                    'has_batch'        => 0,
//                    'has_subscription' => 0,
//                    'type'             => 'link',
//                    'merchant_country_code' => 'IN'
//                ]);

        $this->createOrder();

        $this->createIssuedInvoice(['type' => 'link', 'description' => 'Sample description']);

        $this->callViewUrlAndMakeAssertions();
    }

    public function testGetLinkViewDraft()
    {

        config(['app.query_cache.mock' => false]);

        // TODO: Very brittle testcase around metrics, should refactor we test this before enabling again

//        $this->createMetricsMock()
//             ->expects($this->at(8))
//             ->method('count')
//             ->with(
//                'invoice_view_total',
//                1,
//                [
//                    'has_batch'        => 0,
//                    'has_subscription' => 0,
//                    'type'             => 'link',
//                    'merchant_country_code' => 'IN'
//                ]);

        $this->createDraftInvoice(['type' => 'link']);

        $this->callViewUrlAndMakeAssertions(
                self::TEST_INV_ID,
                200,
                'Payment Link with id inv_1000000invoice is not issued yet');
    }

    public function testGetLinkViewCancelled()
    {
        $order = $this->createOrder();

        $this->createDraftInvoice(
            [
                'type'         => 'link',
                'order_id'     => $order->getId(),
                'status'       => 'cancelled',
                'amount'       => 100000,
                'cancelled_at' => Carbon::now(Timezone::IST)->getTimestamp(),
            ]);

        $this->callViewUrlAndMakeAssertions(
                self::TEST_INV_ID,
                200,
                'this payment link was cancelled');
    }

    public function testGetLinkViewExpired()
    {
        $this->createOrder();

        $this->createIssuedInvoice(['type' => 'link', 'status' => 'expired']);

        $this->callViewUrlAndMakeAssertions(
                self::TEST_INV_ID,
                200,
                'this payment link was expired');
    }

    // Merchant has feature `block_pl_pay_post_expiry`.
    public function testGetLinkViewPartiallyPaidPostExpiry()
    {
        $this->createOrder();

        $yesterday = Carbon::yesterday()->timestamp;

        $this->createIssuedInvoice(['type' => 'link', 'status' => 'partially_paid', 'expire_by' => $yesterday]);

        $this->fixtures->merchant->addFeatures([Features::BLOCK_PL_PAY_POST_EXPIRY]);

        $this->callViewUrlAndMakeAssertions(
            self::TEST_INV_ID,
            200,
            'Payment Link with id inv_1000000invoice is past its expiry.');
    }

    public function testGetInvoiceView()
    {
        $this->createOrder();

        $this->createIssuedInvoice();

        $this->callViewUrlAndMakeAssertions();
    }

    public function testGetInvoiceViewDraft()
    {
        $this->createDraftInvoice();

        $this->callViewUrlAndMakeAssertions(
                self::TEST_INV_ID,
                200,
                'Invoice with id inv_1000000invoice is not issued yet');
    }

    public function testGetInvoiceViewCancelled()
    {
        $this->createOrder();

        $this->createIssuedInvoice(['status' => 'cancelled']);

        $this->callViewUrlAndMakeAssertions(
                self::TEST_INV_ID,
                200,
                'Invoice with id inv_1000000invoice is cancelled');
    }

    public function testGetInvoiceViewExpired()
    {
        $this->createOrder();

        $this->createDraftInvoice(['status' => 'expired']);

        $this->callViewUrlAndMakeAssertions(self::TEST_INV_ID,200, '<h2>Error</h2>');

    }

    public function testInvoiceSoftDelete()
    {
        $this->ba->appAuth('rzp_test', 'RANDOM_CRON_PASSWORD');

        $pastDay = Carbon::now()->subHour(24)->getTimestamp();

        $currentTimestamp = Carbon::now()->getTimestamp();

        $this->createOrder(['id' => '10000000order0']);
        $this->fixtures->create('invoice',
            [
                'id' => '100000invoice0',
                'status' => 'expired',
                'updated_at' => $pastDay,
                'order_id' => '10000000order0',
                'type' => 'link',
                'merchant_id' => '100000Razorpay'
            ]);


        $this->fixtures->create('invoice',
            [
                'id' => '100000invoice1',
                'status' => 'expired',
                'updated_at' => $currentTimestamp,
                'order_id' => '10000000order0',
                'type' => 'link',
                'merchant_id' => '100000Razorpay'
            ]);

        $this->fixtures->create('invoice',
            [
                'id' => '100000invoice2',
                'status' => 'cancelled',
                'updated_at' => $pastDay,
                'order_id' => '10000000order0',
                'type' => 'link',
                'merchant_id' => '100000Razorpay'
            ]);

        $this->fixtures->create('invoice',
            [
                'id' => '100000invoice3',
                'status' => 'issued',
                'updated_at' => $pastDay,
                'order_id' => '10000000order0',
                'type' => 'link',
                'merchant_id' => '100000Razorpay'
            ]);

        $this->fixtures->create('invoice',
            [
                'id' => '100000invoice4',
                'status' => 'partially_paid',
                'updated_at' => $pastDay,
                'order_id' => '10000000order0',
                'type' => 'link',
                'merchant_id' => '100000Razorpay'
            ]);

        $this->fixtures->create('invoice',
            [
                'id' => '100000invoice5',
                'status' => 'expired',
                'updated_at' => $pastDay,
                'order_id' => '10000000order0',
                'type' => 'link',
                'merchant_id' => '10000000000000'
            ]);

        $this->fixtures->create('invoice',
            [
                'id' => '100000invoice6',
                'status' => 'expired',
                'updated_at' => $currentTimestamp,
                'order_id' => '10000000order0',
                'type' => 'link',
                'merchant_id' => '10000000000000'
            ]);

        $this->startTest();

    }

    /**
     * Calls GET invoice route and makes assertions for status code
     * and errors if any.
     *
     * @param string $id
     * @param int    $code
     * @param string $errorMessage
     *
     */
    protected function callViewUrlAndMakeAssertions(
        string $id = self::TEST_INV_ID,
        int $code = 200,
        string $errorMessage = null)
    {
        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/t/$id", ['key_id' => $this->ba->getKey()]);

        $response->assertStatus($code);

        //
        // If there is an error message expected, assert that else assert
        // that view doesn't contain Error heading.
        //

        if (empty($errorMessage) === false)
        {
            $this->assertStringContainsString($errorMessage, $response->getContent());
        }
        else
        {
            $this->assertStringNotContainsString('<h2>Error</h2>', $response->getContent());
        }
    }

    public function testPayExpiredInvoice()
    {
        $order = $this->createOrder();

        $invoice = $this->fixtures->create('invoice', ['status' => 'expired']);

        $payment             = $this->getDefaultPaymentArray();
        $payment['order_id'] = $invoice->order->getPublicId();
        $payment['amount']   = $invoice->getAmount();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPayDeletedInvoice()
    {
        $order = $this->createOrder();

        $invoice = $this->fixtures->create('invoice', ['deleted_at' => time()]);

        $this->assertNull($this->getLastEntity('invoice'));

        $payment             = $this->getDefaultPaymentArray();
        $payment['order_id'] = $invoice->order->getPublicId();
        $payment['amount']   = $invoice->getAmount();

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testPartialPayment()
    {
        $order = $this->fixtures->create(
                                    'order',
                                    [
                                        'id'              => '100000000order',
                                        'amount'          => 1000,
                                        'partial_payment' => true,
                                        'payment_capture' => true,
                                    ]);

        $invoice = $this->fixtures->create(
                                        'invoice',
                                        [
                                            'partial_payment' => true,
                                            'amount'          => 1000,
                                        ]);

        // Makes a partial payment

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 600;

        $expectedPaymentResponse = [
            'status'     => 'captured',
            'order_id'   => $order->getPublicId(),
            'invoice_id' => $invoice->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $invoice = $this->getLastEntity('invoice');

        $this->assertEquals('partially_paid', $invoice['status']);
        $this->assertEquals(600, $invoice['amount_paid']);
        $this->assertEquals(400, $invoice['amount_due']);
    }

    public function testConsecutivePartialPaymentPostExpiry()
    {
        $order = $this->fixtures->create(
                    'order',
                    [
                        'id'              => '100000000order',
                        'amount'          => 1000,
                        'partial_payment' => true,
                        'payment_capture' => true,
                    ]);

        $invoice = $this->fixtures->create(
                    'invoice',
                    [
                        'partial_payment' => true,
                        'amount'          => 1000,
                        'type'            => 'link',
                    ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 300;

        $expectedPaymentResponse = [
            'status'     => 'captured',
            'order_id'   => $order->getPublicId(),
            'invoice_id' => $invoice->getPublicId(),
        ];

        $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $invoice = $this->getLastEntity('invoice');

        $this->assertEquals('partially_paid', $invoice['status']);
        $this->assertEmpty($invoice['paid_at']);
        $this->assertEquals(300, $invoice['amount_paid']);
        $this->assertEquals(700, $invoice['amount_due']);

        // Add feature to block payments after expiry time in partially paid status
        $this->fixtures->merchant->addFeatures([Features::BLOCK_PL_PAY_POST_EXPIRY]);

        $this->fixtures->edit(
            'invoice',
            $invoice['id'],
            ['expire_by' => Carbon::yesterday(Timezone::IST)->getTimestamp()]
        );

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 700;

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);

        $this->expectExceptionMessage(
            'Payment Link is not payable post its expiry time');

        $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);
    }

    public function testMultiplePartialPayments()
    {
        $this->testPartialPayment();

        $order = $this->getLastEntity('order');
        $invoice = $this->getLastEntity('invoice');

        // Make another 2 partial payments and check if invoice is paid

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];
        $payment['amount']   = 300;

        $expectedPaymentResponse = [
            'status'     => 'captured',
            'order_id'   => $order['id'],
            'invoice_id' => $invoice['id'],
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $invoice = $this->getLastEntity('invoice');

        $this->assertEquals('partially_paid', $invoice['status']);
        $this->assertEmpty($invoice['paid_at']);
        $this->assertEquals(900, $invoice['amount_paid']);
        $this->assertEquals(100, $invoice['amount_due']);

        // Last partial payment of 100 should turn invoice into paid.

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order['id'];
        $payment['amount']   = 100;

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $invoice = $this->getLastEntity('invoice');

        $this->assertEquals('paid', $invoice['status']);
        $this->assertNotEmpty($invoice['paid_at']);
        $this->assertEquals(1000, $invoice['amount_paid']);
        $this->assertEquals(0, $invoice['amount_due']);
    }

    public function testCancelInvoice()
    {
        $this->createOrder();
        $this->fixtures->create('invoice');
        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();
    }

    public function testCancelPaymentInProgressInvoice()
    {
        $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        //
        // Just adds one failed payment too, for testing purposes.
        //
        $this->fixtures->create('payment:failed',
            [
                'order_id'   => '100000000order',
                'invoice_id' => '1000000invoice',
                'card_id'    => null,
            ]);

        $this->fixtures->create('payment:authorized',
            [
                'order_id'   => '100000000order',
                'invoice_id' => '1000000invoice',
                'card_id'    => null,
            ]);

        $this->startTest();
    }

    public function testCancelPaidInvoice()
    {
        $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCancelInvoiceWithFailedPayment()
    {
        $this->createOrder();

        $invoice = $this->fixtures->create('invoice');

        $this->fixtures->create('payment:failed',
            [
                'order_id'   => '100000000order',
                'invoice_id' => '1000000invoice',
                'card_id'    => null,
            ]);

        $this->startTest();
    }

    public function testUpdateExpiredInvoiceNotes()
    {
        $this->createOrder();

        $attributes = [
            'notes'  => [
                'key1' => 'value1'
            ],
            'status' => 'expired',
        ];

        $this->fixtures->create('invoice', $attributes);

        $invoice = $this->getLastEntity('invoice');

        $this->assertArraySelectiveEquals(['key1' => 'value1'], $invoice['notes']);

        $this->startTest();
    }

    public function testExpireInvoices()
    {
        $metrics = $this->createMetricsMock();

        $now = Carbon::now(Timezone::IST)->getTimestamp();

        // Issued invoice
        $this->createOrder();
        $this->fixtures->create('invoice');

        // Picked and expired: Issued invoice and past expire_by
        $this->createOrder(['id' => '100000001order']);
        $this->fixtures->create('invoice',
            [
                'id'         => '1000001invoice',
                'order_id'   => '100000001order',
                'expire_by'  => $now,
            ]);

        // Not picked: Cancelled invoice, can be past expire_by if cancelled in
        // between after issuing.
        $this->createOrder(['id' => '100000002order']);
        $this->fixtures->create('invoice',
            [
                'id'           => '1000002invoice',
                'order_id'     => '100000002order',
                'expire_by'    => $now,
                'status'       => 'cancelled',
                'cancelled_at' => 1484519200,
            ]);

        // Not picked: Draft invoice
        $this->createDraftInvoice(['id' => '1000003invoice', 'created_at' => $now]);

        // Not picked: Past expire_by but paid invoice
        $this->createOrder(['id' => '100000004order']);
        $this->fixtures->create('invoice',
            [
                'id'         => '1000004invoice',
                'order_id'   => '100000004order',
                'expire_by'  => $now,
                'status'     => 'paid',
            ]);

        // Picked and failed: Issued invoice with created payments (not captured
        // so invoice still not paid) and past expire_by.
        $this->createOrder(['id' => '100000005order']);
        $this->fixtures->create('invoice',
            [
                'id'         => '1000005invoice',
                'order_id'   => '100000005order',
                'expire_by'  => $now,
                'status'     => 'issued',
            ]);
        $this->fixtures->payment->createAuthorized(
            [
                'order_id'   => '100000005order',
                'invoice_id' => '1000005invoice',
            ]);

        // Picked and expired: Issued invoice with a payment which might be
        // late authorized and got auto refunded by cron.
        $this->createOrder(['id' => '100000006order']);
        $this->fixtures->create('invoice',
            [
                'id'         => '1000006invoice',
                'order_id'   => '100000006order',
                'expire_by'  => $now,
                'status'     => 'issued',
            ]);
        $payment = $this->fixtures->payment->createAuthorized(
                        [
                            'order_id'   => '100000006order',
                            'invoice_id' => '1000006invoice',
                        ]);

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        // This flow is not live yet on prod for this flow, so disabling flag for the time being.
        $flag = false;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();

            $input['amount'] = $payment->getAmount();
            $refund = $this->refundAuthorizedPayment($payment->getPublicId(), $input);
            $this->assertPassportKeyExists('consumer.id'); // just check for presence of passport

            $this->ba->cronAuth();

            $this->startTest();

            return;
        }

        $this->refundAuthorizedPayment($payment->getPublicId());

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testIssueInvoiceByBatchId()
    {
        $this->testCreateDraftInvoiceWithSomeData();
        $this->testCreateDraftInvoiceWithSomeData();
        $this->testCreateDraftInvoiceWithSomeData();

        $response = $this->getEntities('invoice');

        $ids = array_column($response['items'], 'id');

        $this->fixtures->create(
            'batch',
            [
                'id'          => '00000000000001',
                'type'        => 'payment_link',
                'total_count' => 2,
            ]);

        // Associate 2 invoices with above batch

        $this->fixtures->invoice->edit($ids[0], ['batch_id' => '00000000000001']);
        $this->fixtures->invoice->edit($ids[2], ['batch_id' => '00000000000001']);

        $this->startTest();
    }

    // ------------------------------------------------------------
    // Tests around invoice web hooks
    // ------------------------------------------------------------

    public function testInvoiceExpiredWebhook()
    {
        // Creates expire-able invoice
        $yesterday = Carbon::yesterday(Timezone::IST);
        $now       = Carbon::now(Timezone::IST);
        $issuedAt  = $yesterday->timestamp;
        $expireBy  = $now->subSecond()->timestamp;

        $this->createOrder();

        $this->fixtures->create('invoice', ['issued_at' => $issuedAt, 'expire_by' => $expireBy]);

        $this->expectWebhookEventWithContents('invoice.expired', 'testInvoiceExpiredWebhookEventData');

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testInvoicePartiallyPaidWebhook()
    {
        $this->fixtures->merchant->addFeatures(['invoice_partial_payments']);

        $order   = $this->createOrder(['partial_payment' => '1']);
        $invoice = $this->createIssuedInvoice(['partial_payment' => '1']);

        $this->expectWebhookEventWithContents('invoice.partially_paid', 'testInvoicePartiallyPaidWebhookEventData');

        // Makes a partial payment and asserts payment and web hook (^)
        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 60000;

        $expectedPaymentResponse = [
            'status'     => 'captured',
            'order_id'   => $order->getPublicId(),
            'invoice_id' => $invoice->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);
    }

    public function testInvoiceMultiplePartiallyPaidWebhooks()
    {
        $this->fixtures->merchant->addFeatures(['invoice_partial_payments']);

        $order   = $this->createOrder(['partial_payment' => '1']);
        $invoice = $this->createIssuedInvoice(['partial_payment' => '1']);

        $this->expectWebhookEventWithContents('invoice.partially_paid', 'testInvoiceMultiplePartiallyPaidWebhooksEventData1');
        $this->expectWebhookEventWithContents('order.paid', 'testInvoiceMultiplePartiallyPaidWebhooksEventData2');
        $this->expectWebhookEventWithContents('invoice.paid', 'testInvoiceMultiplePartiallyPaidWebhooksEventData3');

        // Makes two partial payments and asserts payment and web hook(^)
        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 60000;

        $expectedPaymentResponse = [
            'status'     => 'captured',
            'order_id'   => $order->getPublicId(),
            'invoice_id' => $invoice->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 40000;

        $expectedPaymentResponse = [
            'status'     => 'captured',
            'order_id'   => $order->getPublicId(),
            'invoice_id' => $invoice->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);
    }

    public function testInvoicePaidAndOrderPaidWebhooks()
    {
        $order   = $this->createOrder();
        $invoice = $this->createIssuedInvoice();

        $this->expectWebhookEventWithContents('order.paid', 'testInvoicePaidAndOrderPaidWebhooksEventData1');
        $this->expectWebhookEventWithContents('invoice.paid', 'testInvoicePaidAndOrderPaidWebhooksEventData2');

        // Makes a payment and asserts payment and web hooks (^)
        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 100000;

        $expectedPaymentResponse = [
            'status'     => 'captured',
            'order_id'   => $order->getPublicId(),
            'invoice_id' => $invoice->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);
    }

    /**
     * Asserts that after invoice's payment, Invoice\Job's captured handler is
     * triggered which updates the invoice's pdf version and does few other things.
     *
     * @return void
     */
    public function testInvoicePaidAndCapturedJobQueued()
    {
        Queue::fake();

        $order   = $this->createOrder();
        $invoice = $this->createIssuedInvoice();

        // Makes a payment on the invoice
        $payment             = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = 100000;

        $expectedPaymentResponse = [
            'status'     => 'captured',
            'order_id'   => $order->getPublicId(),
            'invoice_id' => $invoice->getPublicId(),
        ];

        $payment = $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        // Now we assert that an invoice job was queued with proper even name
        // and payload.
        Queue::assertPushed(
            InvoiceJob::class,
            function($job) use ($invoice, $payment)
            {
                $this->assertEquals(Mode::TEST, $job->getMode());
                $this->assertEquals(InvoiceJob::CAPTURED, $job->getEvent());
                $this->assertEquals($invoice->getId(), $job->getId());

                return true;
            });
    }

    public function testUpdateBillingPeriod()
    {
        $this->createOrder();

        $this->createIssuedInvoice();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateSendEmailForPaymentLinkService()
    {
        $this->ba->paymentLinksAuth();

        $this->startTest();
    }

    protected function enableRazorXTreatmentForQrOnEmail()
    {
        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
               ->andReturnUsing(function(string $id, string $featureFlag, string $mode) {
                   if ($featureFlag === (RazorxTreatment::QR_ON_EMAIL))
                   {
                       return 'on';
                   }

                   return 'control';
               });
    }

    public function testCreateSendEmailForPaymentLinkServiceWithIntentUrl()
    {
        $this->enableRazorXTreatmentForQrOnEmail();

        $this->ba->paymentLinksAuth();

        $this->startTest();
    }

    public function testAutoCaptureInvoiceOnLateAuthorizedPayment()
    {
        $payment = $this->createInvoiceAndFailedPayment();

        $this->doPartialSuccessfulPaymentAndVerify();

        $this->authorizeFailedPayment($payment['id']);

        $order   = $this->getLastEntity('order', true);

        $invoice = $this->getLastEntity('invoice', true);

        $payment = $this->getEntityById("payment", $payment["id"], true);

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals('paid', $order['status']);

        $this->assertEquals('paid', $invoice['status']);
    }

    public function testInvoicePdfSignedUrlInHostedPage()
    {
        $this->testCreateInvoiceWithNewCustomer();

        $invoice = $this->getDbLastEntity('invoice');

        $this->ba->publicAuth();

        $response = $this->call('GET', '/v1/t/'.$invoice->getPublicId());

        $response->assertStatus(200);

        $this->assertStringContainsString('signed_pdf_url', $response->getContent());
    }

    public function testFetchIssuedLinkOlderThanSixMonths()
    {
        $this->testCreateIssuedLinkWithAmountAndDesc();

        $invoice = $this->getDbLastEntity('invoice');

        $oldTimeStamp = Carbon::now(Timezone::IST)->subDays(181)->timestamp;

        $this->fixtures->edit('invoice', $invoice->getId(), ['created_at' => $oldTimeStamp]);

        $this->testData[__FUNCTION__]['request']['url'] = '/invoices/'.$invoice->getPublicId();

        $this->startTest();
    }

    public function testFetchCancelledAndExpiredLinkOlderThanSixMonths()
    {
        $this->testCreateIssuedLinkWithAmountAndDesc();

        $invoice = $this->getDbLastEntity('invoice');

        $oldTimeStamp = Carbon::now(Timezone::IST)->subDays(181)->timestamp;

        $this->fixtures->edit(
            'invoice',
            $invoice->getId(),
            [
                'created_at' => $oldTimeStamp,
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(Timezone::IST)->timestamp
            ]
        );

        $this->testData[__FUNCTION__]['request']['url'] = '/invoices/'.$invoice->getPublicId();

        $this->startTest();

        $this->fixtures->edit(
            'invoice',
            $invoice->getId(),
            [
                'created_at' => $oldTimeStamp,
                'status' => 'expired',
                'cancelled_at' => null,
                'expired_at' => Carbon::now(Timezone::IST)->timestamp
            ]
        );

        $this->startTest();
    }

    // -------------------- Protected methods --------------------

    protected function doPartialSuccessfulPaymentAndVerify()
    {
        $payment = $this->getDefaultPaymentArray();

        $order   = $this->getLastEntity('order', true);

        $invoice = $this->getLastEntity('invoice', true);

        $payment['order_id'] = $order["id"];

        $payment['amount']   = 600;

        $payment['card']['number'] = '5081597022059105';

        unset($payment['card']['cvv']);

        unset($payment['card']['expiry_month']);

        unset($payment['card']['expiry_year']);

        $expectedPaymentResponse = [
            'status'     => 'captured',
            'order_id'   => $order["id"],
            'invoice_id' => $invoice["id"],
        ];

        $this->mockServerContentFunction(function (& $content, $action)
        {
            return $content;
        });

        $this->doAuthAndGetPayment($payment, $expectedPaymentResponse);

        $payment = $this->getLastEntity('payment', true);

        $invoice = $this->getLastEntity('invoice');

        $this->assertEquals('captured', $payment['status']);

        $this->assertEquals('partially_paid', $invoice['status']);

        $this->assertEquals(600, $invoice['amount_paid']);

        $this->assertEquals(999400, $invoice['amount_due']);
    }

    public function testSwitchPlVersionToV2()
    {
        $this->fixtures->merchant->addFeatures(['paymentlinks_v2_compat']);

        $this->ba->proxyAuth();

        $this->startTest();

        $liveFeaturesArray = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ],
            'live')->pluck('name')->toArray();

        $this->assertContains('paymentlinks_v2', $liveFeaturesArray);

        $this->assertNotContains('paymentlinks_v2_compat', $liveFeaturesArray);

        $tags = DB::table('tagging_tagged')->where('taggable_id', '10000000000000')->get();

        $tags = $tags->pluck('tag_name')->toArray();

        $this->assertContains('Self_switched_to_v2', $tags);
    }

    protected function createInvoiceAndFailedPayment()
    {
        $this->app['config']->set('gateway.mock_hdfc', true);

        $order = $this->fixtures->create(
            'order',
            [
                'id'              => '100000000order',
                'payment_capture' => "1",
                'partial_payment' => true,
            ]);

        $dueBy = Carbon::now(Timezone::IST)->addDays(10)->timestamp;

        $this->fixtures->create(
            'invoice',
            [
                'due_by' => $dueBy,
                'amount' => 1000000,
                'partial_payment' => true,
            ]);

        $this->gateway = 'hdfc';

        $this->mockServerVerifyContentFunction();

        $this->doAuthPaymentAndCatchException($order);

        $payment = $this->getLastEntity('payment', true);

        $this->fixtures->edit("payment",$payment["id"],["status" => "failed"]);

        $payment = $this->getLastEntity('payment', true);

        return $payment;
    }

    protected function mockServerVerifyContentFunction()
    {
        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                throw new Exception\GatewayErrorException('GATEWAY_ERROR_UNKNOWN_ERROR');
            }

            if ($action === 'inquiry')
            {
                $content['RESPCODE'] = '0';
                $content['RESPMSG'] = 'Transaction succeeded';
                $content['STATUS'] = 'TXN_SUCCESS';
            }

            return $content;
        });
    }

    protected function doAuthPaymentAndCatchException($order)
    {
        $this->makeRequestAndCatchException(function() use ($order)
        {
            $payment             = $this->getDefaultPaymentArray();
            $payment['amount']   = 999400;
            $payment['order_id'] = $order->getPublicId();

            $content = $this->doAuthPayment($payment);

            return $content;
        });
    }

    protected function assertInvoiceCreateResponse(array $response)
    {
        $order = $this->getLastEntity('order', true);

        $invoice = $this->getLastEntity('invoice', true);
        $lineItem = $this->getLastEntity('line_item', true);

        $this->assertEquals($order['id'], $response['order_id']);
        $this->assertEquals($order['payment_capture'], true);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItem['entity_id']);
        $this->assertStringContainsString('http://dwarf.razorpay.in/', $invoice['short_url']);
        $this->assertEquals('10000000000000', $invoice['merchant_id']);
    }

    protected function createOrder(array $overrideWith = [])
    {
        $order = $this->fixtures
                      ->create(
                            'order',
                            array_merge(
                                [
                                    'id'              => '100000000order',
                                    'amount'          => 100000,
                                    'payment_capture' => true,
                                ],
                                $overrideWith
                            )
                        );

        return $order;
    }

    protected function createFewLineItems()
    {
        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->fixtures->create('item', ['id' => '1000000001item']);
        $this->fixtures->create('line_item', ['id' => '100001lineitem', 'item_id' => '1000000001item']);

        $this->fixtures->create('item', ['id' => '1000000002item']);
        $this->fixtures->create('line_item', ['id' => '100002lineitem', 'item_id' => '1000000002item']);
    }

    protected function createManyInvoicesForFetchTests()
    {
        $this->createDraftInvoice(
            [
                'id'    => '1000000invoice',
                'notes' => [
                    'extra' => 'Extra Information in notes key!!',
                    'ref'   => 'Sample Reference Number',
                ]
            ]);

        $this->createDraftInvoice(
            [
                'id'    => '1000001invoice',
                'terms' => 'Random terms and conditions',
            ]);

        $merchant = $this->fixtures->create('merchant');

        $this->createDraftInvoice(
            [
                'id'          => '1000004invoice',
                'merchant_id' => $merchant->getId(),
            ]);

        $order = $this->createOrder();

        $this->createIssuedInvoice(
            [
                'id'       => '1000006invoice',
                'order_id' => $order->getId(),
            ]);

        $order = $this->createOrder(['id' => '100000001order']);

        $this->createIssuedInvoice(
            [
                'id'       => '1000007invoice',
                'order_id' => $order->getId(),
            ]);
    }

    protected function createBatchInvoices()
    {
        $attributes = $this->testData['testInvoiceNotifyForBatchInputData']['attributes'];

        $this->fixtures->create(
            'batch',
            [
                'id'          => '00000000000001',
                'type'        => 'payment_link',
                'total_count' => count($attributes),
            ]);

        $this->ba->proxyAuth();

        foreach ($attributes as $attribute)
        {
            $this->createInvoice($attribute['invoiceAttributes'], $attribute['orderAttributes']);
        }
    }

    public function testGetInvoiceDetailsForCheckout(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $order = $this->fixtures->create('order');

        $invoice = $this->fixtures->create('invoice', ["order_id" => $order->getId()]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/internal/invoices/checkout/' . $invoice->getPublicId();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals($order->getPublicId(), $response['invoice']['order_id']);
    }
}
