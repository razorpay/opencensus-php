<?php

namespace RZP\Tests\Functional\International;

use Mockery;
use Illuminate\Database\Eloquent\Factory;

use RZP\Models\Address\Type;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\Invoice\InvoiceTestTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->publicAuth();
        $this->fixtures->create('terminal:shared_sharp_terminal');

    }

    public function testOpgspImportPaymentWithAmountGreaterThanOpgspLimit()
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

    }

    public function testOpgspImportPaymentWithAmountGreaterThanOpgspLimitConfigKey()
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

    }

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

    public function testOpgspImportDuplicateInvoiceNumberForSuccessfulPayment()
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
    }

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

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = '1000000';
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

    public function testOpgspImportPaymentPositive()
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

    }

    public function testOpgspImportPaymentNBPositive()
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
    }

    public function testUpdateMerchantDocumentForPayment()
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
    }

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
}
