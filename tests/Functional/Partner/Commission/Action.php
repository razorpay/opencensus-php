<?php

namespace Functional\Partner\Commission;

use RZP\Models\Partner\Commission\Calculator;

class Action
{
    /**
     * Gets triggered if the test's action function is not defined here explictly. Calculates commission.
     *
     * @param array $postSetupData
     * @param array $postActionData
     */
    public function defaultAction(array $postSetupData, array & $postActionData)
    {
        $calculator = new Calculator($postSetupData['source_entity']);

        self::invokePrivateMethod($calculator, Calculator::class, 'calculate');

        $postActionData['calculator'] = $calculator;
    }

    public function testInvalidSource(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testPartnerDoesNotExist(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testPartnerConfigDoesNotExist(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testPostpaidFeeModel(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function testCommissionDisabled(array $postSetupData, array & $postActionData)
    {
        $this->instantiateCalculator($postSetupData, $postActionData);
    }

    public function instantiateCalculator(array $postSetupData, array & $postActionData)
    {
        $calculator = new Calculator($postSetupData['source_entity']);

        $postActionData['calculator'] = $calculator;
    }

    /**
     * A wrapper to invoke the private or protected methods of a class
     *
     * @param       $classObj
     * @param       $className
     * @param       $methodName
     * @param array $args
     *
     * @return mixed
     */
    protected static function invokePrivateMethod($classObj, $className, $methodName, $args = [])
    {
        $privateMethod = self::getPrivateMethod($className, $methodName);

        return $privateMethod->invokeArgs($classObj, $args);
    }

    protected static function getPrivateMethod($class, $methodName)
    {
        $class = new \ReflectionClass($class);

        $method = $class->getMethod($methodName);

        $method->setAccessible(true);

        return $method;
    }
}
