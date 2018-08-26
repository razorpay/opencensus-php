<?php

namespace RZP\Tests\Functional\Gateway\Esigner\Legaldesk;

use RZP\Constants\Entity;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Esigner\Legaldesk;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Fixtures\Entity\TransactionTrait;

class LegaldeskGatewayTest extends TestCase
{
    use PaymentTrait;
    use TransactionTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/LegaldeskGatewayTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_legaldesk_terminal');
        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $this->gateway = 'esigner_legaldesk';
    }

    public function testEsignGeneration()
    {
        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'UTIB0000123',
            'name'              => 'Test account',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);
    }

    // Mandate fails at the S2S request before we redirect the user to Legaldesk page
    public function testMandateGenerationFailure()
    {
        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'UTIB0000123',
            'name'              => 'Test account',
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
    }

    protected function runPaymentCallbackFlowEsignerLegaldesk($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest(
                $url, $method, $content);
        }

        return $this->submitPaymentCallbackRequest($request);
    }
}
