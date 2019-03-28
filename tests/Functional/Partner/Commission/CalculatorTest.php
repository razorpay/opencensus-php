<?php

namespace RZP\Tests\Functional\Partner\Commission;

use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\Commission\Base;

class CalculatorTest extends OAuthTestCase
{
    use OAuthTrait;

    /**
     * @var Base\Engine
     */
    private $ruleEngine;

    public function setUp()
    {
        parent::setUp();

        include_once __DIR__ . '/Base/Engine.php';

        $this->ruleEngine = new Base\Engine($this->fixtures);

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->ba->privateAuth();
    }

    public function testImplicitVariable()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testImplicitFixed()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testImplicitFixedCommissionGreaterThanMerchantFees()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testImplicitFixedCommissionIsZero()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testInvalidSource()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testPartnerDoesNotExist()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testPartnerConfigDoesNotExist()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * Asserts that the commission doesn't get created if neither implicit nor explicit pricing are defined
     */
    public function testImplicitExplicitPricingDoesNotExist()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testPostpaidFeeModel()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testCommissionDisabled()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * Asserts that resellers get commission even when the payment is originated using the public auth
     */
    public function testPublicAuthPaymentForReseller()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * Asserts that aggregators do not get commission when the payment is originated using the public auth
     */
    public function testPublicAuthPaymentForAggregator()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * Fee calculation can have multiple rules - base rule + add on rule (like recurring payment pricing rule)
     * This test asserts that if multiple pricing rules have been added in the pricing plan for the merchant and the
     * partner, the commission calculation takes into account the following calculation -
     *
     * commission = sum(merchant fees from all rules) - sum(partner fees from all rules)
     */
    public function testImplicitVariableMultiplePricingRules()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * Asserts that the commission doesn't get created if implicit commission is expired and explicit is not defined.
     */
    public function testImplicitPricingExpiredNoExplicitDefined()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * Asserts that the commission gets created if implicit pricing is expired and but explicit pricing is defined.
     */
    public function testImplicitPricingExpiredExplicitExists()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * Asserts that the calculator does not calculate implicit variable commission if the pricing rule is missing.
     */
    public function testMissingPartnerPricingRule()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * Asserts that the commission gets created if implicit pricing is expired and but explicit pricing is defined.
     */
    public function testGSTOnCommissionForPaymentWithNoGST()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * Asserts that the implicit commission gets created if the merchant is on a customer fee bearer model
     */
    public function testImplicitCustomerFeeBearer()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }
}
