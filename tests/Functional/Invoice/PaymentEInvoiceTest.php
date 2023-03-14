<?php

namespace RZP\Tests\Functional\Invoice;

use Carbon\Carbon;
use Queue;
use RZP\Constants\Entity as E;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Jobs\PaymentEInvoice;
use RZP\Models\Currency\Currency;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Invoice\Constants;
use RZP\Models\Invoice\Entity;
use RZP\Models\Invoice\Status;
use RZP\Models\Invoice\Type;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\MocksRazorx;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;

class PaymentEInvoiceTest extends TestCase
{
    use MocksRazorx;
    use TestsMetrics;
    use PaymentTrait;
    use CreatesInvoice;
    use InvoiceTestTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;

    const PAYMENT_AMOUNT = 100000;
    const PAYMENT_CURRENCY = 'INR';
    const PAYMENT_GATEWAY_CURRENCY = 'USD';
    const DCC_MARK_UP_PERCENT = 8;
    const FOREX_RATE = 10;
    const COUNTRY_CODE = 'us';
    const REFUND_AMOUNT = 50000;

    const INVOICE_REF_NUM = '4fb6f33102dd5c8f950bbab6313bc3f5606a4959c334c94fd5f1ad57e038448b';

    const MOCK_SUCCESS_RESPONSE = [
        'status' => '200',
        'body'   => [
            'results' => [
                'message' => [
                    'Status'        => 'ACT',
                    'Irn'           => self::INVOICE_REF_NUM,
                    'SignedInvoice' => 'test invoice',
                    'SignedQRCode'  => 'test qr',
                    'QRCodeUrl'     => 'test url',
                    'EinvoicePdf'   => 'test pdf url',
                ],
                'status' => 'Success',
            ],
        ],
    ];
    const MOCK_FAILED_RESPONSE = [
        'status' => 400,
        'body'   => [
            'results' => [
                'message'      => '',
                'status'       => 'Failed',
                'code'         => 204,
                'errorMessage' => '2150: Duplicate IRN',
                'InfoDtls'     => '[{\"InfCd\":\"DUPIRN\",\"Desc\":{\"AckNo\":142210013513825,\"AckDt\":\"2022-12-21 11:26:00\",\"Irn\":\"e4bd9084ee69a5d97c958229fa32f039c1e2c047bbee4da4f23331898d5cfa0d\"}}]',
            ],
        ],
    ];

    const CRON_REQUEST = [
        'url'       => '/invoices/dcc-payment/cron',
        'method'    => 'POST',
    ];

