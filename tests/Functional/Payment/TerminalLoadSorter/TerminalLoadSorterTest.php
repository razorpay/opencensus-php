<?php

namespace RZP\Tests\Functional\Payment\TerminalLoadSorter;

use Carbon\Carbon;
use RZP\Models\Merchant;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal\Options;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class TerminalLoadSorterTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/TerminalLoadSorterTestData.php';

        parent::setUp();
    }

    public function testCreateGatewayRuleForCard()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleForCardWithInvalidGateway()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleForCardWithInvalidCardGateway()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleWithInvalidMethod()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleForCardWithInvalidNetwork()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleForCardWithInvalidNetworkForGateway()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleForCardWithInvalidIssuer()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleForCardWithInvalidCardType()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleForCardWithInvalidGatewayAcquirer()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleForNetbanking()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleForNetbankingWithInvalidGatewayForNetbanking()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleForNetbankingWithDirectNetbankingGatewayAndNullIssuer()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleForNetbankingWithIssuerNotSupportedByGateway()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleForWallet()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleWithAlreadyExistingRule()
    {
        $existingRule = $this->fixtures->create('gateway_rule:card');

        $this->ba->appAuth();

        $this->startTest();
    }

    /**
     * Tests the case when there is a load existing and new load input potentially
     * conflictes with the new load input but the cumulate load is <= 100 %
     */
    public function testCreateGatewayRuleWithConflictingRulesButTotalLoadNotExceedingMaxLoad()
    {
        $existingRule = $this->fixtures->create('gateway_rule:card', [
            'network' => null,
            'load' => 60,
        ]);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleWithConflictingRulesButTotalLoadExceedsMaxLoad()
    {
        $existingRule = $this->fixtures->create('gateway_rule:card', [
            'network' => null,
            'load' => 60,
        ]);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testUpdadateGatewayRuleLoad()
    {
        $existingRule = $this->fixtures->create('gateway_rule:card');

        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/rules/' . $existingRule->getId();

        $this->startTest();
    }

    public function testUpdateGatewayRuleLoadButWithTotalLoadExceedingMaxLoad()
    {
        $rule1 = $this->fixtures->create('gateway_rule:card');

        $rule2 = $existingRule = $this->fixtures->create('gateway_rule:card', [
            'network' => null,
            'load' => 50,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/rules/' . $rule2->getId();

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testDeleteGatewayRule()
    {
        $rule = $this->fixtures->create('gateway_rule:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/rules/' . $rule->getId();

        $this->ba->appAuth();

        $content = $this->startTest();
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
        $this->mockTokenex();

        $data = $this->testData[__FUNCTION__];

        $rules = $this->createRules($data['method'], $data['rules']);

        Options::setTestChance($data['test_chance']);

        $paymentData = $this->getPaymentArray($data['method']);

        $content = $this->doAuthAndCapturePayment($paymentData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($data['expected_terminal'], $payment['terminal_id']);
    }

    public function testInternationalAndDomesticPaymentsWithRules()
    {
        $this->fixtures->create('terminal:shared_hdfc_terminal', ['international' => true]);
        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal', ['international' => true]);
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->fixtures->merchant->enableInternational();
        $this->mockTokenex();

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
        $this->mockTokenex();
    }
}
