<?php

namespace RZP\Tests\Functional\Gateway\Esigner\Legaldesk;

use RZP\Models\Admin;
use RZP\Constants\Entity;
use RZP\Models\Payment\Gateway;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Esigner\Legaldesk;
use RZP\Exception\GatewayTimeoutException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Fixtures\Entity\TransactionTrait;

class LegaldeskGatewayTest extends TestCase
{
    use PaymentTrait;
    use TransactionTrait;
    use DbEntityFetchTrait;

    /**
     * @var array
     */
    protected $terminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/LegaldeskGatewayTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_enach_rbl_terminal');
        $this->fixtures->create(Entity::CUSTOMER);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::MERCHANT_ENACH_CONFIGS => [
                    'auth_gateway' =>
                        [
                            'override' => Gateway::ESIGNER_LEGALDESK,
                        ]
                ]
            ]);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $this->gateway = 'esigner_legaldesk';
    }

    public function testEsignGeneration()
    {
        $payment = $this->getEmandatePaymentArray('SVCB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'SVCB0010015',
            'name'              => 'Test account',
            'account_type'      => 'savings',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getDbLastEntity('payment')->toArray();
        $this->assertEquals('authorized', $payment['status']);
    }

    public function testBiometricEsignGeneration()
    {
        $this->markTestSkipped("aadhaar_fp is temporarily blocked");

        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar_fp', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'SVCB0010015',
            'name'              => 'Test account',
            'account_type'      => 'savings',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getDbLastEntity('payment')->toArray();
        $this->assertEquals('aadhaar_fp', $payment['auth_type']);
        $this->assertEquals('authorized', $payment['status']);
    }

    // Mandate fails at the S2S request before we redirect the user to Legaldesk page
    public function testMandateGenerationFailure()
    {
        $payment = $this->getEmandatePaymentArray('SVCB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'SVCB0010015',
            'name'              => 'Test account',
            'account_type'      => 'savings',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'mandate_create')
            {
                $content[Legaldesk\ResponseFields::STATUS] = 'failed';
                $content[Legaldesk\ResponseFields::ERROR] = 'The debtor_name used in the request is invalid.';
                $content[Legaldesk\ResponseFields::ERROR_CODE] = 'em_102';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntity('payment')->toArray();
        $this->assertEquals('failed', $payment['status']);
    }

    public function testMandateSigningFailure()
    {
        $payment = $this->getEmandatePaymentArray('SVCB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'SVCB0010015',
            'name'              => 'Test account',
            'account_type'      => 'savings',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'mandate_sign')
            {
                $content[Legaldesk\ResponseFields::CALLBACK_STATUS] = 'failed';
                $content[Legaldesk\ResponseFields::MESSAGE]         = 'Signing failed';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntity('payment')->toArray();
        $this->assertEquals('failed', $payment['status']);
    }

    public function testMandateSigningTimeout()
    {
        $payment = $this->getEmandatePaymentArray('SVCB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'SVCB0010015',
            'name'              => 'Test account',
            'account_type'      => 'savings',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'mandate_sign')
            {
                throw new GatewayTimeoutException("Timed out");
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('created', $payment['status']);

        return $payment;
    }

    protected function runPaymentCallbackFlowEnachRbl($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest(
                $url, $method, $content);
        }

        $response = $this->sendRequest($request);

        $data = array(
            'url' => $response->headers->get('location'),
            'method' => 'get');

        return $this->submitPaymentCallbackRequest($data);
    }
}
