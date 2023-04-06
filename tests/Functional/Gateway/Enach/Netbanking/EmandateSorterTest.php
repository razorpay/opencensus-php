<?php

namespace RZP\Tests\Functional\Gateway;

use RZP\Constants\Entity;
use RZP\Models\Payment\Gateway;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;

class EmandateSorterTest extends TestCase
{
    use AttemptTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();

        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $this->gateway = 'enach_npci_netbanking';
    }

    public function testEmandateSorter()
    {
        $this->fixtures->create('terminal:shared_enach_npci_netbanking_terminal');

        $this->fixtures->create('terminal:shared_emandate_axis_terminal');

        $payment = $this->getEmandatePaymentArray('UTIB', 'netbanking', 0);

        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'UTIB0002766',
            'name'           => 'Test account',
            'account_type'   => 'savings',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);

        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Gateway::NETBANKING_AXIS, $payment['gateway']);

        $this->assertEquals('NAxRecurringTl', $payment['terminal_id']);
    }

    protected function runPaymentCallbackFlowNetbanking($response, &$callback = null, $gateway)
    {
        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);
        $data = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);
        $result = $this->submitPaymentCallbackRedirect($data);
        return $result;
    }
}
