<?php

namespace RZP\Tests\Functional\International;

use Queue;
use Mockery;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Factory;

use RZP\Models\Feature\Constants;
use RZP\Models\Invoice\Constants as InvoiceConstants;
use RZP\Models\Payment;
use RZP\Models\Address\Type;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Payment\Constant;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Refund\Speed as RefundSpeed;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\Invoice\InvoiceTestTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Services\RazorXClient;
use RZP\Services\PaymentsCrossBorderClient;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\InternationalIntegration;
use RZP\Jobs\CrossBorder\CrossBorderCommonUseCases;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Tests\Functional\Helpers\PaymentsUpiRecurringTrait;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Admin\ConfigKey;
use RZP\Tests\Traits\MocksSplitz;

class CBPaymentCreateTest extends TestCase
{
    use OAuthTrait;
    use MocksSplitz;
    use PartnerTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use InvoiceTestTrait;
    use TerminalTrait;
    use HeimdallTrait;
    use PaymentsUpiRecurringTrait;

    protected $testData = null;
    protected $payment = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CBPaymentCreateTestData.php';
        parent::setUp();

        $this->ba->publicAuth();

        $this->fixtures->create('terminal:shared_sharp_terminal');
        $this->fixtures->create('terminal:shared_netbanking_icici_terminal');
    }

    //Not needed
    /*public function testOpgspImportPaymentWithAmountGreaterThanOpgspLimit()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '2000100';

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);
            print_r($response);

            $error = $response['error'];
            $this->assertEquals($error['field'], 'amount');
            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Amount exceeds maximum amount allowed.');
        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Amount exceeds maximum amount allowed.');

    }*/

    //Not needed
    /*public function testOpgspImportPaymentWithAmountGreaterThanOpgspLimitConfigKey()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        (new AdminService)->setConfigKeys(
            [
                ConfigKey::DEFAULT_OPGSP_TRANSACTION_LIMIT_USD => "100000"
            ]);

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000100';

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];
            $this->assertEquals($error['field'], 'amount');
            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Amount exceeds maximum amount allowed.');

        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Amount exceeds maximum amount allowed.');

    }*/

    public function testOpgspImportPaymentWithoutInvoiceNumber()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];
            $this->assertEquals($error['field'], 'notes');
            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Invoice number field is required with in the notes.');

        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Invoice number field is required with in the notes.');

    }

    /*public function testOpgspImportDuplicateInvoiceNumberForSuccessfulPayment()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000000';
        $payment['notes'] = [
            'invoice_number' => 'INV123',
        ];

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        // this function makes sure that checks for card rearch pass
        $this->mockPGRouterForRearch();

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        // opgps payment should not go through rearch
        $this->assertArrayNotHasKey('pg_router', $responseContent);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);

        $this->assertArrayHasKey('next', $responseContent);

        $this->assertArrayHasKey('action', $responseContent['next'][0]);

        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToAddressCollectUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/address_collect');

        $this->redirectToAddressCollect= true;

        $url = $this->getPaymentRedirectToAddressCollectUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();
        $this->redirectToUpdateAndAuthorize = true;

        list($url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');

        $content['billing_address'] = $this->getDefaultBillingAddressArray();
        $content['billing_address']['first_name'] = 'First';
        $content['billing_address']['last_name'] = 'Rahul';

        $firstRequest = [
            'content'=>$content,
            'method'=>$method,
            'url'=>$url
        ];
        $firstResponse=$this->sendRequest($firstRequest);

        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getLastEntity('invoice', true);
        $this->assertEquals($paymentSupportingDocs['entity_id'], $paymentEntity['id'] );
        $this->assertEquals($paymentSupportingDocs['type'],'opgsp_invoice');
        $this->assertEquals($paymentSupportingDocs['receipt'],'INV123');
        $this->validatePaymentBillingAddress($paymentEntity, $content['billing_address']);

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];
            $this->assertEquals($error['field'], 'notes');
            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment already exist with same invoice number.');

        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Payment already exist with same invoice number.');
    }*/

    public function testOpgspImportDuplicateInvoiceNumberForFailedPayment()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $order = $this->fixtures->create('order',
            [
                'amount' => 1000000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000000';
        $payment['currency'] = 'INR';
        $payment['order_id'] = $order->getPublicId();
        $payment['notes'] = [
            'invoice_number' => 'INV123',
        ];

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        // this function makes sure that checks for card rearch pass
        $this->mockPGRouterForRearch();

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getLastEntity('invoice', true);
        $this->assertEquals($paymentSupportingDocs['entity_id'], $paymentEntity['id'] );
        $this->assertEquals($paymentSupportingDocs['type'],'opgsp_invoice');
        $this->assertEquals($paymentSupportingDocs['receipt'],'INV123');

        $this->fixtures->edit('payment', $responseContent['razorpay_payment_id'], ['status' => 'failed']);

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getLastEntity('invoice', true);
        $this->assertEquals($paymentSupportingDocs['entity_id'], $paymentEntity['id'] );
        $this->assertEquals($paymentSupportingDocs['type'],'opgsp_invoice');
        $this->assertEquals($paymentSupportingDocs['receipt'],'INV123');
    }

    public function testOpgspImportPaymentWithUnsupportedLibrary()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000000';

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doAuthPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'The payment request has invalid library');

        },
            \RZP\Exception\BadRequestException::class,
            'The payment request has invalid library');

    }

    public function testOpgspImportPaymentWithUnsupportedMethod()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['amount'] = '1000000';

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);
        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment method invalid / not allowed');

        },
            \RZP\Exception\BadRequestException::class,
            'Payment method invalid / not allowed');

    }


    //checking  two entries opgsp_invoice and opgsp_awb will be created after a payment in invoice table
    public function testOpgspImportForAWB()
    {
        $merchantId = "10000000000000";
        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0101',
        ];
        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);
        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];
        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);
        $payment = $this->getDefaultPaymentArray();
        $payment['amount'] = '1000000';
        $payment['notes'] = [
            'invoice_number' => 'AWBINV12'
        ];
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);
        $this->mockPGRouterForRearch();
        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);
        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getDbEntities('invoice', ['entity_id' =>$paymentEntity['id']]);
        $this->assertEquals($paymentSupportingDocs[0]['type'],'opgsp_awb');
        $this->assertEquals($paymentSupportingDocs[1]['type'],'opgsp_invoice');
        $this->assertEquals($paymentSupportingDocs[0]['receipt'], 'AWBINV12');
        $this->assertEquals($paymentSupportingDocs[1]['receipt'], 'AWBINV12');
    }


    //checking for invalidpurposecode for awb as invoice will be strored
    public function testOPGSPImportForAWBInvalidPurposeCode()
    {
        $merchantId = "10000000000000";
        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0104',
        ];
        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];
        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);
        $payment = $this->getDefaultPaymentArray();
        $payment['amount'] = '1000000';
        $payment['notes'] = [
            'invoice_number' => 'AWBINV12'
        ];
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        // this function makes sure that checks for card rearch pass
        $this->mockPGRouterForRearch();
        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);
        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getDbEntities('invoice', ['entity_id' =>$paymentEntity['id']]);
        $this->assertNotEquals($paymentSupportingDocs[0]['type'],'opgsp_awb');
        $this->assertEquals($paymentSupportingDocs[0]['type'],'opgsp_invoice');
    }

    //checking ref num  will be set after single upload in invoice table for awb
    public function testOpgspImportForSingleUploadAWB()
{
    $merchantId = "10000000000000";
    $merchantAttribute = [
        MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        'purpose_code' => 'S0101',
    ];

    $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
    $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'opgsp_import_flow', 'opgsp_settlement', 'import_settlement']);

    $merchantDetailAttribute = [
        DetailEntity::MERCHANT_ID => $merchantId,
    ];
    $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

    $this->fixtures->create('merchant_international_integrations', [
        InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
        InternationalIntegration\Entity::INTEGRATION_ENTITY => 'opgsp_import_flow',
        InternationalIntegration\Entity::INTEGRATION_KEY => 'opgsp_import_flow',
        InternationalIntegration\Entity::NOTES => [
            'purpose_code' => 'S0101',
        ],
    ]);
    // create order
    $order = $this->fixtures->create('order',
        [
            'amount' => 1000,
            'currency' => 'INR',
            'customer_id' => '100000customer',
        ]);
    $this->fixtures->create('order_meta',
        [
            'order_id' => $order->getId(),
            'value' => self::getOrderMetaValue(),
            'type' => 'cart_info',
        ]);

    // create payment
    $payment = $this->getDefaultPaymentArray();

    $payment['amount'] = '1000';
    $payment['currency'] = 'INR';
    $payment['order_id'] = $order->getPublicId();
    $payment['notes'] = [
        'invoice_number' => '1234567890qwertyuiop'
    ];

    $response = $this->doS2SPrivateAuthPayment($payment);

    $this->assertArrayHasKey('razorpay_payment_id', $response);
    $this->assertArrayHasKey('razorpay_order_id', $response);
    $this->assertArrayHasKey('razorpay_signature', $response);
    $this->assertEquals($order->getPublicId(), $response['razorpay_order_id']);

    // validate payment authorized and details saved
    $paymentEntity = $this->getDbLastPayment();
    $this->assertEquals('authorized', $paymentEntity['status']);
    $this->assertEquals($order->getId(), $paymentEntity['order_id']);
    $this->assertEquals($payment['notes']['invoice_number'], $paymentEntity['notes']['invoice_number']);

    // validate payment invoice updated
    $paymentInvoice = $this->getDbEntities('invoice', ['entity_id' => $paymentEntity['id']]);
    $this->assertEquals($paymentEntity['id'], $paymentInvoice[0]['entity_id']);
    $this->assertEquals($paymentInvoice[0]['type'], 'opgsp_awb');
    $this->assertNull($paymentInvoice[0]['ref_num']);

    // capture payment
    $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

    // validate payment captured and txn  on_hold
    $paymentEntity = $this->getDbLastPayment();
    $transactionEntity = $paymentEntity->transaction;

    $this->assertEquals('captured', $paymentEntity['status']);
    $this->assertTrue($paymentEntity['captured']);

    $this->assertEquals($paymentEntity['amount'], $transactionEntity['amount']);
    $this->assertTrue($transactionEntity['on_hold']);

    $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);
    $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

    Queue::fake();

    $request = [
        'url' => '/payment/' . $paymentEntity['id'] . '/update_merchant_doc',
        'method' => 'patch',
        'content' => [
            'document_id' => "12345678901234",
            'document_type' => "opgsp_awb"
        ]
    ];
    $response = $this->makeRequestAndGetContent($request);
    $this->assertEquals(true, $response['document_updated']);
    $paymentSupportingDocs = $this->getDbEntities('invoice', ['entity_id' => $paymentEntity['id'], 'type' => 'opgsp_awb']);
    $this->assertNotNull($paymentSupportingDocs[0]['ref_num']);

    Queue::assertPushed(CrossBorderCommonUseCases::class);

    $data = [
        'merchant_id' => $merchantId,
        'action' => CrossBorderCommonUseCases::OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT,
        'payment_id' => $paymentInvoice[0]['entity_id'],
        'mode' => 'test',
    ];
    (new CrossBorderCommonUseCases($data))->handle();
    // validate  txn not on_hold
    $paymentEntity = $this->getDbLastPayment();
    $transactionEntity = $paymentEntity->transaction;
    $this->assertFalse($transactionEntity['on_hold']);

}
    /*public function testOpgspImportPaymentPositive()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000000';
        $payment['notes'] = [
            'invoice_number' => 'INV123',
        ];

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        // this function makes sure that checks for card rearch pass
        $this->mockPGRouterForRearch();

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        // opgps payment should not go through rearch
        $this->assertArrayNotHasKey('pg_router', $responseContent);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);

        $this->assertArrayHasKey('next', $responseContent);

        $this->assertArrayHasKey('action', $responseContent['next'][0]);

        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToAddressCollectUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/address_collect');

        $this->redirectToAddressCollect= true;

        $url = $this->getPaymentRedirectToAddressCollectUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();
        $this->redirectToUpdateAndAuthorize = true;

        list($url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');

        $content['billing_address'] = $this->getDefaultBillingAddressArray();
        $content['billing_address']['first_name'] = 'First';
        $content['billing_address']['last_name'] = 'Rahul';

        $firstRequest = [
            'content'=>$content,
            'method'=>$method,
            'url'=>$url
        ];
        $firstResponse=$this->sendRequest($firstRequest);

        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getLastEntity('invoice', true);
        $this->assertEquals($paymentSupportingDocs['entity_id'], $paymentEntity['id'] );
        $this->assertEquals($paymentSupportingDocs['type'],'opgsp_invoice');
        $this->assertEquals($paymentSupportingDocs['receipt'],'INV123');
        $this->validatePaymentBillingAddress($paymentEntity, $content['billing_address']);

    }*/

    /*public function testOpgspImportPaymentNBPositive()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['amount'] = '1000000';
        $payment['notes'] = [
            'invoice_number' => 'INV123',
        ];

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        // this function makes sure that checks for NB rearch pass
        $this->mockPGRouterForRearch();
        $order = $this->fixtures->order->createPaymentCaptureOrder(['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        // opgps payment should not go through rearch
        $this->assertArrayNotHasKey('pg_router', $responseContent);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);

        $this->assertArrayHasKey('next', $responseContent);

        $this->assertArrayHasKey('action', $responseContent['next'][0]);

        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToAddressCollectUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/address_collect');

        $this->redirectToAddressCollect= true;

        $url = $this->getPaymentRedirectToAddressCollectUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();
        $this->redirectToUpdateAndAuthorize = true;

        list($url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');

        $content['billing_address'] = $this->getDefaultBillingAddressArray();
        $content['billing_address']['first_name'] = 'First';
        $content['billing_address']['last_name'] = 'Rahul';

        $firstRequest = [
            'content'=>$content,
            'method'=>$method,
            'url'=>$url
        ];
        $firstResponse=$this->sendRequest($firstRequest);

        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getLastEntity('invoice', true);
        $this->assertEquals($paymentSupportingDocs['entity_id'], $paymentEntity['id'] );
        $this->assertEquals($paymentSupportingDocs['type'],'opgsp_invoice');
        $this->assertEquals($paymentSupportingDocs['receipt'],'INV123');
        $this->validatePaymentBillingAddress($paymentEntity, $content['billing_address']);
    }*/

    /*public function testUpdateMerchantDocumentForPayment()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['opgsp_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['amount'] = '1000000';
        $payment['notes'] = [
            'invoice_number' => 'INV123',
        ];

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $responseContent = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $responseContent);

        $this->assertArrayHasKey('next', $responseContent);

        $this->assertArrayHasKey('action', $responseContent['next'][0]);

        $this->assertArrayHasKey('url', $responseContent['next'][0]);

        $redirectContent = $responseContent['next'][0];

        $this->assertTrue($this->isRedirectToAddressCollectUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/address_collect');

        $this->redirectToAddressCollect= true;

        $url = $this->getPaymentRedirectToAddressCollectUrl($id);

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);
        $this->ba->publicAuth();

        $content = $infoResponse->getContent();
        $this->redirectToUpdateAndAuthorize = true;

        list($url, $method, $content) = $this->getFormDataFromResponse($content, 'http://localhost');

        $content['billing_address'] = $this->getDefaultBillingAddressArray();
        $content['billing_address']['first_name'] = 'First';
        $content['billing_address']['last_name'] = 'Rahul';

        $firstRequest = [
            'content'=>$content,
            'method'=>$method,
            'url'=>$url
        ];
        $firstResponse=$this->sendRequest($firstRequest);

        $paymentEntity = $this->getDbLastPayment();
        $paymentSupportingDocs = $this->getLastEntity('invoice', true);
        $this->assertEquals($paymentSupportingDocs['entity_id'], $paymentEntity['id'] );
        $this->assertEquals($paymentSupportingDocs['type'],'opgsp_invoice');
        $this->assertEquals($paymentSupportingDocs['receipt'],'INV123');
        $this->validatePaymentBillingAddress($paymentEntity, $content['billing_address']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId , $merchantUser['id']);

        $request = [
            'url'    => '/payment/'.$paymentEntity['id'].'/update_merchant_doc',
            'method' => 'patch',
            'content' => [
                'document_id' => "doc_1234567890",
                'document_type' => "opgsp_invoice"
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['document_updated']);

        $paymentSupportingDocs = $this->getLastEntity('invoice', true);

        $this->assertEquals($paymentSupportingDocs['ref_num'],'doc_1234567890');
    }*/

    /**
     * @param $paymentEntity
     * @param array $billingAddressArray
     */
    protected function validatePaymentBillingAddress($paymentEntity, array $billingAddressArray): void
    {
        $paymentAddressEntity = (new \RZP\Models\Address\Repository)->fetchPrimaryAddressOfEntityOfType($paymentEntity, Type::BILLING_ADDRESS);

        $this->assertNotNull($paymentAddressEntity);

        $this->validateBillingAddress($billingAddressArray, $paymentAddressEntity);
    }

    /**
     * @param $postal_code
     * @param $addressEntity
     */
    private function validateBillingAddress($billingAddress, $addressEntity): void
    {
        foreach (['line1', 'line2', 'city', 'state', 'country'] as $attribute) {
            $this->assertEquals($billingAddress[$attribute], $addressEntity[$attribute]);
        }

        $this->assertEquals($billingAddress['postal_code'], $addressEntity['zipcode']);
    }

    private function mockPGRouterForRearch()
    {
        $this->enablePgRouterConfig();

        // mock the experiments for s2s payment
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();
        $this->app->instance('razorx', $razorxMock);
        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 's2s_card_payments_via_pg_router_v2' or
                        $feature === 'netbanking_payments_via_pg_router_disable_mid' or
                        $feature === 'netbanking_payments_via_pg_router_create_json' or
                        $feature === 'netbanking_payments_via_pg_router')
                    {
                        return 'on';
                    }
                    return 'off';
                }));

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();
        $this->app->instance('pg_router', $pgService);
        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout)
            {
                return [
                    'body' => [
                        'pg_router' => 'true'
                    ]
                ];
            });
    }

    public function testLRSEducationPaymentWithoutTPV()
    {
        $this->fixtures->merchant->addFeatures(['lrs_education_flow']);

        $payment = $this->getDefaultPaymentArray();
        $payment['_']['library'] = 'checkoutjs';

        $this->makeRequestAndCatchException(function () use ($payment) {
            $response = $this->doAuthPaymentViaAjaxRoute($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'SERVER_ERROR');
            $this->assertEquals($error['description'], 'Failed to complete request');

        },
            \RZP\Exception\ServerErrorException::class,
            'Failed to complete request'
        );
    }

    public function testLRSEducationPaymentWithUnsupportedLibrary()
    {
        $this->fixtures->merchant->addFeatures(['lrs_education_flow', 'tpv', 's2s', 's2s_json']);

        $payment = $this->getDefaultPaymentArray();

        $this->makeRequestAndCatchException(function () use ($payment) {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'The payment request has invalid library');

        },
            \RZP\Exception\BadRequestException::class,
            'The payment request has invalid library'
        );
    }

    public function testLRSEducationPaymentWithUnsupportedMethod()
    {
        $this->fixtures->merchant->addFeatures(['lrs_education_flow', 'tpv']);

        $payment = $this->getDefaultPaymentArray();
        $payment['_']['library'] = 'checkoutjs';

        $this->makeRequestAndCatchException(function () use ($payment) {
            $response = $this->doAuthPaymentViaAjaxRoute($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment method invalid / not allowed');

        },
            \RZP\Exception\BadRequestException::class,
            'Payment method invalid / not allowed');
    }

    public function testLRSEducationPaymentWithUnsupportedCurrency()
    {
        $this->fixtures->merchant->addFeatures(['lrs_education_flow', 'tpv']);
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();
        $order = $this->createOrder([
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
            'bank_account' => [
                'account_number' => '765432123456789',
                'name' => 'test user',
                'ifsc' => 'ICIC0006561',
            ],
        ]);
        $payment['_']['library'] = 'checkoutjs';
        $payment['order_id'] = $order['id'];
        $payment['bank'] = 'ICIC';

        $this->makeRequestAndCatchException(function () use ($payment) {
            $response = $this->doAuthPaymentViaAjaxRoute($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Currency is not supported');

        },
            \RZP\Exception\BadRequestException::class,
            'Currency is not supported');
    }

    public function testLRSEducationPaymentWithoutOrder()
    {
        $this->fixtures->merchant->addFeatures(['lrs_education_flow', 'tpv']);

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['currency'] = 'USD';
        $payment['_']['library'] = 'checkoutjs';

        $this->makeRequestAndCatchException(function () use ($payment) {
            $response = $this->doAuthPaymentViaAjaxRoute($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Order id is mandatory for payment');

        },
            \RZP\Exception\BadRequestException::class,
            'Order id is mandatory for payment');
    }

    public function testLRSEducationPaymentPositive()
    {
        $this->fixtures->merchant->addFeatures(['lrs_education_flow', 'tpv']);
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => true]);

        $this->setMockForPCBClient();

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['currency'] = 'USD';
        $order = $this->createOrder([
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
            'bank_account' => [
                'account_number' => '765432123456789',
                'name' => 'test user',
                'ifsc' => 'ICIC0006561',
            ],
        ]);
        $payment['_']['library'] = 'checkoutjs';
        $payment['order_id'] = $order['id'];
        $payment['bank'] = 'ICIC';

        $this->doAuthPaymentViaAjaxRoute($payment);

        $lastPayment = $this->getLastEntity('payment');

        $this->assertSame('authorized', $lastPayment['status']);
    }

    public function testCitiTravelLrsPaymentWithUnsupportedLibrary()
    {
        $this->fixtures->merchant->addFeatures(['lrs_travel_citi_flow', 's2s', 's2s_json']);

        $payment = $this->getDefaultPaymentArray();

        $this->makeRequestAndCatchException(function () use ($payment) {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'The payment request has invalid library');

        },
            \RZP\Exception\BadRequestException::class,
            'The payment request has invalid library'
        );
    }

    public function testCitiTravelLrsWithUnsupportedMethod()
    {
        $this->fixtures->merchant->addFeatures(['lrs_travel_citi_flow']);


        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');
        $payment['_']['library'] = 'checkoutjs';

        $payment['amount'] = '1000000';

        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');

        $this->makeRequestAndCatchException(function () use ($payment) {
            $response = $this->doAuthPaymentViaAjaxRoute($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment method invalid / not allowed');

        },
            \RZP\Exception\BadRequestException::class,
            'Payment method invalid / not allowed');
    }

    public function testCitiTravelLrsPaymentWithUnsupportedCurrency()
    {
        $merchantId = "10000000000000";
        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'convert_currency' => true,
            'international' => false,
        ];
        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $this->fixtures->merchant->addFeatures(['lrs_travel_citi_flow']);
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['currency'] = 'USD';
        $order = $this->createOrder([
            'amount'            => '50000',
            'currency'          => 'USD',
            'bank_account' => [
                'account_number' => '765432123456789',
                'name' => 'test user',
                'ifsc' => 'ICIC0006561',
            ],
        ]);
        $payment['_']['library'] = 'checkoutjs';
        $payment['order_id'] = $order['id'];
        $payment['bank'] = 'ICIC';

        $this->makeRequestAndCatchException(function () use ($payment) {
            $response = $this->doAuthPaymentViaAjaxRoute($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Currency is not supported');

        },
            \RZP\Exception\BadRequestException::class,
            'Currency is not supported');
    }

    public function testCitiTravelLrsPaymentWithoutOrder()
    {
        $this->fixtures->merchant->addFeatures(['lrs_travel_citi_flow']);
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['_']['library'] = 'checkoutjs';

        $this->makeRequestAndCatchException(function () use ($payment) {
            $response = $this->doAuthPaymentViaAjaxRoute($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');

        },
            \RZP\Exception\BadRequestException::class,
            'Payment processing failed due to missing order id');
    }

    public function testCitiTravelLrsPaymentPositive()
    {
        $this->fixtures->merchant->addFeatures(['lrs_travel_citi_flow']);
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $payment = $this->getDefaultUpiPaymentArray();
        $order = $this->createOrder([
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
        ]);
        $payment['_']['library'] = 'checkoutjs';
        $payment['order_id'] = $order['id'];
        $payment['bank'] = 'ICIC';
        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop'
        ];

        $this->doAuthPaymentViaAjaxRoute($payment);

        $lastPayment = $this->getLastEntity('payment');

        $this->assertEquals($payment['notes']['invoice_number'], $lastPayment['notes']['invoice_number']);

        // validate payment invoice updated
        $paymentInvoice = $this->getLastEntity('invoice', true);
        $this->assertEquals($lastPayment['id'], 'pay_'. $paymentInvoice['entity_id']);
        $this->assertEquals('citi_invoice', $paymentInvoice['type']);
        $this->assertEquals($lastPayment['notes']['invoice_number'], $paymentInvoice['receipt']);
        $this->assertNull($paymentInvoice['ref_num']);
    }
    public function setMockForPCBClient()
    {
        $mockResponseGetLRSQuote =[
            'data' => [
                'converted_amount' => 40228,
                'converted_currency' => 'INR',
                'exchange_rate' => 80.12,
                'fees' => [
                    'gst' => 20108,
                    'tcs' => 12108,
                ],
            ],
        ];

        $mockResponseUpdatePaymentStatus = [
            'data' => [
                'status' => 'success',
            ],
        ];

        $pxbServiceMock = $this->getMockBuilder(PaymentsCrossBorderClient::class)
            ->onlyMethods(['getLRSQuote','updatePaymentStatus'])->getMock();

        $this->app->instance('payments-cross-border', $pxbServiceMock);
        $pxbServiceMock->method("getLRSQuote")
            ->willReturn($mockResponseGetLRSQuote);
        $pxbServiceMock->method("updatePaymentStatus")
            ->willReturn($mockResponseUpdatePaymentStatus);
    }

    public function testLRSEducationNbPaymentAuthorized()
    {
        $this->fixtures->merchant->addFeatures(['lrs_education_flow', 'tpv']);
        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => true]);

        $this->payment = $this->getDefaultNetbankingPaymentArray();
        $this->setMockForPCBClient();

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['currency'] = 'USD';
        $order = $this->createOrder([
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
            'bank_account' => [
                'account_number' => '765432123456789',
                'name' => 'test user',
                'ifsc' => 'ICIC0006561',
            ],
        ]);
        $payment['_']['library'] = 'checkoutjs';
        $payment['order_id'] = $order['id'];
        $payment['bank'] = 'ICIC';

        $this->doAuthPaymentViaAjaxRoute($payment);

        $lastPayment = $this->getLastEntity('payment');

        $this->assertSame('authorized', $lastPayment['status']);
    }

    public function testLRSEducationNbPaymentAuthorizedConvertCurrencyDisabled()
    {
        $this->fixtures->merchant->addFeatures(['lrs_education_flow', 'tpv']);
        $this->fixtures->merchant->enableMethod('10000000000000', 'netbanking');
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => false]);

        $this->setMockForPCBClient();

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['currency'] = 'USD';
        $order = $this->createOrder([
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
            'bank_account' => [
                'account_number' => '765432123456789',
                'name' => 'test user',
                'ifsc' => 'ICIC0006561',
            ],
        ]);
        $payment['_']['library'] = 'checkoutjs';
        $payment['order_id'] = $order['id'];
        $payment['bank'] = 'ICIC';

        $this->doAuthPaymentViaAjaxRoute($payment);

        $lastPayment = $this->getLastEntity('payment');

        $this->assertSame('authorized', $lastPayment['status']);

    }

    public function testLRSEducationPaymentCaptureFailure()
    {
        $this->setMockForPCBClient();
        $this->captureLRSPayment($this->makeLRSAuthPayment());
    }

    public function testLRSEducationPaymentInternalCapture()
    {
        $this->setMockForPCBClient();
        $this->captureLRSPayment($this->makeLRSAuthPayment(), true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);
    }

    public function testLRSEducationRefund()
    {
        $this->setMockForPCBClient();
        $this->makeLRSAuthPayment();
        $this->gateway = Gateway::UPI_MINDGATE;

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $refund = $this->refundAuthorizedPayment($this->payment['payment_id']);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));
        $refund = $this->getLastEntity('refund', true);
        $this->assertEquals(true, $refund['gateway_refunded']);
        $this->assertEquals(RefundSpeed::NORMAL, $refund['speed_processed']);
    }

    protected function makeLRSAuthPayment()
    {
        $this->fixtures->merchant->addFeatures(['lrs_education_flow', 'tpv']);
        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => true]);

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['currency'] = 'USD';
        $order = $this->createOrder([
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
            'bank_account' => [
                'account_number' => '765432123456789',
                'name' => 'test user',
                'ifsc' => 'ICIC0006561',
            ],
        ]);
        $payment['_']['library'] = 'checkoutjs';
        $payment['order_id'] = $order['id'];
        $payment['bank'] = 'ICIC';

        $this->payment = $this->doAuthPaymentViaAjaxRoute($payment);
        return $payment;
    }

    protected function captureLRSPayment($payment, $internal=false)
    {
        $this->ba->privateAuth();
        $this->startTest($this->payment['payment_id'], $payment['amount'], $internal);
    }

    public function startTest($id = null, $amount = null, $internal = false)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $name = $trace[2]['function'];

        $testData = $this->testData[$name];

        $this->setRequestData($testData['request'], $id, $amount, $internal);

        return $this->runRequestResponseFlow($testData);
    }

    protected function setRequestData(& $request, $id = null, $amount = null, $internal = false)
    {
        $this->checkAndSetIdAndAmount($id, $amount);

        $request['content']['amount'] = $amount;

        $url = '/payments/'.$id.'/capture';
        if ($internal)
        {
            $this->ba->paymentsCrossBorderAppAuth();
        }

        $this->setRequestUrlAndMethod($request, $url, 'POST');
    }

    protected function checkAndSetIdAndAmount(& $id = null, & $amount = null)
    {
        if ($id === null)
        {
            $id = $this->payment['id'];
        }

        if ($amount === null)
        {
            if (isset($this->payment['amount']))
                $amount = $this->payment['amount'];
        }
    }

    public function testJPMCImportFlowPaymentWithInvoiceNumberSuccessTransactionNotOnHold()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        // create order
        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        // create payment
        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertArrayHasKey('razorpay_order_id', $response);
        $this->assertArrayHasKey('razorpay_signature', $response);
        $this->assertEquals($order->getPublicId(), $response['razorpay_order_id']);

        // validate payment authorized and details saved
        $paymentEntity = $this->getDbLastPayment();

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($order->getId(), $paymentEntity['order_id']);
        $this->assertEquals($payment['notes']['invoice_number'], $paymentEntity['notes']['invoice_number']);
        $this->assertEquals($payment['notes']['goods_description'], $paymentEntity['notes']['goods_description']);

        // validate payment invoice updated
        $paymentInvoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($paymentEntity['id'], $paymentInvoice['entity_id']);
        $this->assertEquals('jpmc_invoice', $paymentInvoice['type']);
        $this->assertEquals($paymentEntity['notes']['invoice_number'], $paymentInvoice['receipt']);
        $this->assertNull($paymentInvoice['ref_num']);

        // capture payment
        $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

        // validate payment captured and txn not on_hold
        $paymentEntity = $this->getDbLastPayment();
        $transactionEntity = $paymentEntity->transaction;

        $this->assertEquals('captured', $paymentEntity['status']);
        $this->assertTrue($paymentEntity['captured']);

        $this->assertEquals($paymentEntity['amount'], $transactionEntity['amount']);
        $this->assertFalse($transactionEntity['on_hold']);

    }

    public function testJPMCImportFlowPaymentWithoutGoodsDescription()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);
        $this->fixtures->merchant->enableMethod($merchantId, 'upi');

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        // create order
        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        // create payment
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop'
        ];

        $response = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        // validate payment authorized and details saved
        $paymentEntity = $this->getDbLastPayment();

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($order->getId(), $paymentEntity['order_id']);
        $this->assertEquals(Constant::JPMC_IMPORT_FLOW_GOODS_DESCRIPTION_DEFAULT_OTHER, $paymentEntity['notes']['goods_description']);

    }

    public function testJPMCImportFlowPaymentWithUnsupportedLibrary()
    {
        $this->markTestSkipped('Now Standard checkout also supported on JPMC');

        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doAuthPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'The payment request has invalid library');

        },
            \RZP\Exception\BadRequestException::class,
            'The payment request has invalid library');
    }

    public function testJPMCImportFlowPaymentWithUnsupportedMethod()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $payment = $this->getDefaultWalletPaymentArray('airtelmoney');

        $payment['amount'] = '1000';

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->fixtures->merchant->enableWallet('10000000000000', 'airtelmoney');
        $this->fixtures->merchant->addFeatures(['email_optional', 'contact_optional']);

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment method invalid / not allowed');

        },
            \RZP\Exception\BadRequestException::class,
            'Payment method invalid / not allowed');
    }

    public function testJPMCImportFlowPaymentWithUnsupportedRecurringMethod()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow', 'charge_at_will']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $orderId = $this->createUpiRecurringOrder(['amount' => '1000']);

        $payment = $this->getDefaultUpiRecurringPaymentArray();

        $payment['amount'] = '1000';

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $payment['order_id'] = $orderId;

        $payment['customer_id'] = 'cust_100000customer';

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'UPI transactions are not enabled for the merchant');

        },
            \RZP\Exception\BadRequestException::class,
            'UPI transactions are not enabled for the merchant');
    }

    public function testJPMCImportFlowPaymentWithNoOrder()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment processing failed due to missing order id');

        },
            \RZP\Exception\BadRequestException::class,
            'Payment processing failed due to missing order id');
    }

    public function testJPMCImportFlowPaymentWithoutCustomerIDInOrder()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                // 'customer_id' => 'cust_100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment does not have a customer_id.');

        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Payment does not have a customer_id.');
    }

    public function testJPMCImportFlowPaymentWithInvalidCurrency()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'AWG',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'AWG';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Currency is not supported');

        },
            \RZP\Exception\BadRequestException::class,
            'Currency is not supported');
    }

    public function testJPMCImportFlowPaymentWithInvalidPurposeCode()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'P0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_VALIDATION_FAILURE');
            $this->assertEquals($error['description'], 'Merchant Purpose Code is invalid');

        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Merchant Purpose Code is invalid');
    }

    public function testJPMCImportFlowPaymentWithDuplicateInvoiceNumberFailed_RefundedCaptured()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        $firstPayment = $this->fixtures->create('payment:authorized', [
            'merchant_id'   => $merchantId,
            'currency'      => 'INR',
            'amount'        => '1000',
            'notes'         => [
                'invoice_number'    => '1234567890qwertyuiop',
                'goods_description' => 'sample description for goods or services',
            ],
            'authorized_at' => strtotime('2021-01-01 00:00:00 UTC'),
            'captured_at' => strtotime('2021-01-01 00:00:00 UTC'),
        ]);

        $this->fixtures->edit('payment', $firstPayment->getId(), ['status' => 'refunded']);

        $firstInvoice = $this->fixtures->create('invoice', [
            'type'          => 'jpmc_invoice',
            'receipt'       => $firstPayment->getNotes()->toArray()['invoice_number'],
            'order_id'      => $this->fixtures->create('order')->getId(),
            'entity_id'     => $firstPayment->getId(),
            'entity_type'   => 'payment',
            'merchant_id'   => $merchantId,
        ]);

        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment already exist with same invoice number.');

        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Payment already exist with same invoice number.');
    }

    public function testJPMCImportFlowPaymentWithDuplicateInvoiceNumberFailed_AuthorizedCaptured()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        $firstPayment = $this->fixtures->create('payment:authorized', [
            'merchant_id'   => $merchantId,
            'currency'      => 'INR',
            'amount'        => '1000',
            'notes'         => [
                'invoice_number'    => '1234567890qwertyuiop',
                'goods_description' => 'sample description for goods or services',
            ],
            'authorized_at' => strtotime('2021-01-01 00:00:00 UTC'),
            'captured_at' => strtotime('2021-01-01 00:00:00 UTC'),
        ]);

        $firstInvoice = $this->fixtures->create('invoice', [
            'type'          => 'jpmc_invoice',
            'receipt'       => $firstPayment->getNotes()->toArray()['invoice_number'],
            'order_id'      => $this->fixtures->create('order')->getId(),
            'entity_id'     => $firstPayment->getId(),
            'entity_type'   => 'payment',
            'merchant_id'   => $merchantId,
        ]);

        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment already exist with same invoice number.');

        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Payment already exist with same invoice number.');
    }

    public function testJPMCImportFlowPaymentWithDuplicateInvoiceNumberFailed_AuthorizedNotCaptured()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        $firstPayment = $this->fixtures->create('payment:authorized', [
            'merchant_id'   => $merchantId,
            'currency'      => 'INR',
            'amount'        => '1000',
            'notes'         => [
                'invoice_number'    => '1234567890qwertyuiop',
                'goods_description' => 'sample description for goods or services',
            ],
            'authorized_at' => strtotime('2021-01-01 00:00:00 UTC'),
            'captured_at' => null,
        ]);

        $firstInvoice = $this->fixtures->create('invoice', [
            'type'          => 'jpmc_invoice',
            'receipt'       => $firstPayment->getNotes()->toArray()['invoice_number'],
            'order_id'      => $this->fixtures->create('order')->getId(),
            'entity_id'     => $firstPayment->getId(),
            'entity_type'   => 'payment',
            'merchant_id'   => $merchantId,
        ]);

        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment already exist with same invoice number.');

        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Payment already exist with same invoice number.');
    }

    public function testJPMCImportFlowPaymentWithDuplicateInvoiceNumberSuccess()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        $firstPayment = $this->fixtures->create('payment:authorized', [
            'merchant_id'   => $merchantId,
            'currency'      => 'INR',
            'amount'        => '1000',
            'notes'         => [
                'invoice_number'    => '1234567890qwertyuiop',
                'goods_description' => 'sample description for goods or services',
            ],
            'authorized_at' => strtotime('2021-01-01 00:00:00 UTC'),
            'captured_at' => null,
        ]);

        $this->fixtures->edit('payment', $firstPayment->getId(), ['status' => 'refunded']);

        $firstInvoice = $this->fixtures->create('invoice', [
            'type'          => 'jpmc_invoice',
            'receipt'       => $firstPayment->getNotes()->toArray()['invoice_number'],
            'order_id'      => $this->fixtures->create('order')->getId(),
            'entity_id'     => $firstPayment->getId(),
            'entity_type'   => 'payment',
            'merchant_id'   => $merchantId,
        ]);

        // create order
        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        // create payment
        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertArrayHasKey('razorpay_order_id', $response);
        $this->assertArrayHasKey('razorpay_signature', $response);
        $this->assertEquals($order->getPublicId(), $response['razorpay_order_id']);

        // validate payment authorized and details saved
        $paymentEntity = $this->getDbLastPayment();

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($order->getId(), $paymentEntity['order_id']);
        $this->assertEquals($payment['notes']['invoice_number'], $paymentEntity['notes']['invoice_number']);
        $this->assertEquals($payment['notes']['goods_description'], $paymentEntity['notes']['goods_description']);

        // validate payment invoice updated
        $paymentInvoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($paymentEntity['id'], $paymentInvoice['entity_id']);
        $this->assertEquals('jpmc_invoice', $paymentInvoice['type']);
        $this->assertEquals($paymentEntity['notes']['invoice_number'], $paymentInvoice['receipt']);
        $this->assertNull($paymentInvoice['ref_num']);

        // capture payment
        $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

        // validate payment captured and txn on_hold
        $paymentEntity = $this->getDbLastPayment();
        $transactionEntity = $paymentEntity->transaction;

        $this->assertEquals('captured', $paymentEntity['status']);
        $this->assertTrue($paymentEntity['captured']);

        $this->assertEquals($paymentEntity['amount'], $transactionEntity['amount']);
        $this->assertFalse($transactionEntity['on_hold']);

        // upload invoice flow
        Queue::fake();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId , $merchantUser['id']);

        $request = [
            'url'       => '/payment/' . $paymentEntity['id'] . '/update_merchant_doc',
            'method'    => 'patch',
            'content'   => [
                'document_id'   => "doc_1234567890",
                'document_type' => "jpmc_invoice"
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['document_updated']);

        $paymentInvoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($request['content']['document_id'], $paymentInvoice['ref_num']);
    }

    public function testJPMCImportFlowPaymentCardMethodSuccess()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        // create order
        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        // create payment
        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertArrayHasKey('razorpay_order_id', $response);
        $this->assertArrayHasKey('razorpay_signature', $response);
        $this->assertEquals($order->getPublicId(), $response['razorpay_order_id']);

        // validate payment authorized and details saved
        $paymentEntity = $this->getDbLastPayment();

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($order->getId(), $paymentEntity['order_id']);
        $this->assertEquals($payment['notes']['invoice_number'], $paymentEntity['notes']['invoice_number']);
        $this->assertEquals($payment['notes']['goods_description'], $paymentEntity['notes']['goods_description']);

        // validate payment invoice updated
        $paymentInvoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($paymentEntity['id'], $paymentInvoice['entity_id']);
        $this->assertEquals('jpmc_invoice', $paymentInvoice['type']);
        $this->assertEquals($paymentEntity['notes']['invoice_number'], $paymentInvoice['receipt']);
        $this->assertNull($paymentInvoice['ref_num']);

        // capture payment
        $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

        // validate payment captured and txn on_hold
        $paymentEntity = $this->getDbLastPayment();
        $transactionEntity = $paymentEntity->transaction;

        $this->assertEquals('captured', $paymentEntity['status']);
        $this->assertTrue($paymentEntity['captured']);

        $this->assertEquals($paymentEntity['amount'], $transactionEntity['amount']);
        $this->assertFalse($transactionEntity['on_hold']);

        // upload invoice flow
        Queue::fake();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId , $merchantUser['id']);

        $request = [
            'url'       => '/payment/' . $paymentEntity['id'] . '/update_merchant_doc',
            'method'    => 'patch',
            'content'   => [
                'document_id'   => "doc_1234567890",
                'document_type' => "jpmc_invoice"
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['document_updated']);

        $paymentInvoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($request['content']['document_id'], $paymentInvoice['ref_num']);

//        Queue::assertPushed(CrossBorderCommonUseCases::class, 1);
    }

    public function testJPMCImportFlowPaymentWithInvalidIntlCardMethod()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'USD',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4012010000000007';

        $payment['amount'] = '1000';

        $payment['currency'] = 'USD';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Your payment could not be completed as this business accepts domestic (Indian) card payments only. Try another payment method.');

        },
            \RZP\Exception\BadRequestException::class,
            'Your payment could not be completed as this business accepts domestic (Indian) card payments only. Try another payment method.');
    }

    public function testJPMCImportFlowPaymentInvalidMerchantIntlEnabled()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => true,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'USD',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['number'] = '4012010000000007';

        $payment['amount'] = '1000';

        $payment['currency'] = 'USD';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment method request not allowed as international is enabled on the merchant.');

        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Payment method request not allowed as international is enabled on the merchant.');
    }

    public function testJPMCImportFlowPaymentNBMethodSuccess()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);
        $this->fixtures->merchant->enableMethod($merchantId, 'upi');

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        // create order
        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        // create payment
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $response = $this->doS2SPrivateAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertArrayHasKey('razorpay_order_id', $response);
        $this->assertArrayHasKey('razorpay_signature', $response);
        $this->assertEquals($order->getPublicId(), $response['razorpay_order_id']);

        // validate payment authorized and details saved
        $paymentEntity = $this->getDbLastPayment();

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($order->getId(), $paymentEntity['order_id']);
        $this->assertEquals($payment['notes']['invoice_number'], $paymentEntity['notes']['invoice_number']);
        $this->assertEquals($payment['notes']['goods_description'], $paymentEntity['notes']['goods_description']);

        // validate payment invoice updated
        $paymentInvoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($paymentEntity['id'], $paymentInvoice['entity_id']);
        $this->assertEquals('jpmc_invoice', $paymentInvoice['type']);
        $this->assertEquals($paymentEntity['notes']['invoice_number'], $paymentInvoice['receipt']);
        $this->assertNull($paymentInvoice['ref_num']);

        // capture payment
        $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

        // validate payment captured and txn on_hold
        $paymentEntity = $this->getDbLastPayment();
        $transactionEntity = $paymentEntity->transaction;

        $this->assertEquals('captured', $paymentEntity['status']);
        $this->assertTrue($paymentEntity['captured']);

        $this->assertEquals($paymentEntity['amount'], $transactionEntity['amount']);
        $this->assertFalse($transactionEntity['on_hold']);

        // upload invoice flow
        Queue::fake();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId , $merchantUser['id']);

        $request = [
            'url'       => '/payment/' . $paymentEntity['id'] . '/update_merchant_doc',
            'method'    => 'patch',
            'content'   => [
                'document_id'   => "doc_1234567890",
                'document_type' => "jpmc_invoice"
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['document_updated']);

        $paymentInvoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($request['content']['document_id'], $paymentInvoice['ref_num']);

//        Queue::assertPushed(CrossBorderCommonUseCases::class, 1);
    }

    public function testJPMCImportFlowPaymentUPIMethodSuccess()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);
        $this->fixtures->merchant->enableMethod($merchantId, 'upi');

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        // create order
        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        // create payment
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $response = $this->doS2SPrivateAuthJsonPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        // validate payment authorized and details saved
        $paymentEntity = $this->getDbLastPayment();

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($order->getId(), $paymentEntity['order_id']);
        $this->assertEquals($payment['notes']['invoice_number'], $paymentEntity['notes']['invoice_number']);
        $this->assertEquals($payment['notes']['goods_description'], $paymentEntity['notes']['goods_description']);

        // validate payment invoice updated
        $paymentInvoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($paymentEntity['id'], $paymentInvoice['entity_id']);
        $this->assertEquals('jpmc_invoice', $paymentInvoice['type']);
        $this->assertEquals($paymentEntity['notes']['invoice_number'], $paymentInvoice['receipt']);
        $this->assertNull($paymentInvoice['ref_num']);

        // capture payment
        $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

        // validate payment captured and txn on_hold
        $paymentEntity = $this->getDbLastPayment();
        $transactionEntity = $paymentEntity->transaction;

        $this->assertEquals('captured', $paymentEntity['status']);
        $this->assertTrue($paymentEntity['captured']);

        $this->assertEquals($paymentEntity['amount'], $transactionEntity['amount']);
        $this->assertFalse($transactionEntity['on_hold']);

        // upload invoice flow
        Queue::fake();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId , $merchantUser['id']);

        $request = [
            'url'       => '/payment/' . $paymentEntity['id'] . '/update_merchant_doc',
            'method'    => 'patch',
            'content'   => [
                'document_id'   => "doc_1234567890",
                'document_type' => "jpmc_invoice"
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['document_updated']);

        $paymentInvoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($request['content']['document_id'], $paymentInvoice['ref_num']);

//        Queue::assertPushed(CrossBorderCommonUseCases::class, 1);
    }

    /*public function testJPMCImportFlowPaymentWithNoOrderShippingAddress()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
                'customer_id' => '100000customer',
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000';

        $payment['order_id'] = $order->getPublicId();

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            $error = $response['error'];

            $this->assertEquals($error['code'], 'BAD_REQUEST_ERROR');
            $this->assertEquals($error['description'], 'Payment order does not have a customer shipping address.');

        },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'Payment order does not have a customer shipping address.');
    }*/

    protected function getOrderMetaValue()
    {
        $app = App::getFacadeRoot();
        $shipping_address = [
            'line1'         => 'line_one',
            'line2'         => 'line_two',
            'city'          => 'Bangalore',
            'state'         => 'Karnataka',
            'zipcode'       => '560001',
            'country'       => 'IND',
            'type'          => 'shipping_address',
            'primary'       => true
        ];

        $customer = [
            'name'              => 'Test Customer',
            'method'            => 'sameday',
            'gift_wrap'         => false,
            'shipping_address'  => $shipping_address,
        ];

        return [
            'campaign'          => null,
            'customer'          => null,
            'refund_allowed'    => null,
            'line_items'        => null,
            'line_items_total'  => null,
            'customer_details'  => $customer,
        ];
    }

    public function testJPMCImportFlowPaymentWithCustomCheckout()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        // create order
        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        // create payment
        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'razorpayjs';

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['customer_id'] = 'cust_100000customer';

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertArrayHasKey('razorpay_order_id', $response);
        $this->assertArrayHasKey('razorpay_signature', $response);
        $this->assertEquals($order->getPublicId(), $response['razorpay_order_id']);

        // validate payment authorized and details saved
        $paymentEntity = $this->getDbLastPayment();

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($order->getId(), $paymentEntity['order_id']);
        $this->assertEquals($payment['notes']['invoice_number'], $paymentEntity['notes']['invoice_number']);
        $this->assertEquals($payment['notes']['goods_description'], $paymentEntity['notes']['goods_description']);

        // validate payment invoice updated
        $paymentInvoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($paymentEntity['id'], $paymentInvoice['entity_id']);
        $this->assertEquals('jpmc_invoice', $paymentInvoice['type']);
        $this->assertEquals($paymentEntity['notes']['invoice_number'], $paymentInvoice['receipt']);
        $this->assertEquals('issued', $paymentInvoice['status']);
        $this->assertNull($paymentInvoice['ref_num']);

        // capture payment
        $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

        // validate payment captured and txn on_hold
        $paymentEntity = $this->getDbLastPayment();
        $transactionEntity = $paymentEntity->transaction;

        $this->assertEquals('captured', $paymentEntity['status']);
        $this->assertTrue($paymentEntity['captured']);

        $this->assertEquals($paymentEntity['amount'], $transactionEntity['amount']);
        $this->assertFalse($transactionEntity['on_hold']);

        // upload invoice flow
        Queue::fake();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId , $merchantUser['id']);

        $request = [
            'url'       => '/payment/' . $paymentEntity['id'] . '/update_merchant_doc',
            'method'    => 'patch',
            'content'   => [
                'document_id'   => "doc_1234567890",
                'document_type' => "jpmc_invoice"
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['document_updated']);

        $paymentInvoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($request['content']['document_id'], $paymentInvoice['ref_num']);
        $this->assertEquals('paid', $paymentInvoice['status']);

//        Queue::assertPushed(CrossBorderCommonUseCases::class, 1);
    }

    public function testJPMCImportFlowPaymentWithStandardCheckout()
    {
        $merchantId = "10000000000000";

        $merchantAttribute = [
            MERCHANT::MAX_PAYMENT_AMOUNT => 3000000,
            'purpose_code' => 'S0802',
            'convert_currency' => true,
            'international' => false,
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttribute);
        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json', 'enable_jpmc_import_flow']);

        $merchantDetailAttribute = [
            DetailEntity::MERCHANT_ID => $merchantId,
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailAttribute);

        $this->fixtures->create('merchant_international_integrations', [
            InternationalIntegration\Entity::MERCHANT_ID => $merchantId,
            InternationalIntegration\Entity::INTEGRATION_ENTITY => 'jpmc_import_flow',
            InternationalIntegration\Entity::INTEGRATION_KEY => 'jpmc_import_flow',
            InternationalIntegration\Entity::NOTES => [
                'hs_code' => '85238020'
            ],
        ]);

        // create order
        $order = $this->fixtures->create('order',
            [
                'amount' => 1000,
                'currency' => 'INR',
            ]);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => self::getOrderMetaValue(),
                'type'     => 'cart_info',
            ]);

        // create payment
        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'checkoutjs';

        $payment['amount'] = '1000';

        $payment['currency'] = 'INR';

        $payment['order_id'] = $order->getPublicId();

        $payment['customer_id'] = 'cust_100000customer';

        $payment['notes'] = [
            'invoice_number' => '1234567890qwertyuiop',
            'goods_description' => 'sample description for goods or services',
        ];

        $response = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertArrayHasKey('razorpay_order_id', $response);
        $this->assertArrayHasKey('razorpay_signature', $response);
        $this->assertEquals($order->getPublicId(), $response['razorpay_order_id']);

        // validate payment authorized and details saved
        $paymentEntity = $this->getDbLastPayment();

        $this->assertEquals('authorized', $paymentEntity['status']);
        $this->assertEquals($order->getId(), $paymentEntity['order_id']);
        $this->assertEquals($payment['notes']['invoice_number'], $paymentEntity['notes']['invoice_number']);
        $this->assertEquals($payment['notes']['goods_description'], $paymentEntity['notes']['goods_description']);

        // validate payment invoice updated
        $paymentInvoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($paymentEntity['id'], $paymentInvoice['entity_id']);
        $this->assertEquals('jpmc_invoice', $paymentInvoice['type']);
        $this->assertEquals($paymentEntity['notes']['invoice_number'], $paymentInvoice['receipt']);
        $this->assertEquals('issued', $paymentInvoice['status']);
        $this->assertNull($paymentInvoice['ref_num']);

        // capture payment
        $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

        // validate payment captured and txn on_hold
        $paymentEntity = $this->getDbLastPayment();
        $transactionEntity = $paymentEntity->transaction;

        $this->assertEquals('captured', $paymentEntity['status']);
        $this->assertTrue($paymentEntity['captured']);

        $this->assertEquals($paymentEntity['amount'], $transactionEntity['amount']);
        $this->assertFalse($transactionEntity['on_hold']);

        // upload invoice flow
        Queue::fake();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId , $merchantUser['id']);

        $request = [
            'url'       => '/payment/' . $paymentEntity['id'] . '/update_merchant_doc',
            'method'    => 'patch',
            'content'   => [
                'document_id'   => "doc_1234567890",
                'document_type' => "jpmc_invoice"
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['document_updated']);

        $paymentInvoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($request['content']['document_id'], $paymentInvoice['ref_num']);
        $this->assertEquals('paid', $paymentInvoice['status']);

//        Queue::assertPushed(CrossBorderCommonUseCases::class, 1);
    }

    public function testCapturePaymentWithAmexInternationalandDomesticCardWithInternationalPricingWithPlatformFeeBearer()
    {
        $plans = [
            [
                'plan_id' => '1ycviEdCgurrFI',
                'plan_name' => 'testFixturePlan',
                'feature' => 'payment',
                'payment_method' => 'card',
                'percent_rate' => 200,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'international' => 0,
                'fee_bearer'    => 'platform',
            ],
            [
                'plan_id' => '1ycviEdCgurrFI',
                'plan_name' => 'testFixturePlanInternational',
                'feature' => 'payment',
                'payment_method' => 'card',
                'percent_rate' => 300,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'international' => 1,
                'fee_bearer'     => 'platform',
            ],


        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => 'platform',
            'pricing_plan_id'   => '1ycviEdCgurrFI',
        ];

        $this->fixtures->merchant->addFeatures(['s2s','enable_intl_pricing_amex']);
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'US',
            'network' => 'American Express',
        ]);
        $payment = $this->getDefaultPaymentArray();
        $payment['card'] = array(
            'number'            => '5567630000002004',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '5666',
        );
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $this->doAuthAndCapturePayment($payment);
        $internationalTransaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($payment['amount'] * 0.03, $internationalTransaction['fee']);
        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => '',
            'network' => 'American Express',
            'flows'   => [
                '3ds'  => '1',
                'headless_otp'  => '1',
            ]
        ]);
        $domesticPayment = $this->getDefaultPaymentArray();
        $domesticPayment['card'] = array(
            'number'            => '4143667057540458',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '5666',
        );
        $domesticPayment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $this->doAuthAndCapturePayment($domesticPayment);
        $domesticTransaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($domesticPayment['amount'] * 0.02, $domesticTransaction['fee']);
        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'IN',
            'network' => 'Visa',
        ]);
        $domesticMCPayment = $this->getDefaultPaymentArray();
        $domesticMCPayment['card'] = array(
            'number'            => '4044649165235890',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '566',
        );
        $this->doAuthAndCapturePayment($domesticMCPayment);
        $domesticMCTransaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($domesticMCPayment['amount'] * 0.02, $domesticMCTransaction['fee']);
    }

    public function testCapturePaymentWithAmexInternationalAndDomesticCardWithDomesticPricingWithPlatformFeeBearer()
    {
        $plans = [
            [
                'plan_id' => '1ycviEdCgurrFI',
                'plan_name' => 'testFixturePlan',
                'feature' => 'payment',
                'payment_method' => 'card',
                'percent_rate' => 200,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'international' => 0,
                'fee_bearer'    => 'platform',
            ],
            [
                'plan_id' => '1ycviEdCgurrFI',
                'plan_name' => 'testFixturePlanInternational',
                'feature' => 'payment',
                'payment_method' => 'card',
                'percent_rate' => 300,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'international' => 1,
                'fee_bearer'     => 'platform',
            ],


        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => 'platform',
            'pricing_plan_id'   => '1ycviEdCgurrFI',
        ];
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'US',
            'network' => 'American Express',
        ]);
        $payment = $this->getDefaultPaymentArray();
        $payment['card'] = array(
            'number'            => '5567630000002004',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '5666',
        );
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $this->doAuthAndCapturePayment($payment);
        $internationalTransaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($payment['amount'] * 0.02, $internationalTransaction['fee']);
        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'American Express',
            'flows'   => [
                '3ds'  => '1',
                'headless_otp'  => '1',
            ]
        ]);
        $domesticPayment = $this->getDefaultPaymentArray();
        $domesticPayment['card'] = array(
            'number'            => '4143667057540458',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '5666',
        );
        $domesticPayment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $this->doAuthAndCapturePayment($domesticPayment);
        $domesticTransaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($domesticPayment['amount'] * 0.02, $domesticTransaction['fee']);
        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'IN',
            'network' => 'Visa',
        ]);
        $domesticMCPayment = $this->getDefaultPaymentArray();
        $domesticMCPayment['card'] = array(
            'number'            => '4044649165235890',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '566',
        );
        $this->doAuthAndCapturePayment($domesticMCPayment);
        $domesticMCTransaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($domesticMCPayment['amount'] * 0.02, $domesticMCTransaction['fee']);
    }

    public function testCapturePaymentWithAmexInternationalCardWithInternationalPricingWithCustomerFeeBearer()
    {
        $plans = [
            [
                'plan_id' => '1ycviEdCgurrFI',
                'plan_name' => 'testFixturePlanInternational',
                'feature' => 'payment',
                'payment_method' => 'card',
                'percent_rate' => 300,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'international' => 1,
                'fee_bearer'     => FeeBearer::CUSTOMER,
            ],
        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => FeeBearer::CUSTOMER,
            'pricing_plan_id'   => '1ycviEdCgurrFI',
        ];

        $this->fixtures->merchant->addFeatures(['enable_intl_pricing_amex']);
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->enableConvenienceFeeModel();
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([Constants::ALLOW_CFB_INTERNATIONAL]);
        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);
        $amount = 600;
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'US',
            'network' => 'American Express',
        ]);

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequest = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '556763'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponse = $this->makeRequestAndGetContent($flowsRequest);
        $cardCurrency = $flowsResponse['card_currency'];
        $currencyRequestId = $flowsResponse['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '5567630000002004', 'cvv' => 5666, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 27],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $payment = $this->getDefaultPaymentArray();
        $payment['fee'] = $amount * 0.03;
        $payment['amount'] = $amount + $payment['fee'];
        $payment['card'] = array(
            'number'            => '5567630000002004',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '5666',
        );
        $this->doAuthAndCapturePayment($payment, $amount);
        $internationalTransaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($amount * 0.03, $internationalTransaction['fee']);
        $this->assertEquals($amount * 0.03, $calculateFeesResponse['input']['fee']);
    }
    public function testCapturePaymentWithAmexDomesticCardWithDomesticPricingWithCustomerFeeBearer()
    {
        $plans = [
            [
                'plan_id' => '1ycviEdCgurrFI',
                'plan_name' => 'testFixturePlanDomestic',
                'feature' => 'payment',
                'payment_method' => 'card',
                'percent_rate' => 200,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'international' => 0,
                'fee_bearer'     => FeeBearer::CUSTOMER,
            ],
            [
                'plan_id' => '1ycviEdCgurrFI',
                'plan_name' => 'testFixturePlanInternational',
                'feature' => 'payment',
                'payment_method' => 'card',
                'percent_rate' => 300,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'international' => 1,
                'fee_bearer'     => FeeBearer::CUSTOMER,
            ],
        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => FeeBearer::CUSTOMER,
            'pricing_plan_id'   => '1ycviEdCgurrFI',
        ];

        $this->fixtures->merchant->addFeatures(['enable_intl_pricing_amex']);
        $this->fixtures->merchant->enableConvenienceFeeModel();
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([Constants::ALLOW_CFB_INTERNATIONAL]);
        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);
        $amount = 600;
        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'network' => 'American Express',
        ]);

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequest = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '556763'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponse = $this->makeRequestAndGetContent($flowsRequest);
        $cardCurrency = $flowsResponse['card_currency'];
        $currencyRequestId = $flowsResponse['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4143667057540458', 'cvv' => 5666, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 27],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $payment = $this->getDefaultPaymentArray();
        $payment['fee'] = $amount * 0.02;
        $payment['amount'] = $amount + $payment['fee'];
        $payment['card'] = array(
            'number'            => '4143667057540458',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '5666',
        );
        $this->doAuthAndCapturePayment($payment, $amount);
        $internationalTransaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($amount * 0.02, $internationalTransaction['fee']);
        $this->assertEquals($amount * 0.02, $calculateFeesResponse['input']['fee']);
    }

    public function testCapturePaymentWithAmexInternationalCardWithDisabledInternationalPricingWithCustomerFeeBearer()
    {
        $plans = [
            [
                'plan_id' => '1ycviEdCgurrFI',
                'plan_name' => 'testFixturePlan',
                'feature' => 'payment',
                'payment_method' => 'card',
                'percent_rate' => 200,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'international' => 0,
                'fee_bearer'    => FeeBearer::CUSTOMER,
            ],
            [
                'plan_id' => '1ycviEdCgurrFI',
                'plan_name' => 'testFixturePlanInternational',
                'feature' => 'payment',
                'payment_method' => 'card',
                'percent_rate' => 300,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'international' => 1,
                'fee_bearer'     => FeeBearer::CUSTOMER,
            ],
        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => FeeBearer::CUSTOMER,
            'pricing_plan_id'   => '1ycviEdCgurrFI',
        ];
        $this->fixtures->merchant->enableConvenienceFeeModel();
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([Constants::ALLOW_CFB_INTERNATIONAL]);
        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);
        $amount = 60000;
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'US',
            'network' => 'American Express',
        ]);

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequest = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '556763'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponse = $this->makeRequestAndGetContent($flowsRequest);
        $cardCurrency = $flowsResponse['card_currency'];
        $currencyRequestId = $flowsResponse['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '5567630000002004', 'cvv' => 5666, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 27],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $payment = $this->getDefaultPaymentArray();
        $payment['fee'] = $amount * 0.02;
        $payment['amount'] = $amount + $payment['fee'];
        $payment['card'] = array(
            'number'            => '5567630000002004',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '5666',
        );
        $this->doAuthAndCapturePayment($payment, $amount);
        $internationalTransaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($amount * 0.02, $internationalTransaction['fee']);
        $this->assertEquals($amount * 0.02, $calculateFeesResponse['input']['fee']);
    }

    public function testCapturePaymentWithAmexDomesticCardWithDisabledInternationalPricingWithCustomerFeeBearer()
    {
        $plans = [
            [
                'plan_id' => '1ycviEdCgurrFI',
                'plan_name' => 'testFixturePlan',
                'feature' => 'payment',
                'payment_method' => 'card',
                'percent_rate' => 200,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'international' => 0,
                'fee_bearer'    => FeeBearer::CUSTOMER,
            ],
            [
                'plan_id' => '1ycviEdCgurrFI',
                'plan_name' => 'testFixturePlanInternational',
                'feature' => 'payment',
                'payment_method' => 'card',
                'percent_rate' => 300,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'international' => 1,
                'fee_bearer'     => FeeBearer::CUSTOMER,
            ],
        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => FeeBearer::CUSTOMER,
            'pricing_plan_id'   => '1ycviEdCgurrFI',
        ];
        $this->fixtures->merchant->enableConvenienceFeeModel();
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([Constants::ALLOW_CFB_INTERNATIONAL]);
        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);
        $amount = 60000;

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => '',
            'network' => 'American Express',
            'flows'   => [
                '3ds'  => '1',
                'headless_otp'  => '1',
            ]
        ]);
        $flowsRequest = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '414366'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponse = $this->makeRequestAndGetContent($flowsRequest);
        $cardCurrency = $flowsResponse['card_currency'];
        $currencyRequestId = $flowsResponse['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4143667057540458', 'cvv' => 5666, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 27],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $payment = $this->getDefaultPaymentArray();
        $payment['fee'] = $amount * 0.02;
        $payment['amount'] = $amount + $payment['fee'];
        $payment['card'] = array(
            'number'            => '4143667057540458',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '5666',
        );
        $this->doAuthAndCapturePayment($payment, $amount);
        $internationalTransaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($amount * 0.02, $internationalTransaction['fee']);
        $this->assertEquals($amount * 0.02, $calculateFeesResponse['input']['fee']);
    }

    public function testCapturePaymentWithVisaInternationalCardWithInternationalPricingWithCustomerFeeBearer()
    {
        $plans = [
            [
                'plan_id' => '1ycviEdCgurrFI',
                'plan_name' => 'testFixturePlan',
                'feature' => 'payment',
                'payment_method' => 'card',
                'percent_rate' => 200,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'international' => 0,
                'fee_bearer'    => FeeBearer::CUSTOMER,
            ],
            [
                'plan_id' => '1ycviEdCgurrFI',
                'plan_name' => 'testFixturePlanInternational',
                'feature' => 'payment',
                'payment_method' => 'card',
                'percent_rate' => 300,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'international' => 1,
                'fee_bearer'     => FeeBearer::CUSTOMER,
            ],
        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => FeeBearer::CUSTOMER,
            'pricing_plan_id'   => '1ycviEdCgurrFI',
        ];

        $this->fixtures->merchant->enableConvenienceFeeModel();
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([Constants::ALLOW_CFB_INTERNATIONAL]);
        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);
        $amount = 600;
        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'US',
            'network' => 'VISA',
        ]);

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequest = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '414366'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponse = $this->makeRequestAndGetContent($flowsRequest);
        $cardCurrency = $flowsResponse['card_currency'];
        $currencyRequestId = $flowsResponse['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4143667057540458', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 27],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $payment = $this->getDefaultPaymentArray();
        $payment['fee'] = $amount * 0.03;
        $payment['amount'] = $amount + $payment['fee'];
        $payment['card'] = array(
            'number'            => '4143667057540458',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '566',
        );
        $this->doAuthAndCapturePayment($payment, $amount);
        $internationalTransaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($amount * 0.03, $internationalTransaction['fee']);
        $this->assertEquals($amount * 0.03, $calculateFeesResponse['input']['fee']);
    }
    public function testCapturePaymentWithNetworkPricing()
    {
        $plans = [
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'MC',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'platform',
                'percent_rate' => 200,
                'org_id'    => '100000razorpay',
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'VISA',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'platform',
                'percent_rate' => 400,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'AMEX',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'platform',
                'percent_rate' => 600,
                'org_id'    => '100000razorpay',
            ],
        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => 'platform',
            'pricing_plan_id'   => '1ycviEdCgurrHI',
        ];
        $this->fixtures->merchant->addFeatures(['s2s','enable_intl_pricing_amex']);
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'US',
            'network' => 'American Express',
        ]);
        $payment = $this->getDefaultPaymentArray();
        $payment['card'] = array(
            'number'            => '5567630000002004',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2050',
            'cvv'               => '5666',
        );
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $this->doAuthAndCapturePayment($payment);
        $internationalTransaction = $this->getLastEntity('transaction', true);
        $this->assertEquals($payment['amount'] * 0.06, $internationalTransaction['fee']);

        // test visa international card
        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'US',
            'network' => 'Visa',
        ]);
        $payment['card'] = array(
            'number'            => '4143667057540458',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '566',
        );
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $response = $this->doS2SPrivateAuthPayment($payment);
        $paymentEntity = $this->getDbLastPayment();
        // capture payment
        $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

        // validate payment captured fee
        $paymentEntity = $this->getDbLastPayment();
        $transactionEntity = $paymentEntity->transaction;
        $this->assertEquals($payment['amount'] * 0.04, $transactionEntity['fee']);

        // MasterCard Pricing test
        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'network' => 'MasterCard',
        ]);
        $payment['card'] = array(
            'number'            => '4044649165235890',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '566',
        );
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $response = $this->doS2SPrivateAuthPayment($payment);
        $paymentEntity = $this->getDbLastPayment();
        // capture payment
        $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

        // validate payment captured fee
        $paymentEntity = $this->getDbLastPayment();
        $transactionEntity = $paymentEntity->transaction;
        $this->assertEquals($payment['amount'] * 0.02, $transactionEntity['fee']);
    }

    public function testCapturePaymentWithNetworkAndMethodTypePricing()
    {
        $plans = [
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'MC',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'platform',
                'percent_rate' => 200,
                'org_id'    => '100000razorpay',
                "payment_method_type" => "credit",
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'VISA',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'platform',
                'percent_rate' => 400,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                "payment_method_type" => "credit",
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'AMEX',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'platform',
                'percent_rate' => 600,
                'org_id'    => '100000razorpay',
                "payment_method_type" => "debit",
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'AMEX',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'platform',
                'percent_rate' => 800,
                'org_id'    => '100000razorpay',
            ],
        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => 'platform',
            'pricing_plan_id'   => '1ycviEdCgurrHI',
        ];
        $this->fixtures->merchant->addFeatures(['s2s','enable_intl_pricing_amex']);
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'US',
            'network' => 'American Express',
            'type'    => 'credit'
        ]);
        $payment = $this->getDefaultPaymentArray();
        $payment['card'] = array(
            'number'            => '5567630000002004',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2050',
            'cvv'               => '5666',
        );
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $this->doAuthAndCapturePayment($payment);
        $internationalTransaction = $this->getLastEntity('transaction', true);

        // pricing for amex debit method type  and any method type is added and credit card used,
        // so it should pick any pricing
        $this->assertEquals($payment['amount'] * 0.08, $internationalTransaction['fee']);

        // test visa international card
        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'US',
            'network' => 'Visa',
            'type'    => 'credit'
        ]);
        $payment['card'] = array(
            'number'            => '4143667057540458',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '566',
        );
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $response = $this->doS2SPrivateAuthPayment($payment);
        $paymentEntity = $this->getDbLastPayment();
        // capture payment
        $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

        // validate payment captured fee
        $paymentEntity = $this->getDbLastPayment();
        $transactionEntity = $paymentEntity->transaction;
        // pricing for visa credit method type is added and credit card used,
        // so it should pick credit pricing
        $this->assertEquals($payment['amount'] * 0.04, $transactionEntity['fee']);

        // MasterCard Pricing test
        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'network' => 'MasterCard',
        ]);
        $payment['card'] = array(
            'number'            => '4044649165235890',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '566',
        );
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $response = $this->doS2SPrivateAuthPayment($payment);
        $paymentEntity = $this->getDbLastPayment();
        // capture payment
        $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

        // validate payment captured fee
        $paymentEntity = $this->getDbLastPayment();
        $transactionEntity = $paymentEntity->transaction;
        // pricing for mc credit method type is added and credit card used,
        // so it should pick credit pricing
        $this->assertEquals($payment['amount'] * 0.02, $transactionEntity['fee']);
    }

    public function testCapturePaymentWithNetworkAndMethodTypeAndIssuerPricing()
    {
        $plans = [
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'MC',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'platform',
                'percent_rate' => 200,
                'org_id'    => '100000razorpay',
                "payment_issuer" => "SBIN",
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'MC',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'platform',
                'percent_rate' => 300,
                'org_id'    => '100000razorpay',
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'VISA',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'platform',
                'percent_rate' => 400,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                "payment_method_type" => "credit",
                "payment_issuer" => "ICIC",
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'AMEX',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'platform',
                'percent_rate' => 600,
                'org_id'    => '100000razorpay',
                "payment_method_type" => "credit",
                "payment_issuer" => "SBIN",
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'AMEX',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'platform',
                'percent_rate' => 800,
                'org_id'    => '100000razorpay',
            ],
        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => 'platform',
            'pricing_plan_id'   => '1ycviEdCgurrHI',
        ];
        $this->fixtures->merchant->addFeatures(['s2s','enable_intl_pricing_amex']);
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);
        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'US',
            'network' => 'American Express',
            'type'    => 'credit',
            'issuer'  => 'SBIN'
        ]);
        $payment = $this->getDefaultPaymentArray();
        $payment['card'] = array(
            'number'            => '5567630000002004',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2050',
            'cvv'               => '5666',
        );
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $this->doAuthAndCapturePayment($payment);
        $internationalTransaction = $this->getLastEntity('transaction', true);

        // pricing for visa credit method type issuer ICIC  is added and ICICcredit card used,
        // so it should pick ICIC pricing
        $this->assertEquals($payment['amount'] * 0.06, $internationalTransaction['fee']);

        // test visa ICIC international card
        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'US',
            'network' => 'Visa',
            'type'    => 'credit',
            'issuer'  => 'ICIC'
        ]);
        $payment['card'] = array(
            'number'            => '4143667057540458',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '566',
        );
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $response = $this->doS2SPrivateAuthPayment($payment);
        $paymentEntity = $this->getDbLastPayment();
        // capture payment
        $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

        // validate payment captured fee
        $paymentEntity = $this->getDbLastPayment();
        $transactionEntity = $paymentEntity->transaction;
        // pricing for visa credit method type is added and credit card used,
        // so it should pick credit pricing
        $this->assertEquals($payment['amount'] * 0.04, $transactionEntity['fee']);

        // test ICIC MasterCard Pricing
        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'network' => 'MasterCard',
            'issuer'  => 'ICIC'
        ]);
        $payment['card'] = array(
            'number'            => '4044649165235890',
            'name'              => 'Test',
            'expiry_month'      => '12',
            'expiry_year'       => '2027',
            'cvv'               => '566',
        );
        $payment['callback_url'] = $this->getLocalMerchantCallbackUrl();
        $response = $this->doS2SPrivateAuthPayment($payment);
        $paymentEntity = $this->getDbLastPayment();
        // capture payment
        $this->capturePayment($response['razorpay_payment_id'], $paymentEntity['amount'], $paymentEntity['currency'], $paymentEntity['amount']);

        // validate payment captured fee
        $paymentEntity = $this->getDbLastPayment();
        $transactionEntity = $paymentEntity->transaction;
        // pricing for mc method type any and issuer SBIN is added and ICIC credit card is used,
        // so it should pick any pricing
        $this->assertEquals($payment['amount'] * 0.03, $transactionEntity['fee']);
    }

    public function testCalculateFeeWithNetworkPricingWithCustomerFeeBearer()
    {
        $plans = [
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'MC',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'customer',
                'percent_rate' => 200,
                'org_id'    => '100000razorpay',
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'VISA',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'customer',
                'percent_rate' => 400,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'AMEX',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'customer',
                'percent_rate' => 600,
                'org_id'    => '100000razorpay',
            ],
        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => FeeBearer::CUSTOMER,
            'pricing_plan_id'   => '1ycviEdCgurrHI',
        ];

        $this->fixtures->merchant->enableConvenienceFeeModel();
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([Constants::ALLOW_CFB_INTERNATIONAL, 'enable_intl_pricing_amex']);
        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);
        $amount = 600;
        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'US',
            'network' => 'Visa',
        ]);

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequest = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '414366'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponse = $this->makeRequestAndGetContent($flowsRequest);
        $cardCurrency = $flowsResponse['card_currency'];
        $currencyRequestId = $flowsResponse['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4143667057540458', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 27],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $this->assertEquals($amount * 0.04, $calculateFeesResponse['input']['fee']);

        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'network' => 'MasterCard',
        ]);

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequestMC = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '404464'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponseMC = $this->makeRequestAndGetContent($flowsRequestMC);
        $cardCurrency = $flowsResponseMC['card_currency'];
        $currencyRequestId = $flowsResponseMC['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4044649165235890', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 40],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $this->assertEquals($amount * 0.02, $calculateFeesResponse['input']['fee']);

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'US',
            'network' => 'American Express',
        ]);

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequestAmex = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '556763'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponseAmex = $this->makeRequestAndGetContent($flowsRequestAmex);
        $cardCurrency = $flowsResponseAmex['card_currency'];
        $currencyRequestId = $flowsResponseAmex['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '5567630000002004', 'cvv' => 5666, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 40],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $this->assertEquals($amount * 0.06, $calculateFeesResponse['input']['fee']);
    }

    public function testCalculateFeeWithNetworkAndMethodTypePricingWithCustomerFeeBearer()
    {
        $plans = [
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'MC',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'customer',
                'percent_rate' => 200,
                'org_id'    => '100000razorpay',
                'payment_method_type' => 'credit',
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'VISA',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'customer',
                'percent_rate' => 400,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'payment_method_type' => 'credit',
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'AMEX',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'customer',
                'percent_rate' => 600,
                'org_id'    => '100000razorpay',
                'payment_method_type' => 'debit',
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'AMEX',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'customer',
                'percent_rate' => 800,
                'org_id'    => '100000razorpay',
                'payment_method_type' => 'credit',
            ],
        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => FeeBearer::CUSTOMER,
            'pricing_plan_id'   => '1ycviEdCgurrHI',
        ];

        $this->fixtures->merchant->enableConvenienceFeeModel();
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([Constants::ALLOW_CFB_INTERNATIONAL, 'enable_intl_pricing_amex']);
        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);
        $amount = 600;
        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'US',
            'network' => 'Visa',
            'type'    => 'credit'
        ]);

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequest = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '414366'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponse = $this->makeRequestAndGetContent($flowsRequest);
        $cardCurrency = $flowsResponse['card_currency'];
        $currencyRequestId = $flowsResponse['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4143667057540458', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 27],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $this->assertEquals($amount * 0.04, $calculateFeesResponse['input']['fee']);

        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'network' => 'MasterCard',
            'type'    => 'credit'
        ]);

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequestMC = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '404464'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponseMC = $this->makeRequestAndGetContent($flowsRequestMC);
        $cardCurrency = $flowsResponseMC['card_currency'];
        $currencyRequestId = $flowsResponseMC['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4044649165235890', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 40],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $this->assertEquals($amount * 0.02, $calculateFeesResponse['input']['fee']);

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'US',
            'network' => 'American Express',
            'type'    => 'credit'
        ]);

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequestAmex = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '556763'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponseAmex = $this->makeRequestAndGetContent($flowsRequestAmex);
        $cardCurrency = $flowsResponseAmex['card_currency'];
        $currencyRequestId = $flowsResponseAmex['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '5567630000002004', 'cvv' => 5666, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 40],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $this->assertEquals($amount * 0.08, $calculateFeesResponse['input']['fee']);
    }

    public function testCalculateFeeWithNetworkAndMethodTypeAndIssuerPricingWithCustomerFeeBearer()
    {
        $plans = [
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'MC',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'customer',
                'percent_rate' => 200,
                'org_id'    => '100000razorpay',
                'payment_method_type' => 'credit',
                'payment_issuer' => 'SBIN'
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'MC',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'customer',
                'percent_rate' => 1000,
                'org_id'    => '100000razorpay',
                'payment_method_type' => 'credit',
                'payment_issuer' => 'ICIC'
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'VISA',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'customer',
                'percent_rate' => 400,
                'fixed_rate' => 0,
                'org_id'    => '100000razorpay',
                'payment_method_type' => 'credit',
                'payment_issuer' => 'ICIC'
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'AMEX',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'customer',
                'percent_rate' => 600,
                'org_id'    => '100000razorpay',
                'payment_method_type' => 'debit',
                'payment_issuer' => 'ICIC'
            ],
            [
                'plan_id' => '1ycviEdCgurrHI',
                'plan_name' => 'testFixturePlan',
                'product' => 'primary',
                'feature' => 'payment',
                'payment_method' => 'card',
                'payment_network' => 'AMEX',
                'international' => '1',
                'type' => 'pricing',
                'procurer' => 'razorpay',
                'fee_bearer' => 'customer',
                'percent_rate' => 800,
                'org_id'    => '100000razorpay',
                'payment_method_type' => 'credit',
                'payment_issuer' => 'SBIN'
            ],
        ];

        foreach ($plans as $plan)
        {
            $this->fixtures->create('pricing', $plan);
        }

        $merchantAttributes = [
            'fee_bearer'        => FeeBearer::CUSTOMER,
            'pricing_plan_id'   => '1ycviEdCgurrHI',
        ];

        $this->fixtures->merchant->enableConvenienceFeeModel();
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([Constants::ALLOW_CFB_INTERNATIONAL, 'enable_intl_pricing_amex']);
        $this->fixtures->edit('merchant', '10000000000000', $merchantAttributes);
        $amount = 600;
        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'US',
            'network' => 'Visa',
            'type'    => 'credit',
            'issuer'  => 'ICIC'
        ]);

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequest = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '414366'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponse = $this->makeRequestAndGetContent($flowsRequest);
        $cardCurrency = $flowsResponse['card_currency'];
        $currencyRequestId = $flowsResponse['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4143667057540458', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 27],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $this->assertEquals($amount * 0.04, $calculateFeesResponse['input']['fee']);

        $this->fixtures->iin->create([
            'iin'     => '404464',
            'country' => 'US',
            'network' => 'MasterCard',
            'type'    => 'credit',
            'issuer'  => 'ICIC'
        ]);

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequestMC = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '404464'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponseMC = $this->makeRequestAndGetContent($flowsRequestMC);
        $cardCurrency = $flowsResponseMC['card_currency'];
        $currencyRequestId = $flowsResponseMC['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '4044649165235890', 'cvv' => 566, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 40],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $this->assertEquals($amount * 0.10, $calculateFeesResponse['input']['fee']);

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'US',
            'network' => 'American Express',
            'type'    => 'credit',
            'issuer'  => 'SBIN'
        ]);

        // CALLING FLOWS API TO FETCH DCC RATES AND CURRENCY REQUEST ID

        $flowsRequestAmex = [
            'content' => ['amount' => $amount, 'currency' => 'INR', 'iin' => '556763'],
            'method'  => 'GET',
            'url'     => '/payment/flows',
        ];

        $flowsResponseAmex = $this->makeRequestAndGetContent($flowsRequestAmex);
        $cardCurrency = $flowsResponseAmex['card_currency'];
        $currencyRequestId = $flowsResponseAmex['currency_request_id'];

        // CALLING CALCULATE FEES API TO VERIFY DISPLAY AMOUNTS AND DCC CALCULATIONS

        $calculateFeesRequest = [
            'url'     => '/payments/calculate/fees',
            'method'  => 'POST',
            'content' => [
                'amount'                => $amount,
                'currency'              => 'INR',
                'method'                => 'card',
                'email'                 => 'qa.testing@razorpay.com',
                'contact'               => '+918888888888',
                'card'                  => ['number' => '5567630000002004', 'cvv' => 5666, 'name' => 'Harshil', 'expiry_month' => 12, 'expiry_year' => 40],
                'dcc_currency'          => $cardCurrency,
                'currency_request_id'   => $currencyRequestId,
            ],
        ];

        $calculateFeesResponse = $this->makeRequestAndGetContent($calculateFeesRequest);
        $this->assertEquals($amount * 0.08, $calculateFeesResponse['input']['fee']);
    }
}
