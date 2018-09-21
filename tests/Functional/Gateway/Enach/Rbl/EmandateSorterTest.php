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

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/EnachRblGatewayTestData.php';

        parent::setUp();

        //$this->sharedTerminal =

        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $this->gateway = 'enach_rbl';
    }

    public function testEmandateSorter()
    {
        $this->fixtures->create('terminal:shared_enach_rbl_terminal');

        $this->fixtures->create('terminal:emandate_icici_terminal');

        $this->fixtures->create('terminal:shared_emandate_icici_terminal');

        $payment                 = $this->getEmandatePaymentArray('ICIC', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'ICIC0002766',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Gateway::NETBANKING_ICICI, $payment['gateway']);
    }

    public function testEmandateSorterShared()
    {
        $this->fixtures->create('terminal:shared_enach_rbl_terminal');

        $this->fixtures->create('terminal:shared_emandate_icici_terminal');

        $payment                 = $this->getEmandatePaymentArray('ICIC', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'ICIC0002766',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Gateway::ENACH_RBL, $payment['gateway']);
    }

    public function testEmandateSorterDirectAndShared()
    {
        $this->fixtures->create('terminal:emandate_icici_terminal');

        $this->fixtures->create('terminal:shared_emandate_icici_terminal');

        $payment                 = $this->getEmandatePaymentArray('ICIC', 'netbanking', 0);
        $payment['bank_account'] = [
            'account_number' => '914010009305862',
            'ifsc'           => 'ICIC0002766',
            'name'           => 'Test account',
        ];

        $order               = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(Gateway::NETBANKING_ICICI, $payment['gateway']);
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

        if($this->isNpciEmandateFlow($content) === true)
        {
            $response = $this->sendRequest($request);

            $this->assertEquals($response->getStatusCode(), '302');

            $data = array(
                'url' => $response->headers->get('location'),
                'method' => 'post');

            if (filter_var($data['url'], FILTER_VALIDATE_URL))
            {
                return $this->submitPaymentCallbackRedirect($data['url']);
            }
        }

        return $this->submitPaymentCallbackRequest($request);
    }

    protected function isNpciEmandateFlow($content)
    {
        $keys = array_keys($content);

        $result = in_array('MerchantID', $keys) and
        in_array('MandateReqDoc', $keys) and
        in_array('CheckSumVal', $keys) and
        in_array('BankID', $keys);

        return $result;
    }
}