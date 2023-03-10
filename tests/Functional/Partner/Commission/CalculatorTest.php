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

    protected function setUp(): void
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

    public function testImplicitVariableWithMYRCurrency()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testImplicitFixed()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testImplicitForPartnerWithNoGstin()
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

    public function testFullyManagedPartnerTypeCommission()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testPartnerConfigDoesNotExist()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testPartnerAsSubmerchantCommission()
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

    public function testImplicitVariableWithSubmerchantPartnerDiffPricingRules()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testImplicitVariableWithSubmerchantPartnerESPricingRules()
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

    public function testZeroPartnerPricingRule()
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
     * checks that explicit commission is calculated correctly
     */
    public function testExplicit()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * checks that explicit commission is calculated correctly with MYR Currency
     */
    public function testExplicitWithMYRCurrency()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * checks that explicit commission is calculated correctly with USD Currency
     */
    public function testExplicitWithUSDCurrency()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }


    /**
     * checks that commission is created for record-only model
     */
    public function testExplicitRecordOnly()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * checks that gst is being charged for add on commission irrespective of base_amount < 2k
     */
    public function testGSTOnExplicit()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * checks that explicit commission is calculated correctly for fixed fee instead of percentage commission plan
     */
    public function testExplicitFixedFeesType()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * checks that both implicit and explicit commissions are created
     * if both implicit variable and explicit plans are present
     */
    public function testImplicitVariableAndExplicit()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testImplicitVariableAndExplicitForReferredApp()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * checks that both implicit and explicit commissions are created
     * if both implicit fixed and explicit plans are present
     */
    public function testImplicitFixedAndExplicit()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * checks that commissions are not created if total commission greater than base amount
     */
    public function testImplicitAndExplicitGreaterThanAmount()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * checks that explicit commission is calculated correctly for recurring payments
     */
    public function testExplicitWithAddOnPricingRules()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * Asserts that the implicit commission gets created if the merchant is on a customer fee bearer model
     * @throws \Exception
     */
    public function testImplicitCustomerFeeBearer()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testImplicitFixedCustomerFeeBearer()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testImplicitVariablePostpaid()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testImplicitFixedPostpaid()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testExplicitPostpaid()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testImplicitFixedAndExplicitPostpaid()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * Asserts that the explicit commission gets created if the merchant is on a customer fee bearer model
     */
    public function testExplicitCustomerFeeBearer()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * Asserts that both explicit and implicit commission gets created
     * if the merchant is on a customer fee bearer model
     */
    public function testImplicitAndExplicitCustomerFeeBearer()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * Asserts that both explicit and implicit commission gets created
     * if the merchant is on a customer fee bearer model and sum is greater than payment amount
     */
    public function testImplicitAndExplicitGreaterThanAmountCustomerFeeBearer()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    /**
     * checks that commission is created for record-only model
     */
    public function testExplicitRecordOnlyCustomerFeeBearer()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }

    public function testNoCommissionOnDetachedMerchant()
    {
        $this->ruleEngine->execute(__FUNCTION__);
    }
}
