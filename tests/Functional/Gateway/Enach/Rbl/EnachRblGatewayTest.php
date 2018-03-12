<?php

namespace RZP\Tests\Functional\Gateway\Enach\Rbl;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Fixtures\Entity\TransactionTrait;

class EnachRblGatewayTest extends TestCase
{
    use PaymentTrait;
    use TransactionTrait;

    public function setUp()
    {
        parent::setUp();

        $this->fixtures->create('terminal:shared_enach_rbl_terminal');
        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $this->gateway = 'enach_rbl';
    }

    public function testSuccessfulEsignGeneration()
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

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('authorize', $enach['action']);
        $this->assertEquals('UTIB', $enach['bank']);
        $this->assertEquals('ratn', $enach['acquirer']);
        $this->assertEquals(0, $enach['amount']);
        $this->assertNotNull($enach['signed_xml']);
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
        else
        {
            ;
        }

        return $this->submitPaymentCallbackRequest($request);
    }
}