    protected $eInvoiceClientMock;
    protected $payment;
    protected $address;
    protected $refund;
    protected $eInvoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createRequiredEntities();
        $this->setUpEInvoiceClientMock();
        $this->ba->privateAuth();
    }

    // creates payment, payment_meta and address entities
    protected function createRequiredEntities()
    {
        $paymentAttributes = [
            'amount' => self::PAYMENT_AMOUNT,
            'currency' => self::PAYMENT_CURRENCY,
        ];
        $this->payment = $this->fixtures->create('payment:captured', $paymentAttributes);

        $paymentMetaAttributes = [
            'payment_id' => $this->payment->getId(),
            'gateway_currency' => self::PAYMENT_GATEWAY_CURRENCY,
            'gateway_amount' => self::PAYMENT_AMOUNT / self::FOREX_RATE,
            'dcc_mark_up_percent' => self::DCC_MARK_UP_PERCENT,
        ];
        $this->fixtures->create(E::PAYMENT_META, $paymentMetaAttributes);

        $addressAttributes = [
            'type' => 'billing_address',
            'entity_id' => $this->payment->getId(),
            'name' => 'test user',
            'line1' => 'billing address line 1',
            'country' => self::COUNTRY_CODE,
        ];
        $this->address = $this->fixtures->create(E::ADDRESS, $addressAttributes);
    }

    // mocks masters india client API
    protected function setUpEInvoiceClientMock()
    {
        $this->app['rzp.mode']= 'test';

        $this->eInvoiceClientMock = \Mockery::mock('RZP\Services\EInvoice', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app['einvoice_client'] = $this->eInvoiceClientMock;
    }

    // successful invoice for tax invoice where merchant has INVOICE_RECEIPT_MANDATORY feature enabled
    public function testDCCInvoicePaymentInvoiceReceiptMandatoryFlow()
    {
        $features = [Features::INVOICE_RECEIPT_MANDATORY];

        $this->fixtures->merchant->addFeatures($features);

        $invoice = $this->generateDCCEInvoice(Constants::PAYMENT_FLOW);

        $this->assertEquals($this->payment->getId(), $invoice[Entity::ENTITY_ID]);
        $this->assertEquals($this->payment->getId(), $invoice[Entity::REF_NUM]);
        $this->assertEquals(Status::GENERATED, $invoice[Entity::STATUS]);
        $this->assertEquals(Type::DCC_INV, $invoice[Entity::TYPE]);
        $this->assertEquals(self::INVOICE_REF_NUM, $invoice[Entity::NOTES][Constants::IRN]);
        $this->assertEmpty($invoice[Entity::COMMENT]);
    }

    // successful invoice for tax invoice
    public function testDCCInvoicePaymentFlow()
    {
        $invoice = $this->generateDCCEInvoice(Constants::PAYMENT_FLOW);

        $this->assertEquals($this->payment->getId(), $invoice[Entity::ENTITY_ID]);
        $this->assertEquals($this->payment->getId(), $invoice[Entity::REF_NUM]);
        $this->assertEquals(Status::GENERATED, $invoice[Entity::STATUS]);
        $this->assertEquals(Type::DCC_INV, $invoice[Entity::TYPE]);
        $this->assertEquals(self::INVOICE_REF_NUM, $invoice[Entity::NOTES][Constants::IRN]);
        $this->assertEmpty($invoice[Entity::COMMENT]);
    }

    // successful invoice for credit note
    public function testDCCInvoiceRefundFlow()
    {
        $this->createdRefundAndInvoiceEntity();

        $invoice = $this->generateDCCEInvoice(Constants::REFUND_FLOW);

        $this->assertEquals($this->payment->getId(), $invoice[Entity::ENTITY_ID]);
        $this->assertEquals($this->refund->getId(), $invoice[Entity::REF_NUM]);
        $this->assertEquals(Status::GENERATED, $invoice[Entity::STATUS]);
        $this->assertEquals(Type::DCC_CRN, $invoice[Entity::TYPE]);
        $this->assertEquals(self::INVOICE_REF_NUM, $invoice[Entity::NOTES][Constants::IRN]);
        $this->assertEmpty($invoice[Entity::COMMENT]);
    }

    // failed invoice in case of missing customer details
    public function testDCCInvoiceWithoutCustomerDetails()
    {
        $this->fixtures->edit(E::ADDRESS, $this->address->getId(), ['entity_id' => 'a1b2c3e4f5g6h7']);
        $this->fixtures->edit(E::PAYMENT, $this->payment->getId(), ['card_id' => null]);

        $invoice = $this->generateDCCEInvoice(Constants::PAYMENT_FLOW, 0);

        $this->assertEquals($this->payment->getId(), $invoice[Entity::ENTITY_ID]);
        $this->assertEquals($this->payment->getId(), $invoice[Entity::REF_NUM]);
        $this->assertEquals(Status::FAILED, $invoice[Entity::STATUS]);
        $this->assertArrayNotHasKey(Constants::IRN, $invoice[Entity::NOTES]);
        $this->assertEquals(Constants::BUILDING_REQUEST_DATA_FAILED, $invoice[Entity::COMMENT]);
    }

    // failed invoice in case of error response
    public function testDCCInvoiceWithFailedResponse()
    {
        $invoice = $this->generateDCCEInvoice(Constants::PAYMENT_FLOW, 0, true);

        $this->assertEquals($this->payment->getId(), $invoice[Entity::ENTITY_ID]);
        $this->assertEquals($this->payment->getId(), $invoice[Entity::REF_NUM]);
        $this->assertEquals(Status::FAILED, $invoice[Entity::STATUS]);
        $this->assertArrayNotHasKey(Constants::IRN, $invoice[Entity::NOTES]);
        $this->assertEquals(Constants::INVOICE_REGISTRATION_FAILED, $invoice[Entity::COMMENT]);
    }

    // successful invoice for multiple refunds on same payment
    public function testDCCInvoiceMultipleRefundFlow()
    {
        for ($i = 0; $i < 2; $i++)
        {
            $this->createdRefundAndInvoiceEntity();
            $invoice = $this->generateDCCEInvoice(Constants::REFUND_FLOW);

            $this->setUpEInvoiceClientMock();

            $this->assertEquals($this->payment->getId(), $invoice[Entity::ENTITY_ID]);
            $this->assertEquals($this->refund->getId(), $invoice[Entity::REF_NUM]);
            $this->assertEquals(Status::GENERATED, $invoice[Entity::STATUS]);
            $this->assertEquals(self::INVOICE_REF_NUM, $invoice[Entity::NOTES][Constants::IRN]);
            $this->assertEmpty($invoice[Entity::COMMENT]);
        }

        $entityCount = $this->getDbEntities(E::INVOICE, [Entity::ENTITY_ID => $this->payment->getId(), Entity::TYPE => Type::DCC_CRN])->count();
        $this->assertEquals(2, $entityCount);
    }

    // successful invoice for MCC payment
    public function testDCCInvoiceWithNonINRCurrency()
    {
        $this->payment = $this->fixtures->edit(E::PAYMENT, $this->payment->getId(), ['currency' => 'USD']);
        $this->fixtures->edit(E::PAYMENT_META, $this->payment->paymentMeta->getId(), ['mcc_forex_rate' => self::FOREX_RATE]);

        $invoice = $this->generateDCCEInvoice(Constants::PAYMENT_FLOW);

        $this->assertEquals($this->payment->getId(), $invoice[Entity::ENTITY_ID]);
        $this->assertEquals($this->payment->getId(), $invoice[Entity::REF_NUM]);
        $this->assertEquals(Status::GENERATED, $invoice[Entity::STATUS]);
        $this->assertEquals(self::INVOICE_REF_NUM, $invoice[Entity::NOTES][Constants::IRN]);
        $this->assertEquals($this->getInvoiceAmount(Constants::PAYMENT_FLOW), $invoice[Entity::AMOUNT]);
        $this->assertEmpty($invoice[Entity::COMMENT]);
    }

    // invocation of cron with payload
    public function testDCCInvoiceCronWithPayload()
    {
        Queue::fake();

        $request = self::CRON_REQUEST;
        $request['content'] = [
            Constants::REFERENCE_ID => ['test-id-1', 'test-id-2'],
            Constants::REFERENCE_TYPE => Constants::PAYMENT_FLOW,
        ];

        $this->ba->cronAuth();
        $response = $this->sendRequest($request);
        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals(true, $content['success']);
        Queue::assertPushed(PaymentEInvoice::class);
    }

    // invocation of cron to handle yesterday's failed invoices
    public function testDCCInvoiceCronWithoutPayload()
    {
        Queue::fake();

        $this->createdRefundAndInvoiceEntity();
        $yesterdayTime = Carbon::now(Timezone::IST)->subDay()->getTimestamp();
        $this->fixtures->edit(E::INVOICE, $this->eInvoice->getId(), ['status' => Status::FAILED, 'created_at' => $yesterdayTime]);

        $this->ba->cronAuth();
        $response = $this->sendRequest(self::CRON_REQUEST);
        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals(true, $content['success']);
        Queue::assertPushed(PaymentEInvoice::class);
    }

    // invocation of cron to without any failed invoices to process
    public function testDCCInvoiceCronWithoutPayloadAndWithoutFailedInvoice()
    {
        Queue::fake();

        $this->createdRefundAndInvoiceEntity();
        $yesterdayTime = Carbon::now(Timezone::IST)->subDay()->getTimestamp();
        $this->fixtures->edit(E::INVOICE, $this->eInvoice->getId(), ['created_at' => $yesterdayTime]);

        $this->ba->cronAuth();
        $response = $this->sendRequest(self::CRON_REQUEST);
        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals(true, $content['success']);
        Queue::assertNotPushed(PaymentEInvoice::class);
    }

    // utility method to invoke worker
    protected function generateDCCEInvoice($referenceType, $limit = 1, $isFailed = false)
    {
        $requestData = $this->getRequestContent($referenceType);
        $this->setupEInvoiceClientResponse($requestData, $isFailed);
        $this->mockPDFAndUFHService($limit);

        $payload = [
            Constants::REFERENCE_ID => $referenceType == Constants::PAYMENT_FLOW ? $this->payment->getId() : $this->refund->getId(),
            Constants::REFERENCE_TYPE => $referenceType,
            Constants::MODE         => Mode::TEST,
        ];

        (new PaymentEInvoice($payload))->handle();

        return $this->getLastEntity(E::INVOICE, true);
    }

    // creates refund and invoice entity of payment
    protected function createdRefundAndInvoiceEntity()
    {
        if(isset($this->eInvoice) === false)
        {
            $invoiceAttributes = [
                Entity::ENTITY_ID => $this->payment->getId(),
                Entity::ENTITY_TYPE => E::PAYMENT,
                Entity::TYPE => Type::DCC_INV,
                Entity::REF_NUM => $this->payment->getId(),
                Entity::ORDER_ID => $this->fixtures->create(E::ORDER)->getId(),
                Entity::AMOUNT => $this->getInvoiceAmount(Constants::PAYMENT_FLOW),
                Entity::STATUS => Status::GENERATED,
            ];
            $this->eInvoice = $this->fixtures->create(E::INVOICE, $invoiceAttributes);
        }

        $refundAttributes = [
            'payment' => $this->payment,
            'amount' => self::REFUND_AMOUNT,
        ];
        $this->refund = $this->fixtures->create('refund:from_payment', $refundAttributes);
    }

    // validates and returns masters india mock response
    protected function setupEInvoiceClientResponse($expectedContent, $isFailed = false)
    {
        $this->eInvoiceClientMock
            ->shouldReceive('getEInvoice')
            ->with(\Mockery::on(function (string $mode)  use ($expectedContent)
            {
                if($mode !== Mode::TEST)
                {
                    return false;
                }

                return true;

            }), \Mockery::on(function(array $input) use ($expectedContent)
            {
                $this->assertArraySelectiveEquals($input, $expectedContent);

                return true;
            }))
            ->andReturnUsing(function () use ($isFailed) {
                return $isFailed === true ? self::MOCK_FAILED_RESPONSE : self::MOCK_SUCCESS_RESPONSE;
            });
    }

    // generate mock request data to register invoice
    protected function getRequestContent($referenceType)
    {
        $baseEntity = $referenceType === Constants::PAYMENT_FLOW ? $this->payment : $this->refund;
        $invoiceAmount = $this->getAmountInRupees($this->getInvoiceAmount($referenceType));

        return [
            Constants::ACCESS_TOKEN => 'a78e74508f285f5cd120716b81d8e91f2af96326',
            Constants::USER_GSTIN => Constants::SELLER_ENTITY_DETAILS[Constants::GSTIN],
            Constants::TRANSACTION_DETAILS => [
                Constants::SUPPLY_TYPE => Constants::EXPWOP,
            ],
            Constants::DOCUMENT_DETAILS => [
                Constants::DOCUMENT_TYPE => Constants::TYPE_TO_DOC_TYPE_MAP[Constants::REFERENCE_TYPE_TO_TYPE_MAP[$referenceType]],
                Constants::DOCUMENT_NUMBER => $baseEntity->getId(),
                Constants::DOCUMENT_DATE => Date('d/m/Y', $baseEntity->getCreatedAt()),
            ],
            Constants::SELLER_DETAILS => Constants::SELLER_ENTITY_DETAILS,
            Constants::BUYER_DETAILS => [
                Constants::GSTIN => Constants::UNREGISTERED_PERSON,
                Constants::LEGAL_NAME => $this->address->getName(),
                Constants::ADDRESS_1 => $this->address->getLine1(),
                Constants::LOCATION => $this->address->getCountryName(),
                Constants::PLACE_OF_SUPPLY => Constants::OUT_OF_INDIA,
            ],
            Constants::VALUE_DETAILS => [
                Constants::TOTAL_ASSESSABLE_VALUE => $invoiceAmount,
                Constants::TOTAL_INVOICE_VALUE => $invoiceAmount,
            ],
            Constants::ITEM_LIST => [
                [
                    Constants::ITEM_SERIAL_NUMBER => Constants::SERIAL_NUMBER,
                    Constants::PRODUCT_DESCRIPTION => Constants::DESCRIPTION,
                    Constants::IS_SERVICE => Constants::SERVICE,
                    Constants::HSN_CODE => Constants::ITEM_CODE,
                    Constants::UNIT_PRICE => $invoiceAmount,
                    Constants::TOTAL_AMOUNT => $invoiceAmount,
                    Constants::ASSESSABLE_VALUE => $invoiceAmount,
                    Constants::TOTAL_ITEM_VALUE => $invoiceAmount,
                ],
            ],
        ];
    }

    // converts amount in rupees
    protected function getAmountInRupees($amount)
    {
        return number_format((abs($amount) /100), '2', '.', '');
    }

    // calculates expected invoice amount
    protected function getInvoiceAmount($referenceType)
    {
        if ($referenceType === Constants::PAYMENT_FLOW)
        {
            $invoiceAmount = (self::PAYMENT_AMOUNT * self::DCC_MARK_UP_PERCENT) / 100;
        }
        else
        {
            $invoiceAmount = (self::REFUND_AMOUNT / self::PAYMENT_AMOUNT) * $this->eInvoice->getAmount();
        }
        if ($this->payment->getCurrency() != Currency::INR)
        {
            $invoiceAmount *= self::FOREX_RATE;
        }
        return round($invoiceAmount);
    }

    // mocks PDF and UFH service
    protected function mockPDFAndUFHService($limit=1)
    {
        $tempFile = fopen("testFile.txt", "w");
        fclose($tempFile);

        $pdfMock = \Mockery::mock('RZP\Models\Invoice\DccEInvoicePdfGenerator')->makePartial();
        $this->app->instance('invoice.dccEInvoice', $pdfMock);
        $pdfMock->shouldReceive('generateDccInvoice')->times($limit)->andReturn("testFile.txt");

        $ufhService = \Mockery::mock('RZP\Services\UfhService')->makePartial();
        $this->app->instance('ufh.service', $ufhService);
        $ufhService->shouldReceive('uploadFileAndGetResponse')->times($limit);
    }
}
