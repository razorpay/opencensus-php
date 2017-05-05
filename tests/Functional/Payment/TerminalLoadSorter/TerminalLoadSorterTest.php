<?php

namespace RZP\Tests\Functional\Payment\TerminalLoadSorter;

use Carbon\Carbon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Terminal\Options;

class TerminalLoadSorterTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/TerminalLoadSorterTestData.php';

        parent::setUp();
    }

    public function testCreateGatewayLoadRuleForCard()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleForCardWithInvalidGateway()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleForCardWithInvalidCardGateway()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleWithInvalidMethod()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleForCardWithInvalidNetwork()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleForCardWithInvalidNetworkForGateway()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleForCardWithInvalidIssuer()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleForCardWithInvalidCardType()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleForCardWithInvalidGatewayAcquirer()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleForNetbanking()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleForNetbankingWithInvalidGatewayForNetbanking()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleForNetbankingWithDirectNetbankingGatewayAndNullIssuer()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleForNetbankingWithIssuerNotSupportedByGateway()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleForWallet()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleForWalletWithInvalidIssuer()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleWithAlreadyExistingRule()
    {
        $existingRule = $this->fixtures->create('gateway_load_rule:card');

        $this->ba->appAuth();

        $this->startTest();
    }

    /**
     * Tests the case when there is a load existing and new load input potentially
     * conflictes with the new load input but the cumulate load is <= 100 %
     */
    public function testCreateGatewayLoadRuleWithConflictingRulesButTotalLoadNotExceedingMaxLoad()
    {
        $existingRule = $this->fixtures->create('gateway_load_rule:card', [
            'network' => null,
            'load' => 6000,
        ]);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayLoadRuleWithConflictingRulesButTotalLoadExceedsMaxLoad()
    {
        $existingRule = $this->fixtures->create('gateway_load_rule:card', [
            'network' => null,
            'load' => 6000,
        ]);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testUpdadateGatewayLoadRuleLoad()
    {
        $existingRule = $this->fixtures->create('gateway_load_rule:card');

        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/load_rules/' . $existingRule->getId();

        $this->startTest();
    }

    public function testUpdateGatewayLoadRuleLoadButWithTotalLoadExceedingMaxLoad()
    {
        $rule1 = $this->fixtures->create('gateway_load_rule:card');

        $rule2 = $existingRule = $this->fixtures->create('gateway_load_rule:card', [
            'network' => null,
            'load' => 5000,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/load_rules/' . $rule2->getId();

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testDeleteGatewayLoadRule()
    {
        $rule = $this->fixtures->create('gateway_load_rule:card');

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/load_rules/' . $rule->getId();

        $this->ba->appAuth();

        $this->startTest();
    }
}
