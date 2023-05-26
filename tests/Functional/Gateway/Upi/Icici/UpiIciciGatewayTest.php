<?php

namespace RZP\Tests\Functional\Gateway\Upi\Icici;

use Mail;
use Cache;
use Carbon\Carbon;
use RZP\Exception;
use Illuminate\Database\Eloquent\Factory;

use RZP\Constants\Timezone;
use RZP\Models\Payment\Method;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Upi\Icici\Fields;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Exception\PaymentVerificationException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Tests\Functional\Gateway\Upi\UpiCustomAmountTrait;

class UpiIciciGatewayTest extends TestCase
{
    use OAuthTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use UpiCustomAmountTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/UpiIciciGatewayTestData.php';

        parent::setUp();



        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_icici_terminal');

        $this->gateway = 'upi_icici';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPayment($status = 'created')
    {
        unset($this->payment['description']);

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, $status);

        return $paymentId;
    }

    public function testTpvPayment()
    {
        $this->fixtures->create('terminal:shared_upi_icici_tpv_terminal', ['tpv' => 3]);

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest();

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['amount'] = $order['amount'];
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('100UPIICTpvTml', $payment['terminal_id']);

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('collect', $gatewayEntity['type']);
        $this->assertEquals('vishnu@icici', $gatewayEntity['vpa']);
    }

    public function testIntentTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        $terminal->setAttribute('tpv', 2)->saveOrFail();

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['refId'] = 'ICICIRefId';
            }
            else
            {
                $content['PayerVA'] = 'user@icici';
            }
        });

        $this->startTest($this->testData['testTpvPayment']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultUpiPaymentArray();

        unset($payment['vpa']);
        $payment['_']['flow'] = 'intent';

        $payment['amount'] = $order['amount'];
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1UpiIntICICTml', $payment['terminal_id']);

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('pay', $gatewayEntity['type']);
    }

    public function testIntentTpvPaymentWithOldIfscCode()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        $terminal->setAttribute('tpv', 2)->saveOrFail();

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['refId'] = 'ICICIRefId';
            }
            else
            {
                $content['PayerVA'] = 'user@icici';
            }
        });

        $this->startTest($this->testData['testIntentTpvPaymentWithOldIfscCode']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultUpiPaymentArray();

        unset($payment['vpa']);
        $payment['_']['flow'] = 'intent';

        $payment['amount']   = $order['amount'];
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $order = $this->getLastEntity('order', true);

        //olf ifsc : CORP0001471, new ifsc : UBIN0914711
        $this->assertEquals('UBIN', $order['bank']);

        $this->assertEquals('1UpiIntICICTml', $payment['terminal_id']);

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('pay', $gatewayEntity['type']);
    }

    public function testStaticIfscTPVPayment()
    {
        $this->fixtures->create('terminal:shared_upi_icici_tpv_terminal', ['tpv' => 2]);

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $data = $this->testData['testTpvPayment'];

        $this->mockServerRequestFunction(function ($input) use (& $asserted)
        {
            $asserted = true;

            $this->assertSame('IFSCXXXXXNA', $input['payerIFSC']);
        });

        $this->startTest($data);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['amount'] = $order['amount'];
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $this->assertTrue($asserted, 'Static IFSC assertion failed');

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('100UPIICTpvTml', $payment['terminal_id']);

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('collect', $gatewayEntity['type']);

        $this->assertEquals('vishnu@icici', $gatewayEntity['vpa']);
    }

    public function testIntentTpvPaymentWithBankAsNull()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        $terminal->setAttribute('tpv', 2)->saveOrFail();

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['refId'] = 'ICICIRefId';
            }
            else
            {
                $content['PayerVA'] = 'user@icici';
            }
        });

        $this->startTest($this->testData['testTpvPayment']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultUpiPaymentArray();

        unset($payment['vpa']);
        $payment['_']['flow'] = 'intent';
        $payment['amount'] = $order['amount'];
        // Passing bank as null
        $payment['bank'] = null;
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1UpiIntICICTml', $payment['terminal_id']);

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('pay', $gatewayEntity['type']);
    }

    public function testIntentTpvPaymentWithDifferentBankFromOrderBank()
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        $terminal->setAttribute('tpv', 2)->saveOrFail();

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['refId'] = 'ICICIRefId';
            }
            else
            {
                $content['PayerVA'] = 'user@icici';
            }
        });

        $this->startTest($this->testData['testTpvPayment']);

        $order = $this->getLastEntity('order', true);

        $payment = $this->getDefaultUpiPaymentArray();

        unset($payment['vpa']);
        $payment['_']['flow'] = 'intent';
        $payment['amount'] = $order['amount'];
        // Passing bank as ALLA
        $payment['bank'] = 'ALLA';
        $payment['order_id'] = $order['id'];

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1UpiIntICICTml', $payment['terminal_id']);

        $this->fixtures->merchant->disableTPV();

        $gatewayEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('pay', $gatewayEntity['type']);
    }

    public function tpvBankAccountHandling()
    {
        $cases = [];

        $cases['sbi_missing_zeroes'] = [
            [
                'account_number' => '03040304',
                'ifsc'           => 'SBIN0001069'
            ],
            [
                'payerAccount'   => '00000000003040304', // Leading zeroes padding for 17 chars
            ]
        ];

        $cases['ratn_with_leading_zeroes'] = [
            [
                'account_number'    => '0000840304030466',
                'ifsc'              => 'RATN0000001',
            ],
            [
                'payerAccount'      => '840304030466', // Removing leading zeroes
            ]
        ];

        $cases['ratn_without_leading_zeroes'] = [
            [
                'account_number'    => '1000840304030466',
                'ifsc'              => 'RATN0000001',
            ],
            [
                'payerAccount'      => '1000840304030466', // Unchanged as no leading zeroes
            ]
        ];

        $cases['apgb_missing_zeroes'] = [
            [
                'account_number'    => '91122249452',
                'ifsc'              => 'APGB0000001',
            ],
            [
                'payerAccount'      => '00000091122249452', //Leading zeroes padding for 17 chars
            ]
        ];
        $cases['apgv_missing_zeroes'] = [
            [
                'account_number'    => '91122249452',
                'ifsc'              => 'APGB0000001',
            ],
            [
                'payerAccount'      => '00000091122249452', //Leading zeroes padding for 17 chars
            ]
        ];

        $cases['vara_missing_zeroes'] = [
            [
                'account_number'    => '135791208642',
                'ifsc'              => 'VARA0289011',
            ],
            [
                'payerAccount'      => '0000000135791208642', // Leading zeroes padding for 19 chars
            ]
        ];

        $cases['spcb_missing_zeroes'] = [
            [
                'account_number'    => '135791208642',
                'ifsc'              => 'SPCB0251001',
            ],
            [
                'payerAccount'      => '00000135791208642', // Leading zeroes padding for 17 chars
            ]
        ];

        $cases['mahg_missing_zeroes'] = [
            [
                'account_number'    => '13579120864',
                'ifsc'              => 'MAHG0099922',
            ],
            [
                'payerAccount'      => '00000013579120864', // Leading zeroes padding for 17 chars
            ]
        ];

        return $cases;
    }

    /**
     * @dataProvider tpvBankAccountHandling
     * @param array $bankAccount
     * @param array $expected
     */
    public function testTpvBankAccountHandling(array $bankAccount, array $expected)
    {
        $terminal = $this->fixtures->create('terminal:shared_upi_icici_tpv_terminal');

        $this->fixtures->merchant->enableTpv();

        $this->ba->privateAuth();

        $testdata = $this->testData[__FUNCTION__];

        $testdata['request']['content']['bank_account'] = array_merge([
            'name'  => 'Test User',
        ], $bankAccount);

        // Create Order with Test data
        $this->startTest($testdata);

        $order = $this->getDbLastOrder();

        $payment = $this->getDefaultUpiBlockPaymentArray();

        $payment['amount']      = $order->getAmount();
        $payment['order_id']    = $order->getPublicId();

        // Set assertion
        $this->mockServerContentFunction(function (& $content, $action, $request) use (& $asserted, $expected)
        {
            $asserted = true;
            // remove first four zeroes from the account number since it starts with 0
            $this->assertEquals($expected['payerAccount'], $request['payerAccount']);
        });

        $this->doAuthPayment($payment);

        $this->assertTrue($asserted, 'Gateway request assertion failed');

        $this->assertArraySubset([
            'status'        => 'created',
            'terminal_id'   => $terminal->getId(),
        ], $this->getDbLastPayment()->toArray());

        $this->assertArraySubset([
            'type'          => 'collect',
        ], $this->getDbLastUpi()->toArray());
    }

    public function testIntentDisabledPayment()
    {
        $this->fixtures->merchant->addFeatures(['disable_upi_intent']);

        $payment = $this->getDefaultUpiPaymentArray();

        unset($payment['description']);
        unset($payment['vpa']);

        $payment['_']['flow'] = 'intent';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['refId'] = 'ICICIRefId';
            }
            else
            {
                $content['PayerVA'] = 'user@icici';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testIntentPayment()
    {
        $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $this->fixtures->merchant->setCategory('1111');

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['refId'] = 'ICICIRefId';
            }
            else
            {
                $content['PayerVA'] = 'user@icici';
            }
        });
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $intentUrl = $response['data']['intent_url'];
        $mccFromIntentUrl = substr($intentUrl, strpos($intentUrl,'&mc=') + 4, 4);

        $this->assertEquals('1111', $mccFromIntentUrl);

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals('1UpiIntICICTml', $payment['terminal_id']);
        $this->assertNull($payment['vpa']);
        $this->assertNull($upiEntity['npci_reference_id']);

        $content = $this->getMockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $upi = $this->getEntityById('upi', $upiEntity['id'], true);
        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertEquals($payment['vpa'], 'user@icici');
        $this->assertEquals('ICIC', $upi['bank']);
        $this->assertEquals('icici', $upi['acquirer']);
        $this->assertEquals('icici', $upi['provider']);
        $this->assertSame('12345678987654321', $upi['npci_reference_id']);
        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);
    }

    public function testGPayIntentPayment()
    {
        $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['upi']['flow'] = 'intent';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['refId'] = 'ICICIRefId';
            }
            else
            {
                $content['PayerVA'] = 'user@icici';
            }
        });

        // Not removing method in authorization for now,
        // because methodless payments are not handled in authorization yet.

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        // Changing payment to methodless payment for testing callback flow.
        $payment = $this->getDbLastPayment();
        $payment->setMethod('unselected');
        $payment->setAuthenticationGateway('google_pay');
        $payment->saveOrFail();

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        // before callback, method must be empty
        $this->assertEquals('unselected', $payment['method']);

        $this->assertEquals('1UpiIntICICTml', $payment['terminal_id']);
        $this->assertNull($payment['vpa']);
        $this->assertNull($upiEntity['npci_reference_id']);

        $content = $this->getMockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertTrue($response['success']);

        $upi     = $this->getEntityById('upi', $upiEntity['id'], true);
        $payment = $this->getEntityById('payment', $paymentId, true);

        // Payment method changed to UPI after callback
        $this->assertEquals(Method::UPI, $payment['method']);

        $this->assertEquals($payment['vpa'], 'user@icici');
        $this->assertEquals('ICIC', $upi['bank']);
        $this->assertEquals('icici', $upi['acquirer']);
        $this->assertEquals('icici', $upi['provider']);
        $this->assertSame('12345678987654321', $upi['npci_reference_id']);
        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);
    }

    public function testGPayIntentPaymentFailure()
    {
        $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['upi']['flow'] = 'intent';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['refId'] = 'ICICIRefId';
            }
        });

        // Not removing method in authorization for now,
        // because methodless payments are not handled in authorization yet.

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);
        $paymentId = $response['payment_id'];

        // Changing payment to methodless payment for testing callback flow.
        $payment = $this->getDbLastPayment();
        $payment->setMethod('unselected');
        $payment->setAuthenticationGateway('google_pay');
        $payment->saveOrFail();

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        // before callback, method must be empty
        $this->assertEquals('unselected', $payment['method']);

        $content = $this->getMockServer()->getFailedAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertEquals($response, ['success' => false]);

        $payment = $this->getEntityById('payment', $paymentId, true);

        // Payment method changed to UPI after callback
        $this->assertEquals(Method::UPI, $payment['method']);

        $this->assertEquals('failed', $payment['status']);
    }

    public function testPaymentWithExpiryPublicAuth()
    {
        $payment = $this->payment;

        unset($payment['description']);

        $payment['upi']['expiry_time'] = 10;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testPaymentWithExpiryPrivateAuth()
    {
        $this->fixtures->merchant->addFeatures(['s2supi']);

        $payment = $this->getDefaultUpiPaymentArray();

        $payment['upi']['expiry_time'] = 10;

        $response = $this->doS2SUpiPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $this->checkPaymentStatus($paymentId, 'created');

        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertEquals(10, $upiEntity['expiry_time']);
    }

    /**
     * Tests s2s upi on partner auth with application feature(s2s)
     */
    public function testPaymentWithExpiryPartnerAuth()
    {
        $client = $this->createPartnerApplicationAndGetClientByEnv(
            'dev',
            [
                'type' => 'partner',
                'id'   => 'AwtIC8XQqM0Wet',
                'partner_type'=>'aggregator',
            ]);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $sub = $this->fixtures->merchant->createWithBalance();

        $this->fixtures->methods->createDefaultMethods(['merchant_id' => $sub->getId()]);

        $this->fixtures->merchant->enableUpi($sub->getId());

        $this->fixtures->feature->create([
            'entity_type' => 'application', 'entity_id'  => 'AwtIC8XQqM0Wet', 'name' => 's2s']);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $sub->getId(),
            ]
        );

        $payment = $this->getDefaultUpiPaymentArray();

        $payment['upi']['expiry_time'] = 10;

        $response = $this->doS2sUpiPaymentPartner($client, $sub->getId(), $payment);

        $paymentId = $response['razorpay_payment_id'];

        $pay = $this->getLastEntity('payment', true);

        $this->assertEquals('created', $pay['status']);

        $this->assertEquals($paymentId, $pay['public_id']);

        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertEquals(10, $upiEntity['expiry_time']);
    }

    public function testPaymentViaRedirection()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        // Payment status is a polling API which checkout hits
        // continously. Replicating the same in test case
        $this->checkPaymentStatus($paymentId, 'created');
        $this->checkPaymentStatus($paymentId, 'created');

        return $paymentId;
    }

    public function testPaymentWithXmlResponse()
    {
        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'authorize')
            {
                $content = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body>
        <soapenv:Fault>
            <faultcode>soapenv:Server</faultcode>
            <faultstring>Policy Falsified</faultstring>
            <faultactor>https://apigwuat.icicibank.com:8443/newCollectPay</faultactor>
            <detail>
                <l7:policyResult status="Assertion Falsified" xmlns:l7="http://www.layer7tech.com/ws/policy/fault"/>
            </detail>
        </soapenv:Fault>
    </soapenv:Body>
</soapenv:Envelope>
EOT;
            }
        });

        $payment = $this->getDefaultUpiPaymentArray();
        $payment['vpa'] = 'dontencrypt@icici';
        $payment['notes']['status'] = 'created';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $status = 'created';
        $this->checkPaymentStatus($paymentId, $status);

        return $paymentId;
    }

    public function testPaymentS2S()
    {
        $this->fixtures->merchant->addFeatures(['s2supi']);

        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doS2SUpiPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $this->checkPaymentStatus($paymentId, 'created');

        return $paymentId;
    }

    public function testPaymentWithRandomResponseCode()
    {
        $this->payment['vpa'] = 'unknownresponse@icici';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->testPayment('failed');
        });
    }

    public function testLongVpa()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = str_repeat('thisisaverylongvpa', 6) . '@icici';

        $data = $this->testData['testLongVPA'];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testUpiVpa()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['vpa'] = 'nemo@upi';

        Cache::forever('upi:excluded_psps', '["upi"]');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->doAuthPayment($payment);
        });
    }

    public function testInvalidVpa()
    {
        $vpas = [
            // Emails are not VPAs
            'nemo@razorpay.com',
            // See ProviderCode
            'nemo@statebank',
            'fake@invalidbank',
            // \ not valid
            'a\b@razorpay',
        ];

        foreach ($vpas as $vpa)
        {
            $payment = $this->getDefaultUpiPaymentArray();

            $payment['vpa'] = $vpa;

            $data = $this->testData['testInvalidVpa'];

            $this->runRequestResponseFlow($data, function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            });
        }
    }

    public function testInvalidVpaError()
    {
        $vpas = [
            'invalidvpa@icici'
        ];

        foreach ($vpas as $vpa)
        {
            $payment = $this->getDefaultUpiPaymentArray();

            $payment['vpa'] = $vpa;

            $data = $this->testData['testInvalidVpaError'];

            $this->runRequestResponseFlow($data, function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            });

        }
    }

    public function testSingleWordVpa()
    {
        $payment = $this->getDefaultUpiPaymentArray();
        $payment['vpa'] = 's@dcb';

        $this->doAuthPaymentViaAjaxRoute($payment);
    }

    public function testInvalidResponsePayment()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $this->mockServerContentFunction(function (& $content)
        {
            $content = null;
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPaymentViaAjaxRoute($payment);
        });
    }

    public function testPaymentWithS2S($assert = true)
    {
        $paymentId = $this->testPayment();

        $upiEntity = $this->getLastEntity('upi_icici', true);
        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        if ($assert)
        {
            $this->assertEquals($response, ['success' => true]);
        }

        $payment = $this->getEntityById('payment', $paymentId, true);

        return $payment;
    }

    public function testRejectedPayment($assert = true)
    {
        $paymentId = $this->testPayment();

        $upiEntity = $this->getLastEntity('upi_icici', true);
        $payment = $this->getEntityById('payment', $paymentId, true);

        $server = $this->mockServerContentFunction(function (& $content)
        {
            $content['TxnStatus'] = 'REJECT';
        });

        $content = $server->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertEquals($response, ['success' => false]);

        $data = $this->testData['testStatusRejectPayment'];

        $this->runRequestResponseFlow($data, function () use ($payment)
        {
            $this->getPaymentStatus($payment['id']);
        });
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }

    public function testFullRefund()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $this->mockServerContentFunction(function(& $content, $action)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }

            if ($action === 'refund')
            {
                $content[Fields::ORIGINAL_BANK_RRN_REQ] = '836416213628';
            }
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertNotNull($refund['reference1']);

        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertTestResponse($upiEntity, 'testRefundUpiEntity');
    }

    public function testFullRefundVerifyRecordNotFound()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $this->mockServerContentFunction(function(& $content, $action)
        {
            if ($action === 'verify')
            {
                $content['status'] = '';
                $content['message'] = 'original record not found';
            }

            if ($action === 'refund')
            {
                $content[Fields::ORIGINAL_BANK_RRN_REQ] = '836416213628';
            }
        });

        $this->refundPayment($payment['id']);

        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertTestResponse($upiEntity, 'testRefundUpiEntity');
    }

    public function testFullRefundVerifySuccess()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $this->refundPayment($payment['id']);

        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('authorize', $upiEntity['action']);
        $this->assertEquals(null, $upiEntity['refund_id']);
    }

    public function testRetryRefund()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $refundAmount = 30000;

        $this->mockServerContentFunction(function(& $content, $action)
        {
            if ($action === 'refund')
            {
                $content['status'] = 'FAILURE';
            }
        });

        $refund = $this->refundPayment($payment['id']);

        $this->mockServerContentFunction(function(& $content, $action)
        {
            if ($action === 'refund')
            {
                $content['status'] = 'SUCCESS';
            }
        });

        $refund = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $this->assertEquals($refund['status'], 'processed');
    }

    public function testRetryRefundWithNoTxnFound()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $refundAmount = 30000;

        $this->mockServerContentFunction(function(& $content, $action)
        {
            if ($action === 'refund')
            {
                $content['status'] = 'FAILURE';
            }
        });

        $refund = $this->refundPayment($payment['id']);

        $this->mockServerContentFunction(function(& $content, $action)
        {
            if ($action === 'verify')
            {
                $content['status'] = '';

                $content['message'] = 'original record not found';
            }

            if ($action === 'refund')
            {
                $content['status'] = 'SUCCESS';
            }
        });

        $refund = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $this->assertEquals($refund['status'], 'processed');
    }

    public function testPartialRefund()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $refundAmount = 30000;

        $this->mockServerContentFunction(function(& $content, $action) use ($refundAmount)
        {
            if ($action === 'validateRefund')
            {
                $actualRefundAmount = (int) ($content['refundAmount'] * 100);

                $assertion = ($actualRefundAmount === $refundAmount);

                $this->assertTrue($assertion, 'Actual refund amount different than expected amount');
            }
        });

        $this->mockServerContentFunction(function(& $content, $action)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }
        });

        $this->refundPayment($payment['id'], $refundAmount);

        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertTestResponse($upiEntity, 'testPartialRefundUpiEntity');
    }

    public function testRefundInvalidVpa()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function(& $content)
        {
            $content['success'] = 'false';
            $content['response'] = 5013;
            $content['status'] = 'FAILED';
        });

        $this->refundPayment($payment['id']);
    }

    public function testRefundRequestTimeout()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function(& $content)
        {
            $content['success'] = 'false';
            $content['response'] = 5009;
            $content['status'] = 'FAILED';
        });

        $this->refundPayment($payment['id']);
    }

    public function testRefundDuplicateRequest()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function(& $content)
        {
            $content['success'] = 'false';
            $content['response'] = 5011;
            $content['status'] = 'FAILED';
        });

        $this->refundPayment($payment['id']);
    }

    public function testRefundInsufficientBalance()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function(& $content)
        {
            $content['success'] = 'false';
            $content['response'] = 5014;
            $content['status'] = 'FAILED';
        });

        $this->refundPayment($payment['id']);
    }

    public function testRefundInvalidEncryptedRequest()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function(& $content)
        {
            $content['success'] = 'false';
            $content['response'] = 8000;
            $content['status'] = 'FAILED';
        });

        $this->refundPayment($payment['id']);
    }

    public function testRefundInternalServerError()
    {
        $payment = $this->testPaymentWithS2S();

        $this->capturePayment($payment['id'], 50000);

        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function(& $content)
        {
            $content['success'] = 'false';
            $content['response'] = 8009;
            $content['status'] = 'FAILED';
        });

        $this->refundPayment($payment['id']);
    }

    public function testVerifyPayment()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $authPayment = $this->doAuthPaymentViaAjaxRoute($payment);

        $upiEntity = $this->getLastEntity('upi', true);
        $payment = $this->getEntityById('payment', $authPayment['payment_id'], true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);
        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->payment = $this->verifyPayment($payment['id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyPaymentGateway()
    {
        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();
        $upi = $this->getDBLastEntity('upi');

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $this->makeS2SCallbackAndGetContent($content);

        $payment->reload();
        $this->assertEquals('authorized', $payment['status']);
        $payment_updated_at = $payment['updated_at'];

        $upi = $this->getDBLastEntity('upi');
        $updated_at = $upi['updated_at'];

        sleep(1);

        $this->verifyGatewayPayment($payment->getPublicId());

        $payment->reload();

        $upi = $this->getDbLastEntity('upi');

        // asserting that entities are not updated
        $this->assertEquals($updated_at, $upi['updated_at']);
        $this->assertEquals($payment_updated_at, $payment['updated_at']);

    }

    public function testVerifyPaymentWithNpciRefIdMismatch()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $authPayment = $this->doAuthPaymentViaAjaxRoute($payment);

        $upi = $this->getDbLastEntity('upi');
        $payment = $this->getDbLastPayment();

        $this->assertNotNull($upi->getNpciReferenceId());
        $this->assertNull($payment->getReference16());
        $afterRequest = $upi->getNpciReferenceId();

        $content = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());
        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertNotNull($upi->refresh()->getNpciReferenceId());
        $this->assertNotNull($payment->refresh()->getReference16());
        $this->assertSame($payment->getReference16(), $upi->getNpciReferenceId());
        $afterCallback = $upi->getNpciReferenceId();

        $this->assertSame($afterRequest, $afterCallback);

        $this->verifyPayment($payment->getPublicId());

        $this->assertNotNull($upi->refresh()->getNpciReferenceId());
        $this->assertNotNull($payment->refresh()->getReference16());
        $this->assertNotEquals($payment->getReference16(), $upi->getNpciReferenceId());
        $afterVerify = $upi->getNpciReferenceId();

        $this->assertNotSame($afterCallback, $afterVerify);

        $rrns = $upi->getGatewayData()[Entity::NPCI_REFERENCE_ID];

        $this->assertCount(2, $rrns);

        $this->assertSame('callback', $rrns[$afterCallback]['action']);
        $this->assertSame('verify', $rrns[$afterVerify]['action']);

        $this->assertArrayHasKey('updated_at', $rrns[$afterCallback]);
        $this->assertArrayHasKey('updated_at', $rrns[$afterVerify]);
    }

    public function testVerifyPaymentWithAmountMismatch()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['notes']['amount'] = 'mismatch';

        $authPayment = $this->doAuthPaymentViaAjaxRoute($payment);

        $upiEntity = $this->getLastEntity('upi', true);
        $payment = $this->getEntityById('payment', $authPayment['payment_id'], true);

        $payment['notes']['amount'] = 'mismatch';

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);
        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->expectException(PaymentVerificationException::class);

        $this->payment = $this->verifyPayment($payment['id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    /**
     * Make sure a 5006 is taken as a gateway failure
     */
    public function testVerifyMissingPayment()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        // TODO: Stop using notes for status
        // Instead use something like `status_code_success_etc@icici`
        // To encode all expected information in the VPA itself
        //
        // Will work on this in #1997
        $payment['notes']['status'] = 'failed';
        $payment['vpa'] = 'missingpayment@icici';

        $authPayment = $this->doAuthPaymentViaAjaxRoute($payment);

        $upiEntity = $this->getLastEntity('upi', true);
        $payment = $this->getEntityById('payment', $authPayment['payment_id'], true);

        $this->payment = $this->verifyPayment($payment['id']);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        // This will be updated if ran via cron
        $this->assertSame($payment['verified'], 1);
    }

    public function testVerifyPaymentWithEncryptedResponse()
    {
        $payment = $this->getDefaultUpiPaymentArray();
        $payment['notes']['encrypt'] = 'true';

        $authPayment = $this->doAuthPaymentViaAjaxRoute($payment);

        $upiEntity = $this->getLastEntity('upi', true);
        $payment = $this->getEntityById('payment', $authPayment['payment_id'], true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);
        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->payment = $this->verifyPayment($payment['id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testVerifyCreatedPayment()
    {
        //inititate payment
        $input = $this->getDefaultUpiPaymentArray();
        $input['notes']['status'] = 'success';

        $this->doAuthPaymentViaAjaxRoute($input);

        $payment = $this->getDbLastPayment();
        $upi = $this->getDbLastEntity('upi');

        //No callback was fired from mock at this point, hence payment status == created
        $this->assertSame($payment->getId(), $upi->getPaymentId());
        $this->assertTrue($payment->isCreated());
        $this->assertSame(['rrn' => null], $payment->acquirer_data->toArray());
        $this->assertSame($input['vpa'], $upi['vpa']);
        $this->assertSame($input['vpa'], $payment['vpa']);
        $this->assertNotNull($upi['npci_reference_id']);

        $this->authorizeFailedPayment($payment->getPublicId());  //will change payment status from fail to authorised
        $this->assertTestResponse($upi->refresh()->toArrayAdmin(), 'testPaymentUpiEntity');

        $this->assertNotEmpty($upi->getNpciReferenceId());
        $this->assertSame($upi->getNpciReferenceId(), $payment->refresh()->getReference16());
        $this->assertSame(true, $upi->getReceived());
        $this->assertSame(['rrn' => $upi->getNpciReferenceId()], $payment->acquirer_data->toArray()); //Assertion to make sure RRN is captured
        $this->assertArrayHasKey('gateway_payment_id', $upi);
        $this->assertSame($input['vpa'], $upi['vpa']);
        $this->assertSame($input['vpa'], $payment['vpa']);
    }

    public function testRefundExcelFile()
    {
        Mail::fake();

        $payment = $this->testPaymentWithS2S();
        $this->capturePayment($payment['id'], 50000);

        $refund = $this->refundPayment($payment['id']);

        $payment = $this->testPaymentWithS2S();
        $this->capturePayment($payment['id'], 50000);
        $refund = $this->refundPayment($payment['id'], 10000);
        $refund = $this->refundPayment($payment['id']);

        $refunds = $this->getEntities('refund', [], true);

        // Convert the created_at dates to yesterday's so that they are picked
        // up during refund excel generation
        foreach ($refunds['items'] as $refund)
        {
            $createdAt = Carbon::yesterday(Timezone::IST)->timestamp + 5;
            $this->fixtures->edit('refund', $refund['id'], ['created_at' => $createdAt]);
        }

        $payment = $this->testPaymentWithS2S();
        $this->capturePayment($payment['id'], 50000);
        $this->refundPayment($payment['id']);

        $data = $this->generateRefundsExcelForIciciUpi();

        $this->assertEquals(3, $data['upi_icici']['count']);
        $this->assertTrue(file_exists($data['upi_icici']['file']));

        Mail::assertQueued(RefundFileMail::class, function ($mail)
        {
            $body = 'Please find attached refunds information for UPI';

            $this->assertEquals($body, $mail->viewData['body']);

            return true;
        });

        unlink($data['upi_icici']['file']);
    }

    protected function generateRefundsExcelForIciciUpi($date = false)
    {
        $this->ba->adminAuth();

        $request = array(
            'url' => '/refunds/excel',
            'method' => 'post',
            'content' => [
                'method'    => 'upi',
                'bank'      => 'ICIC',
                'frequency' => 'daily'
            ],
        );

        if ($date)
        {
            $request['content']['on'] = Carbon::now()->format('Y-m-d');
        }

        return $this->makeRequestAndGetContent($request);
    }

    /**
     * TODO: Move this test to Payment Test
     * But we can only do that once we have sharp support for async
     */
    public function testAsyncPaymentAutoCaptured()
    {
        $this->ba->privateAuth();

        $res = $this->startTest($this->testData['testCreateAutoCaptureOrder']);

        $this->payment['order_id'] = $res['id'];

        $payment = $this->testPaymentWithS2S(true);

        $this->assertEquals('captured', $payment['status']);

        $response = $this->getPaymentStatus($payment['id']);

        $this->assertEquals([
            'razorpay_payment_id',
            'razorpay_order_id',
            'razorpay_signature'],

        array_keys($response));
    }

    public function testCollectForceAuthorized()
    {
        $now  = Carbon::now();

        Carbon::setTestNow(Carbon::parse('15 minutes ago'));

        $this->doAuthPaymentViaAjaxRoute(array_except($this->payment, 'description'));

        $payment = $this->getDbLastPayment();
        $upi     = $this->getDbLastEntity('upi');

        $this->assertSame('created', $payment->getStatus());
        $this->assertSame('92', $upi->status_code);

        Carbon::setTestNow($now);

        $this->timeoutOldPayment();

        $payment->reload();
        $upi->reload();

        $this->assertSame('failed', $payment->getStatus());
        $this->assertSame('92', $upi->status_code);

        $this->forceAuthorizeFailedPayment($payment->getPublicId(), []);

        $payment->reload();
        $upi->reload();

        $this->assertSame('authorized', $payment->getStatus());
        $this->assertSame('SUCCESS', $upi->status_code);
    }

    public function testIntentForceAuthorized()
    {
        $now  = Carbon::now();

        Carbon::setTestNow(Carbon::parse('15 minutes ago'));

        $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);
        $this->payment['_']['flow'] = 'intent';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['refId'] = 'ICICIRefId';
            }
            else
            {
                $content['PayerVA'] = 'user@icici';
            }
        });

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();
        $upi     = $this->getDbLastEntity('upi');

        $this->assertSame('created', $payment->getStatus());
        $this->assertSame('0', $upi->status_code);

        Carbon::setTestNow($now);

        $this->timeoutOldPayment();

        $payment->reload();
        $upi->reload();

        $this->assertSame('failed', $payment->getStatus());
        $this->assertSame('0', $upi->status_code);

        $this->forceAuthorizeFailedPayment($payment->getPublicId(),
            [
                'vpa'                => 'vishnu@icici',
                'gateway_payment_id' => '800800800800',
            ]);

        $payment->reload();
        $upi->reload();

        $this->assertSame('authorized', $payment->getStatus());
        $this->assertSame('SUCCESS', $upi->status_code);
        $this->assertSame('vishnu@icici', $upi->vpa);
        $this->assertSame('icici', $upi->provider);
        $this->assertSame('800800800800', $upi->gateway_payment_id);
    }

    public function testLateAuthorizePayment()
    {
        $now  = Carbon::now();

        Carbon::setTestNow(Carbon::parse('15 minutes ago'));

        $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);
        $this->payment['_']['flow'] = 'intent';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content['refId'] = 'ICICIRefId';
            }
            else
            {
                $content['PayerVA'] = 'user@icici';
            }
        });

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();
        $upi     = $this->getDbLastEntity('upi');

        $this->assertSame('created', $payment->getStatus());
        $this->assertSame('0', $upi->status_code);

        Carbon::setTestNow($now);

        $this->timeoutOldPayment();

        $this->assertNull($payment->getReference16());

        $this->authorizedFailedPayment($payment->getPublicId());

        $payment->reload();

        $this->assertNotNull($payment->getReference16());
        $this->assertTrue($payment->isAuthorized());
        $this->assertTrue($payment->isLateAuthorized());

        $upi->reload();
        $this->assertEquals($upi->getPaymentId(), $payment['id']);

        $this->assertSame('icici', $upi->provider);
        $this->assertSame('ICIC', $upi->bank);
        $this->assertSame('0', $upi->status_code);
    }

    public function testValidateAccountVpa()
    {
        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIICICITml');

        $this->ba->publicAuth();
        $this->startTest();
    }

    public function testValidateVpaInvalidVpa()
    {
        config()->set('gateway.validate_vpa_terminal_ids.test', '100UPIICICITml');

        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testUnexpectedPaymentCreation()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertTrue($response['success']);

        $this->assertNotEmpty($response['payment_id']);
    }

    public function testUnexpectedPaymentCreationWithPayerAccountType()
    {
        $content = $this->buildUnexpectedPaymentRequest();
        $content['payment']['payer_account_type'] = 'BANK_ACCOUNT';

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $payment = $this->getDbLastPayment();

        $this->assertEquals('bank_account', $payment['reference2']);

        $this->assertTrue($response['success']);

        $this->assertNotEmpty($response['payment_id']);
    }

    public function testUnexpectedPaymentCreationWithInvalidPayerAccountType()
    {
        $content = $this->buildUnexpectedPaymentRequest();
        $content['payment']['payer_account_type'] = 'INVALIDTYPE';

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $payment = $this->getDbLastPayment();

        $this->assertNull($payment['reference2']);

        $this->assertTrue($response['success']);

        $this->assertNotEmpty($response['payment_id']);
    }

    /**
     * Test unexpected payment request mandatory validation
     */
    public function testUnexpectedPaymentValidationFailure()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        unset($content['upi']['npci_reference_id']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url' => '/payments/create/upi/unexpected',
                'method' => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class);
    }

    /**
     * Tests the duplicate unexpected payment creation
     * for recon edge cases invalid paymentId, rrn mismatch ,Multiple RRN.
     * Amount mismatch case is handled in seperate testcase
     */
    public function testUnexpectedPaymentCreateForAmountMismatch()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastUpi();

        $this->assertSame('created', $payment->getStatus());

        $callbackResponse = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $this->makeS2SCallbackAndGetContent($callbackResponse);

        $content['upi']['merchant_reference'] = $upi->getPaymentId();
        $content['upi']['npci_reference_id'] = $upi->getNpciReferenceId();
        $content['upi']['vpa'] = $upi->getVpa();

        //Setting amount to different amount for validating payment creation for amount mismatch
        $content['payment']['amount'] = 10000;
        //First occurence of amount mismatch payment request with matching rrn, paymentId, differing in amount
        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $upi = $this->getDbLastUpi();

        $this->assertEquals($upi['amount'], $content['payment']['amount']);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);
    }

    /**
     * Tests the payment create for duplicate unexpected payment
     */
    public function testDuplicateUnexpectedPaymentCreate()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastUpi();

        $this->assertSame('created', $payment->getStatus());

        $callbackResponse = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $this->makeS2SCallbackAndGetContent($callbackResponse);

        $content['upi']['merchant_reference'] = $upi->getPaymentId();
        $content['upi']['npci_reference_id'] = $upi->getNpciReferenceId();

        // Hit payment create again
        $this->makeRequestAndCatchException(function() use ($content) {
            $request = [
                'url'     => '/payments/create/upi/unexpected',
                'method'  => 'POST',
                'content' => $content,
            ];
            $this->ba->appAuth();
            $this->makeRequestAndGetContent($request);

        }, Exception\BadRequestException::class,
           'Duplicate Unexpected payment with same amount');
    }

    public function testDuplicateUnexpectedPaymentCreateAmountMismatch()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        //Setting amount to different amount for validating payment creation for amount mismatch
        $content['payment']['amount'] = 10000;
        //First occurence of amount mismatch payment request with matching rrn, paymentId, differing in amount
        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $upiEntity = $this->getDbLastUpi();

        $this->assertEquals($upiEntity['amount'],$content['payment']['amount']);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        // Hitting the payment create again for same amount mismatch request
        $this->makeRequestAndCatchException(function() use ($content) {
            $request = [
                'url'     => '/payments/create/upi/unexpected',
                'method'  => 'POST',
                'content' => $content,
            ];
            $this->ba->appAuth();
            $this->makeRequestAndGetContent($request);

        }, Exception\BadRequestException::class,
           'Multiple payments with same RRN');
    }

    /**
     * Tests the payment create for multiple payments with same RRN
     */
    public function testUnexpectedPaymentForDuplicateRRN()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        //First occurence of unexpected payment request with matching rrn, paymentId, differing in amount
        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi = $this->getDbLastUpi();

        $this->assertSame('created', $payment->getStatus());

        $callbackResponse = $this->mockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $this->makeS2SCallbackAndGetContent($callbackResponse);

        $upiEntity = $this->getDbLastUpi();

        $this->fixtures->edit('upi', $upiEntity['id'], ['npci_reference_id' => '123456789012']);

        $this->makeRequestAndCatchException(function() use ($content) {
            $request = [
                'url'     => '/payments/create/upi/unexpected',
                'method'  => 'POST',
                'content' => $content,
            ];
            $this->ba->appAuth();
            $this->makeRequestAndGetContent($request);

        }, Exception\BadRequestException::class,
            'Multiple payments with same RRN');
    }

    /**
     * Authorize the failed payment by force authorizing it
     */
    public function testAuthorizeFailedPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame('created', $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'              => 'failed',
                'authorized_At'       => null,
                'error_code'          => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'   => 'Payment was not completed on time.',
            ]);

        $this->fixtures->edit('upi', $upiEntity['id'], ['status_code' => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        unset($content['upi']['npci_txn_id']);

        $content['upi']['gateway'] = 'upi_icici';

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = true;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $this->assertNotEmpty($response['payment_id']);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference16']);

        $this->assertEquals('123456789013', $updatedPayment['reference16']);

        $this->assertEquals('razor.pay@sbi', $updatedPayment['vpa']);

        $this->assertEquals(true, $response['success']);

        // explicitly capturing the payment to stimulate the auto capture in case DS merchants
        $this->capturePayment('pay_'.$updatedPayment['id'], 50000);

        $this->assertEquals(true, $response['success']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);
    }

    /**
     * Successful farce auth of failed payment with input only containing upi, meta and payment fiedls, not netbanking.
     */
    public function testAuthorizeFailedPaymentWithOnlyUpiInput()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame('created', $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'              => 'failed',
                'authorized_At'       => null,
                'error_code'          => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'   => 'Payment was not completed on time.',
            ]);

        $this->fixtures->edit('upi', $upiEntity['id'], ['status_code' => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        // remove netbanking block
        unset($content['netbanking']);

        unset($content['upi']['npci_txn_id']);

        $content['upi']['gateway'] = 'upi_icici';

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = true;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $this->assertNotEmpty($response['payment_id']);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference16']);

        $this->assertEquals('123456789013', $updatedPayment['reference16']);

        $this->assertEquals('razor.pay@sbi', $updatedPayment['vpa']);

        $this->assertEquals(true, $response['success']);

        // explicitly capturing the payment to stimulate the auto capture in case DS merchants
        $this->capturePayment('pay_'.$updatedPayment['id'], 50000);

        $this->assertEquals(true, $response['success']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);
    }

    /**
     * Validate negative case of authorizing successful payment
     */
    public function testForceAuthorizeSuccessfulPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame('created', $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('authorized', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        unset($content['upi']['npci_txn_id']);

        $content['upi']['gateway'] = 'upi_icici';

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = true;

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
           'Non failed payment given for authorization');
    }

    /**
     * Checks for validation failure in case of missing payment_id
     */
    public function testForceAuthorizePaymentValidationFailure()
    {
        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $this->makeRequestAndCatchException(function() use ($content) {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
           'The payment.id field is required.');
    }

    /**
     * Checks for validation failure in case of missing npci_reference_id
     */
    public function testForceAuthorizePaymentValidationFailure2()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame('created', $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'              => 'failed',
                'authorized_At'       => null,
                'error_code'          => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'   => 'Payment was not completed on time.',
            ]);

        $this->fixtures->edit('upi', $upiEntity['id'], ['status_code' => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = false;

        // Unsetting the npci_reference_id to mimic validation failure
        unset($content['upi']['npci_reference_id']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
           'The upi.npci reference id field is required.');
    }

    //Tests for force authorize with mismatched amount in request.
    public function testForceAuthorizePaymentAmountMismatch()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame('created', $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        $this->fixtures->payment->edit($payment['id'],
            [
               'status'              => 'failed',
               'authorized_At'       =>  null,
               'error_code'          => 'BAD_REQUEST_ERROR',
               'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
               'error_description'   => 'Payment was not completed on time.',
            ]);

        $this->fixtures->edit('upi', $upiEntity['id'], ['status_code' => '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = false;

        // Change amount to 60000 for mismatch scenario
        $content['payment']['amount'] = 60000;

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
           'The amount does not match with payment amount');

    }

    /**
     * Authorize the failed payment by verifying at gateway
     */
    public function testVerifyAuthorizeFailedPayment()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertSame('created', $payment['status']);

        $callbackContent = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $callbackResponse = $this->makeS2SCallbackAndGetContent($callbackContent);

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'              => 'failed',
                'authorized_At'       => null,
                'error_code'          => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'   => 'Payment was not completed on time.',
            ]);

        $this->fixtures->edit('upi', $upiEntity['id'], ['status_code'=> '']);

        $payment = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray(Entity::UPI);

        $this->assertNotEquals('S', $upiEntity['status_code']);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultUpiAuthorizeFailedPaymentArray();

        unset($content['upi']['npci_txn_id']);

        $content['upi']['gateway'] = 'upi_icici';

        $content['payment']['id'] = $payment['id'];

        $content['meta']['force_auth_payment'] = false;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference16']);

        // asset the late authorized flag for authorizing via verify
        $this->assertTrue($updatedPayment['late_authorized']);

        $this->assertEquals(true, $response['success']);

        // explicitly capturing the payment to stimulate the auto capture in case DS merchants
        $this->capturePayment('pay_'.$updatedPayment['id'], 50000);

        $this->assertEquals(true, $response['success']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);
    }

    public function testCallbackRedirectToDark()
    {
        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $upi     = $this->getDbLastEntity('upi');

        $upiEntity = $upi->toArray();
        // This is to force the dark redirection, Making it similar to recurring payments
        // Using notify action because actual callback will never expect this and the test will remain same
        $upiEntity['payment_id'] = $payment->getId() . '1' . 'notify' . '0';

        $content = $this->getMockServer()->getAsyncCallbackContent($upiEntity, $payment->toArray());

        $request = [
            'url'       => '/callback/recurring/upi_icici',
            'method'    => 'post',
            'raw'       => $content,
        ];

        $requestSentToDark = false;

        $this->mockServerContentFunction(function (&$content, $action = null) use (&$requestSentToDark) {
            if ($action === 'redirectToDark')
            {
                $requestSentToDark = true;
            }
        }, $this->gateway);

        $response = $this->makeRequestParent($request);

        $this->assertTrue($requestSentToDark, 'The callback was not sent to dark-api');

        $this->assertTrue($payment->refresh()->isCreated());

        // set the mozart url as the mozart-dark url so that callback is not sent to dark-api
        config()->set('applications.mozart.live.url', 'https://mozart-dark.razorpay.com');

        $requestSentToDark = false;

        $response = $this->makeS2sCallbackAndGetContent($content);

        $this->assertFalse($requestSentToDark, 'The callback was sent to dark-api');

        $this->assertEquals($response, ['success' => false]);
    }

    function testNpciErrorCodeForCallback()
    {
        // Do an Auth Payment
        $this->doAuthPaymentViaAjaxRoute($this->payment);

        // Fetch the last payment entity
        $payment = $this->getDbLastPayment();

        // Fetch the last UPI Entity
        $upiEntity = $this->getDbLastEntity('upi')->toArray();

        // Set ResponseCode in Mock Server Content Function to NPCI Error Code
        $server = $this->mockServerContentFunction(function (& $content)
        {
            $content['ResponseCode'] = 'U03';
            $content['TxnStatus'] = 'FAILURE';
        });

        // Get the callback content
        $content = $server->getAsyncCallbackContent($upiEntity, $payment->toArray());

        // Now the callback will throw assertion error as the callback payment id is not same as actual payment id
        $response = $this->makeS2sCallbackAndGetContent($content);
        $this->assertEquals($response, ['success' => false]);
        // Fetch the last payment
        $payment = $this->getDbLastPayment();

        // Assert Payment INTERNAL_ERROR_CODE
        $this->assertSame(
            'BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED',
            $payment['internal_error_code']
        );

        // Fetch the UPI Entity from database
        $upiEntity = $this->getLastEntity('upi', true);

        // Assert status_code in UPI Entity
        $this->assertSame(
            'U03',
            $upiEntity['status_code']
        );
    }

    function testNpciGatewayErrorStoreAndShare()
    {
        $this->fixtures->merchant->addFeatures(['expose_gateway_errors']);
        // Do an Auth Payment
        $this->doAuthPaymentViaAjaxRoute($this->payment);

        // Fetch the last payment entity
        $payment = $this->getDbLastPayment();

        // Fetch the last UPI Entity
        $upiEntity = $this->getDbLastEntity('upi')->toArray();

        // Set ResponseCode in Mock Server Content Function to NPCI Error Code
        $server = $this->mockServerContentFunction(function (& $content)
        {
            $content['ResponseCode'] = 'U03';
            $content['TxnStatus'] = 'FAILURE';
        });

        // Get the callback content
        $content = $server->getAsyncCallbackContent($upiEntity, $payment->toArray());

        // Now the callback will throw assertion error as the callback payment id is not same as actual payment id
        $response = $this->makeS2sCallbackAndGetContent($content);

        $this->assertEquals($response, ['success' => false]);
        // Fetch the last payment
        $payment = $this->getDbLastPayment();

        $paymentFetchResponse = $this->fetchPayment($payment['public_id']);

        // Assert status_code in UPI Entity
        $this->assertSame(
            'U03',
            $paymentFetchResponse['gateway_data']['error_code']
        );
    }

    function testErrorCodeWithoutResponseCodeForCallback()
    {
        $this->doAuthPaymentViaAjaxRoute($this->payment);
        $payment = $this->getDbLastPayment();
        $upiEntity = $this->getDbLastEntity('upi')->toArray();

        // Set response in Mock Server Content Function for TxnStatus field
        // There will be no ResponseCode field in the callback
        // This test case you check the case when there is no ResponseCode in callback
        $server = $this->mockServerContentFunction(function (& $content)
        {
            $content['TxnStatus'] = 'FAILURE';
            $content['response'] = 8000;
        });

        // Get the callback content
        $content = $server->getAsyncCallbackContent($upiEntity, $payment->toArray());

        $response = $this->makeS2sCallbackAndGetContent($content);
        $this->assertEquals($response, ['success' => false]);

        // Assert Payment INTERNAL_ERROR_CODE
        $payment = $this->getDbLastPayment();
        $this->assertSame(
            'GATEWAY_ERROR_REQUEST_ERROR',
            $payment['internal_error_code']
        );

        // Assert status_code in UPI Entity
        $upiEntity = $this->getLastEntity('upi', true);
        $this->assertSame('8000', $upiEntity['status_code']);
    }

    protected function setUpUpiCustomAmountTest($status, $amount)
    {
        // Technically Custom Amount is only on intent
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_icici_intent_terminal');

        $order = $this->createOrder();

        $payment = $this->getDefaultUpiIntentPaymentArray($order);

        unset($payment['bank']);

        $payment['upi']['flow'] = 'intent';
        $payment['amount']      = $order['amount'];
        $payment['order_id']    = $order['id'];

        $this->mockServerContentFunction(function(& $content, $action = null) use ($status, $amount)
        {
            if ($action === 'authorize')
            {
                $content['refId']       = 'ICICIRefId';
            }
            else if ($action === 'verify')
            {
                $content['Amount']          = number_format($amount / 100, 2, '.', '');
                $content['status']          = $status === 'authorized' ? 'SUCCESS' : 'FAILURE';
                $content['response']        = $status === 'authorized' ? '00' : 'U03';
                $content['payerVA']         = 'user@icici';
                $content['OriginalBankRRN'] = '101010101010';
            }
            else
            {
                $content['PayerVA']         = 'user@icici';
                $content['PayerAmount']     = number_format($amount / 100, 2, '.', '');
                $content['TxnStatus']       = $status === 'authorized' ? 'SUCCESS' : 'FAILURE';
                $content['ResponseCode']    = $status === 'authorized' ? '00' : 'U03';
            }
        });

        return $this->doAuthPaymentViaAjaxRoute($payment);
    }

    protected function makeAsyncCallbackGatewayCall($upi, $payment, $success = true)
    {
        $content = $this->getMockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $response = $this->makeS2sCallbackAndGetContent($content);

        $this->assertSame($response['success'], $success, 'Callback assertion failed');
    }

    protected function tearDownUpiCustomAmountTest()
    {
        // Nothing Specific
    }

    /**
     * This test is for all vpa handles declared in ProviderCode.php.
     * Essentially, all the whitelisted vpa handles must be mapped to a bank code for UPI regular for the test to pass.
     * Note - This would not fail if a new vpa handle is added and not whitelisted.
     */
    public function testValidateVpaRegular()
    {
        $vpaHandles = $this->getWhitelistedVpaHandlesUpiRegular();
        foreach ($vpaHandles as $vpaHandle)
        {
            $this->assertTrue(ProviderCode::validate($vpaHandle), 'Failed to get bank code for vpa handle: ' . $vpaHandle);
        }
    }

    protected function makeUnexpectedPaymentAndGetContent(array $content)
    {
        $request =[
            'url' => '/payments/create/upi/unexpected',
            'method' => 'POST',
            'content' => $content,
        ];

        $this->ba->appAuth();

       return $this->makeRequestAndGetContent($request);
    }

    /**
     * @return array
     */
    protected function buildUnexpectedPaymentRequest()
    {
        $this->fixtures->merchant->createAccount('100DemoAccount');
        $this->fixtures->merchant->enableUpi('100DemoAccount');

        $content = $this->getDefaultUpiUnexpectedPaymentArray();

        // Unsetting fields which will not be present in UpiIcici MIS
        unset($content['upi']['account_number']);
        unset($content['upi']['ifsc']);
        unset($content['upi']['npci_txn_id']);
        unset($content['upi']['gateway_data']);

        $content['terminal']['gateway']             = 'upi_icici';
        $content['terminal']['gateway_merchant_id'] = $this->sharedTerminal->getGatewayMerchantId();

        return $content;
    }

    protected function makeAuthorizeFailedPaymentAndGetPayment(array $content)
    {
        $request = [
            'url'      => '/payments/authorize/upi/failed',
            'method'   => 'POST',
            'content'  => $content,
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }
}
