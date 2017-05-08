<?php

namespace RZP\Tests\Functional\Payment\TerminalLoadSorter;

use Carbon\Carbon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant;
use RZP\Models\Terminal\Options;

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

    public function testCreateGatewayRuleForWalletWithInvalidIssuer()
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
            'load' => 6000,
        ]);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreateGatewayRuleWithConflictingRulesButTotalLoadExceedsMaxLoad()
    {
        $existingRule = $this->fixtures->create('gateway_rule:card', [
            'network' => null,
            'load' => 6000,
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
            'load' => 5000,
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

    public function testTerminalSelectionWithRuleApplied()
    {
        $this->setUpTerminals();

        $rule = $this->fixtures->create('gateway_rule:card', [
            'gateway' => 'axis_migs',
            'method'  => 'card',
            'network' => 'VISA',
            'load'    => 7000
        ]);

        Options::setTestChance(5000);

        $this->payment = $this->getDefaultPaymentArray();

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1000AxisMigsTl', $payment['terminal_id']);
    }

    /**
     * Tests the case where a load rule is present but it does not satisfy the
     * payment criteria
     */
    public function testTerminalSelectionWithRulePresentButNotApplied()
    {
        $this->setUpTerminals();

        $rule = $this->fixtures->create('gateway_rule:card', [
            'gateway' => 'axis_migs',
            'method'  => 'card',
            'network' => 'MC',
            'load'    => 7000
        ]);

        Options::setTestChance(8000);

        $this->payment = $this->getDefaultPaymentArray();

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1000HdfcShared', $payment['terminal_id']);
    }

    /**
     * Tests the case where an applicable rule is present but the laod value does
     * not fall into the bucket as set by the chance value
     */
    public function testTerminalSelectionWithApplicableRulePresentButNotSelectedByChancePercent()
    {
        $this->setUpTerminals();

        $rule = $this->fixtures->create('gateway_rule:card', [
            'gateway' => 'axis_migs',
            'method'  => 'card',
            'network' => 'VISA',
            'load'    => 7000
        ]);

        Options::setTestChance(9000);

        $this->payment = $this->getDefaultPaymentArray();

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1000HdfcShared', $payment['terminal_id']);
    }

    /**
     * Tests the case where there are no merchant specific rules present, but a
     * shared rule is present. In this case we operate on the shared rule
     */
    public function testTerminalSelectionWithSharedRulePresentAndNoMerchantSpecificRules()
    {
        $this->setUpTerminals();

        $rule = $this->fixtures->create('gateway_rule:card', [
            'merchant_id' => Merchant\Account::SHARED_ACCOUNT,
            'gateway'     => 'axis_migs',
            'method'      => 'card',
            'network'     => 'VISA',
            'load'        => 7000
        ]);

        Options::setTestChance(5000);

        $this->payment = $this->getDefaultPaymentArray();

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1000AxisMigsTl', $payment['terminal_id']);
    }

    public function testTerminalSelectionWithMultipleApplicableRules()
    {
        $this->setUpTerminals();

        $this->fixtures->create('gateway_rule:card', [
            'gateway' => 'axis_migs',
            'method'  => 'card',
            'network' => 'VISA',
            'load'    => 7000
        ]);

        $this->fixtures->create('gateway_rule:card', [
            'gateway' => 'hdfc',
            'method'  => 'card',
            'network' => 'VISA',
            'load'    => 2000
        ]);

        Options::setTestChance(4000);

        $this->payment = $this->getDefaultPaymentArray();

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1000AxisMigsTl', $payment['terminal_id']);
    }

    public function testTerminalSelectionWithCardPaymentAllRuleDetailsPresent()
    {
        $this->setUpTerminals();

        $this->fixtures->create('gateway_rule:card', [
            'gateway'          => 'cybersource',
            'method_type'      => 'credit',
            'network'          => 'VISA',
            'issuer'           => 'HDFC',
            'gateway_acquirer' => 'hdfc',
            'load'             => 7000
        ]);

        Options::setTestChance(4000);

        $this->payment = $this->getDefaultPaymentArray();

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1000CybrsTrmnl', $payment['terminal_id']);
    }

    /**
     * Tests terminal selection for netbanking when a shared netbanking gateway
     * like billdesk is given precedence over a direct netbanking gateway as
     * per rule defined in the load sorter
     */
    public function testTerminalSelectionWithNetbankingPaymentWithRulePresentForNonDirectGateway()
    {
        $this->setUpTerminals();

        $this->fixtures->create('gateway_rule:netbanking', [
            'gateway' => 'paytm',
            'issuer'  => 'HDFC',
            'load'    => 7000
        ]);

        Options::setTestChance(4000);

        $this->payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1000PaytmTrmnl', $payment['terminal_id']);
    }

    public function testTerminalSelectionWithWalletPaymentWithRulePresentForWalletGateway()
    {
        $this->fixtures->merchant->enableMobikwik();
        $this->setUpTerminals();

        $this->fixtures->create('gateway_rule:wallet', [
            'gateway' => 'wallet_mobikwik',
            'issuer'  => 'mobikwik',
            'load'    => 7000
        ]);

        Options::setTestChance(4000);

        $this->payment = $this->getDefaultWalletPaymentArray();

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('1000MobiKwikTl', $payment['terminal_id']);

        $this->fixtures->merchant->disableMobikwik();
    }

    protected function setUpTerminals()
    {
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->mockTokenex();
    }
}
