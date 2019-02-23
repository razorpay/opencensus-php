<?php

namespace RZP\Tests\Functional\Helpers;

trait PrivateMethodTrait
{
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

    /**
     * @param $class
     * @param $methodName
     *
     * @return \ReflectionMethod
     */
    protected static function getPrivateMethod($class, $methodName)
    {
        $class = new \ReflectionClass($class);

        $method = $class->getMethod($methodName);

        $method->setAccessible(true);

        return $method;
    }
}
