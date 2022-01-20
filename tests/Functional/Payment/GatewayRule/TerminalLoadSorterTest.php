<?php

namespace RZP\Tests\Functional\Payment\GatewayRule;

use RZP\Models\Payment\Method;
use RZP\Models\Terminal\Options;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

/**
 * Tests for terminal selection with different combination of load sorter rules
 */
class TerminalLoadSorterTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TerminalLoadSorterTestData.php';

        parent::setUp();
    }

    public function testTerminalSelectionWithRule()
    {
        $this->setUpTerminals();

        $this->fixtures->merchant->enableMobikwik();

        $testData = $this->testData[__FUNCTION__];

        foreach ($testData as $data)
        {
            $rules = $this->createRules($data['method'], $data['rules']);

            Options::setTestChance($data['test_chance']);

            $paymentData = $this->getPaymentArray($data['method']);

            $content = $this->doAuthAndCapturePayment($paymentData);

            $payment = $this->getLastEntity('payment', true);

            $this->assertEquals($data['expected_terminal'], $payment['terminal_id']);

            $this->fixtures->gateway_rule->delete($rules);
        }

        $this->fixtures->merchant->disableMobikwik();
    }

    /**
     * Tests the case where multiple rules are present but no terminals match
     * any of the rules
     */
    public function testWithMultipleRulesButNomatchingTerminal()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal');
        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->mockCardVault();

        $data = $this->testData[__FUNCTION__];

        $rules = $this->createRules($data['method'], $data['rules']);

        Options::setTestChance($data['test_chance']);

        $paymentData = $this->getPaymentArray($data['method']);

        $content = $this->doAuthAndCapturePayment($paymentData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($data['expected_terminal'], $payment['terminal_id']);
    }

    public function testCapabilityTerminalsSelection()
    {
        $this->fixtures->create('terminal:shared_axis_terminal');
        $this->fixtures->create('terminal:shared_axis_terminal', ['id' => '1001AxisTrmnal', 'capability' => 2]);
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->mockCardVault();

        $testData = $this->testData[__FUNCTION__];

        foreach ($testData as $data)
        {
            $rules = $this->createRules($data['method'], $data['rules']);

            Options::setTestChance($data['test_chance']);

            $paymentData = $this->getPaymentArray($data['method']);

            $content = $this->doAuthAndCapturePayment($paymentData);

            $payment = $this->getLastEntity('payment', true);

            $this->assertEquals($data['expected_terminal'], $payment['terminal_id']);

            $this->fixtures->gateway_rule->delete($rules);
        }
    }

    public function testInternationalAndDomesticPaymentsWithRules()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal', ['international' => true]);
        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal', ['international' => true]);
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->merchant->enableInternational();
        $this->fixtures->merchant->addFeatures([\RZP\Models\Feature\Constants::DISABLE_NATIVE_CURRENCY]);
        $this->mockCardVault();

        $testData = $this->testData[__FUNCTION__];

        foreach ($testData as $data)
        {
            $rules = $this->createRules($data['method'], $data['rules']);

            Options::setTestChance($data['test_chance']);

            $paymentData = $this->getPaymentArray($data['method']);

            if ($data['international'] === true)
            {
                $paymentData['card']['number'] = '4012010000000007';
            }

            $content = $this->doAuthAndCapturePayment($paymentData);

            $payment = $this->getLastEntity('payment', true);

            $this->assertEquals($data['expected_terminal'], $payment['terminal_id']);

            $this->fixtures->gateway_rule->delete($rules);
        }

        $this->fixtures->merchant->disableInternational();
    }

    protected function createRules(string $method, array $ruleParams): array
    {
        $ruleIds = [];

        foreach ($ruleParams as $params)
        {
            $rule = $this->fixtures->create("gateway_rule:$method", $params);

            $ruleIds[] = $rule->getId();
        }

        return $ruleIds;
    }

    protected function getPaymentArray(string $method): array
    {
        switch ($method)
        {
            case Method::CARD:
                return $this->getDefaultPaymentArray();

            case Method::NETBANKING:
                return $this->getDefaultNetbankingPaymentArray('HDFC');

            case Method::WALLET:
                return $this->getDefaultWalletPaymentArray();
        }
    }

    protected function setUpTerminals()
    {
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->mockCardVault();
    }
}
