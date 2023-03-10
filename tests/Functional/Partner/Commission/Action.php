<?php

namespace RZP\Tests\Functional\Partner\Commission;

use RZP\Models\Partner\Commission\Calculator;
use RZP\Tests\Functional\Helpers\PrivateMethodTrait;

class Action
{
    use PrivateMethodTrait;

    /**
     * Gets triggered if the test's action function is not defined here explictly. Calculates commission.
     *
     * @param array $postSetupData
     * @param array $postActionData
     */
    public function defaultAction(array $postSetupData, array & $postActionData)
    {
        $calculator = new Calculator($postSetupData['source_entity']);

        $this->invokePrivateMethod($calculator, Calculator::class, 'calculate');

        $postActionData['calculator'] = $calculator;
    }

    public function testInvalidSource(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testExplicitWithUSDCurrency(array $postSetupData, array & $postActionData)
    {
        $calculator = new Calculator($postSetupData['source_entity']);

        $this->invokePrivateMethod($calculator, Calculator::class, 'calculate');

        $postActionData['calculator'] = $calculator;
    }

    public function testPartnerDoesNotExist(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testFullyManagedPartnerTypeCommission(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testImplicitExplicitPricingDoesNotExist(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testPartnerAsSubmerchantCommission(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testPartnerConfigDoesNotExist(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testCommissionDisabled(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testImplicitPricingExpiredNoExplicitDefined(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testImplicitPricingExpiredExplicitExists(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testPublicAuthPaymentForReseller(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testPublicAuthPaymentForAggregator(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testMissingPartnerPricingRule(array $postSetupData, array & $postActionData)
    {
        $calculator = new Calculator($postSetupData['source_entity']);

        $this->invokePrivateMethod($calculator, Calculator::class, 'calculate');

        $postActionData['calculator'] = $calculator;
    }

    public function instantiateCalculator(array $postSetupData, array & $postActionData)
    {
        $calculator = new Calculator($postSetupData['source_entity']);

        $postActionData['calculator'] = $calculator;
    }
}
